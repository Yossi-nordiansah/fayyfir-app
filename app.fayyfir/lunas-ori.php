<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";
$level = $_SESSION["role_id"] ?? "";

$query = "
  SELECT c.*, p.name AS product_name 
  FROM containers c
  LEFT JOIN products p ON c.product_id = p.id
  WHERE c.status = 'lunas'
  ORDER BY c.number ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kontainer Lunas - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <script>
    function openModal(id) {
      alert('Modal detail untuk baris ke-' + (id + 1));
    }
  </script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Kontainer Lunas</h1>
    </div>
  </header>
  
  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    
    <!-- Notifikasi Kontainer -->
      <?php if (isset($_SESSION["status_pesan"])): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
          <?=
          $_SESSION["status_pesan"];
          unset($_SESSION["status_pesan"]);
          ?>
        </div>
      <?php endif; ?>
      
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php while ($row = $result->fetch_assoc()): ?>
        <a href="riwayat-kontainer2?id=<?= $row["id"] ?>" class="bg-white rounded-lg shadow p-4">
          <div class="text-gray-800 flex justify-between items-center mb-2">
            <div class="flex items-center space-x-4">
              <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>
              <div>
                <h2 class="text-sm text-gray-500"><?= htmlspecialchars(
                  $row["container_number"]
                ) ?></h2>
                <p class="text-2xl font-bold text-gray-500"><?= htmlspecialchars(
                  $row["number"]
                ) ?></p>
                <h2 class="text-sm text-gray-500">Produk: <?= htmlspecialchars(
                  $row["product_name"] ?? "-"
                ) ?> | Area: <?= htmlspecialchars(
                  $row["region_name"] ?? "-"
                ) ?></h2>
              </div>
            </div>
            <div class="flex flex-col items-center">
              <h2 class="text-sm text-gray-500">Status</h2>
              <?php if ($row["status"] == "lunas"): ?>
                <span class="text-green-500 mt-1 text-sm font-semibold">Lunas</span>
              <?php else: ?>
                <span class="text-red-500 mt-1 text-sm font-semibold">Load</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex items-center justify-between gap-1">
            <span class="text-sm text-gray-300">Closed
            </span>
            <span class="text-sm text-gray-300">Diterima
            </span>
            <span class="text-sm text-gray-300">Lunas
            </span>
          </div>
          <div class="flex items-center justify-between gap-1">
            <span class="text-sm text-gray-500">
              <?= !empty($row["verified_at"]) ? date("d/m/Y", strtotime($row["verified_at"])) : "-" ?>
            </span>
            <span class="text-sm text-gray-500">
              <?= !empty($row["accepted_at"]) ? date("d/m/Y", strtotime($row["accepted_at"])) : "-" ?>
            </span>
            <span class="text-sm text-gray-500">
              <?= !empty($row["lunas_at"]) ? date("d/m/Y", strtotime($row["lunas_at"])) : "-" ?>
            </span>
          </div>
        </a>
      <?php endwhile; ?>
    </section>

  </main>
</body>
</html>