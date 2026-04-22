<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$sql = "
    SELECT
        p.*,
        b.nama_buyer,
        SUM(biaya_exp) AS total_biaya_exp,

        (
            SELECT bm.nama_bahan
            FROM bb_pembelian_awal pa
            LEFT JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
            WHERE pa.status = 'siap_jual'
            ORDER BY pa.id ASC
            LIMIT 1
        ) AS nama_bahan,

        (
            SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
            FROM bb_pembelian_awal pa
            LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
            WHERE pa.status = 'siap_jual'
        ) AS harga_akhir_perkg,

        (
            p.berat_jual *
            (
                SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
                FROM bb_pembelian_awal pa
                LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
                WHERE pa.status = 'siap_jual'
            )
        ) AS total_modal_akhir

    FROM bb_penjualan p
    LEFT JOIN bb_buyer b ON p.id_buyer = b.id
    LEFT JOIN bb_pengeluaran k ON k.id_penjualan = p.id
    WHERE p.no_invoice IS NOT NULL
    GROUP BY p.id
    ORDER BY p.no_invoice DESC
";

$result = $conn->query($sql);
if ($result === false) {
  die("SQL Error: " . $conn->error);
}

$sql2 = "
    SELECT 
        bm.nama_bahan,
        pa.id_bahan,
        SUM(ps.berat_akhir) AS total_berat_akhir
    FROM bb_pembelian_awal pa
    LEFT JOIN bb_bahan_master bm ON pa.id_bahan = bm.id
    LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
    WHERE pa.status = 'siap_jual'
    GROUP BY pa.id_bahan, bm.nama_bahan
    ORDER BY bm.nama_bahan ASC
";

$result2 = $conn->query($sql2);
if ($result2 === false) {
  die("SQL Error: " . $conn->error);
}

$sql3 = "SELECT SUM(berat_jual) AS total_berat_jual FROM bb_penjualan";
$res3 = $conn->query($sql3);
$row3 = $res3->fetch_assoc();
$total_berat_jual_global = floatval($row3['total_berat_jual'] ?? 0);

$activeMenu = "sales";
$activeModule = "Daftar Penjualan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">
      <span class="material-symbols-outlined">inventory_2</span> Stock Bahan
    </h2>
  </div>

  <section class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
    <?php while ($data = $result2->fetch_assoc()): ?>
    <div class="bg-gray-800 rounded-lg shadow p-4 text-white flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>
        <div>
          <h2 class="text-sm"><?= htmlspecialchars($data["nama_bahan"] ?? "-") ?></h2>

          <?php
              $sisa_stock = floatval($data['total_berat_akhir']) - $total_berat_jual_global;
              if ($sisa_stock < 0) $sisa_stock = 0;
          ?>

          <p class="text-xl font-bold"><?= format_angka($sisa_stock) ?> Kg</p>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </section>

  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">📊 Daftar Penjualan</h2>

    <a href="input-penjualan"
      class="mt-4 sm:mt-0 inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-yellow-400 px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
        stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
      </svg>
      Tambah Penjualan
    </a>
  </div>

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

    <div class="overflow-x-auto">
      <table class="min-w-full border-collapse text-sm text-gray-700">
        <thead class="bg-gray-100 tracking-wide text-gray-600">
          <tr class="whitespace-nowrap text-center">
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3">No. Invoice</th>
            <th class="px-4 py-3">Bahan</th>
            <th class="px-4 py-3">Buyer</th>
            <th class="px-4 py-3">Tanggal Jual</th>
            <th class="px-4 py-3">Berat Jual (Kg)</th>
            <th class="px-4 py-3">Harga/Kg</th>
            <th class="px-4 py-3">Harga Jual</th>
            <th class="px-4 py-3">Total Biaya</th>
            <th class="px-4 py-3">Total Penjualan</th>
            <th class="px-4 py-3">Aksi</th>
            <th class="px-4 py-3">Invoice</th>
          </tr>
        </thead>

        <tbody id="materialTable" class="divide-y divide-gray-100">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php 
              $no = 1;
              while ($row = $result->fetch_assoc()):
                $harga_jual = $row['total_penjualan'];
                $biaya_exp = $row['total_biaya_exp'];
                $total_harga_jual = $harga_jual + $biaya_exp;

                $total_modal += $row['total_modal_akhir'];
                $total_berat += $row['berat_jual'];
                $total_penjualan += $row['total_penjualan'];
                $g_total_biaya_exp += $biaya_exp;
                $g_total_harga_jual += $total_harga_jual;
            ?>
              <tr class="data-row whitespace-nowrap hover:bg-gray-50 transition <?= ($row['status'] === 'siap_jual') ? 'text-red-600' : '' ?>">
                <td class="px-4 py-3"><?= $no++ ?></td>
                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['no_invoice']) ?: '-' ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_bahan']) ?: '-' ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_buyer']) ?: '-' ?></td>
                <td class="px-4 py-3"><?= format_tanggal($row['tanggal_jual']) ?></td>
                <td class="px-4 py-3 text-right"><?= number_format($row['berat_jual'], 2) ?></td>
                <td class="px-4 py-3 text-right"><?= format_rupiah($row['harga_jual_per_kg']) ?></td>
                <td class="px-4 py-3 text-right font-semibold"><?= format_rupiah($row['total_penjualan']) ?></td>
                <td class="px-4 py-3 text-right font-semibold"><?= format_rupiah($row['total_biaya_exp']) ?></td>
                <td class="px-4 py-3 text-right font-semibold"><?= format_rupiah($total_harga_jual) ?></td>

                <td class="px-4 py-3 text-center flex items-center justify-center gap-4">
                  <a href="detail-penjualan?id=<?= $row['id'] ?>"
                    class="inline-flex items-center bg-blue-600 text-white hover:bg-blue-800 transition px-4 py-1.5 rounded-lg">
                    Detail
                  </a>
                  <a href="edit-penjualan?id_penjualan=<?= $row['id'] ?>"
                    class="inline-flex items-center bg-green-600 text-white hover:bg-green-800 transition px-4 py-1.5 rounded-lg">
                    Edit
                  </a>
                </td>

                <td class="px-4 py-3 text-center">
                  <a <?= ($row['total_biaya_exp'] > 0) ? '' : 'hidden' ?>
                     href="invoice-pdf?id=<?= $row['id'] ?>"
                     class="inline-flex items-center bg-gray-800 text-yellow-400 hover:bg-gray-900 transition px-4 py-1.5 rounded-lg">
                    Invoice
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="12" class="px-4 py-6 text-center text-gray-500">
                Belum ada data penjualan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>

        <tfoot class="bg-gray-100 tracking-wide text-gray-600">
          <tr class="whitespace-nowrap font-semibold">
            <td colspan="5" class="px-4 py-3 text-right">TOTAL</td>
            <td class="px-4 py-3 text-right"><?= format_angka($total_berat) ?></td>
            <td colspan="2" class="px-4 py-3 text-right"><?= format_rupiah($total_penjualan) ?></td>
            <td class="px-4 py-3 text-right"><?= format_rupiah($g_total_biaya_exp) ?></td>
            <td class="px-4 py-3 text-right"><?= format_rupiah($g_total_harga_jual) ?></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
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