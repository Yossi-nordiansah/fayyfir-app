<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$container_id = isset($_GET["container_id"])
  ? intval($_GET["container_id"])
  : 0;
if ($container_id === 0) {
  echo "Kontainer tidak ditemukan.";
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $expense_date = $_POST["expense_date"];
  $expense_type = $_POST["expense_type"];
  $amount = str_replace('.', '', $_POST["amount"]); // hilangkan titik
  $notes = $_POST["notes"];
  $created_by = $_SESSION["user_id"];

  $stmt = $conn->prepare(
    "INSERT INTO expenses (container_id, expense_date, expense_type, amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)"
  );
  $stmt->bind_param(
    "issdsi",
    $container_id,
    $expense_date,
    $expense_type,
    $amount,
    $notes,
    $created_by
  );

  if ($stmt->execute()) {
    header("Location: riwayat-kontainer.php?id=" . $container_id);
    exit();
  } else {
    echo "Gagal menyimpan pengeluaran.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Pengeluaran - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-icons text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Pengeluaran</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Tanggal Pengeluaran</label>
      <input type="date" name="expense_date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= date(
        "Y-m-d"
      ) ?>" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Jenis Pengeluaran</label>
      <input type="text" name="expense_type" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Solar, Tol, Makan" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Jumlah (Rp)</label>
      <input type="text" id="jumlah" name="amount" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Catatan</label>
      <textarea name="notes" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Opsional"></textarea>
    </div>
    
    <div class="text-right">
      <button type="submit" class="bg-yellow-400 text-white font-semibold px-4 py-2 rounded hover:bg-yellow-500">
        Simpan Pengeluaran
      </button>
    </div>
  </form>
</main>

<!-- Format input angka -->
<script>
  document.getElementById("jumlah").addEventListener("input", function(e) {
    let value = e.target.value.replace(/\./g, "").replace(/\D/g, "");
    if (value !== "") {
      e.target.value = parseInt(value).toLocaleString("id-ID");
    } else {
      e.target.value = "";
    }
  });
</script>
</body>
</html>