<?php
session_start();
require "../../config.php";
$conn = $conn2;

require "../includes/helpers.php";
require "../includes/validation.php";

// User must login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// GET penjualan ID
$id_penjualan = isset($_GET['id_penjualan']) ? intval($_GET['id_penjualan']) : 0;
if ($id_penjualan <= 0) {
  header("Location: detail-penjualan?error=invalid_id");
  exit();
}

// Get penjualan info
$stmt = $conn->prepare("
  SELECT 
    pj.id,
    pj.no_invoice,
    pj.tanggal_jual,
    pj.berat_jual,
    pj.total_penjualan,
    pj.harga_jual_per_kg,
    bj.nama_buyer
  FROM bb_penjualan pj
  LEFT JOIN bb_buyer bj ON pj.id_buyer = bj.id
  WHERE pj.id = ?
");
$stmt->bind_param("i", $id_penjualan);
$stmt->execute();
$result = $stmt->get_result();
$penjualan = $result->fetch_assoc();
$stmt->close();

if (!$penjualan) {
  header("Location: list-penjualan.php?error=penjualan_not_found");
  exit();
}

$errors = [];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $deskripsi_exp = trim($_POST["deskripsi_exp"] ?? "");
  $biaya_exp = floatval($_POST["biaya_exp"] ?? 0);
  $tanggal_exp = trim($_POST["tanggal_exp"] ?? date("Y-m-d"));
  $catatan_exp = trim($_POST["catatan_exp"] ?? "");

  if ($deskripsi_exp === "") $errors[] = "Jenis pengeluaran wajib diisi.";
  if ($biaya_exp <= 0)     $errors[] = "Biaya pengeluaran harus lebih besar dari 0.";

  if (empty($errors)) {
    $stmt = $conn->prepare("
      INSERT INTO bb_pengeluaran (id_penjualan, deskripsi_exp, biaya_exp, tanggal_exp, catatan_exp)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isdss", $id_penjualan, $deskripsi_exp, $biaya_exp, $tanggal_exp, $catatan_exp);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: detail-penjualan?id={$id_penjualan}&success=1");
      exit();
    } else {
      $errors[] = "Gagal menyimpan data pengeluaran: " . htmlspecialchars($stmt->error);
      $stmt->close();
    }
  }
}

$activeMenu  = "sales";
$activeModule = "Tambah Pengeluaran";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">

  <!-- Navigation -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="detail-penjualan.php?id=<?= $id_penjualan ?>"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 text-sm font-medium transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke detail penjualan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Tambah Pengeluaran Penjualan
    </h1>
  </div>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
    <div class="max-w-3xl mx-auto mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
      <ul class="list-disc list-inside text-sm space-y-1">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>


  <!-- Card Wrapper -->
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200">
    <div class="p-6 sm:p-8">

      <!-- Penjualan Summary -->
      <div class="mb-6 pb-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Invoice <?= htmlspecialchars($penjualan['no_invoice']) ?>
          </h2>

          <p class="text-sm text-gray-500 mt-1">
            Tambah pengeluaran terkait transaksi ini
          </p>
        </div>

        <div class="mt-3 sm:mt-0 px-3 py-1.5 text-sm font-medium bg-blue-100 text-blue-700
                    rounded-full border border-blue-200">
          <?= htmlspecialchars($penjualan['nama_buyer']) ?>
        </div>
      </div>


      <!-- FORM -->
      <form method="POST" class="space-y-5">

        <!-- Jenis Pengeluaran -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengeluaran</label>
          <input type="text" name="deskripsi_exp" id="deskripsi_exp"
            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 transition"
            placeholder="Contoh: Biaya angkut, upah tenaga, konsumsi..." required>
        </div>

        <!-- Grid: Biaya + Tanggal -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Pengeluaran</label>
            <input type="number" step="0.01" name="biaya_exp" id="biaya_exp"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 transition"
              placeholder="0.00" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengeluaran</label>
            <input type="date" name="tanggal_exp" id="tanggal_exp"
              value="<?= date('Y-m-d') ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 transition">
          </div>
        </div>

        <!-- Catatan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
          <textarea name="catatan_exp" id="catatan_exp" rows="3"
            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 transition"
            placeholder="Tambahkan catatan tambahan jika diperlukan..."></textarea>
        </div>

        <!-- Actions -->
        <div class="pt-4 flex justify-between flex-col sm:flex-row gap-3">
          <button type="submit"
            class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-2.5 
                   bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
              viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Simpan Pengeluaran
          </button>

          <a href="detail-penjualan.php?id=<?= $id_penjualan ?>"
            class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-2.5 
                   border border-gray-300 text-gray-700 hover:bg-gray-100 font-medium 
                   rounded-lg shadow-sm transition">
            Batal
          </a>
        </div>

      </form>
    </div>
  </div>

</main>

<?php include "../partials/footer.php"; ?>