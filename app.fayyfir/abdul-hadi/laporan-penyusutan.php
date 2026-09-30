<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

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
// FILTERS
// ============================================================
$f_from    = isset($_GET['from'])    && $_GET['from']    !== '' ? $_GET['from']    : null;
$f_to      = isset($_GET['to'])      && $_GET['to']      !== '' ? $_GET['to']      : null;
$f_year    = isset($_GET['tahun'])   && $_GET['tahun']   !== '' ? (int)$_GET['tahun'] : null;
$f_product = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;
$f_type    = isset($_GET['type'])    && in_array($_GET['type'], ['gudang','perjalanan']) ? $_GET['type'] : null;
$f_all     = isset($_GET['all'])     && $_GET['all'] === '1';

$cm_start = date('Y-m-01');
$cm_end   = date('Y-m-t');
$lm_start = date('Y-m-01', strtotime('first day of last month'));
$lm_end   = date('Y-m-t', strtotime('last day of last month'));
$cy_start = date('Y-01-01');
$cy_end   = date('Y-12-31');

// Penentuan periode: jika ada filter tahun atau jika belum ada filter tanggal & all
if ($f_year) {
  $f_from = "{$f_year}-01-01";
  $f_to   = "{$f_year}-12-31";
  $active_year = $f_year;
} elseif (!$f_from && !$f_to && !$f_all) {
  // Default per tahun berjalan sesuai instruksi waktu laporan per tahun
  $f_year = $current_year;
  $f_from = "{$current_year}-01-01";
  $f_to   = "{$current_year}-12-31";
  $active_year = $current_year;
} else {
  $active_year = $f_from ? (int)date('Y', strtotime($f_from)) : $current_year;
}

$active_preset = 'this_year';
if ($f_all) {
  $active_preset = 'all';
  $f_from = null;
  $f_to   = null;
  $f_year = null;
} elseif ($f_from === $cm_start && $f_to === $cm_end) {
  $active_preset = 'this_month';
} elseif ($f_from === $lm_start && $f_to === $lm_end) {
  $active_preset = 'last_month';
} elseif ($f_year && $f_year === $current_year && $f_from === $cy_start && $f_to === $cy_end) {
  $active_preset = 'this_year';
} elseif ($f_year) {
  $active_preset = "year_{$f_year}";
} elseif ($f_from || $f_to) {
  $active_preset = 'custom';
}

// ============================================================
// Ambil semua produk untuk dropdown filter
// ============================================================
$all_products = [];
$rp = $conn->query("SELECT id, product_name FROM product_stocks ORDER BY product_name ASC");
if ($rp) { while ($r = $rp->fetch_assoc()) $all_products[] = $r; }

// ============================================================
// BUILD WHERE clauses
// ============================================================
$where_g  = ["psl.qty_shrinkage > 0"];
$params_g = [];
$types_g  = "";
if ($f_from)    { $where_g[] = "DATE(psl.adjustment_date) >= ?"; $params_g[] = $f_from;    $types_g .= "s"; }
if ($f_to)      { $where_g[] = "DATE(psl.adjustment_date) <= ?"; $params_g[] = $f_to;      $types_g .= "s"; }
if ($f_product) { $where_g[] = "psl.product_id = ?";            $params_g[] = $f_product;  $types_g .= "i"; }

$where_p  = ["sp.qty_shrinkage > 0"];
$params_p = [];
$types_p  = "";
if ($f_from)    { $where_p[] = "DATE(sp.selling_date) >= ?"; $params_p[] = $f_from;   $types_p .= "s"; }
if ($f_to)      { $where_p[] = "DATE(sp.selling_date) <= ?"; $params_p[] = $f_to;     $types_p .= "s"; }
if ($f_product) { $where_p[] = "sp.product_id = ?";         $params_p[] = $f_product; $types_p .= "i"; }

$sql_w_g = implode(' AND ', $where_g);
$sql_w_p = implode(' AND ', $where_p);

// ============================================================
// QUERY: Log Penyusutan Gudang
// ============================================================
$logs_gudang = [];
if ($f_type === null || $f_type === 'gudang') {
  $sql_g = "
    SELECT psl.id, psl.ref_no, psl.product_id, ps.product_name,
           psl.adjustment_date, psl.qty_before, psl.qty_after, psl.qty_shrinkage,
           psl.unsold_cost_at_time, psl.hpp_before, psl.hpp_after, psl.notes,
           (psl.hpp_before * psl.qty_shrinkage) AS biaya_susut
    FROM product_shrinkage_logs psl
    JOIN product_stocks ps ON ps.id = psl.product_id
    WHERE {$sql_w_g}
    ORDER BY psl.adjustment_date DESC, psl.id DESC
  ";
  $stmt_g = $conn->prepare($sql_g);
  if ($types_g && $params_g) $stmt_g->bind_param($types_g, ...$params_g);
  $stmt_g->execute();
  $res_g = $stmt_g->get_result();
  while ($r = $res_g->fetch_assoc()) $logs_gudang[] = $r;
  $stmt_g->close();
}

// ============================================================
// QUERY: Log Penyusutan Perjalanan
// ============================================================
$logs_perjalanan = [];
if ($f_type === null || $f_type === 'perjalanan') {
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
    ORDER BY sp.selling_date DESC, sp.id DESC
  ";
  $stmt_p = $conn->prepare($sql_p);
  if ($types_p && $params_p) $stmt_p->bind_param($types_p, ...$params_p);
  $stmt_p->execute();
  $res_p = $stmt_p->get_result();
  while ($r = $res_p->fetch_assoc()) $logs_perjalanan[] = $r;
  $stmt_p->close();
}

// ============================================================
// KPI TOTALS
// ============================================================
$total_susut_gudang     = array_sum(array_column($logs_gudang,     'qty_shrinkage'));
$total_susut_perjalanan = array_sum(array_column($logs_perjalanan, 'qty_shrinkage'));
$total_susut_all        = $total_susut_gudang + $total_susut_perjalanan;
$biaya_susut_gudang     = array_sum(array_column($logs_gudang,     'biaya_susut'));
$biaya_susut_perjalanan = array_sum(array_column($logs_perjalanan, 'biaya_susut'));
$total_biaya_susut      = $biaya_susut_gudang + $biaya_susut_perjalanan;

// ============================================================
// RANKING per produk
// ============================================================
$rank_map = [];
foreach ($logs_gudang as $lg) {
  $pid = $lg['product_id'];
  if (!isset($rank_map[$pid])) $rank_map[$pid] = ['name' => $lg['product_name'], 'gudang' => 0, 'perjalanan' => 0, 'biaya' => 0.0];
  $rank_map[$pid]['gudang'] += (float)$lg['qty_shrinkage'];
  $rank_map[$pid]['biaya']  += (float)$lg['biaya_susut'];
}
foreach ($logs_perjalanan as $lp) {
  $pid = $lp['product_id'];
  if (!isset($rank_map[$pid])) $rank_map[$pid] = ['name' => $lp['product_name'], 'gudang' => 0, 'perjalanan' => 0, 'biaya' => 0.0];
  $rank_map[$pid]['perjalanan'] += (float)$lp['qty_shrinkage'];
  $rank_map[$pid]['biaya']      += (float)$lp['biaya_susut'];
}
foreach ($rank_map as &$rm) $rm['total'] = $rm['gudang'] + $rm['perjalanan'];
unset($rm);
usort($rank_map, fn($a, $b) => $b['total'] <=> $a['total']);

// ============================================================
// CHART DATA: Bar — per produk (top 10)
// ============================================================
$chart_labels     = [];
$chart_gudang     = [];
$chart_perjalanan = [];
foreach (array_slice($rank_map, 0, 10) as $rm) {
  $chart_labels[]     = $rm['name'];
  $chart_gudang[]     = round($rm['gudang'],     2);
  $chart_perjalanan[] = round($rm['perjalanan'], 2);
}

// ============================================================
// CHART DATA: Line — Tren Bulanan
// ============================================================
$trend_map = [];
foreach ($logs_gudang as $lg) {
  $m = date('Y-m', strtotime($lg['adjustment_date']));
  if (!isset($trend_map[$m])) $trend_map[$m] = ['gudang' => 0, 'perjalanan' => 0];
  $trend_map[$m]['gudang'] += (float)$lg['qty_shrinkage'];
}
foreach ($logs_perjalanan as $lp) {
  $m = date('Y-m', strtotime($lp['adjustment_date']));
  if (!isset($trend_map[$m])) $trend_map[$m] = ['gudang' => 0, 'perjalanan' => 0];
  $trend_map[$m]['perjalanan'] += (float)$lp['qty_shrinkage'];
}
ksort($trend_map);
$trend_labels     = array_map(fn($k) => date('M Y', strtotime($k . '-01')), array_keys($trend_map));
$trend_gudang_arr = array_column(array_values($trend_map), 'gudang');
$trend_pjln_arr   = array_column(array_values($trend_map), 'perjalanan');

// ============================================================
// COMBINED LOG (sorted date desc)
// ============================================================
$all_logs = [];
foreach ($logs_gudang     as $lg) $all_logs[] = array_merge($lg, ['jenis' => 'gudang']);
foreach ($logs_perjalanan as $lp) $all_logs[] = array_merge($lp, ['jenis' => 'perjalanan']);
usort($all_logs, fn($a, $b) => strtotime($b['adjustment_date']) <=> strtotime($a['adjustment_date']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laporan Penyusutan - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .card-kpi { transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .card-kpi:hover { transform: translateY(-3px); box-shadow: 0 10px 28px 0 rgba(0,0,0,0.11); }
    .badge-gudang     { background:#fef3c7; color:#b45309; }
    .badge-perjalanan { background:#dbeafe; color:#1d4ed8; }
    .gradient-header  { background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%); }
    .gradient-orange  { background: linear-gradient(135deg,#ea580c 0%,#f97316 100%); }
    .gradient-blue    { background: linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%); }
    .gradient-red     { background: linear-gradient(135deg,#b91c1c 0%,#ef4444 100%); }
    .gradient-purple  { background: linear-gradient(135deg,#7c3aed 0%,#a855f7 100%); }
    .table-row:nth-child(even) { background-color:#f9fafb; }
    .table-row:hover { background-color:#fffbeb !important; }
    .filter-pill {
      display:inline-flex; align-items:center; gap:4px;
      padding:5px 14px; border-radius:9999px; font-size:12px;
      font-weight:600; cursor:pointer; transition:all 0.15s; border:2px solid transparent;
    }
    .filter-pill.active  { background:#1e293b; color:#fff; border-color:#1e293b; }
    .filter-pill:not(.active) { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }
    .filter-pill:not(.active):hover { border-color:#f97316; color:#ea580c; }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- ====== HEADER ====== -->
  <header class="gradient-header text-white py-4 px-6 fixed top-0 left-0 right-0 z-40 shadow-xl">
    <div class="flex justify-between items-center max-w-7xl mx-auto">
      <div class="flex items-center gap-3">
        <a href="hasil-produksi.php" class="flex items-center gap-1 text-orange-400 hover:text-orange-300 text-sm font-medium transition">
          <span class="material-symbols-outlined text-base">chevron_left</span>
          <span class="hidden sm:inline">Hasil Produksi</span>
        </a>
        <span class="text-gray-600 text-sm">|</span>
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-orange-400 text-xl">analytics</span>
          <h1 class="text-base font-bold">Laporan Penyusutan</h1>
          <span class="bg-orange-500 text-white text-xs font-bold px-2.5 py-0.5 rounded-full shadow-sm">
            Tahun <?= $active_year ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button type="button" onclick="openPrintModal()"
          class="inline-flex items-center gap-1.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow transition cursor-pointer">
          <span class="material-symbols-outlined text-sm">print</span>
          <span>Cetak Laporan</span>
        </button>
        <div class="text-xs text-gray-400 hidden sm:block"><?= date('d M Y, H:i') ?> WIB</div>
      </div>
    </div>
  </header>

  <main class="pt-24 px-4 pb-16 max-w-7xl mx-auto space-y-6">

    <!-- ====== FILTER ====== -->
    <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
      <div class="flex flex-wrap justify-between items-center gap-2 mb-4 pb-3 border-b border-gray-100">
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
          <span class="material-symbols-outlined text-base text-orange-500">filter_list</span>
          Filter Periode & Laporan (Tahunan)
        </h2>
        <div class="flex items-center gap-2">
          <button type="button" onclick="openPrintModal()"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg border border-orange-200 transition cursor-pointer">
            <span class="material-symbols-outlined text-sm">print</span>
            <span>Cetak Laporan Tahun <?= $active_year ?></span>
          </button>
        </div>
      </div>

      <form method="GET" id="filterForm" class="space-y-4">
        <!-- Preset Waktu / Filter Per Tahun -->
        <div>
          <p class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wide">Pilih Periode / Tahun Laporan</p>
          <div class="flex flex-wrap gap-2 items-center">
            <!-- Year Pills -->
            <?php foreach ($available_years as $yr): ?>
              <button type="button"
                class="filter-pill <?= ($f_year === $yr || ($active_preset === 'this_year' && $yr === $current_year && !$f_year && !$f_from)) ? 'active' : '' ?>"
                onclick="setYear(<?= $yr ?>)">
                <span class="material-symbols-outlined text-xs">calendar_today</span>
                Tahun <?= $yr ?>
              </button>
            <?php endforeach; ?>

            <span class="text-gray-300">|</span>

            <!-- Month & All Presets -->
            <button type="button"
              class="filter-pill <?= $active_preset === 'this_month' ? 'active' : '' ?>"
              onclick="setPreset('<?= $cm_start ?>', '<?= $cm_end ?>')">
              Bulan Ini
            </button>
            <button type="button"
              class="filter-pill <?= $active_preset === 'last_month' ? 'active' : '' ?>"
              onclick="setPreset('<?= $lm_start ?>', '<?= $lm_end ?>')">
              Bulan Lalu
            </button>
            <button type="button"
              class="filter-pill <?= $active_preset === 'all' ? 'active' : '' ?>"
              onclick="setAll()">
              Semua Waktu
            </button>
          </div>
        </div>

        <input type="hidden" name="all" id="f_all" value="<?= $f_all ? '1' : '' ?>">

        <!-- Inputs Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Tahun</label>
            <select name="tahun" id="f_tahun" onchange="onYearSelectChange(this.value)"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white">
              <option value="">-- Kustom Tanggal --</option>
              <?php foreach ($available_years as $yr): ?>
                <option value="<?= $yr ?>" <?= ($f_year === $yr) ? 'selected' : '' ?>>Tahun <?= $yr ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
            <input type="date" name="from" id="f_from" value="<?= htmlspecialchars($f_from ?? '') ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="to" id="f_to" value="<?= htmlspecialchars($f_to ?? '') ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Produk</label>
            <select name="product" id="f_product" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white">
              <option value="">Semua Produk</option>
              <?php foreach ($all_products as $ap): ?>
                <option value="<?= $ap['id'] ?>" <?= $f_product === (int)$ap['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ap['product_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Penyusutan</label>
            <select name="type" id="f_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white">
              <option value="">Semua Jenis</option>
              <option value="gudang"     <?= $f_type === 'gudang'     ? 'selected' : '' ?>>🏭 Penyusutan Gudang</option>
              <option value="perjalanan" <?= $f_type === 'perjalanan' ? 'selected' : '' ?>>🚛 Penyusutan Perjalanan</option>
            </select>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
          <div class="flex gap-2">
            <button type="submit"
              class="inline-flex items-center gap-1.5 bg-gray-900 hover:bg-gray-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition shadow cursor-pointer">
              <span class="material-symbols-outlined text-base">search</span>
              Terapkan Filter
            </button>
            <a href="laporan-penyusutan.php?tahun=<?= $current_year ?>"
              class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 hover:border-gray-500 transition">
              <span class="material-symbols-outlined text-base">restart_alt</span>
              Reset
            </a>
          </div>

          <div class="flex items-center gap-2">
            <button type="button" onclick="openPrintModal()"
              class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow cursor-pointer">
              <span class="material-symbols-outlined text-base">print</span>
              <span>Cetak Laporan Tahunan</span>
            </button>
          </div>
        </div>
      </form>
    </section>

    <!-- ====== KPI CARDS ====== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

      <!-- Susut Gudang -->
      <div class="card-kpi bg-white rounded-2xl shadow-sm border border-orange-100 p-5 relative overflow-hidden">
        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full gradient-orange opacity-10"></div>
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl gradient-orange flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-white text-xl">warehouse</span>
          </div>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full badge-gudang">Gudang</span>
        </div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Penyusutan Gudang</p>
        <p class="text-2xl font-extrabold text-gray-900"><?= number_format($total_susut_gudang, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">g</span></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= count($logs_gudang) ?> kejadian timbang ulang</p>
      </div>

      <!-- Susut Perjalanan -->
      <div class="card-kpi bg-white rounded-2xl shadow-sm border border-blue-100 p-5 relative overflow-hidden">
        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full gradient-blue opacity-10"></div>
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl gradient-blue flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-white text-xl">local_shipping</span>
          </div>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full badge-perjalanan">Perjalanan</span>
        </div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Penyusutan Perjalanan</p>
        <p class="text-2xl font-extrabold text-gray-900"><?= number_format($total_susut_perjalanan, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">g</span></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= count($logs_perjalanan) ?> transaksi pengiriman</p>
      </div>

      <!-- Total Penyusutan -->
      <div class="card-kpi bg-white rounded-2xl shadow-sm border border-purple-100 p-5 relative overflow-hidden">
        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full gradient-purple opacity-10"></div>
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl gradient-purple flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-white text-xl">scale</span>
          </div>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Total</span>
        </div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Total Penyusutan</p>
        <p class="text-2xl font-extrabold text-gray-900"><?= number_format($total_susut_all, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">g</span></p>
        <p class="text-xs text-gray-400 mt-0.5">Gabungan gudang & perjalanan</p>
      </div>

      <!-- Estimasi Biaya -->
      <div class="card-kpi bg-white rounded-2xl shadow-sm border border-red-200 p-5 relative overflow-hidden">
        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full gradient-red opacity-10"></div>
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl gradient-red flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-white text-xl">money_off</span>
          </div>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Kerugian</span>
        </div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estimasi Biaya Susut</p>
        <p class="text-xl font-extrabold text-red-600">Rp <?= number_format($total_biaya_susut, 0, ',', '.') ?></p>
        <div class="mt-1 space-y-0.5">
          <p class="text-xs text-gray-400">Gudang: <span class="text-orange-600 font-semibold">Rp <?= number_format($biaya_susut_gudang, 0, ',', '.') ?></span></p>
          <p class="text-xs text-gray-400">Perjalanan: <span class="text-blue-600 font-semibold">Rp <?= number_format($biaya_susut_perjalanan, 0, ',', '.') ?></span></p>
        </div>
      </div>
    </div>

    <!-- ====== CHARTS ====== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- Bar Chart: Per Produk -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <h3 class="text-sm font-bold text-gray-800 mb-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-base text-orange-500">bar_chart</span>
          Penyusutan per Produk
          <span class="text-xs text-gray-400 font-normal">(Top 10)</span>
        </h3>
        <p class="text-xs text-gray-400 mb-4">Perbandingan susut gudang vs perjalanan per jenis produk</p>
        <?php if (!empty($chart_labels)): ?>
          <div style="position:relative;height:280px">
            <canvas id="chartProduk"></canvas>
          </div>
        <?php else: ?>
          <div class="flex flex-col items-center justify-center h-52 text-gray-300">
            <span class="material-symbols-outlined text-5xl mb-2">bar_chart</span>
            <p class="text-sm">Belum ada data penyusutan</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Line Chart: Tren Bulanan -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <h3 class="text-sm font-bold text-gray-800 mb-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-base text-blue-500">show_chart</span>
          Tren Penyusutan Bulanan
        </h3>
        <p class="text-xs text-gray-400 mb-4">Jumlah susut (gram) per bulan — gudang vs perjalanan</p>
        <?php if (!empty($trend_labels)): ?>
          <div style="position:relative;height:280px">
            <canvas id="chartTren"></canvas>
          </div>
        <?php else: ?>
          <div class="flex flex-col items-center justify-center h-52 text-gray-300">
            <span class="material-symbols-outlined text-5xl mb-2">show_chart</span>
            <p class="text-sm">Belum ada data tren</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ====== RANKING PRODUK ====== -->
    <?php if (!empty($rank_map)): ?>
    <section class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2 bg-gradient-to-r from-gray-900 to-gray-800">
        <span class="material-symbols-outlined text-orange-400 text-xl">leaderboard</span>
        <h3 class="text-sm font-bold text-white">Ranking Produk — Paling Banyak Penyusutan</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-center w-12">Rank</th>
              <th class="px-4 py-3 text-left">Produk</th>
              <th class="px-4 py-3 text-right whitespace-nowrap text-orange-600">Susut Gudang</th>
              <th class="px-4 py-3 text-right whitespace-nowrap text-blue-600">Susut Perjalanan</th>
              <th class="px-4 py-3 text-right whitespace-nowrap">Total Susut</th>
              <th class="px-4 py-3 text-right whitespace-nowrap text-red-600">Est. Kerugian</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($rank_map as $ri => $rm): ?>
              <tr class="table-row">
                <td class="px-4 py-3 text-center">
                  <?php if ($ri === 0): ?>
                    <span class="inline-flex w-8 h-8 rounded-full bg-yellow-400 text-yellow-900 font-extrabold text-sm items-center justify-center shadow-sm">1</span>
                  <?php elseif ($ri === 1): ?>
                    <span class="inline-flex w-8 h-8 rounded-full bg-gray-300 text-gray-700 font-extrabold text-sm items-center justify-center">2</span>
                  <?php elseif ($ri === 2): ?>
                    <span class="inline-flex w-8 h-8 rounded-full bg-orange-300 text-orange-900 font-extrabold text-sm items-center justify-center">3</span>
                  <?php else: ?>
                    <span class="text-gray-400 font-semibold text-xs"><?= $ri + 1 ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($rm['name']) ?></td>
                <td class="px-4 py-3 text-right font-semibold text-orange-600"><?= number_format($rm['gudang'], 0, ',', '.') ?> g</td>
                <td class="px-4 py-3 text-right font-semibold text-blue-600"><?= number_format($rm['perjalanan'], 0, ',', '.') ?> g</td>
                <td class="px-4 py-3 text-right">
                  <span class="font-bold text-gray-900"><?= number_format($rm['total'], 0, ',', '.') ?> g</span>
                </td>
                <td class="px-4 py-3 text-right font-bold text-red-600 whitespace-nowrap">Rp <?= number_format($rm['biaya'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <!-- ====== LOG DETAIL ====== -->
    <section class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-gray-500 text-xl">receipt_long</span>
          <h3 class="text-sm font-bold text-gray-800">Log Penyusutan Detail</h3>
          <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?= count($all_logs) ?> entri</span>
        </div>
        <div class="flex items-center gap-2 text-xs">
          <span class="inline-flex items-center gap-1 badge-gudang px-2.5 py-1 rounded-full font-semibold">
            <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>Gudang
          </span>
          <span class="inline-flex items-center gap-1 badge-perjalanan px-2.5 py-1 rounded-full font-semibold">
            <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>Perjalanan
          </span>
        </div>
      </div>

      <?php if (!empty($all_logs)): ?>
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
          <input type="text" id="logSearch" placeholder="🔍 Cari produk, ref, keterangan..."
            class="w-full sm:w-80 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none bg-white">
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs">
            <thead class="bg-gray-800 text-yellow-400 uppercase tracking-wider">
              <tr>
                <th class="px-3 py-2.5 text-center w-8">No</th>
                <th class="px-3 py-2.5 text-left whitespace-nowrap">Ref / Invoice</th>
                <th class="px-3 py-2.5 text-left whitespace-nowrap">Produk</th>
                <th class="px-3 py-2.5 text-center whitespace-nowrap">Tanggal</th>
                <th class="px-3 py-2.5 text-center">Jenis</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok Sebelum</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap text-red-300">Susut</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok Sesudah</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">HPP Sebelum</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">HPP Sesudah</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap text-red-300">Est. Biaya Susut</th>
                <th class="px-3 py-2.5 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="logTbody">
              <?php foreach ($all_logs as $no => $log): ?>
                <tr class="table-row log-row">
                  <td class="px-3 py-2.5 text-center text-gray-400"><?= $no + 1 ?></td>
                  <td class="px-3 py-2.5 font-mono text-gray-600 whitespace-nowrap"><?= htmlspecialchars($log['ref_no'] ?? '-') ?></td>
                  <td class="px-3 py-2.5 font-semibold text-gray-800 whitespace-nowrap"><?= htmlspecialchars($log['product_name']) ?></td>
                  <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-600">
                    <?= date('d M Y', strtotime($log['adjustment_date'])) ?>
                    <?php if ($log['jenis'] === 'gudang'): ?>
                      <div class="text-gray-400"><?= date('H:i', strtotime($log['adjustment_date'])) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <?php if ($log['jenis'] === 'gudang'): ?>
                      <span class="inline-flex items-center gap-0.5 badge-gudang px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">
                        <span class="material-symbols-outlined" style="font-size:10px">warehouse</span>Gudang
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-0.5 badge-perjalanan px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">
                        <span class="material-symbols-outlined" style="font-size:10px">local_shipping</span>Perjalanan
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-2.5 text-right text-gray-600 whitespace-nowrap"><?= number_format($log['qty_before'], 0, ',', '.') ?> g</td>
                  <td class="px-3 py-2.5 text-right font-bold text-red-600 whitespace-nowrap">−<?= number_format($log['qty_shrinkage'], 0, ',', '.') ?> g</td>
                  <td class="px-3 py-2.5 text-right text-gray-700 whitespace-nowrap"><?= number_format($log['qty_after'], 0, ',', '.') ?> g</td>
                  <td class="px-3 py-2.5 text-right text-gray-500 whitespace-nowrap">
                    <?php if ($log['hpp_before'] !== null && $log['hpp_before'] > 0): ?>
                      Rp <?= number_format($log['hpp_before'], 0, ',', '.') ?>
                    <?php else: ?>
                      <span class="text-gray-300">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-2.5 text-right text-gray-500 whitespace-nowrap">
                    <?php if ($log['hpp_after'] !== null && $log['hpp_after'] > 0): ?>
                      Rp <?= number_format($log['hpp_after'], 0, ',', '.') ?>
                    <?php else: ?>
                      <span class="text-gray-300">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-2.5 text-right font-bold text-red-500 whitespace-nowrap">
                    Rp <?= number_format($log['biaya_susut'], 0, ',', '.') ?>
                  </td>
                  <td class="px-3 py-2.5 text-gray-500 max-w-xs" style="min-width:120px"
                    title="<?= htmlspecialchars($log['notes'] ?? '') ?>">
                    <?= $log['notes'] ? htmlspecialchars(mb_strimwidth($log['notes'], 0, 55, '…')) : '<span class="text-gray-300">—</span>' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Footer Totals -->
        <div class="px-5 py-3.5 bg-gray-50 border-t-2 border-gray-200 flex flex-wrap gap-4 justify-between items-center">
          <div class="flex flex-wrap gap-4 text-xs font-semibold text-gray-600">
            <span>Gudang: <span class="text-orange-600"><?= number_format($total_susut_gudang, 0, ',', '.') ?> g</span></span>
            <span>Perjalanan: <span class="text-blue-600"><?= number_format($total_susut_perjalanan, 0, ',', '.') ?> g</span></span>
            <span class="font-bold text-gray-800">Total: <?= number_format($total_susut_all, 0, ',', '.') ?> g</span>
          </div>
          <div class="text-sm font-bold text-red-600">
            Est. Total Kerugian: Rp <?= number_format($total_biaya_susut, 0, ',', '.') ?>
          </div>
        </div>
      <?php else: ?>
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
          <span class="material-symbols-outlined text-6xl mb-3 text-gray-200">receipt_long</span>
          <p class="text-base font-semibold text-gray-500 mb-1">Tidak Ada Data Penyusutan</p>
          <p class="text-sm text-center max-w-sm">Coba ubah filter atau catat penyusutan melalui menu Timbang Ulang di halaman <a href="hasil-produksi.php" class="text-orange-500 hover:underline font-semibold">Hasil Produksi</a>.</p>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <!-- ====== MODAL CETAK LAPORAN TAHUNAN ====== -->
  <div id="printModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all border border-gray-100">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center">
            <span class="material-symbols-outlined text-orange-600 text-xl">print</span>
          </div>
          <div>
            <h3 class="text-sm font-bold text-gray-800">Cetak Laporan Penyusutan</h3>
            <p class="text-xs text-gray-400">Pilih opsi laporan tahunan resmi</p>
          </div>
        </div>
        <button type="button" onclick="closePrintModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form id="printModalForm" method="GET" target="_blank" class="space-y-4">
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Pilih Tahun Laporan</label>
          <select name="tahun" id="modalTahun" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white font-semibold text-gray-800">
            <?php foreach ($available_years as $yr): ?>
              <option value="<?= $yr ?>" <?= $active_year === $yr ? 'selected' : '' ?>>Tahun <?= $yr ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-400 mt-1">Laporan dicetak per tahun lengkap (Januari s/d Desember).</p>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Jenis Penyusutan</label>
          <select name="type" id="modalType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white">
            <option value="">Semua Jenis (Gudang & Perjalanan)</option>
            <option value="gudang" <?= $f_type === 'gudang' ? 'selected' : '' ?>>🏭 Hanya Penyusutan Gudang</option>
            <option value="perjalanan" <?= $f_type === 'perjalanan' ? 'selected' : '' ?>>🚛 Hanya Penyusutan Perjalanan</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Filter Produk</label>
          <select name="product" id="modalProduct" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white">
            <option value="">Semua Produk</option>
            <?php foreach ($all_products as $ap): ?>
              <option value="<?= $ap['id'] ?>" <?= $f_product === (int)$ap['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ap['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 border-t border-gray-100">
          <button type="button" onclick="closePrintModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-xs font-semibold transition cursor-pointer">
            Batal
          </button>
          <button type="button" onclick="submitPrintForm('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow cursor-pointer">
            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
            <span>Unduh PDF</span>
          </button>
          <button type="button" onclick="submitPrintForm('print')" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow cursor-pointer">
            <span class="material-symbols-outlined text-sm">print</span>
            <span>Cetak Dokumen</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ====== SCRIPTS ====== -->
  <script>
    // Filter Preset (Tanggal Khusus)
    function setPreset(from, to) {
      document.getElementById('f_all').value = '';
      document.getElementById('f_tahun').value = '';
      document.getElementById('f_from').value = from || '';
      document.getElementById('f_to').value   = to   || '';
      document.getElementById('filterForm').submit();
    }

    // Filter Per Tahun
    function setYear(yr) {
      document.getElementById('f_all').value = '';
      document.getElementById('f_tahun').value = yr;
      document.getElementById('f_from').value = yr + '-01-01';
      document.getElementById('f_to').value   = yr + '-12-31';
      document.getElementById('filterForm').submit();
    }

    // Filter Semua Waktu
    function setAll() {
      document.getElementById('f_all').value = '1';
      document.getElementById('f_tahun').value = '';
      document.getElementById('f_from').value = '';
      document.getElementById('f_to').value   = '';
      document.getElementById('filterForm').submit();
    }

    // Ubah Tahun pada Select
    function onYearSelectChange(yr) {
      if (yr) {
        document.getElementById('f_from').value = yr + '-01-01';
        document.getElementById('f_to').value   = yr + '-12-31';
        document.getElementById('f_all').value = '';
      }
    }

    // Modal Cetak Laporan
    const printModal = document.getElementById('printModal');
    function openPrintModal() {
      if (printModal) printModal.classList.remove('hidden');
    }
    function closePrintModal() {
      if (printModal) printModal.classList.add('hidden');
    }
    if (printModal) {
      printModal.addEventListener('click', function(e) {
        if (e.target === printModal) closePrintModal();
      });
    }

    function submitPrintForm(target) {
      const form = document.getElementById('printModalForm');
      if (target === 'pdf') {
        form.action = 'laporan-penyusutan-pdf.php';
      } else {
        form.action = 'laporan-penyusutan-cetak.php';
      }
      form.submit();
      closePrintModal();
    }

    // Log Search
    const logSearch = document.getElementById('logSearch');
    if (logSearch) {
      logSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.log-row').forEach(row => {
          row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    const idr = n => new Intl.NumberFormat('id-ID').format(n);

    // ============================================================
    // Bar Chart — per Produk
    // ============================================================
    <?php if (!empty($chart_labels)): ?>
    (function() {
      const ctx = document.getElementById('chartProduk').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($chart_labels) ?>,
          datasets: [
            {
              label: 'Susut Gudang (g)',
              data: <?= json_encode($chart_gudang) ?>,
              backgroundColor: 'rgba(234,88,12,0.80)',
              borderColor: '#c2410c',
              borderWidth: 1.5,
              borderRadius: 5,
              borderSkipped: false,
            },
            {
              label: 'Susut Perjalanan (g)',
              data: <?= json_encode($chart_perjalanan) ?>,
              backgroundColor: 'rgba(37,99,235,0.80)',
              borderColor: '#1d4ed8',
              borderWidth: 1.5,
              borderRadius: 5,
              borderSkipped: false,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', labels: { font: { size: 11, family: 'Inter' }, boxWidth: 12, padding: 12 } },
            tooltip: {
              callbacks: {
                label: ctx => ` ${ctx.dataset.label}: ${idr(ctx.raw)} g`
              }
            }
          },
          scales: {
            x: {
              ticks: { font: { size: 10 }, maxRotation: 30, color: '#6b7280' },
              grid: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { font: { size: 10 }, color: '#6b7280', callback: v => idr(v) + ' g' },
              grid: { color: '#f3f4f6', lineWidth: 1 }
            }
          }
        }
      });
    })();
    <?php endif; ?>

    // ============================================================
    // Line Chart — Tren Bulanan
    // ============================================================
    <?php if (!empty($trend_labels)): ?>
    (function() {
      const ctx = document.getElementById('chartTren').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: <?= json_encode($trend_labels) ?>,
          datasets: [
            {
              label: 'Susut Gudang (g)',
              data: <?= json_encode($trend_gudang_arr) ?>,
              borderColor: '#ea580c',
              backgroundColor: ctx => {
                const g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
                g.addColorStop(0,'rgba(249,115,22,0.30)');
                g.addColorStop(1,'rgba(249,115,22,0.00)');
                return g;
              },
              borderWidth: 2.5,
              pointRadius: 5,
              pointHoverRadius: 7,
              pointBackgroundColor: '#ea580c',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              tension: 0.38,
              fill: true
            },
            {
              label: 'Susut Perjalanan (g)',
              data: <?= json_encode($trend_pjln_arr) ?>,
              borderColor: '#2563eb',
              backgroundColor: ctx => {
                const g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
                g.addColorStop(0,'rgba(59,130,246,0.25)');
                g.addColorStop(1,'rgba(59,130,246,0.00)');
                return g;
              },
              borderWidth: 2.5,
              pointRadius: 5,
              pointHoverRadius: 7,
              pointBackgroundColor: '#2563eb',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              tension: 0.38,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'top', labels: { font: { size: 11, family: 'Inter' }, boxWidth: 12, padding: 12 } },
            tooltip: {
              callbacks: {
                label: ctx => ` ${ctx.dataset.label}: ${idr(ctx.raw)} g`
              }
            }
          },
          scales: {
            x: {
              ticks: { font: { size: 10 }, color: '#6b7280' },
              grid: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { font: { size: 10 }, color: '#6b7280', callback: v => idr(v) + ' g' },
              grid: { color: '#f3f4f6', lineWidth: 1 }
            }
          }
        }
      });
    })();
    <?php endif; ?>
  </script>

</body>
</html>
