<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
  $id = (int) $_POST["id"];

  $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header("Location: modal-dan-aset?deleted=1");
    exit();
  } else {
    echo "Gagal menghapus data aset.";
  }
}
?>
