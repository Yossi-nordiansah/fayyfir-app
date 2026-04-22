<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil ID transaksi dan user_id dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;

// Cek data user
$stmtUser = $conn->prepare("SELECT * FROM user_cash_flows WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$user) {
  echo "Data pengguna tidak ditemukan.";
  exit();
}

// Ambil data transaksi yang akan diedit
$stmt = $conn->prepare("SELECT * FROM cash_flows WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaksi) {
  echo "Data transaksi tidak ditemukan.";
  exit();
}

$success = $error = "";

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $date        = $_POST["date"] ?? "";
  $description = $_POST["description"] ?? "";
  $debit_raw   = $_POST["debit"] ?? "0";
  $credit_raw  = $_POST["credit"] ?? "0";

  $debit  = (int) str_replace(".", "", $debit_raw);
  $credit = (int) str_replace(".", "", $credit_raw);

  if (!$date || !$description) {
    $error = "Tanggal dan keterangan wajib diisi.";
  } else {
    $stmt = $conn->prepare("UPDATE cash_flows SET date = ?, description = ?, debit = ?, credit = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssiiii", $date, $description, $debit, $credit, $id, $user_id);
    if ($stmt->execute()) {
      header("Location: rincian-utang-piutang?user_id=$user_id");
      exit();
    } else {
      $error = "Gagal memperbarui data.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Saldo - Fayyfir</title>
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
    <h1 class="text-lg font-semibold">Edit Saldo</h1>
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
      <input type="date" name="date" value="<?= htmlspecialchars($transaksi['date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>
    <div>
      <label class="block text-sm font-medium">Jumlah Credit</label>
      <input type="text" name="debit" value="<?= number_format($transaksi['debit'], 0, ",", ".") ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRupiah(this)">
    </div>
    <div>
      <label class="block text-sm font-medium">Jumlah Debit</label>
      <input type="text" name="credit" value="<?= number_format($transaksi['credit'], 0, ",", ".") ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRupiah(this)">
    </div>
    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded"><?= htmlspecialchars($transaksi['description']) ?></textarea>
    </div>

    <button type="submit" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full">
      <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">save</span>
      <span>Simpan Perubahan</span>
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