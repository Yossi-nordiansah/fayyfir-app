<?php
session_start();
require "../../config.php";
$conn = $conn2;

require "../includes/helpers.php";
require "../includes/validation.php";

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Ambil parameter
$id_pengeluaran = isset($_GET['id_pengeluaran']) ? intval($_GET['id_pengeluaran']) : 0;
$id_penjualan   = isset($_GET['id_penjualan']) ? intval($_GET['id_penjualan']) : 0;

// Validasi parameter
if ($id_pengeluaran <= 0 || $id_penjualan <= 0) {
    header("Location: detail-penjualan.php?id={$id_penjualan}&error=invalid_parameter");
    exit();
}

// Pastikan data pengeluaran ada
$stmt = $conn->prepare("SELECT id FROM bb_pengeluaran WHERE id = ?");
$stmt->bind_param("i", $id_pengeluaran);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: detail-penjualan.php?id={$id_penjualan}&error=pengeluaran_not_found");
    exit();
}

// Hapus data pengeluaran
$stmt = $conn->prepare("DELETE FROM bb_pengeluaran WHERE id = ?");
$stmt->bind_param("i", $id_pengeluaran);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: detail-penjualan.php?id={$id_penjualan}&deleted=1");
    exit();
} else {
    $err = urlencode("Gagal menghapus data: " . $stmt->error);
    $stmt->close();
    header("Location: detail-penjualan.php?id={$id_penjualan}&error={$err}");
    exit();
}
?>