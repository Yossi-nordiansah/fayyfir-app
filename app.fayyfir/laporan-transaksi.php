<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$level = $_SESSION["role_id"] ?? "";

// Ambil data kontainer yang sudah full/verified
$sql = "SELECT c.*, 
               u1.name AS admin_input,
               u2.name AS disetujui
        FROM containers c
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.verified_by = u2.id
        WHERE c.status = 'verified'
        ORDER BY c.fill_date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laporan Transaksi - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-icons text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Laporan Transaksi</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 pb-32 px-4 max-w-7xl mx-auto space-y-6">
    <!-- <div class="flex justify-between items-center">
      <a href="export-laporan-transaksi.php" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200">
        <span class="material-icons text-base text-yellow-400 group-hover:text-gray-800 transition">file_download</span>
        <span>Export Excel</span>
      </a>
    </div> -->

    <!-- Table -->
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-left">Tanggal Buka</th>
            <th class="px-4 py-2 text-left">Tanggal Tutup</th>
            <th class="px-4 py-2 text-left">Kontainer</th>
            <th class="px-4 py-2 text-left">Alamat</th>
            <th class="px-4 py-2 text-left">Admin Input</th>
            <th class="px-4 py-2 text-left">Disetujui Oleh</th>
            <th class="px-4 py-2 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-gray-800">
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="px-4 py-2"><?= date(
                "d/m/Y",
                strtotime($row["fill_date"])
              ) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row["updated_at"]) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row["container_number"]) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row["shipping_line"]) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row["admin_input"] ?? "-") ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row["disetujui"] ?? "-") ?></td>
              <td class="px-4 py-2 text-center space-x-1">
                <a href="riwayat-kontainer?id=<?= $row["id"] ?>" class="flex items-center justify-center text-blue-600 hover:text-blue-800 transition">
                  <span class="material-icons text-base">visibility</span>
                  <span class="ml-1 text-sm hidden sm:inline">Rincian</span>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php if ($result->num_rows === 0): ?>
            <tr>
              <td colspan="7" class="text-center text-gray-500 px-4 py-6">Tidak ada data transaksi ditemukan.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>