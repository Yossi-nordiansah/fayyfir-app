<?php
/**
 * angsuran-update.php
 * Endpoint AJAX: Edit/update data pembayaran (tanggal, jumlah, keterangan)
 * 
 * POST params:
 *   invoice    : string (invoice_number)
 *   payment_id : int (id di invoice_payments, atau 0 jika type='dp')
 *   type       : string ('angsuran' atau 'dp')
 *   tanggal    : string (format Y-m-d\TH:i atau Y-m-d H:i:s)
 *   jumlah     : float  (jumlah pembayaran baru)
 *   keterangan : string (opsional)
 * 
 * Response JSON:
 *   { success, sisa, status, total_dibayar, message }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'config.php';

$invoice    = trim($_POST['invoice'] ?? '');
$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
$type       = strtolower(trim($_POST['type'] ?? 'angsuran'));
$tanggal    = trim($_POST['tanggal'] ?? '');
$jumlah     = (float)($_POST['jumlah'] ?? 0);
$keterangan = trim($_POST['keterangan'] ?? '');

if ($invoice === '' || $jumlah < 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid. Pastikan invoice dan jumlah valid.']);
    exit;
}

if ($tanggal === '') {
    echo json_encode(['success' => false, 'message' => 'Tanggal pembayaran wajib diisi.']);
    exit;
}

// Format tanggal ke Y-m-d H:i:s
$tanggal_ts = strtotime(str_replace('T', ' ', $tanggal));
if (!$tanggal_ts) {
    echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']);
    exit;
}
$tanggal_db = date('Y-m-d H:i:s', $tanggal_ts);

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

$total_tagihan = (float)$row['total_tagihan'];
$dp_awal       = (float)$row['dp_awal'];

// Ambil total angsuran selain pembayaran yang sedang diedit
if ($type === 'dp') {
    $stmt_ang = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total_angsuran FROM invoice_payments WHERE invoice_number = ?");
    $stmt_ang->bind_param('s', $invoice);
    $stmt_ang->execute();
    $total_angsuran_lain = (float)$stmt_ang->get_result()->fetch_assoc()['total_angsuran'];
    $stmt_ang->close();

    $total_lain = $total_angsuran_lain;
} else {
    // Angsuran dari invoice_payments
    $stmt_check = $conn->prepare("SELECT id, jumlah FROM invoice_payments WHERE id = ? AND invoice_number = ?");
    $stmt_check->bind_param('is', $payment_id, $invoice);
    $stmt_check->execute();
    $pay_row = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$pay_row) {
        echo json_encode(['success' => false, 'message' => 'Data pembayaran tidak ditemukan.']);
        exit;
    }

    $stmt_ang = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total_angsuran FROM invoice_payments WHERE invoice_number = ? AND id != ?");
    $stmt_ang->bind_param('si', $invoice, $payment_id);
    $stmt_ang->execute();
    $total_angsuran_lain = (float)$stmt_ang->get_result()->fetch_assoc()['total_angsuran'];
    $stmt_ang->close();

    $total_lain = $dp_awal + $total_angsuran_lain;
}

// Validasi jika jumlah baru melebihi total tagihan
if ($jumlah + $total_lain > $total_tagihan + 0.01) {
    $max_boleh = max(0, $total_tagihan - $total_lain);
    echo json_encode([
        'success' => false,
        'message' => 'Jumlah pembayaran (Rp ' . number_format($jumlah, 0, ',', '.') . ') melebihi batas yang dapat dibayar (Maks. Rp ' . number_format($max_boleh, 0, ',', '.') . ').'
    ]);
    exit;
}

// Proses update
if ($type === 'dp') {
    $upd = $conn->prepare("UPDATE selling_products SET dp = ?, selling_date = ? WHERE invoice_number = ?");
    $upd->bind_param('dss', $jumlah, $tanggal_db, $invoice);
    if (!$upd->execute()) {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui DP: ' . $conn->error]);
        exit;
    }
    $upd->close();
} else {
    $upd = $conn->prepare("UPDATE invoice_payments SET payment_date = ?, jumlah = ?, keterangan = ? WHERE id = ? AND invoice_number = ?");
    $upd->bind_param('sdsis', $tanggal_db, $jumlah, $keterangan, $payment_id, $invoice);
    if (!$upd->execute()) {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui angsuran: ' . $conn->error]);
        exit;
    }
    $upd->close();
}

// Hitung ulang total dibayar dan sisa tagihan
$stmt_dp = $conn->prepare("SELECT MAX(dp) AS dp_now FROM selling_products WHERE invoice_number = ?");
$stmt_dp->bind_param('s', $invoice);
$stmt_dp->execute();
$dp_now = (float)$stmt_dp->get_result()->fetch_assoc()['dp_now'];
$stmt_dp->close();

$stmt_tot = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total_angsuran_now FROM invoice_payments WHERE invoice_number = ?");
$stmt_tot->bind_param('s', $invoice);
$stmt_tot->execute();
$angsuran_now = (float)$stmt_tot->get_result()->fetch_assoc()['total_angsuran_now'];
$stmt_tot->close();

$total_dibayar_baru = $dp_now + $angsuran_now;
$sisa_baru          = max(0, $total_tagihan - $total_dibayar_baru);
$status_baru        = ($sisa_baru <= 0.01) ? 'Lunas' : 'DP';

// Update status di selling_products
$upd_status = $conn->prepare("UPDATE selling_products SET status = ? WHERE invoice_number = ?");
$upd_status->bind_param('ss', $status_baru, $invoice);
$upd_status->execute();
$upd_status->close();

echo json_encode([
    'success'       => true,
    'sisa'          => $sisa_baru,
    'status'        => $status_baru,
    'total_dibayar' => $total_dibayar_baru,
    'message'       => 'Pembayaran berhasil diperbarui.'
]);
exit;
