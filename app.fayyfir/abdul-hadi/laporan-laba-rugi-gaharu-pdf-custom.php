<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once "tcpdf/tcpdf.php";
require_once "config.php";
require_once "helpers-fifo-gaharu.php";

// ambil parameter tanggal
if (!isset($_GET["start_date"]) || !isset($_GET["end_date"])) {
  die("Parameter tanggal tidak lengkap.");
}

$start_date = $_GET["start_date"];
$end_date   = $_GET["end_date"];

// validasi tanggal
if (!strtotime($start_date) || !strtotime($end_date)) {
  die("Format tanggal tidak valid.");
}

// Format datetime range for SQL queries
$start_datetime = date("Y-m-d 00:00:00", strtotime($start_date));
$end_datetime   = date("Y-m-d 23:59:59", strtotime($end_date));
$start_date_db  = date("Y-m-d", strtotime($start_date));
$end_date_db    = date("Y-m-d", strtotime($end_date));

// Pastikan koneksi mengarah ke database yang memiliki data Gaharu jika tersedia
if (function_exists('get_conn2')) {
  $test_sp = $conn->query("SELECT COUNT(*) FROM selling_products");
  if (!$test_sp || (int)$test_sp->fetch_row()[0] === 0) {
    $conn2_candidate = get_conn2();
    $test_sp2 = $conn2_candidate->query("SELECT COUNT(*) FROM selling_products");
    if ($test_sp2 && (int)$test_sp2->fetch_row()[0] > 0) {
      $conn = $conn2_candidate;
    }
  }
}

// 1) Pendapatan / Total Sales & 2) Beban Pokok Penjualan (BPP - Metode FIFO)
$total_pendapatan = 0.0;
$total_bpp        = 0.0;

// Ambil data penjualan & alokasi HPP FIFO riil
$data_gaharu = getLabaRugiGaharuData($conn);
$all_sales   = $data_gaharu['all_processed_sales'] ?? [];

$count_sales = 0;
foreach ($all_sales as $s) {
  $s_date = !empty($s['selling_date']) ? date('Y-m-d', strtotime($s['selling_date'])) : '';
  if ($s_date >= $start_date_db && $s_date <= $end_date_db) {
    $total_pendapatan += (float)$s['total_selling'];
    $total_bpp        += (float)$s['bpp'];
    $count_sales++;
  }
}

// Fallback jika selling_products 0: hitung dari sistem Containers (DB1)
if ($count_sales === 0 && $total_pendapatan == 0) {
  $q_containers = $conn->query("
      SELECT id, selling_price, lunas_at FROM containers
      WHERE status = 'lunas'
        AND ((DATE(lunas_at) BETWEEN '$start_date_db' AND '$end_date_db') OR (lunas_at BETWEEN '$start_datetime' AND '$end_datetime'))
  ");
  if ($q_containers && $q_containers->num_rows > 0) {
    while ($container = $q_containers->fetch_assoc()) {
      $cid = $container['id'];
      $sp  = (float)$container['selling_price'];
      $q_tr = $conn->query("SELECT SUM(weight_kg) as tw, SUM(grand_total) as gt FROM transactions WHERE container_id = $cid");
      $r_tr = $q_tr ? $q_tr->fetch_assoc() : null;
      $tw   = (float)($r_tr['tw'] ?? 0);
      $gt   = (float)($r_tr['gt'] ?? 0);
      $q_ex = $conn->query("SELECT SUM(amount) as ea FROM expenses WHERE container_id = $cid");
      $ea   = (float)(($q_ex && $r_ex = $q_ex->fetch_assoc()) ? ($r_ex['ea'] ?? 0) : 0);

      $total_pendapatan += ($tw * $sp);
      $total_bpp        += ($gt + $ea);
    }
  }
}

// 3) Laba Kotor
$laba_kotor = $total_pendapatan - $total_bpp;

// 4) Beban Operasional Bulanan Gaharu (dari tabel gaharu_monthly_expenses) - Proportional calculation
$total_pengeluaran_bulanan_fix     = 0;
$total_pengeluaran_bulanan_variatif = 0;

$q_bulanan = $conn->query("
  SELECT bulan, jenis, jumlah
  FROM gaharu_monthly_expenses
  WHERE bulan >= DATE_FORMAT('$start_date_db','%Y-%m')
    AND bulan <= DATE_FORMAT('$end_date_db','%Y-%m')
");

if ($q_bulanan) {
  while ($row = $q_bulanan->fetch_assoc()) {
    $year_month = $row['bulan'];
    $jenis      = $row['jenis'];
    $jumlah     = (float)$row['jumlah'];

    // Hitung proporsi hari dalam range tanggal
    $month_start   = date("Y-m-01", strtotime($year_month . "-01"));
    $month_end     = date("Y-m-t", strtotime($year_month . "-01"));
    $days_in_month = (int)date("t", strtotime($year_month . "-01"));

    $overlap_start = max($month_start, $start_date_db);
    $overlap_end   = min($month_end, $end_date_db);

    $proportion = 0;
    if ($overlap_start <= $overlap_end) {
      $overlap_days = (strtotime($overlap_end) - strtotime($overlap_start)) / 86400 + 1;
      $proportion   = $overlap_days / $days_in_month;
    }

    $proportional_amount = $jumlah * $proportion;

    if ($jenis === 'fix') {
      $total_pengeluaran_bulanan_fix += $proportional_amount;
    } else {
      $total_pengeluaran_bulanan_variatif += $proportional_amount;
    }
  }
}

$total_beban_operasional = $total_pengeluaran_bulanan_fix + $total_pengeluaran_bulanan_variatif;

// 5) Laba Bersih (Sebelum PPh)
$laba_bersih = $laba_kotor - $total_beban_operasional;

// 6) PPh 0,25%
$pph = $total_pendapatan * 0.0025;

// 7) Laba Bersih Setelah PPh
$laba_bersih_pph = $laba_bersih - $pph;

// format angka di PHP
$pph_fmt                        = number_format($pph, 0, ",", ".");
$total_pendapatan_fmt           = number_format($total_pendapatan, 0, ",", ".");
$bpp_fmt                        = number_format($total_bpp, 0, ",", ".");
$laba_kotor_fmt                 = number_format($laba_kotor, 0, ",", ".");
$pengeluaran_bulanan_fix_fmt    = number_format($total_pengeluaran_bulanan_fix, 0, ",", ".");
$pengeluaran_bulanan_var_fmt    = number_format($total_pengeluaran_bulanan_variatif, 0, ",", ".");
$total_beban_operasional_fmt    = number_format($total_beban_operasional, 0, ",", ".");
$laba_bersih_fmt                = number_format($laba_bersih, 0, ",", ".");
$laba_bersih_pph_fmt            = number_format($laba_bersih_pph, 0, ",", ".");

// inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 11);

// format periode human readable
$periode_print = date("d M Y", strtotime($start_date)) . " - " . date("d M Y", strtotime($end_date));
$tgl_cetak     = date("d/m/Y");

// konten HTML persis seperti desain awal
$html = <<<EOD
<h2 style="text-align:center;">LAPORAN LABA RUGI GAHARU<br>FAYYFIR<br>$periode_print</h2>
<hr>
<br>
<table cellspacing="0" cellpadding="4" width="100%">
  <tr>
    <td colspan="2"><strong>PENDAPATAN</strong></td>
  </tr>
  <tr>
    <td><strong>Jumlah Pendapatan</strong></td>
    <td style="border-bottom: 1px solid #000;" align="right"><strong>Rp. $total_pendapatan_fmt</strong></td>
  </tr>
  <tr><td colspan="2"><br></td></tr>
  <tr>
    <td colspan="2"><strong>BEBAN POKOK PENJUALAN</strong></td>
  </tr>
  <tr>
    <td><strong>Jumlah Beban Pokok Penjualan</strong></td>
    <td style="border-bottom: 1px solid #000;" align="right"><strong>Rp. $bpp_fmt</strong></td>
  </tr>
  <tr>
    <td><strong>LABA KOTOR</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $laba_kotor_fmt</strong></td>
  </tr>
  <tr><td colspan="2"><br></td></tr>
  <tr>
    <td colspan="2"><strong>BEBAN OPERASIONAL BULANAN GAHARU</strong></td>
  </tr>
  <tr>
    <td style="padding-left:16px;">Pengeluaran tetap (Gaji dan lainnya)</td>
    <td align="right">Rp. $pengeluaran_bulanan_fix_fmt</td>
  </tr>
  <tr>
    <td style="padding-left:16px;">Pengeluaran tidak tetap</td>
    <td align="right">Rp. $pengeluaran_bulanan_var_fmt</td>
  </tr>
  <tr>
    <td style="padding-left:16px;"><strong>Total Beban Operasional</strong></td>
    <td style="border-bottom: 1px solid #000;" align="right"><strong>Rp. $total_beban_operasional_fmt</strong></td>
  </tr>
  <tr><td colspan="2"><br></td></tr>
  <tr>
    <td colspan="2"><strong>LABA BERSIH</strong></td>
  </tr>
  <tr>
    <td>Laba Bersih (Sebelum PPh)</td>
    <td align="right">Rp. $laba_bersih_fmt</td>
  </tr>
  <tr>
    <td>PPh</td>
    <td align="right">Rp. $pph_fmt</td>
  </tr>
  <tr>
    <td><strong>LABA BERSIH</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $laba_bersih_pph_fmt</strong></td>
  </tr>
</table>
<br><br>
<hr>
<table width="100%">
  <tr>
    <td><small>Fayyfir System Report</small></td>
    <td align="right"><small>Dicetak tanggal $tgl_cetak &nbsp; | &nbsp; halaman 1</small></td>
  </tr>
</table>
EOD;

// tulis ke PDF
$pdf->writeHTML($html, true, false, true, false, "");

// output
$pdf->Output("Laporan_Laba_Rugi_Gaharu_Custom_$start_date-$end_date.pdf", "I");
