<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$error = "";
$success = "";

// Proses simpan data buyer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name    = trim($_POST["name"]);
  $address = trim($_POST["address"]);
  $contact = trim($_POST["contact"]);

  if ($name === "") {
    $error = "Nama buyer wajib diisi.";
  } else {
    $stmt = $conn->prepare("
      INSERT INTO buyer_products (name, address, contact, created_at)
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("sss", $name, $address, $contact);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: transaksi-produk"); // arahkan ke daftar buyer setelah simpan
      exit();
    } else {
      $error = "Gagal menyimpan data buyer.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Buyer</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="transaksi-produk" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Buyer</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    
    <?php if ($error): ?>
      <div class="p-3 bg-red-100 text-red-600 rounded-md text-sm"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div>
      <label for="name" class="block text-sm font-medium">Nama Buyer <span class="text-red-500">*</span></label>
      <input type="text" id="name" name="name" required
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Nama lengkap...">
    </div>

    <div>
      <label for="address" class="block text-sm font-medium">Alamat</label>
      <input type="text" id="address" name="address"
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Alamat buyer">
    </div>

    <div>
      <label for="contact" class="block text-sm font-medium">Kontak</label>
      <input type="text" id="contact" name="contact"
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Konak buyer">
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan</span>
      </button>
    </div>
  </form>
</main>

</body>
</html>