<?php
session_start();
require "../../config.php";
$conn = get_conn2();

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$kode_produksi = trim($_POST['kode_produksi'] ?? '');
$id_pembelian_fallback = (int)($_POST['id_pembelian'] ?? 0);
$catatan_closing = trim($_POST['catatan_opname'] ?? '');
$redirect_to = $_POST['redirect_to'] ?? 'index.php';

if (empty($kode_produksi) && $id_pembelian_fallback <= 0) {
    die("Error: Kode produksi tidak ditemukan.");
}

$conn->begin_transaction();
try {
    // Determine target id_pembelian
    $target_id_pembelian = $id_pembelian_fallback;
    if (!empty($kode_produksi) && $target_id_pembelian <= 0) {
        $resPaId = $conn->query("SELECT DISTINCT id_pembelian FROM bb_proses_detail WHERE kode_produksi = '" . $conn->real_escape_string($kode_produksi) . "' LIMIT 1");
        if ($resPaId && $r = $resPaId->fetch_assoc()) {
            $target_id_pembelian = (int)$r['id_pembelian'];
        }
    }

    // Check if group level closing or single batch closing
    // is_group_closing hanya TRUE untuk metode belum_tertimbang
    $is_group_closing = false;
    if ($target_id_pembelian > 0) {
        $resMethod = $conn->query("SELECT metode_produksi FROM bb_proses_detail WHERE id_pembelian = $target_id_pembelian LIMIT 1");
        if ($resMethod && $rM = $resMethod->fetch_assoc()) {
            if ($rM['metode_produksi'] === 'belum_tertimbang') {
                $is_group_closing = true;
            }
        }
    }

    if ($is_group_closing && $target_id_pembelian > 0) {
        // ===== CLOSING UNTUK KELOMPOK BELUM TERTIMBANG =====
        $id_penampungan_target = 0;
        $resPen = $conn->query("SELECT id_penampungan FROM bb_penampungan_detail WHERE id_pembelian = $target_id_pembelian LIMIT 1");
        if ($resPen && $rP = $resPen->fetch_assoc()) {
            $id_penampungan_target = (int)$rP['id_penampungan'];
        }

        if ($id_penampungan_target > 0) {
            $resPa = $conn->query("
                SELECT SUM(pa.berat_awal) as total_berat_awal, SUM(pa.berat_awal * pa.harga_per_kg) as total_raw_cost 
                FROM bb_pembelian_awal pa 
                JOIN bb_penampungan_detail pnd ON pnd.id_pembelian = pa.id 
                WHERE pnd.id_penampungan = $id_penampungan_target
            ");
            $rowPa = $resPa ? $resPa->fetch_assoc() : null;
            $total_raw_intake = (float)($rowPa['total_berat_awal'] ?? 0);
            $total_raw_cost   = (float)($rowPa['total_raw_cost'] ?? 0);

            if ($total_raw_intake <= 0) {
                throw new Exception("Data input mentah awal penampungan tidak ditemukan.");
            }

            $sqlOutGroup = "
                SELECT SUM(pd.berat_keluar) as total_final_output
                FROM bb_proses_detail pd
                JOIN (
                    SELECT COALESCE(kode_produksi, CONCAT('SINGLE-', id_pembelian)) as bk, MAX(tahap_ke) as max_t
                    FROM bb_proses_detail
                    WHERE id_penampungan = $id_penampungan_target AND status = 'aktif'
                    GROUP BY bk
                ) last_t ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_t.bk AND pd.tahap_ke = last_t.max_t
                WHERE pd.id_penampungan = $id_penampungan_target AND pd.status = 'aktif' AND pd.tahap_ke > 0
            ";
            $resOutG = $conn->query($sqlOutGroup);
            $total_final_output = (float)($resOutG ? $resOutG->fetch_assoc()['total_final_output'] : 0);

            $penyusutan_final = max(0, round($total_raw_intake - $total_final_output, 2));
            $hpp_final = ($total_final_output > 0) ? round($total_raw_cost / $total_final_output, 2) : 0;

            $stmtUpdG = $conn->prepare("
                UPDATE bb_proses_detail 
                SET status_batch = 'closed', 
                    hpp_final = ?, 
                    penyusutan_final = ?, 
                    closed_at = NOW()
                WHERE id_penampungan = ? AND status = 'aktif'
            ");
            $stmtUpdG->bind_param("ddi", $hpp_final, $penyusutan_final, $id_penampungan_target);
            $stmtUpdG->execute();

            // Update status selesai_siap_jual: hanya jika sisa stok penampungan sudah habis sepenuhnya
            // (seluruh berat_masuk dari penampungan sudah habis terpakai produksi)
            $conn->query("UPDATE bb_pembelian_awal pa JOIN bb_penampungan_detail pnd ON pnd.id_pembelian = pa.id SET pa.status = 'selesai_siap_jual' WHERE pnd.id_penampungan = $id_penampungan_target");
        } else {
            $resPa = $conn->query("SELECT berat_awal, harga_per_kg FROM bb_pembelian_awal WHERE id = $target_id_pembelian");
            $rowPa = $resPa ? $resPa->fetch_assoc() : null;
            $total_raw_intake = (float)($rowPa['berat_awal'] ?? 0);
            $harga_per_kg     = (float)($rowPa['harga_per_kg'] ?? 0);
            $total_raw_cost   = $total_raw_intake * $harga_per_kg;

            if ($total_raw_intake <= 0) {
                throw new Exception("Data input mentah awal pembelian tidak ditemukan.");
            }

            // Sum output dari tahap TERAKHIR setiap sub-batch aktif di kelompok ini
            $sqlOutGroup = "
                SELECT SUM(pd.berat_keluar) as total_final_output
                FROM bb_proses_detail pd
                JOIN (
                    SELECT COALESCE(kode_produksi, CONCAT('SINGLE-', id_pembelian)) as bk, MAX(tahap_ke) as max_t
                    FROM bb_proses_detail
                    WHERE id_pembelian = ? AND status = 'aktif'
                    GROUP BY bk
                ) last_t ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_t.bk AND pd.tahap_ke = last_t.max_t
                WHERE pd.id_pembelian = ? AND pd.status = 'aktif' AND pd.tahap_ke > 0
            ";
            $stmtOutG = $conn->prepare($sqlOutGroup);
            $stmtOutG->bind_param("ii", $target_id_pembelian, $target_id_pembelian);
            $stmtOutG->execute();
            $total_final_output = (float)($stmtOutG->get_result()->fetch_assoc()['total_final_output'] ?? 0);

            $penyusutan_final = max(0, round($total_raw_intake - $total_final_output, 2));
            $hpp_final = ($total_final_output > 0) ? round($total_raw_cost / $total_final_output, 2) : 0;

            // Update seluruh riwayat sub-batch di kelompok id_pembelian ini
            $stmtUpdG = $conn->prepare("
                UPDATE bb_proses_detail 
                SET status_batch = 'closed', 
                    hpp_final = ?, 
                    penyusutan_final = ?, 
                    closed_at = NOW()
                WHERE id_pembelian = ? AND status = 'aktif'
            ");
            $stmtUpdG->bind_param("ddi", $hpp_final, $penyusutan_final, $target_id_pembelian);
            $stmtUpdG->execute();

            // Hanya selesai_siap_jual jika sisa stok benar-benar habis (berat_awal - total terpakai <= 0)
            $conn->query("
                UPDATE bb_pembelian_awal pa
                LEFT JOIN (
                    SELECT id_pembelian, SUM(berat_masuk) AS terpakai_prod
                    FROM bb_proses_detail
                    WHERE tahap_ke = 0 AND id_penampungan IS NULL AND status != 'batal'
                    GROUP BY id_pembelian
                ) pd_agg ON pd_agg.id_pembelian = pa.id
                LEFT JOIN (
                    SELECT id_pembelian, SUM(berat_masuk) AS terpakai_penampungan
                    FROM bb_penampungan_detail
                    GROUP BY id_pembelian
                ) pnd_agg ON pnd_agg.id_pembelian = pa.id
                SET pa.status = 'selesai_siap_jual'
                WHERE pa.id = $target_id_pembelian
                AND (pa.berat_awal - COALESCE(pd_agg.terpakai_prod, 0) - COALESCE(pnd_agg.terpakai_penampungan, 0)) <= 0
            ");
        }
    } else {
        // ===== CLOSING UNTUK SINGLE BATCH TERTIMBANG =====
        $sqlRaw = "
            SELECT 
                SUM(pd.berat_masuk) as total_raw_intake,
                SUM(pd.berat_masuk * COALESCE(wac.wac_harga, pa.harga_per_kg)) as total_raw_cost
            FROM bb_proses_detail pd
            JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
            LEFT JOIN (
                SELECT pnd.id_penampungan, SUM(pnd.berat_masuk * pa2.harga_per_kg) / SUM(pnd.berat_masuk) as wac_harga
                FROM bb_penampungan_detail pnd
                JOIN bb_pembelian_awal pa2 ON pa2.id = pnd.id_pembelian
                GROUP BY pnd.id_penampungan
            ) wac ON wac.id_penampungan = pd.id_penampungan
            WHERE pd.kode_produksi = ? AND pd.tahap_ke = 0 AND pd.status = 'aktif'
        ";
        $stmtRaw = $conn->prepare($sqlRaw);
        $stmtRaw->bind_param("s", $kode_produksi);
        $stmtRaw->execute();
        $rowRaw = $stmtRaw->get_result()->fetch_assoc();
        $total_raw_intake = (float)($rowRaw['total_raw_intake'] ?? 0);
        $total_raw_cost   = (float)($rowRaw['total_raw_cost'] ?? 0);

        if ($total_raw_intake <= 0) {
            throw new Exception("Data input mentah awal tidak ditemukan.");
        }

        $sqlMaxStage = "SELECT MAX(tahap_ke) as max_stage FROM bb_proses_detail WHERE kode_produksi = ? AND status = 'aktif'";
        $stmtMax = $conn->prepare($sqlMaxStage);
        $stmtMax->bind_param("s", $kode_produksi);
        $stmtMax->execute();
        $max_stage = (int)($stmtMax->get_result()->fetch_assoc()['max_stage'] ?? 0);

        $sqlOut = "SELECT SUM(berat_keluar) as total_final_output FROM bb_proses_detail WHERE kode_produksi = ? AND tahap_ke = ? AND status = 'aktif'";
        $stmtOut = $conn->prepare($sqlOut);
        $stmtOut->bind_param("si", $kode_produksi, $max_stage);
        $stmtOut->execute();
        $total_final_output = (float)($stmtOut->get_result()->fetch_assoc()['total_final_output'] ?? 0);

        $penyusutan_final = max(0, round($total_raw_intake - $total_final_output, 2));
        $hpp_final = ($total_final_output > 0) ? round($total_raw_cost / $total_final_output, 2) : 0;

        $sqlUpdate = "
            UPDATE bb_proses_detail 
            SET status_batch = 'closed', 
                hpp_final = ?, 
                penyusutan_final = ?, 
                closed_at = NOW()
            WHERE kode_produksi = ? AND status = 'aktif'
        ";
        $stmtUpd = $conn->prepare($sqlUpdate);
        $stmtUpd->bind_param("dds", $hpp_final, $penyusutan_final, $kode_produksi);
        $stmtUpd->execute();

        $conn->query("
            UPDATE bb_pembelian_awal pa
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) AS terpakai_prod
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND id_penampungan IS NULL AND status != 'batal'
                GROUP BY id_pembelian
            ) pd_agg ON pd_agg.id_pembelian = pa.id
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) AS terpakai_penampungan
                FROM bb_penampungan_detail
                GROUP BY id_pembelian
            ) pnd_agg ON pnd_agg.id_pembelian = pa.id
            SET pa.status = 'selesai_siap_jual'
            WHERE pa.id IN (
                SELECT DISTINCT id_pembelian FROM bb_proses_detail WHERE kode_produksi = '$kode_produksi'
            )
            AND (pa.berat_awal - COALESCE(pd_agg.terpakai_prod, 0) - COALESCE(pnd_agg.terpakai_penampungan, 0)) <= 0
        ");
    }

    $conn->commit();

    $pct_susut = ($total_raw_intake > 0) ? round(($penyusutan_final / $total_raw_intake) * 100, 1) : 0;
    $batch_label = $kode_produksi ?: ("ID #" . $id_pembelian_fallback);
    $_SESSION['success'] = "✅ Closing Batch $batch_label Berhasil! HPP Final Revaluasi: Rp " . number_format($hpp_final, 0, ',', '.') . "/Kg (Total Susut Aktual: " . number_format($penyusutan_final, 0, ',', '.') . " Kg / $pct_susut%).";
    $_SESSION['closing_detail_url'] = "detail-penyusutan.php?id=" . $id_pembelian_fallback . ($kode_produksi ? "&kode_produksi=" . urlencode($kode_produksi) : "");

} catch (Exception $e) {
    $conn->rollback();
    die("Error saat closing batch: " . $e->getMessage());
}

header("Location: " . $redirect_to);
exit();
