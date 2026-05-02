<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan koneksi aktif

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Pastikan request adalah POST (karena form di modal menggunakan POST)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index");
    exit();
}

// Ambil data dari POST (sesuai dengan name attribute di form index.php)
$id_pembelian = isset($_POST['id_pembelian']) ? intval($_POST['id_pembelian']) : 0;
$next_stage   = isset($_POST['next_stage']) ? intval($_POST['next_stage']) : 0;
$berat_masuk  = isset($_POST['berat_masuk']) ? floatval($_POST['berat_masuk']) : 0;
$berat_keluar = isset($_POST['berat_keluar']) ? floatval($_POST['berat_keluar']) : 0;
$catatan      = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

// Validasi dasar
if ($id_pembelian <= 0 || $next_stage <= 0 || $berat_masuk <= 0) {
    echo "<script>alert('Data input tidak valid!'); window.location='index';</script>";
    exit();
}

// 1. Ambil id_proses_master berdasarkan urutan_tahap
$sql_master = "SELECT id, nama_proses FROM bb_proses_master WHERE urutan_tahap = ? LIMIT 1";
$id_proses_master = 0;
$nama_proses = "";
if ($stmt = $conn->prepare($sql_master)) {
    $stmt->bind_param("i", $next_stage);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $id_proses_master = $row['id'];
        $nama_proses = $row['nama_proses'];
    }
    $stmt->close();
}

if ($id_proses_master <= 0) {
    echo "<script>alert('Master proses untuk tahap $next_stage tidak ditemukan!'); window.location='index';</script>";
    exit();
}

// 2. Simpan ke tabel bb_proses_detail (tabel log proses)
$sql_ins = "INSERT INTO bb_proses_detail (id_pembelian, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan, created_at) 
            VALUES (?, ?, ?, CURDATE(), ?, ?, ?, NOW())";
if ($stmt = $conn->prepare($sql_ins)) {
    $stmt->bind_param("iiidds", $id_pembelian, $id_proses_master, $next_stage, $berat_masuk, $berat_keluar, $catatan);
    if ($stmt->execute()) {
        $stmt->close();

        // 3. Update status di bb_pembelian_awal menjadi tahapN
        $new_status = "tahap" . $next_stage;
        $sql_upd = "UPDATE bb_pembelian_awal SET status = ? WHERE id = ?";
        if ($stmt_upd = $conn->prepare($sql_upd)) {
            $stmt_upd->bind_param("si", $new_status, $id_pembelian);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        echo "<script>
            alert('Batch berhasil diproses ke tahap $next_stage ($nama_proses)!');
            window.location='index';
        </script>";
    } else {
        echo "<script>alert('Terjadi kesalahan saat menyimpan data proses!'); window.location='index';</script>";
    }
}
?>