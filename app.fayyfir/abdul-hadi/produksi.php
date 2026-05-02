<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";
$level = $_SESSION["role_id"] ?? "";
$region = $_SESSION["region"] ?? "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Produksi - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <?php if ($level != "1" && $region != "Gaharu"): ?>
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <?php endif; ?>
      
      <h1 class="text-lg font-semibold">Produksi</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    
    <!-- Button -->
    <div class="flex justify-between items-center gap-4 mb-4">
      <div class="flex justify-between items-center gap-4">
        <?php if ($level != "1" && $region != "Gaharu"): ?>
        <a href="hasil-produksi" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">chevron_right</span>
          <span class="ml-2 group-hover:text-gray-800">Hasil Produksi</span>
        </a>
        <?php endif; ?>
        
        <?php if ($level == "1" && $region == "Gaharu"): ?>
        <a href="bahan-baku" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">chevron_left</span>
          <span class="ml-2 group-hover:text-gray-800">Bahan Baku</span>
        </a>
        <?php endif; ?>
      </div>
      
      <a href="produksi-tambah" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
        <span class="ml-2 group-hover:text-gray-800">Produksi</span>
      </a>
    </div>

    <!-- Card Product -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php
      $query = "
        SELECT p.*, ps.product_name 
        FROM productions p
        JOIN product_stocks ps ON ps.id = p.product_id
        WHERE p.status = 'Proses'
        ORDER BY p.created_at DESC
      ";
      $result = mysqli_query($conn, $query);

      if (mysqli_num_rows($result) > 0):
        while ($row = mysqli_fetch_assoc($result)):
          $id = $row['id'];
          $nomor = $row['production_number'];
          $produk = $row['product_name'];
          $tanggal = date("d M Y", strtotime($row['created_at']));
          $status = $row['status'];
      ?>
        <button onclick="toggleMenu('menu-<?= $id ?>')" class="bg-white rounded-lg shadow p-4 text-gray-800 hover:shadow-md transition relative">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <span class="material-symbols-outlined text-yellow-400 text-4xl">box</span>
              <div class="text-left">
                <h2 class="text-xs text-gray-500">Nomor: <?= htmlspecialchars($nomor) ?></h2>
                <p class="text-lg font-bold text-gray-700"><?= htmlspecialchars($produk) ?></p>
                <h2 class="text-xs text-gray-500">Edisi: <?= $tanggal ?></h2>
              </div>
            </div>
            <div class="flex flex-col items-center mt-2">
              <h2 class="text-sm text-gray-500">Status</h2>
              <span class="mt-1 text-sm font-semibold <?= $status === 'Selesai' ? 'text-green-600' : 'text-yellow-500' ?>">
                <?= htmlspecialchars($status) ?>
              </span>
            </div>
          </div>
        
          <!-- Tombol dropdown -->
          <div class="absolute top-2 right-2">
            <div id="menu-<?= $id ?>" class="hidden absolute right-0 mt-2 w-30 border rounded-lg shadow-md z-50 bg-white">
              <a href="produksi-proses?id=<?= $id ?>&name=<?= urlencode($produk) ?>" 
                 class="flex items-center justify-start block px-5 py-2 text-sm text-white bg-gray-800 hover:bg-gray-900 border-b border-yellow-400 rounded-t-lg"><span class="material-symbols-outlined mr-4 text-sm text-yellow-400">arrow_forward_ios</span>Lanjut</a>
              <a href="produksi-edit?id=<?= $id ?>" 
                 class="flex items-center justify-start block px-5 py-2 text-sm text-white bg-gray-800 hover:bg-gray-900 border-b border-yellow-400"><span class="material-symbols-outlined mr-4 text-sm text-yellow-400">edit</span>Edit</a>
              <a href="produksi-hapus?id=<?= $id ?>" onclick="return confirm('Yakin ingin menghapus data produksi ini?\nMenghapus data produksi ini berarti turut menghapus semua data produksinya.')" class="flex items-center justify-start block px-5 py-2 text-sm text-white bg-gray-800 hover:bg-gray-900 rounded-b-lg"><span class="material-symbols-outlined mr-4 text-sm text-yellow-400">delete</span>Hapus</a>
            </div>
          </div>
        </button>
      <?php
        endwhile;
      else:
      ?>
        <div class="col-span-full text-center py-12">
          <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">inventory_2</span>
          <p class="text-gray-500 text-sm">Belum ada proses produksi</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

<script>
function toggleMenu(id) {
  document.querySelectorAll('[id^="menu-"]').forEach(el => {
    if (el.id === id) {
      el.classList.toggle("hidden");
    } else {
      el.classList.add("hidden");
    }
  });
}

// tutup dropdown jika klik di luar
document.addEventListener("click", function(e) {
  if (!e.target.closest("[id^='menu-']") && !e.target.closest("button")) {
    document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add("hidden"));
  }
});
</script>
</body>
</html>