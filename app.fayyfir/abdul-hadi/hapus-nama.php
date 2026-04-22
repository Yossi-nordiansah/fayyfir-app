<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

  // Cek apakah masih ada transaksi terkait user_id ini
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cash_flows WHERE user_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $count = $result->fetch_assoc()["total"];

  if ($count > 0) {
    // Jika masih ada transaksi, jangan hapus
    echo "<script>alert('Tidak bisa menghapus, karena masih ada transaksi terkait!'); window.location.href='rincian-utang-piutang?user_id=$id';</script>";
    exit();
  }

  // Jika tidak ada transaksi, lanjut hapus user_cash_flows
  $stmt = $conn->prepare("DELETE FROM user_cash_flows WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    echo "<script>alert('Data berhasil dihapus.'); window.location.href='kas.php';</script>";
    exit();
  } else {
    echo "<script>alert('Gagal menghapus data.'); window.location.href='rincian-utang-piutang?user_id=$id';</script>";
    exit();
  }
} else {
  header("Location: kas.php");
  exit();
}