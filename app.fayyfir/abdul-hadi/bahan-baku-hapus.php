<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
  die("ID tidak valid.");
}

// Pastikan data ada
$check_stmt = $conn->prepare("SELECT id, name FROM materials WHERE id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$material = $result->fetch_assoc();
$check_stmt->close();

if (!$material) {
  die("Data bahan baku tidak ditemukan.");
}

// Hapus data terkait di material_stocks
$delete_stock = $conn->prepare("DELETE FROM material_stocks WHERE material_id = ?");
$delete_stock->bind_param("i", $id);
$delete_stock->execute();
$delete_stock->close();

// Hapus data utama di materials
$delete_material = $conn->prepare("DELETE FROM materials WHERE id = ?");
$delete_material->bind_param("i", $id);

if ($delete_material->execute()) {
  $delete_material->close();
  header("Location: bahan-baku.php");
  exit();
} else {
  $delete_material->close();
  die("Gagal menghapus data bahan baku: " . $conn->error);
}