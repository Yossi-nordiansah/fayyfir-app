<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$expense_id = (int) ($_GET["expense_id"] ?? 0);
$production_id = (int) ($_GET["id"] ?? 0);
$production_name = $_GET["name"] ?? "";

$errors = [];

$stmt = $conn->prepare("SELECT description, amount FROM production_expenses WHERE id = ?");
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$stmt->bind_result($current_description, $current_amount);
if (!$stmt->fetch()) {
  echo "Data tidak ditemukan.";
  exit();
}
$stmt->close();

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
    $stmt = $conn->prepare("UPDATE production_expenses SET description = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("sii", $description, $amount, $expense_id);
    if ($stmt->execute()) {
      header("Location: produksi-proses.php?id=$production_id&name=" . urlencode($production_name));
      exit();
    } else {
      $errors[] = "Gagal memperbarui data. Coba lagi.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Biaya Produksi</title>
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
    <h1 class="text-lg font-semibold">Edit Biaya Produksi</h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <ul class="list-disc list-inside text-sm">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white shadow-md rounded-lg p-6 space-y-6">
    <div>
      <label class="block text-sm font-medium">Nama Biaya</label>
      <input type="text" name="description" id="description" value="<?= htmlspecialchars($_POST["description"] ?? $current_description) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Biaya listrik..." />
    </div>

    <div>
      <label class="block text-sm font-medium">Jumlah (Rp)</label>
      <input type="text" id="harga" name="amount" value="<?= number_format($_POST["amount"] ?? $current_amount, 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Nominal biaya..." oninput="formatRibuan(this)" />
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan Perubahan</span>
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
</script>
</body>
</html>