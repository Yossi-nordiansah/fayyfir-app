<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $product_name = trim($_POST["nama_produk"]);

  if ($product_name !== "") {
    $stmt = $conn->prepare("INSERT INTO products (name) VALUES (?)");
    $stmt->bind_param("s", $product_name);

    if ($stmt->execute()) {
      header("Location: tambah-kontainer.php");
      exit();
    } else {
      $error = "Gagal menambahkan produk.";
    }
  } else {
    $error = "Nama produk tidak boleh kosong.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Jenis Produk - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="tambah-kontainer.php" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Tambah Jenis Produk</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
      <div>
        <label class="block text-sm font-medium">Nama Produk</label>
        <input type="text" name="nama_produk" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <?php if (isset($error)): ?>
        <div class="text-red-600 text-sm"><?= $error ?></div>
      <?php endif; ?>

      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md transition flex items-center justify-center space-x-2">
        <span class="material-symbols-outlined">check_circle</span>
        <span>Simpan Produk</span>
      </button>
    </form>
  </main>

  <!-- Bottom Padding for Mobile -->
  <div class="lg:hidden h-24"></div>

</body>
</html>