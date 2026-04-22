<?php
  // Jika variabel judul modul belum diset, gunakan default
  if (!isset($activeModule)) {
    $activeModule = "Dashboard";
  }
?>
<!-- NAVBAR -->
<nav class="w-full bg-gray-800 shadow-sm border-b border-gray-900 fixed top-0 left-0 z-40">
  <div class="flex justify-between items-center px-4 sm:px-6 lg:px-8 h-14">
    
    <!-- Kiri: Nama Modul Aktif -->
    <div class="flex items-center gap-2">
      <button id="sidebarToggle" class="block lg:hidden p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
        <!-- Icon Menu -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <h1 class="text-lg font-semibold text-gray-200"><?= htmlspecialchars($activeModule); ?></h1>
    </div>

    <!-- Kanan: Profil & Logout -->
    <div class="flex items-center gap-3">
      <!-- Profil user -->
      <div class="hidden sm:flex items-center gap-2">
        <!-- <img src="/abdul-hadi/belanja-harian/assets/images/user.png" alt="User" class="w-8 h-8 rounded-full border border-gray-300 object-cover"> -->
        <span class="text-sm font-medium text-gray-200">
          <?= isset($_SESSION["user_name"]) ? htmlspecialchars($_SESSION["user_name"]) : "User"; ?>
        </span>
      </div>

      <!-- Tombol Logout -->
      <a href="/abdul-hadi/logout.php"
         class="inline-flex items-center gap-1 px-3 py-1.5 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600 transition-all">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
        </svg>
        Keluar
      </a>
    </div>

  </div>
</nav>

<!-- Spacer agar konten tidak tertutup navbar -->
<div class="h-14"></div>