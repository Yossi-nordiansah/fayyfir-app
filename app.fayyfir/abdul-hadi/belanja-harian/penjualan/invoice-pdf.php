<?php
session_start();
require_once("../../tcpdf/tcpdf.php");
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// --- Ambil ID penjualan ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("ID penjualan tidak valid.");
}

/*
|============================================|
| AMBIL DATA PENJUALAN + PERHITUNGAN TERBARU |
|============================================|
| Logika disamakan dengan halaman index.php  |
*/

$sql = "
    SELECT  
        p.*,  
        b.nama_buyer,  
        b.alamat,
        SUM(k.biaya_exp) AS total_biaya_exp,

        -- Nama bahan dari batch siap jual
        (
            SELECT bm.nama_bahan  
            FROM bb_pembelian_awal pa2  
            LEFT JOIN bb_bahan_master bm ON bm.id = pa2.id_bahan  
            WHERE pa2.status = 'siap_jual'
            LIMIT 1
        ) AS nama_bahan,

        -- Kode batch
        (
            SELECT pa2.kode_batch
            FROM bb_pembelian_awal pa2
            WHERE pa2.status = 'siap_jual'
            LIMIT 1
        ) AS kode_batch,

        -- Total modal akhir / berat akhir: HPP per KG
        (
            SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
            FROM bb_pembelian_awal pa
            LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
            WHERE pa.status = 'siap_jual'
        ) AS harga_akhir_perkg,

        -- Total modal akhir batch
        (
            p.berat_jual *
            (
                SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
                FROM bb_pembelian_awal pa
                LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
                WHERE pa.status = 'siap_jual'
            )
        ) AS total_modal_akhir

    FROM bb_penjualan p
    LEFT JOIN bb_buyer b ON p.id_buyer = b.id
    LEFT JOIN bb_pengeluaran k ON k.id_penjualan = p.id
    WHERE p.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Data penjualan tidak ditemukan.");
}
$data = $result->fetch_assoc();

// --- Hitung total untuk invoice ---
$grand_total = floatval($data["total_penjualan"]);

// ======================================================================
//  MULAI GENERATE PDF (UI/UX TIDAK DIUBAH SAMA SEKALI)
// ======================================================================

$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Background invoice
$bg_image = '../assets/img/background-invoice2.png';
$pdf->Image($bg_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0, false, false, -1);

$pdf->setPageMark();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetY(40);
$pdf->SetFont("helvetica", "", 10);

// ---------- ISI HTML ----------
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
    <td><span style="text-align:left; color:#000; font-size: 10pt; font-weight: bold;">FROM : Fayyfir</span></td>
    <td><span style="text-align:right; color:#FFC000; font-size: 28pt; font-weight: bolder;">INVOICE</span></td>
  </tr>
</table>

<br><br>

<table cellpadding="4">

  <tr>
    <td></td><td></td>
    <td class="garis" style="background-color:#eee;"><strong>INVOICE TO:</strong></td>
    <td class="garis" style="background-color:#eee;">'.nl2br(htmlspecialchars($data["nama_buyer"])).'</td>
    <td class="garis" style="background-color:#eee;"><strong>INVOICE NO :</strong></td>
    <td class="garis" style="background-color:#eee;">'.nl2br(htmlspecialchars($data["no_invoice"])).'</td>
  </tr>

  <tr>
    <td></td><td></td>
    <td class="garis"><strong>NAMA BAHAN :</strong></td>
    <td class="garis">'.nl2br(htmlspecialchars($data["nama_bahan"])).'</td>
    <td class="garis"><strong>DATE :</strong></td>
    <td class="garis">'.date("d/m/Y", strtotime($data["tanggal_jual"])).'</td>
  </tr>
</table>

<br><br>

<table cellpadding="5">
  <thead>
    <tr style="background-color:#E2B712; color:#fff;">
      <th align="center" class="garis"><strong>DESCRIPTION</strong></th>
      <th align="center" class="garis"><strong>WEIGHT (Kg)</strong></th>
      <th align="center" class="garis"><strong>PRICE/KG</strong></th>
      <th align="center" class="garis"><strong>TOTAL</strong></th>
    </tr>
  </thead>

  <tbody>
    <tr style="background-color:#fff;">
      <td class="garis">'.$data["no_invoice"].' - '.htmlspecialchars($data["nama_bahan"]).'</td>
      <td align="right" class="garis">'.number_format($data['berat_jual'], 2, ",", ".").'</td>
      <td align="right" class="garis">Rp '.number_format($data["harga_jual_per_kg"], 2, ",", ".").'</td>
      <td align="right" class="garis">Rp '.number_format($data["total_penjualan"], 0, ",", ".").'</td>
    </tr>

    <tr>
      <td></td><td></td>
      <td class="garis" style="background-color:#eee; font-weight:bold; text-align:right;">Total :</td>
      <td class="garis" style="background-color:#eee; font-weight:bold; text-align:right;">Rp '.number_format($grand_total, 0, ",", ".").'</td>
    </tr>
  </tbody>
</table>

<br><br>

<table cellpadding="4">
  <tr>
    <td><strong style="font-size:12pt;">PAYMENT INFO</strong></td>
  </tr>

  <tr><td><strong>Account Name :</strong></td></tr>
  <tr><td><strong>Account Number :</strong></td></tr>
  <tr><td><strong>Bank Name :</strong></td></tr>

  <tr>
    <td><br><br><strong style="font-size:12pt; font-style:italic;">Thankyou for your business</strong></td>
  </tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("Invoice_" . $data["kode_batch"] . ".pdf", "I");