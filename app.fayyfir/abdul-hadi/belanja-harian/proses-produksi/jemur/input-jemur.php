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

// Ambil ID proses jemur
if (!isset($_GET["id"])) {    
  header("Location: ../../pembelian-awal/index");    
  exit();
}
$id_jemur = intval($_GET["id"]);

// Ambil data jemur dan pembelian terkait
$query = "
  SELECT j.*, p.kode_batch, p.berat_awal, b.nama_bahan
  FROM bb_proses_jemur j
  JOIN bb_pembelian_awal p ON j.id_pembelian = p.id
  JOIN bb_bahan_master b ON p.id_bahan = b.id
  WHERE j.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_jemur);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<script>alert('Data proses tidak ditemukan!'); window.location='../../pembelian-awal/index.php';</script>";
  exit();
}

$jemur = $result->fetch_assoc();

// Proses update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $berat_setelah_jemur = floatval($_POST["berat_setelah_jemur"]);
  $penyusutan = ($jemur["berat_awal"] - $berat_setelah_jemur) / max($jemur["berat_awal"], 1) * 100;
  $tanggal_mulai = $_POST["tanggal_mulai"];
  $tanggal_selesai = $_POST["tanggal_selesai"];
  $keterangan = trim($_POST["keterangan"]);

  $update = "
    UPDATE bb_proses_jemur
    SET tanggal_mulai = ?, tanggal_selesai = ?, 
        berat_setelah_jemur = ?, penyusutan_jemur = ?, keterangan = ?
    WHERE id = ?
  ";
  $stmt_upd = $conn->prepare($update);
  $stmt_upd->bind_param("ssddsi", $tanggal_mulai, $tanggal_selesai, $berat_setelah_jemur, $penyusutan, $keterangan, $id_jemur);

  if ($stmt_upd->execute()) {
    echo "<script>alert('Data penyusutan berhasil diperbarui!'); window.location='list-jemur.php';</script>";
    exit();
  } else {
    $error = "Gagal memperbarui data penyusutan.";
  }
}

$activeMenu = "productions";
$activeModule = "Input Penyusutan";
include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-jemur.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar penyusutan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Edit Data Penyusutan - Batch <?= htmlspecialchars($jemur['kode_batch']) ?>
    </h1>
  </div>

  <!-- Card Form -->
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200">
    <div class="p-6 sm:p-8">
      <div class="mb-6 border-b pb-4">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 8v8m4-4H8m12 0a8 8 0 11-16 0 8 8 0 0116 0z" />
          </svg>
          Form Edit Penyusutan
        </h2>
        <p class="text-gray-500 mt-1 text-sm">
          Perbarui data hasil penyusutan bahan dari batch ini dengan teliti.
        </p>
      </div>

      <?php if (isset($error)): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="form-jemur" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bahan</label>
            <input type="text" value="<?= htmlspecialchars($jemur['nama_bahan']) ?>" disabled
              class="w-full bg-gray-100 text-gray-700 rounded-xl px-4 py-2 border border-gray-200 cursor-not-allowed">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Awal (kg)</label>
            <input type="number" value="<?= $jemur['berat_awal'] ?>" disabled
              class="w-full bg-gray-100 text-gray-700 rounded-xl px-4 py-2 border border-gray-200 cursor-not-allowed">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Setelah Proses (kg)</label>
            <input type="number" step="0.01" name="berat_setelah_jemur" id="berat_setelah_jemur"
              value="<?= htmlspecialchars($jemur['berat_setelah_jemur']) ?>" required
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Penyusutan (%)</label>
            <input type="text" id="penyusutan" disabled
              value="<?= number_format($jemur['penyusutan_jemur'], 2) ?> %"
              class="w-full bg-gray-100 text-gray-700 rounded-xl px-4 py-2 border border-gray-200 cursor-not-allowed">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai"
              value="<?= htmlspecialchars($jemur['tanggal_mulai']) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai"
              value="<?= htmlspecialchars($jemur['tanggal_selesai']) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
          <textarea name="keterangan" rows="3"
            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-600 transition"><?= htmlspecialchars($jemur['keterangan']) ?></textarea>
        </div>

        <div class="flex justify-between flex-col sm:flex-row gap-3 pt-4">
          <button type="submit"
            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M5 13l4 4L19 7" />
            </svg>
            Simpan Perubahan
          </button>
          <a href="list-jemur.php"
            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Batal
          </a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.getElementById('berat_setelah_jemur').addEventListener('input', function() {
  let awal = <?= $jemur['berat_awal'] ?>;
  let hasil = parseFloat(this.value) || 0;
  let susut = awal > 0 ? ((awal - hasil) / awal * 100).toFixed(2) : 0;
  document.getElementById('penyusutan').value = susut + ' %';
});
</script>

<?php include "../../partials/footer.php"; ?>