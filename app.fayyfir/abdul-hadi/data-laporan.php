<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require_once "config.php";

// --- Helpers ---  
if (!function_exists('fmtIDR')) {
  function fmtIDR($n)
  {
    return number_format($n, 0, ',', '.');
  }
}
if (!function_exists('fmtNum')) {
  function fmtNum($n, $dec = 2)
  {
    return number_format($n, $dec, ',', '.');
  }
}

// --- Filters (GET) ---  
$from = isset($_GET['from']) && $_GET['from'] !== '' ? date('Y-m-d', strtotime($_GET['from'])) : null; // yyyy-mm-dd  
$to   = isset($_GET['to']) && $_GET['to'] !== '' ? date('Y-m-d', strtotime($_GET['to'])) : null; // yyyy-mm-dd  
$buyer_id = isset($_GET['buyer']) && $_GET['buyer'] !== '' ? (int)$_GET['buyer'] : null;
$product_id = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

// --- Periode Preset Helper ---
$current_month_start = date('Y-m-01');
$current_month_end   = date('Y-m-t');
$last_month_start    = date('Y-m-01', strtotime('first day of last month'));
$last_month_end      = date('Y-m-t', strtotime('last day of last month'));
$current_year_start  = date('Y-01-01');
$current_year_end    = date('Y-12-31');

$active_preset = 'all';
if ($from === $current_month_start && $to === $current_month_end) {
  $active_preset = 'this_month';
  $periode_label = "Bulan Ini (" . date('d/m/Y', strtotime($from)) . " - " . date('d/m/Y', strtotime($to)) . ")";
} elseif ($from === $last_month_start && $to === $last_month_end) {
  $active_preset = 'last_month';
  $periode_label = "Bulan Lalu (" . date('d/m/Y', strtotime($from)) . " - " . date('d/m/Y', strtotime($to)) . ")";
} elseif ($from === $current_year_start && $to === $current_year_end) {
  $active_preset = 'this_year';
  $periode_label = "Tahun Ini (" . date('Y', strtotime($from)) . ")";
} elseif ($from || $to) {
  $active_preset = 'custom';
  $f_str = $from ? date('d/m/Y', strtotime($from)) : 'Awal';
  $t_str = $to ? date('d/m/Y', strtotime($to)) : 'Sekarang';
  $periode_label = "Rentang: $f_str s/d $t_str";
} else {
  $active_preset = 'all';
  $periode_label = "Semua Waktu (All Time)";
}

// build WHERE parts untuk Penjualan (selling_products)
$where = ['1=1'];
$params = [];
$types = '';

if ($from) {
  $where[] = "s.selling_date >= ?";
  $params[] = $from . " 00:00:00";
  $types .= 's';
}
if ($to) {
  $where[] = "s.selling_date <= ?";
  $params[] = $to . " 23:59:59";
  $types .= 's';
}
if ($buyer_id) {
  $where[] = "s.buyer_id = ?";
  $params[] = $buyer_id;
  $types .= 'i';
}
if ($product_id) {
  $where[] = "s.product_id = ?";
  $params[] = $product_id;
  $types .= 'i';
}
if ($status_filter) {
  $where[] = "s.status = ?";
  $params[] = $status_filter;
  $types .= 's';
}
$where_sql = implode(" AND ", $where);

// --- KPI / summary queries ---  
// 1) Total pembelian material (sinkron dengan filter tanggal)
$mp_where = ['1=1'];
$mp_params = [];
$mp_types = '';
if ($from) {
  $mp_where[] = "purchase_date >= ?";
  $mp_params[] = $from;
  $mp_types .= 's';
}
if ($to) {
  $mp_where[] = "purchase_date <= ?";
  $mp_params[] = $to;
  $mp_types .= 's';
}
$sql_purchases = "SELECT COALESCE(SUM(total_price),0) as total FROM material_purchases WHERE " . implode(" AND ", $mp_where);
$stmt_mp = $conn->prepare($sql_purchases);
if ($mp_params) $stmt_mp->bind_param($mp_types, ...$mp_params);
$stmt_mp->execute();
$total_purchases = $stmt_mp->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_mp->close();

// 2) Total produksi & Biaya Produksi (sinkron dengan filter tanggal & produk)
$prod_where = ['1=1'];
$prod_params = [];
$prod_types = '';
if ($from) {
  $prod_where[] = "production_date >= ?";
  $prod_params[] = $from;
  $prod_types .= 's';
}
if ($to) {
  $prod_where[] = "production_date <= ?";
  $prod_params[] = $to;
  $prod_types .= 's';
}
if ($product_id) {
  $prod_where[] = "product_id = ?";
  $prod_params[] = $product_id;
  $prod_types .= 'i';
}
$prod_where_sql = implode(" AND ", $prod_where);

// Count produksi
$stmt_pcount = $conn->prepare("SELECT COUNT(*) as total FROM productions WHERE $prod_where_sql");
if ($prod_params) $stmt_pcount->bind_param($prod_types, ...$prod_params);
$stmt_pcount->execute();
$total_productions = $stmt_pcount->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pcount->close();

// Grand total seluruh biaya produksi
$stmt_ptotal = $conn->prepare("SELECT COALESCE(SUM(total_pro_expenses),0) AS total_expenses, COALESCE(SUM(total_pro_materials),0) AS total_materials FROM productions WHERE $prod_where_sql");
if ($prod_params) $stmt_ptotal->bind_param($prod_types, ...$prod_params);
$stmt_ptotal->execute();
$prod_total_row = $stmt_ptotal->get_result()->fetch_assoc();
$total_pengeluaran = $prod_total_row['total_expenses'] ?? 0;
$total_bahan = $prod_total_row['total_materials'] ?? 0;
$grand_total_produksi = $total_pengeluaran + $total_bahan;
$stmt_ptotal->close();

// =========================================================================
// Perhitungan FIFO (First-In, First-Out) Biaya Produksi Sisa / Belum Terjual
// Memisahkan HPP per batch dan memperhitungkan penjualan dari batch tertua lebih dulu
// =========================================================================

// 1. Ambil semua data produk master
$prod_master_conds = ["1=1"];
if ($product_id) {
  $prod_master_conds[] = "ps.id = " . (int)$product_id;
}
$sql_prod_master = "
  SELECT ps.id, ps.product_name, ps.quantity AS current_stock, COALESCE(u.symbol, 'g') AS symbol
  FROM product_stocks ps
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE " . implode(" AND ", $prod_master_conds) . "
  ORDER BY ps.product_name ASC
";
$res_prod_master = $conn->query($sql_prod_master);

// Pastikan tabel product_shrinkage_logs sudah ada (auto-migration safeguard untuk server)
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

// Ambil total susut per produk dari product_shrinkage_logs
$shrinkage_by_product = [];
$res_sst = $conn->query("SELECT product_id, COALESCE(SUM(qty_shrinkage), 0) AS total_shrinkage FROM product_shrinkage_logs GROUP BY product_id");
if ($res_sst) {
  while ($row_sst = $res_sst->fetch_assoc()) {
    $shrinkage_by_product[(int)$row_sst['product_id']] = (float)$row_sst['total_shrinkage'];
  }
}

// 2. Ambil semua batch produksi yang sudah selesai / terhitung (diurutkan kronologis tanggal produksi ASC, id ASC)
$batch_conds = ["p.status != 'Proses'", "COALESCE(p.total_output, 0) > 0"];
$batch_params = [];
$batch_types = '';

if ($product_id) {
  $batch_conds[] = "p.product_id = ?";
  $batch_params[] = (int)$product_id;
  $batch_types .= 'i';
}
if ($to) {
  $batch_conds[] = "COALESCE(p.production_date, DATE(p.created_at)) <= ?";
  $batch_params[] = $to;
  $batch_types .= 's';
}

$sql_batches = "
  SELECT 
    p.id AS batch_id,
    p.product_id,
    p.production_number,
    COALESCE(p.production_date, DATE(p.created_at)) AS prod_date,
    COALESCE(p.total_output, 0) AS total_output,
    (COALESCE(p.total_pro_materials, 0) + COALESCE(p.total_pro_expenses, 0)) AS total_cost,
    COALESCE(p.price_weight, 0) AS price_weight,
    COALESCE(p.fix_price, 0) AS fix_price
  FROM productions p
  WHERE " . implode(" AND ", $batch_conds) . "
  ORDER BY COALESCE(p.production_date, DATE(p.created_at)) ASC, p.id ASC
";

$stmt_b = $conn->prepare($sql_batches);
if ($batch_params) $stmt_b->bind_param($batch_types, ...$batch_params);
$stmt_b->execute();
$res_b = $stmt_b->get_result();

$batches_by_product = [];
while ($b = $res_b->fetch_assoc()) {
  $pid = (int)$b['product_id'];
  $b_out = (float)$b['total_output'];
  $b_cost = (float)$b['total_cost'];
  $b_ucost = $b_out > 0 ? ($b_cost / $b_out) : 0;
  $b_pw = (float)$b['price_weight'];
  $b_fp = (float)$b['fix_price'];

  // Target harga jual per unit (gram) dari Riwayat Produksi
  if ($b_fp > 0 && $b_out > 0) {
    $b_target_price_unit = $b_fp / $b_out;
  } elseif ($b_pw > 0) {
    $b_target_price_unit = $b_pw / 1000;
  } else {
    $b_target_price_unit = 0;
  }

  $batches_by_product[$pid][] = [
    'batch_id'            => (int)$b['batch_id'],
    'production_number'   => $b['production_number'],
    'prod_date'           => $b['prod_date'],
    'output'              => $b_out,
    'total_cost'          => $b_cost,
    'unit_cost'           => $b_ucost,
    'price_weight'        => $b_pw,
    'fix_price'           => $b_fp,
    'target_price_unit'   => $b_target_price_unit,
    'sold_qty'            => 0.0,
    'sold_cost'           => 0.0,
    'sold_revenue_target' => 0.0,
    'sold_margin_target'  => 0.0,
    'real_revenue_sold'   => 0.0,
    'real_margin_sold'    => 0.0,
    'invoices'            => [],
    'remaining_qty'       => 0.0,
    'remaining_cost'      => 0.0,
    'status_fifo'         => ''
  ];
}
$stmt_b->close();

// 3. Ambil daftar penjualan per produk (kuantitas, omzet riil, dan rincian transaksi)
$sold_conds = ["1=1"];
$sold_params = [];
$sold_types = '';

if ($product_id) {
  $sold_conds[] = "product_id = ?";
  $sold_params[] = (int)$product_id;
  $sold_types .= 'i';
}
if ($to) {
  $sold_conds[] = "selling_date <= ?";
  $sold_params[] = $to . " 23:59:59";
  $sold_types .= 's';
}

$sql_sold = "
  SELECT 
    id, product_id, invoice_number, selling_date, qty, price, total_selling
  FROM selling_products
  WHERE " . implode(" AND ", $sold_conds) . "
  ORDER BY selling_date ASC, id ASC
";

$stmt_s = $conn->prepare($sql_sold);
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
    'id'             => (int)$s['id'],
    'invoice_number' => $s['invoice_number'],
    'selling_date'   => $s['selling_date'],
    'qty'            => $sqty,
    'rem_qty'        => $sqty,
    'price'          => (float)$s['price'],
    'total_selling'  => $stot,
    'unit_price'     => $sqty > 0 ? ($stot / $sqty) : 0.0
  ];
}
$stmt_s->close();

// Ambil kuantitas penjualan sebelum periode $from untuk memisahkan BPP periode aktif vs BPP historis
$sold_before_by_product = [];
if ($from) {
  $sb_conds = ["selling_date < ?"];
  $sb_params = [$from . " 00:00:00"];
  $sb_types = 's';
  if ($product_id) {
    $sb_conds[] = "product_id = ?";
    $sb_params[] = (int)$product_id;
    $sb_types .= 'i';
  }
  $stmt_sb = $conn->prepare("SELECT product_id, COALESCE(SUM(qty), 0) AS total_sold FROM selling_products WHERE " . implode(" AND ", $sb_conds) . " GROUP BY product_id");
  if ($sb_params) $stmt_sb->bind_param($sb_types, ...$sb_params);
  $stmt_sb->execute();
  $res_sb = $stmt_sb->get_result();
  while ($sb = $res_sb->fetch_assoc()) {
    $sold_before_by_product[(int)$sb['product_id']] = (float)$sb['total_sold'];
  }
  $stmt_sb->close();
}

// 4. Kalkulasi antrean FIFO (First-In, First-Out)
$grand_total_produksi_belum_terjual = 0.0;
$grand_total_biaya_produksi_terjual = 0.0;
$grand_total_biaya_produksi_terjual_periode = 0.0;
$count_produk_belum_terjual = 0;
$total_batch_belum_terjual = 0;
$fifo_product_summary = [];

if ($res_prod_master) {
  while ($p = $res_prod_master->fetch_assoc()) {
    $pid = (int)$p['id'];
    $p_batches = $batches_by_product[$pid] ?? [];
    $p_sales = $sales_by_product[$pid] ?? [];
    $p_sold = (float)($total_sold_by_product[$pid] ?? 0.0);
    $rem_sold = $p_sold;

    $p_sold_before = (float)($sold_before_by_product[$pid] ?? 0.0);
    $rem_before = $p_sold_before;
    $rem_period = max(0.0, $p_sold - $p_sold_before);
    $prod_sold_cost_period = 0.0;

    $prod_total_output        = 0.0;
    $prod_total_cost          = 0.0;
    $prod_sold_cost           = 0.0;
    $prod_sold_qty            = 0.0;
    $prod_target_revenue_sold = 0.0;
    $prod_unsold_cost         = 0.0;
    $prod_unsold_qty          = 0.0;

    $sale_idx = 0;
    $num_sales = count($p_sales);

    foreach ($p_batches as &$batch) {
      $b_out  = $batch['output'];
      $b_cost = $batch['total_cost'];
      $b_uc   = $batch['unit_cost'];
      $b_tpu  = $batch['target_price_unit'];
      $b_rem  = $b_out;

      $prod_total_output += $b_out;
      $prod_total_cost   += $b_cost;

      // Alokasikan transaksi penjualan ke batch secara FIFO kronologis
      while ($b_rem > 0.0001 && $sale_idx < $num_sales) {
        $curr_sale = &$p_sales[$sale_idx];
        if ($curr_sale['rem_qty'] <= 0.0001) {
          $sale_idx++;
          continue;
        }

        $take = min($b_rem, $curr_sale['rem_qty']);
        $rev_take = $take * $curr_sale['unit_price'];

        $batch['sold_qty'] += $take;
        $batch['real_revenue_sold'] += $rev_take;
        $batch['invoices'][] = [
          'invoice'      => $curr_sale['invoice_number'],
          'selling_date' => $curr_sale['selling_date'],
          'qty'          => $take,
          'revenue'      => $rev_take
        ];

        $curr_sale['rem_qty'] -= $take;
        $b_rem -= $take;

        if ($curr_sale['rem_qty'] <= 0.0001) {
          $sale_idx++;
        }
      }

      $batch['remaining_qty']  = max(0.0, $b_rem);
      $batch['sold_cost']       = $batch['sold_qty'] * $b_uc;
      $batch['remaining_cost']  = max(0.0, $batch['remaining_qty'] * $b_uc);

      // Target omzet produksi (referensi kalkulasi awal)
      $batch['sold_revenue_target'] = $batch['sold_qty'] * $b_tpu;
      $batch['sold_margin_target']  = $b_tpu > 0 ? ($batch['sold_revenue_target'] - $batch['sold_cost']) : 0;

      // Real margin dari transaksi penjualan riil
      $batch['real_margin_sold'] = $batch['real_revenue_sold'] - $batch['sold_cost'];

      if ($batch['remaining_qty'] <= 0.0001) {
        $batch['status_fifo'] = 'Habis Terjual';
      } elseif ($batch['sold_qty'] > 0.0001) {
        $batch['status_fifo'] = 'Sisa Sebagian';
        $total_batch_belum_terjual++;
      } else {
        $batch['status_fifo'] = 'Belum Terjual';
        $total_batch_belum_terjual++;
      }

      $prod_sold_qty            += $batch['sold_qty'];
      $prod_sold_cost           += $batch['sold_cost'];
      $prod_target_revenue_sold += $batch['sold_revenue_target'];
      $prod_unsold_qty          += $batch['remaining_qty'];
      $prod_unsold_cost         += $batch['remaining_cost'];

      // Alokasi BPP periode aktif ($from s/d $to)
      if ($from) {
        $used_before = min($rem_before, $b_out);
        $rem_before -= $used_before;
        $avail_for_period = $b_out - $used_before;
        if ($avail_for_period > 0 && $rem_period > 0) {
          $take_period = min($rem_period, $avail_for_period);
          $prod_sold_cost_period += ($take_period * $b_uc);
          $rem_period -= $take_period;
        }
      } else {
        $prod_sold_cost_period = $prod_sold_cost;
      }
    }
    unset($batch);

    if ($prod_unsold_qty > 0 || $prod_unsold_cost > 0) {
      $count_produk_belum_terjual++;
    }

    $grand_total_produksi_belum_terjual += $prod_unsold_cost;
    $grand_total_biaya_produksi_terjual += $prod_sold_cost;
    $grand_total_biaya_produksi_terjual_periode += ($from ? $prod_sold_cost_period : $prod_sold_cost);

    $real_rev = (float)($total_selling_by_product[$pid] ?? 0.0);
    $real_margin = $real_rev > 0 ? ($real_rev - $prod_sold_cost) : 0;
    $target_margin = $prod_target_revenue_sold > 0 ? ($prod_target_revenue_sold - $prod_sold_cost) : 0;

    // Ambil tanggal produksi terbaru untuk produk ini
    $latest_prod_date = '';
    if (!empty($p_batches)) {
      foreach ($p_batches as $pb) {
        if ($pb['prod_date'] > $latest_prod_date) {
          $latest_prod_date = $pb['prod_date'];
        }
      }
    }

    // Urutkan batch untuk tampilan dari tanggal terbaru ke terlama (DESC)
    usort($p_batches, function ($a, $b) {
      $t1 = strtotime($a['prod_date']);
      $t2 = strtotime($b['prod_date']);
      if ($t1 !== $t2) {
        return $t2 <=> $t1;
      }
      return $b['batch_id'] <=> $a['batch_id'];
    });

    $cur_stock = isset($p['current_stock']) ? (float)$p['current_stock'] : max(0.0, $prod_total_output - $p_sold);
    $tot_shrinkage = (float)($shrinkage_by_product[$pid] ?? 0.0);
    $hpp_sisa = $cur_stock > 0 ? ($prod_unsold_cost / $cur_stock) : 0.0;

    $fifo_product_summary[$pid] = [
      'product_id'          => $pid,
      'product_name'        => $p['product_name'],
      'unit_symbol'         => $p['symbol'],
      'total_output'        => $prod_total_output,
      'total_sold'          => $p_sold,
      'remaining_stock'     => $cur_stock,
      'total_shrinkage'     => $tot_shrinkage,
      'hpp_sisa'            => $hpp_sisa,
      'total_cost'          => $prod_total_cost,
      'sold_cost'           => $prod_sold_cost,
      'unsold_cost'         => $prod_unsold_cost,
      'real_revenue'        => $real_rev,
      'real_margin'         => $real_margin,
      'target_revenue_sold' => $prod_target_revenue_sold,
      'target_margin_sold'  => $target_margin,
      'latest_prod_date'    => $latest_prod_date,
      'batches'             => $p_batches
    ];
  }
}

// Urutkan ringkasan produk di tabel FIFO berdasarkan tanggal produksi terbaru (DESC), lalu nama produk ASC
uasort($fifo_product_summary, function ($a, $b) {
  $d1 = $a['latest_prod_date'] ?? '';
  $d2 = $b['latest_prod_date'] ?? '';
  if ($d1 !== $d2) {
    return strcmp($d2, $d1); // Tanggal terbaru di atas
  }
  return strcasecmp($a['product_name'], $b['product_name']);
});

// 3) Total penjualan (filtered)  
$sql_total_sales = "SELECT COALESCE(SUM(s.total_selling),0) AS total FROM selling_products s WHERE $where_sql";
$stmt = $conn->prepare($sql_total_sales);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_sales = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total penjualan lunas (hanya transaksi yang sudah lunas & ber-invoice)
$where_lunas = $where;
$where_lunas[] = "s.status = 'Lunas'";
$where_lunas[] = "s.invoice_number IS NOT NULL AND s.invoice_number != ''";
$where_lunas_sql = implode(" AND ", $where_lunas);

$sql_total_sales_lunas = "SELECT COALESCE(SUM(s.total_selling),0) AS total FROM selling_products s WHERE $where_lunas_sql";
$stmt_lunas = $conn->prepare($sql_total_sales_lunas);
if ($params) $stmt_lunas->bind_param($types, ...$params);
$stmt_lunas->execute();
$total_sales_lunas = (float)($stmt_lunas->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_lunas->close();

// 4) PPh estimate (0.25% dari total)  
$pph = $total_sales * 0.0025;

// 5) Gross margin simple: total_selling - total_production_expenses (approx)  
$expense_row = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM production_expenses")->fetch_assoc();
$total_production_expenses = $expense_row['total'] ?? 0;
$gross_profit = $total_sales - $total_production_expenses;

// 6) Stok tersisa per produk (calculated per product)
$prod_q = $conn->query("  
SELECT 
    ps.id,
    ps.product_name,
    COALESCE(p.total_output, 0) AS total_output,
    COALESCE(sp.total_sold, 0) AS total_sold,
    GREATEST(0, COALESCE(p.total_output, 0) - COALESCE(sp.total_sold, 0)) AS stok_tersisa,
    u.symbol
FROM product_stocks ps
LEFT JOIN (
    SELECT product_id, SUM(total_output) AS total_output 
    FROM productions 
    WHERE status != 'Proses' 
    GROUP BY product_id
) p ON p.product_id = ps.id
LEFT JOIN (
    SELECT product_id, SUM(qty) AS total_sold 
    FROM selling_products 
    GROUP BY product_id
) sp ON sp.product_id = ps.id
LEFT JOIN units u ON ps.unit_id = u.id
ORDER BY ps.product_name ASC;
");

// 8) Margin Bersih = Total Penjualan - Total Biaya Produksi Barang Terjual (FIFO) Periode Aktif
// Pengeluaran bulanan tidak ditampilkan di sini, hanya dikurangkan di Laporan Laba Rugi
$biaya_produksi_terjual_digunakan = $from ? $grand_total_biaya_produksi_terjual_periode : $grand_total_biaya_produksi_terjual;
$margin_bersih = (float)$total_sales - (float)$biaya_produksi_terjual_digunakan;
$net_cashflow = $margin_bersih; // alias kompatibilitas
$grand_profit_bersih = $margin_bersih; // alias kompatibilitas

// gather products for filter dropdown  
$products_for_filter = $conn->query("SELECT DISTINCT ps.id, ps.product_name
FROM product_stocks ps
ORDER BY ps.product_name ASC");

// gather buyers for filter dropdown  
$buyers_for_filter = $conn->query("SELECT id, name FROM buyer_products ORDER BY name");

// --- Sales monthly (for chart) ---  
$sql_monthly = "  
  SELECT DATE_FORMAT(selling_date, '%Y-%m') AS ym, SUM(total_selling) AS total  
  FROM selling_products s  
  WHERE $where_sql  
  GROUP BY ym  
  ORDER BY ym ASC  
";
$stmt = $conn->prepare($sql_monthly);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res_monthly = $stmt->get_result();
$months = [];
$month_values = [];
while ($r = $res_monthly->fetch_assoc()) {
  $months[] = $r['ym'];
  $month_values[] = (float)$r['total'];
}
$stmt->close();

// --- Top products (by revenue) ---  
$sql_top_products = "  
  SELECT ps.id, ps.product_name, u.symbol, SUM(s.qty) AS total_qty, SUM(s.total_selling) AS revenue
FROM selling_products s
JOIN product_stocks ps ON s.product_id = ps.id
LEFT JOIN units u ON ps.unit_id = u.id
WHERE $where_sql
GROUP BY ps.id, ps.product_name, u.symbol
ORDER BY revenue DESC
LIMIT 8
";
$stmt = $conn->prepare($sql_top_products);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res_top_products = $stmt->get_result();
$top_products = [];
while ($r = $res_top_products->fetch_assoc()) {
  $top_products[] = $r;
}
$stmt->close();

// --- Top buyers ---  
$sql_top_buyers = "  
  SELECT b.id, b.name, SUM(s.total_selling) AS total_spent, COUNT(DISTINCT s.invoice_number) AS invoices  
  FROM selling_products s  
  JOIN buyer_products b ON s.buyer_id = b.id  
  WHERE $where_sql  
  GROUP BY b.id  
  ORDER BY total_spent DESC  
  LIMIT 8  
";
$stmt = $conn->prepare($sql_top_buyers);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res_top_buyers = $stmt->get_result();
$top_buyers = [];
while ($r = $res_top_buyers->fetch_assoc()) $top_buyers[] = $r;
$stmt->close();

// --- Transaction history (paginated simple) ---  
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$sql_history = "  
  SELECT s.id, MAX(s.selling_date) AS selling_date, s.invoice_number, b.name as buyer_name, 
         GROUP_CONCAT(DISTINCT ps.product_name SEPARATOR ', ') AS product_name, 
         u.symbol, s.price, MAX(s.dp) AS dp, s.status,
         SUM(s.qty) AS qty,
         SUM(s.total_selling) AS total_selling
  FROM selling_products s
  LEFT JOIN buyer_products b ON s.buyer_id = b.id
  JOIN product_stocks ps ON ps.id = s.product_id
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE $where_sql
  GROUP BY s.invoice_number, s.buyer_id, b.name, s.status
  ORDER BY selling_date DESC
  LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql_history);
if ($params) {
  // bind dynamic params + two ints for limit/offset  
  $bind_types = $types . "ii";
  $bind_vals = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($bind_types, ...$bind_vals);
} else {
  $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$res_history = $stmt->get_result();
$transactions = $res_history->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// total count for pagination  
$sql_count = "SELECT COUNT(DISTINCT s.invoice_number) AS cnt FROM selling_products s WHERE $where_sql";
$stmt = $conn->prepare($sql_count);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();
$total_pages = max(1, ceil($total_count / $perPage));

// --- Log Alur Stok IN, OUT, & SUSUT (Produksi, Penjualan, & Penyusutan) dengan Perhitungan Stok Terakhir (Running Balance) ---
$base_p_conds = ['1=1'];
$base_s_conds = ['1=1'];
$base_sst_conds = ['1=1'];
$base_params = [];
$base_types = '';

// Params untuk productions
if ($product_id) {
  $base_p_conds[] = "p.product_id = ?";
  $base_params[] = $product_id;
  $base_types .= 'i';
}
if ($to) {
  $base_p_conds[] = "COALESCE(p.production_date, DATE(p.created_at)) <= ?";
  $base_params[] = $to;
  $base_types .= 's';
}

// Params untuk sales
if ($product_id) {
  $base_s_conds[] = "s.product_id = ?";
  $base_params[] = $product_id;
  $base_types .= 'i';
}
if ($to) {
  $base_s_conds[] = "s.selling_date <= ?";
  $base_params[] = $to . " 23:59:59";
  $base_types .= 's';
}

// Params untuk susut (product_shrinkage_logs)
if ($product_id) {
  $base_sst_conds[] = "sst.product_id = ?";
  $base_params[] = $product_id;
  $base_types .= 'i';
}
if ($to) {
  $base_sst_conds[] = "sst.adjustment_date <= ?";
  $base_params[] = $to . " 23:59:59";
  $base_types .= 's';
}

$sql_all_movements = "
(
  SELECT 
    'IN' AS jenis,
    p.id AS source_id,
    p.product_id,
    COALESCE(TIMESTAMP(p.production_date, TIME(COALESCE(p.created_at, '00:00:00'))), p.created_at) AS tanggal,
    p.created_at,
    p.production_number AS ref_no,
    ps.product_name,
    COALESCE(u.symbol, 'g') AS symbol,
    COALESCE(p.total_output, 0.00) AS qty_in,
    0.00 AS qty_out,
    0.00 AS qty_susut,
    (COALESCE(p.total_pro_materials, 0) + COALESCE(p.total_pro_expenses, 0)) AS nominal,
    'Hasil Produksi' AS pihak_keterangan,
    p.status AS status_transaksi,
    NULL AS buyer_id,
    COALESCE(p.production_date, DATE(p.created_at)) AS filter_date,
    0.00 AS hpp_info
  FROM productions p
  JOIN product_stocks ps ON p.product_id = ps.id
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE " . implode(" AND ", $base_p_conds) . "
)
UNION ALL
(
  SELECT 
    'OUT' AS jenis,
    s.id AS source_id,
    s.product_id,
    s.selling_date AS tanggal,
    s.created_at,
    s.invoice_number AS ref_no,
    ps.product_name,
    COALESCE(u.symbol, 'g') AS symbol,
    0.00 AS qty_in,
    s.qty AS qty_out,
    0.00 AS qty_susut,
    s.total_selling AS nominal,
    COALESCE(b.name, '-') AS pihak_keterangan,
    s.status AS status_transaksi,
    s.buyer_id,
    DATE(s.selling_date) AS filter_date,
    0.00 AS hpp_info
  FROM selling_products s
  JOIN product_stocks ps ON s.product_id = ps.id
  LEFT JOIN units u ON ps.unit_id = u.id
  LEFT JOIN buyer_products b ON s.buyer_id = b.id
  WHERE " . implode(" AND ", $base_s_conds) . "
)
UNION ALL
(
  SELECT 
    'SUSUT' AS jenis,
    sst.id AS source_id,
    sst.product_id,
    sst.adjustment_date AS tanggal,
    sst.created_at,
    sst.ref_no AS ref_no,
    ps.product_name,
    COALESCE(u.symbol, 'g') AS symbol,
    0.00 AS qty_in,
    0.00 AS qty_out,
    sst.qty_shrinkage AS qty_susut,
    sst.unsold_cost_at_time AS nominal,
    COALESCE(NULLIF(sst.notes, ''), 'Penyusutan Bobot') AS pihak_keterangan,
    'Penyesuaian' AS status_transaksi,
    NULL AS buyer_id,
    DATE(sst.adjustment_date) AS filter_date,
    sst.hpp_after AS hpp_info
  FROM product_shrinkage_logs sst
  JOIN product_stocks ps ON sst.product_id = ps.id
  LEFT JOIN units u ON ps.unit_id = u.id
  WHERE " . implode(" AND ", $base_sst_conds) . "
)
ORDER BY tanggal ASC, created_at ASC, source_id ASC
";

$stmt_mov = $conn->prepare($sql_all_movements);
if ($base_params) {
  $stmt_mov->bind_param($base_types, ...$base_params);
}
$stmt_mov->execute();
$res_mov = $stmt_mov->get_result();
$all_movements = $res_mov ? $res_mov->fetch_all(MYSQLI_ASSOC) : [];
$stmt_mov->close();

$stock_running = [];
$stock_logs = [];

foreach ($all_movements as $m) {
  $pid = (int)$m['product_id'];
  if (!isset($stock_running[$pid])) {
    $stock_running[$pid] = 0;
  }

  if ($m['jenis'] === 'IN') {
    // Produksi menambah stok jika bukan 'Proses' atau jika ada total_output
    if ($m['status_transaksi'] !== 'Proses' || (float)$m['qty_in'] > 0) {
      $stock_running[$pid] += (float)$m['qty_in'];
    }
  } elseif ($m['jenis'] === 'OUT') {
    // Penjualan mengurangi stok
    $stock_running[$pid] -= (float)$m['qty_out'];
  } elseif ($m['jenis'] === 'SUSUT') {
    // Penyusutan mengurangi stok
    $stock_running[$pid] -= (float)$m['qty_susut'];
  }

  $m['stok_terakhir'] = $stock_running[$pid];

  // Saring data berdasarkan filter tampilan aktif
  $include = true;
  if ($from && $m['filter_date'] < $from) {
    $include = false;
  }
  if ($buyer_id) {
    if ($m['jenis'] === 'IN' || $m['jenis'] === 'SUSUT' || (int)$m['buyer_id'] !== (int)$buyer_id) {
      $include = false;
    }
  }
  if ($status_filter) {
    if ($m['jenis'] === 'IN' || $m['jenis'] === 'SUSUT' || $m['status_transaksi'] !== $status_filter) {
      $include = false;
    }
  }

  if ($include) {
    $stock_logs[] = $m;
  }
}

// Urutkan stock_logs dari transaksi terbaru ke terlama (DESC)
usort($stock_logs, function ($a, $b) {
  $t1 = strtotime($a['tanggal']);
  $t2 = strtotime($b['tanggal']);
  if ($t1 !== $t2) {
    return $t2 <=> $t1;
  }
  $c1 = strtotime($a['created_at']);
  $c2 = strtotime($b['created_at']);
  if ($c1 !== $c2) {
    return $c2 <=> $c1;
  }
  return $b['source_id'] <=> $a['source_id'];
});

// Hitung rekap log
$total_log_in_qty = 0;
$total_log_out_qty = 0;
$total_log_susut_qty = 0;
$count_log_in = 0;
$count_log_out = 0;
$count_log_susut = 0;
foreach ($stock_logs as $lg) {
  if ($lg['jenis'] === 'IN') {
    $total_log_in_qty += (float)$lg['qty_in'];
    $count_log_in++;
  } elseif ($lg['jenis'] === 'OUT') {
    $total_log_out_qty += (float)$lg['qty_out'];
    $count_log_out++;
  } elseif ($lg['jenis'] === 'SUSUT') {
    $total_log_susut_qty += (float)$lg['qty_susut'];
    $count_log_susut++;
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Laporan - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    /* Mobile friendly table */
    @media (max-width: 640px) {
      table thead {
        display: none;
      }

      table tbody tr {
        display: block;
        margin-bottom: .75rem;
        border-radius: .5rem;
        background: #fff;
        padding: .5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
      }

      table tbody td {
        display: flex;
        justify-content: space-between;
        padding: .5rem 0;
        border-bottom: 1px dashed #eee;
      }

      table tbody td:last-child {
        border-bottom: none;
      }

      table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #555;
      }
    }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-4 fixed top-0 left-0 right-0 z-40">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="index" class="text-yellow-400 hover:underline flex items-center"><span class="material-symbols-outlined">chevron_left</span><span class="hidden md:inline ml-1">Kembali</span></a>
        <h1 class="text-lg font-semibold">Laporan</h1>
      </div>
      <div class="flex items-center gap-3">
        <button id="btnExportCSV" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm">Ekspor CSV</button>
        <button id="btnPrint" class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-2 rounded text-sm">Cetak</button>
      </div>
    </div>
  </header>

  <main class="pt-20 pb-16 max-w-7xl mx-auto px-4 space-y-6">
    <!-- Filter Section -->
    <section class="bg-white p-4 rounded-lg shadow">
      <!-- Preset Periode Cepat & Status -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode:</span>
          <div class="inline-flex rounded-md shadow-sm" role="group">
            <a href="data-laporan<?= ($buyer_id || $product_id || $status_filter) ? '?' . http_build_query(array_filter(['buyer' => $buyer_id, 'product' => $product_id, 'status' => $status_filter])) : '' ?>"
              class="px-3 py-1.5 text-xs font-medium rounded-l-lg border <?= $active_preset === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?>">
              Semua Waktu
            </a>
            <a href="data-laporan?<?= http_build_query(array_filter(['from' => $current_month_start, 'to' => $current_month_end, 'buyer' => $buyer_id, 'product' => $product_id, 'status' => $status_filter])) ?>"
              class="px-3 py-1.5 text-xs font-medium border-t border-b border-r <?= $active_preset === 'this_month' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?>">
              Bulan Ini
            </a>
            <a href="data-laporan?<?= http_build_query(array_filter(['from' => $last_month_start, 'to' => $last_month_end, 'buyer' => $buyer_id, 'product' => $product_id, 'status' => $status_filter])) ?>"
              class="px-3 py-1.5 text-xs font-medium border-t border-b border-r <?= $active_preset === 'last_month' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?>">
              Bulan Lalu
            </a>
            <a href="data-laporan?<?= http_build_query(array_filter(['from' => $current_year_start, 'to' => $current_year_end, 'buyer' => $buyer_id, 'product' => $product_id, 'status' => $status_filter])) ?>"
              class="px-3 py-1.5 text-xs font-medium rounded-r-lg border-t border-b border-r <?= $active_preset === 'this_year' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?>">
              Tahun Ini
            </a>
          </div>
        </div>
        <div class="text-xs text-gray-600 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg flex items-center gap-1 self-start md:self-auto">
          <span class="material-symbols-outlined text-sm text-blue-500">calendar_month</span>
          <span class="font-medium"><?= htmlspecialchars($periode_label) ?></span>
        </div>
      </div>

      <!-- Form Filter Rinci -->
      <form method="get" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
        <div>
          <label class="text-xs text-gray-600 font-medium">Dari Tanggal</label>
          <input type="date" name="from" value="<?= htmlentities($from ?? '') ?>" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
          <label class="text-xs text-gray-600 font-medium">Sampai Tanggal</label>
          <input type="date" name="to" value="<?= htmlentities($to ?? '') ?>" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
          <label class="text-xs text-gray-600 font-medium">Buyer</label>
          <select name="buyer" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500">
            <option value="">Semua Buyer</option>
            <?php while ($b = $buyers_for_filter->fetch_assoc()): ?>
              <option value="<?= $b['id'] ?>" <?= $buyer_id == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600 font-medium">Produk</label>
          <select name="product" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500">
            <option value="">Semua Produk</option>
            <?php while ($p = $products_for_filter->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>" <?= $product_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['product_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600 font-medium">Status</label>
          <select name="status" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500">
            <option value="">Semua Status</option>
            <option value="Belum Lunas" <?= $status_filter === 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
            <option value="DP" <?= $status_filter === 'DP' ? 'selected' : '' ?>>DP</option>
            <option value="Lunas" <?= $status_filter === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
          </select>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-1.5 rounded text-sm transition">Terapkan</button>
          <a href="data-laporan" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-3 py-1.5 rounded text-sm transition text-center">Reset</a>
        </div>
      </form>
    </section>

    <!-- KPI -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-sm text-gray-500 font-medium">Total Biaya Produksi (Belum Terjual)</div>
        <div class="mt-2 text-xl font-bold text-blue-600">Rp <?= fmtNum($grand_total_produksi_belum_terjual, 0) ?></div>
        <div class="text-xs text-gray-400 mt-1">
          <?= $count_produk_belum_terjual > 0 ? "$count_produk_belum_terjual produk belum habis / tanpa transaksi" : "Semua produk telah terjual" ?>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-sm text-gray-500 font-medium">Total Penjualan</div>
        <div class="mt-2 text-xl font-bold text-purple-600">Rp <?= fmtIDR($total_sales) ?></div>
        <div class="text-xs text-gray-400 mt-1"><?= $active_preset !== 'all' ? 'Omzet penjualan periode ini' : 'Total omzet penjualan' ?></div>
      </div>

      <div class="bg-white p-4 rounded-lg shadow border-l-4 <?= $margin_bersih >= 0 ? 'border-green-500' : 'border-red-500' ?>">
        <div class="flex items-center justify-between">
          <div class="text-sm font-medium text-gray-500">Margin</div>
          <?php
          $margin_pct = ($total_sales > 0) ? (($margin_bersih / $total_sales) * 100) : 0;
          ?>
          <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold <?= $margin_bersih >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $margin_bersih >= 0 ? 'SURPLUS' : 'DEFISIT' ?> (<?= fmtNum($margin_pct, 1) ?>%)
          </span>
        </div>
        <div class="mt-2 text-xl font-bold <?= $margin_bersih >= 0 ? 'text-green-600' : 'text-red-700' ?>">Rp <?= fmtIDR($margin_bersih) ?></div>
        <div class="text-xs text-gray-400 mt-1">
          Penjualan dikurangi biaya produksi terjual (Rp <?= fmtIDR($biaya_produksi_terjual_digunakan) ?>)
        </div>
      </div>
    </section>

    <!-- Charts + top lists -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white p-4 rounded-lg shadow">
        <div class="flex justify-between items-center mb-3">
          <h3 class="font-semibold">Penjualan Bulanan</h3>
          <div class="flex gap-2">
            <button id="downloadSalesChart" class="text-sm px-2 py-1 bg-gray-100 rounded">Unduh Grafik</button>
          </div>
        </div>
        <canvas id="salesChart" height="160"></canvas>
      </div>

      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-semibold mb-3">Top Produk (Pendapatan)</h3>
        <ul class="space-y-2">
          <?php foreach ($top_products as $tp): ?>
            <li class="flex items-center justify-between">
              <div>
                <div class="text-sm font-medium"><?= htmlspecialchars($tp['product_name']) ?></div>
                <div class="text-xs text-gray-500"><?= fmtNum($tp['total_qty'], 0) ?> <?= htmlspecialchars($tp['symbol']) ?></div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold">Rp <?= fmtIDR($tp['revenue']) ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>

    <!-- Top buyers -->
    <section class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-3">Top Buyer</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <?php foreach ($top_buyers as $tb): ?>
          <div class="p-3 border rounded">
            <div class="flex justify-between items-center">
              <div>
                <div class="text-sm text-gray-500">Buyer</div>
                <div class="font-semibold"><?= htmlspecialchars($tb['name']) ?></div>
              </div>
              <div>
                <div class="text-sm text-gray-500 mt-1">Total Belanja</div>
                <div class="font-bold">Rp <?= fmtIDR($tb['total_spent']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Stock summary with FIFO Batch Breakdown -->
    <section class="bg-white p-4 rounded-lg shadow space-y-3">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-gray-100">
        <div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">inventory_2</span>
            <h3 class="font-bold text-gray-800 text-base">Stok & Nilai Biaya Produksi (Metode FIFO)</h3>
          </div>
          <p class="text-xs text-gray-500 mt-0.5">Penilaian persediaan stok tersisa dan rincian alokasi biaya per batch yang belum terjual</p>
        </div>
        <div class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 self-start sm:self-auto shadow-sm">
          <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
          <span>Total Nilai Belum Terjual: <strong>Rp <?= fmtNum($grand_total_produksi_belum_terjual, 0) ?></strong></span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200">
          <thead class="bg-gray-800 text-white text-xs uppercase tracking-wider">
            <tr>
              <th class="p-2.5 border text-left">Produk</th>
              <th class="p-2.5 border text-right">Total Produksi</th>
              <th class="p-2.5 border text-right">Terjual</th>
              <th class="p-2.5 border text-right">Sisa Stok</th>
              <th class="p-2.5 border text-right bg-blue-700 text-blue-50">Biaya Belum Terjual</th>
              <th class="p-2.5 border text-right bg-purple-700 text-purple-50">Nilai Terjual (Omzet)</th>
              <th class="p-2.5 border text-right bg-green-700 text-green-50">Margin Bersih</th>
              <th class="p-2.5 border text-center">Rincian Batch</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-xs">
            <?php if (empty($fifo_product_summary)): ?>
              <tr>
                <td colspan="8" class="p-4 text-center text-gray-500">Tidak ada data produk.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($fifo_product_summary as $pid => $p):
                $tersisa = (float)$p['remaining_stock'];
                $has_batches = !empty($p['batches']);
                $low = $tersisa <= 0 ? 'bg-red-50/50' : ($tersisa < 5 ? 'bg-yellow-50/50' : 'hover:bg-gray-50');
              ?>
                <tr class="<?= $low ?> transition">
                  <td class="p-2.5 border font-medium text-gray-900" data-label="Produk">
                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($p['product_name']) ?></div>
                    <?php if (!empty($p['latest_prod_date'])): ?>
                      <div class="text-[11px] text-gray-400 font-normal">Prod. Terakhir: <?= date("d/m/Y", strtotime($p['latest_prod_date'])) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="p-2.5 border text-right" data-label="Total Produksi">
                    <?= fmtNum($p['total_output'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?>
                  </td>
                  <td class="p-2.5 border text-right" data-label="Terjual">
                    <?= fmtNum($p['total_sold'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?>
                  </td>
                  <td class="p-2.5 border text-right font-bold <?= $tersisa > 0 ? 'text-gray-900' : 'text-red-500' ?>" data-label="Sisa Stok">
                    <?= fmtNum($tersisa, 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?>
                    <?php if (!empty($p['total_shrinkage']) && $p['total_shrinkage'] > 0): ?>
                      <span class="text-[10px] text-amber-600 block font-normal">(Susut: <?= fmtNum($p['total_shrinkage'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2.5 border text-right font-bold text-blue-700 bg-blue-50/60" data-label="Biaya Belum Terjual">
                    Rp <?= fmtNum($p['unsold_cost'], 0) ?>
                    <?php if ($tersisa > 0 && !empty($p['hpp_sisa'])): ?>
                      <span class="text-[10px] text-blue-600 block font-semibold">HPP: Rp <?= fmtNum($p['hpp_sisa'], 0) ?>/<?= htmlspecialchars($p['unit_symbol']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2.5 border text-right font-bold text-purple-700 bg-purple-50/60" data-label="Nilai Terjual">
                    <?php if ($p['real_revenue'] > 0): ?>
                      Rp <?= fmtIDR($p['real_revenue']) ?>
                      <?php if ($p['target_revenue_sold'] > 0 && abs($p['real_revenue'] - $p['target_revenue_sold']) > 1): ?>
                        <span class="text-[10px] text-gray-400 block font-normal" title="Target di Riwayat Produksi">Target: Rp <?= fmtIDR($p['target_revenue_sold']) ?></span>
                      <?php endif; ?>
                    <?php elseif ($p['target_revenue_sold'] > 0): ?>
                      Rp <?= fmtIDR($p['target_revenue_sold']) ?>
                      <span class="text-[10px] text-gray-400 block font-normal">(Target Produksi)</span>
                    <?php elseif ($p['total_sold'] > 0): ?>
                      <span class="text-gray-400 text-xs">Rp 0</span>
                    <?php else: ?>
                      <span class="text-gray-400 text-xs">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2.5 border text-right bg-green-50/60" data-label="Margin Bersih">
                    <?php
                    $has_rev = ($p['real_revenue'] > 0 || $p['target_revenue_sold'] > 0);
                    $m_val   = ($p['real_revenue'] > 0) ? $p['real_margin'] : $p['target_margin_sold'];
                    $base_rev = ($p['real_revenue'] > 0) ? $p['real_revenue'] : $p['target_revenue_sold'];
                    $m_pct   = ($base_rev > 0) ? (($m_val / $base_rev) * 100) : 0;
                    ?>
                    <?php if ($p['total_sold'] > 0 && $has_rev): ?>
                      <span class="font-bold <?= $m_val >= 0 ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $m_val >= 0 ? '+Rp ' . fmtIDR($m_val) : '-Rp ' . fmtIDR(abs($m_val)) ?>
                      </span>
                      <span class="text-[10px] block font-medium <?= $m_val >= 0 ? 'text-green-600' : 'text-red-500' ?>">
                        (<?= fmtNum($m_pct, 1) ?>%)
                      </span>
                    <?php elseif ($p['total_sold'] > 0): ?>
                      <span class="text-gray-400 text-xs" title="Belum ada transaksi / target harga jual">Belum dihitung</span>
                    <?php else: ?>
                      <span class="text-gray-400 text-xs">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2.5 border text-center" data-label="Rincian Batch">
                    <?php if ($has_batches): ?>
                      <button type="button" onclick="toggleBatchDetail('batch-row-<?= $pid ?>')" class="px-2.5 py-1 text-[11px] font-medium bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 inline-flex items-center gap-1 transition">
                        <span><?= count($p['batches']) ?> Batch</span>
                        <span class="material-symbols-outlined text-xs" id="batch-icon-<?= $pid ?>">expand_more</span>
                      </button>
                    <?php else: ?>
                      <span class="text-gray-400 text-xs">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php if ($has_batches): ?>
                  <tr id="batch-row-<?= $pid ?>" class="hidden bg-gray-50/80">
                    <td colspan="8" class="p-3 border">
                      <div class="bg-white rounded border border-gray-200 p-3 shadow-inner">
                        <div class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-1.5">
                          <span class="material-symbols-outlined text-sm text-blue-600">layers</span>
                          <span>Alur FIFO Batch Produksi: <?= htmlspecialchars($p['product_name']) ?></span>
                        </div>
                        <div class="overflow-x-auto">
                          <table class="min-w-full text-[11px] border border-gray-200">
                            <thead class="bg-gray-100 text-gray-700">
                              <tr>
                                <th class="p-1.5 border text-center">No. Batch</th>
                                <th class="p-1.5 border text-center">Tgl Produksi</th>
                                <th class="p-1.5 border text-right">Hasil (Output)</th>
                                <th class="p-1.5 border text-right">Total Biaya</th>
                                <th class="p-1.5 border text-right">HPP / Unit</th>
                                <th class="p-1.5 border text-right text-green-700">Terjual (FIFO)</th>
                                <th class="p-1.5 border text-left">Invoice Terkait</th>
                                <th class="p-1.5 border text-right text-purple-700 bg-purple-50">Nilai Terjual</th>
                                <th class="p-1.5 border text-right text-green-700 bg-green-50">Margin Bersih</th>
                                <th class="p-1.5 border text-right font-bold text-blue-700">Sisa Stok</th>
                                <th class="p-1.5 border text-right font-bold text-blue-700">Nilai Belum Terjual</th>
                                <th class="p-1.5 border text-center">Status</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($p['batches'] as $b):
                                $is_exhausted = ($b['status_fifo'] === 'Habis Terjual');
                                $is_partial   = ($b['status_fifo'] === 'Sisa Sebagian');
                              ?>
                                <tr class="<?= $is_exhausted ? 'bg-gray-50 text-gray-500' : ($is_partial ? 'bg-yellow-50/60 font-medium' : 'bg-white font-medium') ?>">
                                  <td class="p-1.5 border text-center font-mono"><?= htmlspecialchars($b['production_number']) ?></td>
                                  <td class="p-1.5 border text-center"><?= date("d/m/Y", strtotime($b['prod_date'])) ?></td>
                                  <td class="p-1.5 border text-right"><?= fmtNum($b['output'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?></td>
                                  <td class="p-1.5 border text-right">Rp <?= fmtNum($b['total_cost'], 0) ?></td>
                                  <td class="p-1.5 border text-right text-gray-600">Rp <?= fmtNum($b['unit_cost'], 2) ?></td>
                                  <td class="p-1.5 border text-right text-green-700 font-semibold"><?= fmtNum($b['sold_qty'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?></td>
                                  <td class="p-1.5 border text-left">
                                    <?php if (!empty($b['invoices'])): ?>
                                      <div class="flex flex-wrap gap-1">
                                        <?php foreach ($b['invoices'] as $inv): ?>
                                          <a href="transaksi-invoice.php?invoice=<?= urlencode($inv['invoice']) ?>" target="_blank" class="inline-flex items-center gap-1 font-mono text-[10px] text-blue-600 hover:text-blue-800 hover:underline bg-blue-50/80 border border-blue-200 px-1.5 py-0.5 rounded shadow-xs" title="Terjual <?= fmtNum($inv['qty'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?> - Rp <?= fmtIDR($inv['revenue']) ?>">
                                            <span><?= htmlspecialchars($inv['invoice']) ?></span>
                                            <span class="text-gray-500 font-sans font-normal">(<?= fmtNum($inv['qty'], 0) ?><?= htmlspecialchars($p['unit_symbol']) ?>)</span>
                                          </a>
                                        <?php endforeach; ?>
                                      </div>
                                    <?php else: ?>
                                      <span class="text-gray-400 text-[10px]">-</span>
                                    <?php endif; ?>
                                  </td>
                                  <td class="p-1.5 border text-right bg-purple-50/40">
                                    <?php if ($b['sold_qty'] > 0): ?>
                                      <?php if ($b['real_revenue_sold'] > 0): ?>
                                        <span class="font-bold text-purple-700">Rp <?= fmtIDR($b['real_revenue_sold']) ?></span>
                                        <span class="text-[9px] text-gray-400 block font-normal">(Riil Penjualan)</span>
                                      <?php elseif ($b['target_price_unit'] > 0): ?>
                                        <span class="font-bold text-purple-700">Rp <?= fmtNum($b['sold_revenue_target'], 0) ?></span>
                                        <span class="text-[9px] text-gray-400 block font-normal">@ Rp <?= fmtNum($b['target_price_unit'] * 1000, 0) ?>/kg</span>
                                      <?php else: ?>
                                        <span class="text-[10px] text-amber-600 italic">Belum dihitung</span>
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                  </td>
                                  <td class="p-1.5 border text-right bg-green-50/40">
                                    <?php if ($b['sold_qty'] > 0 && $b['real_revenue_sold'] > 0): ?>
                                      <?php
                                      $bm_val = $b['real_margin_sold'];
                                      $bm_pct = ($b['real_revenue_sold'] > 0) ? (($bm_val / $b['real_revenue_sold']) * 100) : 0;
                                      ?>
                                      <span class="font-bold <?= $bm_val >= 0 ? 'text-green-700' : 'text-red-600' ?>">
                                        <?= $bm_val >= 0 ? '+Rp ' . fmtNum($bm_val, 0) : '-Rp ' . fmtNum(abs($bm_val), 0) ?>
                                      </span>
                                      <span class="text-[10px] block font-medium <?= $bm_val >= 0 ? 'text-green-600' : 'text-red-500' ?>">
                                        (<?= fmtNum($bm_pct, 1) ?>%)
                                      </span>
                                    <?php elseif ($b['sold_qty'] > 0 && $b['target_price_unit'] > 0): ?>
                                      <?php
                                      $bm_val = $b['sold_margin_target'];
                                      $bm_pct = $b['sold_revenue_target'] > 0 ? (($bm_val / $b['sold_revenue_target']) * 100) : 0;
                                      ?>
                                      <span class="font-bold <?= $bm_val >= 0 ? 'text-green-700' : 'text-red-600' ?>">
                                        <?= $bm_val >= 0 ? '+Rp ' . fmtNum($bm_val, 0) : '-Rp ' . fmtNum(abs($bm_val), 0) ?>
                                      </span>
                                      <span class="text-[10px] block font-medium <?= $bm_val >= 0 ? 'text-green-600' : 'text-red-500' ?>">
                                        (<?= fmtNum($bm_pct, 1) ?>%)
                                      </span>
                                    <?php elseif ($b['sold_qty'] > 0): ?>
                                      <span class="text-gray-400 text-[10px]">-</span>
                                    <?php else: ?>
                                      <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                  </td>
                                  <td class="p-1.5 border text-right font-bold <?= $b['remaining_qty'] > 0 ? 'text-blue-700' : 'text-gray-400' ?>">
                                    <?= fmtNum($b['remaining_qty'], 0) ?> <?= htmlspecialchars($p['unit_symbol']) ?>
                                  </td>
                                  <td class="p-1.5 border text-right font-bold <?= $b['remaining_cost'] > 0 ? 'text-blue-700' : 'text-gray-400' ?>">
                                    Rp <?= fmtNum($b['remaining_cost'], 0) ?>
                                  </td>
                                  <td class="p-1.5 border text-center">
                                    <?php if ($is_exhausted): ?>
                                      <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-200 text-gray-700">Habis Terjual</span>
                                    <?php elseif ($is_partial): ?>
                                      <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800 border border-yellow-300">Sisa Sebagian</span>
                                    <?php else: ?>
                                      <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-300">Belum Terjual</span>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Log Alur Stok IN (Produksi), OUT (Penjualan) & SUSUT (Penyusutan) -->
    <section class="bg-white p-4 rounded-lg shadow space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-100">
        <div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">sync_alt</span>
            <h3 class="font-bold text-gray-800 text-base">Log Alur Stok: IN (Produksi), OUT (Terjual) & SUSUT (Penyusutan)</h3>
          </div>
          <p class="text-xs text-gray-500 mt-0.5">Catatan riwayat masuk barang dari proses produksi, keluar barang dari penjualan, dan penyusutan bobot</p>
        </div>
        <!-- Ringkasan Cepat -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <span class="px-2.5 py-1 rounded bg-yellow-100 text-yellow-800 font-semibold border border-yellow-300 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-yellow-500 inline-block"></span>
            Total IN: <?= fmtNum($total_log_in_qty, 0) ?> g (<?= $count_log_in ?>x)
          </span>
          <span class="px-2.5 py-1 rounded bg-green-100 text-green-800 font-semibold border border-green-300 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
            Total OUT: <?= fmtNum($total_log_out_qty, 0) ?> g (<?= $count_log_out ?>x)
          </span>
          <span class="px-2.5 py-1 rounded bg-amber-100 text-amber-900 font-semibold border border-amber-300 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
            Total SUSUT: <?= fmtNum($total_log_susut_qty, 0) ?> g (<?= $count_log_susut ?>x)
          </span>
        </div>
      </div>

      <!-- Controls: Search, Rows Per Page, & Filter Tabs -->
      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 flex-wrap">
        <div class="inline-flex rounded-md shadow-sm" role="group">
          <button type="button" onclick="filterLogType('ALL')" id="btnFilterAll" class="px-3 py-1.5 text-xs font-semibold rounded-l-lg border bg-blue-600 text-white border-blue-600 transition">
            Semua (<?= count($stock_logs) ?>)
          </button>
          <button type="button" onclick="filterLogType('IN')" id="btnFilterIn" class="px-3 py-1.5 text-xs font-semibold border-t border-b border-r bg-white text-yellow-800 border-gray-300 hover:bg-yellow-50 transition">
            IN / Produksi (<?= $count_log_in ?>)
          </button>
          <button type="button" onclick="filterLogType('OUT')" id="btnFilterOut" class="px-3 py-1.5 text-xs font-semibold border-t border-b border-r bg-white text-green-800 border-gray-300 hover:bg-green-50 transition">
            OUT / Terjual (<?= $count_log_out ?>)
          </button>
          <button type="button" onclick="filterLogType('SUSUT')" id="btnFilterSusut" class="px-3 py-1.5 text-xs font-semibold rounded-r-lg border-t border-b border-r bg-white text-amber-800 border-gray-300 hover:bg-amber-50 transition">
            SUSUT (<?= $count_log_susut ?>)
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="text-xs flex items-center gap-1.5 text-gray-600">
            <span>Tampilkan</span>
            <select id="logRowsPerPage" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:ring-1 focus:ring-blue-500">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="all">Semua</option>
            </select>
            <span>baris</span>
          </div>

          <div class="w-full sm:w-64">
            <input type="text" id="searchLogInput" placeholder="Cari produk, no ref, buyer..." class="w-full border border-gray-300 rounded px-3 py-1 text-xs focus:ring-1 focus:ring-blue-500">
          </div>
        </div>
      </div>

      <!-- Table Container -->
      <div class="overflow-x-auto">
        <table id="stockLogTable" class="min-w-full text-sm border border-gray-200">
          <thead class="bg-gray-800 text-white text-xs uppercase tracking-wider">
            <tr>
              <th class="p-2.5 border text-center whitespace-nowrap">Tanggal</th>
              <th class="p-2.5 border text-center whitespace-nowrap">Jenis</th>
              <th class="p-2.5 border text-center whitespace-nowrap">No. Ref / Invoice</th>
              <th class="p-2.5 border text-left whitespace-nowrap">Produk</th>
              <th class="p-2.5 border text-right whitespace-nowrap bg-yellow-600 text-yellow-50">Produksi (IN)</th>
              <th class="p-2.5 border text-right whitespace-nowrap bg-green-600 text-green-50">Terjual (OUT)</th>
              <th class="p-2.5 border text-right whitespace-nowrap bg-amber-600 text-amber-50">Susut</th>
              <th class="p-2.5 border text-right whitespace-nowrap bg-blue-700 text-blue-50">Stok Terakhir</th>
              <th class="p-2.5 border text-right whitespace-nowrap">Nilai / HPP Sisa</th>
              <th class="p-2.5 border text-left whitespace-nowrap">Keterangan / Buyer</th>
            </tr>
          </thead>
          <tbody id="stockLogBody" class="divide-y divide-gray-200 text-xs">
            <?php if (empty($stock_logs)): ?>
              <tr>
                <td colspan="10" class="p-4 text-center text-gray-500">Tidak ada log aktivitas produksi, penjualan, atau penyusutan pada periode ini.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($stock_logs as $log):
                $is_in = ($log['jenis'] === 'IN');
                $is_out = ($log['jenis'] === 'OUT');
                $is_susut = ($log['jenis'] === 'SUSUT');

                if ($is_in) {
                  $row_bg = 'bg-yellow-100 hover:bg-yellow-200 border-yellow-200';
                } elseif ($is_susut) {
                  $row_bg = 'bg-amber-100 hover:bg-amber-200 border-amber-200';
                } else {
                  $row_bg = 'bg-green-100 hover:bg-green-200 border-green-200';
                }
              ?>
                <tr class="log-row <?= $row_bg ?> transition" data-jenis="<?= $log['jenis'] ?>">
                  <td class="p-2 border text-center whitespace-nowrap" data-label="Tanggal">
                    <?= date("d/m/Y H:i", strtotime($log['tanggal'])) ?>
                  </td>
                  <td class="p-2 border text-center whitespace-nowrap" data-label="Jenis">
                    <?php if ($is_in): ?>
                      <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-300 text-yellow-900 border border-yellow-400 inline-flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-xs">arrow_downward</span> IN (Produksi)
                      </span>
                    <?php elseif ($is_susut): ?>
                      <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-300 text-amber-950 border border-amber-400 inline-flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-xs">scale</span> SUSUT
                      </span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-500 text-white border border-green-600 inline-flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-xs">arrow_upward</span> OUT (Terjual)
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2 border text-center font-mono font-medium whitespace-nowrap" data-label="No. Ref">
                    <?= htmlspecialchars($log['ref_no']) ?>
                  </td>
                  <td class="p-2 border font-medium text-gray-900 whitespace-nowrap" data-label="Produk">
                    <?= htmlspecialchars($log['product_name']) ?>
                  </td>
                  <td class="p-2 border text-right font-bold text-yellow-900 whitespace-nowrap" data-label="Total Produksi (IN)">
                    <?php if ($is_in): ?>
                      <?php if ($log['status_transaksi'] === 'Proses' && (float)$log['qty_in'] <= 0): ?>
                        <span class="text-xs px-2 py-0.5 rounded bg-yellow-200 text-yellow-800 font-medium">Dalam Proses</span>
                      <?php else: ?>
                        +<?= fmtNum($log['qty_in'], 0) ?> <?= htmlspecialchars($log['symbol']) ?>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-gray-400 font-normal">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2 border text-right font-bold text-green-900 whitespace-nowrap" data-label="Total Terjual (OUT)">
                    <?php if ($is_out): ?>
                      -<?= fmtNum($log['qty_out'], 0) ?> <?= htmlspecialchars($log['symbol']) ?>
                    <?php else: ?>
                      <span class="text-gray-400 font-normal">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2 border text-right font-bold text-amber-900 whitespace-nowrap" data-label="Susut">
                    <?php if ($is_susut): ?>
                      -<?= fmtNum($log['qty_susut'], 0) ?> <?= htmlspecialchars($log['symbol']) ?>
                    <?php else: ?>
                      <span class="text-gray-400 font-normal">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2 border text-right font-bold <?= (float)$log['stok_terakhir'] <= 0 ? 'text-red-600' : 'text-blue-900' ?> whitespace-nowrap" data-label="Stok Terakhir">
                    <?= fmtNum($log['stok_terakhir'], 0) ?>
                  </td>
                  <td class="p-2 border text-right font-semibold whitespace-nowrap" data-label="Nilai / HPP Sisa">
                    <?php if ($is_susut && (float)$log['hpp_info'] > 0): ?>
                      <div class="text-amber-900 font-bold text-xs">HPP: Rp <?= fmtIDR($log['hpp_info']) ?>/<?= htmlspecialchars($log['symbol']) ?></div>
                      <div class="text-[10px] text-gray-600 font-normal">Modal: Rp <?= fmtIDR($log['nominal']) ?></div>
                    <?php else: ?>
                      Rp <?= fmtIDR($log['nominal']) ?>
                    <?php endif; ?>
                  </td>
                  <td class="p-2 border whitespace-nowrap" data-label="Keterangan">
                    <?= htmlspecialchars($log['pihak_keterangan']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- Footer: Info, Legend & Pagination Controls -->
      <div class="flex flex-col md:flex-row items-center justify-between text-xs text-gray-600 pt-3 border-t border-gray-100 gap-3">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
          <div id="logCountInfo" class="font-medium">Menampilkan <?= count($stock_logs) ?> riwayat pergerakan stok</div>
          <div class="flex items-center gap-3 text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-yellow-200 border border-yellow-400 rounded inline-block"></span> Kuning: Produksi (IN)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-200 border border-green-400 rounded inline-block"></span> Hijau: Terjual (OUT)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-200 border border-amber-400 rounded inline-block"></span> Oranye: Susut</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-blue-200 border border-blue-400 rounded inline-block"></span> Biru: Stok Terakhir</span>
          </div>
        </div>

        <!-- Pagination Controls -->
        <div id="logPaginationControls" class="flex flex-wrap items-center gap-1"></div>
      </div>
    </section>

    <!-- Transactions table -->
    <section class="bg-white p-4 rounded-lg shadow">
      <div class="flex justify-between items-center mb-3 cari-invoice">
        <h3 class="font-semibold">Riwayat Penjualan (<?= $total_count ?>)</h3>
        <div class="text-sm text-gray-500">Halaman <?= $page ?> / <?= $total_pages ?></div>
      </div>
      <div class="overflow-x-auto">
        <table id="transactionsTable" class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2 border">Tanggal</th>
              <th class="p-2 border">Invoice</th>
              <th class="p-2 border">Buyer</th>
              <th class="p-2 border">Qty (Kg)</th>
              <th class="p-2 border">Total</th>
              <th class="p-2 border">DP</th>
              <th class="p-2 border">Status</th>
              <th class="p-2 border">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $tr): ?>
              <tr class="hover:bg-gray-50">
                <td class="p-2 border" data-label="Tanggal"><?= date("d/m/Y H:i", strtotime($tr['selling_date'])) ?></td>
                <td class="p-2 border" data-label="Invoice"><?= htmlspecialchars($tr['invoice_number']) ?></td>
                <td class="p-2 border" data-label="Buyer"><?= htmlspecialchars($tr['buyer_name']) ?></td>
                <td class="p-2 border text-right" data-label="Qty (Kg)"><?php
                                                                        $qty_kg = $tr['qty'] / 1000;
                                                                        echo ($qty_kg == floor($qty_kg)) ? fmtNum($qty_kg, 0) : rtrim(rtrim(fmtNum($qty_kg, 2), '0'), ',');
                                                                        ?></td>
                <td class="p-2 border text-right font-semibold" data-label="Total">Rp <?= fmtIDR($tr['total_selling']) ?></td>
                <td class="p-2 border text-right" data-label="DP">Rp <?= fmtIDR($tr['dp']) ?></td>
                <td class="p-2 border" data-label="Status">
                  <?php
                  $st = $tr['status'] ?? '';
                  if ($st === 'Lunas') {
                    $badge_cls = 'bg-green-100 text-green-700';
                  } elseif ($st === 'DP') {
                    $badge_cls = 'bg-yellow-100 text-yellow-800';
                  } else {
                    $badge_cls = 'bg-red-100 text-red-700';
                  }
                  ?>
                  <span class="px-2 py-1 rounded text-xs font-semibold <?= $badge_cls ?>">
                    <?= htmlspecialchars($st) ?>
                  </span>
                </td>
                <td class="p-2 border text-center whitespace-nowrap" data-label="PDF">
                  <div class="flex justify-between items-center gap-2">
                    <a href="transaksi-invoice.php?invoice=<?= urlencode($tr['invoice_number']) ?>" class="text-green-600 hover:text-green-800" target="_blank">
                      <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- simple pagination -->
      <div class="mt-3 flex justify-between items-center">
        <div class="text-sm text-gray-600">Menampilkan <?= count($transactions) ?> transaksi</div>
        <div class="flex gap-2">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>
          <?php endif; ?>
          <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <footer class="text-center text-xs text-gray-500">Laporan dihasilkan: <?= date("d M Y H:i:s") ?> — Sistem Laporan</footer>
  </main>

  <!-- Modal Cetak PDF Laporan Data Penjualan -->
  <div id="modalPDF" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
      <h2 class="text-lg font-semibold mb-1">Cetak PDF &mdash; Laporan Data Penjualan</h2>
      <p class="text-xs text-gray-500 mb-4">Kosongkan tanggal untuk mencetak semua data (all time).</p>
      <form action="data-laporan-pdf.php" method="get" target="_blank" class="space-y-4">
        <div>
          <label for="pdf_start_date" class="block text-sm font-medium text-gray-700">Dari Tanggal <span class="text-gray-400">(opsional)</span></label>
          <input type="date" name="start_date" id="pdf_start_date" class="w-full border rounded px-3 py-2 mt-1 text-sm" />
        </div>
        <div>
          <label for="pdf_end_date" class="block text-sm font-medium text-gray-700">Sampai Tanggal <span class="text-gray-400">(opsional)</span></label>
          <input type="date" name="end_date" id="pdf_end_date" class="w-full border rounded px-3 py-2 mt-1 text-sm" />
        </div>
        <div>
          <label for="pdf_buyer" class="block text-sm font-medium text-gray-700">Buyer <span class="text-gray-400">(opsional)</span></label>
          <select name="buyer" id="pdf_buyer" class="w-full border rounded px-3 py-2 mt-1 text-sm">
            <option value="">Semua Buyer</option>
            <?php
            $buyers_modal = $conn->query("SELECT id, name FROM buyer_products ORDER BY name ASC");
            if ($buyers_modal) while ($bm = $buyers_modal->fetch_assoc()) {
              $sel_bm = $buyer_id == $bm['id'] ? 'selected' : '';
              echo '<option value="' . (int)$bm['id'] . '" ' . $sel_bm . '>' . htmlspecialchars($bm['name']) . '</option>';
            }
            ?>
          </select>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="closeModalPDF" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">Batal</button>
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-semibold">&#128438; Cetak PDF</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Chart Sales  
    const months = <?= json_encode($months) ?>;
    const monthValues = <?= json_encode($month_values) ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: months,
        datasets: [{
          label: 'Penjualan',
          data: monthValues,
          fill: true,
          backgroundColor: 'rgba(99,102,241,0.12)',
          borderColor: 'rgba(99,102,241,1)',
          pointRadius: 3,
          tension: 0.2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            ticks: {
              callback: function(value) {
                return new Intl.NumberFormat('id-ID').format(value);
              }
            }
          }
        }
      }
    });

    // download chart image  
    document.getElementById('downloadSalesChart').addEventListener('click', () => {
      const url = salesChart.toBase64Image();
      const a = document.createElement('a');
      a.href = url;
      a.download = 'penjualan_bulanan.png';
      a.click();
    });

    // Export CSV (transactions table)  
    function tableToCSV() {
      const rows = Array.from(document.querySelectorAll('#transactionsTable tr'));
      const csv = rows.map(tr => {
        const cols = Array.from(tr.querySelectorAll('th,td')).map(td => {
          return '"' + td.innerText.replace(/"/g, '""').trim() + '"';
        });
        return cols.join(',');
      }).join('\n');
      return csv;
    }
    document.getElementById('btnExportCSV').addEventListener('click', () => {
      const csv = tableToCSV();
      const blob = new Blob([csv], {
        type: 'text/csv;charset=utf-8;'
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'laporan_transaksi_<?= date("Ymd_His") ?>.csv';
      a.click();
      URL.revokeObjectURL(url);
    });

    // Modal PDF & Cetak Button
    const btnPrint = document.getElementById('btnPrint');
    const modalPDF = document.getElementById('modalPDF');
    const closeModalPDF = document.getElementById('closeModalPDF');
    const pdfStartDate = document.getElementById('pdf_start_date');
    const pdfEndDate = document.getElementById('pdf_end_date');

    if (btnPrint && modalPDF) {
      btnPrint.addEventListener('click', (e) => {
        e.preventDefault();
        const filterFrom = document.querySelector('input[name="from"]');
        const filterTo = document.querySelector('input[name="to"]');

        if (filterFrom && filterFrom.value) {
          pdfStartDate.value = filterFrom.value;
        }
        if (filterTo && filterTo.value) {
          pdfEndDate.value = filterTo.value;
        }

        modalPDF.classList.remove('hidden');
      });
    }

    if (closeModalPDF && modalPDF) {
      closeModalPDF.addEventListener('click', () => {
        modalPDF.classList.add('hidden');
      });
    }

    // Log Alur Stok: Filtering (Tabs & Search) + Pagination + Rows Per Page
    let activeLogFilter = 'ALL';
    let currentLogPage = 1;
    const searchLogInput = document.getElementById('searchLogInput');
    const logRowsPerPage = document.getElementById('logRowsPerPage');
    const logRows = Array.from(document.querySelectorAll('.log-row'));
    const logCountInfo = document.getElementById('logCountInfo');
    const logPaginationControls = document.getElementById('logPaginationControls');

    function filterLogType(type) {
      activeLogFilter = type;
      currentLogPage = 1;

      const btnAll = document.getElementById('btnFilterAll');
      const btnIn = document.getElementById('btnFilterIn');
      const btnOut = document.getElementById('btnFilterOut');
      const btnSusut = document.getElementById('btnFilterSusut');

      if (btnAll && btnIn && btnOut) {
        btnAll.className = 'px-3 py-1.5 text-xs font-semibold rounded-l-lg border transition ' + (type === 'ALL' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50');
        btnIn.className = 'px-3 py-1.5 text-xs font-semibold border-t border-b border-r transition ' + (type === 'IN' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-white text-yellow-800 border-gray-300 hover:bg-yellow-50');
        btnOut.className = 'px-3 py-1.5 text-xs font-semibold border-t border-b border-r transition ' + (type === 'OUT' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-green-800 border-gray-300 hover:bg-green-50');
        if (btnSusut) {
          btnSusut.className = 'px-3 py-1.5 text-xs font-semibold rounded-r-lg border-t border-b border-r transition ' + (type === 'SUSUT' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-amber-800 border-gray-300 hover:bg-amber-50');
        }
      }

      paginateLog();
    }

    function paginateLog() {
      const q = searchLogInput ? searchLogInput.value.toLowerCase().trim() : '';
      const rppVal = logRowsPerPage ? logRowsPerPage.value : '10';
      const isAll = (rppVal === 'all');
      const maxRows = isAll ? logRows.length : (parseInt(rppVal, 10) || 10);

      // 1. Filter baris sesuai tab & query pencarian
      const matchingRows = logRows.filter(row => {
        const jenis = row.getAttribute('data-jenis');
        const text = row.innerText.toLowerCase();
        const matchesType = (activeLogFilter === 'ALL' || jenis === activeLogFilter);
        const matchesSearch = (!q || text.indexOf(q) !== -1);
        return matchesType && matchesSearch;
      });

      const totalMatching = matchingRows.length;
      const totalPages = Math.max(1, Math.ceil(totalMatching / maxRows));
      currentLogPage = Math.min(Math.max(1, currentLogPage), totalPages);

      const startIndex = (currentLogPage - 1) * maxRows;
      const endIndex = startIndex + maxRows;

      // 2. Sembunyikan semua baris
      logRows.forEach(row => {
        row.style.display = 'none';
      });

      // 3. Tampilkan baris pada halaman aktif
      matchingRows.forEach((row, index) => {
        if (isAll || (index >= startIndex && index < endIndex)) {
          row.style.display = '';
        }
      });

      // 4. Perbarui Info Teks
      if (logCountInfo) {
        if (totalMatching === 0) {
          logCountInfo.textContent = 'Tidak ada data yang sesuai filter';
        } else if (isAll) {
          logCountInfo.textContent = `Menampilkan semua ${totalMatching} data dari total ${logRows.length} riwayat`;
        } else {
          const fromNum = startIndex + 1;
          const toNum = Math.min(endIndex, totalMatching);
          logCountInfo.textContent = `Menampilkan ${fromNum}-${toNum} dari total ${totalMatching} data`;
        }
      }

      // 5. Render Tombol Paginasi
      if (logPaginationControls) {
        logPaginationControls.innerHTML = '';

        if (totalPages > 1 && !isAll) {
          // Tombol Prev
          const btnPrev = document.createElement('button');
          btnPrev.type = 'button';
          btnPrev.className = 'px-2 py-1 text-xs border rounded transition ' + (currentLogPage > 1 ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed');
          btnPrev.innerHTML = '&laquo;';
          btnPrev.disabled = (currentLogPage <= 1);
          btnPrev.onclick = () => {
            if (currentLogPage > 1) {
              currentLogPage--;
              paginateLog();
            }
          };
          logPaginationControls.appendChild(btnPrev);

          // Nomor Halaman (sliding window)
          let startPage = Math.max(1, currentLogPage - 2);
          let endPage = Math.min(totalPages, startPage + 4);
          if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
          }

          if (startPage > 1) {
            const btnFirst = document.createElement('button');
            btnFirst.type = 'button';
            btnFirst.className = 'px-2.5 py-1 text-xs border rounded bg-white hover:bg-gray-100 text-gray-700 transition';
            btnFirst.textContent = '1';
            btnFirst.onclick = () => {
              currentLogPage = 1;
              paginateLog();
            };
            logPaginationControls.appendChild(btnFirst);

            if (startPage > 2) {
              const dots = document.createElement('span');
              dots.className = 'px-1 text-gray-400 text-xs';
              dots.textContent = '...';
              logPaginationControls.appendChild(dots);
            }
          }

          for (let p = startPage; p <= endPage; p++) {
            const btnPage = document.createElement('button');
            btnPage.type = 'button';
            btnPage.className = 'px-2.5 py-1 text-xs border rounded font-medium transition ' + (p === currentLogPage ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300');
            btnPage.textContent = p;
            btnPage.onclick = () => {
              currentLogPage = p;
              paginateLog();
            };
            logPaginationControls.appendChild(btnPage);
          }

          if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
              const dots = document.createElement('span');
              dots.className = 'px-1 text-gray-400 text-xs';
              dots.textContent = '...';
              logPaginationControls.appendChild(dots);
            }
            const btnLast = document.createElement('button');
            btnLast.type = 'button';
            btnLast.className = 'px-2.5 py-1 text-xs border rounded bg-white hover:bg-gray-100 text-gray-700 transition';
            btnLast.textContent = totalPages;
            btnLast.onclick = () => {
              currentLogPage = totalPages;
              paginateLog();
            };
            logPaginationControls.appendChild(btnLast);
          }

          // Tombol Next
          const btnNext = document.createElement('button');
          btnNext.type = 'button';
          btnNext.className = 'px-2 py-1 text-xs border rounded transition ' + (currentLogPage < totalPages ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed');
          btnNext.innerHTML = '&raquo;';
          btnNext.disabled = (currentLogPage >= totalPages);
          btnNext.onclick = () => {
            if (currentLogPage < totalPages) {
              currentLogPage++;
              paginateLog();
            }
          };
          logPaginationControls.appendChild(btnNext);
        }
      }
    }

    if (searchLogInput) {
      searchLogInput.addEventListener('input', () => {
        currentLogPage = 1;
        paginateLog();
      });
    }

    if (logRowsPerPage) {
      logRowsPerPage.addEventListener('change', () => {
        currentLogPage = 1;
        paginateLog();
      });
    }

    // Toggle Rincian Batch FIFO
    function toggleBatchDetail(rowId) {
      const row = document.getElementById(rowId);
      const pid = rowId.replace('batch-row-', '');
      const icon = document.getElementById('batch-icon-' + pid);
      if (row) {
        if (row.classList.contains('hidden')) {
          row.classList.remove('hidden');
          if (icon) icon.textContent = 'expand_less';
        } else {
          row.classList.add('hidden');
          if (icon) icon.textContent = 'expand_more';
        }
      }
    }

    // Inisialisasi paginasi
    paginateLog();
  </script>
</body>

</html>