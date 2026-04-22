<?php
session_start();
require "../../../config.php";
$conn = $conn2;
require "../../includes/helpers.php";

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../../login");
  exit();
}

// Ambil semua data susut 1
$query = "SELECT pj.*, SUM(pj.berat_sebelum_jemur) AS total_berat_sebelum, pa.kode_batch, pa.harga_per_kg, pa.berat_awal, b.nama_bahan, s.nama_supplier
          FROM bb_proses_jemur pj
          JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
          JOIN bb_bahan_master b ON pa.id_bahan = b.id
          JOIN bb_supplier s ON pa.id_supplier = s.id
          GROUP BY s.nama_supplier
          ORDER BY pj.created_at DESC";
$result = $conn->query($query);

$activeMenu = "productions";
$activeModule = "Daftar Penyusutan 1";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="../index"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali Produksi</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Daftar Penyusutan 1</h1>
  </div>

  <!-- Card Wrapper -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header Card -->
    <div class="p-5 border-b flex flex-col sm:flex-row justify-between sm:items-center">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Data Proses Susut 1</h2>
        <p class="text-sm text-gray-500 mt-1">Menampilkan seluruh proses penyusutan terbaru.</p>
      </div>
      <div class="mt-3 sm:mt-0">
        <a href="input-jemur"
          class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition focus:ring-4 focus:ring-green-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 4v16m8-8H4" />
          </svg>
          Tambah Bahan
        </a>
      </div>
    </div>

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

    <!-- Responsive Table -->
    <div class="overflow-x-auto pb-16">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-center">
          <tr>
            <th class="px-5 py-3 font-medium text-gray-600">#</th>
            <th class="px-5 py-3 font-medium text-gray-600">Nama Supplier</th>
            <th class="px-5 py-3 font-medium text-gray-600">Bahan</th>
            <th class="px-5 py-3 font-medium text-gray-600">Belum Diproses (Kg)</th>
            <th class="px-5 py-3 font-medium text-gray-600">Sudah Diproses (Kg)</th>
            <th class="px-5 py-3 font-medium text-gray-600">PSP 1 (%)</th>
            <th class="px-5 py-3 font-medium text-gray-600">Harga Awal</th>
            <th class="px-5 py-3 font-medium text-gray-600">Harga Setelah Proses</th>
            <th class="px-5 py-3 font-medium text-gray-600">Tanggal Selesai</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Aksi</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Selanjutnya</th>
          </tr>
        </thead>

        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php $no = 1;
            while ($row = $result->fetch_assoc()):
              $berat_awal = $row['berat_awal'];
              $berat_sebelum = $row['total_berat_sebelum'];
              $harga_awal = $row['harga_per_kg'];
              $berat_setelah = $row['berat_setelah_jemur'];

              // Hitung harga setelah proses jika ada berat akhir
              $harga_setelah = (!empty($berat_setelah) && $berat_setelah > 0)
                ? ($harga_awal * $berat_awal / $berat_setelah)
                : null;

              $isEmpty = $berat_sebelum > 0;
              $textColor = $isEmpty ? 'text-red-500' : 'text-gray-800';
              
              // Hitungan Total
              $total_befor += $berat_sebelum;
              $total_awal += $berat_awal;
              $total_bsp1 += $berat_setelah;
              
            ?>

              <tr class="data-row whitespace-nowrap hover:bg-gray-50 transition">
                <td class="px-5 py-3 <?= $textColor ?>"><?= $no++ ?></td>
                <td class="px-5 py-3 font-medium <?= $textColor ?>"><?= htmlspecialchars($row['nama_supplier']) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                <td class="px-5 py-3 text-right <?= $textColor ?>"><?= format_angka($berat_sebelum) ?></td>
                <td class="px-5 py-3 text-right <?= $textColor ?>"><?= format_angka($row['berat_setelah_jemur'], 2) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_jemur']) ?></td>
                <td class="px-5 py-3 text-right <?= $textColor ?>"><?= format_rupiah($harga_awal) ?></td>
                <td class="px-5 py-3 text-right <?= $textColor ?>"><?= $harga_setelah ? format_rupiah($harga_setelah) : 'Rp 0' ?></td>
                <td class="px-5 py-3 text-center <?= $textColor ?>"><?= htmlspecialchars(format_tanggal($row['tanggal_selesai'])) ?></td>

                <!-- Tombol aksi (tidak diubah) -->
                <td class="px-5 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="detail-jemur?id=<?= $row['id'] ?>"
                      class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      Detail
                    </a>
                    <a href="input-jemur?id=<?= $row['id'] ?>"
                      class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                      </svg>
                      Input
                    </a>
                    <a href="hapus-jemur?id=<?= $row['id'] ?>"
                      class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Hapus
                    </a>
                  </div>
                </td>

                <!-- Dropdown proses (tidak diubah) -->
                <td class="px-5 py-3 text-center">
                  <?php if (!$isEmpty): ?>
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
                        <a href="proses-ke-kupas?id=<?= $row['id'] ?>"
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Lanjut</a>
                        <a href="proses-ke-sortir?id=<?= $row['id'] ?>"
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Selesai</a>
                      </div>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="11" class="px-5 py-10 text-center text-gray-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                  <span class="text-gray-500">Belum ada data penyusutan.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot class="bg-gray-50">
          <tr class="whitespace-nowrap font-semibold">
            <td colspan="3" class="px-5 py-3 text-right text-gray-600">TOTAL</td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_befor) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp1) ?></td>
            <td colspan="6" class="px-5 py-3 text-right text-gray-600"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    
    <div class="p-4 flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>
    
    <div class="px-4 pb-4 flex flex-col sm:flex-row justify-between sm:items-center text-red-400 text-xs">
      <p>BA = Berat Awal</p>
      <p>BSP = Berat Setelah Proses</p>
      <p>PSP = Penyusutan Setelah Proses</p>
    </div>
  </div>
</main>

<script src="../../assets/js/table-pagination.js"></script>

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

<?php include "../../partials/footer.php"; ?>