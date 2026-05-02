<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan DB alsz2632_ahadi

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

// Ambil ID supplier dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id <= 0) {
  echo "<script>alert('ID Supplier tidak valid!'); window.location='index';</script>";
  exit();
}

// Ambil detail supplier
$stmt = $conn->prepare("SELECT * FROM bb_supplier WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

if (!$supplier) {
  echo "<script>alert('Data supplier tidak ditemukan!'); window.location='index';</script>";
  exit();
}
?>

<?php
// Variabel layout aktif
$activeMenu = "suppliers";
$activeModule = "Detail Supplier";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header Navigasi -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="index"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar supplier</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Detail Supplier
    </h1>
  </div>

  <!-- Kartu Detail -->
  <div
    class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200">
    <div class="p-6 sm:p-8">
      <!-- Header Card -->
      <div class="flex items-center justify-between border-b pb-4 mb-6">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
            <?= strtoupper(substr($supplier['nama_supplier'], 0, 1)) ?>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($supplier['nama_supplier']) ?></h2>
            <p class="text-sm text-gray-500">Informasi lengkap data supplier</p>
          </div>
        </div>
      </div>

      <!-- Informasi Utama -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-10 text-sm">
        <div>
          <dt class="text-gray-500">Nama Supplier</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($supplier['nama_supplier']) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Kontak</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($supplier["kontak"]) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Alamat Lengkap</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($supplier["alamat"]) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Keterangan</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($supplier['catatan'] ?: '-') ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Dibuat pada</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($supplier['created_at'] ?: '-') ?></dd>
        </div>
      </dl>

      <!-- Tombol Aksi -->
      <div class="flex flex-col sm:flex-row justify-end gap-3 mt-10">
        <a href="edit-supplier?id=<?= $supplier['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-yellow-400 hover:bg-yellow-500 font-medium transition focus:ring-4 focus:ring-yellow-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/edit-light.svg" class="w-5 h-5" alt="Edit">
          Edit Bahan
        </a>

        <a href="hapus-supplier?id=<?= $supplier['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 font-medium transition focus:ring-4 focus:ring-red-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/trash-light.svg" class="w-5 h-5" alt="Hapus">
          Hapus Bahan
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>