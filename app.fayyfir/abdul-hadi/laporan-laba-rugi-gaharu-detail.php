<?php
session_start();
require_once "config.php";
require_once "helpers-fifo-gaharu.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$periode = $_GET['periode'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
  die("Format periode tidak valid.");
}

$tahun = substr($periode, 0, 4);
$bulan_dt = DateTime::createFromFormat('Y-m', $periode);
$periode_label = $bulan_dt ? $bulan_dt->format('F Y') : $periode;

$data = getLabaRugiGaharuData($conn, $tahun);

$pendapatan        = $data['pendapatan_per_bulan'][$periode] ?? 0.0;
$bpp               = $data['bpp_per_bulan'][$periode] ?? 0.0;
$laba_kotor        = $data['laba_kotor_per_bulan'][$periode] ?? 0.0;
$operasional       = $data['operasional_per_bulan'][$periode] ?? 0.0;
$laba_bersih       = $data['laba_bersih_per_bulan'][$periode] ?? 0.0;

$sales             = $data['sales_by_month'][$periode] ?? [];
$batches_used      = $data['batches_used_by_month'][$periode] ?? [];
$operasional_items = $data['operasional_items_by_month'][$periode] ?? [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detail Laba Rugi Kayu Gaharu — <?= htmlspecialchars($periode_label) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <!-- Header Bar -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center max-w-7xl mx-auto">
      <a href="laporan-laba-rugi-gaharu.php?tahun=<?= $tahun ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm font-medium">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span>Kembali ke Rekap</span>
      </a>
      <div class="text-center">
        <h1 class="text-lg font-bold">Detail Laba Rugi — <?= htmlspecialchars($periode_label) ?></h1>
        <p class="text-xs text-gray-400">Unit Usaha Kayu Gaharu</p>
      </div>
      <a href="laporan-laba-rugi-gaharu-pdf.php?periode=<?= $periode ?>" target="_blank" class="flex items-center space-x-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-semibold shadow transition">
        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
        <span class="hidden sm:inline">Cetak PDF</span>
      </a>
    </div>
  </header>

  <main class="pt-24 pb-32 px-4 max-w-7xl mx-auto space-y-8">

    <!-- Ringkasan Periode Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <span class="text-xs font-bold text-gray-500 uppercase">1. Pendapatan</span>
        <h3 class="text-lg font-bold text-blue-700 mt-1">Rp <?= number_format($pendapatan, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-1"><?= count($sales) ?> Transaksi Penjualan</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <span class="text-xs font-bold text-gray-500 uppercase">2. BPP (FIFO)</span>
        <h3 class="text-lg font-bold text-gray-800 mt-1">Rp <?= number_format($bpp, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-1">HPP Barang Terjual</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
        <span class="text-xs font-bold text-gray-500 uppercase">3. Laba Kotor</span>
        <h3 class="text-lg font-bold <?= $laba_kotor >= 0 ? 'text-indigo-600' : 'text-red-600' ?> mt-1">
          Rp <?= number_format($laba_kotor, 0, ',', '.') ?>
        </h3>
        <p class="text-xs text-gray-400 mt-1">Margin: <?= $pendapatan > 0 ? number_format(($laba_kotor / $pendapatan) * 100, 1) : 0 ?>%</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <span class="text-xs font-bold text-gray-500 uppercase">4. Biaya Operasional</span>
        <h3 class="text-lg font-bold text-red-600 mt-1">Rp <?= number_format($operasional, 0, ',', '.') ?></h3>
        <p class="text-xs text-gray-400 mt-1"><?= count($operasional_items) ?> Pos Pengeluaran</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 <?= $laba_bersih >= 0 ? 'border-green-500' : 'border-red-600' ?>">
        <span class="text-xs font-bold text-gray-500 uppercase">5. Laba Bersih</span>
        <h3 class="text-lg font-bold <?= $laba_bersih >= 0 ? 'text-green-700' : 'text-red-600' ?> mt-1">
          Rp <?= number_format($laba_bersih, 0, ',', '.') ?>
        </h3>
        <p class="text-xs text-gray-400 mt-1">Laba Kotor - Operasional</p>
      </div>
    </div>

    <!-- Bagian 1: Rincian Transaksi Penjualan & Alokasi BPP FIFO -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="bg-gray-800 text-white px-5 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <span class="material-symbols-outlined text-yellow-400">point_of_sale</span>
          <h2 class="font-bold text-sm tracking-wide">A. Rincian Penjualan & Alokasi BPP (FIFO) per Transaksi</h2>
        </div>
        <span class="text-xs text-gray-300"><?= count($sales) ?> Data</span>
      </div>

      <div class="overflow-x-auto p-4">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
          <thead class="bg-gray-100 text-gray-700 uppercase font-semibold">
            <tr>
              <th class="px-3 py-2 text-left">Tanggal</th>
              <th class="px-3 py-2 text-left">Invoice</th>
              <th class="px-3 py-2 text-left">Buyer</th>
              <th class="px-3 py-2 text-left">Produk</th>
              <th class="px-3 py-2 text-right">Qty</th>
              <th class="px-3 py-2 text-right">Harga Jual</th>
              <th class="px-3 py-2 text-right">Total Penjualan</th>
              <th class="px-3 py-2 text-right">BPP FIFO</th>
              <th class="px-3 py-2 text-right">Laba Kotor</th>
              <th class="px-3 py-2 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-gray-800">
            <?php
            if (!empty($sales)) {
              foreach ($sales as $s) {
                $tgl_fmt = !empty($s['selling_date']) ? date('d/m/Y', strtotime($s['selling_date'])) : '-';
                $qty_fmt = number_format((float)$s['qty'], 2, ',', '.') . ' ' . $s['unit_symbol'];
                $price_fmt = number_format((float)$s['price'], 0, ',', '.');
                $tot_fmt = number_format((float)$s['total_selling'], 0, ',', '.');
                $bpp_fmt = number_format((float)$s['bpp'], 0, ',', '.');
                $margin_fmt = number_format((float)$s['margin'], 0, ',', '.');
                $margin_class = (float)$s['margin'] >= 0 ? 'text-green-700 font-bold' : 'text-red-600 font-bold';

                $badge_status = $s['status'] === 'Lunas' 
                  ? "<span class='px-2 py-0.5 text-xs bg-green-100 text-green-800 font-semibold rounded'>Lunas</span>"
                  : "<span class='px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 font-semibold rounded'>" . htmlspecialchars($s['status']) . "</span>";

                echo "<tr class='hover:bg-gray-50'>
                  <td class='px-3 py-2 whitespace-nowrap text-gray-600'>{$tgl_fmt}</td>
                  <td class='px-3 py-2 whitespace-nowrap font-medium text-blue-700'>" . htmlspecialchars($s['invoice_number'] ?? '-') . "</td>
                  <td class='px-3 py-2 whitespace-nowrap'>" . htmlspecialchars($s['buyer_name']) . "</td>
                  <td class='px-3 py-2 whitespace-nowrap font-medium'>" . htmlspecialchars($s['product_name']) . "</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap'>{$qty_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap text-gray-600'>Rp {$price_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap font-semibold text-gray-900'>Rp {$tot_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap text-gray-700 font-medium'>Rp {$bpp_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap {$margin_class}'>Rp {$margin_fmt}</td>
                  <td class='px-3 py-2 text-center whitespace-nowrap'>{$badge_status}</td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='10' class='text-center py-6 text-gray-400'>Tidak ada transaksi penjualan pada bulan ini.</td></tr>";
            }
            ?>
          </tbody>
          <?php if (!empty($sales)): ?>
          <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
            <tr>
              <td colspan="6" class="px-3 py-2 text-left">TOTAL PENJUALAN</td>
              <td class="px-3 py-2 text-right text-blue-800">Rp <?= number_format($pendapatan, 0, ',', '.') ?></td>
              <td class="px-3 py-2 text-right text-gray-800">Rp <?= number_format($bpp, 0, ',', '.') ?></td>
              <td class="px-3 py-2 text-right <?= $laba_kotor >= 0 ? 'text-green-700' : 'text-red-600' ?>">Rp <?= number_format($laba_kotor, 0, ',', '.') ?></td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Bagian 2: Rincian Batch Produksi yang Terpakai (FIFO) -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="bg-gray-800 text-white px-5 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <span class="material-symbols-outlined text-yellow-400">inventory_2</span>
          <h2 class="font-bold text-sm tracking-wide">B. Rincian Batch Produksi yang Terpakai (FIFO) pada Bulan Ini</h2>
        </div>
        <span class="text-xs text-gray-300"><?= count($batches_used) ?> Batch</span>
      </div>

      <div class="overflow-x-auto p-4">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
          <thead class="bg-gray-100 text-gray-700 uppercase font-semibold">
            <tr>
              <th class="px-3 py-2 text-left">No Batch</th>
              <th class="px-3 py-2 text-left">Tanggal Produksi</th>
              <th class="px-3 py-2 text-left">Nama Produk</th>
              <th class="px-3 py-2 text-right">Qty Terpakai</th>
              <th class="px-3 py-2 text-right">HPP per Satuan</th>
              <th class="px-3 py-2 text-right">Subtotal BPP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-gray-800">
            <?php
            if (!empty($batches_used)) {
              $sub_tot_bpp_batch = 0;
              foreach ($batches_used as $b) {
                $sub_tot_bpp_batch += (float)$b['total_cost'];
                $tgl_p_fmt = !empty($b['prod_date']) ? date('d/m/Y', strtotime($b['prod_date'])) : '-';
                $qty_used_fmt = number_format((float)$b['qty_used'], 2, ',', '.') . ' ' . $b['unit_symbol'];
                $ucost_fmt = number_format((float)$b['unit_cost'], 2, ',', '.');
                $tcost_fmt = number_format((float)$b['total_cost'], 0, ',', '.');

                echo "<tr class='hover:bg-gray-50'>
                  <td class='px-3 py-2 whitespace-nowrap font-medium text-gray-900'>" . htmlspecialchars($b['production_number'] ?? '-') . "</td>
                  <td class='px-3 py-2 whitespace-nowrap text-gray-600'>{$tgl_p_fmt}</td>
                  <td class='px-3 py-2 whitespace-nowrap font-medium'>" . htmlspecialchars($b['product_name']) . "</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap font-semibold text-blue-700'>{$qty_used_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap text-gray-600'>Rp {$ucost_fmt}</td>
                  <td class='px-3 py-2 text-right whitespace-nowrap font-bold text-gray-900'>Rp {$tcost_fmt}</td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='6' class='text-center py-6 text-gray-400'>Tidak ada batch produksi yang terpakai pada bulan ini.</td></tr>";
            }
            ?>
          </tbody>
          <?php if (!empty($batches_used)): ?>
          <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
            <tr>
              <td colspan="5" class="px-3 py-2 text-left">TOTAL BPP DARI BATCH FIFO</td>
              <td class="px-3 py-2 text-right text-gray-900 font-bold">Rp <?= number_format($bpp, 0, ',', '.') ?></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Bagian 3: Rincian Beban Operasional Bulanan Gaharu -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="bg-gray-800 text-white px-5 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <span class="material-symbols-outlined text-yellow-400">receipt</span>
          <h2 class="font-bold text-sm tracking-wide">C. Rincian Beban Operasional Bulanan Gaharu</h2>
        </div>
        <span class="text-xs text-gray-300"><?= count($operasional_items) ?> Item</span>
      </div>

      <div class="overflow-x-auto p-4">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
          <thead class="bg-gray-100 text-gray-700 uppercase font-semibold">
            <tr>
              <th class="px-3 py-2 text-left">Nama Pengeluaran</th>
              <th class="px-3 py-2 text-center">Kategori</th>
              <th class="px-3 py-2 text-left">Keterangan</th>
              <th class="px-3 py-2 text-right">Jumlah Biaya</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-gray-800">
            <?php
            if (!empty($operasional_items)) {
              foreach ($operasional_items as $op) {
                $badge_jenis = $op['jenis'] === 'fix'
                  ? "<span class='px-2 py-0.5 text-xs bg-purple-100 text-purple-800 font-semibold rounded'>Biaya Tetap (Fix)</span>"
                  : "<span class='px-2 py-0.5 text-xs bg-blue-100 text-blue-800 font-semibold rounded'>Variatif (Tidak Fix)</span>";
                $jml_fmt = number_format((float)$op['jumlah'], 0, ',', '.');
                $ket = !empty($op['keterangan']) ? htmlspecialchars($op['keterangan']) : '-';

                echo "<tr class='hover:bg-gray-50'>
                  <td class='px-3 py-2 font-medium text-gray-900'>" . htmlspecialchars($op['nama_pengeluaran']) . "</td>
                  <td class='px-3 py-2 text-center'>{$badge_jenis}</td>
                  <td class='px-3 py-2 text-gray-600'>{$ket}</td>
                  <td class='px-3 py-2 text-right font-bold text-red-600 whitespace-nowrap'>Rp {$jml_fmt}</td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='4' class='text-center py-6 text-gray-400'>Tidak ada catatan pengeluaran bulanan pada bulan ini.</td></tr>";
            }
            ?>
          </tbody>
          <?php if (!empty($operasional_items)): ?>
          <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
            <tr>
              <td colspan="3" class="px-3 py-2 text-left">TOTAL BEBAN OPERASIONAL</td>
              <td class="px-3 py-2 text-right text-red-600 font-bold">Rp <?= number_format($operasional, 0, ',', '.') ?></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

  </main>
</body>

</html>
