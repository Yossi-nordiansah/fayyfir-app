<?php
session_start();
require "config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if (isset($_GET["id"])) {
  $purchase_id = intval($_GET["id"]);

  // Ambil data pembelian
  $get = $conn->prepare("SELECT mp.material_id, mp.quantity, mp.unit_price, m.name AS material_name 
                         FROM material_purchases mp 
                         JOIN materials m ON mp.material_id = m.id 
                         WHERE mp.id = ?");
  $get->bind_param("i", $purchase_id);
  $get->execute();
  $result = $get->get_result();

  if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $material_id = $data["material_id"];
    $name = $data['material_name'];
    $quantity_to_deduct = $data["quantity"];  // selalu pakai dalam Kg
    $unit_price = $data["unit_price"] ?? 0;        // harga per Kg
    $amount = $quantity_to_deduct * $unit_price;

    // 1. Kurangi stok di material_stocks
    $update = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
    if (!$update) die("Update prepare error: " . $conn->error);
    $update->bind_param("di", $quantity_to_deduct, $material_id);
    $update->execute();

    // 2. Tambahkan catatan koreksi ke stock_movements
    $note = "Hapusan pembelian $name";
    $movement = $conn->prepare("INSERT INTO stock_movements (material_id, change_type, quantity, unit_price, amount, note) 
                                VALUES (?, 'OUT', ?, ?, ?, ?)");
    if (!$movement) die("Insert prepare error: " . $conn->error);
    $movement->bind_param("iddds", $material_id, $quantity_to_deduct, $unit_price, $amount, $note);
    $movement->execute();

    // 3. Hapus data dari material_purchases
    $delete = $conn->prepare("DELETE FROM material_purchases WHERE id = ?");
    if (!$delete) die("Delete prepare error: " . $conn->error);
    $delete->bind_param("i", $purchase_id);
    if ($delete->execute()) {
      header("Location: bahan-baku-rincian?id=" . $material_id);
      exit();
    } else {
      echo "Gagal menghapus data stok.";
    }
  } else {
    echo "Data tidak ditemukan.";
  }
} else {
  echo "Permintaan tidak valid.";
}
?>