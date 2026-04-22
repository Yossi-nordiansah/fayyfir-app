<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$production_id = (int) $_GET["id"];
$production_name = $_GET["name"] ?? "";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $description = trim($_POST["description"] ?? "");
  $amount = (int) str_replace([".", ","], ["", ""], $_POST["amount"] ?? 0);

  if ($description === "") {
    $errors[] = "Deskripsi wajib diisi.";
  }
  if ($amount <= 0) {
    $errors[] = "Jumlah harus lebih dari 0.";
  }

  if (!$errors) {
    $stmt = $conn->prepare("INSERT INTO production_expenses (production_id, description, amount, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $production_id, $description, $amount);
    if ($stmt->execute()) {
      header("Location: produksi-proses.php?id=$production_id&name=" . urlencode($production_name));
      exit();
    } else {
      $errors[] = "Gagal menyimpan data. Coba lagi.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tambah Biaya Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="produksi-proses.php?id=<?= $production_id ?>&name=<?= urlencode($production_name) ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Biaya Produksi</h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-xl mx-auto">

  <form method="post" class="bg-white shadow-md rounded-lg p-6 space-y-6">
    <div>
      <label class="block text-sm font-medium">Nama Biaya</label>
      <input type="text" name="description" id="description" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Biaya listrik..." />
    </div>

    <div>
      <label class="block text-sm font-medium">Jumlah (Rp)</label>
      <input type="text" id="harga" name="amount" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Nomimlnal biaya..." oninput="formatRibuan(this)" />
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan</span>
      </button>
    </div>
  </form>
</main>

<script>
const formatter = new Intl.NumberFormat("id-ID");

function formatRibuan(input) {
  const v = input.value.replace(/\D/g, "");
  input.value = formatter.format(v);
}

function unformat(v) {
  return parseInt(v.replace(/\./g,"")) || 0;
}
</script>
</body>
</html>