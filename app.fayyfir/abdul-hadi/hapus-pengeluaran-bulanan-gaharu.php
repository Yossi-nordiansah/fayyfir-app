<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}
require "config.php";

$level = $_SESSION["role_id"] ?? "";
if ($level != "2" && $level != "3") {
  header("Location: index");
  exit();
}

$id         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$back_bulan = $_GET['bulan'] ?? date('Y-m');

if ($id) {
  $stmt = $conn->prepare("DELETE FROM gaharu_monthly_expenses WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: pengeluaran-bulanan-gaharu.php?bulan=" . urlencode($back_bulan));
exit();
