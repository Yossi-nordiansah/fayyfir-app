<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}
require "config.php";

$level = $_SESSION["role_id"] ?? "";
if ($level != "2" && $level != "3") {
  header("Location: index");
  exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$back_bulan = $_GET['bulan'] ?? date('Y-m');

if (!$id) {
  header("Location: pengeluaran-bulanan-gaharu.php");
  exit();
}

// Ambil data yang akan diedit
$stmt_get = $conn->prepare("SELECT * FROM gaharu_monthly_expenses WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$row = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$row) {
  header("Location: pengeluaran-bulanan-gaharu.php");
  exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
  $bulan      = trim($_POST['bulan'] ?? '');
  $nama       = trim($_POST['nama_pengeluaran'] ?? '');
  $jenis      = $_POST['jenis'] ?? 'tidak_fix';
  $jumlah_raw = str_replace('.', '', $_POST['jumlah'] ?? '0');
  $jumlah     = (float)$jumlah_raw;
  $keterangan = trim($_POST['keterangan'] ?? '');

  if (!$bulan || !$nama || $jumlah <= 0) {
    $error = "Harap isi semua field wajib dengan benar.";
  } else {
    $stmt_upd = $conn->prepare(
      "UPDATE gaharu_monthly_expenses SET bulan=?, nama_pengeluaran=?, jenis=?, jumlah=?, keterangan=? WHERE id=?"
    );
    $stmt_upd->bind_param("sssdsi", $bulan, $nama, $jenis, $jumlah, $keterangan, $id);
    if ($stmt_upd->execute()) {
      header("Location: pengeluaran-bulanan-gaharu.php?bulan=" . urlencode($bulan));
      exit();
    } else {
      $error = "Gagal memperbarui data.";
    }
    $stmt_upd->close();
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Pengeluaran Bulanan Gaharu - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
      <a href="pengeluaran-bulanan-gaharu.php?bulan=<?= urlencode($back_bulan) ?>"
        class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span>Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Edit Pengeluaran Bulanan</h1>
      <span></span>
    </div>
  </header>

  <main class="pt-24 px-4 pb-16 max-w-3xl mx-auto">
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 border border-red-300 rounded p-3 mb-4 flex items-center space-x-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <section class="bg-white shadow rounded-lg p-6">
      <h2 class="text-base font-bold mb-6 flex items-center space-x-2 text-gray-800">
        <span class="material-symbols-outlined text-yellow-500">edit</span>
        <span>Edit Pengeluaran</span>
      </h2>
      <form method="POST" class="space-y-4">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Bulan <span class="text-red-500">*</span></label>
            <input type="month" name="bulan" value="<?= htmlspecialchars($row['bulan']) ?>" required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Jenis <span class="text-red-500">*</span></label>
            <select name="jenis" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm">
              <option value="fix" <?= $row['jenis'] === 'fix' ? 'selected' : '' ?>>Fix (Tetap — mis. Gaji)</option>
              <option value="tidak_fix" <?= $row['jenis'] === 'tidak_fix' ? 'selected' : '' ?>>Tidak Fix (Variabel)</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nama Pengeluaran <span class="text-red-500">*</span></label>
          <input type="text" name="nama_pengeluaran" value="<?= htmlspecialchars($row['nama_pengeluaran']) ?>" required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
          <input type="text" name="jumlah" id="inp_jumlah_edit"
            value="<?= number_format((float)$row['jumlah'], 0, ',', '.') ?>" required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Keterangan</label>
          <input type="text" name="keterangan" value="<?= htmlspecialchars($row['keterangan'] ?? '') ?>"
            placeholder="Opsional..."
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" name="update"
            class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold px-6 py-2 rounded transition flex items-center space-x-2">
            <span class="material-symbols-outlined text-base">save</span>
            <span>Simpan Perubahan</span>
          </button>
          <a href="pengeluaran-bulanan-gaharu.php?bulan=<?= urlencode($back_bulan) ?>"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded transition flex items-center space-x-2">
            <span class="material-symbols-outlined text-base">close</span>
            <span>Batal</span>
          </a>
        </div>
      </form>
    </section>
  </main>

  <script>
    const inp = document.getElementById('inp_jumlah_edit');
    if (inp) {
      inp.addEventListener('input', function(e) {
        let val = e.target.value.replace(/\./g, '').replace(/\D/g, '');
        e.target.value = val ? parseInt(val).toLocaleString('id-ID') : '';
      });
    }
  </script>
</body>

</html>
