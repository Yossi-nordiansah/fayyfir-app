<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

if (!isset($_GET['id'])) {
  header("Location: transaksi-rincian");
  exit();
}

$id = intval($_GET['id']);
$error = "";
$success = "";

// Ambil data buyer berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM buyer_products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$buyer = $result->fetch_assoc();
$stmt->close();

if (!$buyer) {
  header("Location: transaksi-rincian?id=$id");
  exit();
}

// Proses update data buyer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name    = trim($_POST["name"]);
  $address = trim($_POST["address"]);
  $contact = trim($_POST["contact"]);

  if ($name === "") {
    $error = "Nama buyer wajib diisi.";
  } else {
    $stmt = $conn->prepare("
      UPDATE buyer_products
      SET name = ?, address = ?, contact = ?
      WHERE id = ?
    ");
    $stmt->bind_param("sssi", $name, $address, $contact, $id);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: transaksi-rincian?id=$id"); // arahkan ke daftar buyer setelah update
      exit();
    } else {
      $error = "Gagal mengupdate data buyer.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Buyer</title>
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
    <h1 class="text-lg font-semibold">Edit Buyer</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    
    <?php if ($error): ?>
      <div class="p-3 bg-red-100 text-red-600 rounded-md text-sm"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div>
      <label for="name" class="block text-sm font-medium">Nama Buyer</label>
      <input type="text" id="name" name="name" required
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Nama lengkap..."
        value="<?= htmlspecialchars($buyer['name']) ?>">
    </div>

    <div>
      <label for="address" class="block text-sm font-medium">Alamat</label>
      <input type="text" id="address" name="address"
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Alamat buyer"
        value="<?= htmlspecialchars($buyer['address']) ?>">
    </div>

    <div>
      <label for="contact" class="block text-sm font-medium">Kontak</label>
      <input type="text" id="contact" name="contact"
        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-yellow-400 focus:border-yellow-400"
        placeholder="Kontak buyer"
        value="<?= htmlspecialchars($buyer['contact']) ?>">
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Update</span>
      </button>
    </div>
  </form>
</main>

</body>
</html>