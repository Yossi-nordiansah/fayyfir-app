<?php  
session_start();  
require "config.php";  
  
if (!isset($_SESSION["user_id"])) {  
  header("Location: login");  
  exit();  
}  
  
$today = date("Y-m-d");  
$tanggal = date("d");  
$bulan = date("m");  
$tahun = date("y");  
  
// Cari nomor urut terakhir di tanggal yang sama
$stmt = $conn->prepare("SELECT production_number 
                        FROM productions 
                        WHERE production_date = ? 
                        ORDER BY production_number DESC 
                        LIMIT 1");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Ambil 2 digit terakhir dari production_number
    $lastNumber = (int)substr($row['production_number'], -2);
    $urutan = str_pad($lastNumber + 1, 2, "0", STR_PAD_LEFT);
} else {
    // Kalau belum ada, mulai dari 01
    $urutan = "01";
}

$production_number = $tanggal . $bulan . $tahun . $urutan;
  
// Ambil daftar produk unik  
$product_list = [];  
$res = $conn->query("SELECT DISTINCT product_name FROM product_stocks ORDER BY product_name ASC");  
while ($row = $res->fetch_assoc()) {  
  $product_list[] = $row["product_name"];  
}  
  
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $product_name = $_POST["product_name"];

  // Jika produk baru
  if ($product_name === "__new__" && !empty($_POST["new_product_name"])) {
    $product_name = $_POST["new_product_name"];

    // Insert produk baru
    $unit_id = 1;
    $stmt = $conn->prepare("
      INSERT INTO product_stocks (product_name, quantity, unit_id)
      VALUES (?, 0, ?)
    ");
    $stmt->bind_param("si", $product_name, $unit_id);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();
  } else {
    // Ambil ID produk lama
    $stmt = $conn->prepare("SELECT id FROM product_stocks WHERE product_name = ? LIMIT 1");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $stmt->bind_result($product_id);
    $stmt->fetch();
    $stmt->close();
  }

  $production_date = $_POST["production_date"];

  // Insert ke tabel productions
  $status = "Proses";
  $insert_stock_stmt = $conn->prepare("
    INSERT INTO productions (product_id, production_number, production_date, status)
    VALUES (?, ?, ?, ?)
  ");
  $insert_stock_stmt->bind_param("isss", $product_id, $production_number, $production_date, $status);

  if ($insert_stock_stmt->execute()) {
    $insert_stock_stmt->close();
    header("Location: produksi");
    exit();
  } else {
    echo "Gagal menyimpan data produksi: " . $insert_stock_stmt->error;
  }
}
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
  <title>Tambah Produksi</title>  
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
    <h1 class="text-lg font-semibold">Tambah Produksi</h1>  
  </div>  
</header>  
  
<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">  
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">  
    <div>  
      <label class="block text-sm font-medium">Nama Produk</label>  
      <select id="productSelect" name="product_name" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" required>  
        <option value="">-- Pilih Produk --</option>  
          <?php foreach ($product_list as $prodName) {
            echo '<option value="' . htmlspecialchars($prodName) . '">' . htmlspecialchars($prodName) . '</option>'; } ?>
        <option value="__new__">Tambah baru...</option>  
      </select>  
      
      <input type="text" id="newProductInput" name="new_product_name" class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md hidden" placeholder="Nama produk baru..." />  
    </div>  
      
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
      
      document.querySelector("form").addEventListener("submit", function(e) {  
        if (productSelect.value === "") {  
          alert("Silakan pilih produk atau tambahkan produk baru terlebih dahulu.");  
          e.preventDefault();  
        }  
      });  
    });  
    </script>  
  
    <div>  
      <label class="block text-sm font-medium">Tanggal Produksi</label>  
      <input type="date" name="production_date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($today) ?>" />  
    </div>  
  
    <div>  
      <label class="block text-sm font-medium">Nomor Produksi</label>  
      <input type="text" name="production_number" readonly class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" value="<?= htmlspecialchars($production_number) ?>" />  
      <small class="text-xs text-red-400">Nomor ini dibuat otomatis berdasarkan tanggal dan urutan hari ini</small>  
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