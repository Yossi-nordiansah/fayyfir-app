<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

// Pastikan ada parameter id
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION["error"] = "ID buyer tidak valid.";
    header("Location: buyer");
    exit();
}

$buyer_id = (int) $_GET["id"];

// Cek apakah data buyer ada
$stmt = $conn->prepare("SELECT id FROM buyer_products WHERE id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Data buyer tidak ditemukan.";
    header("Location: buyer");
    exit();
}

// Hapus data buyer
$stmt = $conn->prepare("DELETE FROM buyer_products WHERE id = ?");
$stmt->bind_param("i", $buyer_id);

if ($stmt->execute()) {
    $_SESSION["success"] = "Data buyer beserta seluruh transaksinya berhasil dihapus.";
} else {
    $_SESSION["error"] = "Gagal menghapus data buyer.";
}

header("Location: transaksi-produk");
exit();