<?php
session_start();
require "../../config.php";
require "includes/helpers.php";
require "includes/functions.php";

if(!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

$conn = $conn2;

// Ambil ringkasan data
$total_bahan = $conn->query("SELECT COUNT(*) as total FROM bb_bahan_master")->fetch_assoc()['total'];
$total_supplier = $conn->query("SELECT COUNT(*) as total FROM bb_supplier")->fetch_assoc()['total'];
$total_batch = $conn->query("SELECT COUNT(*) as total FROM bb_pembelian_awal")->fetch_assoc()['total'];
$total_penjualan = $conn->query("SELECT IFNULL(SUM(total_penjualan),0) as total FROM bb_penjualan")->fetch_assoc()['total'];

// Statistik tambahan
$penjualan_terakhir = $conn->query("
    SELECT b.nama_buyer, p.total_penjualan, p.tanggal_jual 
    FROM bb_penjualan p 
    LEFT JOIN bb_buyer b ON b.id = p.id_buyer 
    ORDER BY p.tanggal_jual DESC LIMIT 5
");

// Ambil data grafik dari DB
$data_grafik = $conn->query("
    SELECT 
        no_invoice,
        IFNULL(SUM(total_penjualan), 0) AS total_penjualan
    FROM bb_penjualan
    GROUP BY id
    ORDER BY tanggal_jual ASC
");
$labels = [];
$data_penjualan = [];

while ($row = $data_grafik->fetch_assoc()) {
    $labels[] = $row['no_invoice'];
    $data_penjualan[] = (float)$row['total_penjualan'];
}
?>

<?php include "partials/header.php"; ?>
<?php include "partials/sidebar.php"; ?>
<?php include "partials/navbar.php"; ?>

<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
  <h2 class="text-2xl font-semibold mb-6 text-gray-800 flex items-center gap-2">
    <!-- Dashboard Icon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/>
    </svg>
    Dashboard Utama
  </h2>

  <!-- Shortcut Modul -->
  <div class="mb-8">
    <!-- <h3 class="text-xl font-semibold mb-4 text-gray-800">🚀 Shortcut Modul</h3> -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php
      $modules = [
        ["url"=>"pembelian-awal/","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3zm5 18h8a2 2 0 002-2H6a2 2 0 002 2z"/></svg>',"label"=>"Belanja Harian"],
        ["url"=>"proses-produksi/","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m-6 10h8a2 2 0 002-2V6a2 2 0 00-2-2h-3.172a2 2 0 01-1.414-.586l-.828-.828A2 2 0 009.172 2H6a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg>',"label"=>"Produksi"],
        ["url"=>"penjualan/","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11l-4-4m4 4l-4 4M21 19H3"/></svg>',"label"=>"Penjualan"],
        ["url"=>"laporan/ringkasan-modal-hpp","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm8 16V4"/></svg>',"label"=>"Laporan HPP"],
        ["url"=>"laporan/penyusutan-tahap","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>',"label"=>"Penyusutan"],
        ["url"=>"laporan/laba-rugi","icon"=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20V10M7 20V4m10 10H7"/></svg>',"label"=>"Laba Rugi"],
      ];
      foreach($modules as $mod): ?>
        <a href="<?= $mod['url'] ?>" class="flex items-center bg-white/80 backdrop-blur-md rounded-xl shadow-sm p-4 hover:shadow-lg transition border border-gray-100 hover:scale-[1.02]">
          <?= $mod['icon'] ?>
          <span class="ml-3 font-medium text-gray-800"><?= $mod['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Statistik Ringkas -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white/80 backdrop-blur-md shadow-md p-4 rounded-2xl text-center hover:shadow-lg transition">
      <!-- Bahan -->
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2h-3V3h-6v2H6a2 2 0 00-2 2v6h16zM4 17a2 2 0 002 2h12a2 2 0 002-2v-4H4v4z"/>
      </svg>
      <h4 class="text-sm text-gray-600">Total Jenis Bahan</h4>
      <p class="text-3xl font-bold text-gray-900"><?= $total_bahan ?></p>
    </div>

    <div class="bg-white/80 backdrop-blur-md shadow-md p-4 rounded-2xl text-center hover:shadow-lg transition">
      <!-- Supplier -->
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V8l-7-5-7 5v12h5v-6h4v6zM9 22h6"/>
      </svg>
      <h4 class="text-sm text-gray-600">Total Supplier</h4>
      <p class="text-3xl font-bold text-gray-900"><?= $total_supplier ?></p>
    </div>

    <div class="bg-white/80 backdrop-blur-md shadow-md p-4 rounded-2xl text-center hover:shadow-lg transition">
      <!-- Batch -->
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4v10l-9 4-9-4V7z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9 4 9-4" />
      </svg>
      <h4 class="text-sm text-gray-600">Total Batch</h4>
      <p class="text-3xl font-bold text-gray-900"><?= $total_batch ?></p>
    </div>

    <div class="bg-white/80 backdrop-blur-md shadow-md p-4 rounded-2xl text-center hover:shadow-lg transition">
      <!-- Penjualan -->
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h4 class="text-sm text-gray-600">Total Penjualan</h4>
      <p class="text-2xl font-bold text-green-700"><?= format_rupiah($total_penjualan) ?></p>
    </div>
  </div>

  <!-- Grafik dan Data Penjualan -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-md">
      <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
        <!-- Chart -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6v16h16M8 16V8m4 8V4m4 12v-6" />
        </svg>
        Grafik Penjualan
      </h3>
      <canvas id="salesChart" height="120"></canvas>
    </div>

    <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-md">
      <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
        <!-- History -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Riwayat Penjualan Terakhir
      </h3>
      <ul class="divide-y divide-gray-200">
        <?php while($row = $penjualan_terakhir->fetch_assoc()): ?>
          <li class="py-2 flex justify-between">
            <span><?= $row['nama_buyer'] ?></span>
            <span class="font-medium text-green-700"><?= format_rupiah($row['total_penjualan']) ?></span>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const chartLabels = <?= json_encode($labels) ?>;
const chartData = <?= json_encode($data_penjualan) ?>;
</script>

<script>
const ctx = document.getElementById('salesChart');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartLabels,
    datasets: [{
      label: 'Total Penjualan (Rp)',
      data: chartData,
      borderWidth: 1,
      backgroundColor: '#FBBF24'
    }]
  },
  options: {
    scales: {
      y: { 
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return 'Rp ' + value.toLocaleString('id-ID');
          }
        }
      }
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: function(ctx) {
            return 'Rp ' + ctx.raw.toLocaleString('id-ID');
          }
        }
      }
    }
  }
});
</script>

<?php include "partials/footer.php"; ?>