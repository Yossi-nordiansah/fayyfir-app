<?php
session_start();
require "../../../config.php";
$conn = $conn2;
require "../../includes/helpers.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID dari query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("Data tidak valid.");
}

// Ambil data jemur + batch + bahan
$stmt = $conn->prepare("
  SELECT pj.*, pa.kode_batch, pa.berat_awal, pa.total_modal, pa.harga_per_kg, b.nama_bahan
  FROM bb_proses_jemur pj
  JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
  JOIN bb_bahan_master b ON pa.id_bahan = b.id
  WHERE pj.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  die("Data jemur tidak ditemukan.");
}
$data = $result->fetch_assoc();

// Hitung penyusutan otomatis
$berat_awal = $data['berat_awal'];
$berat_hasil = $data['berat_setelah_jemur'] ?? 0;
$penyusutan = $berat_awal > 0 ? (($berat_awal - $berat_hasil) / $berat_awal * 100) : 0;

// Perhitungan modal & harga per kg
$modal_awal = $data['total_modal'];
$harga_per_kg_awal = $data['harga_per_kg'];
$harga_per_kg_setelah = ($berat_hasil > 0) ? $modal_awal / $berat_hasil : 0;
$modal_setelah_proses = $berat_hasil * $harga_per_kg_setelah; // belum ada biaya tambahan

$activeMenu = "productions";
$activeModule = "Detail Penyusutan";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-jemur.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar jemur</span>
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
          <p class="text-sm text-gray-500 mt-1">Detail proses penyusutan bahan produksi</p>
        </div>
        <div
          class="mt-3 sm:mt-0 px-3 py-1.5 text-sm font-medium bg-yellow-100 text-yellow-700 rounded-full border border-yellow-200">
          <?= htmlspecialchars($data['nama_bahan']) ?>
        </div>
      </div>

      <!-- Data Grid -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
        <div>
          <dt class="text-gray-500 mb-1">Berat Awal (kg)</dt>
          <dd class="font-semibold text-gray-900"><?= htmlspecialchars($berat_awal) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Berat Setelah Proses (kg)</dt>
          <dd class="font-semibold text-gray-900">
            <?= $data['berat_setelah_jemur'] !== null ? htmlspecialchars($berat_hasil) : '<span class="text-red-600">Belum diinput</span>' ?>
          </dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Modal Awal</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($modal_awal) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Modal Setelah Proses</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($modal_setelah_proses) ?></dd>
        </div>
        
        <div>
          <dt class="text-gray-500 mb-1">Harga/Kg Awal</dt>
          <dd class="font-semibold text-gray-900"><?= format_rupiah($harga_per_kg_awal) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Harga/Kg Setelah Proses</dt>
          <dd class="font-semibold text-gray-900">
            <?= $berat_hasil > 0 ? format_rupiah($harga_per_kg_setelah) : '<span class="text-gray-400">-</span>' ?>
          </dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Tanggal Mulai</dt>
          <dd class="font-semibold text-gray-900">
            <?= htmlspecialchars(format_tanggal($data['tanggal_mulai'])) ?>
          </dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Tanggal Selesai</dt>
          <dd class="font-semibold text-gray-900">
            <?= $data['tanggal_selesai'] ? htmlspecialchars(format_tanggal($data['tanggal_selesai'])) : '-' ?>
          </dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Penyusutan (%)</dt>
          <dd class="font-semibold <?= $data['berat_setelah_jemur'] === null ? 'text-gray-400' : 'text-red-600' ?>">
            <?= $data['berat_setelah_jemur'] !== null ? number_format($penyusutan, 2) . '%' : '-' ?>
          </dd>
        </div>

        <div>
          <dt class="text-gray-500 mb-1">Keterangan</dt>
          <dd class="font-medium text-gray-800 leading-relaxed">
            <?= nl2br(htmlspecialchars($data['keterangan'] ?: '-')) ?>
          </dd>
        </div>
      </dl>

      <!-- Highlight Box -->
      <?php if ($data['berat_setelah_jemur'] === null || $data['berat_setelah_jemur'] == 0): ?>
        <div class="mt-8 bg-red-50 border border-red-200 rounded-xl p-4 sm:p-5 flex items-center gap-4">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-red-100 text-red-600 font-semibold">!</div>
          <div>
            <p class="text-sm text-red-500">Berat setelah penyusutan belum diinput.</p>
            <p class="text-base font-semibold text-gray-800">Silakan isi terlebih dahulu sebelum melanjutkan ke tahap berikutnya.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-5 flex items-center gap-4">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-700 font-semibold">
            <?= number_format($penyusutan, 1) ?>%
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Penyusutan dari Batch Ini</p>
            <p class="text-base font-semibold text-gray-900">
              <?= htmlspecialchars($berat_awal - $berat_hasil) ?> kg hilang dari proses pengeringan
            </p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Tombol Aksi -->
      <div class="flex justify-between flex-col sm:flex-row gap-3 pt-4 mt-8">
        <a href="input-jemur?id=<?= $id ?>" 
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-medium transition">
           Input Data Penyusutan
        </a>
        <a href="hapus-jemur" 
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 font-medium transition">
           Hapus
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../../partials/footer.php"; ?>