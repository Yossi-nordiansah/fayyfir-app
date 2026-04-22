<?php
session_start();
require "../../../config.php";
$conn = $conn2;

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../../login");
    exit();
}

// Ambil id dari query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
    header("Location: list-jemur.php");
    exit();
}

// Konfirmasi penghapusan via GET parameter confirm
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

if ($confirm === 'yes') {
    // Proses hapus data
    $stmt = $conn->prepare("DELETE FROM bb_proses_jemur WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: list-jemur.php?msg=hapus-success");
        exit();
    } else {
        die("Gagal menghapus data.");
    }
} else {
    // Ambil data jemur untuk ditampilkan pada konfirmasi
    $stmt = $conn->prepare("
        SELECT pj.id, pa.kode_batch, b.nama_bahan
        FROM bb_proses_jemur pj
        JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
        JOIN bb_bahan_master b ON pa.id_bahan = b.id
        WHERE pj.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0){
        header("Location: list-jemur.php?msg=not-found");
        exit();
    }
    $data = $result->fetch_assoc();
}

$activeMenu = "productions";
$activeModule = "Hapus Proses";
include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-jemur.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar penyusutan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Hapus Data Proses</h1>
  </div>

  <!-- Card Konfirmasi -->
  <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-100">
    <div class="p-6 sm:p-8">
      <div class="mb-5 border-b pb-4">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
          </svg>
          Konfirmasi Penghapusan
        </h2>
        <p class="text-gray-500 mt-1 text-sm leading-relaxed">
          Penghapusan data penyusutan ini bersifat permanen dan tidak dapat dibatalkan. Pastikan
          kamu telah memeriksa data dengan benar sebelum melanjutkan.
        </p>
      </div>

      <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-gray-500">Kode Batch</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($data['kode_batch']) ?></dd>
          </div>
          <div>
            <dt class="text-gray-500">Nama Bahan</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($data['nama_bahan']) ?></dd>
          </div>
        </dl>
      </div>

      <!-- Tombol Aksi -->
      <div class="mt-8 flex flex-col sm:flex-row gap-3">
        <a href="hapus-jemur.php?id=<?= $id ?>&confirm=yes"
          class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-200 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12" />
          </svg>
          Ya, Hapus Sekarang
        </a>

        <a href="list-jemur.php"
          class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M10 14L4 8m0 0l6-6m-6 6h16" />
          </svg>
          Batal
        </a>
      </div>
    </div>
  </div>
</main>

<?php include "../../partials/footer.php"; ?>