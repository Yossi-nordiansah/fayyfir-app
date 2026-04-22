<?php
require "config.php";

$invoice   = $_POST['invoice'] ?? '';
$pelunasan = floatval($_POST['pelunasan'] ?? 0);

if ($invoice === '' || $pelunasan <= 0) {
  echo "Data tidak valid.";
  exit;
}

// Ambil dp lama
$stmt = $conn->prepare("SELECT dp FROM selling_products WHERE invoice_number = ? LIMIT 1");
$stmt->bind_param("s", $invoice);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  echo "Invoice tidak ditemukan.";
  exit;
}

$dp_lama = floatval($row['dp']);
$dp_baru = $dp_lama - $pelunasan;
if ($dp_baru < 0) $dp_baru = 0; // tidak boleh minus

$status = ($dp_baru == 0) ? "Lunas" : "DP";

// Update dp & status
$upd = $conn->prepare("UPDATE selling_products SET dp = ?, status = ? WHERE invoice_number = ?");
$upd->bind_param("dss", $dp_baru, $status, $invoice);

if ($upd->execute()) {
  echo "Pelunasan berhasil. Status: " . $status;
} else {
  echo "Gagal menyimpan pelunasan.";
}
$upd->close();