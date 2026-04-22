<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit();
}

if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit();
}

$id = intval($_GET['id']);
$user_id = intval($_SESSION["user_id"]); // Ambil ID user dari sesi login

// Ambil container_number terlebih dahulu
$query = $conn->prepare("SELECT container_number FROM containers WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
  $_SESSION["status_pesan"] = "Kontainer tidak ditemukan.";
  header("Location: index");
  exit();
}

$row = $result->fetch_assoc();
$container_number = $row['container_number'];

// Update status, created_by, dan updated_at
$sql = "UPDATE containers SET status = 'draft', created_by = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $id);

if ($stmt->execute()) {
  $_SESSION["status_pesan"] = "Status kontainer ($container_number) kembali untuk diisi, silahkan lanjutkan pengisian kontainer lainnya!";
} else {
  $_SESSION["status_pesan"] = "Gagal mengubah status kontainer.";
}

header("Location: index");
exit();
?>