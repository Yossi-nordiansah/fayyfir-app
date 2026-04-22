<?php
session_start();
require "../../config.php";
$conn = $conn2;

require "../includes/helpers.php";
require "../includes/validation.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Ambil ID dari URL
$id_pengeluaran = isset($_GET['id_pengeluaran']) ? intval($_GET['id_pengeluaran']) : 0;
$id_penjualan   = isset($_GET['id_penjualan']) ? intval($_GET['id_penjualan']) : 0;

if ($id_pengeluaran <= 0 || $id_penjualan <= 0) {
    header("Location: list-penjualan.php?error=invalid_id");
    exit();
}

// Ambil data pengeluaran
$stmt = $conn->prepare("
    SELECT 
        p.*,
        j.no_invoice,
        j.tanggal_jual,
        b.nama_buyer
    FROM bb_pengeluaran p
    LEFT JOIN bb_penjualan j ON p.id_penjualan = j.id
    LEFT JOIN bb_buyer b ON j.id_buyer = b.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id_pengeluaran);
$stmt->execute();
$result = $stmt->get_result();
$pengeluaran = $result->fetch_assoc();
$stmt->close();

if (!$pengeluaran) {
    header("Location: detail-penjualan.php?id={$id_penjualan}&error=pengeluaran_not_found");
    exit();
}

$errors = [];

// Proses update form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $deskripsi_exp = trim($_POST["deskripsi_exp"] ?? "");
    $biaya_exp     = floatval($_POST["biaya_exp"] ?? 0);
    $tanggal_exp   = trim($_POST["tanggal_exp"] ?? date("Y-m-d"));
    $catatan_exp   = trim($_POST["catatan_exp"] ?? "");

    if ($deskripsi_exp === "") $errors[] = "Jenis pengeluaran wajib diisi.";
    if ($biaya_exp <= 0) $errors[] = "Biaya pengeluaran harus lebih besar dari 0.";

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE bb_pengeluaran
            SET deskripsi_exp = ?, biaya_exp = ?, tanggal_exp = ?, catatan_exp = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sdssi", $deskripsi_exp, $biaya_exp, $tanggal_exp, $catatan_exp, $id_pengeluaran);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: detail-penjualan.php?id={$id_penjualan}&updated=1");
            exit();
        } else {
            $errors[] = "Gagal memperbarui data pengeluaran: " . htmlspecialchars($stmt->error);
            $stmt->close();
        }
    }
}

$activeMenu   = "penjualan";
$activeModule = "Edit Pengeluaran";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">

  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="detail-penjualan.php?id=<?= htmlspecialchars($id_penjualan) ?>"
       class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" 
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke Detail Penjualan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Edit Pengeluaran
    </h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="max-w-3xl mx-auto mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
      <ul class="list-disc list-inside text-sm space-y-1">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200">
    <div class="p-6 sm:p-8">

      <!-- Info Pengeluaran -->
      <div class="mb-6 border-b pb-4">
        <h2 class="text-xl font-semibold text-gray-800">
          Invoice: <?= htmlspecialchars($pengeluaran['no_invoice']) ?>
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          Buyer: <?= htmlspecialchars($pengeluaran['nama_buyer']) ?>  
        </p>
        <p class="text-xs text-gray-500">Tanggal Jual: <?= htmlspecialchars($pengeluaran['tanggal_jual']) ?></p>
      </div>

      <form method="POST" class="space-y-5">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengeluaran</label>
          <input type="text" name="deskripsi_exp" id="deskripsi_exp"
              value="<?= htmlspecialchars($pengeluaran['deskripsi_exp']) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Biaya</label>
            <input type="number" step="0.01" name="biaya_exp"
              value="<?= htmlspecialchars($pengeluaran['biaya_exp']) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" name="tanggal_exp"
              value="<?= htmlspecialchars($pengeluaran['tanggal_exp']) ?>"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
          <textarea name="catatan_exp" rows="3"
              class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-yellow-500"><?= htmlspecialchars($pengeluaran['catatan_exp']) ?></textarea>
        </div>

        <div class="pt-4 flex justify-between flex-col sm:flex-row gap-3">
          <button type="submit"
            class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg shadow-sm">
            Simpan Perubahan
          </button>

          <a href="detail-penjualan.php?id=<?= htmlspecialchars($id_penjualan) ?>"
             class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-100">
            Batal
          </a>
        </div>

      </form>

    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>