<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Ambil total modal
$sql_total_modal = "SELECT SUM(amount) as total FROM modal_log";
$total_modal = $conn->query($sql_total_modal)->fetch_assoc()["total"] ?? 0;

// Ambil total nilai aset aktif
$sql_total_aset = "SELECT SUM(value) as total FROM assets WHERE status = 'aktif'";
$total_aset = $conn->query($sql_total_aset)->fetch_assoc()["total"] ?? 0;

// Ambil riwayat modal
$sql_riwayat_modal = "SELECT * FROM modal_log ORDER BY date DESC";
$riwayat_modal = $conn->query($sql_riwayat_modal);

// Ambil daftar aset
$sql_daftar_aset = "SELECT * FROM assets ORDER BY name ASC";
$daftar_aset = $conn->query($sql_daftar_aset);

// Total Pengeluaran Kontainer (status lunas, per container_id)
$sql_expenses = "
  SELECT 
    c.container_number,
    MAX(e.expense_date) AS latest_date,
    SUM(e.amount) AS total
  FROM expenses e
  JOIN containers c ON e.container_id = c.id
  WHERE c.status = 'lunas'
  GROUP BY e.container_id
  ORDER BY latest_date DESC
";
$data_pengeluaran_kontainer = $conn->query($sql_expenses);

// Total seluruh DP supplier
$sql_total_dp = "SELECT (SUM(debit) - SUM(credit)) AS total FROM deposits_supplier";
$total_dp_supplier = $conn->query($sql_total_dp)->fetch_assoc()["total"] ?? 0;

// Total Deposit Supplier (per supplier_id)
$sql_deposit = "
  SELECT s.name, s.id, SUM(d.debit) AS total
  FROM deposits_supplier d
  JOIN suppliers s ON d.supplier_id = s.id
  GROUP BY d.supplier_id
  ORDER BY s.name ASC
";
$data_deposit_supplier = $conn->query($sql_deposit);

// =======================
// Hitung Total Pengeluaran
// =======================

// 1.1 Ambil total grand_total dari seluruh transaksi yang sudah terhubung ke container (counted)
$sqlTransaksi1 = "
  SELECT t.grand_total 
  FROM transactions t 
  JOIN containers c ON t.container_id = c.id 
  WHERE c.status = 'counted' AND c.status = 'full'
";

$transaksi1 = $conn->query($sqlTransaksi1);

$grand_total_counted = 0;
while ($row = $transaksi1->fetch_assoc()) {
  $grand_total_counted += $row["grand_total"];
}

// 1.2 Ambil total total_price dari seluruh transaksi yang sudah terhubung ke seluruh container
$sqlTransaksi2 = "
  SELECT t.total_price 
  FROM transactions t 
  JOIN containers c ON t.container_id = c.id
";
$transaksi2 = $conn->query($sqlTransaksi2);

$grand_total_price = 0;
while ($row = $transaksi2->fetch_assoc()) {
  $grand_total_price += $row["total_price"];
}

// 1.3 Ambil total total_price dari seluruh transaksi yang sudah terhubung ke seluruh container
$sqlTransaksi3 = "
  SELECT t.total_price 
  FROM transactions t 
  JOIN containers c ON t.container_id = c.id 
  WHERE c.status IN ('draft', 'counted', 'verified', 'accepted')
";
$transaksi3 = $conn->query($sqlTransaksi3);

$verified_total_price = 0;
while ($row = $transaksi3->fetch_assoc()) {
  $verified_total_price += $row["total_price"];
}

// 1.4 Ambil total total_fee dari seluruh transaksi yang sudah terhubung ke seluruh container
$sqlTransaksi4 = "
  SELECT t.total_fee 
  FROM transactions t 
  JOIN containers c ON t.container_id = c.id
  WHERE c.status IN ('draft', 'counted', 'verified', 'accepted')
";
$transaksi4 = $conn->query($sqlTransaksi4);

$grand_total_fee = 0;
while ($row = $transaksi4->fetch_assoc()) {
  $grand_total_fee += $row["total_fee"];
}

// 2. Ambil total expenses dari semua container lunas
$sqlExpense = "
  SELECT e.amount 
  FROM expenses e 
  JOIN containers c ON e.container_id = c.id 
  WHERE c.status = 'verified'
";
$expenses = $conn->query($sqlExpense);

$total_operasional = 0;
while ($row = $expenses->fetch_assoc()) {
  $total_operasional += $row["amount"];
}

// 3. Gabungkan jadi total pengeluaran
$total_pengeluaran = $grand_total_verified + $total_operasional;

// 4. Hitung sisa dana dari DP supplier
// $grand_total_dp_supplier = $total_dp_supplier - $total_pengeluaran;
$grand_total_dp_supplier = $total_dp_supplier - $grand_total_counted;

// 5. Gabungkan jadi sisa DP Supplier
$sisa_dp_supplier = $total_dp_supplier - $grand_total_price;

// =======================
// Hitung saldo tersisa (dana tersisa yang tidak dipakai DP maupun aset)
// =======================
$sisa_modal = $total_modal - $total_aset;
$total_pembelian_fee = $verified_total_price + $grand_total_fee;
$total_pembelian_sisadp = $total_pembelian_fee + $sisa_dp_supplier;
$saldo_tersisa = $sisa_modal - $total_pembelian_fee - $sisa_dp_supplier;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Modal dan Aset - Fayyfir</title>
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
      <h1 class="text-lg font-semibold">Modal dan Aset</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-4xl mx-auto space-y-6">
    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Total Modal Masuk</p>
        <p class="text-2xl text-green-600 font-semibold">Rp <?= number_format(
          $total_modal,
          0,
          ",",
          "."
        ) ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Total Nilai Aset Aktif</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format(
          $total_aset,
          0,
          ",",
          "."
        ) ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Sisa Modal</p>
        <p class="text-2xl text-green-600 font-semibold">Rp <?= number_format($sisa_modal, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow" hidden>
        <p class="text-gray-500 text-sm">Total DP Supplier</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format($total_dp_supplier, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Total Pembelian</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format($total_pembelian_fee, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Sisa DP Supplier</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format($sisa_dp_supplier, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Total Pembelian dan DP</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format($total_pembelian_sisadp, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow" hidden>
        <p class="text-gray-500 text-sm">Total Fee</p>
        <p class="text-2xl text-red-600 font-semibold">Rp <?= number_format($grand_total_fee, 0, ",", ".") ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <p class="text-gray-500 text-sm">Total Saldo Tersisa</p>
        <p class="text-2xl text-green-600 font-semibold">Rp <?= number_format(
          $saldo_tersisa,
          0,
          ",",
          "."
        ) ?></p>
      </div>
    </div>

    <!-- Section Modal -->
    <section>
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-md font-semibold">MODAL</h2>
        <a href="tambah-modal" class="flex items-center space-x-1 bg-gray-800 hover:bg-yellow-400 text-white text-sm px-3 py-2 rounded transition lg:space-x-2">
          <span class="material-symbols-outlined text-yellow-400 lg:mr-1">add_circle</span>
          <span class="hidden lg:inline text-white">Tambah Modal</span>
        </a>
      </div>
      <!-- tabel modal -->
      <div class="overflow-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-4 py-2 text-center">Deskripsi</th>
              <th class="px-4 py-2 text-center">Nilai</th>
              <th class="px-4 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php while ($modal = $riwayat_modal->fetch_assoc()): ?>
              <tr>
                <td class="px-4 py-2 text-left"><?= date("d/m/Y", strtotime($modal["date"])) ?></td>
                <td class="px-4 py-2 text-left"><?= htmlspecialchars($modal["description"]) ?></td>
                <td class="px-4 py-2 text-right"><?= number_format($modal["amount"], 0, ",", ".") ?></td>
                <td class="px-4 py-2 text-center space-x-2 whitespace-nowrap">
                  <a href="edit-modal.php?id=<?= $modal['id'] ?>" class="text-blue-500 hover:text-blue-600">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <form method="POST" action="hapus-modal.php" onsubmit="return confirm('Yakin ingin menghapus data modal ini?')" style="display:inline">
                    <input type="hidden" name="id" value="<?= $modal['id'] ?>">
                    <button type="submit" class="text-red-500 hover:text-red-600">
                      <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    
    <!-- Section Aset -->
    <section>
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-md font-semibold">ASET</h2>
        <a href="tambah-aset" class="flex items-center space-x-1 bg-gray-800 hover:bg-yellow-400 text-white text-sm px-3 py-2 rounded transition lg:space-x-2">
          <span class="material-symbols-outlined text-yellow-400 lg:mr-1">add_circle</span>
          <span class="hidden lg:inline text-white">Tambah Aset</span>
        </a>
      </div>
      <!-- tabel aset -->
      <div class="overflow-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Nama Aset</th>
              <th class="px-4 py-2 text-center">Status</th>
              <th class="px-4 py-2 text-center">Nilai</th>
              <th class="px-4 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php while ($aset = $daftar_aset->fetch_assoc()): ?>
            <tr>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $aset["name"]
              ) ?></td>
              <td class="px-4 py-2 text-center"><?= htmlspecialchars(
                $aset["status"]
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $aset["value"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-center space-x-2 whitespace-nowrap">
                  <a href="edit-aset.php?id=<?= $aset['id'] ?>" class="text-blue-500 hover:text-blue-600">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <form method="POST" action="hapus-aset.php" onsubmit="return confirm('Yakin ingin menghapus data modal ini?')" style="display:inline">
                    <input type="hidden" name="id" value="<?= $aset['id'] ?>">
                    <button type="submit" class="text-red-500 hover:text-red-600">
                      <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                  </form>
                </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    
    <!-- Section Pengeluaran Kontainer -->
    <section>
      <h2 class="text-md font-semibold mb-2 mt-6">PENGELUARAN PER KONTAINER</h2>
      <div class="overflow-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-4 py-2 text-center">Deskripsi</th>
              <th class="px-4 py-2 text-center">Nilai</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php while ($row = $data_pengeluaran_kontainer->fetch_assoc()): ?>
            <tr>
              <td class="px-4 py-2 text-left"><?= date("d/m/Y", strtotime($row["last_date"])) ?></td>
              <td class="px-4 py-2 text-left">Pengeluaran (<?= htmlspecialchars($row["container_number"]) ?>)</td>
              <td class="px-4 py-2 text-right"><?= number_format($row["total"], 0, ",", ".") ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    
    <!-- Section Deposit Supplier -->
    <section>
      <h2 class="text-md font-semibold mb-2 mt-6">DEPOSIT SUPPLIER</h2>
      <div class="overflow-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Nomor</th>
              <th class="px-4 py-2 text-center">Nama</th>
              <th class="px-4 py-2 text-center">Nilai</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php 
            $no = 1; 
            while ($row = $data_deposit_supplier->fetch_assoc()): ?>
            <tr>
              <td class="px-4 py-2 text-center"><?= str_pad($no++, 3, '0', STR_PAD_LEFT) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["name"]) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($row["total"], 0, ",", ".") ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>