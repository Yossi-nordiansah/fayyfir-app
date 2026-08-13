<?php
session_start();
require "../../config.php";
$conn = get_conn2(); // Lazy loader — tidak buka koneksi ganda

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$id_bahan = (int)$_POST['id_bahan'];
$tanggal_proses = $_POST['tanggal_proses'];
$stok_method = $_POST['stok_method'];
$active_pembelian = [];

// Kedua mode (all/specific) kini sama-sama mengirim supplier_ids[] + supplier_qty[]
// 'all'      = ambil dari penampungan gabungan (id prefix 'p')
// 'specific' = ambil dari supplier mandiri (id prefix 's')
$supplier_ids   = $_POST['supplier_ids']  ?? [];
$supplier_qtys  = $_POST['supplier_qty']  ?? [];
$metode_produksi = $_POST['metode_produksi'] ?? 'tertimbang';
if (!in_array($metode_produksi, ['tertimbang', 'belum_tertimbang'])) {
    $metode_produksi = 'tertimbang';
}

foreach ($supplier_ids as $index => $sid) {
    if (empty($sid)) continue;

    $raw_val = $supplier_qtys[$index] ?? '0';
    $needed = 0.0;
    if ($metode_produksi === 'belum_tertimbang' || $raw_val === '(jumlah tidak diketahui)') {
        $needed = 999999999.0;
    } else {
        $qty_raw = str_replace('.', '', $raw_val);
        $needed  = (float)$qty_raw;
    }

    if ($needed <= 0 && $metode_produksi !== 'belum_tertimbang') {
        continue;
    }

    $is_penampungan = (strpos((string)$sid, 'p') === 0);
    $actual_id      = (int)substr((string)$sid, 1);

    if ($is_penampungan) {
        // FIFO dari penampungan gabungan
        $query = "
            SELECT pnd.id_pembelian, pnd.berat_masuk,
                IFNULL(pd_agg.total_terpakai, 0) as terpakai
            FROM bb_penampungan_detail pnd
            JOIN bb_pembelian_awal pa ON pa.id = pnd.id_pembelian
            LEFT JOIN (
                SELECT id_pembelian, id_penampungan, SUM(berat_masuk) as total_terpakai
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND status != 'batal'
                GROUP BY id_pembelian, id_penampungan
            ) pd_agg ON pd_agg.id_pembelian = pnd.id_pembelian AND pd_agg.id_penampungan = pnd.id_penampungan
            WHERE pnd.id_penampungan = ? AND pa.status != 'selesai_siap_jual'
            ORDER BY pnd.created_at ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $available = max(0, (float)$row['berat_masuk'] - (float)$row['terpakai']);
            $take = ($metode_produksi === 'belum_tertimbang') ? (($available > 0) ? $available : (float)$row['berat_masuk']) : min($available, $needed);
            if ($take <= 0 && $metode_produksi === 'belum_tertimbang') {
                $take = (float)$row['berat_masuk'];
            }
            $active_pembelian[] = [
                'id_pembelian'   => $row['id_pembelian'],
                'id_penampungan' => $actual_id,
                'qty'            => $take
            ];
            $needed -= $take;
            if ($needed <= 0) break;
        }
    } else {
        // FIFO dari supplier mandiri
        $query = "
            SELECT pa.id, pa.berat_awal,
                IFNULL(pd_agg.terpakai_prod, 0) as terpakai_prod,
                IFNULL(pnd_agg.terpakai_penampungan, 0) as terpakai_penampungan
            FROM bb_pembelian_awal pa
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) as terpakai_prod
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND status != 'batal'
                GROUP BY id_pembelian
            ) pd_agg ON pd_agg.id_pembelian = pa.id
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) as terpakai_penampungan
                FROM bb_penampungan_detail
                GROUP BY id_pembelian
            ) pnd_agg ON pnd_agg.id_pembelian = pa.id
            WHERE pa.id_bahan = ? AND pa.id_supplier = ? AND pa.status != 'selesai_siap_jual'
            ORDER BY pa.tanggal_pembelian ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id_bahan, $actual_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $available = max(0, (float)$row['berat_awal'] - (float)$row['terpakai_prod'] - (float)$row['terpakai_penampungan']);
            $take = ($metode_produksi === 'belum_tertimbang') ? (($available > 0) ? $available : (float)$row['berat_awal']) : min($available, $needed);
            if ($take <= 0 && $metode_produksi === 'belum_tertimbang') {
                $take = (float)$row['berat_awal'];
            }
            $active_pembelian[] = [
                'id_pembelian'   => $row['id'],
                'id_penampungan' => null,
                'qty'            => $take
            ];
            $needed -= $take;
            if ($needed <= 0) break;
        }
    }
}

$catatan       = $_POST['catatan'] ?? '';
$final_catatan = $catatan;

if (empty($active_pembelian)) {
    die("Error: Tidak ada bahan yang dipilih atau stok tidak cukup.");
}

$prefix   = "PRD-" . date('Ymd', strtotime($tanggal_proses)) . "-";
$resCount = $conn->query("SELECT COUNT(DISTINCT kode_produksi) as total FROM bb_proses_detail WHERE kode_produksi LIKE '$prefix%'");
$nextNum  = ($resCount->fetch_assoc()['total'] ?? 0) + 1;
$kode_produksi = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

$metode_produksi = $_POST['metode_produksi'] ?? 'tertimbang';
if (!in_array($metode_produksi, ['tertimbang', 'belum_tertimbang'])) {
    $metode_produksi = 'tertimbang';
}

$conn->begin_transaction();
try {
    foreach ($active_pembelian as $data) {
        $id_pembelian   = $data['id_pembelian'];
        $id_penampungan = $data['id_penampungan'];
        $berat_masuk    = $data['qty'];
        $berat_keluar   = $berat_masuk;

        // Ambil harga per kg sebagai HPP sementara awal
        $resPrice = $conn->query("SELECT harga_per_kg FROM bb_pembelian_awal WHERE id = $id_pembelian");
        $harga_per_kg = 0.0;
        if ($resPrice && $rP = $resPrice->fetch_assoc()) {
            $harga_per_kg = (float)$rP['harga_per_kg'];
        }

        $sqlInsert = "INSERT INTO bb_proses_detail 
                        (kode_produksi, id_pembelian, id_penampungan, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan, status, metode_produksi, status_batch, hpp_temporary) 
                      VALUES (?, ?, ?, NULL, 0, ?, ?, ?, ?, 'aktif', ?, 'berjalan', ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert) {
            throw new Exception("Gagal prepare query insert: " . $conn->error);
        }
        $stmtInsert->bind_param("siisddssd",
            $kode_produksi, $id_pembelian, $id_penampungan,
            $tanggal_proses, $berat_masuk, $berat_keluar, $final_catatan,
            $metode_produksi, $harga_per_kg
        );
        if (!$stmtInsert->execute()) {
            throw new Exception("Gagal menyimpan detail produksi: " . $stmtInsert->error);
        }

        $conn->query("UPDATE bb_pembelian_awal SET status = 'proses' WHERE id = $id_pembelian AND status IN ('load', 'uang_terbayar')");
    }
    $conn->commit();
    $_SESSION['success'] = "Produksi $kode_produksi (" . ($metode_produksi === 'belum_tertimbang' ? 'Belum Tertimbang' : 'Tertimbang') . ") berhasil didaftarkan.";
} catch (Exception $e) {
    $conn->rollback();
    die("Error saat menyimpan produksi: " . $e->getMessage());
}

header("Location: index.php");
exit();
