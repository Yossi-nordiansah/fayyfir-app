<?php
session_start();
require "../../config.php";
$conn = $conn2; // Gunakan koneksi DB alsz2632_ahadi

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID buyer dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id === 0) {
  header("Location: index");
  exit();
}

// Ambil data buyer dari database
$stmt = $conn->prepare("SELECT * FROM bb_buyer WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$buyer = $result->fetch_assoc();

if (!$buyer) {
  header("Location: index");
  exit();
}

$activeMenu = "buyers";
$activeModule = "Detail Buyer";
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
      <span>Kembali ke daftar buyer</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Detail Buyer
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
            <?= strtoupper(substr($buyer['nama_buyer'], 0, 1)) ?>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($buyer['nama_buyer']) ?></h2>
            <p class="text-sm text-gray-500">Informasi lengkap buyer</p>
          </div>
        </div>
      </div>

      <!-- Detail Informasi -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-10 text-sm">
        <div>
          <dt class="text-gray-500">Nama Buyer</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($buyer['nama_buyer']) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Nomor Kontak</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($buyer['kontak']) ?></dd>
        </div>

        <div>
          <dt class="text-gray-500">Alamat</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($buyer['alamat']) ?></dd>
        </div>

        <?php if (!empty($buyer['email'])): ?>
        <div>
          <dt class="text-gray-500">Email</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($buyer['email']) ?></dd>
        </div>
        <?php endif; ?>

        <?php if (!empty($buyer['perusahaan'])): ?>
        <div>
          <dt class="text-gray-500">Perusahaan</dt>
          <dd class="text-gray-900 font-medium"><?= htmlspecialchars($buyer['perusahaan']) ?></dd>
        </div>
        <?php endif; ?>

        <?php if (!empty($buyer['catatan'])): ?>
        <div class="sm:col-span-2">
          <dt class="text-gray-500">Catatan Tambahan</dt>
          <dd class="text-gray-900 font-medium leading-relaxed whitespace-pre-line">
            <?= htmlspecialchars($buyer['catatan']) ?>
          </dd>
        </div>
        <?php endif; ?>
      </dl>

      <!-- Tombol Aksi -->
      <div class="flex flex-col sm:flex-row justify-end gap-3 mt-10">
        <a href="edit-buyer?id=<?= $buyer['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-yellow-400 hover:bg-yellow-500 font-medium transition focus:ring-4 focus:ring-yellow-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/edit-light.svg" class="w-5 h-5" alt="Edit">
          Edit Buyer
        </a>

        <a href="hapus-buyer?id=<?= $buyer['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 font-medium transition focus:ring-4 focus:ring-red-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/trash-light.svg" class="w-5 h-5" alt="Hapus">
          Hapus Buyer
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>