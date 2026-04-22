<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Inisialisasi array
$pendapatan_per_bulan = [];
$bpp_per_bulan = [];
$laba_kotor_per_bulan = [];
$operasional_per_bulan = [];
$laba_bersih_per_bulan = [];

// Ambil seluruh container yang statusnya lunas
$query_containers = $conn->query("
  SELECT id, selling_price, lunas_at
  FROM containers
  WHERE status = 'lunas'
");

while ($container = $query_containers->fetch_assoc()) {
  $container_id = $container['id'];
  $selling_price = $container['selling_price'];
  $created_at_container = $container['lunas_at'];

  $created_date = new DateTime($created_at_container);
  $periode_key = $created_date->format('Y-m'); // YYYY-MM

  // Pendapatan
  $q_trans = $conn->query("
    SELECT SUM(t.weight_kg) AS total_weight
    FROM transactions t
    JOIN containers c ON t.container_id = c.id
    WHERE t.container_id = $container_id
      AND c.status = 'lunas'
  ");
  $trans = $q_trans->fetch_assoc();
  $total_weight = $trans['total_weight'] ?? 0;

  $pendapatan_container = $total_weight * $selling_price;
  $pendapatan_per_bulan[$periode_key] = ($pendapatan_per_bulan[$periode_key] ?? 0) + $pendapatan_container;

  // BPP
  $q_total_price = $conn->query("
    SELECT SUM(t.grand_total) AS total_price
    FROM transactions t
    JOIN containers c ON t.container_id = c.id
    WHERE t.container_id = $container_id
      AND c.status = 'lunas'
  ");
  $trx_price = $q_total_price->fetch_assoc();
  $total_price_trans = $trx_price['total_price'] ?? 0;

  $q_exp = $conn->query("
    SELECT SUM(e.amount) AS total_expense
    FROM expenses e
    JOIN containers c ON e.container_id = c.id
    WHERE e.container_id = $container_id
      AND c.status = 'lunas'
  ");
  $exp = $q_exp->fetch_assoc();
  $total_expenses = $exp['total_expense'] ?? 0;

  $bpp_container = $total_price_trans + $total_expenses;
  $bpp_per_bulan[$periode_key] = ($bpp_per_bulan[$periode_key] ?? 0) + $bpp_container;
}

// Laba kotor
foreach ($pendapatan_per_bulan as $periode_key => $pendapatan) {
  $bpp = $bpp_per_bulan[$periode_key] ?? 0;
  $laba_kotor_per_bulan[$periode_key] = $pendapatan - $bpp;
}

// Beban operasional
$query_op = $conn->query("SELECT jumlah, created_at FROM operational_costs");
while ($op = $query_op->fetch_assoc()) {
  $jumlah = $op['jumlah'];
  $created_date = new DateTime($op['created_at']);
  $periode_key = $created_date->format('Y-m');

  $operasional_per_bulan[$periode_key] = ($operasional_per_bulan[$periode_key] ?? 0) + $jumlah;
}

// Ambil filter tahun dari GET, default ke tahun ini
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? intval($_GET['tahun']) : intval(date('Y'));

// Kumpulkan semua tahun yang ada dari data
$semua_tahun = [];
foreach (array_keys($pendapatan_per_bulan) as $k) {
  $semua_tahun[substr($k, 0, 4)] = true;
}
foreach (array_keys($operasional_per_bulan) as $k) {
  $semua_tahun[substr($k, 0, 4)] = true;
}
krsort($semua_tahun); // urutkan tahun DESC

// Filter data berdasarkan tahun yang dipilih
$filtered_pendapatan = [];
foreach ($pendapatan_per_bulan as $k => $v) {
  if (substr($k, 0, 4) == $filter_tahun) $filtered_pendapatan[$k] = $v;
}

// Urutkan bulan secara DESC (terbaru ke terlama)
krsort($filtered_pendapatan);

// Rebuild filtered arrays based on filtered_pendapatan
$filtered_bpp = [];
$filtered_laba_kotor = [];
$filtered_operasional = [];
$filtered_laba_bersih = [];
foreach ($filtered_pendapatan as $k => $v) {
  $filtered_bpp[$k]        = $bpp_per_bulan[$k] ?? 0;
  $filtered_laba_kotor[$k] = $laba_kotor_per_bulan[$k] ?? 0;
  $filtered_operasional[$k]= $operasional_per_bulan[$k] ?? 0;
  $filtered_laba_bersih[$k]= $laba_bersih_per_bulan[$k] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporan Laba Rugi - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Laba Rugis</h1>
    </div>
  </header>

  <main class="pt-24 pb-32 px-4 max-w-6xl mx-auto space-y-6">
    <div class="overflow-auto bg-white shadow rounded-lg p-4">
      <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
        <div class="flex gap-2">
          <button id="openModal" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
            <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">picture_as_pdf</span>
            <span class="ml-2">Export PDF +</span>
          </button>
        </div>
        <!-- Filter Tahun -->
        <form method="get" class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-600">Tahun:</label>
          <select name="tahun" onchange="this.form.submit()" class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-yellow-300">
            <?php foreach (array_keys($semua_tahun) as $thn): ?>
              <option value="<?= $thn ?>" <?= $thn == $filter_tahun ? 'selected' : '' ?>><?= $thn ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-center">Bulan</th>
            <th class="px-4 py-2 text-center">Pendapatan</th>
            <th class="px-4 py-2 text-center">Beban Pokok Penjualan</th>
            <th class="px-4 py-2 text-center">Laba Kotor</th>
            <th class="px-4 py-2 text-center">Beban Operasional</th>
            <th class="px-4 py-2 text-center">Laba Bersih</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-gray-800">
          <?php
          if (!empty($filtered_pendapatan)) {
            foreach ($filtered_pendapatan as $periode => $pendapatan) {
              $bpp         = $filtered_bpp[$periode] ?? 0;
              $laba_kotor  = $filtered_laba_kotor[$periode] ?? 0;
              $operasional = $filtered_operasional[$periode] ?? 0;
              $laba_bersih = $filtered_laba_bersih[$periode] ?? 0;

              $bulanTahun = DateTime::createFromFormat('Y-m', $periode)->format('F Y');

              echo "<tr>
                <td class='px-4 py-2 text-left whitespace-nowrap'>{$bulanTahun}</td>
                <td class='px-4 py-2 text-right'>" . number_format($pendapatan, 0, ',', '.') . "</td>
                <td class='px-4 py-2 text-right'>" . number_format($bpp, 0, ',', '.') . "</td>
                <td class='px-4 py-2 text-right'>" . number_format($laba_kotor, 0, ',', '.') . "</td>
                <td class='px-4 py-2 text-right'>" . number_format($operasional, 0, ',', '.') . "</td>
                <td class='px-4 py-2 text-right'>" . number_format($laba_bersih, 0, ',', '.') . "</td>
                <td class='px-4 py-2 text-center'>
                  <a href='laporan-laba-rugi-pdf.php?periode={$periode}' target='_blank' class='text-red-600 hover:text-red-800'>
                    <span class='material-symbols-outlined text-base'>picture_as_pdf</span>
                  </a>
                  <a href='laporan-laba-rugi-detail.php?periode={$periode}' 
                     class='text-blue-600 hover:underline text-sm'>
                    Detail
                  </a>
                </td>
              </tr>";
            }
          } else {
            echo "<tr>
              <td colspan='7' class='text-center py-4'>Belum ada data untuk tahun {$filter_tahun}</td>
            </tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Modal Export PDF -->
  <div id="modalPDF" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
      <h2 class="text-lg font-semibold mb-4">Export Laporan PDF +</h2>
      <form action="laporan-laba-rugi-pdf-custom.php" method="get" target="_blank" class="space-y-4">
        <div>
          <label for="start_date" class="block text-sm font-medium">Dari Tanggal</label>
          <input type="date" name="start_date" id="start_date" required class="w-full border px-3 py-2 rounded"/>
        </div>
        <div>
          <label for="end_date" class="block text-sm font-medium">Sampai Tanggal</label>
          <input type="date" name="end_date" id="end_date" required class="w-full border px-3 py-2 rounded"/>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <button type="button" id="closeModal" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">Batal</button>
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">Export</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('modalPDF');
    const openModalBtn = document.getElementById('openModal');
    const closeModalBtn = document.getElementById('closeModal');

    openModalBtn.addEventListener('click', () => modal.classList.remove('hidden'));
    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
  </script>
</body>
</html>