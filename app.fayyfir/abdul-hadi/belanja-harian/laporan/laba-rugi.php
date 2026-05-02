<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";
require "../includes/queries.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$sql = "
SELECT 
    pa.kode_batch,
    b.nama_bahan,
    s.nama_supplier,
    pa.total_modal,
    p.total_penjualan AS total_penjualan,
    p.laba_bersih AS laba_bersih
FROM bb_pembelian_awal pa
JOIN bb_bahan_master b ON pa.id_bahan = b.id
JOIN bb_supplier s ON pa.id_supplier = s.id
LEFT JOIN bb_penjualan p ON pa.id = p.id_pembelian
GROUP BY pa.id, pa.kode_batch, b.nama_bahan, s.nama_supplier, pa.total_modal, p.total_penjualan, p.laba_bersih
ORDER BY pa.kode_batch ASC
";

$result = $conn->query($sql);

// Inisialisasi variabel total
$total_modal = 0;
$total_penjualan = 0;
$total_laba = 0;

$activeMenu = "reports";
$activeModule = "Laporan Laba Rugi";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <div>
      <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-800">
        <!-- Icon Chart -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3m4 4H3" />
        </svg>
        Laporan Laba Rugi per Batch
      </h1>
      <p class="text-gray-500 text-sm mt-1">Ringkasan penjualan dan laba bersih setiap batch produksi.</p>
    </div>
    <div class="flex gap-3 mt-4 sm:mt-0">
      <!-- Tombol Excel -->
      <!-- <a href="export-excel.php?type=laba" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-xl transition">Icon Excel
        
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24">
          <path d="M4 4h10v2H4v14h10v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm12 0h4a2 2 0 0 1 2 2v2h-6V4zm0 6h6v4h-6v-4zm0 6h6v2a2 2 0 0 1-2 2h-4v-4zM7.5 8h1.8l1.2 2.7L11.7 8h1.8l-2 3.8 2 3.8h-1.8l-1.2-2.7L9.3 15h-1.8l2-3.8L7.5 8z"/>
        </svg>
        Export Semua Excel
      </a> -->

      <!-- Tombol PDF -->
      <a href="export-pdf.php?type=laba" class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-xl transition">
        <!-- Icon PDF -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24">
          <path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6zm7 1.5L18.5 9H13V3.5zM8 13h1.5a1.5 1.5 0 1 1 0 3H8v-3zm0 1.5v.5h1.5a.5.5 0 1 0 0-1H8v.5zm3-.5h2v.5h-1.5v2h-.5v-2.5zM15 13h1.5a1 1 0 1 1 0 2H16v1h-.5v-3z"/>
        </svg>
        Export Semua PDF
      </a>
    </div>
  </div>

  <!-- Tabel -->
  <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">

    <div class="p-4 flex justify-between items-center flex-wrap gap-2">
      <input id="searchInput" type="text" placeholder="Cari di sini..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">
      <div class="text-sm">
        Tampilkan 
        <select id="rowsPerPage" class="border border-gray-300 rounded px-2 py-1">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select> 
        baris
      </div>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full border-collapse">
        <thead class="bg-gray-100 text-gray-700 tracking-wide">
          <tr class="whitespace-nowrap">
            <th class="px-4 py-3 text-left">Kode Batch</th>
            <th class="px-4 py-3 text-left">Bahan</th>
            <th class="px-4 py-3 text-left">Supplier</th>
            <th class="px-4 py-3 text-right">Total Modal</th>
            <th class="px-4 py-3 text-right">Total Penjualan</th>
            <th class="px-4 py-3 text-right">Laba Bersih</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="text-sm divide-y divide-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
            
              //Hitungan Total 
              $total_modal += $row['total_modal'];
              $total_penjualan += $row['total_penjualan'];
              $total_laba += $row['laba_bersih'];
            ?>
              <tr class="data-row hover:bg-gray-50 transition whitespace-nowrap">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($row['kode_batch']) ?></td>
                <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($row['nama_supplier']) ?></td>
                <td class="px-4 py-3 text-right text-gray-800"><?= format_rupiah($row['total_modal']) ?></td>
                <td class="px-4 py-3 text-right text-gray-800"><?= format_rupiah($row['total_penjualan']) ?></td>
                <td class="px-4 py-3 text-right font-semibold <?= $row['laba_bersih'] >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                  <?= format_rupiah($row['laba_bersih']) ?>
                </td>
                <td class="px-4 py-3 text-center flex justify-center gap-2">
                  <!-- Tombol Excel -->
                  <!-- <a href="export-excel.php?type=laba&id=<?= urlencode($row['kode_batch']) ?>"
                     class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24">
                      <path d="M4 4h10v2H4v14h10v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm12 0h4a2 2 0 0 1 2 2v2h-6V4zm0 6h6v4h-6v-4zm0 6h6v2a2 2 0 0 1-2 2h-4v-4zM7.5 8h1.8l1.2 2.7L11.7 8h1.8l-2 3.8 2 3.8h-1.8l-1.2-2.7L9.3 15h-1.8l2-3.8L7.5 8z"/>
                    </svg>
                    Excel
                  </a> -->

                  <!-- Tombol PDF -->
                  <a href="export-pdf.php?type=laba&id=<?= urlencode($row['kode_batch']) ?>"
                     class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24">
                      <path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6zm7 1.5L18.5 9H13V3.5zM8 13h1.5a1.5 1.5 0 1 1 0 3H8v-3zm0 1.5v.5h1.5a.5.5 0 1 0 0-1H8v.5zm3-.5h2v.5h-1.5v2h-.5v-2.5zM15 13h1.5a1 1 0 1 1 0 2H16v1h-.5v-3z"/>
                    </svg>
                    PDF
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                Tidak ada data laba rugi ditemukan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot class="bg-gray-100 text-gray-700 tracking-wide">
          <tr class="whitespace-nowrap font-semibold">
            <td colspan="3" class="px-5 py-3 text-right">TOTAL</td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_rupiah($total_modal) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_rupiah($total_penjualan) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_rupiah($total_laba) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    
    <div class="p-4 flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>
    
  </div>

  <!-- Footer Info -->
  <div class="text-xs text-gray-500 text-center mt-6">
    Terakhir diperbarui: <?= date('d M Y, H:i') ?> WIB
  </div>
</main>

<script src="../assets/js/table-pagination.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  initTablePagination({
    tableId: "materialTable",
    rowsPerPageId: "rowsPerPage",
    searchInputId: "searchInput",
    paginationId: "paginationControls",
    infoId: "totalRowsInfo"
  });
});
</script>

<?php include "../partials/footer.php"; ?>