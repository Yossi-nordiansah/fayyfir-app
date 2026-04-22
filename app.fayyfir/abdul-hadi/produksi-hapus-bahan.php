<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = (int) $_GET["id"]; // id dari production_materials
$production_id = (int) $_GET["production_id"];
$name = htmlspecialchars($_GET["name"] ?? '');

if (!$id || !$production_id) {
  die("Parameter tidak valid.");
}

// Ambil data bahan terlebih dahulu
$stmt = $conn->prepare("SELECT material_id, quantity_used FROM production_materials WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($material_id, $quantity_used);

if (!$stmt->fetch()) {
  $stmt->close();
  die("Data bahan tidak ditemukan.");
}
$stmt->close();

// Tambahkan kembali stok bahan
$updateStock = $conn->prepare("UPDATE material_stocks SET quantity = quantity + ? WHERE material_id = ?");
$updateStock->bind_param("di", $quantity_used, $material_id);
$updateStock->execute();
$updateStock->close();

// Catat perubahan ke log
$note = "Hapus bahan dari produksi $name pada " . date('d-m-Y');
$logStmt = $conn->prepare("INSERT INTO stock_movements (material_id, change_type, quantity, note, created_at) VALUES (?, 'IN', ?, ?, NOW())");
$logStmt->bind_param("ids", $material_id, $quantity_used, $note);
$logStmt->execute();
$logStmt->close();

// Hapus dari production_materials
$delStmt = $conn->prepare("DELETE FROM production_materials WHERE id = ?");
$delStmt->bind_param("i", $id);
$delStmt->execute();
$delStmt->close();

// Redirect kembali
header("Location: produksi-proses.php?id=$production_id&name=" . urlencode($name));
exit();
?>