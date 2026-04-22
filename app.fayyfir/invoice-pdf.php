<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

// Ambil ID invoice
if (!isset($_GET["invoice_id"])) {
  die("Invoice ID tidak ditemukan.");
}
$invoice_id = intval($_GET["invoice_id"]);

// Ambil data invoice
$invoice = $conn->query("SELECT * FROM invoice_info WHERE id = $invoice_id")->fetch_assoc();
if (!$invoice) {
  die("Data invoice tidak ditemukan.");
}
$container_id = $invoice["container_id"];

// Ambil info kontainer dan produk
$container = $conn->query("
  SELECT c.*, p.name AS product_name
  FROM containers c
  LEFT JOIN products p ON c.product_id = p.id
  WHERE c.id = $container_id
")->fetch_assoc();
if (!$container) {
  die("Data kontainer tidak ditemukan.");
}

// Ambil transaksi
$transactions = $conn->query("
  SELECT t.*, s.name AS supplier_name
  FROM transactions t
  LEFT JOIN suppliers s ON t.supplier_id = s.id
  WHERE t.container_id = $container_id
");

$total_berat = 0;
while ($t = $transactions->fetch_assoc()) {
  $total_berat += $t["weight_kg"];
}
$total_harga = $total_berat * $container["selling_price"];

// Inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// ====== Background ======
$bg_image = 'assets/blank.png';
$pdf->Image($bg_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0, false, false, -1);

// 👉 Kunci background, konten berikutnya akan di atas
$pdf->setPageMark();

// Set margin untuk isi dokumen
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetY(40);
$pdf->SetFont("helvetica", "", 10);

// Hitungan Akumulasi
$pph = $total_harga * 0.0025;
$sub_total = $total_harga - $pph;
$dp = 0;
$remaining = $sub_total - $dp;

// Konten HTML
$html = '
<style>
  .garis {
    border: 1px solid #ddd;
    border-collapse: collapse;
  }
</style>
<br>
<table>
  <tr>
    <td><span style="text-align:left; color:#000; font-size: 10pt; font-weight: bold;">FROM : ' . nl2br(htmlspecialchars($invoice["invoice_from"])) . '</span></td>
    <td><span style="text-align:right; color:#FFC000; font-size: 28pt; font-weight: bolder;">INVOICE</span></td>
  </tr>
</table>
<br>
<br>
<table cellpadding="4">
  <tr>
    <td></td>
    <td class="garis" style="background-color: #fff;"><strong>INVOICE TO:</strong></td>
    <td class="garis" style="background-color: #fff;">' . nl2br(htmlspecialchars($invoice["invoice_to"])) . '</td>
    <td class="garis" style="background-color: #fff;"><strong>CONTAINER NO :</strong></td>
    <td class="garis" style="background-color: #fff;">' . nl2br(htmlspecialchars($invoice["container_no"])) . '</td>
  </tr>
  <tr>
    <td></td>
    <td class="garis" style="background-color: #eee;"><strong>ADDRESS :</strong></td>
    <td class="garis" style="background-color: #eee;">' . nl2br(htmlspecialchars($invoice["address"])) . '</td>
    <td class="garis" style="background-color: #eee;"><strong>INVOICE NO :</strong></td>
    <td class="garis" style="background-color: #eee;">' . nl2br(htmlspecialchars($invoice["invoice_no"])) . '</td>
  </tr>
  <tr>
    <td></td>
    <td class="garis" style="background-color: #fff;"><strong>CONTANER :</strong></td>
    <td class="garis" style="background-color: #fff;">' . nl2br(htmlspecialchars($invoice["containers"])) . '</td>
    <td class="garis" style="background-color: #fff;"><strong>DATE :</strong></td>
    <td class="garis" style="background-color: #fff;">' . date("d/m/Y", strtotime($invoice["invoice_date"])) . '</td>
  </tr>
</table>
<br><br>

<table cellpadding="5" width="100%">
  <thead>
    <tr style="background-color:#E2B712; color: #fff;">
      <th align="center" class="garis" style="width:45%;"><strong>ITEM DESCRIPTION</strong></th>
      <th align="center" class="garis" style="width:15%;"><strong>WEIGHT (Kg)</strong></th>
      <th align="center" class="garis" style="width:20%;"><strong>PRICE/KG</strong></th>
      <th align="center" class="garis" style="width:20%;"><strong>TOTAL</strong></th>
    </tr>
  </thead>
  <tbody>
    <tr style="background-color:#fff;">
      <td class="garis" style="width:45%;">' . nl2br(htmlspecialchars($invoice["description"])) . '</td>
      <td align="right" class="garis" style="width:15%;">' . number_format($total_berat, 0, ",", ".") . '</td>
      <td align="right" class="garis" style="width:20%;">Rp ' . number_format($container["selling_price"], 0, ",", ".") . '</td>
      <td align="right" class="garis" style="width:20%;">Rp ' . number_format($total_harga, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td style="width:45%;"></td>
      <td style="width:15%;"></td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Total :</td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Rp ' . number_format($total_harga, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td style="width:45%;"></td>
      <td style="width:15%;"></td>
      <td class="garis" style="width:20%; background-color:#fff; font-weight: bold; text-align: right;">PPh 0,25% :</td>
      <td class="garis" style="width:20%; background-color:#fff; font-weight: bold; text-align: right;">Rp ' . number_format($pph, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td style="width:45%;"></td>
      <td style="width:15%;"></td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Sub Total :</td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Rp ' . number_format($sub_total, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td style="width:45%;"></td>
      <td style="width:15%;"></td>
      <td class="garis" style="width:20%; background-color:#fff; font-weight: bold; text-align: right;">Down Payment :</td>
      <td class="garis" style="width:20%; background-color:#fff; font-weight: bold; text-align: right;">Rp ' . number_format($dp, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td style="width:45%;"></td>
      <td style="width:15%;"></td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Remaining :</td>
      <td class="garis" style="width:20%; background-color:#eee; font-weight: bold; text-align: right;">Rp ' . number_format($remaining, 0, ",", ".") . '</td>
    </tr>
  </tbody>
</table>

<br><br>
<table cellpadding="4">
  <tr>
    <td style="background-color: #fff;"><strong style="font-size: 12pt;">PAYMENT INFO</strong></td>
    <td></td>
  </tr>
  <tr>
    <td style="background-color: #eee;"><strong>Account Name :</strong> ' . htmlspecialchars($invoice["account_name"]) . '</td>
    <td></td>
  </tr>
  <tr>
    <td style="background-color: #fff;"><strong>Account Number :</strong> ' . htmlspecialchars($invoice["account_number"]) . '</td>
    <td></td>
  </tr>
  <tr>
    <td style="background-color: #eee;"><strong>Bank Name :</strong> ' . htmlspecialchars($invoice["bank_name"]) . '</td>
    <td></td>
  </tr>
  <tr>
    <td><br><br><br><strong style="font-size: 12pt; font-style: italic;">Thankyou for your business</strong><br><br><br><br><br></td>
  </tr>
</table>

<table hidden>
  <tr>
    <td></td>
    <td></td>
    <td><span style="text-align:center; color:#000; font-size: 10pt; font-weight: bold; right: 20px;"></span></td>
  </tr>
</table>
';

// Tampilkan isi HTML di atas background
$pdf->writeHTML($html, true, false, true, false, '');

// Output file
$pdf->Output("Invoice_" . $container["container_number"] . ".pdf", "I");