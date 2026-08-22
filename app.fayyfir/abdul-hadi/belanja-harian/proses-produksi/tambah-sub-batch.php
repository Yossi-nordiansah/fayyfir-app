<?php
session_start();
require "../../config.php";
$conn = get_conn2();

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$id_pembelian = (int)($_POST['id_pembelian'] ?? 0);
$tanggal_proses = $_POST['tanggal_proses'] ?? date('Y-m-d');
$catatan = trim($_POST['catatan'] ?? '');

if ($id_pembelian <= 0) {
    die("Error: Pembelian tidak valid.");
}

// Cek data pembelian awal
$resPa = $conn->query("SELECT pa.*, s.nama_supplier, bm.nama_bahan FROM bb_pembelian_awal pa JOIN bb_bahan_master bm ON bm.id = pa.id_bahan LEFT JOIN bb_supplier s ON s.id = pa.id_supplier WHERE pa.id = $id_pembelian AND pa.status != 'selesai_siap_jual'");
$pembelian = $resPa ? $resPa->fetch_assoc() : null;

if (!$pembelian) {
    die("Error: Pembelian/Bahan tidak ditemukan atau sudah selesai.");
}

// Generate Kode Sub-Batch Produksi Baru
$prefix   = "PRD-" . date('Ymd', strtotime($tanggal_proses)) . "-";
$resCount = $conn->query("SELECT COUNT(DISTINCT kode_produksi) as total FROM bb_proses_detail WHERE kode_produksi LIKE '$prefix%'");
$nextNum  = ($resCount->fetch_assoc()['total'] ?? 0) + 1;
$kode_produksi = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

$resTerpakai = $conn->query("SELECT IFNULL(SUM(berat_masuk), 0) as terpakai FROM bb_proses_detail WHERE id_pembelian = $id_pembelian AND tahap_ke = 0 AND status != 'batal'");
$terpakai = $resTerpakai ? (float)$resTerpakai->fetch_assoc()['terpakai'] : 0;
$available_stok = max(0, (float)$pembelian['berat_awal'] - $terpakai);
$berat_masuk  = ($available_stok > 0) ? $available_stok : (float)$pembelian['berat_awal'];
$berat_keluar = $berat_masuk;
$harga_per_kg = (float)$pembelian['harga_per_kg'];

$id_penampungan = null;
$resPen = $conn->query("SELECT id_penampungan FROM bb_penampungan_detail WHERE id_pembelian = $id_pembelian LIMIT 1");
if ($resPen && $rP = $resPen->fetch_assoc()) {
    $id_penampungan = (int)$rP['id_penampungan'];
}

$conn->begin_transaction();
try {
    $sqlInsert = "INSERT INTO bb_proses_detail 
                    (kode_produksi, id_pembelian, id_penampungan, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan, status, metode_produksi, status_batch, hpp_temporary) 
                  VALUES (?, ?, ?, NULL, 0, ?, ?, ?, ?, 'aktif', 'belum_tertimbang', 'berjalan', ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert) {
        throw new Exception("Gagal prepare query: " . $conn->error);
    }
    // Types: s=kode_produksi, i=id_pembelian, i=id_penampungan, s=tanggal_proses, d=berat_masuk, d=berat_keluar, s=catatan, d=hpp_temporary
    $stmtInsert->bind_param("siisddsd",
        $kode_produksi, $id_pembelian, $id_penampungan,
        $tanggal_proses, $berat_masuk, $berat_keluar,
        $catatan, $harga_per_kg
    );
    if (!$stmtInsert->execute()) {
        throw new Exception("Gagal menyimpan sub-batch: " . $stmtInsert->error);
    }

    $conn->query("UPDATE bb_pembelian_awal SET status = 'proses' WHERE id = $id_pembelian AND status IN ('load', 'uang_terbayar')");

    $conn->commit();
    $_SESSION['success'] = "Sub-Batch baru $kode_produksi berhasil ditambahkan ke dalam kelompok produksi Belum Tertimbang.";
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: kelola-sub-batch.php?id_pembelian=" . $id_pembelian);
exit();
