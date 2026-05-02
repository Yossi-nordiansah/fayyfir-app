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

// Ambil data produksi (product_id & total_output) untuk penyesuaian stok
$data_stmt = $conn->prepare("SELECT product_id, total_output FROM productions WHERE id = ?");
$data_stmt->bind_param("i", $id);
$data_stmt->execute();
$data_res = $data_stmt->get_result();
$prod_data = $data_res->fetch_assoc();
$data_stmt->close();

if (!$prod_data) {
    header("Location: produksi?status=notfound");
    exit();
}

$product_id = $prod_data['product_id'];
$total_output = $prod_data['total_output'] ?? 0;

// Mulai Transaksi
$conn->begin_transaction();

try {
    // 1. Kurangi stok di product_stocks
    $stock_stmt = $conn->prepare("UPDATE product_stocks SET quantity = quantity - ? WHERE id = ?");
    $stock_stmt->bind_param("di", $total_output, $product_id);
    $stock_stmt->execute();
    $stock_stmt->close();

    // 2. Hapus data produksi
    $stmt = $conn->prepare("DELETE FROM productions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: produksi?status=deleted");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Terjadi kesalahan: " . $e->getMessage();
}

$stmt->close();
$conn->close();