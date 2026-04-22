<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $tanggal = $_POST["tanggal"] ?? "";
  $deskripsi = $_POST["deskripsi"] ?? "";
  $nilai = str_replace(".", "", $_POST["nilai"] ?? "");

  if (!$tanggal || !$deskripsi || !$nilai) {
    $error = "Semua field wajib diisi.";
  } elseif (!is_numeric($nilai)) {
    $error = "Nilai modal harus berupa angka.";
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO modal_log (date, description, amount, created_by) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
      "ssdi",
      $tanggal,
      $deskripsi,
      $nilai,
      $_SESSION["user_id"]
    );

    if ($stmt->execute()) {
      header("Location: modal-dan-aset");
      exit();
    } else {
      echo "Gagal menyimpan modal.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Modal - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="modal-dan-aset" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Tambah Modal</h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-xl mx-auto">
    <?php if ($success): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 border border-red-300 rounded"><?= $error ?></div>
    <?php endif; ?>

    <form action="" method="POST" class="bg-white p-6 rounded shadow space-y-4">
      <div>
        <label for="tanggal" class="block text-sm font-medium">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= date(
        "Y-m-d"
      ) ?>"
          class="w-full border border-gray-300 px-3 py-2 rounded mt-1" />
      </div>
      <div>
        <label for="deskripsi" class="block text-sm font-medium">Deskripsi</label>
        <input type="text" id="deskripsi" name="deskripsi" placeholder="Contoh: Modal tambahan dari investor"
          class="w-full border border-gray-300 px-3 py-2 rounded mt-1" />
      </div>
      <div>
        <label for="nilai" class="block text-sm font-medium">Jumlah Modal (Rp)</label>
        <input type="text" id="nilai" name="nilai" placeholder="Contoh: 5000000"
          class="w-full border border-gray-300 px-3 py-2 rounded mt-1" oninput="formatRupiah(this)" />
      </div>
      <div class="flex justify-between items-center pt-2">
        <button type="submit"
          class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2 rounded">Simpan</button>
        <a href="modal-dan-aset" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
      </div>
    </form>
  </main>

  <script>
    function formatRupiah(input) {
      let value = input.value.replace(/\D/g, "");
      input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
  </script>
</body>
</html>