<?php
session_start();
require "../../config.php";
$conn = $conn2;

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
$supplier_ids  = $_POST['supplier_ids']  ?? [];
$supplier_qtys = $_POST['supplier_qty']  ?? [];

foreach ($supplier_ids as $index => $sid) {
    $qty_raw = str_replace('.', '', $supplier_qtys[$index] ?? '0');
    $needed  = (float)$qty_raw;
    if ($needed <= 0) continue;

    $is_penampungan = (strpos((string)$sid, 'p') === 0);
    $actual_id      = (int)substr((string)$sid, 1);

    if ($is_penampungan) {
        // FIFO dari penampungan gabungan
        $query = "
            SELECT pnd.id_pembelian, pnd.berat_masuk,
                IFNULL(pd_agg.total_terpakai, 0) as terpakai
            FROM bb_penampungan_detail pnd
            LEFT JOIN (
                SELECT id_pembelian, id_penampungan, SUM(berat_masuk) as total_terpakai
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND status = 'aktif'
                GROUP BY id_pembelian, id_penampungan
            ) pd_agg ON pd_agg.id_pembelian = pnd.id_pembelian AND pd_agg.id_penampungan = pnd.id_penampungan
            WHERE pnd.id_penampungan = ?
            AND (pnd.berat_masuk - IFNULL(pd_agg.total_terpakai, 0)) > 0
            ORDER BY pnd.created_at ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $available = $row['berat_masuk'] - $row['terpakai'];
            $take = min($available, $needed);
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
                WHERE tahap_ke = 0 AND status = 'aktif'
                GROUP BY id_pembelian
            ) pd_agg ON pd_agg.id_pembelian = pa.id
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) as terpakai_penampungan
                FROM bb_penampungan_detail
                GROUP BY id_pembelian
            ) pnd_agg ON pnd_agg.id_pembelian = pa.id
            WHERE pa.id_bahan = ? AND pa.id_supplier = ? AND pa.status != 'selesai_siap_jual'
            AND (pa.berat_awal - IFNULL(pd_agg.terpakai_prod, 0) - IFNULL(pnd_agg.terpakai_penampungan, 0)) > 0
            ORDER BY pa.tanggal_pembelian ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id_bahan, $actual_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $available = $row['berat_awal'] - $row['terpakai_prod'] - $row['terpakai_penampungan'];
            $take = min($available, $needed);
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

$conn->begin_transaction();
try {
    foreach ($active_pembelian as $data) {
        $id_pembelian   = $data['id_pembelian'];
        $id_penampungan = $data['id_penampungan'];
        $berat_masuk    = $data['qty'];
        $berat_keluar   = $berat_masuk;

        $sqlInsert = "INSERT INTO bb_proses_detail 
                        (kode_produksi, id_pembelian, id_penampungan, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan) 
                      VALUES (?, ?, ?, NULL, 0, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("siisdds",
            $kode_produksi, $id_pembelian, $id_penampungan,
            $tanggal_proses, $berat_masuk, $berat_keluar, $final_catatan
        );
        $stmtInsert->execute();

        $conn->query("UPDATE bb_pembelian_awal SET status = 'proses' WHERE id = $id_pembelian AND status IN ('load', 'uang_terbayar')");
    }
    $conn->commit();
    $_SESSION['success'] = "Produksi $kode_produksi berhasil didaftarkan.";
} catch (Exception $e) {
    $conn->rollback();
    die("Error saat menyimpan produksi: " . $e->getMessage());
}

header("Location: index.php");
exit();
