<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
  $id = (int) $_POST["id"];

  // Ambil container_id sebelum hapus
  $getStmt = $conn->prepare(
    "SELECT container_id FROM expenses WHERE id = ?"
  );
  $getStmt->bind_param("i", $id);
  $getStmt->execute();
  $getResult = $getStmt->get_result();

  if ($getResult->num_rows > 0) {
    $row = $getResult->fetch_assoc();
    $id_kontainer = $row["container_id"];

    // Hapus data
    $deleteStmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    if ($deleteStmt->execute()) {
      header("Location: riwayat-kontainer?id=$id_kontainer&deleted=1");
      exit();
    } else {
      echo "Gagal menghapus operasional.";
    }
  } else {
    echo "Data transaksi tidak ditemukan.";
  }
}
?>
