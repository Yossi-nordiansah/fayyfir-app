<?php
require "../../../config.php";
$conn = $conn2;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ada_pengeluaran = false;

if ($id > 0) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS jml FROM bb_pengeluaran WHERE id_pembelian = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($jumlah);
  $stmt->fetch();
  $stmt->close();

  $ada_pengeluaran = ($jumlah > 0);
}

header('Content-Type: application/json');
echo json_encode(['ada_pengeluaran' => $ada_pengeluaran]);