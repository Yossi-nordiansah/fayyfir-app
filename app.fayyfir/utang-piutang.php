<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil dan kelompokkan data kas berdasarkan user_id
$sql = "
  SELECT 
    u.id AS user_id,
    u.name,
    u.phone,
    u.address,
    SUM(c.debit) AS total_debit,
    SUM(c.credit) AS total_credit
  FROM user_cash_flows u
  LEFT JOIN cash_flows c ON c.user_id = u.id
  GROUP BY u.id, u.name, u.phone, u.address
  ORDER BY u.name ASC
";
$result = $conn->query($sql);

// Hitung total saldo keseluruhan
$totalSaldoSeluruhUser = 0;
$rows = [];
while ($row = $result->fetch_assoc()) {
  $saldo = ($row["total_debit"] ?? 0) - ($row["total_credit"] ?? 0);
  $row["saldo"] = $saldo;
  $totalSaldoSeluruhUser += $saldo;
  $rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Utang Piutang - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Utang Piutang</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    
    <div class="flex justify-start gap-2 mb-4">
      <a href="tambah-nama" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
        <span class="ml-2">Tambah Nama</span>
      </a>
    </div>

    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-center">Nama</th>
            <th class="px-4 py-2 text-center">No HP</th>
            <th class="px-4 py-2 text-center">Alamat</th>
            <th class="px-4 py-2 text-center">Sisa Saldo</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="5" class="px-4 py-2 text-center text-gray-500">Belum ada data kas.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["name"]) ?></td>
                <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["phone"]) ?></td>
                <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["address"]) ?></td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format($row["saldo"], 0, ",", ".") ?></td>
                <td class="px-4 py-2 text-center">
                  <a href="rincian-utang-piutang.php?user_id=<?= $row["user_id"] ?>" class="text-blue-500 hover:text-blue-600 flex items-center justify-center space-x-1">
                    <span class="material-symbols-outlined text-base">visibility</span>
                    <span class="text-sm hidden sm:inline">Lihat</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <!-- Total Keseluruhan -->
            <tr class="bg-gray-100 font-semibold">
              <td colspan="3" class="px-4 py-2 text-right">Total</td>
              <td class="px-4 py-2 text-right"><?= number_format($totalSaldoSeluruhUser, 0, ",", ".") ?></td>
              <td class="px-4 py-2 text-center"></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>