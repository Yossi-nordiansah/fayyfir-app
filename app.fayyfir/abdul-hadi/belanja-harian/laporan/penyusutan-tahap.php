<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if(!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Ambil data penyusutan per batch
$sql = "SELECT kode_batch, penyusutan_jemur, penyusutan_kupas, penyusutan_total 
        FROM bb_v_penyusutan_tahap 
        ORDER BY kode_batch ASC";
$result = $conn->query($sql);

$labels = [];
$jemur = [];
$kupas = [];
$total = [];

while($row = $result->fetch_assoc()) {
    $labels[] = $row['kode_batch'];
    $jemur[] = floatval($row['penyusutan_jemur']);
    $kupas[] = floatval($row['penyusutan_kupas']);
    $total[] = floatval($row['penyusutan_total']);
}

// DETEKSI: apakah semua penyusutan kupas = 0?
$hideKupas = true;
foreach ($kupas as $val) {
    if ($val > 0) {
        $hideKupas = false;
        break;
    }
}

$activeMenu = "reports";
$activeModule = "Tahapan Penyusutan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-4-10h.01M4 6a2 2 0 012-2h12a2 2 0 012 2v14l-4-2H6a2 2 0 01-2-2V6z"/>
        </svg>
        Laporan Penyusutan per Batch
      </h1>
      <p class="text-sm text-gray-600 mt-1">Visualisasi penyusutan bahan di setiap tahap produksi</p>
    </div>

    <div class="mt-4 sm:mt-0 flex gap-3">
      <a href="export-pdf.php?type=penyusutan"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
          stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M6 2h9l5 5v15a1 1 0 01-1 1H6a1 1 0 01-1-1V3a1 1 0 011-1zm9 1.5V8h4.5M8 13h8M8 17h5" />
        </svg>
        <span class="font-medium text-sm">Export PDF</span>
      </a>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
      </svg>
      Grafik Penyusutan Tahap Produksi
    </h2>

    <div class="w-full overflow-x-auto">
      <canvas id="chartPenyusutan" class="min-w-[700px] max-h-[400px]"></canvas>
    </div>

    <p class="text-sm text-gray-500 mt-4">
      *Semakin tinggi nilai penyusutan, semakin besar kehilangan berat bahan pada tahap tersebut.
    </p>
  </div>
</main>

<!-- Load Chart.js dulu -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Baru load utilitas custom Anda -->
<script src="../assets/js/chart.js"></script>

<script>
const ctx = document.getElementById('chartPenyusutan').getContext('2d');

// Dataset dasar
const datasets = [
  {
    label: '  Proses-1 (%)',
    data: <?= json_encode($jemur) ?>,
    backgroundColor: 'rgba(59, 130, 246, 0.7)',
    borderRadius: 6,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 1)'
  },
  {
    label: '  Proses-3 (%)',
    data: <?= json_encode($total) ?>,
    backgroundColor: 'rgba(239, 68, 68, 0.7)',
    borderRadius: 6,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 1)'
  }
];

// Tambahkan dataset KUPAS hanya jika tidak semuanya nol
<?php if (!$hideKupas): ?>
datasets.splice(1, 0, {
  label: '  Proses-2 (%)',
  data: <?= json_encode($kupas) ?>,
  backgroundColor: 'rgba(234, 179, 8, 0.7)',
  borderRadius: 6,
  borderWidth: 1,
  borderColor: 'rgba(234, 179, 8, 1)'
});
<?php endif; ?>

const chartPenyusutan = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: datasets
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
        labels: { usePointStyle: true, boxWidth: 8 }
      },
      title: {
        display: true,
        text: 'Grafik Persentase Penyusutan Bahan per Batch',
        font: { size: 16, weight: 'bold' }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        max: 100,
        title: { display: true, text: 'Persentase (%)' },
        ticks: { color: '#4B5563' },
        grid: { color: '#E5E7EB' }
      },
      x: {
        title: { display: true, text: 'Kode Batch' },
        ticks: { color: '#4B5563' },
        grid: { display: false }
      }
    }
  }
});
</script>

<?php include "../partials/footer.php"; ?>