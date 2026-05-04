<?php
session_start();
require "../../config.php";
$conn = $conn2; // Gunakan koneksi DB alsz2632_ahadi

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

// Ambil data bahan dari tabel bb_bahan_master
$query = "SELECT * FROM bb_bahan_master WHERE deleted_at IS NULL ORDER BY nama_bahan ASC";
$result = $conn->query($query);
?>

<?php
// Variabel layout aktif
$activeMenu = "materials";
$activeModule = "Daftar Bahan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<!-- Konten Utama -->
<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">

  <!-- Header Page -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Daftar Bahan</h2>
    <a href="tambah-bahan"
      class="mt-3 sm:mt-0 inline-flex items-center justify-center font-medium border border-yellow-500 gap-2 px-4 py-2 bg-yellow-400 text-white rounded-lg hover:bg-yellow-500 transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 4v16m8-8H4" />
      </svg>
      Tambah Bahan
    </a>
  </div>

  <?php if (isset($_SESSION["success"])): ?>
    <div class="mb-6 flex items-center gap-2 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
      <?= $_SESSION["success"];
      unset($_SESSION["success"]); ?>
    </div>
  <?php endif; ?>

  <!-- Tabel Data Bahan -->
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
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3">Nama Bahan</th>
            <th class="px-4 py-3">Satuan</th>
            <th class="px-4 py-3">Keterangan</th>
            <th class="px-12 md:px-4 py-3 w-32">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1;
            while ($row = $result->fetch_assoc()): ?>
              <tr class="data-row hover:bg-gray-50">
                <td class="px-4 py-3 text-center text-gray-700"><?= $no++; ?></td>
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($row["nama_bahan"]); ?></td>
                <td class="px-4 py-3 text-gray-600 text-center"><?= htmlspecialchars($row["satuan"]); ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($row["keterangan"]); ?></td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <!-- Tombol Edit -->
                    <a href="edit-bahan?id=<?= $row['id']; ?>" class="text-yellow-600 hover:text-yellow-800"
                      title="Edit">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/edit-dark.svg" class="w-5 h-5" alt="Edit">
                    </a>

                    <!-- Tombol Hapus -->
                    <a href="hapus-bahan?id=<?= $row['id']; ?>"
                      class="text-red-600 hover:text-red-800" title="Hapus">
                      <img src="/abdul-hadi/belanja-harian/assets/icons/trash-dark.svg" class="w-5 h-5" alt="Hapus">
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                Tidak ada data bahan ditemukan.
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