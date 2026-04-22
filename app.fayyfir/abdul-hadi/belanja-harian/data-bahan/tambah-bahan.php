<?php
session_start();
require "../../config.php";
$conn = $conn2;

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$success = "";
$error = "";

// Proses input
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $nama_bahan = trim($_POST["nama_bahan"] ?? "");
  $nama_bahan = ucwords(strtolower($nama_bahan));
  $satuan = trim($_POST["satuan"] ?? "");
  $keterangan = trim($_POST["keterangan"] ?? "");

  if ($nama_bahan === "" || $satuan === "") {

    $error = "Nama bahan dan satuan wajib diisi.";

  } else {

    // ==========================
    // CEK DUPLIKAT
    // ==========================
    $check = $conn->prepare("
      SELECT id FROM bb_bahan_master
      WHERE nama_bahan = ?
      AND deleted_at IS NULL
    ");

    $check->bind_param("s", $nama_bahan);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

      $error = "Nama bahan sudah digunakan.";

    } else {

      // ==========================
      // INSERT DATA
      // ==========================
      $stmt = $conn->prepare("
        INSERT INTO bb_bahan_master
        (nama_bahan, satuan, keterangan, created_at)
        VALUES (?, ?, ?, NOW())
      ");

      $stmt->bind_param("sss", $nama_bahan, $satuan, $keterangan);

      if ($stmt->execute()) {

        $bahan_id = $stmt->insert_id;

        // ==========================
        // DATA BARU UNTUK LOG
        // ==========================
        $data_baru = json_encode([
          "nama_bahan" => $nama_bahan,
          "satuan" => $satuan,
          "keterangan" => $keterangan
        ]);

        // ==========================
        // SIMPAN AUDIT LOG
        // ==========================
        $log = $conn->prepare("
          INSERT INTO bb_bahan_log
          (bahan_id, user_id, aksi, data_lama, data_baru)
          VALUES (?, ?, 'create', NULL, ?)
        ");

        $log->bind_param(
          "iis",
          $bahan_id,
          $_SESSION["user_id"],
          $data_baru
        );

        $log->execute();

        $success = "Bahan baru berhasil ditambahkan.";

      } else {

        $error = "Terjadi kesalahan: " . $conn->error;

      }

    }

  }

}

$activeMenu = "materials";
$activeModule = "Tambah Bahan";

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
<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
</svg>
Kembali ke daftar bahan
</a>

<h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
Tambah Bahan Baru
</h1>

</div>

<!-- Notifikasi -->
<?php if ($success): ?>
<div class="mb-6 flex items-center gap-2 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
</svg>
<?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 flex items-center gap-2 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round"
d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9"/>
</svg>
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>


<!-- Form -->
<form method="POST"
class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition border border-gray-100 p-6 sm:p-8">

<div class="mb-6 border-b pb-4">
<h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">

<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none"
viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round"
d="M12 4v16m8-8H4"/>
</svg>

Form Tambah Bahan

</h2>

<p class="text-gray-500 mt-1 text-sm">
Masukkan bahan baru untuk kebutuhan produksi.
</p>

</div>


<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">

<!-- Nama Bahan -->
<div>

<label class="block text-sm font-medium text-gray-700 mb-1">
Nama Bahan <span class="text-red-500">*</span>
</label>

<input
type="text"
name="nama_bahan"
required
value="<?= htmlspecialchars($_POST['nama_bahan'] ?? '') ?>"
class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none"
placeholder="Misal: Arang Batok">

</div>


<!-- Satuan -->
<div>

<label class="block text-sm font-medium text-gray-700 mb-1">
Satuan <span class="text-red-500">*</span>
</label>

<input
type="text"
name="satuan"
list="list_satuan"
required
class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none"
placeholder="Misal: Kg">

<datalist id="list_satuan">
<option value="Kg">
<option value="Ton">
<option value="Gram">
<option value="Liter">
<option value="Karung">
<option value="Sak">
<option value="Box">
<option value="Pcs">
</datalist>

</div>

</div>


<!-- Keterangan -->
<div class="mb-8">

<label class="block text-sm font-medium text-gray-700 mb-1">
Keterangan
</label>

<textarea
name="keterangan"
rows="3"
class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none"
placeholder="Opsional"
></textarea>

</div>


<div class="flex flex-col sm:flex-row justify-end gap-3">

<button
type="submit"
class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition">

<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round"
d="M5 13l4 4L19 7"/>
</svg>

Simpan Bahan

</button>


<a
href="index"
class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">

<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round"
d="M15 19l-7-7 7-7"/>
</svg>

Batal

</a>

</div>

</form>

</main>

<?php include "../partials/footer.php"; ?>