<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan koneksi DB alsz2632_ahadi

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil semua data buyer dari tabel bb_buyer
$sql = "SELECT id, nama_buyer, kontak, alamat, catatan, created_at 
        FROM bb_buyer 
        ORDER BY id DESC";
$result = $conn->query($sql);

// Variabel layout aktif
$activeMenu = "buyers";
$activeModule = "Data Buyer";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Data Buyer</h1>
    <a href="tambah-buyer.php"
      class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg shadow transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 4v16m8-8H4" />
      </svg>
      Tambah Buyer
    </a>
  </div>

  <!-- Table Container -->
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
      <table class="min-w-full border border-gray-100 rounded-lg overflow-hidden">
        <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">#</th>
            <th class="px-4 py-3 text-left font-semibold">Nama Buyer</th>
            <th class="px-4 py-3 text-left font-semibold">Kontak</th>
            <th class="px-4 py-3 text-left font-semibold">Alamat</th>
            <th class="px-4 py-3 text-left font-semibold">Catatan</th>
            <th class="px-4 py-3 text-left font-semibold">Dibuat</th>
            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
          </tr>
        </thead>

        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
              <tr class="data-row hover:bg-gray-50 transition">
                <td class="px-4 py-3 border-t"><?= $no++ ?></td>
                <td class="px-4 py-3 border-t font-medium"><?= htmlspecialchars($row["nama_buyer"]) ?></td>
                <td class="px-4 py-3 border-t"><?= htmlspecialchars($row["kontak"] ?: '-') ?></td>
                <td class="px-4 py-3 border-t"><?= htmlspecialchars($row["alamat"] ?: '-') ?></td>
                <td class="px-4 py-3 border-t text-gray-600 italic"><?= htmlspecialchars($row["catatan"] ?: '-') ?></td>
                <td class="px-4 py-3 border-t text-gray-500">
                  <?= date("d M Y", strtotime($row["created_at"])) ?>
                </td>
                <td class="px-4 py-3 border-t text-center flex justify-center gap-2">
                  <a href="detail-buyer.php?id=<?= $row['id'] ?>"
                    class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 transition">
                    <img src="/abdul-hadi/belanja-harian/assets/icons/eye.svg" class="w-5 h-5" alt="Detail">
                  </a>
                  <a href="edit-buyer.php?id=<?= $row['id'] ?>"
                    class="inline-flex items-center gap-1 text-yellow-600 hover:text-yellow-700 transition">
                    <img src="/abdul-hadi/belanja-harian/assets/icons/edit-dark.svg" class="w-5 h-5" alt="Edit">
                  </a>
                  <a href="hapus-buyer.php?id=<?= $row['id'] ?>"
                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 transition">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/trash-dark.svg" class="w-5 h-5" alt="Hapus">
                    </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center py-6 text-gray-500">Belum ada data buyer.</td>
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