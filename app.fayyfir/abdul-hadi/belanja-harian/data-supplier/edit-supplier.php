<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan koneksi DB alsz2632_ahadi

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

// Ambil data supplier dari database
$stmt = $conn->prepare("SELECT * FROM bb_supplier WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

if (!$supplier) {
  echo "<script>alert('Data supplier tidak ditemukan!'); window.location='index';</script>";
  exit();
}

// Proses update data supplier
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nama = trim($_POST["nama_supplier"]);
  $kontak = trim($_POST["kontak"]);
  $alamat = trim($_POST["alamat"]);
  $catatan = trim($_POST["catatan"]);

  // Validasi sederhana
  if (empty($nama) || empty($kontak) || empty($alamat)) {
    $error = "Semua field wajib diisi!";
  } else {
    $update = $conn->prepare("UPDATE bb_supplier SET nama_supplier=?, kontak=?, alamat=?, catatan=? WHERE id=?");
    $update->bind_param("ssssi", $nama, $kontak, $alamat, $catatan, $id);

    if ($update->execute()) {
      echo "<script>alert('Data supplier berhasil diperbarui!'); window.location='index';</script>";
      exit();
    } else {
      $error = "Terjadi kesalahan saat menyimpan perubahan.";
    }
  }
}

$activeMenu = "suppliers";
$activeModule = "Edit Supplier";
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
      <span>Kembali ke daftar supplier</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Edit Supplier</h1>
  </div>

  <!-- Notifikasi Error -->
  <?php if (isset($error)): ?>
    <div class="max-w-2xl mx-auto mb-6">
      <div class="flex items-center gap-2 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
          stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Form Edit Supplier -->
  <form method="POST"
    class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-100 p-6 sm:p-8">

    <div class="mb-5 border-b pb-4">
      <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M11 17a4 4 0 100-8 4 4 0 000 8zm0-8V5m0 12v2m6-6h2M3 11H1m16 8h2m-8-8V3" />
        </svg>
        Detail Supplier
      </h2>
      <p class="text-gray-500 mt-1 text-sm leading-relaxed">
        Ubah data supplier di bawah ini, lalu tekan <strong>Simpan Perubahan</strong> untuk menyimpan.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
      <!-- Nama Supplier -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier<span
            class="text-red-500">*</span></label>
        <input type="text" id="nama_supplier" name="nama_supplier"
          value="<?= htmlspecialchars($supplier['nama_supplier']) ?>"
          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
          placeholder="Contoh: CV Coklat Indah" required>
      </div>

      <!-- Kontak -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kontak<span
            class="text-red-500">*</span></label>
        <input type="text" id="kontak" name="kontak" value="<?= htmlspecialchars($supplier['kontak']) ?>"
          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
          placeholder="Misal: 089..." required>
      </div>
    </div>

    <!-- Alamat -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
      <textarea id="alamat" name="alamat" rows="3"
        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
        placeholder="Tambahkan alamat lengkap supplier jika diperlukan"><?= htmlspecialchars($supplier['alamat']) ?></textarea>
    </div>
    
    <!-- Keterangan -->
    <div class="mb-8">
      <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
      <textarea id="catatan" name="catatan" rows="3"
        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
        placeholder="Tambahkan keterangan jika diperlukan"><?= htmlspecialchars($supplier['catatan']) ?></textarea>
    </div>

    <!-- Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-end gap-3">

      <button type="submit"
        class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M5 13l4 4L19 7" />
        </svg>
        Simpan Perubahan
      </button>
      <a href="index"
        class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
          stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Batal
      </a>
    </div>
  </form>
</main>

<?php include "../partials/footer.php"; ?>