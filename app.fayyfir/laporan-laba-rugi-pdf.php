<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

// cek periode
if (!isset($_GET["periode"])) {
    die("Parameter periode tidak ditemukan.");
}
$periode = $_GET["periode"]; // contoh: 2025-06

// hitung periode payroll
list($year, $month) = explode("-", $periode);
$periode_start = new DateTime("$year-$month-01");
$periode_end   = new DateTime("$year-$month-01");
$periode_end->modify('last day of this month');

// inisialisasi total
$total_pendapatan = 0;
$total_bpp = 0;

// ambil semua container yang statusnya lunas dan di periode tersebut
$query_containers = $conn->query("
    SELECT id, selling_price, lunas_at
    FROM containers
    WHERE status = 'lunas'
      AND lunas_at BETWEEN '{$periode_start->format('Y-m-d')}' AND '{$periode_end->format('Y-m-d')}'
");

while ($container = $query_containers->fetch_assoc()) {
    $container_id = $container['id'];
    $selling_price = $container['selling_price'];

    // ambil total berat dan total price dari transaksi, join ke containers ✅
    $q_trans = $conn->query("
        SELECT SUM(t.weight_kg) as total_weight, SUM(t.grand_total) as total_price
        FROM transactions t
        JOIN containers c ON t.container_id = c.id
        WHERE t.container_id = $container_id
          AND c.status = 'lunas'
    ");
    $trx = $q_trans->fetch_assoc();
    $total_weight = $trx['total_weight'] ?? 0;
    $total_price_trans = $trx['total_price'] ?? 0;

    $pendapatan_container = $total_weight * $selling_price;

    // expenses per container, join ke containers ✅
    $q_exp = $conn->query("
        SELECT SUM(e.amount) as total_expense
        FROM expenses e
        JOIN containers c ON e.container_id = c.id
        WHERE e.container_id = $container_id
          AND c.status = 'lunas'
    ");
    $exp = $q_exp->fetch_assoc();
    $total_expenses = $exp['total_expense'] ?? 0;

    $bpp_container = $total_price_trans + $total_expenses;

    // akumulasi
    $total_pendapatan += $pendapatan_container;
    $total_bpp += $bpp_container;
}

// laba kotor
$laba_kotor = $total_pendapatan - $total_bpp;

// beban operasional (tidak terkait container, jadi tetap by periode)
$query_operasional = $conn->query("
    SELECT SUM(jumlah) as total_operasional
    FROM operational_costs
    WHERE created_at BETWEEN '{$periode_start->format('Y-m-d')}' AND '{$periode_end->format('Y-m-d')}'
");
$data_operasional = $query_operasional->fetch_assoc();
$total_operasional = $data_operasional['total_operasional'] ?? 0;

// laba bersih
$laba_bersih = $laba_kotor - $total_operasional;

// pph 0,25%
$pph = $total_pendapatan * 0.0025;

// laba bersih setelah pph
$laba_bersih_pph = $laba_bersih - $pph;

// format angka
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

// format periode
$periode_print = $periode_start->format('d M Y') . " - " . $periode_end->format('d M Y');
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
    <td>Penjualan</td>
    <td align="right">Rp. $total_pendapatan_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Pendapatan</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $total_pendapatan_fmt</strong></td>
  </tr>
  <tr><td colspan="2"><br></td></tr>
  <tr>
    <td colspan="2"><strong>BEBAN POKOK PENJUALAN</strong></td>
  </tr>
  <tr>
    <td>Beban Pokok Penjualan</td>
    <td align="right">Rp. $bpp_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Beban Pokok Penjualan</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $bpp_fmt</strong></td>
  </tr>
  <tr>
    <td><strong>LABA KOTOR</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $laba_kotor_fmt</strong></td>
  </tr>
  <tr><td colspan="2"><br></td></tr>
  <tr>
    <td colspan="2"><strong>BEBAN OPERASIONAL</strong></td>
  </tr>
  <tr>
    <td>Beban Operasional</td>
    <td align="right">Rp. $operasional_fmt</td>
  </tr>
  <tr>
    <td><strong>Jumlah Beban Operasional</strong></td>
    <td style="border-top: 1px solid #000;" align="right"><strong>Rp. $operasional_fmt</strong></td>
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
$pdf->Output("Laporan_Laba_Rugi_$periode.pdf", "I");