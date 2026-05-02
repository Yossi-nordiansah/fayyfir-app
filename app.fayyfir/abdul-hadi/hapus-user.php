<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
  $id = (int) $_POST["id"];

  // Jangan hapus diri sendiri
  if ($id == $_SESSION["user_id"]) {
    die("Tidak dapat menghapus akun sendiri.");
  }

  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header("Location: daftar-tim?deleted=1");
    exit();
  } else {
    echo "Gagal menghapus user.";
  }
}
?>