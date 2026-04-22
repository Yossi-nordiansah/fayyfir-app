<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

require "config.php";

$product_name = $_GET['product_name'] ?? null;
if (!$product_name) {
    echo "Produk tidak ditemukan";
    exit();
}

/*
=========================================================
AMBIL DATA PRODUKSI
=========================================================
*/
$stmt = $conn->prepare("
    SELECT p.*, u.symbol AS unit_symbol
    FROM productions p
    JOIN product_stocks ps ON p.product_id = ps.id
    LEFT JOIN units u ON p.unit_id = u.id
    WHERE ps.product_name = ?
    ORDER BY p.production_date DESC
");
$stmt->bind_param("s", $product_name);
$stmt->execute();
$productions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_global = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rincian Biaya Produksi</title>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- HEADER FIXED -->
<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40 shadow">
  <div class="flex justify-between items-center">

    <a href="hasil-produksi-riwayat.php?product_name=<?= urlencode($product_name) ?>"
       class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>

    <h1 class="text-lg font-semibold">
      Rincian Biaya: <?= htmlspecialchars($product_name) ?>
    </h1>

  </div>
</header>


<!-- MAIN WRAPPER -->
<main class="pt-24 px-4 pb-24 max-w-7xl mx-auto space-y-6">


<?php foreach ($productions as $prod): ?>

<?php
/*
=========================================================
AMBIL MATERIAL
=========================================================
*/
$stmtM = $conn->prepare("
    SELECT pm.*, m.name,
           u.symbol
    FROM production_materials pm
    JOIN materials m ON pm.material_id = m.id
    LEFT JOIN units u ON pm.unit_id = u.id
    WHERE pm.production_id = ?
");
$stmtM->bind_param("i", $prod['id']);
$stmtM->execute();
$materials = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtM->close();


/*
=========================================================
AMBIL EXPENSE
=========================================================
*/
$stmtE = $conn->prepare("
    SELECT *
    FROM production_expenses
    WHERE production_id = ?
");
$stmtE->bind_param("i", $prod['id']);
$stmtE->execute();
$expenses = $stmtE->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtE->close();


/*
=========================================================
HITUNG TOTAL
=========================================================
*/
$total_material = array_sum(array_column($materials,'total_price'));
$total_expense  = array_sum(array_column($expenses,'amount'));
$grand = $total_material + $total_expense;
$total_global += $grand;
?>

<div class="bg-white shadow rounded-xl p-5 space-y-4">

<!-- HEADER -->
<div class="flex justify-between">
<div>
<div class="font-semibold">
Batch : <?= $prod['production_number'] ?>
</div>
<div class="text-sm text-gray-500">
<?= $prod['production_date'] ?>
</div>
</div>

<div class="text-right">
<div class="text-sm text-gray-500">Total Batch</div>
<div class="font-bold text-lg text-red-600">
Rp <?= number_format($grand,0,',','.') ?>
</div>
</div>
</div>


<!-- MATERIAL TABLE -->
<div>
<h2 class="font-semibold mb-2">Material Digunakan</h2>

<div class="overflow-x-auto">
<table class="min-w-full text-xs md:text-sm border">
<thead class="bg-gray-50">
<tr>
<th class="px-3 py-2 text-left whitespace-nowrap">Material</th>
<th class="px-3 py-2 whitespace-nowrap">Qty</th>
<th class="px-3 py-2 whitespace-nowrap">Harga Unit</th>
<th class="px-3 py-2 whitespace-nowrap">Total</th>
</tr>
</thead>

<tbody>
<?php foreach($materials as $m): ?>
<tr class="border-t hover:bg-gray-50">
<td class="px-3 py-2 whitespace-nowrap">
<?= htmlspecialchars($m['name']) ?>
</td>
<td class="px-3 py-2 text-center whitespace-nowrap">
<?= number_format($m['quantity_used'],2) ?>
<?= $m['symbol'] ?>
</td>
<td class="px-3 py-2 text-right whitespace-nowrap">
Rp <?= number_format($m['unit_price'],0,',','.') ?>
</td>
<td class="px-3 py-2 text-right font-semibold whitespace-nowrap">
Rp <?= number_format($m['total_price'],0,',','.') ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="text-right font-bold mt-2">
Subtotal Material :
Rp <?= number_format($total_material,0,',','.') ?>
</div>

</div>


<!-- EXPENSE TABLE -->
<div>
<h2 class="font-semibold mb-2">Biaya Lainnya</h2>

<div class="overflow-x-auto">
<table class="min-w-full text-xs md:text-sm border">
<thead class="bg-gray-50">
<tr>
<th class="px-3 py-2 text-left whitespace-nowrap">Deskripsi</th>
<th class="px-3 py-2 whitespace-nowrap">Total</th>
</tr>
</thead>

<tbody>
<?php foreach($expenses as $e): ?>
<tr class="border-t hover:bg-gray-50">
<td class="px-3 py-2 whitespace-nowrap">
<?= htmlspecialchars($e['description']) ?>
</td>
<td class="px-3 py-2 text-right font-semibold whitespace-nowrap">
Rp <?= number_format($e['amount'],0,',','.') ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="text-right font-bold mt-2">
Subtotal Expense :
Rp <?= number_format($total_expense,0,',','.') ?>
</div>

</div>

<!-- TOMBOL -->
<div class="flex justify-end items-center">
<a href="produksi-proses?id=<?= $prod['id'] ?>&name=<?= urlencode($product_name) ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
  <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">edit</span>
  <span class="ml-2 group-hover:text-gray-800">Sesuaikan</span>
</a>
</div>

</div>

<?php endforeach; ?>


<!-- GRAND TOTAL -->
<div class="bg-yellow-400 text-black rounded-xl p-6 text-right shadow-lg">
<div class="text-sm">TOTAL BIAYA PRODUKSI GLOBAL</div>
<div class="text-2xl font-bold">
Rp <?= number_format($total_global,0,',','.') ?>
</div>
</div>

</main>
</body>
</html>