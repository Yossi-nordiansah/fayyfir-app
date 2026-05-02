<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";
$level = $_SESSION["role_id"] ?? "";
$region = $_SESSION["region"] ?? "";
$user_id = $_SESSION["user_id"] ?? 0;

if ($level == "1") {
  // admin: hanya boleh melihat containers yang filled_by dirinya sendiri atau null
  $query = "
    SELECT c.*, p.name AS product_name 
    FROM containers c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.status IN ('draft', 'counted')
      AND (c.filled_by IS NULL OR c.filled_by = $user_id)
    ORDER BY c.fill_date DESC
  ";
} else {
  // spv / owner: bebas melihat semua containers
  $query = "
    SELECT c.*, p.name AS product_name 
    FROM containers c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.status IN ('draft', 'counted')
    ORDER BY c.fill_date DESC
  ";
}

$result = $conn->query($query);
?>
<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
  <title>Dashboard - Fayyfir</title>  
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />  
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">  
  <style>  
    .material-symbols-outlined {  
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;  
    }  
  </style>  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>  
  <script defer>  
    function toggleSidebar() {  
      document.getElementById('sidebar').classList.toggle('-translate-x-full');  
    }  
    function toggleDropdown1() {  
      document.getElementById('dropdownMenu1').classList.toggle('hidden');  
    }  
    function toggleDropdown2() {  
      document.getElementById('dropdownMenu2').classList.toggle('hidden');  
    }  
    function toggleDropdown3() {  
      document.getElementById('dropdownMenu3').classList.toggle('hidden');  
    }  
    function toggleDropdown4() {  
      document.getElementById('dropdownMenu4').classList.toggle('hidden');  
    }  
    function toggleDropdown5() {  
      document.getElementById('dropdownMenu5').classList.toggle('hidden');  
    }  
    function toggleDropdown6() {  
      document.getElementById('dropdownMenu6').classList.toggle('hidden');  
    }  
  </script>  
</head>  
<body class="bg-gray-100 text-white min-h-screen">  
  <div class="flex min-h-screen">  
    <!-- Sidebar -->  
    <aside id="sidebar" class="bg-gray-800 w-72 min-h-screen space-y-6 py-7 px-2 fixed inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition duration-200 ease-in-out z-50 overflow-y-auto">  
      <div class="flex items-center justify-between px-4">  
        <img src="assets/logo-fayyfir3.png" alt="Logo Fayyfir" class="h-8" />  
        <button onclick="toggleSidebar()" class="lg:hidden text-white">  
          <span class="material-symbols-outlined">close</span>  
        </button>  
      </div>  
      <nav class="px-4 space-y-2">  
          
        <!-- Dashboar(Dropdown) -->  
        <?php if (!($level == "1" && $region == "Gaharu")): ?>
        <div>  
          <button onclick="toggleDropdown4()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">  
            <div class="flex items-center space-x-2">  
              <span class="material-symbols-outlined text-sm">home</span>  
              <span class="text-base">Dashboard</span>  
            </div>  
            <span class="material-symbols-outlined text-sm">expand_more</span>  
          </button>  
          <div id="dropdownMenu4" class="hidden ml-8 mt-1 space-y-1">  
            <a href="tambah-kontainer" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">battery_0_bar</span>  
              <span class="text-base">Kontainer Baru</span>  
            </a>  
            
            <?php if ($level == "2" || $level == "3"): ?>  
            <a href="full" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">battery_full</span>  
              <span class="text-base">Kontainer Full</span>  
            </a>  
            <a href="verifikasi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">verified_user</span>  
              <span class="text-base">Kontainer Verified</span>  
            </a>  
            <a href="sudah-diterima" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">checklist</span>  
              <span class="text-base">Kontainer Diterima</span>  
            </a>
            <a href="lunas" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">check_box</span>  
              <span class="text-base">Kontainer Lunas</span>  
            </a>  
            <?php endif; ?>  
          </div>  
        </div>  
        <?php endif; ?>
        
        <?php if ($level == "2" || $level == "3"): ?>
        <a href="daftar-tim" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">  
          <span class="material-symbols-outlined text-sm">person</span>  
          <span class="text-base">Team</span>  
        </a>  
        <!-- Petani/ Supplier (Dropdown) -->  
        <div>  
          <button onclick="toggleDropdown2()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">  
            <div class="flex items-center space-x-2">  
              <span class="material-symbols-outlined text-sm">groups</span>  
              <span class="text-base">Petani/ Supplier</span>  
            </div>  
            <span class="material-symbols-outlined text-sm">expand_more</span>  
          </button>  
          <div id="dropdownMenu2" class="hidden ml-8 mt-1 space-y-1">  
            <a href="daftar-supplier" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">groups</span>  
              <span class="text-base">Data Petani/ Supplier</span>  
            </a>  
            <a href="riwayat-dp-supplier" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">credit_score</span>  
              <span class="text-base">DP Petani/ Supplier</span>  
            </a>  
          </div>  
        </div>  
        <a href="riwayat-operasional" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">  
          <span class="material-symbols-outlined text-sm">payments</span>  
          <span class="text-base">Cost Operasional</span>  
        </a>  
        <a href="modal-dan-aset" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">  
          <span class="material-symbols-outlined text-sm">account_balance</span>  
          <span class="text-base">Modal & Aset</span>  
        </a>  
        <!-- Laporan (Dropdown) -->  
        <div>  
          <button onclick="toggleDropdown3()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">  
            <div class="flex items-center space-x-2">  
              <span class="material-symbols-outlined text-sm">article</span>  
              <span class="text-base">Laporan</span>  
            </div>  
            <span class="material-symbols-outlined text-sm">expand_more</span>  
          </button>  
          <div id="dropdownMenu3" class="hidden ml-8 mt-1 space-y-1">  
            <a href="laporan-transaksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">receipt_long</span>  
              <span class="text-base">Laporan Transaksi</span>  
            </a>  
            <a href="laporan-modal" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">account_balance_wallet</span>  
              <span class="text-base">Laporan Modal</span>  
            </a>  
            <a href="laporan-laba-rugi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">  
              <span class="material-symbols-outlined text-sm">bar_chart</span>  
              <span class="text-base">Laporan Laba Rugi</span>  
            </a>  
          </div>  
        </div>
        <?php endif; ?>  
        
        <!-- Link kembali ke Fayyfir Utama -->  
        <?php if ($level == "2" || $level == "3" || ($level == "1" && ($region == "Palu" || $region == "Luwuk"))): ?>
        <div>
          <a href="switch-2db2?target=abdul-hadi/index" class="flex items-center space-x-2 text-yellow-400 hover:text-yellow-500 px-3 py-2 mt-12">  
            <span class="material-symbols-outlined text-sm">chevron_right</span>  
            <span class="text-base">Fayyfir 2</span>  
          </a>  
        </div>
        <?php endif; ?>  
      </nav>  
    </aside>  
    <!-- Main Content -->  
    <div class="flex-1 flex flex-col">  
      <header class="bg-gray-900 py-2 px-4 flex justify-between items-center fixed top-0 left-0 right-0 z-40">  
        <button onclick="toggleSidebar()" class="text-white focus:outline-none">  
          <span class="material-symbols-outlined">menu</span>  
        </button>  
        <div class="relative">  
          <button onclick="toggleDropdown1()" class="focus:outline-none">  
            <img src="assets/profile-owner.jpg" alt="User" class="h-8 w-8 rounded-full border border-white mt-1.5" />  
          </button>  
          <div id="dropdownMenu1" class="absolute right-0 mt-2 w-40 bg-white text-gray-800 rounded shadow-lg hidden z-50">  
            <a href="logout" class="flex items-center space-x-2 block px-4 py-2 hover:bg-gray-100">  
              <span class="material-symbols-outlined text-sm">logout</span>  
              <span>Log Out</span>  
            </a>  
          </div>  
        </div>  
      </header>  
  
      <!-- Main -->  
      <main class="pt-20 py-4 px-6 space-y-6 pb-24 lg:pb-6">  
          
        <!-- Notifikasi Kontainer -->  
      <?php if (isset($_SESSION["status_pesan"])): ?>  
        <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">  
          <?=  
          $_SESSION["status_pesan"];  
          unset($_SESSION["status_pesan"]);  
          ?>  
        </div>  
      <?php endif; ?>  
      
      <?php if ($level == "2" || $level == "3"): ?>
      <div class="flex justify-end items-center mb-4">
        <a href="tambah-pengeluaran-tetap" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
          <span class="ml-2 group-hover:text-gray-800">Pengeluaran Tetap</span>
        </a>
      </div>
      <?php endif; ?>
  
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">  
          <?php while ($row = $result->fetch_assoc()): ?>  
            <a href="riwayat-kontainer?id=<?= $row["id"] ?>">  
              <div class="bg-white rounded-lg shadow p-4 text-gray-800 flex justify-between items-center">  
                <div class="flex items-center space-x-4">  
                  <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>  
                  <div>  
                    <h2 class="text-sm text-gray-500">Area: <?= htmlspecialchars(  
                      $row["region_name"] ?? "-"  
                    ) ?></h2>
                    <p class="text-xl font-bold text-gray-500"><?= htmlspecialchars(  
                      $row["container_number"]  
                    ) ?></p>  
                    <h2 class="text-sm text-gray-500">Produk: <?= htmlspecialchars(  
                      $row["product_name"] ?? "-"  
                    ) ?></h2>  
                  </div>  
                </div>  
                <div class="flex flex-col items-center">  
                  <h2 class="text-sm text-gray-500">Status</h2>  
                  <?php if ($row["status"] == "full"): ?>  
                    <span class="text-green-500 mt-1 text-sm font-semibold">Full</span>  
                  <?php else: ?>  
                    <span class="text-red-500 mt-1 text-sm font-semibold">Load</span>  
                  <?php endif; ?>  
                </div>  
              </div>  
            </a>  
          <?php endwhile; ?>  
        </section>  
      
        <?php if ($level == "2" || $level == "3"): ?>  
        <section class="hidden lg:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">  
          <a href="tambah-kontainer" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded text-center font-medium transition duration-200">  
            <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">add_circle</span>  
            <span>Tambah Kontainer</span>  
          </a>  
          <a href="full" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded text-center font-medium transition duration-200">  
            <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">check_circle</span>  
            <span>Verifikasi Transaksi</span>  
          </a>  
          <a href="verifikasi" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded text-center font-medium transition duration-200" hidden>  
            <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">assessment</span>  
            <span>Invoice</span>  
          </a>  
        </section>  
        <?php endif; ?>  
        
        <?php if ($level == "1"): ?>
        <section class="hidden lg:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">  
          <a href="tambah-kontainer-admin" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded text-center font-medium transition duration-200">  
            <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">add_circle</span>  
            <span>Tambah Kontainer</span>  
          </a>  
        </section>  
        <?php endif; ?>
      </main>  
    </div>  
  </div>  
    
  <?php if ($level == "2" || $level == "3"): ?>  
  <nav class="fixed bottom-0 left-1 right-1 bg-gray-900 border-t border-gray-700 flex justify-around items-center py-3 lg:hidden z-40 rounded-full">  
    <a href="tambah-kontainer" class="flex items-center text-yellow-400 space-x-2">  
      <span class="material-symbols-outlined">add_circle</span>  
      <span class="text-sm text-white">Tambah</span>  
    </a>  
    <a href="full" class="flex items-center text-yellow-400 space-x-2">  
      <span class="material-symbols-outlined">check_circle</span>  
      <span class="text-sm text-white">Verifikasi</span>  
    </a>  
    <a href="verifikasi" class="flex items-center text-yellow-400 space-x-2">  
      <span class="material-symbols-outlined">assessment</span>  
      <span class="text-sm text-white">Invoice</span>  
    </a>  
  </nav>  
  <?php endif; ?>
  
  <?php if (!($level == "1" && $region == "Gaharu")): ?>
  <?php if ($level == "1"): ?>  
  <nav class="fixed bottom-0 left-1 right-1 bg-gray-900 border-t border-gray-700 flex justify-around items-center py-3 lg:hidden z-40 rounded-full">  
    
    <a href="tambah-kontainer-admin" class="flex items-center text-yellow-400 space-x-2">  
      <span class="material-symbols-outlined">add_circle</span>  
      <span class="text-sm text-white">Tambah</span>  
    </a>
  </nav>  
  <?php endif; ?>
  <?php endif; ?>
</body>  
</html>