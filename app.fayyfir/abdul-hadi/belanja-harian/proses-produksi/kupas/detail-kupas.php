<?php
session_start();
require "../../../config.php";
$conn = $conn2;
require "../../includes/helpers.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../../login");
  exit();
}

// Ambil ID dari query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("Data tidak valid.");
}

// Ambil data kupas + batch + bahan
$stmt = $conn->prepare("
  SELECT 
    pk.*,
    pa.total_modal,
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
  WHERE pk.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  die("Data kupas tidak ditemukan.");
}
$data = $result->fetch_assoc();

// Fungsi helper: ambil berat awal penjemuran
function getBeratJemur($id_pembelian, $conn)
{
  $stmt = $conn->prepare("SELECT berat_setelah_jemur FROM bb_proses_jemur WHERE id_pembelian = ?");
  $stmt->bind_param("i", $id_pembelian);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows > 0) {
    $data = $res->fetch_assoc();
    return $data['berat_setelah_jemur'];
  }
  return 0;
}
$berat_awal = $data['berat_awal'];
$harga_awal = $data['harga_per_kg'];
$berat_setelah_1 = $data['berat_setelah_jemur'];
$berat_setelah_2 = $data['berat_setelah_kupas'];

// Hitung harga setelah proses 1 jika ada berat akhir
$harga_setelah_1 = (!empty($berat_setelah_1) && $berat_setelah_1 > 0)
  ? ($harga_awal * $berat_awal / $berat_setelah_1)
  : null;
  
  // Hitung harga setelah proses 2 jika ada berat akhir
$harga_setelah_2 = (!empty($berat_setelah_2) && $berat_setelah_2 > 0)
  ? ($harga_awal * $berat_awal / $berat_setelah_2)
  : null;

$activeMenu = "productions";
$activeModule = "Detail Penyusutan";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-kupas.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar penyusutan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Detail Penyusutan
    </h1>
  </div>

  <!-- Card Detail -->
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-100 transition-all duration-200">
    <div class="p-6 sm:p-8">
      <!-- Header Batch Info -->
      <div class="mb-6 border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Batch <?= htmlspecialchars($data['kode_batch']) ?></h2>
          <p class="text-sm text-gray-500 mt-1">Detail proses pengupasan bahan produksi</p>
        </div>
        <div
          class="mt-3 sm:mt-0 px-3 py-1.5 text-sm font-medium bg-yellow-100 text-yellow-700 rounded-full border border-yellow-200">
          <?= htmlspecialchars($data['nama_bahan']) ?>
        </div>
      </div>

      <!-- Data Grid -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
        <div>
          <dt class="text-gray-500 mb-1">Modal Awal</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($data['total_modal']) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Berat Awal (kg)</dt>
          <dd class="font-semibold text-gray-900"><?= format_angka($berat_awal) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Harga Awal</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($harga_awal) ?>/Kg</dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Berat Setelah Proses 1 (kg)</dt>
          <dd class="font-semibold text-gray-900"><?= format_angka($berat_setelah_1) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Penyusutan Proses 1 (%)</dt>
          <dd class="font-semibold text-gray-900"><?= format_persen($data['penyusutan_jemur']) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Harga Setelah Proses 1</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($harga_setelah_1) ?>/Kg</dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Berat Setelah Proses 2 (kg)</dt>
          <dd class="font-semibold text-gray-900"><?= format_angka($berat_setelah_2) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Penyusutan Proses 2 (%)</dt>
          <dd class="font-semibold text-red-600"><?= format_persen($data['penyusutan_kupas']) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Harga Setelah Proses 2</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($harga_setelah_2) ?>/Kg</dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Tanggal Proses</dt>
          <dd class="font-semibold text-gray-900">
            <?= htmlspecialchars(format_tanggal($data['tanggal_proses'])) ?>
          </dd>
        </div>

        <!-- <div>
          <dt class="text-gray-500 mb-1">Tanggal Selesai</dt>
          <dd class="font-semibold text-gray-900">
            <?= $data['tanggal_selesai'] ? htmlspecialchars(format_tanggal($data['tanggal_selesai'])) : '-' ?>
          </dd>
        </div> -->

        <div>
          <dt class="text-gray-500 mb-1">Keterangan</dt>
          <dd class="font-medium text-gray-800 leading-relaxed">
            <?= nl2br(htmlspecialchars($data['keterangan'] ?: '-')) ?>
          </dd>
        </div>
      </dl>

      <!-- Highlight Box -->
      <?php if ($berat_setelah_2 === null || $berat_setelah_2 == 0): ?>
        <div class="mt-8 bg-red-50 border border-red-200 rounded-xl p-4 sm:p-5 flex items-center gap-4">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-red-100 text-red-600 font-semibold">!</div>
          <div>
            <p class="text-sm text-red-500">Berat setelah kupas belum diinput.</p>
            <p class="text-base font-semibold text-gray-800">Silakan isi terlebih dahulu sebelum melanjutkan ke tahap berikutnya.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-5 flex items-center gap-4">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-700 font-semibold">
            <?= format_persen($data['penyusutan_jemur'] + $data['penyusutan_kupas']) ?>
          </div>
          
          <div>
            <p class="text-sm text-gray-500">Total Penyusutan sampai Batch Ini</p>
            <p class="text-base font-semibold text-gray-900">
              <?= format_angka(($berat_awal - $berat_setelah_1) + ($berat_setelah_1 - $berat_setelah_2)) ?> kg hilang hingga proses 2 ini.
            </p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Tombol Aksi -->
      <div class="flex justify-between flex-col sm:flex-row gap-3 pt-4 mt-8">
        <a href="input-kupas?id=<?= $id ?>" 
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-medium transition">
           Input Data Susut
        </a>
        <a href="hapus-kupas" 
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 font-medium transition">
           Hapus
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../../partials/footer.php"; ?>