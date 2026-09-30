<?php
session_start();
require_once "config.php";
require_once "helpers-fifo-gaharu.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? intval($_GET['tahun']) : intval(date('Y'));
$data = getLabaRugiGaharuData($conn, $filter_tahun);

$semua_tahun           = $data['semua_tahun'];
$filtered_months       = $data['filtered_months'];
$pendapatan_per_bulan  = $data['pendapatan_per_bulan'];
$bpp_per_bulan         = $data['bpp_per_bulan'];
$laba_kotor_per_bulan  = $data['laba_kotor_per_bulan'];
$operasional_per_bulan = $data['operasional_per_bulan'];
$laba_bersih_per_bulan = $data['laba_bersih_per_bulan'];
$total_biaya_belum_terjual = (float)($data['total_biaya_belum_terjual'] ?? 0.0);

// Hitung total akumulasi tahunan untuk kartu KPI
$tot_pen_tahun   = 0;
$tot_bpp_tahun   = 0;
$tot_kotor_tahun = 0;
$tot_op_tahun    = 0;
$tot_bersih_tahun= 0;

foreach ($filtered_months as $m) {
  $tot_pen_tahun    += ($pendapatan_per_bulan[$m] ?? 0);
  $tot_bpp_tahun    += ($bpp_per_bulan[$m] ?? 0);
  $tot_kotor_tahun  += ($laba_kotor_per_bulan[$m] ?? 0);
  $tot_op_tahun     += ($operasional_per_bulan[$m] ?? 0);
  $tot_bersih_tahun += ($laba_bersih_per_bulan[$m] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laporan Laba Rugi Kayu Gaharu - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <!-- Header Bar -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center max-w-7xl mx-auto">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm font-medium">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <div class="text-center">
        <h1 class="text-lg font-bold tracking-wide">Laba Rugi Kayu Gaharu</h1>
        <p class="text-xs text-gray-400">Beban Pokok Penjualan Berbasis FIFO Produk Terjual</p>
      </div>
      <div class="w-16"></div> <!-- Spacer for symmetry -->
    </div>
  </header>

  <main class="pt-24 pb-32 px-4 max-w-7xl mx-auto space-y-6">

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
      <div class="bg-white rounded-lg shadow p-3 border-l-4 border-blue-500">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Penjualan (<?= $filter_tahun ?>)</p>
        <h3 class="text-base lg:text-lg font-bold text-gray-800 mt-1">Rp <?= number_format($tot_pen_tahun, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-0.5">Omzet Periode Ini</p>
      </div>

      <div class="bg-white rounded-lg shadow p-3 border-l-4 border-yellow-500">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">BPP FIFO (<?= $filter_tahun ?>)</p>
        <h3 class="text-base lg:text-lg font-bold text-gray-800 mt-1">Rp <?= number_format($tot_bpp_tahun, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-0.5">HPP Barang Terjual</p>
      </div>

      <div class="bg-white rounded-lg shadow p-3 border-l-4 border-indigo-500">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laba Kotor (Margin)</p>
        <h3 class="text-base lg:text-lg font-bold <?= $tot_kotor_tahun >= 0 ? 'text-indigo-600' : 'text-red-600' ?> mt-1">
          Rp <?= number_format($tot_kotor_tahun, 0, ',', '.') ?>
        </h3>
        <p class="text-xs text-gray-400 mt-0.5">Penjualan - BPP</p>
      </div>

      <div class="bg-white rounded-lg shadow p-3 border-l-4 border-red-500">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Beban Operasional</p>
        <h3 class="text-base lg:text-lg font-bold text-gray-800 mt-1">Rp <?= number_format($tot_op_tahun, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-0.5">Pengeluaran Bulanan</p>
      </div>

      <div class="bg-white rounded-lg shadow p-3 border-l-4 <?= $tot_bersih_tahun >= 0 ? 'border-green-500' : 'border-red-600' ?>">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laba Bersih</p>
        <h3 class="text-base lg:text-lg font-bold <?= $tot_bersih_tahun >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1">
          Rp <?= number_format($tot_bersih_tahun, 0, ',', '.') ?>
        </h3>
        <p class="text-xs text-gray-400 mt-0.5">Laba Kotor - Operasional</p>
      </div>

      <div class="bg-white rounded-lg shadow p-3 border-l-4 border-purple-500">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Biaya Belum Terjual</p>
        <h3 class="text-base lg:text-lg font-bold text-purple-700 mt-1">Rp <?= number_format($total_biaya_belum_terjual, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-0.5">Stok Sisa Gudang (Aset)</p>
      </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white shadow rounded-lg p-5">
      <!-- Toolbar Filter & Export -->
      <div class="flex flex-wrap justify-between items-center gap-3 mb-5 border-b pb-4">
        <div class="flex items-center gap-2">
          <button id="openModal" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white hover:text-gray-900 px-4 py-2 rounded text-sm font-medium transition shadow">
            <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-900 mr-2">picture_as_pdf</span>
            <span>Export PDF +</span>
          </button>
        </div>

        <!-- Filter Tahun -->
        <form method="get" class="flex items-center gap-2">
          <label class="text-sm font-semibold text-gray-700">Tahun:</label>
          <select name="tahun" onchange="this.form.submit()" class="border border-gray-300 rounded px-3 py-1.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-white">
            <?php foreach (array_keys($semua_tahun) as $thn): ?>
              <option value="<?= $thn ?>" <?= $thn == $filter_tahun ? 'selected' : '' ?>><?= $thn ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <!-- Tabel Laba Rugi Bulanan -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-100 text-gray-700 uppercase font-semibold text-xs tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left">Bulan</th>
              <th class="px-4 py-3 text-right">Pendapatan</th>
              <th class="px-4 py-3 text-right">Beban Pokok Penjualan (FIFO)</th>
              <th class="px-4 py-3 text-right">Laba Kotor</th>
              <th class="px-4 py-3 text-right">Beban Operasional</th>
              <th class="px-4 py-3 text-right">Laba Bersih</th>
              <th class="px-4 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-gray-800">
            <?php
            if (!empty($filtered_months)) {
              foreach ($filtered_months as $periode) {
                $pendapatan  = $pendapatan_per_bulan[$periode] ?? 0;
                $bpp         = $bpp_per_bulan[$periode] ?? 0;
                $laba_kotor  = $laba_kotor_per_bulan[$periode] ?? 0;
                $operasional = $operasional_per_bulan[$periode] ?? 0;
                $laba_bersih = $laba_bersih_per_bulan[$periode] ?? 0;

                $bulanTahun = DateTime::createFromFormat('Y-m', $periode)->format('F Y');
                $kotor_class = $laba_kotor >= 0 ? 'text-gray-800 font-semibold' : 'text-red-600 font-semibold';
                $bersih_class = $laba_bersih >= 0 ? 'text-green-700 font-bold' : 'text-red-600 font-bold';

                echo "<tr class='hover:bg-gray-50 transition'>
                  <td class='px-4 py-3 text-left font-medium text-gray-900 whitespace-nowrap'>
                    <div class='flex items-center space-x-2'>
                      <span class='material-symbols-outlined text-sm text-yellow-500'>calendar_month</span>
                      <span>{$bulanTahun}</span>
                    </div>
                  </td>
                  <td class='px-4 py-3 text-right whitespace-nowrap font-medium text-blue-700'>Rp " . number_format($pendapatan, 0, ',', '.') . "</td>
                  <td class='px-4 py-3 text-right whitespace-nowrap text-gray-700'>Rp " . number_format($bpp, 0, ',', '.') . "</td>
                  <td class='px-4 py-3 text-right whitespace-nowrap {$kotor_class}'>Rp " . number_format($laba_kotor, 0, ',', '.') . "</td>
                  <td class='px-4 py-3 text-right whitespace-nowrap text-red-600'>Rp " . number_format($operasional, 0, ',', '.') . "</td>
                  <td class='px-4 py-3 text-right whitespace-nowrap {$bersih_class}'>Rp " . number_format($laba_bersih, 0, ',', '.') . "</td>
                  <td class='px-4 py-3 text-center whitespace-nowrap'>
                    <div class='inline-flex items-center space-x-2'>
                      <a href='laporan-laba-rugi-gaharu-pdf.php?periode={$periode}' target='_blank' title='Cetak PDF' class='p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition'>
                        <span class='material-symbols-outlined text-base'>picture_as_pdf</span>
                      </a>
                      <a href='laporan-laba-rugi-gaharu-detail.php?periode={$periode}' 
                         class='px-2.5 py-1 bg-gray-100 hover:bg-yellow-400 hover:text-gray-900 text-blue-700 font-semibold rounded text-xs transition border border-gray-200'>
                        Detail
                      </a>
                    </div>
                  </td>
                </tr>";
              }
            } else {
              echo "<tr>
                <td colspan='7' class='text-center py-8 text-gray-500'>
                  <span class='material-symbols-outlined text-4xl text-gray-300 block mb-2'>event_busy</span>
                  Belum ada catatan transaksi penjualan maupun pengeluaran untuk tahun {$filter_tahun}.
                </td>
              </tr>";
            }
            ?>
          </tbody>
          <?php if (!empty($filtered_months)): ?>
          <tfoot class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-300">
            <tr>
              <td class="px-4 py-3 text-left">TOTAL TAHUN <?= $filter_tahun ?></td>
              <td class="px-4 py-3 text-right text-blue-800">Rp <?= number_format($tot_pen_tahun, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-right text-gray-800">Rp <?= number_format($tot_bpp_tahun, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-right <?= $tot_kotor_tahun >= 0 ? 'text-indigo-700' : 'text-red-600' ?>">Rp <?= number_format($tot_kotor_tahun, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-right text-red-600">Rp <?= number_format($tot_op_tahun, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-right <?= $tot_bersih_tahun >= 0 ? 'text-green-700' : 'text-red-600' ?>">Rp <?= number_format($tot_bersih_tahun, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-center">-</td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </main>

  <!-- Modal Export PDF + -->
  <div id="modalPDF" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md transform transition-all">
      <div class="flex items-center justify-between border-b pb-3 mb-4">
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
          <span class="material-symbols-outlined text-red-600">picture_as_pdf</span>
          Export Laporan PDF Kustom
        </h2>
        <button type="button" id="closeModalCross" class="text-gray-400 hover:text-gray-600 focus:outline-none">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form action="laporan-laba-rugi-gaharu-pdf-custom.php" method="get" target="_blank" class="space-y-4">
        <div>
          <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Dari Tanggal</label>
          <input type="date" name="start_date" id="start_date" required class="w-full border border-gray-300 px-3 py-2 rounded focus:ring-2 focus:ring-yellow-400 focus:outline-none text-sm" />
        </div>
        <div>
          <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Sampai Tanggal</label>
          <input type="date" name="end_date" id="end_date" required class="w-full border border-gray-300 px-3 py-2 rounded focus:ring-2 focus:ring-yellow-400 focus:outline-none text-sm" />
        </div>
        <p class="text-xs text-gray-500 italic bg-yellow-50 p-2.5 rounded border border-yellow-200">
          Laporan akan menghitung nilai penjualan dan alokasi BPP FIFO untuk transaksi yang berada dalam rentang tanggal yang dipilih.
        </p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="closeModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-semibold transition">Batal</button>
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded text-sm font-semibold transition shadow flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">download</span>
            Export PDF
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('modalPDF');
    const openModalBtn = document.getElementById('openModal');
    const closeModalBtn = document.getElementById('closeModal');
    const closeModalCross = document.getElementById('closeModalCross');

    openModalBtn.addEventListener('click', () => modal.classList.remove('hidden'));
    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
    if (closeModalCross) closeModalCross.addEventListener('click', () => modal.classList.add('hidden'));

    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.add('hidden');
    });
  </script>
</body>

</html>
