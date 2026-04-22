<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// pastikan ada id
if (!isset($_GET["id"])) {
  header("Location: produksi");
  exit();
}

$id = intval($_GET["id"]);

// ambil data lama + join produk
$stmt = $conn->prepare("
  SELECT p.*, ps.product_name 
  FROM productions p 
  JOIN product_stocks ps ON p.product_id = ps.id 
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$production = $result->fetch_assoc();
$stmt->close();

if (!$production) {
  echo "Data tidak ditemukan.";
  exit();
}

// ambil daftar produk untuk dropdown
$product_list = [];
$res = $conn->query("SELECT id, product_name FROM product_stocks ORDER BY product_name ASC");
while ($row = $res->fetch_assoc()) {
  $product_list[] = $row;
}

// jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $product_name = $_POST["product_name"];
  $product_id = null;

  // Jika user memilih tambah produk baru
  if ($product_name === "__new__" && !empty($_POST["new_product_name"])) {
    $product_name = $_POST["new_product_name"];
    $unit_id = 1; // default unit (nanti bisa diubah sesuai kebutuhan)

    // insert produk baru ke product_stocks
    $stmt = $conn->prepare("INSERT INTO product_stocks (product_name, quantity, unit_id) VALUES (?, 0, ?)");
    $stmt->bind_param("si", $product_name, $unit_id);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();
  } else {
    // cari ID produk lama
    $stmt = $conn->prepare("SELECT id FROM product_stocks WHERE product_name = ? LIMIT 1");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $stmt->bind_result($product_id);
    $stmt->fetch();
    $stmt->close();
  }

  $production_date = $_POST["production_date"];

  // update tabel productions
  $stmt = $conn->prepare("UPDATE productions SET product_id=?, production_date=? WHERE id=?");
  $stmt->bind_param("isi", $product_id, $production_date, $id);

  if ($stmt->execute()) {
    $stmt->close();
    header("Location: produksi");
    exit();
  } else {
    echo "Gagal mengupdate data produksi: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="produksi" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Edit Produksi</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">

    <!-- Nama Produk -->
    <div>
      <label class="block text-sm font-medium">Nama Produk</label>
      <select id="productSelect" name="product_name" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" required>
        <option value="">-- Pilih Produk --</option>
        <?php foreach ($product_list as $prod): ?>
          <option value="<?= htmlspecialchars($prod['product_name']) ?>" 
            <?= $prod['id'] == $production["product_id"] ? "selected" : "" ?>>
            <?= htmlspecialchars($prod['product_name']) ?>
          </option>
        <?php endforeach; ?>
        <option value="__new__">Tambah baru...</option>
      </select>
      <input type="text" id="newProductInput" name="new_product_name" 
        class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md hidden" 
        placeholder="Nama produk baru..." />
    </div>

    <!-- Tanggal Produksi -->
    <div>
      <label class="block text-sm font-medium">Tanggal Produksi</label>
      <input type="date" name="production_date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" 
        value="<?= htmlspecialchars($production["production_date"]) ?>" required />
    </div>

    <!-- Nomor Produksi (readonly) -->
    <div>
      <label class="block text-sm font-medium">Nomor Produksi</label>
      <input type="text" readonly class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" 
        value="<?= htmlspecialchars($production["production_number"]) ?>" />
      <small class="text-xs text-gray-400">Nomor produksi tidak bisa diubah.</small>
    </div>

    <!-- Tombol -->
    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan Perubahan</span>
      </button>
    </div>
  </form>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const productSelect = document.getElementById("productSelect");
  const newProductInput = document.getElementById("newProductInput");

  productSelect.addEventListener("change", function() {
    if (this.value === "__new__") {
      newProductInput.classList.remove("hidden");
      newProductInput.setAttribute("required", "required");
    } else {
      newProductInput.classList.add("hidden");
      newProductInput.removeAttribute("required");
    }
  });
});
</script>

</body>
</html>