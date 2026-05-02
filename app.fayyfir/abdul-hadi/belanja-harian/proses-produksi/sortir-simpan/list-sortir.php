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

// Ambil semua data sortir dengan total biaya_exp dan harga akhir
$query = "
  SELECT 
    ps.*,
    pa.kode_batch,
    pa.berat_awal,
    pa.harga_per_kg,
    pj.berat_setelah_jemur,
    pj.penyusutan_jemur,
    pk.berat_setelah_kupas,
    pk.penyusutan_kupas,
    b.nama_bahan
  FROM bb_proses_sortir ps
  JOIN bb_pembelian_awal pa ON ps.id_pembelian = pa.id
  LEFT JOIN bb_proses_jemur pj ON pj.id_pembelian = pa.id
  LEFT JOIN bb_proses_kupas pk ON pk.id_pembelian = pa.id
  JOIN bb_bahan_master b ON pa.id_bahan = b.id
  WHERE pa.status = 'sortir'
  GROUP BY pa.id
  ORDER BY ps.created_at DESC
";

$result = $conn->query($query) or die("SQL Error: " . $conn->error);

$activeMenu = "productions";
$activeModule = "Daftar Penyimpanan";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>      
        
<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">        
  <!-- Header -->        
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">        
    <a href="../index"        
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">        
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"        
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">        
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />        
      </svg>        
      <span>Kembali ke Produksi</span>        
    </a>        
        
    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">        
      Daftar Penyimpanan Bahan        
    </h1>        
  </div>        
        
  <!-- Card Wrapper -->        
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">        
    <!-- Header Card -->        
    <div class="p-5 border-b flex flex-col sm:flex-row justify-between sm:items-center">        
      <div>        
        <h2 class="text-lg font-semibold text-gray-800">Data Proses Penyimpanan</h2>        
        <p class="text-sm text-gray-500 mt-1">Menampilkan seluruh batch hasil sortir & penyimpanan</p>        
      </div>        
      <div class="mt-3 sm:mt-0">        
        <a href="input-sortir"        
          class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition focus:ring-4 focus:ring-green-200">        
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"        
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">        
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />        
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
        <thead class="bg-gray-50">        
          <tr class="whitespace-nowrap">        
            <th class="px-5 py-3 text-center font-medium text-gray-600">#</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Kode Batch</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Bahan</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Berat Awal (kg)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Harga Awal/ Kg</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">BSP-1 (kg)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">PSP-1 (%)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">HSP-1/ Kg</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">BSP-2 (kg)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">PSP-2 (%)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">HSP-2/ Kg</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Berat Akhir (kg)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Susut Akhir (kg)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Total PSP (%)</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Harga Akhir/Kg</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Tanggal Proses</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Aksi</th>        
            <th class="px-5 py-3 text-center font-medium text-gray-600">Selanjutnya</th>        
          </tr>        
        </thead>        
        
        <tbody id="materialTable" class="divide-y divide-gray-100">        
          <?php if ($result->num_rows > 0): ?>        
            <?php $no = 1; while ($row = $result->fetch_assoc()):        
        
              $berat_awal = $row['berat_awal'];
              $harga_awal = $row['harga_per_kg'];
              $total_biaya = $row['total_biaya'];
              $exp_kg = $row['exp_kg'];
              
              $berat_setelah_1 = $row['berat_setelah_jemur'];        
              $berat_setelah_2 = $row['berat_setelah_kupas'];        
              $berat_setelah_3 = $row['berat_akhir'];        
                      
              // Hitung harga setelah proses 1 jika ada berat akhir        
              $harga_setelah_1 = (!empty($berat_setelah_1) && $berat_setelah_1 > 0)        
                ? ($harga_awal * $berat_awal / $berat_setelah_1)        
                : null;        
                        
              // Hitung harga setelah proses 2 jika ada berat akhir        
              $harga_setelah_2 = (!empty($berat_setelah_2) && $berat_setelah_2 > 0)        
                ? ($harga_awal * $berat_awal / $berat_setelah_2)        
                : null;        
                        
                // Hitung harga setelah proses 2 jika ada berat akhir        
              $harga_setelah_3 = (!empty($berat_setelah_3) && $berat_setelah_3 > 0)        
                ? ($harga_awal * $berat_awal / $berat_setelah_3)        
                : null;
                
              $total_harga = $harga_setelah_3 + $exp_kg;
                        
                        
              // Tentukan apakah berat_setelah_kupas kosong        
              $isEmpty = empty($row['berat_akhir']) || $row['berat_akhir'] == 0;          
              $textColor = $isEmpty ? 'text-red-500' : 'text-gray-800';
              
              //Hitungan Total 
              $total_ba += $berat_awal;
              $total_bsp1 += $berat_setelah_1;
              $total_bsp2 += $berat_setelah_2;
              $total_bsp3 += $berat_setelah_3;
            ?>        
              <tr class="data-row whitespace-nowrap hover:bg-gray-50 transition">        
                <td class="px-5 py-3 <?= $textColor ?>"><?= $no++ ?></td>        
                <td class="px-5 py-3 font-medium <?= $textColor ?>"><?= htmlspecialchars($row['kode_batch']) ?></td>        
                <td class="px-5 py-3 <?= $textColor ?>"><?= htmlspecialchars($row['nama_bahan']) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_awal) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_awal) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_setelah_1) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_jemur']) ?></td>        
                <td class="text-right whitespace-nowrappx-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_setelah_1) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_angka($berat_setelah_2) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_kupas']) ?></td>        
                <td class="whitespace-nowrap text-right px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_setelah_2) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_angka($row['berat_akhir']) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_persen($row['penyusutan_total']) ?></td>        
                <td class="text-right px-5 py-3 <?= $textColor ?>"><?= format_persen(($row['penyusutan_jemur'] + $row['penyusutan_kupas'] + $row['penyusutan_total'])) ?></td>        
                <td class="whitespace-nowrap text-right px-5 py-3 <?= $textColor ?>"><?= format_rupiah($harga_setelah_3) ?></td>        
                <td class="px-5 py-3 <?= $textColor ?>"><?= htmlspecialchars(format_tanggal($row['tanggal_simpan'])) ?></td>        
        
                <!-- Aksi -->        
                <td class="px-5 py-3 text-center">        
                  <div class="flex justify-center gap-2">        
                    <a href="detail-sortir?id=<?= $row['id'] ?>"        
                      class="inline-flex justify-center items-center w-full rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-blue-600 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">        
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 -ml-1" fill="none"        
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">        
                        <path stroke-linecap="round" stroke-linejoin="round"        
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />        
                        <path stroke-linecap="round" stroke-linejoin="round"        
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />        
                      </svg>        
                      Detail        
                    </a>
                    
                    <!-- Hidden Dropdown Menu Input -->
                    <div class="relative inline-block text-left" hidden>        
                      <button type="button"        
                        class="inline-flex justify-center items-center w-full rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-green-600 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300"        
                        onclick="this.nextElementSibling.classList.toggle('hidden')">        
                        Input        
                        <svg class="w-4 h-4 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"        
                          stroke="currentColor" stroke-width="2">        
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />        
                        </svg>        
                      </button>        
                      <div        
                        class="hidden absolute right-0 mt-2 w-36 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-lg z-99">        
                        <a href="input-sortir?id=<?= $row['id_pembelian'] ?>"        
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Penyusutan</a>        
                        <a href="pengeluaran?id=<?= $row['id_pembelian'] ?>"        
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pengeluaran</a>        
                      </div>        
                    </div>
                    
                    <a href="input-sortir?id=<?= $row['id'] ?>"        
                      class="inline-flex justify-center items-center w-full rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-green-600 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-300">        
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 -ml-1" fill="none"        
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">        
                        <path stroke-linecap="round" stroke-linejoin="round"        
                          d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />        
                      </svg>        
                      Input        
                    </a>
                    <a href="hapus-sortir?id=<?= $row['id'] ?>"        
                      class="inline-flex justify-center items-center w-full rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-red-600 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-300">        
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 -ml-1" fill="none"        
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">        
                        <path stroke-linecap="round" stroke-linejoin="round"        
                          d="M6 18L18 6M6 6l12 12" />        
                      </svg>        
                      Hapus        
                    </a>        
                  </div>        
                </td>        
        
                <!-- Selanjutnya -->        
                <td class="px-5 py-3 text-center">        
                  <?php if ($row['berat_akhir'] !== null): ?>        
                    <a href="proses-siap-jual?id=<?= $row['id'] ?>"        
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-yellow-400 bg-gray-800 hover:bg-gray-900 rounded-lg transition">        
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"        
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">        
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />        
                      </svg>        
                      Siap Jual        
                    </a>        
                  <?php endif; ?>        
                </td>        
              </tr>        
            <?php endwhile; ?>        
          <?php else: ?>        
            <tr>        
              <td colspan="18" class="px-5 py-10 text-center text-gray-500">        
                <div class="flex flex-col items-center justify-center gap-2">        
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" fill="none"        
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">        
                    <path stroke-linecap="round" stroke-linejoin="round"        
                      d="M4 6h16M4 12h16M4 18h16" />        
                  </svg>        
                  <span class="text-gray-500">Belum ada data sortir & penyimpanan.</span>        
                </div>        
              </td>        
            </tr>        
          <?php endif; ?>        
        </tbody>
        <tfoot class="bg-gray-50">
          <tr class="whitespace-nowrap font-semibold">
            <td colspan="3" class="px-5 py-3 text-right text-gray-600">TOTAL</td>
            <td class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_ba) ?></td>
            <td colspan="2" class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp1) ?></td>
            <td colspan="3" class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp2) ?></td>
            <td colspan="3" class="px-5 py-3 text-right text-gray-600"><?= format_angka($total_bsp3) ?></td>
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
        
<?php        
// Fungsi helper: ambil berat awal kupas        
function getBeratAwal($id_pembelian, $conn)        
{        
  // Coba ambil dari proses jemur terlebih dahulu        
  $stmt = $conn->prepare("SELECT berat_setelah_jemur FROM bb_proses_jemur WHERE id_pembelian = ?");        
  $stmt->bind_param("i", $id_pembelian);        
  $stmt->execute();        
  $res = $stmt->get_result();        
        
  if ($res->num_rows > 0) {        
    $row = $res->fetch_assoc();        
    if (!is_null($row['berat_setelah_jemur']) && $row['berat_setelah_jemur'] > 0) {        
      return $row['berat_setelah_jemur'];        
    }        
  }        
        
  // Jika tidak ada, ambil dari proses kupas        
  $stmt = $conn->prepare("SELECT berat_setelah_kupas FROM bb_proses_kupas WHERE id_pembelian = ?");        
  $stmt->bind_param("i", $id_pembelian);        
  $stmt->execute();        
  $res = $stmt->get_result();        
        
  if ($res->num_rows > 0) {        
    $row = $res->fetch_assoc();        
    if (!is_null($row['berat_setelah_kupas']) && $row['berat_setelah_kupas'] > 0) {        
      return $row['berat_setelah_kupas'];        
    }        
  }        
        
  // Jika keduanya tidak ditemukan        
  return 0;        
}        
?>