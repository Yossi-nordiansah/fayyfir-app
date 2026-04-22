<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

// Ambil invoice_number
if (!isset($_GET["invoice"])) {
  die("Invoice number tidak ditemukan.");
}

$invoice_number = $conn->real_escape_string($_GET["invoice"]);

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
    SUM(s.dp) AS total_dp,
    s.status
  FROM selling_products s
  JOIN buyer_products b ON s.buyer_id = b.id
  WHERE s.invoice_number = '$invoice_number'
  GROUP BY s.invoice_number, s.buyer_id, b.name, b.address, b.contact, s.status
")->fetch_assoc();

if (!$invoice) {
  die("Data invoice tidak ditemukan.");
}

// Ambil detail produk dari invoice
$details = $conn->query("
  SELECT   
    ps.product_name,  
    sp.qty,  
    sp.price,  
    sp.total_selling,  
    u.symbol  
  FROM selling_products sp  
  JOIN product_stocks ps ON sp.product_id = ps.id  
  JOIN units u ON ps.unit_id = u.id  
  WHERE sp.invoice_number = '$invoice_number'  
");

$total_harga = $invoice["total_selling"];
$pph = $total_harga * 0.0025;
$sub_total = $total_harga - $pph;
$dp = $invoice["total_dp"];
$remaining = $total_harga - $dp;

// Inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Background (opsional, kalau ada)
$bg_image = 'assets/background-invoice.png';
if (file_exists($bg_image)) {
  $pdf->Image($bg_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
}

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetY(40);
$pdf->SetFont("helvetica", "", 10);

// HTML Invoice
$html = '
<style>
  .garis { border: 1px solid #ddd; border-collapse: collapse; }
</style>

<table>
  <tr>
    <td><span style="text-align:left; font-size: 10pt; font-weight: bold;">FROM : ABDUL HADI ALSHARIF</span></td>
    <td><span style="text-align:right; font-size: 28pt; font-weight: bolder; color:#000078;">INVOICE</span></td>
  </tr>
</table>
<br><br>

<table cellpadding="4">
  <tr>
    <td class="garis"><strong>INVOICE TO:</strong></td>
    <td class="garis">'. htmlspecialchars($invoice["buyer_name"]) .'</td>
    <td class="garis"><strong>INVOICE NO :</strong></td>
    <td class="garis">'. htmlspecialchars($invoice["invoice_number"]) .'</td>
  </tr>
  <tr>
    <td class="garis" style="background-color:#eee;"><strong>ADDRESS :</strong></td>
    <td class="garis" style="background-color:#eee;">'. nl2br(htmlspecialchars($invoice["address"])) .'</td>
    <td class="garis" style="background-color:#eee;"><strong>INVOICE DATE :</strong></td>
    <td class="garis" style="background-color:#eee;">'. date("d/m/Y", strtotime($invoice["selling_date"])) .'</td>
  </tr>
  <tr>
    <td class="garis"><strong>CONTACT :</strong></td>
    <td class="garis">'. htmlspecialchars($invoice["contact"]) .'</td>
    <td class="garis"><strong>STATUS :</strong></td>
    <td class="garis">'. htmlspecialchars($invoice["status"]) .'</td>
  </tr>
</table>

<br><br>
<table cellpadding="5">
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
  $html .= '
    <tr>
      <td class="garis">'. htmlspecialchars($d["product_name"]) .'</td>
      <td align="right" class="garis">'. number_format($d["qty"], 2, ",", ".") .' '. htmlspecialchars($d["symbol"]) .'</td>
      <td align="right" class="garis">Rp '. number_format($d["price"], 0, ",", ".") .'</td>
      <td align="right" class="garis">Rp '. number_format($d["total_selling"], 0, ",", ".") .'</td>
    </tr>';
}

$html .= '
    <tr>
      <td colspan="3" align="right" class="garis" style="background-color:#eee; font-weight:bold;">Total :</td>
      <td align="right" class="garis" style="background-color:#eee; font-weight:bold;">Rp '. number_format($total_harga, 0, ",", ".") .'</td>
    </tr>
    <!-- <tr>
      <td colspan="3" align="right" class="garis">PPh 0,25% :</td>
      <td align="right" class="garis">Rp '. number_format($pph, 0, ",", ".") .'</td>
    </tr>
    <tr>
      <td colspan="3" align="right" class="garis" style="background-color:#eee; font-weight:bold;">Sub Total :</td>
      <td align="right" class="garis" style="background-color:#eee; font-weight:bold;">Rp '. number_format($sub_total, 0, ",", ".") .'</td>
    </tr> -->
    <tr>
      <td colspan="3" align="right" class="garis">Down Payment :</td>
      <td align="right" class="garis">Rp '. number_format($dp, 0, ",", ".") .'</td>
    </tr>
    <tr>
      <td colspan="3" align="right" class="garis" style="background-color:#eee; font-weight:bold;">Remaining :</td>
      <td align="right" class="garis" style="background-color:#eee; font-weight:bold;">Rp '. number_format($remaining, 0, ",", ".") .'</td>
    </tr>
  </tbody>
</table>

<br><br>
<table cellpadding="4">
  <tr><td><strong style="font-size: 12pt;">PAYMENT INFO</strong></td></tr>
  <tr><td><strong>Bank Name :</strong> BCA</td></tr>
  <tr><td><strong>Account Name :</strong> Abdul Hadi Alsharif</td></tr>
  <tr><td><strong>Account Number :</strong> xxxx xxxx xx</td></tr>
  <tr><td><br><br><br><strong style="font-size: 12pt; font-style: italic;">Thank you for your business</strong></td></tr>
</table>
';

// Cetak PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("Invoice_" . $invoice["invoice_number"] . ".pdf", "I");