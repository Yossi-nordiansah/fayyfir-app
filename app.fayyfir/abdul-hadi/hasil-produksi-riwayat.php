<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// --- Simpan form modal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['production_id'])) {
  $production_id = (int) $_POST['production_id'];
  $price_weight = (int) str_replace('.', '', $_POST['price_weight']); // Harga/Kg
  $fix_price    = (int) str_replace('.', '', $_POST['fix_price']);   // Harga Fix

  $stmt = $conn->prepare("UPDATE productions 
                          SET price_weight = ?, fix_price = ?, status = 'Terhitung' 
                          WHERE id = ?");
  $stmt->bind_param("iii", $price_weight, $fix_price, $production_id);
  $stmt->execute();
  $stmt->close();

  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

// --- Ambil data produksi berdasarkan product_name ---
$product_name = $_GET['product_name'] ?? null;
if (!$product_name) {
  header("Location: hasil-produksi");
  exit();
}

$stmt = $conn->prepare("
  SELECT p.*, ps.product_name, u.name as unit_name, u.symbol as unit_symbol
  FROM productions p
  INNER JOIN product_stocks ps ON p.product_id = ps.id
  LEFT JOIN units u ON p.unit_id = u.id
  WHERE ps.product_name = ?
  ORDER BY p.production_date DESC
");
$stmt->bind_param("s", $product_name);
$stmt->execute();
$result = $stmt->get_result();
$productions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ringkasan
$total_output = $total_weight = $total_fix_price = $total_expenses = $total_materials = 0;
foreach ($productions as $row) {
  $total_output += $row['total_output'] ?? 0;
  $total_weight += $row['total_weight'] ?? 0;
  $total_fix_price += $row['fix_price'] ?? 0;
  $total_expenses += $row['total_pro_expenses'] ?? 0;
  $total_materials += $row['total_pro_materials'] ?? 0;
}
$total_production = $total_expenses + $total_materials;
$total_margin = $total_fix_price - $total_production;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Hasil Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40 shadow">
  <div class="flex justify-between items-center">
    <a href="hasil-produksi" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Riwayats: <?= htmlspecialchars($product_name) ?></h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-7xl mx-auto space-y-10">

  <!-- Ringkasan -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white shadow rounded-xl p-4">
      <p class="text-gray-500 text-sm">Total Berat</p>
      <p class="text-lg font-bold"><?= number_format($total_weight, 0, ',', '.') ?></p>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <p class="text-gray-500 text-sm">Total Hasil</p>
      <p class="text-lg font-bold"><?= number_format($total_output, 0, ',', '.') ?></p>
    </div>
    <a href="total-biaya-produksi-rincian.php?product_name=<?= urlencode($product_name) ?>" class="bg-white shadow rounded-xl p-4">
      <p class="text-gray-500 text-sm">Total Biaya Produksi</p>
      <p class="text-lg font-bold">
        Rp <?= number_format($total_production, 0, ',', '.') ?>
      </p>
    </a>
    <div class="bg-white shadow rounded-xl p-4">
      <p class="text-gray-500 text-sm">Total Margin</p>
      <p class="text-lg font-bold">Rp <?= number_format($total_margin, 0, ',', '.') ?></p>
    </div>
  </section>

  <!-- Grafik -->
  <section class="bg-white shadow rounded-xl p-6">
    <h2 class="text-lg font-semibold mb-4">Tren Output & Biaya</h2>
    <canvas id="productionChart" height="120"></canvas>
  </section>

  <!-- Tabel -->
  <section class="bg-white shadow rounded-xl p-6 overflow-x-auto">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Riwayat Produksi</h2>
      <input type="text" id="searchInput" placeholder="Cari nomor produksi..." class="border rounded px-3 py-1 text-sm" />
    </div>
    <table class="table-auto w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2 text-left">Tanggal</th>
          <th class="px-3 py-2">No Produksi</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2">Mentah (gram)</th>
          <th class="px-3 py-2">Hasil (gram)</th>
          <th class="px-3 py-2">Total Biaya</th>
          <th class="px-3 py-2">Harga Jual/Kg</th>
          <th class="px-3 py-2">Harga Jual</th>
          <th class="px-3 py-2">Aksi</th>
        </tr>
      </thead>
      <tbody id="productionTable">
        <?php foreach ($productions as $row): ?>
          <?php 
            $total_biaya = ($row['total_pro_expenses'] ?? 0) + ($row['total_pro_materials'] ?? 0); 
          ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-3 py-2"><?= htmlspecialchars($row['production_date']) ?></td>
            <td class="px-3 py-2 font-mono"><?= htmlspecialchars($row['production_number']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($row['status']) ?></td>
            <td class="px-3 py-2 text-right"><?= number_format($row['total_weight'], 0, ',', '.') ?></td>
            <td class="px-3 py-2 text-right"><?= number_format($row['total_output'], 0, ',', '.') ?></td>
            <td class="px-3 py-2 text-right">Rp <?= number_format($total_biaya, 0, ',', '.') ?></td>
            <td class="px-3 py-2 text-right">Rp <?= number_format($row['price_weight'] ?? 0, 0, ',', '.') ?></td>
            <td class="px-3 py-2 text-right">Rp <?= number_format($row['fix_price'] ?? 0, 0, ',', '.') ?></td>
            <td class="px-3 py-2 text-center whitespace-nowrap gap-4">
              <button 
                class="text-blue-500"
                onclick="openModal(<?= $row['id'] ?>, <?= $row['total_output'] ?>, <?= $row['price_weight'] ?? 0 ?>, <?= $row['fix_price'] ?? 0 ?>)">
                <span class="material-symbols-outlined">payments</span>
              </button>
              <a href="produksi-proses?id=<?= $row['id'] ?>&name=<?= urlencode($row['product_name']) ?>" 
                class="text-blue-500">
                <span class="material-symbols-outlined">visibility</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-xl shadow-lg w-96 p-6 relative">
    <h2 class="text-lg font-semibold mb-4">Hitung Harga Produksi</h2>
    <form method="post" class="space-y-4">
      <input type="hidden" name="production_id" id="production_id">
      <div>
        <label class="block text-sm mb-1">Harga/Kg</label>
        <input type="text" name="price_weight" id="price_weight" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm mb-1">Total Harga Jual</label>
        <input type="text" name="fix_price" id="fix_price" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
      </div>
      <div class="flex justify-end space-x-2 pt-4">
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded">Simpan</button>
      </div>
    </form>
  </div>
</div>

</main>

<script>
// Pencarian di tabel
document.getElementById("searchInput").addEventListener("keyup", function() {
  const filter = this.value.toLowerCase();
  document.querySelectorAll("#productionTable tr").forEach(tr => {
    const text = tr.innerText.toLowerCase();
    tr.style.display = text.includes(filter) ? "" : "none";
  });
});

// Grafik tren produksi
const ctx = document.getElementById('productionChart').getContext('2d');
const chartData = {
  labels: <?= json_encode(array_column($productions, 'production_date')) ?>,
  datasets: [
    {
      label: 'Output',
      data: <?= json_encode(array_column($productions, 'total_output')) ?>,
      borderColor: 'blue',
      backgroundColor: 'rgba(59, 130, 246, 0.2)',
      fill: true,
      yAxisID: 'y',
    },
    {
      label: 'Biaya Produksi',
      data: <?= json_encode(array_map(fn($r) => ($r['total_pro_expenses'] ?? 0) + ($r['total_pro_materials'] ?? 0), $productions)) ?>,
      borderColor: 'red',
      backgroundColor: 'rgba(239, 68, 68, 0.2)',
      fill: true,
      yAxisID: 'y1',
    }
  ]
};
new Chart(ctx, {
  type: 'line',
  data: chartData,
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    stacked: false,
    scales: {
      y: { type: 'linear', position: 'left' },
      y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
    }
  }
});

// PopUp Modal
let modal = document.getElementById('modal');
let priceWeightInput = document.getElementById('price_weight');
let fixPriceInput = document.getElementById('fix_price');
let productionIdInput = document.getElementById('production_id');
let currentOutput = 0;

function openModal(id, totalOutput, priceWeight, fixPrice) {
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  productionIdInput.value = id;
  currentOutput = totalOutput;

  priceWeightInput.value = formatNumber(priceWeight);
  fixPriceInput.value = formatNumber(fixPrice || (priceWeight * totalOutput / 1000));
}

function closeModal() {
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

// Format ribuan
function formatNumber(num) {
  return num ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "0";
}
function unformatNumber(numStr) {
  return parseInt(numStr.replace(/\./g, "")) || 0;
}

// Hitung otomatis fix price
priceWeightInput.addEventListener('input', function() {
  let pw = unformatNumber(this.value);
  this.value = formatNumber(pw);
  fixPriceInput.value = formatNumber(pw * currentOutput / 1000);
});
</script>

</body>
</html>