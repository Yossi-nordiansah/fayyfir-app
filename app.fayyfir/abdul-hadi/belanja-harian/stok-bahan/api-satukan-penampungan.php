<?php
session_start();
require "../../config.php";
$conn = $conn2;

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id_bahan = (int)$_POST['id_bahan'];
$ids_pembelian = $_POST['ids_pembelian'] ?? []; // Array of IDs
$nama_penampungan = $_POST['nama_penampungan'] ?? '';

if (empty($ids_pembelian) || empty($nama_penampungan)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$conn->begin_transaction();
try {
    // 1. Create Penampungan
    $stmt = $conn->prepare("INSERT INTO bb_penampungan (id_bahan, nama_penampungan) VALUES (?, ?)");
    $stmt->bind_param("is", $id_bahan, $nama_penampungan);
    $stmt->execute();
    $id_penampungan = $stmt->insert_id;

    // 2. Update bb_pembelian_awal
    $ids_placeholders = implode(',', array_fill(0, count($ids_pembelian), '?'));
    $sql = "UPDATE bb_pembelian_awal SET id_penampungan = ? WHERE id IN ($ids_placeholders) AND id_bahan = ?";
    $stmt = $conn->prepare($sql);
    
    $params = array_merge([$id_penampungan], $ids_pembelian, [$id_bahan]);
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Berhasil menyatukan bahan ke penampungan: ' . $nama_penampungan]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
