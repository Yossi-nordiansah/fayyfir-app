<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

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

// ============================================================
// AMBIL SEMUA TAHUN YANG ADA DI DATABASE
// ============================================================
$current_year = (int)date('Y');
$available_years = [];
$ry = $conn->query("
  SELECT DISTINCT YEAR(adjustment_date) as y FROM product_shrinkage_logs WHERE adjustment_date IS NOT NULL
  UNION 
  SELECT DISTINCT YEAR(selling_date) as y FROM selling_products WHERE qty_shrinkage > 0 AND selling_date IS NOT NULL
  ORDER BY y DESC
");
if ($ry) {
  while ($row_y = $ry->fetch_assoc()) {
    if (!empty($row_y['y'])) $available_years[] = (int)$row_y['y'];
  }
}
if (!in_array($current_year, $available_years)) {
  array_unshift($available_years, $current_year);
}

// ============================================================
// PARAMETER FILTER
// ============================================================
$tahun     = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : $current_year;
$f_type    = isset($_GET['type']) && in_array($_GET['type'], ['gudang', 'perjalanan']) ? $_GET['type'] : null;
$f_product = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;
$auto_print = isset($_GET['print']) && $_GET['print'] === '1';

// Ambil daftar produk untuk dropdown filter & nama produk terpilih
$all_products = [];
$selected_product_name = "Semua Produk";
$rp = $conn->query("SELECT id, product_name FROM product_stocks ORDER BY product_name ASC");
if ($rp) {
  while ($r = $rp->fetch_assoc()) {
    $all_products[] = $r;
    if ($f_product && (int)$r['id'] === $f_product) {
      $selected_product_name = $r['product_name'];
    }
  }
}

// Rentang tanggal 1 tahun penuh
$start_date = "{$tahun}-01-01 00:00:00";
$end_date   = "{$tahun}-12-31 23:59:59";

// ============================================================
// QUERY DATA GUDANG PER TAHUN
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
    while ($r = $res_g->fetch_assoc()) {
      $logs_gudang[] = $r;
    }
    $stmt_g->close();
  }
}

// ============================================================
// QUERY DATA PERJALANAN PER TAHUN
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
    while ($r = $res_p->fetch_assoc()) {
      $logs_perjalanan[] = $r;
    }
    $stmt_p->close();
  }
}

// ============================================================
// KALKULASI KPI TOTAL TAHUNAN
// ============================================================
$total_susut_gudang     = (float)array_sum(array_column($logs_gudang,     'qty_shrinkage'));
$total_susut_perjalanan = (float)array_sum(array_column($logs_perjalanan, 'qty_shrinkage'));
$total_susut_all        = $total_susut_gudang + $total_susut_perjalanan;

$biaya_susut_gudang     = (float)array_sum(array_column($logs_gudang,     'biaya_susut'));
$biaya_susut_perjalanan = (float)array_sum(array_column($logs_perjalanan, 'biaya_susut'));
$total_biaya_susut      = $biaya_susut_gudang + $biaya_susut_perjalanan;

$count_gudang           = count($logs_gudang);
$count_perjalanan       = count($logs_perjalanan);
$count_all              = $count_gudang + $count_perjalanan;

// ============================================================
// REKAPITULASI 12 BULAN (JANUARI - DESEMBER)
// ============================================================
$nama_bulan = [
  1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
  5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
  9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$rekap_bulan = [];
for ($m = 1; $m <= 12; $m++) {
  $rekap_bulan[$m] = [
    'nama'             => $nama_bulan[$m],
    'count_gudang'    => 0,
    'susut_gudang'    => 0.0,
    'biaya_gudang'    => 0.0,
    'count_perjalanan'=> 0,
    'susut_perjalanan'=> 0.0,
    'biaya_perjalanan'=> 0.0,
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

// ============================================================
// REKAPITULASI PER PRODUK (RANKING TAHUNAN - SEMUA PRODUK)
// ============================================================
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

// ============================================================
// COMBINED LOGS (TERURUT KRONOLOGIS ASC)
// ============================================================
$all_logs = [];
foreach ($logs_gudang     as $lg) $all_logs[] = array_merge($lg, ['jenis' => 'gudang']);
foreach ($logs_perjalanan as $lp) $all_logs[] = array_merge($lp, ['jenis' => 'perjalanan']);
usort($all_logs, fn($a, $b) => strtotime($a['adjustment_date']) <=> strtotime($b['adjustment_date']));

// Info User & Tanggal Cetak
$printed_by = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Administrator';
$printed_at = date('d F Y, H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Penyusutan Tahunan <?= $tahun ?> - Fayyfir</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    /* Reset & Base Fonts */
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: #f1f5f9;
      color: #0f172a;
      margin: 0;
      padding: 0;
      font-size: 11px;
      line-height: 1.4;
      -webkit-font-smoothing: antialiased;
    }
    .font-mono { font-family: 'JetBrains Mono', monospace; }

    /* Screen Topbar */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 50;
      background: #0f172a;
      color: #fff;
      padding: 10px 24px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .topbar-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
    .topbar-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: all 0.15s ease;
    }
    .btn-primary { background: #ea580c; color: #fff; }
    .btn-primary:hover { background: #c2410c; }
    .btn-secondary { background: #334155; color: #f8fafc; }
    .btn-secondary:hover { background: #475569; }
    .btn-success { background: #059669; color: #fff; }
    .btn-success:hover { background: #047857; }
    .btn-pdf { background: #dc2626; color: #fff; }
    .btn-pdf:hover { background: #b91c1c; }

    .select-sm {
      background: #1e293b;
      color: #fff;
      border: 1px solid #475569;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 12px;
      outline: none;
      cursor: pointer;
    }
    .select-sm:focus { border-color: #f97316; }

    /* Printable Page Container */
    .page-container {
      max-width: 1100px;
      margin: 24px auto 60px auto;
      background: #ffffff;
      padding: 28px 36px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
    }

    /* Kop Surat */
    .kop-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 12px;
      margin-bottom: 4px;
    }
    .kop-logo-area { display: flex; align-items: center; gap: 14px; }
    .kop-brand {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 0.5px;
      color: #0f172a;
      line-height: 1.1;
    }
    .kop-sub { font-size: 11px; color: #475569; margin-top: 2px; }
    .kop-tagline { font-size: 9.5px; color: #64748b; }
    .kop-meta {
      text-align: right;
      font-size: 10px;
      color: #334155;
      line-height: 1.45;
    }
    .kop-meta strong { color: #0f172a; }
    .kop-divider-sub {
      height: 1px;
      background: #cbd5e1;
      margin-bottom: 16px;
    }

    /* Document Title Banner */
    .report-banner {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-left: 4px solid #ea580c;
      padding: 10px 14px;
      border-radius: 4px;
      margin-bottom: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .report-banner h1 {
      margin: 0;
      font-size: 15px;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: 0.2px;
    }
    .report-banner p {
      margin: 2px 0 0 0;
      font-size: 11px;
      color: #475569;
    }
    .report-badge {
      display: inline-block;
      padding: 3px 10px;
      background: #ea580c;
      color: #fff;
      font-weight: 700;
      font-size: 11px;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Section Headings */
    .section-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #0f172a;
      margin: 20px 0 8px 0;
      display: flex;
      align-items: center;
      gap: 6px;
      border-bottom: 1.5px solid #e2e8f0;
      padding-bottom: 4px;
    }
    .section-title .badge-num {
      background: #0f172a;
      color: #fff;
      width: 17px;
      height: 17px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 9.5px;
      font-weight: 800;
    }

    /* KPI Summary Cards Grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-bottom: 18px;
    }
    .kpi-card {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px 12px;
      background: #ffffff;
      position: relative;
    }
    .kpi-card.c-gudang { border-left: 3.5px solid #ea580c; background: #fffaf5; }
    .kpi-card.c-pjln   { border-left: 3.5px solid #2563eb; background: #f8faff; }
    .kpi-card.c-total  { border-left: 3.5px solid #7c3aed; background: #faf5ff; }
    .kpi-card.c-cost   { border-left: 3.5px solid #dc2626; background: #fff5f5; }

    .kpi-card .kpi-label {
      font-size: 9.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #64748b;
      margin-bottom: 4px;
    }
    .kpi-card .kpi-value {
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.2;
    }
    .kpi-card .kpi-sub {
      font-size: 9.5px;
      color: #64748b;
      margin-top: 3px;
    }

    /* Tables */
    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-bottom: 18px;
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
      color: #1e293b;
    }
    table.data-table th, table.data-table td {
      padding: 5px 8px;
      border: 1px solid #cbd5e1;
      vertical-align: middle;
    }
    table.data-table th {
      background-color: #f1f5f9;
      color: #0f172a;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: 0.3px;
      text-align: left;
    }
    table.data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
    table.data-table tfoot tr {
      background-color: #e2e8f0;
      font-weight: 700;
      border-top: 2px solid #0f172a;
    }
    table.data-table tfoot td { font-weight: 700; }

    .text-center { text-align: center !important; }
    .text-right  { text-align: right !important; }
    .text-left   { text-align: left !important; }
    .whitespace-nowrap { white-space: nowrap; }

    .badge-jenis {
      display: inline-block;
      padding: 1.5px 6px;
      border-radius: 9999px;
      font-size: 8.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .badge-jenis.gudang { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-jenis.perjalanan { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

    /* Signatures Section */
    .sig-section {
      margin-top: 28px;
      page-break-inside: avoid;
    }
    .sig-location {
      text-align: right;
      font-size: 10.5px;
      margin-bottom: 12px;
      color: #334155;
    }
    .sig-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
      text-align: center;
    }
    .sig-box {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px;
      background: #fafafa;
    }
    .sig-title { font-weight: 700; font-size: 10.5px; color: #0f172a; margin-bottom: 50px; }
    .sig-name { font-weight: 700; font-size: 10.5px; color: #0f172a; text-decoration: underline; }
    .sig-role { font-size: 9.5px; color: #64748b; margin-top: 1px; }

    /* Print Footer */
    .report-footer {
      margin-top: 24px;
      padding-top: 10px;
      border-top: 1px solid #cbd5e1;
      display: flex;
      justify-content: space-between;
      font-size: 9px;
      color: #64748b;
    }

    /* Print Media Styling */
    @media print {
      @page {
        size: A4 landscape;
        margin: 8mm 10mm 10mm 10mm;
      }
      body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 9.5px;
      }
      .no-print {
        display: none !important;
      }
      .page-container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }
      .kpi-card {
        border: 1px solid #cbd5e1 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      table.data-table th {
        background-color: #e2e8f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      table.data-table tfoot tr {
        background-color: #cbd5e1 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      tr {
        page-break-inside: avoid;
      }
      .page-break {
        page-break-before: always;
      }
    }
  </style>
</head>
<body>

  <!-- ====== SCREEN TOOLBAR (HIDDEN IN PRINT) ====== -->
  <div class="topbar no-print">
    <div class="topbar-left">
      <a href="laporan-penyusutan.php?tahun=<?= $tahun ?>" class="btn btn-secondary" title="Kembali ke Laporan Penyusutan">
        <span class="material-symbols-outlined" style="font-size:16px">arrow_back</span>
        <span>Kembali</span>
      </a>
      <div class="topbar-title">
        <span class="material-symbols-outlined" style="font-size:20px; color:#f97316">print</span>
        <span>Cetak Laporan Penyusutan Tahunan</span>
      </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="topbar-controls">
      <label for="tahunSelect" style="font-size:11px; font-weight:600; color:#cbd5e1;">Tahun:</label>
      <select name="tahun" id="tahunSelect" class="select-sm" onchange="this.form.submit()">
        <?php foreach ($available_years as $yr): ?>
          <option value="<?= $yr ?>" <?= $tahun === $yr ? 'selected' : '' ?>>Tahun <?= $yr ?></option>
        <?php endforeach; ?>
      </select>

      <label for="typeSelect" style="font-size:11px; font-weight:600; color:#cbd5e1;">Jenis:</label>
      <select name="type" id="typeSelect" class="select-sm" onchange="this.form.submit()">
        <option value="">Semua Jenis</option>
        <option value="gudang"     <?= $f_type === 'gudang' ? 'selected' : '' ?>>🏭 Gudang</option>
        <option value="perjalanan" <?= $f_type === 'perjalanan' ? 'selected' : '' ?>>🚛 Perjalanan</option>
      </select>

      <label for="productSelect" style="font-size:11px; font-weight:600; color:#cbd5e1;">Produk:</label>
      <select name="product" id="productSelect" class="select-sm" onchange="this.form.submit()" style="max-width:180px">
        <option value="">Semua Produk</option>
        <?php foreach ($all_products as $ap): ?>
          <option value="<?= $ap['id'] ?>" <?= $f_product === (int)$ap['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($ap['product_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="button" onclick="window.print()" class="btn btn-primary" title="Cetak atau Simpan sebagai PDF">
        <span class="material-symbols-outlined" style="font-size:16px">print</span>
        <span>Cetak Dokumen</span>
      </button>

      <a href="laporan-penyusutan-pdf.php?tahun=<?= $tahun ?><?= $f_type ? '&type='.$f_type : '' ?><?= $f_product ? '&product='.$f_product : '' ?>"
         target="_blank" class="btn btn-pdf" title="Unduh File PDF">
        <span class="material-symbols-outlined" style="font-size:16px">picture_as_pdf</span>
        <span>Unduh PDF</span>
      </a>

      <button type="button" onclick="exportToCSV()" class="btn btn-success" title="Ekspor Data ke File CSV">
        <span class="material-symbols-outlined" style="font-size:16px">table_view</span>
        <span>CSV</span>
      </button>
    </form>
  </div>

  <!-- ====== PRINTABLE PAPER CANVAS ====== -->
  <div class="page-container" id="printableArea">

    <!-- 1. KOP SURAT RESMI -->
    <header class="kop-header">
      <div class="kop-logo-area">
        <div style="width:40px; height:40px; background:#0f172a; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#f97316; font-weight:900; font-size:22px;">
          F
        </div>
        <div>
          <div class="kop-brand">FAYYFIR</div>
          <div class="kop-sub">Sistem Manajemen Produksi & Distribusi Kayu Gaharu</div>
          <div class="kop-tagline">Unit Usaha: Pengolahan, Penyimpanan Gudang & Ekspedisi Penjualan</div>
        </div>
      </div>
      <div class="kop-meta">
        <div><strong>NO. DOKUMEN:</strong> SST-THN/<?= $tahun ?>/<?= str_pad($count_all, 3, '0', STR_PAD_LEFT) ?></div>
        <div><strong>TANGGAL CETAK:</strong> <?= $printed_at ?> WIB</div>
        <div><strong>PETUGAS CETAK:</strong> <?= htmlspecialchars($printed_by) ?></div>
        <div><strong>FILTER LAPORAN:</strong> <?= htmlspecialchars($selected_product_name) ?> (<?= $f_type ? ucfirst($f_type) : 'Gudang & Perjalanan' ?>)</div>
      </div>
    </header>
    <div class="kop-divider-sub"></div>

    <!-- 2. BANNER JUDUL LAPORAN -->
    <div class="report-banner">
      <div>
        <h1>LAPORAN PENYUSUTAN BARANG & PRODUK (TAHUNAN)</h1>
        <p>Rekapitulasi dan Audit Kerugian Penyusutan Fisik Periode <strong>01 Januari <?= $tahun ?> s/d 31 Desember <?= $tahun ?></strong></p>
      </div>
      <div class="report-badge">
        TAHUN <?= $tahun ?>
      </div>
    </div>

    <!-- 3. BAGIAN I: RINGKASAN EKSEKUTIF KPI TAHUNAN -->
    <div class="section-title">
      <span class="badge-num">I</span>
      <span>Ringkasan Eksekutif Penyusutan Tahunan</span>
    </div>

    <div class="kpi-grid">
      <!-- Gudang -->
      <div class="kpi-card c-gudang">
        <div class="kpi-label">Penyusutan Gudang</div>
        <div class="kpi-value"><?= number_format($total_susut_gudang, 0, ',', '.') ?> <span style="font-size:11px; font-weight:500;">g</span></div>
        <div class="kpi-sub">
          <strong><?= number_format($total_susut_gudang / 1000, 2, ',', '.') ?> kg</strong> &bull; <?= $count_gudang ?> timbang ulang<br>
          Est. Biaya: <strong style="color:#c2410c">Rp <?= number_format($biaya_susut_gudang, 0, ',', '.') ?></strong>
        </div>
      </div>

      <!-- Perjalanan -->
      <div class="kpi-card c-pjln">
        <div class="kpi-label">Penyusutan Perjalanan</div>
        <div class="kpi-value"><?= number_format($total_susut_perjalanan, 0, ',', '.') ?> <span style="font-size:11px; font-weight:500;">g</span></div>
        <div class="kpi-sub">
          <strong><?= number_format($total_susut_perjalanan / 1000, 2, ',', '.') ?> kg</strong> &bull; <?= $count_perjalanan ?> pengiriman<br>
          Est. Biaya: <strong style="color:#1d4ed8">Rp <?= number_format($biaya_susut_perjalanan, 0, ',', '.') ?></strong>
        </div>
      </div>

      <!-- Total Akumulasi Susut -->
      <div class="kpi-card c-total">
        <div class="kpi-label">Total Akumulasi Susut</div>
        <div class="kpi-value"><?= number_format($total_susut_all, 0, ',', '.') ?> <span style="font-size:11px; font-weight:500;">g</span></div>
        <div class="kpi-sub">
          <strong><?= number_format($total_susut_all / 1000, 2, ',', '.') ?> kg</strong><br>
          Total Transaksi/Kejadian: <strong><?= $count_all ?> catatan</strong>
        </div>
      </div>

      <!-- Estimasi Total Kerugian -->
      <div class="kpi-card c-cost">
        <div class="kpi-label">Estimasi Total Kerugian</div>
        <div class="kpi-value" style="color:#b91c1c; font-size:15px;">Rp <?= number_format($total_biaya_susut, 0, ',', '.') ?></div>
        <div class="kpi-sub">
          Gudang: <?= $total_biaya_susut > 0 ? round(($biaya_susut_gudang / $total_biaya_susut) * 100, 1) : 0 ?>% &bull;
          Kirim: <?= $total_biaya_susut > 0 ? round(($biaya_susut_perjalanan / $total_biaya_susut) * 100, 1) : 0 ?>%
        </div>
      </div>
    </div>

    <!-- 4. BAGIAN II: REKAPITULASI PENYUSUTAN BULANAN (12 BULAN) -->
    <div class="section-title">
      <span class="badge-num">II</span>
      <span>Rekapitulasi Penyusutan Bulanan (Januari – Desember <?= $tahun ?>)</span>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:18px;">
      <!-- Tabel A: Susut Gudang -->
      <div>
        <div style="font-size:11px; font-weight:700; color:#c2410c; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
          <span class="material-symbols-outlined" style="font-size:14px">warehouse</span>
          A. Rekapitulasi Penyusutan Gudang (Timbang Ulang)
        </div>
        <div class="table-container" style="margin-bottom:0;">
          <table class="data-table" id="tableBulananGudang">
            <thead>
              <tr style="background:#fff7ed;">
                <th class="text-center" style="width:28px">No</th>
                <th>Bulan</th>
                <th class="text-center">Kejadian</th>
                <th class="text-right">Susut (g)</th>
                <th class="text-right">Est. Biaya (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rekap_bulan as $bln_idx => $rb):
                $has_g = $rb['susut_gudang'] > 0;
              ?>
                <tr style="<?= $has_g ? 'font-weight:600;' : 'color:#94a3b8;' ?>">
                  <td class="text-center"><?= $bln_idx ?></td>
                  <td><strong><?= $rb['nama'] ?></strong></td>
                  <td class="text-center"><?= $rb['count_gudang'] ?></td>
                  <td class="text-right font-mono" style="<?= $has_g ? 'color:#c2410c;' : '' ?>">
                    <?= $has_g ? number_format($rb['susut_gudang'], 0, ',', '.') . ' g' : '-' ?>
                  </td>
                  <td class="text-right font-mono">
                    <?= $rb['biaya_gudang'] > 0 ? 'Rp ' . number_format($rb['biaya_gudang'], 0, ',', '.') : '-' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:#ffedd5;">
                <td colspan="2" class="text-center">TOTAL GUDANG</td>
                <td class="text-center"><?= $count_gudang ?></td>
                <td class="text-right font-mono" style="color:#c2410c"><?= number_format($total_susut_gudang, 0, ',', '.') ?> g</td>
                <td class="text-right font-mono">Rp <?= number_format($biaya_susut_gudang, 0, ',', '.') ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Tabel B: Susut Perjalanan -->
      <div>
        <div style="font-size:11px; font-weight:700; color:#1d4ed8; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
          <span class="material-symbols-outlined" style="font-size:14px">local_shipping</span>
          B. Rekapitulasi Penyusutan Perjalanan (Pengiriman)
        </div>
        <div class="table-container" style="margin-bottom:0;">
          <table class="data-table" id="tableBulananPerjalanan">
            <thead>
              <tr style="background:#eff6ff;">
                <th class="text-center" style="width:28px">No</th>
                <th>Bulan</th>
                <th class="text-center">Kirim Invc</th>
                <th class="text-right">Susut (g)</th>
                <th class="text-right">Est. Biaya (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rekap_bulan as $bln_idx => $rb):
                $has_p = $rb['susut_perjalanan'] > 0;
              ?>
                <tr style="<?= $has_p ? 'font-weight:600;' : 'color:#94a3b8;' ?>">
                  <td class="text-center"><?= $bln_idx ?></td>
                  <td><strong><?= $rb['nama'] ?></strong></td>
                  <td class="text-center"><?= $rb['count_perjalanan'] ?></td>
                  <td class="text-right font-mono" style="<?= $has_p ? 'color:#1d4ed8;' : '' ?>">
                    <?= $has_p ? number_format($rb['susut_perjalanan'], 0, ',', '.') . ' g' : '-' ?>
                  </td>
                  <td class="text-right font-mono">
                    <?= $rb['biaya_perjalanan'] > 0 ? 'Rp ' . number_format($rb['biaya_perjalanan'], 0, ',', '.') : '-' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:#dbeafe;">
                <td colspan="2" class="text-center">TOTAL PERJALANAN</td>
                <td class="text-center"><?= $count_perjalanan ?></td>
                <td class="text-right font-mono" style="color:#1d4ed8"><?= number_format($total_susut_perjalanan, 0, ',', '.') ?> g</td>
                <td class="text-right font-mono">Rp <?= number_format($biaya_susut_perjalanan, 0, ',', '.') ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- 5. BAGIAN III: REKAPITULASI PENYUSUTAN PER PRODUK (SEMUA PRODUK) -->
    <div class="section-title">
      <span class="badge-num">III</span>
      <span>Rekapitulasi Penyusutan per Jenis Produk (Tahun <?= $tahun ?>)</span>
    </div>

    <div class="table-container">
      <?php if (!empty($rank_map)): ?>
        <table class="data-table" id="tableProduk">
          <thead>
            <tr>
              <th class="text-center" style="width:30px">No</th>
              <th>Nama Produk</th>
              <th class="text-center" style="width:80px">Total Kejadian</th>
              <th class="text-right">Susut Gudang</th>
              <th class="text-right">Susut Perjalanan</th>
              <th class="text-right">Total Susut (g)</th>
              <th class="text-right">Total Susut (kg)</th>
              <th class="text-right">Estimasi Kerugian (Rp)</th>
              <th class="text-center" style="width:70px">Kontribusi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rnk = 1;
            foreach ($rank_map as $rm):
              $pct_p = $total_susut_all > 0 ? ($rm['total'] / $total_susut_all) * 100 : 0;
              $rm_gudang_fmt = $rm['gudang'] > 0 ? number_format($rm['gudang'], 0, ',', '.') . ' g' : '0 g';
              $rm_pjln_fmt   = $rm['perjalanan'] > 0 ? number_format($rm['perjalanan'], 0, ',', '.') . ' g' : '0 g';
              $rm_total_fmt  = number_format($rm['total'], 0, ',', '.') . ' g';
              $rm_biaya_fmt  = 'Rp ' . number_format($rm['biaya'], 0, ',', '.');
              $cnt = $rm['count']; // kejadian = 0 jika tidak ada penyusutan
            ?>
              <tr style="<?= $rm['total'] == 0 ? 'color:#94a3b8;' : '' ?>">
                <td class="text-center"><strong><?= $rnk++ ?></strong></td>
                <td><strong><?= htmlspecialchars($rm['name']) ?></strong></td>
                <td class="text-center"><?= $cnt ?></td>
                <td class="text-right font-mono" style="<?= $rm['gudang'] > 0 ? 'color:#c2410c;' : '' ?>"><?= $rm_gudang_fmt ?></td>
                <td class="text-right font-mono" style="<?= $rm['perjalanan'] > 0 ? 'color:#1d4ed8;' : '' ?>"><?= $rm_pjln_fmt ?></td>
                <td class="text-right font-mono"><strong><?= $rm_total_fmt ?></strong></td>
                <td class="text-right font-mono"><?= number_format($rm['total'] / 1000, 2, ',', '.') ?> kg</td>
                <td class="text-right font-mono" style="<?= $rm['biaya'] > 0 ? 'color:#b91c1c; font-weight:700;' : '' ?>"><?= $rm_biaya_fmt ?></td>
                <td class="text-center font-mono"><?= number_format($pct_p, 1, ',', '.') ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
              <td class="text-center"><?= $count_all ?></td>
              <td class="text-right font-mono" style="color:#c2410c"><?= number_format($total_susut_gudang, 0, ',', '.') ?> g</td>
              <td class="text-right font-mono" style="color:#1d4ed8"><?= number_format($total_susut_perjalanan, 0, ',', '.') ?> g</td>
              <td class="text-right font-mono"><?= number_format($total_susut_all, 0, ',', '.') ?> g</td>
              <td class="text-right font-mono"><?= number_format($total_susut_all / 1000, 2, ',', '.') ?> kg</td>
              <td class="text-right font-mono" style="color:#b91c1c">Rp <?= number_format($total_biaya_susut, 0, ',', '.') ?></td>
              <td class="text-center font-mono">100.0%</td>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <p style="color:#64748b; font-style:italic; padding:10px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:4px; text-align:center;">
          Tidak ada data master produk.
        </p>
      <?php endif; ?>
    </div>

    <!-- 6. BAGIAN IV: RINCIAN LOG PENYUSUTAN (TAHUNAN) -->
    <div class="section-title">
      <span class="badge-num">IV</span>
      <span>Rincian Log Penyusutan (Tahun <?= $tahun ?>)</span>
    </div>

    <div class="table-container">
      <?php if (!empty($all_logs)): ?>
        <table class="data-table" id="tableLog">
          <thead>
            <tr>
              <th class="text-center" style="width:26px">No</th>
              <th class="whitespace-nowrap">Ref / Invoice</th>
              <th class="text-center whitespace-nowrap">Tanggal & Jam</th>
              <th class="text-center">Jenis</th>
              <th>Nama Produk</th>
              <th class="text-right whitespace-nowrap">Stok Sblm</th>
              <th class="text-right whitespace-nowrap">Susut (g)</th>
              <th class="text-right whitespace-nowrap">Stok Ssdh</th>
              <th class="text-right whitespace-nowrap">HPP / Harga</th>
              <th class="text-right whitespace-nowrap">Est. Kerugian</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_logs as $idx => $log): ?>
              <tr>
                <td class="text-center"><?= $idx + 1 ?></td>
                <td class="font-mono whitespace-nowrap"><?= htmlspecialchars($log['ref_no'] ?? '-') ?></td>
                <td class="text-center whitespace-nowrap font-mono">
                  <?= date('d/m/Y', strtotime($log['adjustment_date'])) ?>
                  <span style="color:#64748b; font-size:8.5px;"><?= date('H:i', strtotime($log['adjustment_date'])) ?></span>
                </td>
                <td class="text-center whitespace-nowrap">
                  <?php if ($log['jenis'] === 'gudang'): ?>
                    <span class="badge-jenis gudang">Gudang</span>
                  <?php else: ?>
                    <span class="badge-jenis perjalanan">Perjalanan</span>
                  <?php endif; ?>
                </td>
                <td class="whitespace-nowrap"><strong><?= htmlspecialchars($log['product_name']) ?></strong></td>
                <td class="text-right font-mono whitespace-nowrap"><?= number_format($log['qty_before'], 0, ',', '.') ?> g</td>
                <td class="text-right font-mono whitespace-nowrap" style="color:#dc2626; font-weight:700;">
                  <?= number_format($log['qty_shrinkage'], 0, ',', '.') ?> g
                </td>
                <td class="text-right font-mono whitespace-nowrap"><?= number_format($log['qty_after'], 0, ',', '.') ?> g</td>
                <td class="text-right font-mono whitespace-nowrap">
                  <?php if (!empty($log['hpp_before']) && $log['hpp_before'] > 0): ?>
                    Rp <?= number_format($log['hpp_before'], 0, ',', '.') ?>
                  <?php elseif (!empty($log['price']) && $log['price'] > 0): ?>
                    Rp <?= number_format($log['price'], 0, ',', '.') ?>
                  <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-right font-mono whitespace-nowrap" style="color:#b91c1c; font-weight:700;">
                  Rp <?= number_format($log['biaya_susut'], 0, ',', '.') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" class="text-center">TOTAL <?= count($all_logs) ?> LOG</td>
              <td class="text-right font-mono">—</td>
              <td class="text-right font-mono" style="color:#dc2626"><?= number_format($total_susut_all, 0, ',', '.') ?> g</td>
              <td class="text-right font-mono">—</td>
              <td class="text-right font-mono">—</td>
              <td class="text-right font-mono" style="color:#b91c1c">Rp <?= number_format($total_biaya_susut, 0, ',', '.') ?></td>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <p style="color:#64748b; font-style:italic; padding:10px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:4px; text-align:center;">
          Tidak ada catatan log penyusutan untuk tahun <?= $tahun ?>.
        </p>
      <?php endif; ?>
    </div>

    <!-- 7. LEMBAR PENGESAHAN / TANDA TANGAN -->
    <div class="sig-section">
      <div class="sig-location">
        Banjarbaru, <?= date('d F Y') ?>
      </div>
      <div class="sig-grid">
        <div class="sig-box">
          <div class="sig-title">Dibuat Oleh,</div>
          <div class="sig-name"><?= htmlspecialchars($printed_by) ?></div>
          <div class="sig-role">Bagian Gudang & Produksi</div>
        </div>
        <div class="sig-box">
          <div class="sig-title">Diperiksa Oleh,</div>
          <div class="sig-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
          <div class="sig-role">Supervisor Operasional</div>
        </div>
        <div class="sig-box">
          <div class="sig-title">Mengetahui & Menyetujui,</div>
          <div class="sig-name">Abdul Hadi</div>
          <div class="sig-role">Pimpinan Fayyfir</div>
        </div>
      </div>
    </div>

    <!-- 8. FOOTER DOKUMEN -->
    <footer class="report-footer">
      <div>Fayyfir Management System &bull; Dokumen Rahasia & Resmi Perusahaan</div>
      <div>Laporan Penyusutan Tahunan &bull; Periode Tahun <?= $tahun ?> &bull; Hal. 1 dari 1</div>
    </footer>

  </div>

  <script>
    // Auto-print jika parameter ?print=1
    <?php if ($auto_print): ?>
    window.addEventListener('load', function() {
      setTimeout(() => window.print(), 350);
    });
    <?php endif; ?>

    // Ekspor Data ke CSV
    function exportToCSV() {
      const rows = [];
      // Judul Laporan
      rows.push(['LAPORAN PENYUSUTAN TAHUNAN FAYYFIR']);
      rows.push(['Tahun:', '<?= $tahun ?>']);
      rows.push(['Tanggal Cetak:', '<?= $printed_at ?>']);
      rows.push(['Total Susut Gudang (g):', '<?= $total_susut_gudang ?>']);
      rows.push(['Total Susut Perjalanan (g):', '<?= $total_susut_perjalanan ?>']);
      rows.push(['Total Susut Keseluruhan (g):', '<?= $total_susut_all ?>']);
      rows.push(['Estimasi Total Kerugian (Rp):', '<?= $total_biaya_susut ?>']);
      rows.push([]);

      // Data Rekap Bulanan
      rows.push(['REKAPITULASI BULANAN']);
      rows.push(['Bulan', 'Kejadian Gudang', 'Susut Gudang (g)', 'Biaya Gudang (Rp)', 'Kirim Invc', 'Susut Perjalanan (g)', 'Biaya Perjalanan (Rp)', 'Total Susut (g)', 'Total Biaya (Rp)']);
      <?php foreach ($rekap_bulan as $rb): ?>
      rows.push([
        '<?= $rb['nama'] ?>',
        '<?= $rb['count_gudang'] ?>',
        '<?= $rb['susut_gudang'] ?>',
        '<?= $rb['biaya_gudang'] ?>',
        '<?= $rb['count_perjalanan'] ?>',
        '<?= $rb['susut_perjalanan'] ?>',
        '<?= $rb['biaya_perjalanan'] ?>',
        '<?= $rb['total_susut'] ?>',
        '<?= $rb['total_biaya'] ?>'
      ]);
      <?php endforeach; ?>
      rows.push([]);

      // Data Detail Log
      rows.push(['RINCIAN LOG PENYUSUTAN TAHUN <?= $tahun ?>']);
      rows.push(['No', 'Ref / Invoice', 'Tanggal & Waktu', 'Jenis', 'Nama Produk', 'Stok Sebelum (g)', 'Susut (g)', 'Stok Sesudah (g)', 'HPP / Harga (Rp)', 'Est Biaya Susut (Rp)']);
      <?php foreach ($all_logs as $i => $l): ?>
      rows.push([
        '<?= $i + 1 ?>',
        '<?= addslashes($l['ref_no'] ?? '') ?>',
        '<?= $l['adjustment_date'] ?>',
        '<?= $l['jenis'] ?>',
        '<?= addslashes($l['product_name']) ?>',
        '<?= $l['qty_before'] ?>',
        '<?= $l['qty_shrinkage'] ?>',
        '<?= $l['qty_after'] ?>',
        '<?= !empty($l['hpp_before']) ? $l['hpp_before'] : ($l['price'] ?? 0) ?>',
        '<?= $l['biaya_susut'] ?>'
      ]);
      <?php endforeach; ?>

      // Convert rows to CSV content
      const csvContent = rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");
      const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.setAttribute("href", url);
      link.setAttribute("download", "Laporan_Penyusutan_Tahunan_<?= $tahun ?>.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>
</body>
</html>
