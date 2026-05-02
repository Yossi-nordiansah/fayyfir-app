<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit();
}

// Cek apakah token di session masih sama dengan di database
$stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($db_token);
$stmt->fetch();
$stmt->close();

if ($db_token !== $_SESSION["session_token"]) {
  session_destroy();
  header("Location: login.php?force_logout=1");
  exit();
}

$level = $_SESSION["role_id"] ?? "";
$user_id = $_SESSION["user_id"] ?? 0;

// Ambil ID kontainer dari URL
$container_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($container_id === 0) {
  echo "Kontainer tidak ditemukan.";
  exit();
}

// Ambil detail kontainer
$kontainer = $conn
  ->query("SELECT * FROM containers WHERE id = $container_id")
  ->fetch_assoc();
if (!$kontainer) {
  echo "Data kontainer tidak ditemukan.";
  exit();
}

$container_number = $kontainer["container_number"] ?? "Tidak Diketahui";

$region_name = $kontainer["region_name"] ?? "Tidak Diketahui";

// Ambil data transaksi
$sqlTransaksi = "SELECT t.*, s.name AS supplier_name FROM transactions t 
                JOIN suppliers s ON t.supplier_id = s.id
                WHERE t.container_id = $container_id ORDER BY transaction_date DESC";
$transaksi = $conn->query($sqlTransaksi);

// Ambil data expenses
$sqlExpense = "SELECT * FROM expenses WHERE container_id = $container_id ORDER BY expense_date DESC";
$expenses = $conn->query($sqlExpense);

// Hitung total
$total_karung = 0;
$total_berat = 0;
$total_pembelian = 0;
$total_fee = 0;
$grand_total = 0;
$total_jumlah_jual = 0; // Tambahan untuk jumlah jual

while ($row = $transaksi->fetch_assoc()) {
  $total_karung += $row["sack_count"];
  $total_berat += $row["weight_kg"];
  $total_pembelian += $row["total_price"];
  $total_fee += $row["total_fee"];
  $grand_total += $row["grand_total"];

  // Hitung harga jual
  $harga_jual = $kontainer["selling_price"] ?? 0;
  $jumlah_jual = $row["weight_kg"] * $harga_jual;
  $total_jumlah_jual += $jumlah_jual;
  
  // Hitung harga beli + fee
  $harga_beli_fee = $row["fee_per_kg"] + $row["price_per_kg"];

  // Simpan ke array untuk ditampilkan
  $row["harga_jual"] = $harga_jual;
  $row["jumlah_jual"] = $jumlah_jual;
  $row["harga_beli_fee"] = $harga_beli_fee;
  $data_transaksi[] = $row;
}
$transaksi->data_seek(0); // reset pointer

$total_operasional = 0;
$data_expenses = [];

while ($row = $expenses->fetch_assoc()) {
    // Jika tipe pengeluaran adalah "Bayar timbang"
    if (strcasecmp(trim($row['expense_type']), 'Bayar timbang') === 0) {
        $total_timbang = $total_berat * 50; // hitung ulang
        $row['amount'] = $total_timbang;    // ganti amount di array
    }

    $total_operasional += $row["amount"];
    $data_expenses[] = $row;
}

$expenses->data_seek(0);

$total_pengeluaran = $grand_total + $total_operasional;

$margin = $total_jumlah_jual - $total_pengeluaran;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Kontainer - Fayyfir</title>
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
      <?php if (isset($kontainer) &&  $kontainer["status"] === "draft" || $kontainer["status"] === "counted" || $kontainer["status"] === "full"): ?>
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <?php endif; ?>
      <?php if (isset($kontainer) && $kontainer["status"] === "verified"): ?>
      <a href="verifikasi" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <?php endif; ?>
      <h1 class="text-lg font-semibold">Riwayat <?= htmlspecialchars(
        $container_number
      ) ?></h1>
    </div>
  </header>  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto">
    
    <!-- Button Full -->
    <?php if (isset($kontainer) && ($kontainer["status"] === "draft" || $kontainer["status"] === "counted")): ?>
      <div class="block md:hidden fixed bottom-0 left-0 right-0 bg-tranparent shadow-md z-50">
        <div class="p-3 flex justify-center gap-3">
          <a href="ubah-status-kontainer.php?id=<?= $kontainer["id"] ?>" 
             class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
            <span class="material-symbols-outlined text-base mr-1">check_circle</span> Kontainer Full
          </a>
        </div>
      </div>
      
      <div class="hidden md:flex justify-center mt-4 gap-3">
        <a href="ubah-status-kontainer.php?id=<?= $kontainer["id"] ?>" 
           class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
          <span class="material-symbols-outlined text-base mr-1">check_circle</span> Kontainer Full
        </a>
      </div>
    <?php endif; ?>
    
    <?php if ($level == "2" || $level == "3"): ?>
    <!-- Button Verifikasi -->
    <?php if (isset($kontainer) && $kontainer["status"] === "full"): ?>
      <div class="block md:hidden fixed bottom-0 left-0 right-0 bg-tranparent shadow-md z-50">
        <div class="p-3 flex justify-center gap-3">
          <a href="ubah-status-verifikasi.php?id=<?= $kontainer["id"] ?>" 
             class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
            <span class="material-symbols-outlined text-base mr-1">check_circle</span> Verifikasi
          </a>
        </div>
      </div>
    
      <div class="hidden md:flex justify-center mt-4 gap-3">
        <a href="ubah-status-verifikasi.php?id=<?= $kontainer["id"] ?>" 
           class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
          <span class="material-symbols-outlined text-base mr-1">check_circle</span> Verifikasi
        </a>
      </div>
    <?php endif; ?>
    
    <!-- Button Invoice -->
    <?php if (isset($kontainer) && $kontainer["status"] === "verified"): ?>
      <div class="block md:hidden fixed bottom-0 left-0 right-0 bg-tranparent shadow-md z-50">
        <div class="p-3 flex justify-center">
          <a href="form-invoice.php?id=<?= $kontainer["id"] ?>" 
             class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
            <span class="material-symbols-outlined text-base mr-1">check_circle</span> Cetak Invoice
          </a>
        </div>
      </div>
    
      <div class="hidden md:flex justify-center mt-4">
        <a href="form-invoice.php?id=<?= $kontainer["id"] ?>" 
           class="inline-flex items-center px-4 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-semibold rounded">
          <span class="material-symbols-outlined text-base mr-1">check_circle</span> Cetak Invoice
        </a>
      </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Tabel Transaksi -->
    <section>
      <div class="flex justify-end gap-2 mb-4">
        <a href="edit-kontainer-admin.php?id=<?= $container_id ?>" class="group flex items-center bg-blue-800 hover:bg-blue-900 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-white">add_circle</span>
          <span class="ml-2">Edit Kontainer</span>
        </a>
        <?php if ($level == "2" || $level == "3"): ?>
        <form action="hapus-kontainer.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus kontainer ini?');" class="inline">
          <input type="hidden" name="id" value="<?= $container_id ?>">
          <button type="submit" class="group flex items-center bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded text-sm transition">
            <span class="material-symbols-outlined text-sm text-white">delete</span>
            <span class="ml-2">Hapus Kontainer</span>
          </button>
        </form>
        <?php endif; ?>
      </div>
      <div class="flex justify-between items-center mb-2">
        <div>
          <h2 class="text-md font-semibold">PENGISIAN <?= htmlspecialchars(
            $container_number
          ) ?></h2>
          <span class="text-sm">Area: <?= htmlspecialchars(
            $region_name
          ) ?></span>
        </div>
        <a href="tambah-transaksi?container_id=<?= $container_id ?>" class="flex items-center space-x-1 bg-gray-800 hover:bg-yellow-400 text-white text-sm px-3 py-2 rounded transition lg:space-x-2">
          <span class="material-symbols-outlined text-yellow-400 lg:mr-1">add_circle</span>
          <span class="hidden lg:inline text-white">Tambah Transaksi</span>
        </a>
      </div>
      <!-- tabel transaksi tetap -->
      <div class="overflow-auto bg-white shadow rounded-lg mb-2">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-4 py-2 text-center">Petani/Supplier</th>
              <th class="px-4 py-2 text-center">Karung</th>
              <th class="px-4 py-2 text-center">Berat (Kg)</th>
              <th class="px-4 py-2 text-center">Keterangan</th>
              <th class="px-4 py-2 text-center">Aksi Admin</th>
              <?php if ($level == "2" || $level == "3"): ?>
              <th class="px-4 py-2 text-center">Harga Beli</th>
              <th class="px-4 py-2 text-center">Total Harga Beli</th>
              <th class="px-4 py-2 text-center">Aksi</th>
              <th class="px-4 py-2 text-center">Harga Jual</th>
              <th class="px-4 py-2 text-center">Jumlah Jual</th>
              <th class="px-4 py-2 text-center">Aksi</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php foreach ($data_transaksi as $row): ?>
            <tr>
              <td class="px-4 py-2 text-left"><?= date(
                "d/m/Y",
                strtotime($row["transaction_date"])
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["supplier_name"]
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["sack_count"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["weight_kg"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["notes"]
              ) ?></td>
              <td class="px-4 py-2 text-center">
                <button onclick='showTrxModalAdmin(<?= json_encode([
                  "id" => $row["id"],
                  "nama" => $row["supplier_name"],
                  "berat" => intval($row["weight_kg"]),
                  "tanggal" => $row["transaction_date"],
                ]) ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
              </td>
              <?php if ($level == "2" || $level == "3"): ?>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["harga_beli_fee"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["grand_total"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-center">
                <?php if ($level == "3"): ?>
                <button onclick='showTrxModal(<?= json_encode([
                  "id" => $row["id"],
                  "nama" => $row["supplier_name"],
                  "berat" => intval($row["weight_kg"]),
                  "harga" => $row["harga_beli_fee"],
                  "total" => $row["grand_total"],
                  "tanggal" => $row["transaction_date"],
                ]) ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
                <?php endif; ?>
                <?php if ($level == "2"): ?>
                <button onclick='showTrxModalSpv(<?= json_encode([
                  "id" => $row["id"],
                  "nama" => $row["supplier_name"],
                  "berat" => intval($row["weight_kg"]),
                  "total" => $row["grand_total"],
                  "tanggal" => $row["transaction_date"],
                ]) ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["harga_jual"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["jumlah_jual"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-center">
                <button onclick='showKtnModal(<?= json_encode([
                  "id2" => $container_id,
                  "jual" => $row["harga_jual"],
                  "nomor" => $kontainer["container_number"],
                  "ekspedisi" => $kontainer["expedition"],
                  "pelayaran" => $kontainer["shipping_line"],
                ]) ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="bg-gray-100 text-gray-800 font-semibold">
            <tr>
              <td class="px-4 py-2 text-right" colspan="2">TOTAL</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_karung,
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_berat,
                0,
                ",",
                "."
              ) ?></td>
              <td></td>
              <td></td>
              <td></td>
              <?php if ($level == "2" || $level == "3"): ?>
              <td class="px-4 py-2 text-right"><?= number_format(
                $grand_total,
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-right"></td>
              <td class="px-4 py-2 text-center"></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_jumlah_jual,
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-center"></td>
              <?php endif; ?>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>
    
    <!-- Tabel Pengeluaran -->
    <?php if ($level == "2" || $level == "3"): ?>    
    <section>
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-md font-semibold">BIAYA PENGELUARAN</h2>
        <a href="tambah-pengeluaran?container_id=<?= $container_id ?>" class="flex items-center space-x-1 bg-gray-800 hover:bg-yellow-400 text-white text-sm px-3 py-2 rounded transition lg:space-x-2">
          <span class="material-symbols-outlined text-yellow-400 lg:mr-1">add_circle</span>
          <span class="hidden lg:inline text-white">Tambah Pengeluaran</span>
        </a>
      </div>
      <!-- tabel pengeluaran tetap -->
      <div class="overflow-auto bg-white shadow rounded-lg mb-2">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-8 py-2 text-center">Deskripsi</th>
              <th class="px-4 py-2 text-center">Jumlah</th>
              <th class="px-12 py-2 text-center">Keterangan</th>
              <th class="px-12 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <?php foreach ($data_expenses as $row): ?>
            <tr>
              <td class="px-4 py-2 text-left"><?= date(
                "d/m/Y",
                strtotime($row["expense_date"])
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["expense_type"]
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $row["amount"],
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["notes"]
              ) ?></td>
              <td class="px-4 py-2 text-center">
                <button onclick='showOprModal(<?= json_encode([
                  "id" => $row["id"],
                  "tanggal" => $row["expense_date"],
                  "deskripsi" => $row["expense_type"],
                  "jumlah" => $row["amount"],
                  "keterangan" => $row["notes"],
                ]) ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="bg-gray-100 text-gray-800 font-semibold">
            <tr>
              <td class="px-4 py-2 text-right" colspan="2">TOTAL</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_operasional,
                0,
                ",",
                "."
              ) ?></td>
              <td class="px-4 py-2 text-left"></td>
              <td class="px-4 py-2 text-center"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>
    <?php endif; ?>
    
    <!-- Akumulasi -->
    <section>
      <?php if ($level == "2" || $level == "3"): ?>
      <h2 class="text-md font-semibold mb-2">AKUMULASI TOTAL</h2>
      <?php endif; ?>
      <div class="overflow-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <tbody class="text-gray-800 divide-y divide-gray-200">
            <!-- Untuk Admin -->
            <?php if ($level == "1"): ?>
            <tr hidden="">
              <td class="px-4 py-2 text-left">Total Transaksi</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_operasional,
                0,
                ",",
                "."
              ) ?></td>
            </tr>
            <?php endif; ?>
            
            <!-- Untuk SPV & Owner -->
            <?php if ($level == "2" || $level == "3"): ?>
            <tr>
              <td class="px-4 py-2 text-left">Total Penjualan</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_jumlah_jual,
                0,
                ",",
                "."
              ) ?></td>
            </tr>
            <tr>
              <td class="px-4 py-2 text-left">Total Pengeluaran</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $total_pengeluaran,
                0,
                ",",
                "."
              ) ?></td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100 text-gray-800 font-semibold">
            <tr>
              <td class="px-4 py-2 text-left">MARGIN</td>
              <td class="px-4 py-2 text-right"><?= number_format(
                $margin,
                0,
                ",",
                "."
              ) ?></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </section>

  </main>

<!-- Modals Transaksi Admin -->
<div id="trxModalAdmin" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
  <!-- Detail Transaksi -->
  <div class="bg-white rounded-xl w-[90%] max-w-md p-6 relative shadow-lg">
    <button onclick="closeModalAdmin()" class="absolute right-3 top-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Detail Transaksi</h2>

    <div class="space-y-2 text-sm text-gray-700">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Tanggal</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-tanggalAdmin"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Nama Supplier</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-namaAdmin"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Berat (Kg)</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-beratAdmin"></span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-3 my-6">
      <a id="btnEditAdmin" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
    </div>
  </div>
</div>

<!-- Modals Transaksi -->
<div id="trxModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
  <!-- Detail Transaksi -->
  <div class="bg-white rounded-xl w-[90%] max-w-md p-6 relative shadow-lg">
    <button onclick="closeModal()" class="absolute right-3 top-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Detail Transaksi</h2>

    <div class="space-y-2 text-sm text-gray-700">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Tanggal</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-tanggal"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Nama Supplier</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-nama"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Berat (Kg)</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-berat"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Harga/Kg</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-harga"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Total</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-total"></span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-3 my-6">
      <a id="btnEdit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
      <form method="POST" action="hapus-transaksi.php" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
        <input type="hidden" name="id" id="delete-id">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Hapus</button>
      </form>
    </div>
  </div>
</div>

<!-- Modals Transaksi SPV-->
<div id="trxModalSpv" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
  <!-- Detail Transaksi -->
  <div class="bg-white rounded-xl w-[90%] max-w-md p-6 relative shadow-lg">
    <button onclick="closeModalSpv()" class="absolute right-3 top-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Detail Transaksi</h2>

    <div class="space-y-2 text-sm text-gray-700">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Tanggal</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-tanggalSpv"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Nama Supplier</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-namaSpv"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Berat (Kg)</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-beratSpv"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Total</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-totalSpv"></span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-3 my-6">
      <a id="btnEditSpv" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
    </div>
  </div>
</div>

<!-- Modals Kontainer -->
<div id="ktnModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
  <!-- Detail Transaksi -->
  <div class="bg-white rounded-xl w-[90%] max-w-md p-6 relative shadow-lg">
    <button onclick="closeModal3()" class="absolute right-3 top-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Detail Kontainer</h2>
    <div class="space-y-2 text-sm text-gray-700">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Nomor Kontainer</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-nomor"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Harga Jual</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-jual"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Ekspedisi</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-ekspedisi"></span></td>
            <tr>
            <td class="font-semibold px-4 py-2 text-left">Pelayaran</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-pelayaran"></span></td>
          </tr>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <a id="btnEdit3" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
    </div>
  </div>
</div>

<!-- Modals Pengeluaran -->
<div id="oprModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-xl w-[90%] max-w-md p-6 relative shadow-lg">
    <button onclick="closeModal2()" class="absolute right-3 top-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
    <h2 class="text-lg font-semibold mb-4">Detail Pengeluaran</h2>

    <div class="space-y-2 text-sm text-gray-700">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Tanggal</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-tanggal2"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Deskripsi</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-deskripsi2"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Jumlah</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-jumlah2"></span></td>
          </tr>
          <tr>
            <td class="font-semibold px-4 py-2 text-left">Keterangan</td>
            <td class="px-4 py-2 text-left">:</td>
            <td class="py-2 text-left"><span id="modal-keterangan2"></span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <a id="btnEdit2" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
      <form method="POST" action="hapus-pengeluaran.php" onsubmit="return confirm('Yakin ingin menghapus operasional ini?')">
        <input type="hidden" name="id" id="delete-id2">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Hapus</button>
      </form>
    </div>
  </div>
</div>

<script>
// Modals Transaks Admin
function showTrxModalAdmin(data) {
  // Isi data ke dalam modal
  document.getElementById('modal-namaAdmin').innerText = data.nama;
  document.getElementById('modal-beratAdmin').innerText = formatRupiah(data.berat);
  document.getElementById('modal-tanggalAdmin').innerText = data.tanggal;

  // Edit button (optional)
  document.getElementById('btnEditAdmin').href = 'edit-transaksi-admin2.php?id=' + data.id;

  // Tampilkan modal
  document.getElementById('trxModalAdmin').classList.remove('hidden');
}

function closeModalAdmin() {
  document.getElementById('trxModalAdmin').classList.add('hidden');
}
  
// Modals Transaksi
function showTrxModal(data) {
  // Isi data ke dalam modal
  document.getElementById('modal-nama').innerText = data.nama;
  document.getElementById('modal-berat').innerText = formatRupiah(data.berat);
  document.getElementById('modal-harga').innerText = 'Rp. ' + formatRupiah(data.harga);
  document.getElementById('modal-total').innerText = 'Rp. ' + formatRupiah(data.total);
  document.getElementById('modal-tanggal').innerText = data.tanggal;

  // Edit & Delete button (optional)
  document.getElementById('btnEdit').href = 'edit-transaksi.php?id=' + data.id;
  document.getElementById('delete-id').value = data.id;

  // Tampilkan modal
  document.getElementById('trxModal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('trxModal').classList.add('hidden');
}

// Modals Transaks Spv
function showTrxModalSpv(data) {
  // Isi data ke dalam modal
  document.getElementById('modal-namaSpv').innerText = data.nama;
  document.getElementById('modal-beratSpv').innerText = formatRupiah(data.berat);
  document.getElementById('modal-totalSpv').innerText = 'Rp. ' + formatRupiah(data.total);
  document.getElementById('modal-tanggalSpv').innerText = data.tanggal;

  // Edit & Delete button (optional)
  document.getElementById('btnEditSpv').href = 'edit-transaksi-spv.php?id=' + data.id;

  // Tampilkan modal
  document.getElementById('trxModalSpv').classList.remove('hidden');
}

function closeModalSpv() {
  document.getElementById('trxModalSpv').classList.add('hidden');
}

// Modals Kontainer
function showKtnModal(data) {
  // Isi data ke dalam modal
  document.getElementById('modal-jual').innerText = 'Rp. ' + formatRupiah(data.jual);
  document.getElementById('modal-nomor').innerText = data.nomor;
  document.getElementById('modal-ekspedisi').innerText = data.ekspedisi;
  document.getElementById('modal-pelayaran').innerText = data.pelayaran;

  // Edit button (optional)
  document.getElementById('btnEdit3').href = 'edit-kontainer.php?id=' + data.id2;

  // Tampilkan modal
  document.getElementById('ktnModal').classList.remove('hidden');
}

function closeModal3() {
  document.getElementById('ktnModal').classList.add('hidden');
}

// Modals Operasional
function showOprModal(data) {
  // Isi data ke dalam modal
  document.getElementById('modal-tanggal2').innerText = data.tanggal;
  document.getElementById('modal-deskripsi2').innerText = data.deskripsi;
  document.getElementById('modal-jumlah2').innerText = 'Rp. ' + formatRupiah(data.jumlah);
  document.getElementById('modal-keterangan2').innerText = data.keterangan;

  // Edit & Delete button (optional)
  document.getElementById('btnEdit2').href = 'edit-pengeluaran.php?id=' + data.id;
  document.getElementById('delete-id2').value = data.id;

  // Tampilkan modal
  document.getElementById('oprModal').classList.remove('hidden');
}

function closeModal2() {
  document.getElementById('oprModal').classList.add('hidden');
}

function formatRupiah(angka) {
  return parseInt(angka).toLocaleString('id-ID');
}
</script>
</body>
</html>