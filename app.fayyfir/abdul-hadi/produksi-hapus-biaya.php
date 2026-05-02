<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$expense_id = (int) ($_GET["expense_id"] ?? 0);
$production_id = (int) ($_GET["id"] ?? 0);
$production_name = $_GET["name"] ?? "";

if ($expense_id > 0) {
  $stmt = $conn->prepare("DELETE FROM production_expenses WHERE id = ?");
  $stmt->bind_param("i", $expense_id);
  if ($stmt->execute()) {
    header("Location: produksi-proses.php?id=$production_id&name=" . urlencode($production_name));
      exit();
  } else {
    echo "Gagal menghapus data.";
  }
} else {
  echo "Parameter tidak valid.";
}
?>