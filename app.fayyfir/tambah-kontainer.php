<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$area_result = $conn->query("SELECT DISTINCT region_name FROM users WHERE region_name IS NOT NULL");

// Ambil data produk untuk selection
$product_query = "SELECT * FROM products";
$product_result = $conn->query($product_query);

// Ambil data user untuk selection
$user_query = "SELECT * FROM users";
$user_result = $conn->query($user_query);

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $container_number = $_POST["kontainer"];
  $region_name = $_POST["area"];
  $fill_date = $_POST["tanggal"];
  $expedition = $_POST["ekspedisi"];
  $shipping_line = $_POST["pelayaran"];
  $description = $_POST["keterangan"];
  $product_id = $_POST["produk"];
  $selling_price = str_replace('.', '', $_POST["harga_jual"]); // hilangkan titik
  if ($selling_price === "") $selling_price = 0; // Default ke 0 jika kosong
  $created_by = $_SESSION["user_id"];
  $filled_by = $_POST["user"];

  // Simpan ke database
  $stmt = $conn->prepare(
    "INSERT INTO containers (container_number, region_name, fill_date, expedition, shipping_line, description, status, filled_by, created_by, product_id, selling_price) 
     VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)"
  );
  $stmt->bind_param(
    "ssssssiiis",
    $container_number,
    $region_name,
    $fill_date,
    $expedition,
    $shipping_line,
    $description,
    $filled_by,
    $created_by,
    $product_id,
    $selling_price
  );

  if ($stmt->execute()) {
    $new_container_id = $conn->insert_id; // ID kontainer baru

    // Cek apakah ada biaya tetap sesuai expedition (region_name)
    $check_fixed = $conn->prepare("SELECT expense_type, amount, notes FROM fixed_expenses WHERE region_name = ?");
    $check_fixed->bind_param("s", $region_name);
    $check_fixed->execute();
    $fixed_result = $check_fixed->get_result();

    while ($row = $fixed_result->fetch_assoc()) {
      $insert_exp = $conn->prepare("INSERT INTO expenses (container_id, expense_date, expense_type, amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
      $today = date("Y-m-d");
      $insert_exp->bind_param("issisi", $new_container_id, $today, $row['expense_type'], $row['amount'], $row['notes'], $created_by);
      $insert_exp->execute();
      $insert_exp->close();
    }

    $check_fixed->close();

    header("Location: index");
    exit();
  } else {
    echo "Gagal menyimpan data kontainer.";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Kontainer - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Tambah Kontainer</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
      <div>
        <label class="block text-sm font-medium">Nomor Kontainer</label>
        <input type="text" name="kontainer" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>
      
      <div>
        <label class="block text-sm font-medium">Area</label>
        <select name="area" id="areaSelect" class="mt-1 w-full border px-3 py-2 rounded">
          <option value="">-- Pilih Area --</option>
          <?php while($r = $area_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($r['region_name']) ?>"><?= htmlspecialchars($r['region_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Tanggal Release</label>
        <input type="date" name="tanggal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" value="<?= date(
        "Y-m-d"
      ) ?>" />
      </div>

      <!-- Jenis Produk + Tombol -->
      <div>
        <label class="block text-sm font-medium mb-1">Jenis Produk</label>
        <div class="flex gap-2">
          <select name="produk" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none">
            <option value="">Pilih Jenis Produk</option>
            <?php while ($product = $product_result->fetch_assoc()): ?>
              <option value="<?= $product["id"] ?>"><?= htmlspecialchars($product["name"]) ?></option>
            <?php endwhile; ?>
          </select>
          <a href="tambah-jenis-produk" class="bg-gray-800 hover:bg-yellow-400 text-white rounded-md px-3 flex items-center justify-center transition">
            <span class="material-symbols-outlined text-yellow-400 group-hover:text-gray-800 transition">add</span>
          </a>
        </div>
      </div>
      
      <!-- Jenis User -->
      <div>
        <label class="block text-sm font-medium mb-1">Pilih Admin</label>
          <select name="user" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none">
            <option value="">Pilih Admin</option>
            <?php while ($user = $user_result->fetch_assoc()): ?>
              <option value="<?= $user["id"] ?>"><?= htmlspecialchars($user["name"]) ?></option>
            <?php endwhile; ?>
          </select>
      </div>

      <!-- Harga Jual Produk -->
      <div hidden="">
        <label class="block text-sm font-medium">Harga Jual Produk</label>
        <input type="text" name="harga_jual" id="harga_jual" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Ekspedisi</label>
        <input type="text" name="ekspedisi" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Pelayaran</label>
        <input type="text" name="pelayaran" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Keterangan</label>
        <textarea name="keterangan" rows="2" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none"></textarea>
      </div>

      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md transition flex items-center justify-center space-x-2">
        <span class="material-symbols-outlined">check_circle</span>
        <span>Simpan Kontainer</span>
      </button>
    </form>
  </main>

  <!-- Bottom Padding for Mobile -->
  <div class="lg:hidden h-24"></div>

  <script>
    // Format angka ribuan di input harga
    document.getElementById("harga_jual").addEventListener("input", function(e) {
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