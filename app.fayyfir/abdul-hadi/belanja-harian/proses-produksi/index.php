<?php
session_start();
require "../../config.php";
$conn = $conn2;

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$activeMenu = "productions";
$activeModule = "Dashboard Produksi";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Proses Produksi</h1>
    <p class="text-sm text-gray-500">Kelola seluruh tahap proses penyusutan hingga siap jual.</p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <!-- JEMUR -->
    <a href="jemur/list-jemur"
      class="bg-white border border-gray-100 rounded-2xl p-6 shadow hover:shadow-lg transition flex flex-col items-center text-center">
      <h1 class="mb-3 text-4xl font-bold">1</h1>
      <!-- <img src="/abdul-hadi/belanja-harian/assets/icons/sun.svg" alt="Jemur" class="w-10 h-10 mb-3 opacity-80"> -->
      <h2 class="text-lg font-semibold text-gray-800">Proses Susut 1</h2>
      <p class="text-sm text-gray-500 mt-1">Proses penyusutan tahap pertama.</p>
    </a>
    
    <!-- KUPAS -->
    <a href="kupas/list-kupas"
      class="bg-white border border-gray-100 rounded-2xl p-6 shadow hover:shadow-lg transition flex flex-col items-center text-center">
      <h1 class="mb-3 text-4xl font-bold">2</h1>
      <!-- <img src="/abdul-hadi/belanja-harian/assets/icons/knife.svg" alt="Kupas" class="w-10 h-10 mb-3 opacity-80"> -->
      <h2 class="text-lg font-semibold text-gray-800">Proses Susut 2</h2>
      <p class="text-sm text-gray-500 mt-1">Proses penyusutan tahap kedua.</p>
    </a>

    <!-- SORTIR -->
    <a href="sortir-simpan/list-sortir"
      class="bg-white border border-gray-100 rounded-2xl p-6 shadow hover:shadow-lg transition flex flex-col items-center text-center">
      <h1 class="mb-3 text-4xl font-bold">3</h1>
      <!-- <img src="/abdul-hadi/belanja-harian/assets/icons/sort.svg" alt="Sortir" class="w-10 h-10 mb-3 opacity-80"> -->
      <h2 class="text-lg font-semibold text-gray-800">Sortir & Simpan</h2>
      <p class="text-sm text-gray-500 mt-1">Tahap akhir penyimpanan bahan.</p>
    </a>
  </div>
</main>

<?php include "../partials/footer.php"; ?>