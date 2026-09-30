<?php

/**
 * transaksi-angsuran-invoice.php
 * Cetak bukti pembayaran / invoice angsuran per transaksi
 * 
 * Parameter GET:
 * - invoice    : string (nomor invoice, wajib)
 * - payment_id : int (id pembayaran di invoice_payments)
 * - type       : string ('dp' jika cetak bukti DP Awal)
 * - download   : int (1 untuk langsung unduh PDF)
 */

require_once("tcpdf/tcpdf.php");
require_once("config.php");

if (!isset($_GET["invoice"])) {
  die("Invoice number tidak ditemukan.");
}

$invoice_number = $conn->real_escape_string($_GET["invoice"]);
$payment_id     = isset($_GET["payment_id"]) ? (int)$_GET["payment_id"] : 0;
$type           = strtolower($_GET["type"] ?? '');

// Ambil data invoice & buyer
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

$total_harga = (float)$invoice["total_selling"];
$dp          = (float)$invoice["total_dp"];

// Ambil semua riwayat angsuran
$payments_q = $conn->query("
    SELECT id, payment_date, jumlah, keterangan
    FROM invoice_payments
    WHERE invoice_number = '$invoice_number'
    ORDER BY payment_date ASC, id ASC
");
$all_payments = [];
if ($payments_q) {
  while ($p = $payments_q->fetch_assoc()) {
    $all_payments[] = $p;
  }
}

// Tentukan target pembayaran yang dicetak
$target_payment = null;
$nomor_pembayaran = 1;
$label_pembayaran = "";
$tanggal_pembayaran = "";
$nominal_pembayaran = 0;
$keterangan_pembayaran = "";
$cumulative_paid = 0;

if ($type === 'dp' || ($payment_id === 0 && $dp > 0 && empty($all_payments))) {
  // Cetak bukti DP Awal
  if ($dp <= 0) {
    die("Transaksi ini tidak memiliki catatan DP awal.");
  }
  $nomor_pembayaran      = 1;
  $label_pembayaran      = "Pembayaran ke-1 (DP Awal)";
  $tanggal_pembayaran    = $invoice["selling_date"];
  $nominal_pembayaran    = $dp;
  $keterangan_pembayaran = "Down Payment (Uang Muka) Awal";
  $cumulative_paid       = $dp;
} else {
  // Pembayaran angsuran dari invoice_payments
  $found = false;
  $running_paid = $dp;

  foreach ($all_payments as $idx => $pay) {
    $running_paid += (float)$pay['jumlah'];
    $nomor_angsuran = $idx + 1; // DP tidak termasuk angsuran, angsuran dimulai dari ke-1
    $ket_raw = trim($pay['keterangan'] ?? '');
    $ket_final = ($ket_raw !== '' && $ket_raw !== '-') ? $ket_raw : "Angsuran ke-{$nomor_angsuran}";

    // Jika payment_id cocok ATAU jika payment_id kosong tapi ada index tertentu
    if ($payment_id > 0 && (int)$pay['id'] === $payment_id) {
      $found = true;
      $nomor_pembayaran      = $nomor_angsuran;
      $label_pembayaran      = "Angsuran ke-{$nomor_angsuran}";
      $tanggal_pembayaran    = $pay['payment_date'];
      $nominal_pembayaran    = (float)$pay['jumlah'];
      $keterangan_pembayaran = $ket_final;
      $cumulative_paid       = $running_paid;
      break;
    }
  }

  // Jika tidak ditemukan berdasarkan ID spesifik, ambil pembayaran terakhir
  if (!$found && !empty($all_payments)) {
    $last_idx = count($all_payments) - 1;
    $pay = $all_payments[$last_idx];
    $nomor_angsuran        = $last_idx + 1;
    $ket_raw               = trim($pay['keterangan'] ?? '');
    $ket_final             = ($ket_raw !== '' && $ket_raw !== '-') ? $ket_raw : "Angsuran ke-{$nomor_angsuran}";
    $nomor_pembayaran      = $nomor_angsuran;
    $label_pembayaran      = "Angsuran ke-{$nomor_angsuran}";
    $tanggal_pembayaran    = $pay['payment_date'];
    $nominal_pembayaran    = (float)$pay['jumlah'];
    $keterangan_pembayaran = $ket_final;
    $cumulative_paid       = $running_paid;
  } elseif (!$found) {
    die("Data pembayaran angsuran tidak ditemukan.");
  }
}

// Sisa tagihan setelah pembayaran ini
$sisa_tagihan = max(0, $total_harga - $cumulative_paid);
$status_pelunasan = ($sisa_tagihan <= 0.01) ? "LUNAS" : "BELUM LUNAS";

// Ambil rincian produk yang dipesan
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

// Siapkan daftar tahapan pembayaran (History Pembayaran sesuai tahapannya)
$stages = [];
$tahap_no = 1;

if ($dp > 0) {
  $is_target = ($type === 'dp' || ($payment_id === 0 && empty($all_payments)));
  $stages[] = [
    'no'         => $tahap_no++,
    'is_target'  => $is_target,
    'tanggal'    => date("d/m/Y", strtotime($invoice["selling_date"])),
    'jumlah'     => $dp,
    'keterangan' => 'DP (Uang Muka) Awal',
    'status'     => 'Diterima'
  ];
}

// Tambahkan angsuran-angsuran dari invoice_payments
if ($type !== 'dp') {
  foreach ($all_payments as $idx => $pay) {
    $nomor_urutan = $idx + 1; // DP tidak termasuk angsuran, angsuran dimulai dari 1
    $is_this_target = ($payment_id > 0 && (int)$pay['id'] === $payment_id) || (!$payment_id && $idx === count($all_payments) - 1);

    $ket_raw = trim($pay['keterangan'] ?? '');
    $keterangan_tampil = ($ket_raw !== '' && $ket_raw !== '-') ? $ket_raw : "Angsuran ke-{$nomor_urutan}";

    $stages[] = [
      'no'         => $tahap_no++,
      'is_target'  => $is_this_target,
      'tanggal'    => date("d/m/Y H:i", strtotime($pay['payment_date'])) . ' WIB',
      'jumlah'     => (float)$pay['jumlah'],
      'keterangan' => $keterangan_tampil,
      'status'     => 'Diterima'
    ];

    if ($payment_id > 0 && (int)$pay['id'] === $payment_id) {
      break;
    }
  }
}

// Inisialisasi TCPDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Background Kop Surat
$bg_image = 'assets/Kop Surat Fayyfir New.png';
if (file_exists($bg_image)) {
  $pdf->Image($bg_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  $pdf->setPageMark();
}

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetY(38);
$pdf->SetFont("helvetica", "", 7.5);

$no_kuitansi = ($type === 'dp') ? "BKT-{$invoice_number}-DP" : "BKT-{$invoice_number}-P{$nomor_pembayaran}";

$html = '
<style>
  .garis { border: 1px solid #000; border-collapse: collapse; }
</style>

<table cellpadding="0" cellspacing="0">
  <tr>
    <td width="55%"><span style="font-size: 8.5pt; font-weight: bold; color:#111;">FROM : ABDUL HADI ALSHARIF</span></td>
    <td width="45%" align="right">
      <span style="font-size: 14pt; font-weight: bolder; color:#000078;">BUKTI PEMBAYARAN</span><br>
      <span style="font-size: 7.5pt; font-weight: bold; color:#555;">KWITANSI ANGSURAN</span>
    </td>
  </tr>
</table>
<div style="line-height: 4px;">&nbsp;</div>

<!-- Tabel Atas: Info Kwitansi & Rincian Produk Pesanan (Dijadikan Satu) -->
<table border="1" cellpadding="2.5" style="font-size: 7.5pt;">
  <tr>
    <td width="18%" class="garis"><strong>DITERIMA DARI:</strong></td>
    <td width="37%" class="garis">' . htmlspecialchars($invoice["buyer_name"]) . '</td>
    <td width="18%" class="garis"><strong>NO. BUKTI :</strong></td>
    <td width="27%" class="garis"><strong>' . htmlspecialchars($no_kuitansi) . '</strong></td>
  </tr>
  <tr>
    <td width="18%" class="garis" style="background-color:#f5f5f5;"><strong>ALAMAT :</strong></td>
    <td width="37%" class="garis" style="background-color:#f5f5f5;">' . nl2br(htmlspecialchars($invoice["address"])) . '</td>
    <td width="18%" class="garis" style="background-color:#f5f5f5;"><strong>NO. INVOICE :</strong></td>
    <td width="27%" class="garis" style="background-color:#f5f5f5;">' . htmlspecialchars($invoice["invoice_number"]) . '</td>
  </tr>
  <tr>
    <td width="18%" class="garis"><strong>KONTAK :</strong></td>
    <td width="37%" class="garis">' . htmlspecialchars($invoice["contact"]) . '</td>
    <td width="18%" class="garis"><strong>TGL INVOICE :</strong></td>
    <td width="27%" class="garis">' . date("d/m/Y", strtotime($invoice["selling_date"])) . '</td>
  </tr>
  <tr style="background-color:#000078; color: #fff;">
    <th width="45%" class="garis" align="left"><strong>PRODUK PESANAN</strong></th>
    <th width="18%" class="garis" align="right"><strong>QTY NETTO</strong></th>
    <th width="17%" class="garis" align="right"><strong>HARGA/KG</strong></th>
    <th width="20%" class="garis" align="right"><strong>SUBTOTAL</strong></th>
  </tr>';

while ($d = $details->fetch_assoc()) {
  $shrinkage = (float)($d["qty_shrinkage"] ?? 0);
  $net_qty   = max(0.0, (float)$d["qty"] - $shrinkage);
  $html .= '
  <tr>
    <td width="45%" class="garis">' . htmlspecialchars($d["product_name"]) . '</td>
    <td width="18%" class="garis" align="right">' . number_format($net_qty, 2, ",", ".") . ' ' . htmlspecialchars($d["symbol"]) . '</td>
    <td width="17%" class="garis" align="right">Rp ' . number_format($d["price"], 0, ",", ".") . '</td>
    <td width="20%" class="garis" align="right">Rp ' . number_format($d["total_selling"], 0, ",", ".") . '</td>
  </tr>';
}

$html .= '
  <tr style="background-color:#f5f5f5; font-weight:bold;">
    <td colspan="3" width="80%" class="garis" align="right">Total Nilai Tagihan Pesanan :</td>
    <td width="20%" class="garis" align="right">Rp ' . number_format($total_harga, 0, ",", ".") . '</td>
  </tr>
</table>

<div style="line-height: 5px;">&nbsp;</div>

<!-- Tabel Riwayat Pembayaran Sesuai Tahapannya -->
<table border="1" cellpadding="3" style="font-size: 7.5pt;">
  <thead>
    <tr style="background-color:#000078; color: #fff;">
      <th class="garis" align="center" width="22%"><strong>TANGGAL</strong></th>
      <th class="garis" align="right" width="24%"><strong>JUMLAH ANGSURAN</strong></th>
      <th class="garis" align="left" width="38%"><strong>KETERANGAN</strong></th>
      <th class="garis" align="center" width="16%"><strong>STATUS</strong></th>
    </tr>
  </thead>
  <tbody>';

foreach ($stages as $stg) {
  $bg = $stg['is_target'] ? 'background-color:#eef4ff;' : '';
  $bold_start = $stg['is_target'] ? '<strong>' : '';
  $bold_end   = $stg['is_target'] ? '</strong>' : '';

  $html .= '
    <tr style="' . $bg . '">
      <td class="garis" align="center" width="22%">' . $bold_start . $stg['tanggal'] . $bold_end . '</td>
      <td class="garis" align="right" width="24%">' . $bold_start . 'Rp ' . number_format($stg['jumlah'], 0, ",", ".") . $bold_end . '</td>
      <td class="garis" align="left" width="38%">' . $bold_start . htmlspecialchars($stg['keterangan']) . $bold_end . '</td>
      <td class="garis" align="center" width="16%"><strong style="color:#008000; font-size: 8pt;">' . $stg['status'] . '</strong></td>
    </tr>';
}

$html .= '
  </tbody>
</table>

<div style="line-height: 5px;">&nbsp;</div>

<!-- Tabel Rekapitulasi & Sisa Tagihan -->
<table border="1" cellpadding="3" style="font-size: 7.5pt;">
  <thead>
    <tr style="background-color:#000078; color: #fff;">
      <th colspan="2" width="100%" align="left"><strong>REKAPITULASI PEMBAYARAN & SISA TAGIHAN</strong></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td width="70%" class="garis">Total Nilai Tagihan Pesanan (Invoice)</td>
      <td width="30%" class="garis" align="right">Rp ' . number_format($total_harga, 0, ",", ".") . '</td>
    </tr>
    <tr>
      <td width="70%" class="garis">Total Telah Dibayar (s/d Pembayaran Ini)</td>
      <td width="30%" class="garis" align="right" style="color:#000078; font-weight:bold;">Rp ' . number_format($cumulative_paid, 0, ",", ".") . '</td>
    </tr>
    <tr style="background-color:' . ($sisa_tagihan <= 0.01 ? '#e6f4ea' : '#fce8e6') . ';">
      <td width="70%" class="garis"><strong style="color:' . ($sisa_tagihan <= 0.01 ? '#006600' : '#cc0000') . ';">SISA TAGIHAN SETELAH PEMBAYARAN INI</strong></td>
      <td width="30%" class="garis" align="right"><strong style="font-size:8.5pt; color:' . ($sisa_tagihan <= 0.01 ? '#006600' : '#cc0000') . ';">Rp ' . number_format($sisa_tagihan, 0, ",", ".") . '</strong></td>
    </tr>
    <tr>
      <td width="70%" class="garis">Status Tagihan Saat Ini</td>
      <td width="30%" class="garis" align="right"><strong style="color:' . ($status_pelunasan === 'LUNAS' ? '#006600' : '#d97706') . ';">' . $status_pelunasan . '</strong></td>
    </tr>
  </tbody>
</table>
<br>
';

if (ob_get_length()) {
  ob_clean();
}
$output_mode = (isset($_GET['download']) && $_GET['download'] == '1') ? 'D' : 'I';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("Bukti_Pembayaran_" . $invoice["invoice_number"] . "_P" . $nomor_pembayaran . ".pdf", $output_mode);
