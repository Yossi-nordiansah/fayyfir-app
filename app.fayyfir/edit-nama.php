<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil ID dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$success = $error = "";

// Ambil data lama dari database (termasuk user_id asli)
$stmt = $conn->prepare("SELECT * FROM user_cash_flows WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
  echo "Data pengguna tidak ditemukan.";
  exit();
}

$user_id_cashflow = $id; // simpan user_id asli untuk redirect dan tombol kembali

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name    = $_POST["name"] ?? "";
  $phone   = $_POST["phone"] ?? "";
  $address = $_POST["address"] ?? "";

  if (!$name) {
    $error = "Minimal isi field Nama";
  } else {
    $stmt = $conn->prepare("UPDATE user_cash_flows SET name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $phone, $address, $id);
    if ($stmt->execute()) {
      header("Location: rincian-utang-piutang?user_id=$user_id_cashflow");
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
  <title>Edit Nama - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="rincian-utang-piutang?user_id=<?= $user_id_cashflow ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Edit Nama</h1>
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
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>
    <div>
      <label class="block text-sm font-medium">No HP</label>
      <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
    </div>
    <div>
      <label class="block text-sm font-medium">Alamat</label>
      <textarea name="address" class="w-full px-3 py-2 border border-gray-300 rounded"><?= htmlspecialchars($user['address']) ?></textarea>
    </div>

    <button type="submit" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full">
      <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">save</span>
      <span>Simpan</span>
    </button>
  </form>
</main>
</body>
</html>