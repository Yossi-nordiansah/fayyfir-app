<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Inisialisasi array
$pendapatan_per_bulan   = [];
$bpp_per_bulan          = [];
$laba_kotor_per_bulan   = [];
$operasional_per_bulan  = [];
$laba_bersih_per_bulan  = [];

// Ambil seluruh container lunas dan punya lunas_at
$query_containers = $conn->query("
  SELECT id, selling_price, lunas_at
  FROM containers
  WHERE status = 'lunas'
    AND lunas_at IS NOT NULL
");

while ($container = $query_containers->fetch_assoc()) {
  $container_id    = $container['id'];
  $selling_price   = $container['selling_price'];
  $lunas_at        = $container['lunas_at'];

  // Periode berdasarkan lunas_at
  $lunas_date  = new DateTime($lunas_at);
  $periode_key = $lunas_date->format('Y-m');

  // Pendapatan (hanya transaksi dengan lunas_at IS NOT NULL)
  $q_trans = $conn->query("
    SELECT SUM(t.weight_kg) AS total_weight
    FROM transactions t
    WHERE t.container_id = $container_id
      AND t.lunas_at IS NOT NULL
  ");
  $trans = $q_trans->fetch_assoc();
  $total_weight = $trans['total_weight'] ?? 0;

  $pendapatan_container = $total_weight * $selling_price;
  $pendapatan_per_bulan[$periode_key] = ($pendapatan_per_bulan[$periode_key] ?? 0) + $pendapatan_container;

  // BPP: pembelian sawit (hanya transaksi dengan lunas_at IS NOT NULL)
  $q_total_price = $conn->query("
    SELECT SUM(t.grand_total) AS total_price
    FROM transactions t
    WHERE t.container_id = $container_id
      AND t.lunas_at IS NOT NULL
  ");
  $trx_price = $q_total_price->fetch_assoc();
  $total_price_trans = $trx_price['total_price'] ?? 0;

  // Biaya tambahan (expenses dengan lunas_at IS NOT NULL)
  $q_exp = $conn->query("
    SELECT SUM(e.amount) AS total_expense
    FROM expenses e
    WHERE e.container_id = $container_id
      AND e.lunas_at IS NOT NULL
  ");
  $exp = $q_exp->fetch_assoc();
  $total_expenses = $exp['total_expense'] ?? 0;

  // Total BPP
  $bpp_container = $total_price_trans + $total_expenses;
  $bpp_per_bulan[$periode_key] = ($bpp_per_bulan[$periode_key] ?? 0) + $bpp_container;
}

// Laba kotor
foreach ($pendapatan_per_bulan as $periode_key => $pendapatan) {
  $bpp = $bpp_per_bulan[$periode_key] ?? 0;
  $laba_kotor_per_bulan[$periode_key] = $pendapatan - $bpp;
}

// Beban operasional (tidak terkait kontainer, tetap pakai created_at)
$query_op = $conn->query("
  SELECT jumlah, created_at 
  FROM operational_costs
");
while ($op = $query_op->fetch_assoc()) {
  $jumlah       = $op['jumlah'];
  $created_date = new DateTime($op['created_at']);
  $periode_key  = $created_date->format('Y-m');

  $operasional_per_bulan[$periode_key] = ($operasional_per_bulan[$periode_key] ?? 0) + $jumlah;
}

// Laba bersih
foreach ($laba_kotor_per_bulan as $periode_key => $laba_kotor) {
  $operasional = $operasional_per_bulan[$periode_key] ?? 0;
  $laba_bersih_per_bulan[$periode_key] = $laba_kotor - $operasional;
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
      <h1 class="text-lg font-semibold">Laba Rugi</h1>
    </div>
  </header>

  <main class="pt-24 pb-32 px-4 max-w-6xl mx-auto space-y-6">
    <div class="overflow-auto bg-white shadow rounded-lg p-4">
      <div class="flex justify-start gap-2 mb-4">
        <button id="openModal" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">picture_as_pdf</span>
          <span class="ml-2">Export PDF +</span>
        </button>
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
          if (!empty($pendapatan_per_bulan)) {
            foreach ($pendapatan_per_bulan as $periode => $pendapatan) {
              $bpp = $bpp_per_bulan[$periode] ?? 0;
              $laba_kotor = $laba_kotor_per_bulan[$periode] ?? 0;
              $operasional = $operasional_per_bulan[$periode] ?? 0;
              $laba_bersih = $laba_bersih_per_bulan[$periode] ?? 0;

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
                </td>
              </tr>";
            }
          } else {
            echo "<tr>
              <td colspan='7' class='text-center py-4'>Belum ada data</td>
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