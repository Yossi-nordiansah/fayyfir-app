<?php
session_start();
require "../../../config.php";
$conn = $conn2;

require "../../includes/helpers.php";
require "../../includes/validation.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../../login");
  exit();
}

// Ambil ID pembelian dari URL
$id_pembelian = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_pembelian === 0) {
  header("Location: list-sortir.php?error=invalid_id");
  exit();
}

// Ambil data batch dari database
$stmt = $conn->prepare("
  SELECT pa.id, pa.kode_batch, b.nama_bahan, pk.berat_setelah_kupas, ps.*
  FROM bb_pembelian_awal pa
  LEFT JOIN bb_proses_kupas pk ON pa.id = pk.id_pembelian
  LEFT JOIN bb_proses_sortir ps ON pa.id = ps.id_pembelian
  LEFT JOIN bb_bahan_master b ON pa.id_bahan = b.id
  WHERE ps.id = ?
");
$stmt->bind_param("i", $id_pembelian);
$stmt->execute();
$result = $stmt->get_result();
$batch = $result->fetch_assoc();
$stmt->close();

if (!$batch) {
  header("Location: list-sortir.php?error=batch_not_found");
  exit();
}

// Inisialisasi error
$errors = [];

// Proses form (update)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $berat_akhir = trim($_POST["berat_akhir"] ?? "");
  $lokasi_simpan = trim($_POST["lokasi_simpan"] ?? "");
  $catatan = trim($_POST["catatan"] ?? "");
  $tanggal_simpan = trim($_POST["tanggal_simpan"] ?? date('Y-m-d'));
  $penyusutan_total = floatval($_POST["penyusutan_total"] ?? 0);

  // Validasi input
  $errors[] = validate_required($berat_akhir, "Berat akhir");
  $errors[] = validate_numeric($berat_akhir, "Berat akhir");
  $errors[] = validate_required($lokasi_simpan, "Lokasi simpan");
  $errors[] = validate_date($tanggal_simpan, "Tanggal simpan");

  // Filter kosong
  $errors = array_filter($errors);

  if (empty($errors)) {
    // Pastikan berat disimpan sebagai float
    $berat_akhir = floatval($berat_akhir);

    $stmt = $conn->prepare("
      UPDATE bb_proses_sortir 
      SET berat_akhir = ?, lokasi_simpan = ?, catatan = ?, tanggal_simpan = ?, penyusutan_total = ?
      WHERE id = ?
    ");
    if (!$stmt) {
      die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    // Binding data (d = double, s = string, i = integer)
    $stmt->bind_param("dsssdi", $berat_akhir, $lokasi_simpan, $catatan, $tanggal_simpan, $penyusutan_total, $id_pembelian);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: list-sortir.php?success=1");
      exit();
    } else {
      $errors[] = "Gagal memperbarui data sortir: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
  }
}

$activeMenu = "productions";
$activeModule = "Update Penyusutan & Simpan";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-sortir.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
        stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar penyimpanan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Update Susut & Simpan
    </h1>
  </div>

  <!-- Error Messages -->
  <?php if (!empty($errors)): ?>
    <div class="max-w-3xl mx-auto mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
      <ul class="list-disc list-inside text-sm space-y-1">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Card Form -->
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200">
    <div class="p-6 sm:p-8">
      <!-- Batch Info -->
      <div class="mb-6 border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Batch <?= htmlspecialchars($batch['kode_batch']) ?></h2>
          <p class="text-sm text-gray-500 mt-1">Update hasil susut dan penyimpanan bahan</p>
        </div>
        <div class="mt-3 sm:mt-0 px-3 py-1.5 text-sm font-medium bg-blue-100 text-blue-700 rounded-full border border-blue-200">
          <?= htmlspecialchars($batch['nama_bahan']) ?>
        </div>
      </div>

      <!-- Form Update -->
      <form method="POST" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Simpan</label>
            <input type="date" name="tanggal_simpan"
              value="<?= htmlspecialchars(date('Y-m-d', strtotime($batch['tanggal_simpan'] ?? date('Y-m-d')))) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Sebelumnya (kg)</label>
            <input type="number" value="<?= htmlspecialchars($batch['berat_setelah_kupas'] ?? '-') ?>" disabled
              class="w-full bg-gray-100 text-gray-700 rounded-xl px-4 py-2 border border-gray-200 cursor-not-allowed">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Saat ini (kg)</label>
            <input type="number" step="0.01" name="berat_akhir" id="berat_akhir"
              value="<?= htmlspecialchars($batch['berat_akhir'] ?? '') ?>" required
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Penyusutan (%)</label>
            <input type="text" name="penyusutan_total" id="penyusutan_total" readonly
              value="<?= number_format($batch['penyusutan_total'] ?? 0, 2) ?> %"
              class="w-full bg-gray-100 text-gray-700 rounded-xl px-4 py-2 border border-gray-200 cursor-not-allowed">
          </div>

          <div>
            <label for="lokasi_simpan" class="block text-sm font-medium text-gray-700 mb-1">Lokasi Simpan</label>
            <input type="text" name="lokasi_simpan" id="lokasi_simpan"
              value="<?= htmlspecialchars($batch['lokasi_simpan'] ?? '') ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>
        </div>

        <div>
          <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
          <textarea name="catatan" id="catatan" rows="3"
            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition"><?= htmlspecialchars($batch['catatan'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 flex justify-between flex-col sm:flex-row gap-3">
          <button type="submit"
            class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Simpan Perubahan
          </button>

          <a href="list-sortir.php"
            class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-100 font-medium rounded-lg shadow-sm transition">
            Batal
          </a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.getElementById('berat_akhir').addEventListener('input', function() {
  const awal = parseFloat("<?= $batch['berat_setelah_kupas'] ?? 0 ?>") || 0;
  const hasil = parseFloat(this.value) || 0;
  const susut = awal > 0 ? ((awal - hasil) / awal * 100).toFixed(2) : 0;
  document.getElementById('penyusutan_total').value = susut + ' %';
});
</script>

<?php include "../../partials/footer.php"; ?>