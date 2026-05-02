<?php
session_start();
require "../../config.php";
$conn = $conn2; // koneksi database aktif
require "../includes/helpers.php";

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil data pembelian awal
$query = "SELECT p.*, s.nama_supplier AS supplier_nama, bm.nama_bahan AS bahan_nama
          FROM bb_pembelian_awal p
          LEFT JOIN bb_supplier s ON p.id_supplier = s.id
          LEFT JOIN bb_bahan_master bm ON p.id_bahan = bm.id
          WHERE p.status = 'proses'
          ORDER BY p.kode_batch DESC";
$result = $conn->query($query);

$activeMenu = "purchases";
$activeModule = "Daftar Pembelian Awal";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m10-9l2 9m-6-9v9" />
      </svg>
      Daftar Pembelian Awal
    </h1>

    <a href="input-pembelian"
      class="mt-4 sm:mt-0 inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-yellow-400 px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
      </svg>
      Tambah Pembelian
    </a>
  </div>

  <!-- Card -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

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
    
    <?php if ($result->num_rows > 0): ?>
      <div class="overflow-x-auto pb-8">
        <table class="w-full text-sm text-gray-700">
          <thead class="bg-gray-800 text-yellow-400 text-center text-lg tracking-wider">
            <tr class="whitespace-nowrap">
              <th class="px-6 py-3 font-semibold">#</th>
              <th class="px-6 py-3 font-semibold">Tanggal</th>
              <th class="px-6 py-3 font-semibold">Batch</th>
              <th class="px-6 py-3 font-semibold">Supplier</th>
              <th class="px-6 py-3 font-semibold">Bahan</th>
              <th class="px-6 py-3 font-semibold text-right">Berat (Kg)</th>
              <th class="px-6 py-3 font-semibold text-right">Harga/Kg (Rp)</th>
              <th class="px-6 py-3 font-semibold text-right">Total (Rp)</th>
              <th class="px-6 py-3 font-semibold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody id="materialTable" class="divide-y divide-gray-100">
            <?php 
              $no = 1; 
              $grand_total = 0; // inisialisasi total keseluruhan
              while ($row = $result->fetch_assoc()): 
                $total = $row["berat_awal"] * $row["harga_per_kg"]; 
                $total_berat += $row["berat_awal"];
                $grand_total += $total; // akumulasi total di sini
            ?>
              <tr class="data-row hover:bg-gray-50 whitespace-nowrap transition">
                <td class="px-6 py-3 font-medium text-gray-800"><?= $no++ ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars(format_tanggal($row['tanggal_pembelian'])) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["kode_batch"] ?? "-") ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["supplier_nama"] ?? "-") ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["bahan_nama"]) ?></td>
                <td class="px-6 py-3 text-right"><?= number_format($row["berat_awal"], 2, ',', '.') ?></td>
                <td class="px-6 py-3 text-right"><?= number_format($row["harga_per_kg"], 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-right font-semibold text-emerald-700"><?= number_format($total, 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="detail-pembelian?id=<?= $row["id"] ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Detail
                    </a>
                    
                    <a href="hapus-pembelian?id=<?= $row["id"] ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition">
                      Hapus
                    </a>
          
                    <!-- <a href="proses-ke-tahap?id=<?= $row["id"] ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 13l4 4L19 7" />
                      </svg>
                      Proses
                    </a> -->
                    <!-- Dropdown proses (tidak diubah) -->
                    <div class="relative inline-block text-left">
                      <button type="button"
                        class="inline-flex justify-center items-center w-full rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-green-600 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300"
                        onclick="this.nextElementSibling.classList.toggle('hidden')">
                        Proses
                        <svg class="w-4 h-4 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>
                      <div
                        class="hidden absolute right-0 mt-2 w-36 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-lg z-99">
                        <a href="#" 
                           onclick="openModal(<?= $row['id'] ?>)"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                           Proses Produksi
                        </a>
                        <a href="proses-ke-akhir?id=<?= $row['id'] ?>"
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Simpan Siap Jual</a>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
          
          <tfoot class="bg-gray-800 text-yellow-400 text-center text-lg tracking-wider font-semibold">
            <tr class="whitespace-nowrap">
              <td colspan="5" class="px-6 py-3 text-right">Grand Total</td>
              <td class="px-6 py-3 text-right"><?= number_format($total_berat, 2, ',', '.') ?></td>
              <td></td>
              <td class="px-6 py-3 text-right">
                <?= number_format($grand_total, 0, ',', '.') ?>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="p-10 text-center text-gray-500 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3 text-gray-400" fill="none"
          viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 6v6l4 2m6 4a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Belum ada data pembelian awal yang tercatat.
      </div>
    <?php endif; ?>
    
    <div class="p-4 flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>
    
  </div>
</main>

<!-- Overlay -->
<div id="modalOverlay" class="hidden fixed inset-0 bg-black bg-opacity-40 z-40"></div>

<!-- MODAL -->
<div id="prosesModal" 
     class="hidden fixed inset-0 flex items-center justify-center z-50">

  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

      <h2 class="text-lg font-semibold mb-4">Berat yang akan diproses</h2>

      <form id="formProses" method="POST">

          <label class="block mb-2 font-medium">Berat (Kg)</label>
          <input 
            type="number" 
            step="0.01" 
            name="berat" 
            placeholder="Masukan berat dalam Kg"
            required
            class="w-full border rounded px-3 py-2 mb-4"
          >

          <div class="flex justify-end gap-3">
              <button type="button"
                      onclick="closeModal()"
                      class="px-4 py-2 bg-gray-300 rounded">
                      Batal
              </button>

              <button type="submit"
                      class="px-4 py-2 bg-blue-600 text-white rounded">
                      Simpan
              </button>
          </div>
      </form>

  </div>
</div>

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

// JS Modals
function openModal(id) {
    // Set action form → proses-ke-tahap.php?id=xx
    document.getElementById("formProses").action = "proses-ke-tahap.php?id=" + id;

    document.getElementById("modalOverlay").classList.remove("hidden");
    document.getElementById("prosesModal").classList.remove("hidden");
}

function closeModal() {
    document.getElementById("modalOverlay").classList.add("hidden");
    document.getElementById("prosesModal").classList.add("hidden");
}
</script>

<?php include "../partials/footer.php"; ?>