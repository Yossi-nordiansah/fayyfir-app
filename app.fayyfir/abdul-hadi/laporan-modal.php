<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Filter tanggal
$from = isset($_GET["from"]) ? $_GET["from"] : null;
$to = isset($_GET["to"]) ? $_GET["to"] : null;
$errorMsg = null;

if ($from && $to) {
  $diff = (strtotime($to) - strtotime($from)) / (60 * 60 * 24);
  if ($diff > 30) {
    $errorMsg = "Maksimal rentang tanggal adalah 30 hari saja.";
    $from = null;
    $to = null;
  }
}

// prepare where filter
$whereFilterModal = "";
$whereFilterAset = "";
$whereFilterExp = "";
$whereFilterKont = "";

if ($from && $to) {
  $whereFilterModal = " AND date BETWEEN '$from' AND '$to' ";
  $whereFilterAset = " AND created_at BETWEEN '$from' AND '$to' ";
  $whereFilterExp = " AND expense_date BETWEEN '$from' AND '$to' ";
  $whereFilterKont = " AND c.updated_at BETWEEN '$from' AND '$to' ";
}

$logs = [];

// Ambil log modal
$result_modal = $conn->query(
  "SELECT date, description, amount FROM modal_log WHERE 1=1 $whereFilterModal ORDER BY date ASC"
);
while ($row = $result_modal->fetch_assoc()) {
  $logs[] = [
    "date" => $row["date"],
    "desc" => $row["description"],
    "debit" => 0,
    "credit" => $row["amount"],
  ];
}

// Ambil aset
$result_aset = $conn->query(
  "SELECT created_at AS date, name AS description, value FROM assets WHERE 1=1 $whereFilterAset ORDER BY created_at ASC"
);
while ($row = $result_aset->fetch_assoc()) {
  $logs[] = [
    "date" => $row["date"],
    "desc" => "Pembelian Aset: " . $row["description"],
    "debit" => $row["value"],
    "credit" => 0,
  ];
}

// Ambil biaya kontainer yang sudah terverifikasi dan akumulasi per kontainer
$result_exp = $conn->query("
  SELECT e.container_id, c.container_number, SUM(e.amount) AS total_expense, MAX(e.expense_date) AS expense_date
  FROM expenses e
  JOIN containers c ON e.container_id = c.id
  WHERE c.status = 'verified' $whereFilterExp
  GROUP BY e.container_id, c.container_number
  ORDER BY expense_date ASC
");

while ($row = $result_exp->fetch_assoc()) {
  $logs[] = [
    "date" => $row["expense_date"],
    "desc" => "Biaya Kontainer " . $row["container_number"],
    "debit" => $row["total_expense"],
    "credit" => 0,
  ];
}

// Ambil penjualan kontainer
$result_kontainer = $conn->query("
  SELECT c.id, c.updated_at AS date, c.container_number, c.selling_price
  FROM containers c
  WHERE c.status = 'verified' AND c.selling_price IS NOT NULL $whereFilterKont
  ORDER BY c.updated_at ASC
");

while ($row = $result_kontainer->fetch_assoc()) {
  $container_id = $row["id"];
  $selling_price = $row["selling_price"];

  // Ambil total berat transaksi dari kontainer ini
  $result_weight = $conn->query("
    SELECT SUM(weight_kg) AS total_weight
    FROM transactions
    WHERE container_id = $container_id
  ");
  $weight_data = $result_weight->fetch_assoc();
  $total_weight = $weight_data["total_weight"] ?? 0;

  $total_penjualan = $total_weight * $selling_price;

  $logs[] = [
    "date" => $row["date"],
    "desc" => "Penjualan Kontainer " . $row["container_number"],
    "debit" => 0,
    "credit" => $total_penjualan,
  ];
}

// urutkan
usort($logs, function ($a, $b) {
  return strtotime($a["date"]) - strtotime($b["date"]);
});

// hitung saldo berjalan
$runningSaldo = 0;
foreach ($logs as $index => $log) {
  $runningSaldo += $log["credit"] - $log["debit"];
  $logs[$index]["saldo"] = $runningSaldo;
}

// pagination
$perPage = 10;
$totalData = count($logs);
$totalPages = ceil($totalData / $perPage);
$currentPage = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$start = ($currentPage - 1) * $perPage;

$logsPage = array_slice($logs, $start, $perPage);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporan Modal - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-icons text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Laporan Modal</h1>
    </div>
  </header>

  <main class="pt-24 pb-32 px-4 max-w-6xl mx-auto space-y-6">
    <div class="overflow-auto bg-white shadow rounded-lg p-4">

      <!-- Filter Form -->
      <form method="get" class="flex flex-col sm:flex-row items-start gap-2 mb-4">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Dari Tanggal</label>
          <input type="date" name="from" value="<?= htmlspecialchars(
            $from
          ) ?>" class="border px-2 py-1 rounded"/>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Sampai Tanggal</label>
          <input type="date" name="to" value="<?= htmlspecialchars(
            $to
          ) ?>" class="border px-2 py-1 rounded"/>
        </div>
        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-white mt-3 px-4 py-2 rounded">Filter</button>
      </form>
      
      <!-- <a href="export_excel_laporan_modal.php<?= $from && $to
        ? "?from=" . $from . "&to=" . $to
        : "" ?>"
         class="inline-block bg-gray-800 hover:bg-gray-900 text-yellow-400 px-4 py-2 rounded">
        Export Excel
      </a> -->

      <?php if ($errorMsg): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-2"><?= htmlspecialchars(
          $errorMsg
        ) ?></div>
      <?php endif; ?>

      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-left">Tanggal</th>
            <th class="px-4 py-2 text-left">Deskripsi</th>
            <th class="px-4 py-2 text-right">Debit</th>
            <th class="px-4 py-2 text-right">Kredit</th>
            <th class="px-4 py-2 text-right">Saldo Akhir</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-gray-800">
          <?php foreach ($logsPage as $log): ?>
            <tr>
              <td class="px-4 py-2"><?= htmlspecialchars(
                date("d/m/Y", strtotime($log["date"]))
              ) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($log["desc"]) ?></td>
              <td class="px-4 py-2 text-right"><?= $log["debit"]
                ? "Rp " . number_format($log["debit"], 0, ",", ".")
                : "-" ?></td>
              <td class="px-4 py-2 text-right"><?= $log["credit"]
                ? "Rp " . number_format($log["credit"], 0, ",", ".")
                : "-" ?></td>
              <td class="px-4 py-2 text-right font-semibold"><?= "Rp " .
                number_format($log["saldo"], 0, ",", ".") ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="min-w-full">
        <div class="flex justify-center mt-4 space-x-2">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i .
              ($from && $to ? "&from=$from&to=$to" : "") ?>"
               class="px-3 py-1 border rounded <?= $i === $currentPage
                 ? "bg-yellow-400 text-white"
                 : "bg-white text-gray-800" ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>

    </div>
  </main>
</body>
</html>