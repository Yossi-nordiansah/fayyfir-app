<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$expense_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($expense_id === 0) {
  echo "ID Pengeluaran tidak ditemukan.";
  exit();
}

// Ambil data lama
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();

if (!$expense) {
  echo "Data pengeluaran tidak ditemukan.";
  exit();
}

$container_id = $expense['container_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $expense_date = $_POST["expense_date"];
  $expense_type = $_POST["expense_type"];
  $amount = str_replace('.', '', $_POST["amount"]);
  $notes = $_POST["notes"];
  $created_by = $_SESSION["user_id"];

  $stmt = $conn->prepare("UPDATE expenses SET expense_date = ?, expense_type = ?, amount = ?, notes = ?, created_by = ? WHERE id = ?");
  $stmt->bind_param("ssdsii", $expense_date, $expense_type, $amount, $notes, $created_by, $expense_id);

  if ($stmt->execute()) {
    header("Location: riwayat-kontainer.php?id=" . $container_id);
    exit();
  } else {
    echo "Gagal mengupdate pengeluaran.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Pengeluaran - Fayyfir</title>
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
    <h1 class="text-lg font-semibold">Edit Pengeluaran</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Tanggal Pengeluaran</label>
      <input type="date" name="expense_date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($expense['expense_date']) ?>" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Jenis Pengeluaran</label>
      <input type="text" name="expense_type" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($expense['expense_type']) ?>" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Jumlah (Rp)</label>
      <input type="text" id="jumlah" name="amount" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($expense['amount'], 0, ',', '.') ?>" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Catatan</label>
      <textarea name="notes" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Opsional"><?= htmlspecialchars($expense['notes']) ?></textarea>
    </div>
    
    <div class="text-right">
      <button type="submit" class="bg-yellow-400 text-white font-semibold px-4 py-2 rounded hover:bg-yellow-500">
        Update Pengeluaran
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