<?php
/**
 * update-status.php
 * Pelunasan dari modal lama (transaksi-rincian.php).
 * Sekarang menggunakan tabel invoice_payments agar konsisten
 * dengan sistem angsuran bertahap.
 */

session_start();
require "config.php";

$invoice   = $_POST['invoice'] ?? '';
$pelunasan = floatval($_POST['pelunasan'] ?? 0);

if ($invoice === '' || $pelunasan <= 0) {
    echo "Data tidak valid.";
    exit;
}

// AUTO-CREATE tabel invoice_payments jika belum ada
$conn->query("
    CREATE TABLE IF NOT EXISTS `invoice_payments` (
      `id`             INT NOT NULL AUTO_INCREMENT,
      `invoice_number` VARCHAR(100) NOT NULL,
      `payment_date`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `jumlah`         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `keterangan`     TEXT NULL,
      `created_by`     INT NULL,
      `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_invoice_number` (`invoice_number`),
      KEY `idx_payment_date`   (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Ambil total tagihan dan DP awal
$stmt = $conn->prepare("
    SELECT 
        SUM(total_selling) AS total_tagihan,
        MAX(dp)            AS dp_awal,
        MAX(status)        AS status_now
    FROM selling_products
    WHERE invoice_number = ?
");
$stmt->bind_param("s", $invoice);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['total_tagihan'] === null) {
    echo "Invoice tidak ditemukan.";
    exit;
}

if (strcasecmp($row['status_now'] ?? '', 'Lunas') === 0) {
    echo "Transaksi ini sudah lunas.";
    exit;
}

$total_tagihan = (float)$row['total_tagihan'];
$dp_awal       = (float)$row['dp_awal'];

// Hitung total angsuran sebelumnya
$stmt2 = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total_angsuran FROM invoice_payments WHERE invoice_number = ?");
$stmt2->bind_param("s", $invoice);
$stmt2->execute();
$row2 = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$total_dibayar = $dp_awal + (float)$row2['total_angsuran'];
$sisa_sebelum  = $total_tagihan - $total_dibayar;

if ($pelunasan > $sisa_sebelum + 0.01) {
    echo "Jumlah pelunasan melebihi sisa tagihan (Rp " . number_format($sisa_sebelum, 0, ',', '.') . ").";
    exit;
}

// Simpan ke invoice_payments
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$keterangan = 'Pelunasan';
$stmt3 = $conn->prepare("
    INSERT INTO invoice_payments (invoice_number, payment_date, jumlah, keterangan, created_by)
    VALUES (?, NOW(), ?, ?, ?)
");
$stmt3->bind_param("sdsi", $invoice, $pelunasan, $keterangan, $user_id);
if (!$stmt3->execute()) {
    echo "Gagal menyimpan: " . $conn->error;
    exit;
}
$stmt3->close();

// Update status
$sisa_baru   = max(0, $sisa_sebelum - $pelunasan);
$status_baru = ($sisa_baru <= 0.01) ? 'Lunas' : 'DP';

$upd = $conn->prepare("UPDATE selling_products SET status = ? WHERE invoice_number = ?");
$upd->bind_param("ss", $status_baru, $invoice);
$upd->execute();
$upd->close();

echo "Pelunasan berhasil. Status: " . $status_baru;
exit;