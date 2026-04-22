<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$periode = $_GET['periode'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
  echo "Periode tidak valid.";
  exit();
}

$periode_label = DateTime::createFromFormat('Y-m', $periode)->format('F Y');
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Detail Laba Rugi - <?= $periode_label ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="laporan-laba-rugi.php" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Detail Laba Rugi — <?= $periode_label ?></h1>
  </div>
</header>

<main class="pt-24 pb-32 px-4 max-w-7xl mx-auto">

  <div class="bg-white shadow rounded-lg overflow-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-100 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Container</th>
          <th class="px-4 py-3 text-right">Total Berat (kg)</th>
          <th class="px-4 py-3 text-right">Pendapatan</th>
          <th class="px-4 py-3 text-right">Pembelian</th>
          <th class="px-4 py-3 text-right">Expenses</th>
          <th class="px-4 py-3 text-right">BPP</th>
          <th class="px-4 py-3 text-right">Laba / Rugi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">

<?php
$q_containers = $conn->query("
  SELECT id, selling_price, number, lunas_at
  FROM containers
  WHERE status = 'lunas'
    AND DATE_FORMAT(lunas_at, '%Y-%m') = '$periode'
");

if ($q_containers->num_rows === 0) {
  echo "<tr><td colspan='7' class='text-center py-6'>Tidak ada data.</td></tr>";
}

while ($c = $q_containers->fetch_assoc()) {
  $container_id = $c['id'];
  $container_number = $c['number'];
  $selling_price = $c['selling_price'];

  // Total berat
  $q_weight = $conn->query("
    SELECT SUM(weight_kg) AS total_weight
    FROM transactions
    WHERE container_id = $container_id
  ");
  $total_weight = $q_weight->fetch_assoc()['total_weight'] ?? 0;

  // Pendapatan
  $pendapatan = $total_weight * $selling_price;

  // Total pembelian (grand_total)
  $q_trx = $conn->query("
    SELECT SUM(grand_total) AS total_trx
    FROM transactions
    WHERE container_id = $container_id
  ");
  $total_trx = $q_trx->fetch_assoc()['total_trx'] ?? 0;

  // Total expenses
  $q_exp = $conn->query("
    SELECT SUM(amount) AS total_exp
    FROM expenses
    WHERE container_id = $container_id
  ");
  $total_exp = $q_exp->fetch_assoc()['total_exp'] ?? 0;

  // BPP & laba
  $bpp = $total_trx + $total_exp;
  $laba = $pendapatan - $bpp;

  $laba_class = $laba < 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold';

  echo "
  <tr>
    <td class='px-4 py-3'>#{$container_number}</td>
    <td class='px-4 py-3 text-right'>" . number_format($total_weight, 0, ',', '.') . "</td>
    <td class='px-4 py-3 text-right'>" . number_format($pendapatan, 0, ',', '.') . "</td>
    <td class='px-4 py-3 text-right'>" . number_format($total_trx, 0, ',', '.') . "</td>
    <td class='px-4 py-3 text-right'>" . number_format($total_exp, 0, ',', '.') . "</td>
    <td class='px-4 py-3 text-right'>" . number_format($bpp, 0, ',', '.') . "</td>
    <td class='px-4 py-3 text-right {$laba_class}'>" . number_format($laba, 0, ',', '.') . "</td>
  </tr>";
}
?>

      </tbody>
    </table>
  </div>

</main>

</body>
</html>