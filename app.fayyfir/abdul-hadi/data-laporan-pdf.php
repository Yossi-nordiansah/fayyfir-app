<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["user_id"])) {
  die("Akses ditolak. Silakan login terlebih dahulu.");
}

require_once "tcpdf/tcpdf.php";
require_once "config.php";

// --- Helpers ---
if (!function_exists('fmtIDR')) {
  function fmtIDR($n) { return number_format($n, 0, ',', '.'); }
}
if (!function_exists('fmtNum')) {
  function fmtNum($n, $dec = 2) { return number_format($n, $dec, ',', '.'); }
}

// Auto-create tabel product_shrinkage_logs jika belum ada
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

// Auto-add kolom qty_shrinkage pada selling_products jika belum ada
try {
  $col_check = $conn->query("SHOW COLUMNS FROM `selling_products` LIKE 'qty_shrinkage'");
  if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE `selling_products` ADD COLUMN `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `qty`");
  }
} catch (\Throwable $e) {}

// ============================================================
// PARAMETER FILTER
// ============================================================
$from       = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? date('Y-m-d', strtotime($_GET['start_date'])) : null;
$to         = isset($_GET['end_date'])   && $_GET['end_date']   !== '' ? date('Y-m-d', strtotime($_GET['end_date']))   : null;
$buyer_id   = isset($_GET['buyer'])   && $_GET['buyer']   !== '' ? (int)$_GET['buyer']   : null;
$product_id = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;

// Label Periode
if ($from && $to) {
  $periode_label = date('d/m/Y', strtotime($from)) . ' s/d ' . date('d/m/Y', strtotime($to));
} elseif ($from) {
  $periode_label = 'Sejak ' . date('d/m/Y', strtotime($from));
} elseif ($to) {
  $periode_label = 'Sampai ' . date('d/m/Y', strtotime($to));
} else {
  $periode_label = 'Semua Waktu (All Time)';
}

$printed_by = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Administrator';
$printed_at = date('d/m/Y H:i');

// ============================================================
// BUILD WHERE CLAUSES
// ============================================================
$where = ['1=1'];
$params = [];
$types  = '';
if ($from)       { $where[] = "s.selling_date >= ?"; $params[] = $from . " 00:00:00"; $types .= 's'; }
if ($to)         { $where[] = "s.selling_date <= ?"; $params[] = $to   . " 23:59:59"; $types .= 's'; }
if ($buyer_id)   { $where[] = "s.buyer_id = ?";     $params[] = $buyer_id;            $types .= 'i'; }
if ($product_id) { $where[] = "s.product_id = ?";   $params[] = $product_id;          $types .= 'i'; }
$where_sql = implode(" AND ", $where);

// ============================================================
// KPI QUERIES
// ============================================================
// Total penjualan
$stmt = $conn->prepare("SELECT COALESCE(SUM(s.total_selling),0) AS total FROM selling_products s WHERE $where_sql");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_sales = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// Total penjualan lunas
$where_lunas = array_merge($where, ["s.status = 'Lunas'", "s.invoice_number IS NOT NULL AND s.invoice_number != ''"]);
$stmt_lunas = $conn->prepare("SELECT COALESCE(SUM(s.total_selling),0) AS total FROM selling_products s WHERE " . implode(" AND ", $where_lunas));
if ($params) $stmt_lunas->bind_param($types, ...$params);
$stmt_lunas->execute();
$total_sales_lunas = (float)($stmt_lunas->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_lunas->close();

// Jumlah invoice
$stmt_inv = $conn->prepare("SELECT COUNT(DISTINCT s.invoice_number) AS cnt FROM selling_products s WHERE $where_sql AND s.invoice_number IS NOT NULL AND s.invoice_number != ''");
if ($params) $stmt_inv->bind_param($types, ...$params);
$stmt_inv->execute();
$total_invoices = (int)($stmt_inv->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt_inv->close();

// Total biaya produksi (semua batch selesai, tanpa filter tanggal penjualan)
$prod_where = ['status != \'Proses\'', 'COALESCE(total_output, 0) > 0'];
$prod_params = []; $prod_types = '';
if ($to)         { $prod_where[] = "COALESCE(production_date, DATE(created_at)) <= ?"; $prod_params[] = $to; $prod_types .= 's'; }
if ($product_id) { $prod_where[] = "product_id = ?";                                   $prod_params[] = $product_id; $prod_types .= 'i'; }
$stmt_pt = $conn->prepare("SELECT COALESCE(SUM(total_pro_expenses),0)+COALESCE(SUM(total_pro_materials),0) AS total FROM productions WHERE " . implode(' AND ', $prod_where));
if ($prod_params) $stmt_pt->bind_param($prod_types, ...$prod_params);
$stmt_pt->execute();
$total_biaya_produksi = (float)($stmt_pt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_pt->close();

// ============================================================
// FIFO RINGKASAN PER PRODUK
// ============================================================
$prod_master_conds = ["1=1"];
if ($product_id) $prod_master_conds[] = "ps.id = " . (int)$product_id;
$res_prod_master = $conn->query("
  SELECT ps.id, ps.product_name, ps.quantity AS current_stock, COALESCE(u.symbol, 'g') AS symbol
  FROM product_stocks ps
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE " . implode(" AND ", $prod_master_conds) . "
  ORDER BY ps.product_name ASC
");

// Batches
$batch_conds = ["p.status != 'Proses'", "COALESCE(p.total_output, 0) > 0"];
$batch_params = []; $batch_types = '';
if ($product_id) { $batch_conds[] = "p.product_id = ?"; $batch_params[] = $product_id; $batch_types .= 'i'; }
if ($to)         { $batch_conds[] = "COALESCE(p.production_date, DATE(p.created_at)) <= ?"; $batch_params[] = $to; $batch_types .= 's'; }
$stmt_b = $conn->prepare("
  SELECT p.id AS batch_id, p.product_id, p.production_number,
    COALESCE(p.production_date, DATE(p.created_at)) AS prod_date,
    COALESCE(p.total_output, 0) AS total_output,
    (COALESCE(p.total_pro_materials,0) + COALESCE(p.total_pro_expenses,0)) AS total_cost,
    COALESCE(p.price_weight, 0) AS price_weight,
    COALESCE(p.fix_price, 0) AS fix_price
  FROM productions p
  WHERE " . implode(" AND ", $batch_conds) . "
  ORDER BY COALESCE(p.production_date, DATE(p.created_at)) ASC, p.id ASC
");
if ($batch_params) $stmt_b->bind_param($batch_types, ...$batch_params);
$stmt_b->execute();
$res_b = $stmt_b->get_result();
$batches_by_product = [];
while ($b = $res_b->fetch_assoc()) {
  $pid = (int)$b['product_id'];
  $b_out  = (float)$b['total_output'];
  $b_cost = (float)$b['total_cost'];
  $b_uc   = $b_out > 0 ? ($b_cost / $b_out) : 0;
  $b_fp   = (float)$b['fix_price'];
  $b_pw   = (float)$b['price_weight'];
  $b_tpu  = $b_fp > 0 && $b_out > 0 ? ($b_fp / $b_out) : ($b_pw > 0 ? ($b_pw / 1000) : 0);
  $batches_by_product[$pid][] = [
    'batch_id' => (int)$b['batch_id'], 'production_number' => $b['production_number'],
    'prod_date' => $b['prod_date'], 'output' => $b_out, 'total_cost' => $b_cost,
    'unit_cost' => $b_uc, 'target_price_unit' => $b_tpu,
    'sold_qty' => 0.0, 'sold_cost' => 0.0, 'real_revenue_sold' => 0.0,
    'remaining_qty' => 0.0, 'remaining_cost' => 0.0, 'status_fifo' => ''
  ];
}
$stmt_b->close();

// Sales
$sold_conds = ["1=1"]; $sold_params = []; $sold_types = '';
if ($product_id) { $sold_conds[] = "product_id = ?"; $sold_params[] = $product_id; $sold_types .= 'i'; }
if ($to)         { $sold_conds[] = "selling_date <= ?"; $sold_params[] = $to . " 23:59:59"; $sold_types .= 's'; }
$stmt_s = $conn->prepare("SELECT id, product_id, invoice_number, selling_date, qty, price, total_selling FROM selling_products WHERE " . implode(" AND ", $sold_conds) . " ORDER BY selling_date ASC, id ASC");
if ($sold_params) $stmt_s->bind_param($sold_types, ...$sold_params);
$stmt_s->execute();
$res_s = $stmt_s->get_result();
$total_sold_by_product = [];
$total_selling_by_product = [];
$sales_by_product = [];
while ($s = $res_s->fetch_assoc()) {
  $spid = (int)$s['product_id'];
  $sqty = (float)$s['qty'];
  $stot = (float)$s['total_selling'];
  $total_sold_by_product[$spid] = ($total_sold_by_product[$spid] ?? 0.0) + $sqty;
  $total_selling_by_product[$spid] = ($total_selling_by_product[$spid] ?? 0.0) + $stot;
  $sales_by_product[$spid][] = [
    'invoice_number' => $s['invoice_number'], 'selling_date' => $s['selling_date'],
    'qty' => $sqty, 'rem_qty' => $sqty, 'price' => (float)$s['price'],
    'total_selling' => $stot, 'unit_price' => $sqty > 0 ? ($stot / $sqty) : 0.0
  ];
}
$stmt_s->close();

// Susut per produk
$shrinkage_by_product = [];
$res_sst = $conn->query("SELECT product_id, COALESCE(SUM(qty_shrinkage), 0) AS total_shrinkage FROM product_shrinkage_logs GROUP BY product_id");
if ($res_sst) while ($r = $res_sst->fetch_assoc()) $shrinkage_by_product[(int)$r['product_id']] = (float)$r['total_shrinkage'];

// FIFO calculation
$grand_total_produksi_belum_terjual = 0.0;
$grand_total_biaya_produksi_terjual = 0.0;
$fifo_product_summary = [];
if ($res_prod_master) {
  while ($p = $res_prod_master->fetch_assoc()) {
    $pid = (int)$p['id'];
    $p_batches = $batches_by_product[$pid] ?? [];
    $p_sales   = $sales_by_product[$pid] ?? [];
    $prod_total_output = $prod_total_cost = $prod_sold_qty = $prod_sold_cost = $prod_unsold_qty = $prod_unsold_cost = 0.0;
    $prod_real_revenue = 0.0;
    $sale_idx = 0;
    $num_sales = count($p_sales);
    foreach ($p_batches as &$batch) {
      $b_out = $batch['output']; $b_cost = $batch['total_cost']; $b_uc = $batch['unit_cost'];
      $b_rem = $b_out;
      $prod_total_output += $b_out; $prod_total_cost += $b_cost;
      while ($b_rem > 0.0001 && $sale_idx < $num_sales) {
        $curr_sale = &$p_sales[$sale_idx];
        if ($curr_sale['rem_qty'] <= 0.0001) { $sale_idx++; continue; }
        $take = min($b_rem, $curr_sale['rem_qty']);
        $rev_take = $take * $curr_sale['unit_price'];
        $batch['sold_qty'] += $take;
        $batch['real_revenue_sold'] += $rev_take;
        $curr_sale['rem_qty'] -= $take;
        $b_rem -= $take;
        if ($curr_sale['rem_qty'] <= 0.0001) $sale_idx++;
      }
      $batch['remaining_qty']  = max(0.0, $b_rem);
      $batch['sold_cost']      = $batch['sold_qty'] * $b_uc;
      $batch['remaining_cost'] = max(0.0, $batch['remaining_qty'] * $b_uc);
      $batch['real_margin_sold'] = $batch['real_revenue_sold'] - $batch['sold_cost'];
      $batch['status_fifo'] = $batch['remaining_qty'] <= 0.0001 ? 'Habis Terjual' : ($batch['sold_qty'] > 0.0001 ? 'Sisa Sebagian' : 'Belum Terjual');
      $prod_sold_qty    += $batch['sold_qty'];
      $prod_sold_cost   += $batch['sold_cost'];
      $prod_unsold_qty  += $batch['remaining_qty'];
      $prod_unsold_cost += $batch['remaining_cost'];
    }
    unset($batch);
    $p_sold    = (float)($total_sold_by_product[$pid] ?? 0.0);
    $real_rev  = (float)($total_selling_by_product[$pid] ?? 0.0);
    $cur_stock = (float)($p['current_stock'] ?? max(0.0, $prod_total_output - $p_sold));
    $hpp_sisa  = $cur_stock > 0 ? ($prod_unsold_cost / $cur_stock) : 0.0;
    $real_margin = $real_rev > 0 ? ($real_rev - $prod_sold_cost) : 0;
    $grand_total_produksi_belum_terjual += $prod_unsold_cost;
    $grand_total_biaya_produksi_terjual += $prod_sold_cost;
    if ($prod_total_output > 0 || $p_sold > 0) {
      $fifo_product_summary[$pid] = [
        'product_name'   => $p['product_name'],
        'unit_symbol'    => $p['symbol'],
        'total_output'   => $prod_total_output,
        'total_sold'     => $p_sold,
        'remaining_stock'=> $cur_stock,
        'total_shrinkage'=> (float)($shrinkage_by_product[$pid] ?? 0.0),
        'hpp_sisa'       => $hpp_sisa,
        'total_cost'     => $prod_total_cost,
        'sold_cost'      => $prod_sold_cost,
        'unsold_cost'    => $prod_unsold_cost,
        'real_revenue'   => $real_rev,
        'real_margin'    => $real_margin,
        'batches'        => $p_batches
      ];
    }
  }
}
$margin_bersih = $total_sales - $grand_total_biaya_produksi_terjual;
$margin_pct    = $total_sales > 0 ? (($margin_bersih / $total_sales) * 100) : 0;

// Top produk (by revenue)
$top_prods = [];
$stmt_tp = $conn->prepare("
  SELECT ps.product_name, COALESCE(u.symbol,'g') AS symbol, SUM(s.qty) AS total_qty, SUM(s.total_selling) AS revenue
  FROM selling_products s
  JOIN product_stocks ps ON s.product_id = ps.id
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE $where_sql GROUP BY ps.id, ps.product_name, u.symbol ORDER BY revenue DESC LIMIT 8
");
if ($params) $stmt_tp->bind_param($types, ...$params);
$stmt_tp->execute();
$res_tp = $stmt_tp->get_result();
while ($r = $res_tp->fetch_assoc()) $top_prods[] = $r;
$stmt_tp->close();

// Top buyer
$top_buyers = [];
$stmt_tb = $conn->prepare("
  SELECT b.name, SUM(s.total_selling) AS total_spent, COUNT(DISTINCT s.invoice_number) AS invoices
  FROM selling_products s JOIN buyer_products b ON s.buyer_id = b.id
  WHERE $where_sql GROUP BY b.id ORDER BY total_spent DESC LIMIT 8
");
if ($params) $stmt_tb->bind_param($types, ...$params);
$stmt_tb->execute();
$res_tb = $stmt_tb->get_result();
while ($r = $res_tb->fetch_assoc()) $top_buyers[] = $r;
$stmt_tb->close();

// Riwayat transaksi (semua invoice, tanpa paginasi untuk PDF)
$transactions = [];
$stmt_tx = $conn->prepare("
  SELECT s.id, MAX(s.selling_date) AS selling_date, s.invoice_number, b.name AS buyer_name,
    GROUP_CONCAT(DISTINCT ps.product_name SEPARATOR ', ') AS product_name,
    COALESCE(u.symbol, 'g') AS symbol, s.price, MAX(s.dp) AS dp, s.status,
    SUM(s.qty) AS qty, SUM(s.total_selling) AS total_selling
  FROM selling_products s
  LEFT JOIN buyer_products b ON s.buyer_id = b.id
  JOIN product_stocks ps ON ps.id = s.product_id
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE $where_sql
  GROUP BY s.invoice_number, s.buyer_id, b.name, s.status
  ORDER BY selling_date DESC
  LIMIT 100
");
if ($params) $stmt_tx->bind_param($types, ...$params);
$stmt_tx->execute();
$res_tx = $stmt_tx->get_result();
while ($r = $res_tx->fetch_assoc()) $transactions[] = $r;
$stmt_tx->close();

// ============================================================
// FORMAT ANGKA KPI
// ============================================================
$f_total_sales        = fmtIDR($total_sales);
$f_sales_lunas        = fmtIDR($total_sales_lunas);
$f_belum_terjual      = fmtIDR($grand_total_produksi_belum_terjual);
$f_margin             = fmtIDR(abs($margin_bersih));
$f_margin_sign        = $margin_bersih >= 0 ? '+Rp ' : '-Rp ';
$f_margin_pct         = fmtNum($margin_pct, 1);
$margin_color         = $margin_bersih >= 0 ? '#16a34a' : '#dc2626';

// ============================================================
// INISIALISASI TCPDF A4 LANDSCAPE
// ============================================================
$pdf = new TCPDF("L", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("Fayyfir System");
$pdf->SetAuthor("Fayyfir - Abdul Hadi");
$pdf->SetTitle("Laporan Data Penjualan & Produksi");
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 8);

// ============================================================
// HTML KONTEN
// ============================================================
$html = <<<EOD
<table width="100%" cellpadding="1" cellspacing="0">
  <tr>
    <td width="65%">
      <span style="font-size: 14pt; font-weight: bold; color: #0f172a;">FAYYFIR</span><br>
      <span style="font-size: 8.5pt; color: #334155;">Sistem Manajemen Produksi &amp; Distribusi Kayu Gaharu</span><br>
      <span style="font-size: 7.5pt; color: #64748b;">Unit Usaha Pengolahan, Penyimpanan Gudang &amp; Ekspedisi Penjualan</span>
    </td>
    <td width="35%" align="right" style="font-size: 7.5pt; color: #334155;">
      <strong>NO. DOKUMEN:</strong> LPJ-DATA/{$printed_at}<br>
      <strong>TANGGAL CETAK:</strong> {$printed_at} WIB<br>
      <strong>PETUGAS:</strong> {$printed_by}<br>
      <strong>PERIODE:</strong> {$periode_label}
    </td>
  </tr>
</table>
<hr style="border: 0; border-top: 1.5px solid #0f172a; margin-top: 4px; margin-bottom: 6px;">

<table width="100%" cellpadding="4" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #cbd5e1;">
  <tr>
    <td width="80%">
      <span style="font-size: 11pt; font-weight: bold; color: #0f172a;">LAPORAN DATA PENJUALAN &amp; PRODUKSI (FIFO)</span><br>
      <span style="font-size: 8pt; color: #475569;">Periode: <strong>{$periode_label}</strong></span>
    </td>
    <td width="20%" align="right" style="vertical-align: middle;">
      <span style="font-size: 10pt; font-weight: bold; color: #1d4ed8;">LAPORAN</span><br>
      <span style="font-size: 8pt; font-weight: bold; color: #475569;">DATA PENJUALAN</span>
    </td>
  </tr>
</table>

<br>
<div style="font-size: 9pt; font-weight: bold;">I. RINGKASAN EKSEKUTIF</div>
<table width="100%" cellpadding="4" cellspacing="3" style="font-size: 8pt;">
  <tr>
    <td width="25%" style="border: 1px solid #bfdbfe; background-color: #eff6ff;">
      <span style="font-size: 7.5pt; color: #1e40af;"><strong>TOTAL OMZET PENJUALAN</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #1d4ed8;">Rp {$f_total_sales}</span><br>
      <span style="font-size: 7.5pt; color: #475569;">{$total_invoices} invoice &bull; Lunas: Rp {$f_sales_lunas}</span>
    </td>
    <td width="25%" style="border: 1px solid #fde68a; background-color: #fffbeb;">
      <span style="font-size: 7.5pt; color: #92400e;"><strong>NILAI STOK BELUM TERJUAL</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #b45309;">Rp {$f_belum_terjual}</span><br>
      <span style="font-size: 7.5pt; color: #475569;">Biaya produksi tersisa dalam stok (FIFO)</span>
    </td>
    <td width="25%" style="border: 1px solid #bbf7d0; background-color: #f0fdf4;">
      <span style="font-size: 7.5pt; color: #14532d;"><strong>MARGIN BERSIH (DARI TERJUAL)</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: {$margin_color};">{$f_margin_sign}{$f_margin}</span><br>
      <span style="font-size: 7.5pt; color: #475569;">{$f_margin_pct}% dari omzet</span>
    </td>
    <td width="25%" style="border: 1px solid #e9d5ff; background-color: #faf5ff;">
      <span style="font-size: 7.5pt; color: #6b21a8;"><strong>TOTAL BIAYA PRODUKSI TERJUAL</strong></span><br>
      <span style="font-size: 11pt; font-weight: bold; color: #7c3aed;">Rp
EOD;
$html .= fmtIDR($grand_total_biaya_produksi_terjual);
$html .= <<<EOD
</span><br>
      <span style="font-size: 7.5pt; color: #475569;">Berdasarkan alokasi FIFO antar batch</span>
    </td>
  </tr>
</table>

EOD;

// ============================================================
// II. RINGKASAN STOK & FIFO PER PRODUK
// ============================================================
$html .= <<<EOD
<br>
<div style="font-size: 9pt; font-weight: bold;">II. RINGKASAN STOK &amp; NILAI BIAYA PRODUKSI PER PRODUK (METODE FIFO)</div>
<table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
  <tr style="background-color: #1e293b; color: #ffffff; font-weight: bold;">
    <th width="4%" align="center">No</th>
    <th width="22%">Produk</th>
    <th width="10%" align="right">Total Produksi</th>
    <th width="10%" align="right">Terjual</th>
    <th width="10%" align="right">Sisa Stok</th>
    <th width="11%" align="right">Biaya Belum Terjual</th>
    <th width="11%" align="right">Omzet Riil</th>
    <th width="11%" align="right">Margin Bersih</th>
    <th width="6%" align="right">HPP Sisa/g</th>
    <th width="5%" align="center">Susut</th>
  </tr>
EOD;

if (!empty($fifo_product_summary)) {
  $rnk = 1;
  foreach ($fifo_product_summary as $pid => $p) {
    $sym         = htmlspecialchars($p['unit_symbol']);
    $bg          = ($rnk % 2 === 0) ? '#f8fafc' : '#ffffff';
    $m_val       = $p['real_margin'];
    $m_color     = $m_val >= 0 ? '#16a34a' : '#dc2626';
    $m_sign      = $m_val >= 0 ? '+Rp ' : '-Rp ';
    $unsold_color= $p['unsold_cost'] > 0 ? '#1d4ed8' : '#64748b';

    $html .= <<<EOD
  <tr style="background-color: {$bg};">
    <td align="center">{$rnk}</td>
    <td><strong>{$p['product_name']}</strong></td>
    <td align="right">{$p['total_output']} {$sym}</td>
    <td align="right">{$p['total_sold']} {$sym}</td>
    <td align="right"><strong>{$p['remaining_stock']} {$sym}</strong></td>
    <td align="right" style="color: {$unsold_color};"><strong>Rp
EOD;
    $html .= fmtIDR($p['unsold_cost']);
    $html .= '</strong></td>';
    $html .= '<td align="right">';
    $html .= $p['real_revenue'] > 0 ? 'Rp ' . fmtIDR($p['real_revenue']) : '-';
    $html .= '</td>';
    $html .= '<td align="right" style="color: ' . $m_color . ';">';
    if ($p['total_sold'] > 0 && $p['real_revenue'] > 0) {
      $html .= '<strong>' . $m_sign . fmtIDR(abs($m_val)) . '</strong>';
    } else {
      $html .= '-';
    }
    $html .= '</td>';
    $html .= '<td align="right">';
    $html .= $p['hpp_sisa'] > 0 ? 'Rp ' . fmtNum($p['hpp_sisa'], 2) : '-';
    $html .= '</td>';
    $html .= '<td align="center">';
    $html .= $p['total_shrinkage'] > 0 ? fmtNum($p['total_shrinkage'], 0) . ' ' . $sym : '-';
    $html .= '</td></tr>';
    $rnk++;
  }
} else {
  $html .= '<tr><td colspan="10" align="center" style="color:#64748b;">Tidak ada data produk.</td></tr>';
}

// Footer row
$html .= <<<EOD
  <tr style="background-color: #1e293b; color: #ffffff; font-weight: bold;">
    <td colspan="2" align="center">TOTAL KESELURUHAN</td>
    <td align="right">-</td>
    <td align="right">-</td>
    <td align="right">-</td>
    <td align="right">Rp
EOD;
$html .= fmtIDR($grand_total_produksi_belum_terjual);
$html .= '</td><td align="right">Rp ' . fmtIDR($total_sales);
$html .= '</td><td align="right">' . ($margin_bersih >= 0 ? '+Rp ' : '-Rp ') . fmtIDR(abs($margin_bersih));
$html .= '</td><td colspan="2" align="center">-</td></tr></table>';

// ============================================================
// III. TOP PRODUK & TOP BUYER (SIDE BY SIDE)
// ============================================================
$html .= <<<EOD
<br>
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="49%" style="vertical-align: top;">
      <div style="font-size: 9pt; font-weight: bold;">III.A TOP PRODUK (BERDASARKAN OMZET)</div>
      <table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
        <tr style="background-color: #1d4ed8; color: #ffffff; font-weight: bold;">
          <th width="8%" align="center">No</th>
          <th width="40%">Nama Produk</th>
          <th width="22%" align="right">Qty Terjual</th>
          <th width="30%" align="right">Omzet</th>
        </tr>
EOD;

if (!empty($top_prods)) {
  foreach ($top_prods as $i => $tp) {
    $bg = ($i % 2 === 0) ? '#eff6ff' : '#ffffff';
    $sym = htmlspecialchars($tp['symbol']);
    $html .= "<tr style=\"background-color: {$bg};\">";
    $html .= "<td align=\"center\">" . ($i+1) . "</td>";
    $html .= "<td><strong>" . htmlspecialchars($tp['product_name']) . "</strong></td>";
    $html .= "<td align=\"right\">" . fmtNum($tp['total_qty'], 0) . " {$sym}</td>";
    $html .= "<td align=\"right\"><strong>Rp " . fmtIDR($tp['revenue']) . "</strong></td>";
    $html .= "</tr>";
  }
} else {
  $html .= '<tr><td colspan="4" align="center">Tidak ada data</td></tr>';
}
$html .= '</table></td>';
$html .= '<td width="2%"></td>';

// Top buyers
$html .= <<<EOD
    <td width="49%" style="vertical-align: top;">
      <div style="font-size: 9pt; font-weight: bold;">III.B TOP BUYER (BERDASARKAN TOTAL BELANJA)</div>
      <table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
        <tr style="background-color: #065f46; color: #ffffff; font-weight: bold;">
          <th width="8%" align="center">No</th>
          <th width="40%">Nama Buyer</th>
          <th width="16%" align="center">Invoice</th>
          <th width="36%" align="right">Total Belanja</th>
        </tr>
EOD;

if (!empty($top_buyers)) {
  foreach ($top_buyers as $i => $tb) {
    $bg = ($i % 2 === 0) ? '#f0fdf4' : '#ffffff';
    $html .= "<tr style=\"background-color: {$bg};\">";
    $html .= "<td align=\"center\">" . ($i+1) . "</td>";
    $html .= "<td><strong>" . htmlspecialchars($tb['name']) . "</strong></td>";
    $html .= "<td align=\"center\">" . (int)$tb['invoices'] . " invc</td>";
    $html .= "<td align=\"right\"><strong>Rp " . fmtIDR($tb['total_spent']) . "</strong></td>";
    $html .= "</tr>";
  }
} else {
  $html .= '<tr><td colspan="4" align="center">Tidak ada data</td></tr>';
}
$html .= '</table></td></tr></table>';

// ============================================================
// IV. RINCIAN TRANSAKSI PENJUALAN
// ============================================================
$count_tx = count($transactions);
$html .= <<<EOD
<br>
<div style="font-size: 9pt; font-weight: bold;">IV. RINCIAN TRANSAKSI PENJUALAN (MAKS. 100 INVOICE TERBARU)</div>
<table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 7pt;">
  <tr style="background-color: #1e293b; color: #ffffff; font-weight: bold;">
    <th width="4%" align="center">No</th>
    <th width="14%" align="center">Tanggal</th>
    <th width="16%">No. Invoice</th>
    <th width="18%">Buyer</th>
    <th width="16%">Produk</th>
    <th width="10%" align="right">Qty</th>
    <th width="12%" align="right">Total</th>
    <th width="6%" align="right">DP</th>
    <th width="4%" align="center">Status</th>
  </tr>
EOD;

if (!empty($transactions)) {
  foreach ($transactions as $i => $tr) {
    $bg     = ($i % 2 === 0) ? '#f8fafc' : '#ffffff';
    $st_c   = $tr['status'] === 'Lunas' ? '#16a34a' : '#b45309';
    $tgl    = date('d/m/Y H:i', strtotime($tr['selling_date']));
    $inv    = htmlspecialchars($tr['invoice_number'] ?? '-');
    $buyer  = htmlspecialchars(mb_strimwidth($tr['buyer_name'] ?? '-', 0, 24, '…'));
    $prod   = htmlspecialchars(mb_strimwidth($tr['product_name'] ?? '-', 0, 24, '…'));
    $qty_g  = fmtNum($tr['qty'], 0) . ' g';
    $qty_kg = fmtNum($tr['qty'] / 1000, 3) . ' kg';
    $dp_fmt = (float)$tr['dp'] > 0 ? 'Rp ' . fmtIDR($tr['dp']) : '-';
    $html .= "<tr style=\"background-color: {$bg};\">";
    $html .= "<td align=\"center\">" . ($i+1) . "</td>";
    $html .= "<td align=\"center\">{$tgl}</td>";
    $html .= "<td><strong>{$inv}</strong></td>";
    $html .= "<td>{$buyer}</td>";
    $html .= "<td>{$prod}</td>";
    $html .= "<td align=\"right\">{$qty_kg}<br><span style=\"color:#64748b;\">{$qty_g}</span></td>";
    $html .= "<td align=\"right\"><strong>Rp " . fmtIDR($tr['total_selling']) . "</strong></td>";
    $html .= "<td align=\"right\">{$dp_fmt}</td>";
    $html .= "<td align=\"center\" style=\"color:{$st_c}; font-weight:bold;\">" . htmlspecialchars($tr['status']) . "</td>";
    $html .= "</tr>";
  }
} else {
  $html .= '<tr><td colspan="9" align="center" style="color:#64748b;">Tidak ada data transaksi pada periode ini.</td></tr>';
}
$html .= <<<EOD
  <tr style="background-color: #1e293b; color: #ffffff; font-weight: bold;">
    <td colspan="6" align="center">TOTAL {$count_tx} INVOICE</td>
    <td align="right">Rp {$f_total_sales}</td>
    <td colspan="2" align="center">-</td>
  </tr>
</table>
EOD;

// ============================================================
// FOOTER DOKUMEN
// ============================================================
$html .= <<<EOD
<br><br>
<hr style="border:0; border-top:1px solid #cbd5e1;">
<table width="100%" style="font-size:7pt; color:#64748b;">
  <tr>
    <td>Fayyfir Management System &bull; Dokumen Rahasia &amp; Resmi Perusahaan</td>
    <td align="right">Laporan Data Penjualan &amp; Produksi &bull; {$periode_label}</td>
  </tr>
</table>
EOD;

// ============================================================
// OUTPUT PDF
// ============================================================
$pdf->writeHTML($html, true, false, true, false, "");

// Nama file
$safe_from = $from ? str_replace('-', '', $from) : 'ALL';
$safe_to   = $to   ? str_replace('-', '', $to)   : 'ALL';
$pdf->Output("Laporan_Data_Penjualan_{$safe_from}_{$safe_to}.pdf", "I");
