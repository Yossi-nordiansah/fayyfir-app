<?php  
session_start();  
if (!isset($_SESSION["user_id"])) {  
  header("Location: login");  
  exit();  
}  
  
require "config.php";  
  
// --- Helpers ---  
function fmtIDR($n){ return number_format($n,0,',','.'); }  
function fmtNum($n, $dec = 2){ return number_format($n,$dec,',','.'); }  
  
// --- Filters (GET) ---  
$from = $_GET['from'] ?? null; // yyyy-mm-dd  
$to   = $_GET['to']   ?? null; // yyyy-mm-dd  
$buyer_id = isset($_GET['buyer']) && $_GET['buyer'] !== '' ? (int)$_GET['buyer'] : null;  
$product_id = isset($_GET['product']) && $_GET['product'] !== '' ? (int)$_GET['product'] : null;  
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;  
  
// build WHERE parts  
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
// 1) Total pembelian material  
$total_purchases = $conn->query("SELECT COALESCE(SUM(total_price),0) as total FROM material_purchases")->fetch_assoc()['total'] ?? 0;  
  
// 2) Total produksi (count)  
$total_productions = $conn->query("SELECT COUNT(*) as total FROM productions")->fetch_assoc()['total'] ?? 0;  
  
// 3) Total penjualan (filtered)  
$sql_total_sales = "SELECT COALESCE(SUM(s.total_selling),0) AS total FROM selling_products s WHERE $where_sql";  
$stmt = $conn->prepare($sql_total_sales);  
if ($params) $stmt->bind_param($types, ...$params);  
$stmt->execute();  
$total_sales = $stmt->get_result()->fetch_assoc()['total'] ?? 0;  
$stmt->close();  
  
// 4) PPh estimate (0.25% dari total)  
$pph = $total_sales * 0.0025;  
  
// 5) Gross margin simple: total_selling - total_production_expenses (approx)  
$expense_row = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM production_expenses")->fetch_assoc();  
$total_production_expenses = $expense_row['total'] ?? 0;  
$gross_profit = $total_sales - $total_production_expenses;  
  
// 6) Stok tersisa per produk (calculated)  
$prod_q = $conn->query("  
SELECT 
    p.id,
    ps.product_name,
    p.total_output,
    COALESCE(SUM(sp.qty),0) AS total_sold,
    (p.total_output - COALESCE(SUM(sp.qty),0)) AS stok_tersisa,
    u.symbol
FROM productions p
JOIN product_stocks ps ON p.product_id = ps.id
LEFT JOIN selling_products sp ON sp.product_id = ps.id
LEFT JOIN units u ON p.unit_id = u.id
WHERE p.status != 'Proses'
GROUP BY p.id, ps.product_name, p.total_output, u.symbol;
");  
  
// 7) Grand total biaya produksi.  
$total = $conn->query("SELECT COALESCE(SUM(total_pro_expenses),0) AS total_expenses, COALESCE(SUM(total_pro_materials),0) AS total_materials FROM productions")->fetch_assoc();  
$total_pengeluaran = $total['total_expenses'] ?? 0;  
$total_bahan = $total['total_materials'] ?? 0;  
$grand_total_produksi = $total_pengeluaran + $total_bahan;  
  
$grand_profit = $total_sales - $grand_total_produksi;  
  
// gather products for filter dropdown  
$products_for_filter = $conn->query("SELECT p.id, ps.product_name
FROM productions p
JOIN product_stocks ps ON p.product_id = ps.id
ORDER BY ps.product_name");  
  
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
$months = []; $month_values = [];  
while ($r = $res_monthly->fetch_assoc()){  
  $months[] = $r['ym'];  
  $month_values[] = (float)$r['total'];  
}  
$stmt->close();  
  
// --- Top products (by revenue) ---  
$sql_top_products = "  
  SELECT p.id, ps.product_name, u.symbol, SUM(sp.qty) AS total_qty, SUM(sp.total_selling) AS revenue
FROM selling_products sp
JOIN productions p ON sp.product_id = p.id
JOIN product_stocks ps ON p.product_id = ps.id
LEFT JOIN units u ON p.unit_id = u.id
WHERE $where_sql
GROUP BY p.id, ps.product_name, u.symbol
ORDER BY revenue DESC
LIMIT 8
";  
$stmt = $conn->prepare($sql_top_products);  
if ($params) $stmt->bind_param($types, ...$params);  
$stmt->execute();  
$res_top_products = $stmt->get_result();  
$top_products = [];  
while ($r = $res_top_products->fetch_assoc()){  
  $top_products[] = $r;  
}  
$stmt->close();  
  
// --- Top buyers ---  
$sql_top_buyers = "  
  SELECT b.id, b.name, SUM(sp.total_selling) AS total_spent, COUNT(DISTINCT sp.invoice_number) AS invoices  
  FROM selling_products sp  
  JOIN buyer_products b ON sp.buyer_id = b.id  
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
$offset = ($page-1)*$perPage;  
  
$sql_history = "  
  SELECT s.id, s.selling_date, s.invoice_number, b.name as buyer_name, ps.product_name, u.symbol, s.price, s.dp, s.status,
       SUM(s.qty) AS qty,
       SUM(s.total_selling) AS total_selling
FROM selling_products s
LEFT JOIN buyer_products b ON s.buyer_id = b.id
LEFT JOIN productions p ON p.product_id = s.product_id
JOIN product_stocks ps ON ps.id = s.product_id
LEFT JOIN units u ON p.unit_id = u.id
WHERE $where_sql
GROUP BY s.invoice_number, s.buyer_id, ps.product_name, u.symbol
ORDER BY s.selling_date DESC
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
$sql_count = "SELECT COUNT(*) AS cnt FROM selling_products s WHERE $where_sql";  
$stmt = $conn->prepare($sql_count);  
if ($params) $stmt->bind_param($types, ...$params);  
$stmt->execute();  
$total_count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;  
$stmt->close();  
$total_pages = max(1, ceil($total_count / $perPage));  
  
?>  
<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="utf-8"/>  
  <meta name="viewport" content="width=device-width,initial-scale=1"/>  
  <title>Laporan - Dashboard</title>  
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>  
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">  
  <style>  
    /* Mobile friendly table */  
    @media (max-width: 640px){  
      table thead { display: none; }  
      table tbody tr {   
        display: block;   
        margin-bottom: .75rem;   
        border-radius: .5rem;   
        background: #fff;   
        padding: .5rem;   
        box-shadow: 0 1px 2px rgba(0,0,0,.04);  
      }  
      table tbody td {   
        display: flex;   
        justify-content: space-between;   
        padding: .5rem 0;   
        border-bottom: 1px dashed #eee;   
      }  
      table tbody td:last-child { border-bottom: none; }  
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
    <!-- Filter -->  
    <section class="bg-white p-4 rounded-lg shadow" hidden>  
      <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">  
        <div>  
          <label class="text-xs text-gray-600">Dari</label>  
          <input type="date" name="from" value="<?= htmlentities($from) ?>" class="mt-1 w-full border rounded px-2 py-1">  
        </div>  
        <div>  
          <label class="text-xs text-gray-600">Sampai</label>  
          <input type="date" name="to" value="<?= htmlentities($to) ?>" class="mt-1 w-full border rounded px-2 py-1">  
        </div>  
        <div>  
          <label class="text-xs text-gray-600">Buyer</label>  
          <select name="buyer" class="mt-1 w-full border rounded px-2 py-1">  
            <option value="">Semua Buyer</option>  
            <?php while($b = $buyers_for_filter->fetch_assoc()): ?>  
              <option value="<?= $b['id'] ?>" <?= $buyer_id == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>  
            <?php endwhile; ?>  
          </select>  
        </div>  
        <div>  
          <label class="text-xs text-gray-600">Produk</label>  
          <select name="product" class="mt-1 w-full border rounded px-2 py-1">  
            <option value="">Semua Produk</option>  
            <?php while($p = $products_for_filter->fetch_assoc()): ?>  
              <option value="<?= $p['id'] ?>" <?= $product_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['product_name']) ?></option>  
            <?php endwhile; ?>  
          </select>  
        </div>  
        <div>  
          <label class="text-xs text-gray-600">Status</label>  
          <select name="status" class="mt-1 w-full border rounded px-2 py-1">  
            <option value="">Semua Status</option>  
            <option value="DP" <?= $status_filter === 'DP' ? 'selected' : '' ?>>DP</option>  
            <option value="Lunas" <?= $status_filter === 'Lunas' ? 'selected' : '' ?>>Lunas</option>  
          </select>  
        </div>  
        <div class="flex gap-2">  
          <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded">Terapkan</button>  
          <a href="data-laporan" class="bg-gray-200 text-gray-800 px-3 py-2 rounded">Reset</a>  
        </div>  
      </form>  
    </section>  
  
    <!-- KPI -->  
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">  
      <div class="bg-white p-4 rounded-lg shadow">  
        <div class="text-sm text-gray-500">Total Pembelian Bahan</div>  
        <div class="mt-2 text-xl font-bold text-green-600">Rp <?= fmtIDR($total_purchases) ?></div>  
      </div>  
      <div class="bg-white p-4 rounded-lg shadow">  
        <div class="text-sm text-gray-500">Total Biaya Produksi</div>  
        <div class="mt-2 text-xl font-bold text-blue-600">Rp <?= fmtNum($grand_total_produksi,0) ?></div>  
      </div>  
      <div class="bg-white p-4 rounded-lg shadow">  
        <div class="text-sm text-gray-500">Total Penjualan</div>  
        <div class="mt-2 text-xl font-bold text-purple-600">Rp <?= fmtIDR($total_sales) ?></div>  
      </div>  
      <div class="bg-white p-4 rounded-lg shadow">  
        <div class="text-sm text-gray-500">Total Profit</div>  
        <div class="mt-2 text-xl font-bold text-red-600">Rp <?= fmtIDR($grand_profit) ?></div>  
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
          <?php foreach($top_products as $tp): ?>  
            <li class="flex items-center justify-between">  
              <div>  
                <div class="text-sm font-medium"><?= htmlspecialchars($tp['product_name']) ?></div>  
                <div class="text-xs text-gray-500"><?= fmtNum($tp['total_qty'],0) ?> <?= htmlspecialchars($tp['symbol']) ?></div>  
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
        <?php foreach($top_buyers as $tb): ?>  
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
  
    <!-- Stock summary -->  
    <section class="bg-white p-4 rounded-lg shadow">  
      <h3 class="font-semibold mb-3">Stok Produk (Tersisa)</h3>  
      <div class="overflow-x-auto">  
        <table class="min-w-full text-sm border">  
          <thead class="bg-gray-50">  
            <tr>  
              <th class="p-2 border">Produk</th>  
              <th class="p-2 border">Stok</th>  
              <th class="p-2 border">Terjual</th>  
              <th class="p-2 border">Tersisa</th>  
            </tr>  
          </thead>  
          <tbody>  
            <?php while($p = $prod_q->fetch_assoc()):     
              $tersisa = (float)$p['stok_tersisa'];    
              $low = $tersisa <= 0 ? 'bg-red-50' : ($tersisa < 5 ? 'bg-yellow-50' : '');    
            ?>    
            <tr class="<?= $low ?>">  
              <td class="p-2 border" data-label="Produk"><?= htmlspecialchars($p['product_name']) ?></td>  
              <td class="p-2 border text-right" data-label="Stok"><?= fmtNum($p['total_output'],0) ?> <?= htmlspecialchars($p['symbol']) ?></td>  
              <td class="p-2 border text-right" data-label="Terjual"><?= fmtNum($p['total_sold'],0) ?> <?= htmlspecialchars($p['symbol']) ?></td>  
              <td class="p-2 border text-right font-semibold" data-label="Tersisa"><?= fmtNum($tersisa,0) ?> <?= htmlspecialchars($p['symbol']) ?></td>  
            </tr>  
            <?php endwhile; ?>  
          </tbody>  
        </table>  
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
              <th class="p-2 border">Qty</th>  
              <th class="p-2 border">Total</th>  
              <th class="p-2 border">DP</th>  
              <th class="p-2 border">Status</th>  
              <th class="p-2 border">Aksi</th>  
            </tr>  
          </thead>  
          <tbody>  
            <?php foreach($transactions as $tr): ?>  
            <tr class="hover:bg-gray-50">  
              <td class="p-2 border" data-label="Tanggal"><?= date("d/m/Y H:i", strtotime($tr['selling_date'])) ?></td>  
              <td class="p-2 border" data-label="Invoice"><?= htmlspecialchars($tr['invoice_number']) ?></td>  
              <td class="p-2 border" data-label="Buyer"><?= htmlspecialchars($tr['buyer_name']) ?></td>  
              <td class="p-2 border text-right" data-label="Qty"><?= fmtNum($tr['qty'],0) ?></td>  
              <td class="p-2 border text-right font-semibold" data-label="Total">Rp <?= fmtIDR($tr['total_selling']) ?></td>  
              <td class="p-2 border text-right" data-label="DP">Rp <?= fmtIDR($tr['dp']) ?></td>  
              <td class="p-2 border" data-label="Status">  
                <span class="px-2 py-1 rounded text-xs <?= $tr['status']=='Lunas' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">  
                  <?= htmlspecialchars($tr['status']) ?>  
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
          <?php if($page > 1): ?>  
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>  
          <?php endif; ?>  
          <?php if($page < $total_pages): ?>  
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>  
          <?php endif; ?>  
        </div>  
      </div>  
    </section>  
  
    <footer class="text-center text-xs text-gray-500">Laporan dihasilkan: <?= date("d M Y H:i:s") ?> — Sistem Laporan</footer>  
  </main>  
  
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
      plugins: { legend: { display: false } },  
      scales: {  
        y: {  
          ticks: {  
            callback: function(value){ return new Intl.NumberFormat('id-ID').format(value); }  
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
        return '"' + td.innerText.replace(/"/g,'""').trim() + '"';  
      });  
      return cols.join(',');  
    }).join('\n');  
    return csv;  
  }  
  document.getElementById('btnExportCSV').addEventListener('click', () => {  
    const csv = tableToCSV();  
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });  
    const url = URL.createObjectURL(blob);  
    const a = document.createElement('a');  
    a.href = url;  
    a.download = 'laporan_transaksi_<?= date("Ymd_His") ?>.csv';  
    a.click();  
    URL.revokeObjectURL(url);  
  });  
  
  // Print  
  document.getElementById('btnPrint').addEventListener('click', () => window.print());  
  
  // Small client-side search (on transactions table)  
  const searchBox = document.createElement('input');  
  searchBox.placeholder = 'Cari invoice / buyer / produk...';  
  searchBox.className = 'w-full md:w-1/3 border rounded px-2 py-1';  
  const container = document.querySelector('section.bg-white.p-4.rounded-lg.shadow div.cari-invoice');  
  if (container) {  
    container.appendChild(searchBox);  
    searchBox.addEventListener('input', (e) => {  
      const q = e.target.value.toLowerCase().trim();  
      document.querySelectorAll('#transactionsTable tbody tr').forEach(tr => {  
        const text = tr.innerText.toLowerCase();  
        tr.style.display = text.indexOf(q) === -1 ? 'none' : '';  
      });  
    });  
  }  
</script>  
</body>  
</html>