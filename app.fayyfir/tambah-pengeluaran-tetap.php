<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$area_result = $conn->query("SELECT DISTINCT region_name FROM users WHERE region_name IS NOT NULL");

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $region_name = $_POST["area"];
  $description = $_POST["keterangan"];
  $amount = str_replace('.', '', $_POST["harga_jual"]); // hilangkan titik

  // Simpan ke database
  $stmt = $conn->prepare(
    "INSERT INTO fixed_expenses (region_name, expense_type, amount) 
     VALUES (?, ?, ?)"
  );
  $stmt->bind_param(
    "sss",
    $region_name,
    $description,
    $amount
  );

  if ($stmt->execute()) {
    header("Location: index");
    exit();
  } else {
    $error = "Gagal menyimpan data. Silakan coba lagi.";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pengeluaran Tetap - Fayyfir</title>
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
      <h1 class="text-lg font-semibold">Pengeluaran Tetap</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
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
        <label class="block text-sm font-medium">Deskripsi</label>
        <textarea name="keterangan" rows="2" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none"></textarea>
      </div>

      <!-- Harga Jual Produk -->
      <div>
        <label class="block text-sm font-medium">Biaya Pengeluaran</label>
        <input type="text" name="harga_jual" id="harga_jual" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md transition flex items-center justify-center space-x-2">
        <span class="material-symbols-outlined">save</span>
        <span>Simpan</span>
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