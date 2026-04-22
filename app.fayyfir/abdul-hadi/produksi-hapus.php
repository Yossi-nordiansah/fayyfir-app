<?php
session_start();
require "config.php";

// Cek apakah user sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

// Ambil id dari query string
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: produksi");
    exit();
}
$id = (int) $id;

// Cek apakah data produksi ada
$check_stmt = $conn->prepare("SELECT id FROM productions WHERE id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    // Data tidak ditemukan
    $check_stmt->close();
    header("Location: produksi?status=notfound");
    exit();
}
$check_stmt->close();

// Hapus data produksi
$stmt = $conn->prepare("DELETE FROM productions WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: produksi?status=deleted");
    exit();
} else {
    echo "Terjadi kesalahan saat menghapus data: " . $stmt->error;
}

$stmt->close();
$conn->close();