<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

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

// 1) Pendapatan / Total Sales
$total_pendapatan = 0;
$q_sales = $conn->query("
    SELECT COALESCE(SUM(total_selling), 0) AS total_sales
    FROM selling_products
    WHERE (DATE(selling_date) BETWEEN '$start_date' AND '$end_date')
       OR (selling_date BETWEEN '$start_datetime' AND '$end_datetime')
");
if ($q_sales && $row_sales = $q_sales->fetch_assoc()) {
  $total_pendapatan = (float)($row_sales['total_sales'] ?? 0);
}

// Fallback jika selling_products 0: hitung dari sistem Containers
if ($total_pendapatan == 0) {
  $q_containers = $conn->query("
        SELECT id, selling_price, lunas_at FROM containers
        WHERE status = 'lunas'
          AND ((DATE(lunas_at) BETWEEN '$start_date' AND '$end_date') OR (lunas_at BETWEEN '$start_datetime' AND '$end_datetime'))
    ");
  if ($q_containers && $q_containers->num_rows > 0) {
    while ($container = $q_containers->fetch_assoc()) {
      $cid = $container['id'];
      $sp = (float)$container['selling_price'];
      $q_tr = $conn->query("SELECT SUM(weight_kg) as tw FROM transactions WHERE container_id = $cid");
      $tw = ($q_tr && $r_tr = $q_tr->fetch_assoc()) ? (float)$r_tr['tw'] : 0;
      $total_pendapatan += ($tw * $sp);
    }
  }
}

// 2) Beban Pokok Penjualan (BPP / Biaya Produksi)
$total_bpp = 0;

// Opsi 1: Dari tabel productions (sistem Gaharu) pada rentang tanggal
$q_bpp1 = $conn->query("
    SELECT COALESCE(SUM(total_pro_expenses + total_pro_materials), 0) AS total_bpp
    FROM productions
    WHERE (DATE(production_date) BETWEEN '$start_date' AND '$end_date')
       OR (production_date BETWEEN '$start_datetime' AND '$end_datetime')
       OR (DATE(created_at) BETWEEN '$start_date' AND '$end_date')
       OR (created_at BETWEEN '$start_datetime' AND '$end_datetime')
");
if ($q_bpp1 && $r1 = $q_bpp1->fetch_assoc()) {
  $total_bpp = (float)($r1['total_bpp'] ?? 0);
}

// Opsi 2: Dari production_expenses + material_purchases jika Opsi 1 bernilai 0
if ($total_bpp == 0) {
  $q_pe = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM production_expenses WHERE (DATE(created_at) BETWEEN '$start_date' AND '$end_date') OR (created_at BETWEEN '$start_datetime' AND '$end_datetime')");
  $q_mp = $conn->query("SELECT COALESCE(SUM(total_price), 0) AS total FROM material_purchases WHERE (DATE(purchase_date) BETWEEN '$start_date' AND '$end_date') OR (DATE(created_at) BETWEEN '$start_date' AND '$end_date') OR (created_at BETWEEN '$start_datetime' AND '$end_datetime')");
  $pe_val = ($q_pe && $r = $q_pe->fetch_assoc()) ? (float)$r['total'] : 0;
  $mp_val = ($q_mp && $r = $q_mp->fetch_assoc()) ? (float)$r['total'] : 0;
  $total_bpp = $pe_val + $mp_val;
}

// Opsi 3: Dari sistem Containers (transactions + expenses) untuk container lunas di rentang tanggal
if ($total_bpp == 0) {
  $q_c_trx = $conn->query("
      SELECT COALESCE(SUM(t.grand_total), 0) AS total_trx
      FROM transactions t
      JOIN containers c ON t.container_id = c.id
      WHERE c.status = 'lunas'
        AND ((DATE(c.lunas_at) BETWEEN '$start_date' AND '$end_date') OR (c.lunas_at BETWEEN '$start_datetime' AND '$end_datetime'))
  ");
  $q_c_exp = $conn->query("
      SELECT COALESCE(SUM(e.amount), 0) AS total_exp
      FROM expenses e
      JOIN containers c ON e.container_id = c.id
      WHERE c.status = 'lunas'
        AND ((DATE(c.lunas_at) BETWEEN '$start_date' AND '$end_date') OR (c.lunas_at BETWEEN '$start_datetime' AND '$end_datetime'))
  ");
  $trx_val = ($q_c_trx && $r = $q_c_trx->fetch_assoc()) ? (float)$r['total_trx'] : 0;
  $exp_val = ($q_c_exp && $r = $q_c_exp->fetch_assoc()) ? (float)$r['total_exp'] : 0;
  $total_bpp = $trx_val + $exp_val;
}

// Opsi 4: Fallback umum jika ada penjualan tapi tanggal produksi tidak terikat rentang tanggal
if ($total_bpp == 0 && $total_pendapatan > 0) {
  $q_bpp_all = $conn->query("SELECT COALESCE(SUM(total_pro_expenses + total_pro_materials), 0) AS total_bpp FROM productions");
  if ($q_bpp_all && $r = $q_bpp_all->fetch_assoc()) {
    $total_bpp = (float)($r['total_bpp'] ?? 0);
  }
}

// 3) Laba Kotor
$laba_kotor = $total_pendapatan - $total_bpp;

// 4) Beban Operasional (Biaya Operasional Umum dari operational_costs)
$total_operasional = 0;
$q_op = $conn->query("
    SELECT COALESCE(SUM(jumlah), 0) AS total_op
    FROM operational_costs
    WHERE (DATE(tanggal) BETWEEN '$start_date' AND '$end_date')
       OR (DATE(created_at) BETWEEN '$start_date' AND '$end_date')
       OR (tanggal BETWEEN '$start_datetime' AND '$end_datetime')
       OR (created_at BETWEEN '$start_datetime' AND '$end_datetime')
");
if ($row_op = $q_op->fetch_assoc()) {
  $total_operasional = (float)($row_op['total_op'] ?? 0);
}

// 5) Laba Bersih (Sebelum PPh)
$laba_bersih = $laba_kotor - $total_operasional;

// 6) PPh 0,25%
$pph = $total_pendapatan * 0.0025;

// 7) Laba Bersih Setelah PPh
$laba_bersih_pph = $laba_bersih - $pph;

// format angka di PHP
$pph_fmt = number_format($pph, 0, ",", ".");
$total_pendapatan_fmt = number_format($total_pendapatan, 0, ",", ".");
$bpp_fmt = number_format($total_bpp, 0, ",", ".");
$laba_kotor_fmt = number_format($laba_kotor, 0, ",", ".");
$operasional_fmt = number_format($total_operasional, 0, ",", ".");
$laba_bersih_fmt = number_format($laba_bersih, 0, ",", ".");
$laba_bersih_pph_fmt = number_format($laba_bersih_pph, 0, ",", ".");

// inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 11);

// format periode human readable
$periode_print = date("d M Y", strtotime($start_date)) . " - " . date("d M Y", strtotime($end_date));
$tgl_cetak = date("d/m/Y");

// konten HTML
$html = <<<EOD
<h2 style="text-align:center;">LAPORAN LABA RUGI<br>FAYYFIR<br>$periode_print</h2>
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
    <td><strong>Jumlah Beban Operasional</strong></td>
    <td style="border-bottom: 1px solid #000;" align="right"><strong>Rp. $operasional_fmt</strong></td>
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
$pdf->Output("Laporan_Laba_Rugi_Custom_$start_date-$end_date.pdf", "I");
