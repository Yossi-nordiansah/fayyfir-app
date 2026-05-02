<?php    
session_start();    
require "../../../config.php";    
$conn = $conn2;    
    
require "../../includes/helpers.php";    
require "../../includes/validation.php";    
    
// Pastikan user sudah login    
if (!isset($_SESSION["user_id"])) {    
  header("Location: ../../../login");    
  exit();    
}    
    
// Ambil ID pengeluaran dan sortir dari URL    
$id_pengeluaran = isset($_GET['id_pengeluaran']) ? intval($_GET['id_pengeluaran']) : 0;    
$id_sortir = isset($_GET['id_sortir']) ? intval($_GET['id_sortir']) : 0;    
    
// Validasi ID    
if ($id_pengeluaran <= 0 || $id_sortir <= 0) {    
  header("Location: list-sortir.php?error=invalid_id");    
  exit();    
}    
    
// Pastikan data pengeluaran ada    
$stmt = $conn->prepare("SELECT id FROM bb_pengeluaran WHERE id = ?");    
$stmt->bind_param("i", $id_pengeluaran);    
$stmt->execute();    
$result = $stmt->get_result();    
$pengeluaran = $result->fetch_assoc();    
$stmt->close();    
    
if (!$pengeluaran) {    
  header("Location: detail-sortir.php?id={$id_sortir}&error=pengeluaran_not_found");    
  exit();    
}    
    
// Hapus data pengeluaran    
$stmt = $conn->prepare("DELETE FROM bb_pengeluaran WHERE id = ?");    
$stmt->bind_param("i", $id_pengeluaran);    
    
if ($stmt->execute()) {    
  $stmt->close();    
  header("Location: detail-sortir.php?id={$id_sortir}&deleted=1");    
  exit();    
} else {    
  $error = htmlspecialchars($stmt->error);    
  $stmt->close();    
  header("Location: detail-sortir.php?id={$id_sortir}&error=" . urlencode("Gagal menghapus data: $error"));    
  exit();    
}    
?>