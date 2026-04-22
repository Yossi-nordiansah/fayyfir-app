<?php
session_start();
require "../../config.php";
$conn = $conn2; // Gunakan koneksi DB alsz2632_ahadi

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID bahan dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id === 0) {
  header("Location: index");
  exit();
}

// Ambil data bahan dari database
$query = $conn->prepare("SELECT * FROM bb_bahan_master WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$bahan = $result->fetch_assoc();

if (!$bahan) {
  header("Location: index");
  exit();
}

// Layout aktif
$activeMenu = "materials";
$activeModule = "Detail Bahan";
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
      <span>Kembali ke daftar bahan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Detail Bahan
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
            <?= strtoupper(substr($bahan['nama_bahan'], 0, 1)) ?>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($bahan['nama_bahan']) ?></h2>
            <p class="text-sm text-gray-500">Informasi lengkap bahan baku</p>
          </div>
        </div>
      </div>

      <!-- Informasi Utama -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-10 text-sm">
        <div>
          <dt class="text-gray-500">Nama Bahan</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($bahan['nama_bahan']) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Satuan</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($bahan['satuan']) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Keterangan</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($bahan['keterangan'] ?: '-') ?></dd>
        </div>
      </dl>

      <!-- Tombol Aksi -->
      <div class="flex flex-col sm:flex-row justify-end gap-3 mt-10">
        <a href="log-bahan?id=<?= $bahan['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-gray-400 hover:bg-gray-500 font-medium transition focus:ring-4 focus:ring-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M13 9l3 3-3 3m8-3H3" />
            </svg>
          Log Bahan
        </a>
        
        <a href="edit-bahan?id=<?= $bahan['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-yellow-400 hover:bg-yellow-500 font-medium transition focus:ring-4 focus:ring-yellow-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/edit-light.svg" class="w-5 h-5" alt="Edit">
          Edit Bahan
        </a>

        <a href="hapus-bahan?id=<?= $bahan['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 font-medium transition focus:ring-4 focus:ring-red-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/trash-light.svg" class="w-5 h-5" alt="Hapus">
          Hapus Bahan
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>