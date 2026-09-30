<?php
/**
 * angsuran-simpan.php
 * Endpoint AJAX: Simpan angsuran/pembayaran bertahap per invoice
 * 
 * POST params:
 *   invoice    : string (invoice_number)
 *   jumlah     : float  (jumlah pembayaran)
 *   keterangan : string (opsional)
 * 
 * Response JSON:
 *   { success, sisa, status, total_dibayar, message }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'config.php';

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

$invoice    = trim($_POST['invoice'] ?? '');
$jumlah     = (float)($_POST['jumlah'] ?? 0);
$keterangan = trim($_POST['keterangan'] ?? '');

if ($invoice === '' || $jumlah <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid. Pastikan jumlah > 0.']);
    exit;
}

// Fitur otomatis: jika user tidak input keterangan, isi otomatis 'Angsuran ke-X' (DP tidak termasuk angsuran)
if ($keterangan === '') {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total_exist FROM invoice_payments WHERE invoice_number = ?");
    $count_stmt->bind_param('s', $invoice);
    $count_stmt->execute();
    $c_row = $count_stmt->get_result()->fetch_assoc();
    $count_stmt->close();
    $next_no = (int)($c_row['total_exist'] ?? 0) + 1;
    $keterangan = "Angsuran ke-{$next_no}";
}

// Ambil total tagihan dan DP awal dari selling_products
$stmt = $conn->prepare("
    SELECT 
        SUM(total_selling) AS total_tagihan,
        MAX(dp)            AS dp_awal,
        MAX(status)        AS status_now
    FROM selling_products
    WHERE invoice_number = ?
");
$stmt->bind_param('s', $invoice);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['total_tagihan'] === null) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak ditemukan.']);
    exit;
}

if (strcasecmp($row['status_now'] ?? '', 'Lunas') === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaksi ini sudah lunas.']);
    exit;
}

$total_tagihan = (float)$row['total_tagihan'];
$dp_awal       = (float)$row['dp_awal'];

// Hitung total angsuran yang sudah dibayar (dari tabel invoice_payments)
$stmt2 = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total_angsuran FROM invoice_payments WHERE invoice_number = ?");
$stmt2->bind_param('s', $invoice);
$stmt2->execute();
$row2 = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$total_angsuran = (float)$row2['total_angsuran'];
$total_dibayar  = $dp_awal + $total_angsuran;
$sisa_sebelum   = $total_tagihan - $total_dibayar;

// Validasi: jangan bayar melebihi sisa
if ($jumlah > $sisa_sebelum + 0.01) {
    echo json_encode([
        'success' => false,
        'message' => 'Jumlah pembayaran (Rp ' . number_format($jumlah, 0, ',', '.') . ') melebihi sisa tagihan (Rp ' . number_format($sisa_sebelum, 0, ',', '.') . ').'
    ]);
    exit;
}

// Simpan angsuran baru
$user_id = (int)$_SESSION['user_id'];
$stmt3 = $conn->prepare("
    INSERT INTO invoice_payments (invoice_number, payment_date, jumlah, keterangan, created_by)
    VALUES (?, NOW(), ?, ?, ?)
");
$stmt3->bind_param('sdsi', $invoice, $jumlah, $keterangan, $user_id);

if (!$stmt3->execute()) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $conn->error]);
    exit;
}
$stmt3->close();

// Hitung sisa terbaru setelah pembayaran ini
$sisa_baru       = $sisa_sebelum - $jumlah;
$total_dibayar_baru = $total_dibayar + $jumlah;

// Update status di selling_products jika sudah lunas
$status_baru = ($sisa_baru <= 0.01) ? 'Lunas' : 'DP';
$upd = $conn->prepare("UPDATE selling_products SET status = ? WHERE invoice_number = ?");
$upd->bind_param('ss', $status_baru, $invoice);
$upd->execute();
$upd->close();

echo json_encode([
    'success'       => true,
    'sisa'          => max(0, $sisa_baru),
    'status'        => $status_baru,
    'total_dibayar' => $total_dibayar_baru,
    'message'       => 'Pembayaran berhasil disimpan. Status: ' . $status_baru
]);
exit;
