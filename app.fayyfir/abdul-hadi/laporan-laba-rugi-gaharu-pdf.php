<?php
session_start();
require_once "tcpdf/tcpdf.php";
require_once "config.php";
require_once "helpers-fifo-gaharu.php";

if (!isset($_SESSION["user_id"])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

if (!isset($_GET["periode"]) || !preg_match('/^\d{4}-\d{2}$/', $_GET["periode"])) {
    die("Parameter periode tidak valid.");
}

$periode = $_GET["periode"]; // contoh: 2026-03
list($year, $month) = explode("-", $periode);

$periode_start = new DateTime("$year-$month-01");
$periode_end   = new DateTime("$year-$month-01");
$periode_end->modify('last day of this month');

$data = getLabaRugiGaharuData($conn, (int)$year);

$total_pendapatan  = (float)($data['pendapatan_per_bulan'][$periode] ?? 0.0);
$total_bpp         = (float)($data['bpp_per_bulan'][$periode] ?? 0.0);
$laba_kotor        = (float)($data['laba_kotor_per_bulan'][$periode] ?? 0.0);
$total_operasional = (float)($data['operasional_per_bulan'][$periode] ?? 0.0);
$laba_bersih       = (float)($data['laba_bersih_per_bulan'][$periode] ?? 0.0);

// Hitung breakdown operasional (tetap vs variabel)
$op_items = $data['operasional_items_by_month'][$periode] ?? [];
$tot_op_fix = 0;
$tot_op_nonfix = 0;
foreach ($op_items as $op) {
    if ($op['jenis'] === 'fix') {
        $tot_op_fix += (float)$op['jumlah'];
    } else {
        $tot_op_nonfix += (float)$op['jumlah'];
    }
}

// Pajak PPh Final UMKM 0,25%
$pph = $total_pendapatan * 0.0025;
$laba_bersih_pph = $laba_bersih - $pph;

// Format angka
$total_pendapatan_fmt  = number_format($total_pendapatan, 0, ",", ".");
$bpp_fmt               = number_format($total_bpp, 0, ",", ".");
$laba_kotor_fmt        = number_format($laba_kotor, 0, ",", ".");
$tot_op_fix_fmt        = number_format($tot_op_fix, 0, ",", ".");
$tot_op_nonfix_fmt     = number_format($tot_op_nonfix, 0, ",", ".");
$total_operasional_fmt = number_format($total_operasional, 0, ",", ".");
$laba_bersih_fmt       = number_format($laba_bersih, 0, ",", ".");
$pph_fmt               = number_format($pph, 0, ",", ".");
$laba_bersih_pph_fmt   = number_format($laba_bersih_pph, 0, ",", ".");

// Inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("Fayyfir System");
$pdf->SetAuthor("Fayyfir App - Abdul Hadi");
$pdf->SetTitle("Laporan Laba Rugi Kayu Gaharu - $periode");
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 10);

$periode_print = $periode_start->format('d F Y') . " - " . $periode_end->format('d F Y');
$tgl_cetak = date("d/m/Y H:i");

$html = <<<EOD
<table width="100%" cellpadding="2">
  <tr>
    <td align="center">
      <h2 style="font-size: 15pt; margin-bottom: 2px;">LAPORAN LABA RUGI KAYU GAHARU</h2>
      <h3 style="font-size: 12pt; color: #333; margin-top: 0;">FAYYFIR APP - ABDUL HADI</h3>
      <p style="font-size: 9pt; color: #555;">Periode: <strong>$periode_print</strong></p>
    </td>
  </tr>
</table>
<hr style="border: 0; border-top: 1px solid #222;">
<br>

<table cellspacing="0" cellpadding="4" width="100%" style="font-size: 10pt;">
  <!-- I. PENDAPATAN -->
  <tr style="background-color: #f2f2f2;">
    <td colspan="2"><strong>I. PENDAPATAN PENJUALAN GAHARU</strong></td>
  </tr>
  <tr>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;Penjualan Produk Gaharu</td>
    <td align="right">Rp. $total_pendapatan_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Pendapatan Usaha</strong></td>
    <td style="border-top: 1px solid #444;" align="right"><strong>Rp. $total_pendapatan_fmt</strong></td>
  </tr>

  <tr><td colspan="2"><br></td></tr>

  <!-- II. BPP -->
  <tr style="background-color: #f2f2f2;">
    <td colspan="2"><strong>II. BEBAN POKOK PENJUALAN (BPP) - FIFO</strong></td>
  </tr>
  <tr>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;Beban Pokok Penjualan Produk Terjual (FIFO)</td>
    <td align="right">Rp. $bpp_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Beban Pokok Penjualan</strong></td>
    <td style="border-top: 1px solid #444;" align="right"><strong>Rp. $bpp_fmt</strong></td>
  </tr>

  <tr><td colspan="2"><br></td></tr>

  <!-- III. LABA KOTOR -->
  <tr style="background-color: #e8eaf6;">
    <td><strong>III. LABA KOTOR (GROSS PROFIT)</strong></td>
    <td align="right"><strong>Rp. $laba_kotor_fmt</strong></td>
  </tr>

  <tr><td colspan="2"><br></td></tr>

  <!-- IV. BEBAN OPERASIONAL -->
  <tr style="background-color: #f2f2f2;">
    <td colspan="2"><strong>IV. BEBAN OPERASIONAL BULANAN GAHARU</strong></td>
  </tr>
  <tr>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;Pengeluaran Biaya Tetap (Fix)</td>
    <td align="right">Rp. $tot_op_fix_fmt</td>
  </tr>
  <tr>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;Pengeluaran Biaya Variatif (Tidak Fix)</td>
    <td align="right">Rp. $tot_op_nonfix_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Beban Operasional</strong></td>
    <td style="border-top: 1px solid #444;" align="right"><strong>Rp. $total_operasional_fmt</strong></td>
  </tr>

  <tr><td colspan="2"><br></td></tr>

  <!-- V. LABA BERSIH -->
  <tr style="background-color: #e8f5e9;">
    <td><strong>V. LABA BERSIH SEBELUM PPh</strong></td>
    <td align="right"><strong>Rp. $laba_bersih_fmt</strong></td>
  </tr>
  <tr>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;Estimasi PPh Final (0.25%)</td>
    <td align="right">Rp. $pph_fmt</td>
  </tr>
  <tr style="background-color: #c8e6c9;">
    <td><strong>VI. LABA BERSIH SETELAH ESTIMASI PPh</strong></td>
    <td style="border-top: 1px solid #444;" align="right"><strong>Rp. $laba_bersih_pph_fmt</strong></td>
  </tr>
</table>

<br><br>
<p style="font-size: 8pt; color: #666; font-style: italic;">
* Catatan: Beban Pokok Penjualan dihitung secara dinamis menggunakan metode FIFO (First-In, First-Out) berdasarkan urutan batch produksi dan alokasi harga pokok dari barang yang laku terjual.
</p>

<br><br>
<hr style="border: 0; border-top: 1px dashed #aaa;">
<table width="100%" style="font-size: 8pt; color: #666;">
  <tr>
    <td>Fayyfir System — Abdul Hadi Kayu Gaharu</td>
    <td align="right">Dicetak: $tgl_cetak &nbsp; | &nbsp; Halaman 1</td>
  </tr>
</table>
EOD;

$pdf->writeHTML($html, true, false, true, false, "");
$pdf->Output("Laporan_Laba_Rugi_Gaharu_$periode.pdf", "I");
