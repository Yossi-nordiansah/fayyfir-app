<?php
session_start();
require "../../config.php";
$conn = $conn2; // Gunakan koneksi DB alsz2632_ahadi

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil data supplier dari database
$query = $conn->query("SELECT * FROM bb_supplier ORDER BY nama_supplier ASC");
$suppliers = $query->fetch_all(MYSQLI_ASSOC);
?>

<?php
// Variabel layout aktif
$activeMenu = "suppliers";
$activeModule = "Daftar Supplier";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<!-- Konten Utama -->
<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">

  <!-- Header Page -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Data Supplier</h2>
    <a href="tambah-supplier"
      class="mt-3 sm:mt-0 inline-flex items-center justify-center font-medium border border-yellow-500 gap-2 px-4 py-2 bg-yellow-400 text-white rounded-lg hover:bg-yellow-500 transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 4v16m8-8H4" />
      </svg>
      Tambah Supplier
    </a>
  </div>

  <!-- Tabel Daftar Supplier -->
  <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">

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
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400 text-center font-semibold">
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Nama Supplier</th>
            <th class="px-4 py-3">Kontak</th>
            <th class="px-4 py-3">Alamat</th>
            <th class="px-4 py-3">Keterangan</th>
            <th class="px-12 md:px-4 py-3 w-32">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if (count($suppliers) > 0): ?>
            <?php foreach ($suppliers as $index => $s): ?>
              <tr class="data-row hover:bg-gray-50 transition">
                <td class="px-5 py-3 text-gray-600"><?= $index + 1 ?></td>
                <td class="px-5 py-3 font-medium text-gray-800"><?= htmlspecialchars($s['nama_supplier']) ?></td>
                <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($s['kontak'] ?: '-') ?></td>
                <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($s['alamat'] ?: '-') ?></td>
                <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($s['catatan'] ?: '-') ?></td>
                <td class="px-5 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="detail-supplier?id=<?= $s['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Detail">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/eye.svg" class="w-5 h-5" alt="Detail">
                    </a>
                    <a href="edit-supplier?id=<?= $s['id'] ?>" class="text-green-600 hover:text-green-800" title="Edit">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/edit-dark.svg" class="w-5 h-5" alt="Edit">
                    </a>
                    <a href="hapus-supplier?id=<?= $s['id'] ?>" class="text-red-600 hover:text-red-800" title="Hapus">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/trash-dark.svg" class="w-5 h-5" alt="Hapus">
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-5 py-6 text-center text-gray-500">Belum ada data supplier.</td>
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