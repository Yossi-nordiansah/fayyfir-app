<?php
session_start();
require "../../config.php";
$conn = $conn2; // koneksi database alsz2632_ahadi

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

// Ambil data buyer
$query = $conn->prepare("SELECT * FROM bb_buyer WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$buyer = $result->fetch_assoc();

if (!$buyer) {
  header("Location: index");
  exit();
}

// Proses hapus
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["konfirmasi_hapus"])) {
  $stmt = $conn->prepare("DELETE FROM bb_buyer WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();

  header("Location: index?delete=success");
  exit();
}

$activeMenu = "buyers";
$activeModule = "Hapus Buyer";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="index"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar buyer</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Hapus Buyer</h1>
  </div>

  <!-- Card Konfirmasi -->
  <div
    class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-100">
    <div class="p-6 sm:p-8">
      <div class="mb-5 border-b pb-4">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
          </svg>
          Konfirmasi Penghapusan Buyer
        </h2>
        <p class="text-gray-500 mt-1 text-sm leading-relaxed">
          Tindakan ini akan menghapus data buyer secara permanen dari sistem. Pastikan kamu telah memeriksa
          data dengan benar sebelum melanjutkan.
        </p>
      </div>

      <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-gray-500">Nama Buyer</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($buyer['nama_buyer']) ?></dd>
          </div>
          <div>
            <dt class="text-gray-500">Kontak</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($buyer['kontak'] ?: '-') ?></dd>
          </div>
          <div>
            <dt class="text-gray-500">Alamat</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($buyer['alamat'] ?: '-') ?></dd>
          </div>
          <?php if (!empty($buyer['email'])): ?>
          <div>
            <dt class="text-gray-500">Email</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($buyer['email']) ?></dd>
          </div>
          <?php endif; ?>
          <?php if (!empty($buyer['catatan'])): ?>
          <div class="sm:col-span-2">
            <dt class="text-gray-500">Catatan</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($buyer['catatan']) ?></dd>
          </div>
          <?php endif; ?>
        </dl>
      </div>

      <!-- Tombol Aksi -->
      <form method="POST" class="mt-8 flex flex-col sm:flex-row gap-3 justify-between">
        <button type="submit" name="konfirmasi_hapus"
          class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-200 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12" />
          </svg>
          Ya, Hapus Sekarang
        </button>
        <a href="index"
          class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Batal
        </a>
      </form>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>