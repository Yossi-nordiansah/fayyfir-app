<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

// Ambil invoice_number
if (!isset($_GET["invoice"])) {
  die("Invoice number tidak ditemukan.");
}

$invoice_number = $conn->real_escape_string($_GET["invoice"]);

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

// Ambil data utama invoice
$invoice = $conn->query("
  SELECT 
    s.invoice_number,
    s.buyer_id,
    b.name AS buyer_name,
    b.address,
    b.contact,
    MAX(s.selling_date) AS selling_date,
    SUM(s.total_selling) AS total_selling,
    MAX(s.dp) AS total_dp,
    MAX(s.status) AS status
  FROM selling_products s
  JOIN buyer_products b ON s.buyer_id = b.id
  WHERE s.invoice_number = '$invoice_number'
  GROUP BY s.invoice_number, s.buyer_id, b.name, b.address, b.contact
")->fetch_assoc();

if (!$invoice) {
  die("Data invoice tidak ditemukan.");
}

// Ambil detail produk dari invoice
$details = $conn->query("
  SELECT   
    ps.product_name,  
    sp.qty,  
    sp.qty_shrinkage,
    sp.price,  
    sp.total_selling,  
    u.symbol  
  FROM selling_products sp  
  JOIN product_stocks ps ON sp.product_id = ps.id  
  JOIN units u ON ps.unit_id = u.id  
  WHERE sp.invoice_number = '$invoice_number'  
");

// Ambil riwayat angsuran dari invoice_payments
$payments_q = $conn->query("
    SELECT payment_date, jumlah, keterangan
    FROM invoice_payments
    WHERE invoice_number = '$invoice_number'
    ORDER BY payment_date ASC, id ASC
");
$payments = [];
$total_angsuran = 0;
if ($payments_q) {
  while ($p = $payments_q->fetch_assoc()) {
    $payments[] = $p;
    $total_angsuran += (float)$p['jumlah'];
  }
}

$total_harga    = (float)$invoice["total_selling"];
$dp             = (float)$invoice["total_dp"];
$total_dibayar  = $dp + $total_angsuran;
$raw_sisa       = max(0, $total_harga - $total_dibayar);
$is_lunas       = (strcasecmp($invoice["status"] ?? '', 'lunas') === 0) || ($raw_sisa <= 0.01);

if ($is_lunas) {
  $status_now   = 'Lunas';
  $sisa_tagihan = 0;
  if ($dp <= 0 && $total_angsuran <= 0) {
    $total_dibayar = $total_harga;
  }
} else {
  $status_now   = 'DP';
  $sisa_tagihan = $raw_sisa;
}

// Inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Background
$bg_image = 'assets/Kop Surat Fayyfir New.png';
if (file_exists($bg_image)) {
  $pdf->Image($bg_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  $pdf->setPageMark();
}

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetY(40);
$pdf->SetFont("helvetica", "", 10);

// HTML Invoice
$html = '
<style>
  .garis { border: 1px solid #000; border-collapse: collapse; }
</style>

<table>
  <tr>
    <td><span style="text-align:left; font-size: 10pt; font-weight: bold;">FROM : ABDUL HADI ALSHARIF</span></td>
    <td><span style="text-align:right; font-size: 28pt; font-weight: bolder; color:#000078;">INVOICE</span></td>
  </tr>
</table>
<br><br>

<table border="1" cellpadding="4">
  <tr>
    <td class="garis"><strong>INVOICE TO:</strong></td>
    <td class="garis">' . htmlspecialchars($invoice["buyer_name"]) . '</td>
    <td class="garis"><strong>INVOICE NO :</strong></td>
    <td class="garis">' . htmlspecialchars($invoice["invoice_number"]) . '</td>
  </tr>
  <tr>
    <td class="garis" style="background-color:#eee;"><strong>ADDRESS :</strong></td>
    <td class="garis" style="background-color:#eee;">' . nl2br(htmlspecialchars($invoice["address"])) . '</td>
    <td class="garis" style="background-color:#eee;"><strong>INVOICE DATE :</strong></td>
    <td class="garis" style="background-color:#eee;">' . date("d/m/Y", strtotime($invoice["selling_date"])) . '</td>
  </tr>
  <tr>
    <td class="garis"><strong>CONTACT :</strong></td>
    <td class="garis">' . htmlspecialchars($invoice["contact"]) . '</td>
    <td class="garis"><strong>STATUS :</strong></td>
    <td class="garis"><strong>' . htmlspecialchars($status_now) . '</strong></td>
  </tr>
</table>

<br><br>
<table border="1" cellpadding="5">
  <thead>
    <tr style="background-color:#000078; color: #fff;">
      <th align="center" class="garis"><strong>PRODUCT</strong></th>
      <th align="center" class="garis"><strong>QTY</strong></th>
      <th align="center" class="garis"><strong>PRICE</strong></th>
      <th align="center" class="garis"><strong>TOTAL</strong></th>
    </tr>
  </thead>
  <tbody>';

while ($d = $details->fetch_assoc()) {
  $shrinkage = (float)($d["qty_shrinkage"] ?? 0);
  $net_qty = max(0.0, (float)$d["qty"] - $shrinkage);
  $qty_text = number_format($net_qty, 2, ",", ".") . ' ' . htmlspecialchars($d["symbol"]);
  if ($shrinkage > 0) {
    $qty_text .= '<br><span style="font-size:8pt; color:#666;">(Kirim: ' . number_format($d["qty"], 2, ",", ".") . ', Susut: ' . number_format($shrinkage, 2, ",", ".") . ')</span>';
  }

  $html .= '
    <tr>
      <td class="garis">' . htmlspecialchars($d["product_name"]) . '</td>
      <td align="right" class="garis">' . $qty_text . '</td>
      <td align="right" class="garis">Rp ' . number_format($d["price"], 0, ",", ".") . '</td>
      <td align="right" class="garis">Rp ' . number_format($d["total_selling"], 0, ",", ".") . '</td>
    </tr>';
}

$html .= '
    <tr>
      <td colspan="3" align="right" class="garis" style="background-color:#eee; font-weight:bold;">Total :</td>
      <td align="right" class="garis" style="background-color:#eee; font-weight:bold;">Rp ' . number_format($total_harga, 0, ",", ".") . '</td>
    </tr>';

if ($dp > 0) {
  $html .= '
    <tr>
      <td colspan="3" align="right" class="garis">Down Payment (DP) :</td>
      <td align="right" class="garis">Rp ' . number_format($dp, 0, ",", ".") . '</td>
    </tr>';
}

// Tampilkan baris angsuran jika ada
if (!empty($payments)) {
  foreach ($payments as $i => $pay) {
    $ket_label = htmlspecialchars($pay['keterangan'] ?: ('Angsuran ' . ($i + 1)));
    $html .= '
    <tr>
      <td colspan="3" align="right" class="garis">' . $ket_label . ' (' . date('d/m/Y', strtotime($pay['payment_date'])) . ') :</td>
      <td align="right" class="garis">Rp ' . number_format($pay['jumlah'], 0, ",", ".") . '</td>
    </tr>';
  }
}

$html .= '
    <tr>
      <td colspan="3" align="right" class="garis" style="background-color:#eee; font-weight:bold;">Total Dibayar :</td>
      <td align="right" class="garis" style="background-color:#eee; font-weight:bold;">Rp ' . number_format($total_dibayar, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td colspan="3" align="right" class="garis" style="font-weight:bold; color: ' . ($sisa_tagihan > 0 ? '#cc0000' : '#006600') . ';">Sisa Tagihan :</td>
      <td align="right" class="garis" style="font-weight:bold; color: ' . ($sisa_tagihan > 0 ? '#cc0000' : '#006600') . ';">Rp ' . number_format($sisa_tagihan, 0, ",", ".") . '</td>
    </tr>
  </tbody>
</table>';

$html .= '
';

// Cetak PDF
if (ob_get_length()) {
  ob_clean();
}
$output_mode = (isset($_GET['download']) && $_GET['download'] == '1') ? 'D' : 'I';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("Invoice_" . $invoice["invoice_number"] . ".pdf", $output_mode);
