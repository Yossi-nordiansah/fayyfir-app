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

// Ambil semua data jemur (dengan fix query dan debug safety)
$query = "
  SELECT 
    pk.*,
    pa.kode_batch, 
    pa.berat_awal, 
    pa.harga_per_kg,
    pj.berat_setelah_jemur, 
    pj.penyusutan_jemur,
    b.nama_bahan
  FROM bb_proses_kupas pk
  JOIN bb_pembelian_awal pa ON pk.id_pembelian = pa.id
  JOIN bb_proses_jemur pj ON pj.id_pembelian = pa.id
  JOIN bb_bahan_master b ON pa.id_bahan = b.id
  WHERE pa.status = 'kupas'
  ORDER BY pk.created_at DESC
";

$result = $conn->query($query) or die("SQL Error: " . $conn->error);

$activeMenu = "productions";
$activeModule = "Daftar Penyusutan 2";

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
      <span>Kembali ke Produksi</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Daftar Penyusutan</h1>
  </div>

  <!-- Card Wrapper -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header Card -->
    <div class="p-5 border-b flex flex-col sm:flex-row justify-between sm:items-center">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Data Proses 2</h2>
        <p class="text-sm text-gray-500 mt-1">Menampilkan seluruh proses penyusutan terbaru</p>
      </div>
      <div class="mt-3 sm:mt-0">
        <a href="input-kupas"
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
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr class="whitespace-nowrap">
            <th class="px-5 py-3 text-left font-medium text-gray-600">#</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">Kode Batch</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">Bahan</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">BA (kg)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">BSP-1 (kg)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">PSP-1 (%)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">BSP-2 (kg)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">PSP-2 (%)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">Total PSP (%)</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">Harga Awal/ Kg</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">HSP-1/ Kg</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">HSP-2/ Kg</th>
            <th class="px-5 py-3 text-left font-medium text-gray-600">Tanggal Proses</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Aksi</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Selanjutnya</th>
          </tr>
        </thead>

        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1; while ($row = $result->fetch_assoc()):
              
              $berat_awal = $row['berat_awal'];
              $harga_awal = $row['harga_per_kg'];
              $berat_setelah_1 = $row['berat_setelah_jemur'];
              $berat_setelah_2 = $row['berat_setelah_kupas'];
              
              // Hitung harga setelah proses 1 jika ada berat akhir
              $harga_setelah_1 = (!empty($berat_setelah_1) && $berat_setelah_1 > 0)
                ? ($harga_awal * $berat_awal / $berat_setelah_1)
                : null;
                
                // Hitung harga setelah proses 2 jika ada berat akhir
              $harga_setelah_2 = (!empty($berat_setelah_2) && $berat_setelah_2 > 0)
                ? ($harga_awal * $berat_awal / $berat_setelah_2)
                : null;
              
              $isEmpty = empty($row['berat_setelah_kupas']) || $row['berat_setelah_kupas'] == 0;
              $textColor = $isEmpty ? 'text-red-500' : 'text-gray-800';
              
              //Hitungan Total
              $total_ba += $berat_awal;
              $total_bsp1 += $berat_setelah_1;
              $total_bsp2 += $berat_setelah_2;
            ?>
              <tr class="data-row hover:bg-gray-50 transition whitespace-nowrap">
                <td class="px-5 py-3 text-gray-600"><?= $no++ ?></td>
                <td class="px-5 py-3 font-medium <?= $textColor ?>"><?= htmlspecialchars($row['kode_batch']) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_awal) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_setelah_1) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_jemur'], 2) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_setelah_2) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_kupas'], 2) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= number_format(($row['penyusutan_jemur'] + $row['penyusutan_kupas']), 2) ?>%</td>
                <td class="whitespace-nowrap px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_awal) ?></td>
                <td class="whitespace-nowrap px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_setelah_1) ?></td>
                <td class="whitespace-nowrap px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_setelah_2) ?></td>
                <td class="px-5 py-3 <?= $textColor ?>"><?= htmlspecialchars(format_tanggal($row['tanggal_proses'])) ?></td>

                <td class="px-5 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <!-- Detail -->
                    <a href="detail-kupas?id=<?= $row['id'] ?>"
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

                    <!-- Input -->
                    <a href="input-kupas?id=<?= $row['id'] ?>"
                      class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-green-50 transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                      </svg>
                      Input
                    </a>

                    <!-- Hapus -->
                    <a href="hapus-kupas?id=<?= $row['id'] ?>"
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

                <!-- Proses Akhir -->
                <td class="px-5 py-3 text-center">
                  <?php if (!empty($row['berat_setelah_kupas'])): ?>
                    <a href="proses-ke-sortir?id=<?= $row['id'] ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                      Proses Akhir
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="13" class="px-5 py-10 text-center text-gray-500">
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
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_ba) ?></td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp1) ?></td>
            <td colspan="2" class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp2) ?></td>
            <td colspan="8" class="px-5 py-3 text-right text-gray-600"></td>
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
      <p>HSP = Harga Setelah Proses</p>
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