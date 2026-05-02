<?php
session_start();
require "../../config.php";
$conn = $conn2; 

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

// Ambil data stok dari View bb_v_stok_bahan
$query = "SELECT * FROM bb_v_stok_bahan ORDER BY nama_bahan ASC";
$result = $conn->query($query);
?>

<?php
// Variabel layout aktif
$activeMenu = "stok-bahan";
$activeModule = "Stok Bahan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<!-- Konten Utama -->
<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">

  <!-- Header Page -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
      Stok Bahan (Bahan Baku)
    </h2>
    <p class="text-sm text-gray-500 mt-2 sm:mt-0">Total stok bahan mentah yang belum masuk proses produksi.</p>
  </div>

  <!-- Table Section -->
  <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">

    <div class="p-4 flex justify-between items-center flex-wrap gap-2">
      <input id="searchInput" type="text" placeholder="Cari bahan..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
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
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400 text-center font-semibold">
          <tr>
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3 text-left">Nama Bahan</th>
            <th class="px-4 py-3 text-center">Total Stok</th>
            <th class="px-4 py-3 text-center">Satuan</th>
            <th class="px-4 py-3 w-32">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
              <tr class="data-row hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-center text-gray-700"><?= $no++; ?></td>
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($row["nama_bahan"]); ?></td>
                <td class="px-4 py-3 text-center font-bold text-gray-900">
                  <span class="<?= ($row['stok_tersedia'] > 0) ? 'text-emerald-600' : 'text-red-500'; ?>">
                    <?= number_format($row["stok_tersedia"], 2, ',', '.'); ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-center"><?= htmlspecialchars($row["satuan"]); ?></td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <!-- Tombol Detail/View -->
                    <a href="view-detail.php?id=<?= $row['id_bahan']; ?>" 
                       class="inline-flex items-center p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition"
                       title="Lihat Rincian Pembelian">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </a>

                    <!-- Tombol Hapus -->
                    <a href="hapus-bahan?id=<?= $row['id_bahan']; ?>"
                       onclick="return confirm('Apakah Anda yakin ingin menghapus data bahan ini?')"
                       class="inline-flex items-center p-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition" 
                       title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                Belum ada data stok bahan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <div class="p-4 flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>
    
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
