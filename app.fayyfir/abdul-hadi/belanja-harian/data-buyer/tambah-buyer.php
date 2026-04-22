<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan DB alsz2632_ahadi

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

// Inisialisasi variabel
$nama = $kontak = $email = $alamat = "";
$errors = [];
$success = "";

// Proses submit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nama = trim($_POST["nama_buyer"]);
  $kontak = trim($_POST["kontak"]);
  $alamat = trim($_POST["alamat"]);
  $catatan = trim($_POST["catatan"]);

  // Validasi server-side
  if (empty($nama)) $errors[] = "Nama buyer wajib diisi.";

  // Jika lolos validasi → simpan ke DB
  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO bb_buyer (nama_buyer, kontak, alamat, catatan, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nama, $kontak, $alamat, $catatan);

    if ($stmt->execute()) {
      $success = "Data buyer berhasil disimpan!";
      $nama = $kontak = $email = $alamat = ""; // reset field
    } else {
      $errors[] = "Gagal menyimpan data buyer. Coba lagi.";
    }
    $stmt->close();
  }
}

$activeMenu = "buyers";
$activeModule = "Tambah Buyer";
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

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Tambah Buyer Baru</h1>
  </div>

  <!-- Notifikasi -->
  <?php if ($success): ?>
    <div
      class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 shadow-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
      <span><?= htmlspecialchars($success); ?></span>
    </div>
  <?php elseif ($error): ?>
    <div
      class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 shadow-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
      </svg>
      <span><?= htmlspecialchars($error); ?></span>
    </div>
  <?php endif; ?>
  
  <!-- Card Form -->
  <div
    class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-100">
    <form method="POST" class="p-6 sm:p-8">
      <div class="mb-6 border-b pb-4">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 4v16m8-8H4" />
          </svg>
          Form Tambah Buyer
        </h2>
        <p class="text-gray-500 mt-1 text-sm leading-relaxed">
          Lengkapi informasi buyer di bawah ini dengan teliti untuk memastikan data produksi akurat.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <!-- Nama Buyer -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nama Buyer
            <span class="text-red-500">*</span></label>
          <input type="text" id="nama_buyer" name="nama_buyer" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
            placeholder="Nama lengkap buyer">
        </div>

        <!-- Nomor Kontak -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Kontak
            <span class="text-red-500">*</span></label>
          <input type="text" name="kontak" id="kontak" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
            placeholder="Nomor kontak buyer">
        </div>
      </div>

      <!-- Alamat -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
        <textarea name="alamat" id="alamat" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
          placeholder="Alamat lengkap buyer"></textarea>
      </div>
      
      <!-- Keterangan -->
      <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
        <textarea name="catatan" id="catatan" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
          placeholder="Tambahkan keterangan"></textarea>
      </div>

      <!-- Tombol Aksi -->
      <div class="flex flex-col sm:flex-row justify-between gap-3">
        <button type="submit"
          class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5 13l4 4L19 7" />
          </svg>
          Simpan Bahan
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
  </div>
</main>

<!-- Validasi Client-side -->
<script>
function validateBuyerForm() {
  const nama = document.getElementById("nama").value.trim();
  const kontak = document.getElementById("kontak").value.trim();
  const email = document.getElementById("email").value.trim();
  const alamat = document.getElementById("alamat").value.trim();

  if (!nama || !kontak || !email || !alamat) {
    alert("Semua field wajib diisi!");
    return false;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    alert("Format email tidak valid!");
    return false;
  }

  return true;
}
</script>

<?php include "../partials/footer.php"; ?>