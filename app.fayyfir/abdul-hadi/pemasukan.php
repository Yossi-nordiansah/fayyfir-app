<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil user_id dari URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Ambil data user
$stmtUser = $conn->prepare("SELECT * FROM user_cash_flows WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

if (!$user) {
  echo "Data pengguna tidak ditemukan.";
  exit();
}

$success = $error = "";

// Handle form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $date        = $_POST["date"] ?? "";
  $description = $_POST["description"] ?? "";
  $debit_raw   = $_POST["debit"] ?? "";
  $debit       = (int) str_replace(".", "", $debit_raw); // hapus titik ribuan

  if (!$date || !$description || $debit <= 0) {
    $error = "Semua field wajib diisi dan jumlah harus lebih dari 0.";
  } else {
    $stmt = $conn->prepare("INSERT INTO cash_flows (date, user_id, description, debit, credit) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("sisi", $date, $user_id, $description, $debit);
    if ($stmt->execute()) {
      header("Location: rincian-utang-piutang?user_id=$user_id");
      exit();
    } else {
      $error = "Gagal menyimpan data.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tambah Pemasukan - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="rincian-utang-piutang?user_id=<?= $user_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Pemasukan</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-6 bg-white shadow rounded-lg p-6">
    <div>
      <label class="block text-sm font-medium">Nama</label>
      <input type="text" value="<?= htmlspecialchars($user['name']) ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
    </div>
    <div>
      <label class="block text-sm font-medium">Tanggal</label>
      <input type="date" name="date" value="<?= date("Y-m-d") ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>
    <div>
      <label class="block text-sm font-medium">Jumlah Credit</label>
      <input type="text" name="debit" class="w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRupiah(this)">
    </div>
    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded"></textarea>
    </div>

    <button type="submit" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full">
      <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">save</span>
      <span>Simpan</span>
    </button>
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