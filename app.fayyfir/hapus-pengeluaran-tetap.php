<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id > 0) {
  $stmt = $conn->prepare("DELETE FROM fixed_expenses WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: tambah-pengeluaran-tetap.php");
exit();
?>
