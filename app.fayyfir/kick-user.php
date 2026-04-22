<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit();
}

$admin_id = $_SESSION["user_id"];
$target_id = $_POST["id"] ?? 0;
$target_id = (int)$target_id;

// Jangan izinkan admin kick dirinya sendiri
if ($admin_id === $target_id) {
  header("Location: daftar-tim.php?error=self_kick");
  exit();
}

// Reset session_token dan status online user target
$stmt = $conn->prepare("UPDATE users SET session_token = NULL, is_online = 0 WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$stmt->close();

header("Location: daftar-tim.php?kick_success=1");
exit();
?>