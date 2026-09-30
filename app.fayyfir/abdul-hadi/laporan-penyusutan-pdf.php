<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["user_id"])) {
  die("Akses ditolak. Silakan login terlebih dahulu.");
}

require_once "tcpdf/tcpdf.php";
require_once "config.php";

// ============================================================
// AUTO-HEALING & MIGRATION DATABASE (Mencegah Error 500 di Hosting)
// ============================================================
// 1. Auto-create tabel product_shrinkage_logs jika belum ada
try {
  $conn->query("
    CREATE TABLE IF NOT EXISTS `product_shrinkage_logs` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `product_id` INT NOT NULL,
      `ref_no` VARCHAR(100) NOT NULL,
      `adjustment_date` DATETIME NOT NULL,
      `qty_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `qty_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `unsold_cost_at_time` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `hpp_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `hpp_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `notes` TEXT NULL,
      `created_by` INT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_product_id` (`product_id`),
      KEY `idx_adjustment_date` (`adjustment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
} catch (\Throwable $e) {}

// 2. Auto-add kolom qty_shrinkage pada selling_products jika belum ada
try {
  $col_check = $conn->query("SHOW COLUMNS FROM `selling_products` LIKE 'qty_shrinkage'");
  if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE `selling_products` ADD COLUMN `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `qty`");
  }
} catch (\Throwable $e) {}

// 3. Fallback DB2 jika data Gaharu ada di DB2 (dieksekusi aman dengan try-catch)
if (function_exists('get_conn2')) {
  try {
    $test_sp = $conn->query("SELECT COUNT(*) FROM product_shrinkage_logs");
    if (!$test_sp || (int)$test_sp->fetch_row()[0] === 0) {
      $conn2_candidate = @get_conn2();
      if ($conn2_candidate && !$conn2_candidate->connect_error) {
        $test_sp2 = $conn2_candidate->query("SELECT COUNT(*) FROM product_shrinkage_logs");
        if ($test_sp2 && (int)$test_sp2->fetch_row()[0] > 0) {
          $conn = $conn2_candidate;
        }
      }
    }
  } catch (\Throwable $e) {}
}

// Parameter
$current_year = (int)date('Y');
$tahun     = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : $current_year;
$f_type    = isset($_GET['type']) && in_array($_GET['type'], ['gudang', 'perjalanan']) ? $_GET['type'] : null;
$f_product = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;

// Nama Produk Terpilih
$selected_product_name = "Semua Produk";
if ($f_product) {
  $qp = $conn->prepare("SELECT product_name FROM product_stocks WHERE id = ?");
  if ($qp) {
    $qp->bind_param("i", $f_product);
    $qp->execute();
    $rp = $qp->get_result()->fetch_assoc();
    if ($rp) $selected_product_name = $rp['product_name'];
    $qp->close();
  }
}

$start_date = "{$tahun}-01-01 00:00:00";
$end_date   = "{$tahun}-12-31 23:59:59";

// ============================================================
// QUERY DATA GUDANG
// ============================================================
$logs_gudang = [];
if ($f_type === null || $f_type === 'gudang') {
  $where_g = [
    "psl.qty_shrinkage > 0",
    "psl.adjustment_date >= ?",
    "psl.adjustment_date <= ?"
  ];
  $params_g = [$start_date, $end_date];
  $types_g  = "ss";

  if ($f_product) {
    $where_g[] = "psl.product_id = ?";
    $params_g[] = $f_product;
    $types_g .= "i";
  }

  $sql_w_g = implode(' AND ', $where_g);
  $sql_g = "
    SELECT psl.id, psl.ref_no, psl.product_id, ps.product_name,
           psl.adjustment_date, psl.qty_before, psl.qty_after, psl.qty_shrinkage,
           psl.unsold_cost_at_time, psl.hpp_before, psl.hpp_after, psl.notes,
           (psl.hpp_before * psl.qty_shrinkage) AS biaya_susut
    FROM product_shrinkage_logs psl
    JOIN product_stocks ps ON ps.id = psl.product_id
    WHERE {$sql_w_g}
    ORDER BY psl.adjustment_date ASC, psl.id ASC
  ";
  $stmt_g = $conn->prepare($sql_g);
  if ($stmt_g) {
    $stmt_g->bind_param($types_g, ...$params_g);
    $stmt_g->execute();
    $res_g = $stmt_g->get_result();
    while ($r = $res_g->fetch_assoc()) $logs_gudang[] = $r;
    $stmt_g->close();
  }
}

// ============================================================
// QUERY DATA PERJALANAN
// ============================================================
$logs_perjalanan = [];
if ($f_type === null || $f_type === 'perjalanan') {
  $where_p = [
    "sp.qty_shrinkage > 0",
    "sp.selling_date >= ?",
    "sp.selling_date <= ?"
  ];
  $params_p = [$start_date, $end_date];
  $types_p  = "ss";

  if ($f_product) {
    $where_p[] = "sp.product_id = ?";
    $params_p[] = $f_product;
    $types_p .= "i";
  }

  $sql_w_p = implode(' AND ', $where_p);
  $sql_p = "
    SELECT sp.id,
           sp.invoice_number AS ref_no,
           sp.product_id,
           ps.product_name,
           sp.selling_date AS adjustment_date,
           (sp.qty + sp.qty_shrinkage) AS qty_before,
           sp.qty AS qty_after,
           sp.qty_shrinkage,
           sp.price,
           NULL AS unsold_cost_at_time,
           NULL AS hpp_before,
           NULL AS hpp_after,
           CONCAT('Invoice: ', sp.invoice_number) AS notes,
           (sp.price / 1000 * sp.qty_shrinkage) AS biaya_susut
    FROM selling_products sp
    JOIN product_stocks ps ON ps.id = sp.product_id
    WHERE {$sql_w_p}
    ORDER BY sp.selling_date ASC, sp.id ASC
  ";
  $stmt_p = $conn->prepare($sql_p);
  if ($stmt_p) {
    $stmt_p->bind_param($types_p, ...$params_p);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    while ($r = $res_p->fetch_assoc()) $logs_perjalanan[] = $r;
    $stmt_p->close();
  }
}

// Totals
$total_susut_gudang     = (float)array_sum(array_column($logs_gudang,     'qty_shrinkage'));
$total_susut_perjalanan = (float)array_sum(array_column($logs_perjalanan, 'qty_shrinkage'));
$total_susut_all        = $total_susut_gudang + $total_susut_perjalanan;

$biaya_susut_gudang     = (float)array_sum(array_column($logs_gudang,     'biaya_susut'));
$biaya_susut_perjalanan = (float)array_sum(array_column($logs_perjalanan, 'biaya_susut'));
$total_biaya_susut      = $biaya_susut_gudang + $biaya_susut_perjalanan;

$count_gudang           = count($logs_gudang);
$count_perjalanan       = count($logs_perjalanan);
$count_all              = $count_gudang + $count_perjalanan;

// Rekap Bulanan
$nama_bulan = [
  1 => 'Januari',
  2 => 'Februari',
  3 => 'Maret',
  4 => 'April',
  5 => 'Mei',
  6 => 'Juni',
  7 => 'Juli',
  8 => 'Agustus',
  9 => 'September',
  10 => 'Oktober',
  11 => 'November',
  12 => 'Desember'
];

$rekap_bulan = [];
for ($m = 1; $m <= 12; $m++) {
  $rekap_bulan[$m] = [
    'nama'             => $nama_bulan[$m],
    'count_gudang'    => 0,
    'susut_gudang'    => 0.0,
    'biaya_gudang'    => 0.0,
    'count_perjalanan' => 0,
    'susut_perjalanan' => 0.0,
    'biaya_perjalanan' => 0.0,
    'total_susut'     => 0.0,
    'total_biaya'     => 0.0,
  ];
}

foreach ($logs_gudang as $lg) {
  $m = (int)date('n', strtotime($lg['adjustment_date']));
  if (isset($rekap_bulan[$m])) {
    $rekap_bulan[$m]['count_gudang']++;
    $rekap_bulan[$m]['susut_gudang'] += (float)$lg['qty_shrinkage'];
    $rekap_bulan[$m]['biaya_gudang'] += (float)$lg['biaya_susut'];
    $rekap_bulan[$m]['total_susut']  += (float)$lg['qty_shrinkage'];
    $rekap_bulan[$m]['total_biaya']  += (float)$lg['biaya_susut'];
  }
}

foreach ($logs_perjalanan as $lp) {
  $m = (int)date('n', strtotime($lp['adjustment_date']));
  if (isset($rekap_bulan[$m])) {
    $rekap_bulan[$m]['count_perjalanan']++;
    $rekap_bulan[$m]['susut_perjalanan'] += (float)$lp['qty_shrinkage'];
    $rekap_bulan[$m]['biaya_perjalanan'] += (float)$lp['biaya_susut'];
    $rekap_bulan[$m]['total_susut']      += (float)$lp['qty_shrinkage'];
    $rekap_bulan[$m]['total_biaya']      += (float)$lp['biaya_susut'];
  }
}

// Rekap Produk (Tampilkan semua produk, jika tidak susut maka kejadian = 0)
$all_prods_res = $conn->query("SELECT id, product_name FROM product_stocks ORDER BY product_name ASC");
$rank_map = [];
if ($all_prods_res) {
  while ($prow = $all_prods_res->fetch_assoc()) {
    $pid = (int)$prow['id'];
    $rank_map[$pid] = [
      'id'         => $pid,
      'name'       => $prow['product_name'],
      'gudang'     => 0.0,
      'perjalanan' => 0.0,
      'total'      => 0.0,
      'biaya'      => 0.0,
      'count'      => 0
    ];
  }
}

foreach ($logs_gudang as $lg) {
  $pid = (int)$lg['product_id'];
  if (!isset($rank_map[$pid])) {
    $rank_map[$pid] = [
      'id'         => $pid,
      'name'       => $lg['product_name'],
      'gudang'     => 0.0,
      'perjalanan' => 0.0,
      'total'      => 0.0,
      'biaya'      => 0.0,
      'count'      => 0
    ];
  }
  $rank_map[$pid]['gudang'] += (float)$lg['qty_shrinkage'];
  $rank_map[$pid]['biaya']  += (float)$lg['biaya_susut'];
  $rank_map[$pid]['count']++;
}

foreach ($logs_perjalanan as $lp) {
  $pid = (int)$lp['product_id'];
  if (!isset($rank_map[$pid])) {
    $rank_map[$pid] = [
      'id'         => $pid,
      'name'       => $lp['product_name'],
      'gudang'     => 0.0,
      'perjalanan' => 0.0,
      'total'      => 0.0,
      'biaya'      => 0.0,
      'count'      => 0
    ];
  }
  $rank_map[$pid]['perjalanan'] += (float)$lp['qty_shrinkage'];
  $rank_map[$pid]['biaya']      += (float)$lp['biaya_susut'];
  $rank_map[$pid]['count']++;
}

foreach ($rank_map as &$rm) {
  $rm['total'] = $rm['gudang'] + $rm['perjalanan'];
}
unset($rm);

// Urutkan: produk dengan susut terbesar di atas, lalu produk dengan 0 susut alfabetis
usort($rank_map, function($a, $b) {
  if ($b['total'] != $a['total']) {
    return $b['total'] <=> $a['total'];
  }
  return strcmp($a['name'], $b['name']);
});

// Combined Logs
$all_logs = [];
foreach ($logs_gudang     as $lg) $all_logs[] = array_merge($lg, ['jenis' => 'gudang']);
foreach ($logs_perjalanan as $lp) $all_logs[] = array_merge($lp, ['jenis' => 'perjalanan']);
usort($all_logs, fn($a, $b) => strtotime($a['adjustment_date']) <=> strtotime($b['adjustment_date']));

$printed_by = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Administrator';
$printed_at = date('d/m/Y H:i');

// Inisialisasi TCPDF Landscape A4
$pdf = new TCPDF("L", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("Fayyfir System");
$pdf->SetAuthor("Fayyfir - Abdul Hadi");
$pdf->SetTitle("Laporan Penyusutan Tahunan $tahun");
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 8);

// Format angka
$f_susut_gudang_g = number_format($total_susut_gudang, 0, ',', '.');
$f_susut_gudang_kg = number_format($total_susut_gudang / 1000, 2, ',', '.');
$f_biaya_gudang = number_format($biaya_susut_gudang, 0, ',', '.');

$f_susut_pjln_g = number_format($total_susut_perjalanan, 0, ',', '.');
$f_susut_pjln_kg = number_format($total_susut_perjalanan / 1000, 2, ',', '.');
$f_biaya_pjln = number_format($biaya_susut_perjalanan, 0, ',', '.');

$f_susut_all_g = number_format($total_susut_all, 0, ',', '.');
$f_susut_all_kg = number_format($total_susut_all / 1000, 2, ',', '.');
$f_biaya_all = number_format($total_biaya_susut, 0, ',', '.');

// Buat konten HTML untuk TCPDF
$html = <<<EOD
<table width="100%" cellpadding="1" cellspacing="0">
  <tr>
    <td width="65%">
      <span style="font-size: 14pt; font-weight: bold; color: #0f172a;">FAYYFIR</span><br>
      <span style="font-size: 8.5pt; color: #334155;">Sistem Manajemen Produksi & Distribusi Kayu Gaharu</span><br>
      <span style="font-size: 7.5pt; color: #64748b;">Unit Usaha Pengolahan, Penyimpanan Gudang & Ekspedisi Penjualan</span>
    </td>
    <td width="35%" align="right" style="font-size: 7.5pt; color: #334155;">
      <strong>NO. DOKUMEN:</strong> SST-THN/$tahun/{$count_all}<br>
      <strong>TANGGAL CETAK:</strong> $printed_at WIB<br>
      <strong>PETUGAS:</strong> $printed_by<br>
      <strong>FILTER:</strong> $selected_product_name
    </td>
  </tr>
</table>
<hr style="border: 0; border-top: 1.5px solid #0f172a; margin-top: 4px; margin-bottom: 6px;">

<table width="100%" cellpadding="4" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #cbd5e1;">
  <tr>
    <td width="80%">
      <span style="font-size: 11pt; font-weight: bold; color: #0f172a;">LAPORAN PENYUSUTAN BARANG & PRODUK (TAHUNAN)</span><br>
      <span style="font-size: 8pt; color: #475569;">Periode: <strong>01 Januari $tahun s/d 31 Desember $tahun</strong></span>
    </td>
    <td width="20%" align="right" style="vertical-align: middle;">
      <span style="font-size: 11pt; font-weight: bold; color: #ea580c;">TAHUN $tahun</span>
    </td>
  </tr>
</table>

<br>
<div style="font-size: 9pt; font-weight: bold;">I. RINGKASAN EKSEKUTIF TAHUNAN</div>
<table width="100%" cellpadding="4" cellspacing="3" style="font-size: 8pt;">
  <tr>
    <td width="25%" style="border: 1px solid #fed7aa; background-color: #fffaf5;">
      <span style="font-size: 7.5pt; color: #9a3412;"><strong>PENYUSUTAN GUDANG</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #c2410c;">$f_susut_gudang_g g</span><br>
      <span style="font-size: 7.5pt; color: #475569;">$f_susut_gudang_kg kg &bull; $count_gudang kali<br>Biaya: <strong>Rp $f_biaya_gudang</strong></span>
    </td>
    <td width="25%" style="border: 1px solid #bfdbfe; background-color: #f8faff;">
      <span style="font-size: 7.5pt; color: #1e40af;"><strong>PENYUSUTAN PERJALANAN</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #1d4ed8;">$f_susut_pjln_g g</span><br>
      <span style="font-size: 7.5pt; color: #475569;">$f_susut_pjln_kg kg &bull; $count_perjalanan invc<br>Biaya: <strong>Rp $f_biaya_pjln</strong></span>
    </td>
    <td width="25%" style="border: 1px solid #ddd6fe; background-color: #faf5ff;">
      <span style="font-size: 7.5pt; color: #6b21a8;"><strong>TOTAL AKUMULASI SUSUT</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #7c3aed;">$f_susut_all_g g</span><br>
      <span style="font-size: 7.5pt; color: #475569;">$f_susut_all_kg kg<br>Total: <strong>$count_all catatan</strong></span>
    </td>
    <td width="25%" style="border: 1px solid #fecaca; background-color: #fff5f5;">
      <span style="font-size: 7.5pt; color: #991b1b;"><strong>EST. TOTAL KERUGIAN</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #b91c1c;">Rp $f_biaya_all</span><br>
      <span style="font-size: 7.5pt; color: #475569;">Gudang: Rp $f_biaya_gudang<br>Perjalanan: Rp $f_biaya_pjln</span>
    </td>
  </tr>
</table>

<br>
<div style="font-size: 9pt; font-weight: bold;">II. REKAPITULASI PENYUSUTAN BULANAN (JANUARI - DESEMBER $tahun)</div>
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <!-- Tabel A: Susut Gudang -->
    <td width="49%" style="vertical-align: top;">
      <div style="font-size: 8pt; font-weight: bold; color: #c2410c; margin-bottom: 2px;">A. PENYUSUTAN GUDANG (TIMBANG ULANG)</div>
      <table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
        <tr style="background-color: #fff7ed; font-weight: bold;">
          <th width="9%" align="center">No</th>
          <th width="31%">Bulan</th>
          <th width="18%" align="center">Kejadian</th>
          <th width="21%" align="right">Susut (g)</th>
          <th width="21%" align="right">Biaya (Rp)</th>
        </tr>
EOD;

foreach ($rekap_bulan as $b_idx => $rb) {
  $sg_fmt = $rb['susut_gudang'] > 0 ? number_format($rb['susut_gudang'], 0, ',', '.') . ' g' : '-';
  $bg_fmt = $rb['biaya_gudang'] > 0 ? 'Rp ' . number_format($rb['biaya_gudang'], 0, ',', '.') : '-';
  $cg_fmt = $rb['count_gudang'] > 0 ? $rb['count_gudang'] : '-';
  $bg_row = ($b_idx % 2 === 0) ? '#fffaf5' : '#ffffff';
  $html .= <<<EOD
        <tr style="background-color: $bg_row;">
          <td align="center">$b_idx</td>
          <td><strong>{$rb['nama']}</strong></td>
          <td align="center">$cg_fmt</td>
          <td align="right">$sg_fmt</td>
          <td align="right">$bg_fmt</td>
        </tr>
EOD;
}

$html .= <<<EOD
        <tr style="background-color: #ffedd5; font-weight: bold;">
          <td colspan="2" align="center">TOTAL GUDANG</td>
          <td align="center">$count_gudang</td>
          <td align="right" style="color: #c2410c;">$f_susut_gudang_g g</td>
          <td align="right">Rp $f_biaya_gudang</td>
        </tr>
      </table>
    </td>

    <td width="2%"></td>

    <!-- Tabel B: Susut Perjalanan -->
    <td width="49%" style="vertical-align: top;">
      <div style="font-size: 8pt; font-weight: bold; color: #1d4ed8; margin-bottom: 2px;">B. PENYUSUTAN PERJALANAN (PENGIRIMAN)</div>
      <table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
        <tr style="background-color: #eff6ff; font-weight: bold;">
          <th width="9%" align="center">No</th>
          <th width="31%">Bulan</th>
          <th width="18%" align="center">Kirim Invc</th>
          <th width="21%" align="right">Susut (g)</th>
          <th width="21%" align="right">Biaya (Rp)</th>
        </tr>
EOD;

foreach ($rekap_bulan as $b_idx => $rb) {
  $sp_fmt = $rb['susut_perjalanan'] > 0 ? number_format($rb['susut_perjalanan'], 0, ',', '.') . ' g' : '-';
  $bp_fmt = $rb['biaya_perjalanan'] > 0 ? 'Rp ' . number_format($rb['biaya_perjalanan'], 0, ',', '.') : '-';
  $cp_fmt = $rb['count_perjalanan'] > 0 ? $rb['count_perjalanan'] : '-';
  $bp_row = ($b_idx % 2 === 0) ? '#f8faff' : '#ffffff';
  $html .= <<<EOD
        <tr style="background-color: $bp_row;">
          <td align="center">$b_idx</td>
          <td><strong>{$rb['nama']}</strong></td>
          <td align="center">$cp_fmt</td>
          <td align="right">$sp_fmt</td>
          <td align="right">$bp_fmt</td>
        </tr>
EOD;
}

$html .= <<<EOD
        <tr style="background-color: #dbeafe; font-weight: bold;">
          <td colspan="2" align="center">TOTAL PERJALANAN</td>
          <td align="center">$count_perjalanan</td>
          <td align="right" style="color: #1d4ed8;">$f_susut_pjln_g g</td>
          <td align="right">Rp $f_biaya_pjln</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<br>
<div style="font-size: 9pt; font-weight: bold;">III. REKAPITULASI PENYUSUTAN PER PRODUK</div>
<table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7.5pt;">
  <tr style="background-color: #f1f5f9; font-weight: bold;">
    <th width="4%" align="center">No</th>
    <th width="28%">Nama Produk</th>
    <th width="10%" align="center">Kejadian</th>
    <th width="14%" align="right">Susut Gudang</th>
    <th width="14%" align="right">Susut Perjalanan</th>
    <th width="15%" align="right">Total Susut (g)</th>
    <th width="15%" align="right">Est. Kerugian (Rp)</th>
  </tr>
EOD;

if (!empty($rank_map)) {
  $rnk = 1;
  foreach ($rank_map as $rm) {
    $rm_gudang_fmt = $rm['gudang'] > 0 ? number_format($rm['gudang'], 0, ',', '.') . ' g' : '0 g';
    $rm_pjln_fmt   = $rm['perjalanan'] > 0 ? number_format($rm['perjalanan'], 0, ',', '.') . ' g' : '0 g';
    $rm_total_fmt  = number_format($rm['total'], 0, ',', '.') . ' g';
    $rm_biaya_fmt  = 'Rp ' . number_format($rm['biaya'], 0, ',', '.');
    $p_name = htmlspecialchars($rm['name']);
    $cnt = $rm['count']; // jika suatu produk tidak susut maka kejadian = 0

    $html .= <<<EOD
    <tr>
      <td align="center">$rnk</td>
      <td><strong>$p_name</strong></td>
      <td align="center">$cnt</td>
      <td align="right">$rm_gudang_fmt</td>
      <td align="right">$rm_pjln_fmt</td>
      <td align="right"><strong>$rm_total_fmt</strong></td>
      <td align="right" style="color:#b91c1c;"><strong>$rm_biaya_fmt</strong></td>
    </tr>
EOD;
    $rnk++;
  }
} else {
  $html .= '<tr><td colspan="7" align="center">Tidak ada data master produk.</td></tr>';
}

$html .= <<<EOD
  <tr style="background-color: #e2e8f0; font-weight: bold;">
    <td colspan="2" align="center">TOTAL KESELURUHAN</td>
    <td align="center">{$count_all}</td>
    <td align="right">$f_susut_gudang_g g</td>
    <td align="right">$f_susut_pjln_g g</td>
    <td align="right">$f_susut_all_g g</td>
    <td align="right" style="color:#b91c1c;">Rp $f_biaya_all</td>
  </tr>
</table>

<br>
<div style="font-size: 9pt; font-weight: bold;">IV. RINCIAN LOG PENYUSUTAN</div>
<table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
  <tr style="background-color: #f1f5f9; font-weight: bold;">
    <th width="4%" align="center">No</th>
    <th width="16%">Ref / Invoice</th>
    <th width="13%" align="center">Tanggal & Jam</th>
    <th width="9%" align="center">Jenis</th>
    <th width="20%">Nama Produk</th>
    <th width="10%" align="right">Stok Sblm</th>
    <th width="9%" align="right">Susut</th>
    <th width="9%" align="right">Stok Ssdh</th>
    <th width="10%" align="right">Est. Kerugian</th>
  </tr>
EOD;

if (!empty($all_logs)) {
  $no = 1;
  foreach ($all_logs as $l) {
    $ref = htmlspecialchars($l['ref_no'] ?? '-');
    $tgl = date('d/m/Y H:i', strtotime($l['adjustment_date']));
    $jns = ucfirst($l['jenis']);
    $pname = htmlspecialchars(mb_strimwidth($l['product_name'], 0, 26, '…'));
    $sb = number_format($l['qty_before'], 0, ',', '.') . ' g';
    $ss = number_format($l['qty_shrinkage'], 0, ',', '.') . ' g';
    $sa = number_format($l['qty_after'], 0, ',', '.') . ' g';
    $by = 'Rp ' . number_format($l['biaya_susut'], 0, ',', '.');

    $html .= <<<EOD
    <tr>
      <td align="center">$no</td>
      <td>$ref</td>
      <td align="center">$tgl</td>
      <td align="center">$jns</td>
      <td>$pname</td>
      <td align="right">$sb</td>
      <td align="right" style="color:#b91c1c;">$ss</td>
      <td align="right">$sa</td>
      <td align="right" style="color:#b91c1c;">$by</td>
    </tr>
EOD;
    $no++;
  }
} else {
  $html .= '<tr><td colspan="9" align="center">Tidak ada catatan log penyusutan pada tahun ' . $tahun . '</td></tr>';
}

$html .= <<<EOD
  <tr style="background-color: #e2e8f0; font-weight: bold;">
    <td colspan="5" align="center">TOTAL $count_all LOG</td>
    <td align="right">-</td>
    <td align="right" style="color:#b91c1c;">$f_susut_all_g g</td>
    <td align="right">-</td>
    <td align="right" style="color:#b91c1c;">Rp $f_biaya_all</td>
  </tr>
</table>

<br><br>

<br>
<hr style="border:0; border-top:1px solid #cbd5e1;">
<table width="100%" style="font-size:7pt; color:#64748b;">
  <tr>
    <td>Fayyfir Management System &bull; Dokumen Rahasia & Resmi Perusahaan</td>
    <td align="right">Laporan Penyusutan Tahunan &bull; Periode $tahun</td>
  </tr>
</table>
EOD;

$pdf->writeHTML($html, true, false, true, false, "");
$pdf->Output("Laporan_Penyusutan_Tahunan_{$tahun}.pdf", "I");
