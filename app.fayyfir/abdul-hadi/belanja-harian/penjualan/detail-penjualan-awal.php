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

// --- Ambil ID penjualan ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("ID penjualan tidak valid.");
}

// --- Ambil data lengkap penjualan ---
$sql = "
    SELECT
    pj.id AS id_penjualan,
    pj.id_pembelian,
    pj.id_buyer,
    pj.tanggal_jual,
    pj.berat_jual,
    pj.harga_jual_per_kg,
    pj.total_penjualan,
    pj.laba_bersih,
    pj.keterangan,
    pa.kode_batch,
    pa.berat_awal,
    pa.status,
    pa.harga_per_kg AS harga_awal_perkg,
    ps.berat_akhir,
    b.nama_bahan,
    br.nama_buyer,

    -- total biaya pengeluaran untuk pembelian ini
    COALESCE(SUM(p.biaya_exp), 0) AS total_biaya,

    -- exp per kg (biaya tambahan per kg), aman pembagian nol
    COALESCE(SUM(p.biaya_exp), 0) / NULLIF(ps.berat_akhir, 0) AS exp_kg,

    -- harga setelah proses 3 (proporsional): harga_awal * berat_awal / berat_akhir
    CASE
      WHEN ps.berat_akhir IS NOT NULL AND ps.berat_akhir > 0
      THEN (pa.harga_per_kg * pa.berat_awal / NULLIF(ps.berat_akhir, 0))
      ELSE NULL
    END AS harga_setelah_3_sql,

    -- harga akhir per kg = harga_setelah_3 + exp_kg (jika harga_setelah_3 ada)
    CASE
      WHEN ps.berat_akhir IS NOT NULL AND ps.berat_akhir > 0
      THEN (
        (pa.harga_per_kg * pa.berat_awal / NULLIF(ps.berat_akhir, 0))
        + (COALESCE(SUM(p.biaya_exp), 0) / NULLIF(ps.berat_akhir, 0))
      )
      ELSE NULL
    END AS harga_akhir_perkg,

    -- total modal akhir = harga_akhir_perkg * berat_akhir
    CASE
      WHEN ps.berat_akhir IS NOT NULL AND ps.berat_akhir > 0
      THEN (
        (
          (pa.harga_per_kg * pa.berat_awal / NULLIF(ps.berat_akhir, 0))
          + (COALESCE(SUM(p.biaya_exp), 0) / NULLIF(ps.berat_akhir, 0))
        ) * ps.berat_akhir
      )
      ELSE NULL
    END AS total_modal_akhir

  FROM bb_penjualan pj
  LEFT JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
  LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
  LEFT JOIN bb_pengeluaran p ON p.id_pembelian = pa.id
  LEFT JOIN bb_bahan_master b ON pa.id_bahan = b.id
  LEFT JOIN bb_buyer br ON pj.id_buyer = br.id
        WHERE pj.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  die("Data penjualan tidak ditemukan.");
}
$data = $result->fetch_assoc();

// --- Hitung HPP & Laba Bersih ---
$hpp_per_kg = $data['total_modal'] / ($data['berat_awal'] ?: 1);
$total_hpp = $hpp_per_kg * $data['berat_jual'];
$laba_bersih = $data['total_penjualan'] - $data['total_modal_akhir'];

$activeMenu = "sales";
$activeModule = "Detail Penjualan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
    <a href="index.php" class="inline-flex items-center text-gray-700 hover:text-gray-900 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Kembali ke Daftar Penjualan
    </a>
    <h1 class="text-2xl font-semibold text-gray-800 mt-4 sm:mt-0">Detail Penjualan</h1>
  </div>

  <!-- Card Detail -->
  <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">
          Batch <span class="text-blue-600"><?= htmlspecialchars($data['kode_batch']) ?></span>
        </h2>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($data['nama_bahan']) ?> — <?= htmlspecialchars($data['nama_buyer']) ?></p>
      </div>

      <div class="mt-4 md:mt-0">
        <span class="px-4 py-1.5 rounded-full text-sm font-medium
          <?= $laba_bersih >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
          <?= $laba_bersih >= 0 ? 'Laba Bersih' : 'Rugi Bersih' ?>:
          <?= format_rupiah($laba_bersih) ?>
        </span>
      </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-100">
      <table class="min-w-full divide-y divide-gray-100 text-sm">
        <tbody class="divide-y divide-gray-50">
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600 w-1/3">Tanggal Jual</th>
            <td class="py-3 px-4 text-gray-800"><?= format_tanggal($data['tanggal_jual']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Berat Jual</th>
            <td class="py-3 px-4 text-gray-800"><?= number_format($data['berat_jual'], 2) ?> kg</td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Harga Jual / Kg</th>
            <td class="py-3 px-4 text-gray-800"><?= format_rupiah($data['harga_jual_per_kg']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50 bg-gray-50">
            <th class="text-left py-3 px-4 font-semibold text-gray-700">Total Penjualan</th>
            <td class="py-3 px-4 font-semibold text-blue-700"><?= format_rupiah($data['total_penjualan']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">HPP / Kg</th>
            <td class="py-3 px-4 text-gray-800"><?= format_rupiah($data['harga_akhir_perkg']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Total HPP</th>
            <td class="py-3 px-4 text-gray-800"><?= format_rupiah($data['total_modal_akhir']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50 bg-gray-50">
            <th class="text-left py-3 px-4 font-semibold text-gray-700">Laba / Rugi Bersih</th>
            <td class="py-3 px-4 font-semibold <?= $laba_bersih >= 0 ? 'text-green-700' : 'text-red-700' ?>">
              <?= format_rupiah($laba_bersih) ?>
            </td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Keterangan</th>
            <td class="py-3 px-4 text-gray-700"><?= nl2br(htmlspecialchars($data['keterangan'] ?: '-')) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>