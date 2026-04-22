<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";
require "../includes/functions.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil data HPP & penyusutan
$sql = "
    SELECT hpp.id, hpp.nama_bahan, hpp.nama_supplier, hpp.tanggal_pembelian,
           hpp.berat_awal, hpp.total_modal, hpp.hpp_per_kg, penyusutan.kode_batch, penyusutan.penyusutan_jemur, penyusutan.penyusutan_kupas, penyusutan.penyusutan_total
    FROM bb_v_hpp_awal hpp
    LEFT JOIN bb_v_penyusutan_tahap penyusutan
      ON hpp.id = penyusutan.id_pembelian
    ORDER BY hpp.tanggal_pembelian DESC
";
$result = $conn->query($sql);

$activeMenu = "reports";
$activeModule = "Ringkasan Modal HPP";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <!-- Header Section -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-800 mb-1 flex items-center gap-2">
        <!-- Icon laporan -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 4a1 1 0 011-1h4l2 3h6l2-3h4a1 1 0 011 1v17a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
        </svg>
        Ringkasan Modal / HPP per Batch
      </h1>
      <p class="text-gray-600 text-sm">Lihat total modal, penyusutan, dan HPP setiap batch pembelian bahan baku.</p>
    </div>

    <div class="flex gap-3 mt-4 sm:mt-0">
      <!-- Tombol Export Excel -->
      <!-- <a href="export-excel.php?type=ringkasan-hpp"
         class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-xl shadow-sm transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19 2H8a2 2 0 0 0-2 2v2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1v2a2 2 0 0 0 2 2h11a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM9.5 15H8l-1.5-2L5 15H3.5l2-3-2-3H5l1.5 2L8 9h1.5L7.8 12l1.7 3zM18 20H8v-2h9a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H8V2h10z"/>
        </svg>
        <span>Export Excel</span>
      </a> -->

      <!-- Tombol Export PDF -->
      <a href="export-pdf.php?type=hpp"
         class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-xl shadow-sm transition">
        <!-- Icon PDF -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M19 2H8a2 2 0 0 0-2 2v2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1v2a2 2 0 0 0 2 2h11a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM18 20H8v-2h10zM9.5 9h1v6h-1zm3 0h1.8c1.2 0 1.7.7 1.7 1.8 0 1-.6 1.8-1.8 1.8h-.7V15h-1zm1 2.3h.5c.5 0 .8-.2.8-.7s-.3-.7-.8-.7h-.5z"/>
        </svg>
        <span>Export PDF</span>
      </a>
    </div>
  </div>

  <!-- Table Section -->
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-200">

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
      <table class="min-w-full text-sm text-gray-700">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
          <tr class="whitespace-nowrap">
            <th class="px-4 py-3 text-left">Kode Batch</th>
            <th class="px-4 py-3 text-left">Bahan</th>
            <th class="px-4 py-3 text-left">Supplier</th>
            <th class="px-4 py-3 text-left">Tanggal</th>
            <th class="px-4 py-3 text-right">Berat Awal (kg)</th>
            <th class="px-4 py-3 text-right">Total Modal</th>
            <th class="px-4 py-3 text-right">HPP / kg</th>
            <th class="px-4 py-3 text-right">Proses-1 (%)</th>
            <th class="px-4 py-3 text-right">Proses-2 (%)</th>
            <th class="px-4 py-3 text-right">Proses-3 (%)</th>
            <th class="px-4 py-3 text-right">Berat Akhir (kg)</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="divide-y divide-gray-100 bg-white">
          <?php while($row = $result->fetch_assoc()): 
              $berat_awal = floatval($row['berat_awal']);
              $penyusutan_total = floatval($row['penyusutan_total']);
              $berat_akhir = $berat_awal * (1 - ($penyusutan_total / 100));
              
              //Hitungan Total 
              $total_berat_awal += $berat_awal;
              $total_modal += $row['total_modal'];
              $total_berat_akhir += $berat_akhir;
          ?>
          <tr class="data-row hover:bg-gray-50 transition whitespace-nowrap">
            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($row['kode_batch']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['nama_bahan']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['nama_supplier']) ?></td>
            <td class="px-4 py-3"><?= format_tanggal($row['tanggal_pembelian']) ?></td>
            <td class="px-4 py-3 text-right"><?= format_angka($berat_awal) ?></td>
            <td class="px-4 py-3 text-right"><?= format_rupiah($row['total_modal']) ?></td>
            <td class="px-4 py-3 text-right"><?= format_rupiah($row['hpp_per_kg']) ?></td>
            <td class="px-4 py-3 text-right"><?= format_angka($row['penyusutan_jemur'] ?? 0, 2) ?></td>
            <td class="px-4 py-3 text-right"><?= format_angka($row['penyusutan_kupas'] ?? 0, 2) ?></td>
            <td class="px-4 py-3 text-right"><?= format_angka($row['penyusutan_total'] ?? 0, 2) ?></td>
            <td class="px-4 py-3 text-right font-semibold text-gray-900"><?= format_angka($berat_akhir) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
        <tfoot class="bg-gray-100 text-gray-700 font-semibold">
          <tr class="whitespace-nowrap font-semibold">
            <td colspan="4" class="px-5 py-3 text-right">TOTAL</td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_berat_awal) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_rupiah($total_modal) ?></td>
            <td colspan="5" class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_berat_akhir) ?></td>
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
  <div class="mt-6 text-sm text-gray-500 text-center flex items-center justify-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 4v16h16V4M8 2v2m8-2v2M4 10h16" />
    </svg>
    <p>Data dihitung otomatis berdasarkan setiap batch pembelian bahan baku dan hasil proses produksi.</p>
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