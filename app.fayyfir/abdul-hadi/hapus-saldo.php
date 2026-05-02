<?php
session_start();
require "config.php";

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Pastikan request POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
  $user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;

  if ($id <= 0 || $user_id <= 0) {
    echo "Data tidak valid.";
    exit();
  }

  // Optional: bisa tambahkan cek apakah id dan user_id cocok
  
  $stmt = $conn->prepare("DELETE FROM cash_flows WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $id, $user_id);
  if ($stmt->execute()) {
    header("Location: rincian-kas.php?user_id=" . $user_id);
    exit();
  } else {
    echo "Gagal menghapus data.";
  }
} else {
  echo "Metode tidak diizinkan.";
}