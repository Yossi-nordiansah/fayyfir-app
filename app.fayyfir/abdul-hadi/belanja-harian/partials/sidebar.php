<?php
// Definisikan halaman aktif agar menu bisa diberi highlight
if (!isset($activeMenu)) {
  $activeMenu = "dashboard";
}

// Daftar menu utama (Laporan dibuat khusus karena memiliki submenu)
$menus = [
  "dashboard" => ["icon" => "home", "label" => "Dashboard", "url" => "/abdul-hadi/belanja-harian/dashboard.php"],
  "materials" => ["icon" => "package", "label" => "Data Bahan", "url" => "/abdul-hadi/belanja-harian/data-bahan/"],
  "stok-bahan" => ["icon" => "archive", "label" => "Stok Bahan",  "url" => "/abdul-hadi/belanja-harian/stok-bahan"],
  "suppliers" => ["icon" => "users", "label" => "Supplier", "url" => "/abdul-hadi/belanja-harian/data-supplier/"],

  // ✅ Tambahan menu baru — Buyer
  "buyers" => ["icon" => "user-check", "label" => "Buyer", "url" => "/abdul-hadi/belanja-harian/data-buyer/"],

  "purchases" => ["icon" => "shopping-cart", "label" => "Pembelian", "url" => "/abdul-hadi/belanja-harian/pembelian-awal/"],
  "productions" => ["icon" => "factory", "label" => "Produksi", "url" => "/abdul-hadi/belanja-harian/proses-produksi/"],
  "sales" => ["icon" => "chart-bar", "label" => "Penjualan", "url" => "/abdul-hadi/belanja-harian/penjualan/"],
];

// Submenu untuk laporan
$reportSubmenu = [
  "laba-rugi" => ["label" => "Laba Rugi", "url" => "/abdul-hadi/belanja-harian/laporan/laba-rugi.php"],
  "penyusutan" => ["label" => "Penyusutan", "url" => "/abdul-hadi/belanja-harian/laporan/penyusutan-tahap.php"],
  "ringkasan-hpp" => ["label" => "Ringkasan Modal HPP", "url" => "/abdul-hadi/belanja-harian/laporan/ringkasan-modal-hpp.php"],
];

// Cek apakah submenu laporan sedang aktif
$isReportActive = ($activeMenu === "reports" || in_array($activeModule ?? "", ["Laba Rugi", "Penyusutan", "Ringkasan Modal HPP"]));
?>

<!-- SIDEBAR -->
<aside id="sidebar"
  class="fixed top-0 left-0 h-full w-64 bg-gray-800 border-r border-gray-900 shadow-sm z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">

  <!-- Logo + Judul -->
  <div class="flex items-center justify-between h-14 px-4 border-b border-gray-700">
    <a href="/abdul-hadi/belanja-harian/dashboard" class="flex items-center gap-2">
      <img src="../app.fayyfir/assets/logo-fayyfir1.png" alt="Logo" class="w-8 h-8">
      <span class="font-semibold text-lg text-gray-200">Belanja Bahan</span>
    </a>
    <!-- Tombol close untuk mobile -->
    <button id="sidebarClose" class="lg:hidden text-gray-200 hover:text-gray-300 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <!-- Menu Navigasi -->
  <nav class="px-3 py-4 overflow-y-auto h-[calc(100%-3.5rem)]">
    <ul class="space-y-1">
      <?php foreach ($menus as $key => $menu): ?>
        <?php $active = ($activeMenu === $key) ? "bg-gray-700 text-yellow-400 font-semibold" : "text-gray-200 hover:bg-gray-800 hover:text-yellow-400"; ?>
        <li>
          <a href="<?= $menu['url']; ?>"
            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all <?= $active; ?>">
            <img src="/abdul-hadi/belanja-harian/assets/icons/<?= $menu['icon']; ?>.svg" class="w-5 h-5 opacity-80" alt="<?= $menu['label']; ?> icon">
            <span><?= $menu['label']; ?></span>
          </a>
        </li>
      <?php endforeach; ?>

      <!-- Dropdown Laporan -->
      <li class="relative">
        <button id="dropdownToggleReports"
          class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all <?= $isReportActive ? 'bg-gray-700 text-yellow-400 font-semibold' : 'text-gray-200 hover:bg-gray-800 hover:text-yellow-400'; ?>">
          <div class="flex items-center gap-3">
            <img src="/abdul-hadi/belanja-harian/assets/icons/file-text.svg" class="w-5 h-5 opacity-80" alt="Laporan icon">
            <span>Laporan</span>
          </div>
          <svg id="dropdownIconReports" xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 transition-transform duration-300 <?= $isReportActive ? 'rotate-180' : ''; ?>"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <ul id="dropdownMenuReports"
          class="mt-1 ml-8 space-y-1 overflow-hidden transition-all duration-300 <?= $isReportActive ? 'max-h-60 opacity-100' : 'max-h-0 opacity-0'; ?>">
          <?php foreach ($reportSubmenu as $subKey => $sub): ?>
            <?php
            $isSubActive = (isset($activeModule) && stripos($activeModule, $sub['label']) !== false);
            $subActiveClass = $isSubActive ? 'text-yellow-400 font-semibold' : 'text-gray-300 hover:text-yellow-400';
            ?>
            <li>
              <a href="<?= $sub['url']; ?>" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm <?= $subActiveClass; ?> transition">
                <span>•</span>
                <?= $sub['label']; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li>
        <a href="../../index"
          class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all hover:font-semibold text-yellow-400 hover:text-yellow-600 mt-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Fayyfir 2
        </a>
      </li>
    </ul>
  </nav>
</aside>

<!-- Overlay (untuk mode mobile) -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/30 hidden lg:hidden z-20"></div>

<script>
  // Dropdown interaktif (Laporan)
  const toggleReports = document.getElementById("dropdownToggleReports");
  const menuReports = document.getElementById("dropdownMenuReports");
  const iconReports = document.getElementById("dropdownIconReports");

  if (toggleReports) {
    toggleReports.addEventListener("click", () => {
      const expanded = menuReports.classList.contains("max-h-60");
      menuReports.classList.toggle("max-h-60", !expanded);
      menuReports.classList.toggle("opacity-100", !expanded);
      menuReports.classList.toggle("max-h-0", expanded);
      menuReports.classList.toggle("opacity-0", expanded);
      iconReports.classList.toggle("rotate-180");
    });
  }
</script>