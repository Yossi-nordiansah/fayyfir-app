<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if(!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// 1. Filter Bahan
$id_bahan_filter = isset($_GET['id_bahan']) ? (int)$_GET['id_bahan'] : 0;

// 2. Ambil semua tahapan proses (Jika filter aktif, ambil hanya untuk bahan tersebut)
$stages_master = [];
$stage_query = "SELECT id, nama_proses, urutan_tahap FROM bb_proses_master";
if ($id_bahan_filter > 0) {
    $stage_query .= " WHERE id_bahan = $id_bahan_filter";
}
$stage_query .= " ORDER BY urutan_tahap ASC";

$res_stages = $conn->query($stage_query);
while($s = $res_stages->fetch_assoc()) {
    $stages_master[] = $s;
}

// 3. Pagination Logic
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total rows query based on Production Batches
$count_sql = "SELECT COUNT(DISTINCT COALESCE(pd.kode_produksi, CONCAT('S-', pd.id_pembelian))) as total 
              FROM bb_proses_detail pd
              JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id";
if ($id_bahan_filter > 0) {
    $count_sql .= " WHERE pa.id_bahan = $id_bahan_filter";
}
$count_res = $conn->query($count_sql);
$total_rows = $count_res ? $count_res->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $limit);

// 4. Ambil data batch produksi dengan rincian penyusutan
$sql = "SELECT 
            COALESCE(pd.kode_produksi, CONCAT('S-', pd.id_pembelian)) as batch_key,
            MAX(pd.kode_produksi) as kode_produksi,
            MIN(pd.id_pembelian) as id_pembelian_min,
            MAX(pa.kode_batch) as sample_kode_batch,
            MAX(pd.tanggal_proses) as tanggal_laporan,
            MAX(bm.nama_bahan) as nama_bahan,
            -- Berat awal batch (total berat_masuk pada tahap_ke = 0)
            (SELECT SUM(pd2.berat_masuk) 
             FROM bb_proses_detail pd2 
             WHERE COALESCE(pd2.kode_produksi, CONCAT('S-', pd2.id_pembelian)) = MAX(COALESCE(pd.kode_produksi, CONCAT('S-', pd.id_pembelian)))
             AND pd2.tahap_ke = 0 AND pd2.status = 'aktif') as berat_awal_batch
        FROM bb_proses_detail pd
        JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
        JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
        WHERE pd.status = 'aktif'";

if ($id_bahan_filter > 0) {
    $sql .= " AND pa.id_bahan = $id_bahan_filter";
}

$sql .= " GROUP BY batch_key ORDER BY MAX(pd.created_at) DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$batch_data = [];
$labels = [];
$total_pct = [];
$chart_stage_data = []; // [stage_id => [pct, pct, ...]]

// Inisialisasi array data per tahap
foreach($stages_master as $stage) {
    $chart_stage_data[$stage['id']] = [];
}

while($row = $result->fetch_assoc()) {
    $batch_key = $row['batch_key'];
    $kode_prd = $row['kode_produksi'];
    $id_p_min = (int)$row['id_pembelian_min'];
    
    $labels[] = ($kode_prd ?: $row['sample_kode_batch']) . " (" . ($row['nama_bahan'] ?? '-') . ")";
    $berat_awal = (float)$row['berat_awal_batch'];
    
    // Ambil log penyusutan per tahap (Kg) untuk batch ini
    $stage_shrinkage = [];
    foreach($stages_master as $stage) {
        $id_m = (int)$stage['id'];
        // Query disesuaikan untuk batch atau single
        if ($kode_prd) {
            $q_shrink = "SELECT SUM(penyusutan) as s FROM bb_proses_detail WHERE kode_produksi = '$kode_prd' AND id_proses_master = $id_m AND status = 'aktif'";
        } else {
            $q_shrink = "SELECT SUM(penyusutan) as s FROM bb_proses_detail WHERE id_pembelian = $id_p_min AND id_proses_master = $id_m AND status = 'aktif'";
        }
        $shrink_res = $conn->query($q_shrink);
        $s_row = $shrink_res->fetch_assoc();
        $shrink_kg = $s_row['s'] ? (float)$s_row['s'] : 0;
        $stage_shrinkage[$id_m] = $shrink_kg;
        
        // Simpan persentase untuk chart
        $chart_stage_data[$id_m][] = $berat_awal > 0 ? round(($shrink_kg / $berat_awal) * 100, 2) : 0;
    }
    
    // Total penyusutan (Kg) untuk batch ini
    if ($kode_prd) {
        $q_total = "SELECT SUM(penyusutan) as total FROM bb_proses_detail WHERE kode_produksi = '$kode_prd' AND status = 'aktif'";
    } else {
        $q_total = "SELECT SUM(penyusutan) as total FROM bb_proses_detail WHERE id_pembelian = $id_p_min AND status = 'aktif'";
    }
    $total_shrink_res = $conn->query($q_total);
    $total_shrink = $total_shrink_res ? (float)$total_shrink_res->fetch_assoc()['total'] : 0;
    
    // Total Persentase untuk Chart
    $total_pct[] = $berat_awal > 0 ? round(($total_shrink / $berat_awal) * 100, 2) : 0;

    $row['stages'] = $stage_shrinkage;
    $row['total_penyusutan'] = $total_shrink;
    $batch_data[] = $row;
}

// Ambil daftar semua bahan untuk filter dropdown (group agar tidak double)
$materials_all = $conn->query("SELECT MAX(id) as id, nama_bahan FROM bb_bahan_master WHERE deleted_at IS NULL GROUP BY nama_bahan ORDER BY nama_bahan ASC");

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

    <div class="mt-4 sm:mt-0 flex flex-wrap gap-3">
      <form method="GET" class="flex items-center gap-2 bg-white p-1 rounded-xl shadow-sm border border-gray-200">
        <select name="id_bahan" onchange="this.form.submit()" class="bg-transparent border-none focus:ring-0 text-sm text-gray-700 px-3 py-1.5 min-w-[150px]">
          <option value="0">-- Semua Bahan --</option>
          <?php while($m = $materials_all->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>" <?= $id_bahan_filter == $m['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['nama_bahan']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
      </svg>
      Grafik Penyusutan <?= $id_bahan_filter > 0 ? 'per Tahap' : 'Total' ?>
    </h2>

    <div class="w-full overflow-x-auto">
      <canvas id="chartPenyusutan" class="min-w-[700px] max-h-[400px]"></canvas>
    </div>

    <p class="text-sm text-gray-500 mt-4 italic">
      <?php if ($id_bahan_filter > 0): ?>
        *Menampilkan rincian penyusutan per tahap untuk bahan yang dipilih.
      <?php else: ?>
        *Menampilkan total persentase penyusutan untuk semua bahan. Pilih bahan untuk melihat rincian per tahap.
      <?php endif; ?>
    </p>
  </div>

  <!-- Tabel Data Penyusutan -->
  <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
      <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        Data Rincian Penyusutan per Batch
      </h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider">No</th>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider">Tanggal</th>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider">Kode Batch</th>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider">Jenis Bahan</th>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider text-center bg-gray-900">Total Susut (Kg)</th>
            <th class="px-6 py-4 font-semibold uppercase tracking-wider text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (count($batch_data) > 0): ?>
            <?php $no = $offset + 1; foreach ($batch_data as $row): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-gray-700"><?= $no++ ?></td>
                <td class="px-6 py-4 text-gray-600"><?= date('d/m/Y', strtotime($row['tanggal_laporan'])) ?></td>
                <td class="px-6 py-4 font-medium text-gray-900">
                    <span class="text-xs text-gray-400 block"><?= $row['kode_produksi'] ? 'Prod Batch' : 'Purchase' ?></span>
                    <?= htmlspecialchars($row['kode_produksi'] ?: $row['sample_kode_batch']) ?>
                </td>
                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($row['nama_bahan'] ?? '-') ?></td>
                <td class="px-6 py-4 text-center text-red-600 font-bold bg-gray-50">
                    <?= number_format($row['total_penyusutan'], 2, ',', '.') ?>
                </td>
                <td class="px-6 py-4 text-center">
                  <?php 
                    $detail_url = "/app.fayyfir/abdul-hadi/belanja-harian/laporan/detail-penyusutan.php?";
                    if ($row['kode_produksi']) {
                        $detail_url .= "kode_produksi=" . urlencode($row['kode_produksi']);
                    } else {
                        $detail_url .= "id=" . $row['id_pembelian_min'];
                    }
                  ?>
                  <a href="<?= $detail_url ?>"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Cek Detail
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">
                Belum ada data penyusutan yang tersedia.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
      <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Menampilkan <span class="font-medium"><?= count($batch_data) ?></span> dari <span class="font-medium"><?= $total_rows ?></span> batch
        </p>
        <div class="flex gap-2">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Previous</a>
          <?php endif; ?>
          
          <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm font-medium <?= $i == $page ? 'bg-emerald-600 text-white border-emerald-600' : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50' ?> border rounded-lg transition"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Next</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<!-- Load Chart.js dulu -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Baru load utilitas custom Anda -->
<script src="../assets/js/chart.js"></script>

<script>
const ctx = document.getElementById('chartPenyusutan').getContext('2d');

// Dataset dasar
const colors = [
  'rgba(59, 130, 246, 0.7)',   // Blue
  'rgba(245, 158, 11, 0.7)',   // Orange
  'rgba(16, 185, 129, 0.7)',   // Emerald
  'rgba(139, 92, 246, 0.7)',   // Violet
  'rgba(236, 72, 153, 0.7)',   // Pink
  'rgba(20, 184, 166, 0.7)',   // Teal
  'rgba(107, 114, 128, 0.7)'   // Gray
];

const borderColors = [
  'rgba(59, 130, 246, 1)',
  'rgba(245, 158, 11, 1)',
  'rgba(16, 185, 129, 1)',
  'rgba(139, 92, 246, 1)',
  'rgba(236, 72, 153, 1)',
  'rgba(20, 184, 166, 1)',
  'rgba(107, 114, 128, 1)'
];

const datasets = [
  <?php if ($id_bahan_filter > 0): ?>
    <?php foreach($stages_master as $idx => $stage): ?>
    {
      label: '  <?= addslashes($stage['nama_proses']) ?> (%)',
      data: <?= json_encode($chart_stage_data[$stage['id']]) ?>,
      backgroundColor: colors[<?= $idx ?> % colors.length],
      borderRadius: 4,
      borderWidth: 1,
      borderColor: borderColors[<?= $idx ?> % borderColors.length]
    },
    <?php endforeach; ?>
  <?php endif; ?>
  {
    label: '  Total Penyusutan (%)',
    data: <?= json_encode($total_pct) ?>,
    backgroundColor: 'rgba(239, 68, 68, 0.2)',
    borderRadius: 6,
    borderWidth: 2,
    borderColor: 'rgba(239, 68, 68, 1)',
    type: '<?= $id_bahan_filter > 0 ? "line" : "bar" ?>', // Bar if global, Line if filtered
    tension: 0.3,
    fill: <?= $id_bahan_filter > 0 ? "false" : "true" ?>
  }
];

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
        text: 'Grafik Persentase Penyusutan <?= $id_bahan_filter > 0 ? "per Tahap" : "Total" ?>',
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
        title: { display: true, text: 'Batch (Bahan)' },
        ticks: { 
          color: '#4B5563',
          maxRotation: 45,
          minRotation: 45
        },
        grid: { display: false }
      }
    }
  }
});
</script>

<?php include "../partials/footer.php"; ?>