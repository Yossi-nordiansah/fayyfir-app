<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = $_GET['id'] ?? null;
$supplier_id = $_GET['supplier_id'] ?? null;

if (!$id || !$supplier_id) {
  echo "ID tidak valid.";
  exit();
}

// Ambil data deposit berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM deposits_supplier WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
  echo "Data tidak ditemukan.";
  exit();
}

// Hanya izinkan hapus jika nilai credit lebih dari 0
  $stmt = $conn->prepare("DELETE FROM deposits_supplier WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header("Location: rincian-dp-supplier.php?id=" . $supplier_id);
    exit();
  } else {
    echo "Gagal menghapus data.";
  }
?>