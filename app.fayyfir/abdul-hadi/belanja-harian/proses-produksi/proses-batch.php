<?php
session_start();
require "../../config.php";
$conn = get_conn2();

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$kode_produksi = $_POST['kode_produksi'] ?? '';
$id_pembelian_fallback = (int)$_POST['id_pembelian']; // Used if kode_produksi is empty
$redirect_to = $_POST['redirect_to'] ?? 'index.php';
$next_stage = (int)$_POST['next_stage'];
$total_raw = str_replace('.', '', $_POST['berat_keluar']);
$total_berat_keluar = (float)$total_raw;
$tanggal_proses = $_POST['tanggal_proses'] ?: date('Y-m-d');
$catatan = $_POST['catatan'];

$conn->begin_transaction();

try {
    // 1. Ambil list item yang ada di tahap SEBELUMNYA untuk batch ini
    if ($kode_produksi) {
        $sqlItems = "
            SELECT pd.*, COALESCE(pm.urutan_tahap, 0) as urutan_tahap 
            FROM bb_proses_detail pd
            LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
            WHERE pd.kode_produksi = ? 
            AND COALESCE(pm.urutan_tahap, 0) = (
                SELECT COALESCE(MAX(pm2.urutan_tahap), 0) 
                FROM bb_proses_detail pd2 
                LEFT JOIN bb_proses_master pm2 ON pm2.id = pd2.id_proses_master
                WHERE pd2.kode_produksi = ? AND pd2.status = 'aktif'
            ) AND pd.status = 'aktif'
        ";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("ss", $kode_produksi, $kode_produksi);
    } else {
        $sqlItems = "
            SELECT pd.*, COALESCE(pm.urutan_tahap, 0) as urutan_tahap 
            FROM bb_proses_detail pd
            LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
            WHERE pd.id_pembelian = ? 
            AND COALESCE(pm.urutan_tahap, 0) = (
                SELECT COALESCE(MAX(pm2.urutan_tahap), 0) 
                FROM bb_proses_detail pd2 
                LEFT JOIN bb_proses_master pm2 ON pm2.id = pd2.id_proses_master
                WHERE pd2.id_pembelian = ? AND pd2.status = 'aktif'
            ) AND pd.status = 'aktif'
        ";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("ii", $id_pembelian_fallback, $id_pembelian_fallback);
    }

    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($items)) {
        throw new Exception("Tidak ada item ditemukan untuk diproses.");
    }

    $total_berat_masuk_batch = 0;
    foreach ($items as $item) {
        $total_berat_masuk_batch += (float)$item['berat_keluar'];
    }

    if ($total_berat_masuk_batch <= 0) {
        throw new Exception("Total berat masuk batch 0.");
    }

    // 2. Ambil id_proses_master untuk NEXT STAGE
    // Kita ambil id_bahan dari salah satu item
    $sample_pa = $conn->query("SELECT id_bahan FROM bb_pembelian_awal WHERE id = " . $items[0]['id_pembelian'])->fetch_assoc();
    $id_bahan = $sample_pa['id_bahan'];

    $sqlMaster = "SELECT id FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ?";
    $stmtMaster = $conn->prepare($sqlMaster);
    $stmtMaster->bind_param("ii", $id_bahan, $next_stage);
    $stmtMaster->execute();
    $id_proses_master = $stmtMaster->get_result()->fetch_assoc()['id'];

    if (!$id_proses_master) {
        throw new Exception("Master tahap $next_stage tidak ditemukan.");
    }

    // 3. Insert ke bb_proses_detail (Proportional distribution)
    $metode_produksi = $items[0]['metode_produksi'] ?? 'tertimbang';
    $status_batch    = $items[0]['status_batch'] ?? 'berjalan';
    $hpp_temporary   = $items[0]['hpp_temporary'] ?? null;

    // Check if next_stage is the final stage for this material
    $resMax = $conn->query("SELECT MAX(urutan_tahap) as max_u FROM bb_proses_master WHERE id_bahan = " . (int)$id_bahan);
    $max_stage_urutan = (int)($resMax->fetch_assoc()['max_u'] ?? 0);
    $is_final_stage = ($max_stage_urutan > 0 && $next_stage == $max_stage_urutan);

    // For weighted mode (tertimbang), if final stage is reached, automatically close the batch
    $auto_close_weighted = ($is_final_stage && $metode_produksi === 'tertimbang');
    if ($auto_close_weighted) {
        $status_batch = 'closed';
    }

    foreach ($items as $item) {
        $berat_masuk    = (float)$item['berat_keluar'];
        $berat_keluar   = ($berat_masuk / $total_berat_masuk_batch) * $total_berat_keluar;
        $id_pembelian   = $item['id_pembelian'];
        $id_penampungan = $item['id_penampungan'] ?? null;

        $sqlInsert = "INSERT INTO bb_proses_detail (kode_produksi, id_pembelian, id_penampungan, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan, status, metode_produksi, status_batch, hpp_temporary) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("siiiisddsssd", 
            $kode_produksi, $id_pembelian, $id_penampungan, 
            $id_proses_master, $next_stage, $tanggal_proses, 
            $berat_masuk, $berat_keluar, $catatan, 
            $metode_produksi, $status_batch, $hpp_temporary
        );
        $stmtInsert->execute();

        // Update status pembelian
        if ($auto_close_weighted) {
            // Hanya set selesai_siap_jual jika sisa stok mentah sudah benar-benar habis (<= 0)
            // Stok sisa = berat_awal - terpakai_produksi_tahap0 - terpakai_penampungan
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
                WHERE pa.id = $id_pembelian
                AND (pa.berat_awal - COALESCE(pd_agg.terpakai_prod, 0) - COALESCE(pnd_agg.terpakai_penampungan, 0)) <= 0
            ");
        } else {
            $new_status = "tahap" . $next_stage;
            $conn->query("UPDATE bb_pembelian_awal SET status = '$new_status' WHERE id = $id_pembelian");
        }
    }

    if ($auto_close_weighted) {
        // Calculate HPP Final & Penyusutan Final for the auto-closed weighted batch
        if ($kode_produksi) {
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

            $penyusutan_final = max(0, round($total_raw_intake - $total_berat_keluar, 2));
            $hpp_final = ($total_berat_keluar > 0) ? round($total_raw_cost / $total_berat_keluar, 2) : 0;

            $stmtUpd = $conn->prepare("UPDATE bb_proses_detail SET status_batch = 'closed', hpp_final = ?, penyusutan_final = ?, closed_at = NOW() WHERE kode_produksi = ? AND status = 'aktif'");
            $stmtUpd->bind_param("dds", $hpp_final, $penyusutan_final, $kode_produksi);
            $stmtUpd->execute();
        } else {
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
                WHERE pd.id_pembelian = ? AND pd.tahap_ke = 0 AND pd.status = 'aktif'
            ";
            $stmtRaw = $conn->prepare($sqlRaw);
            $stmtRaw->bind_param("i", $id_pembelian_fallback);
            $stmtRaw->execute();
            $rowRaw = $stmtRaw->get_result()->fetch_assoc();
            $total_raw_intake = (float)($rowRaw['total_raw_intake'] ?? 0);
            $total_raw_cost   = (float)($rowRaw['total_raw_cost'] ?? 0);

            $penyusutan_final = max(0, round($total_raw_intake - $total_berat_keluar, 2));
            $hpp_final = ($total_berat_keluar > 0) ? round($total_raw_cost / $total_berat_keluar, 2) : 0;

            $stmtUpd = $conn->prepare("UPDATE bb_proses_detail SET status_batch = 'closed', hpp_final = ?, penyusutan_final = ?, closed_at = NOW() WHERE id_pembelian = ? AND status = 'aktif'");
            $stmtUpd->bind_param("ddi", $hpp_final, $penyusutan_final, $id_pembelian_fallback);
            $stmtUpd->execute();
        }
    }

    $conn->commit();
    $_SESSION['success'] = "Berhasil memproses ke tahap $next_stage" . ($auto_close_weighted ? " (Tahap Akhir — Produksi Selesai)." : ".");

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: " . $redirect_to);
exit();
