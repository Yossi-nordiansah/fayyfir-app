<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$transaction_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($transaction_id === 0) {
  echo "Transaksi tidak ditemukan.";
  exit();
}

// Ambil data transaksi
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();
$transaksi = $result->fetch_assoc();

if (!$transaksi) {
  echo "Transaksi tidak ditemukan.";
  exit();
}

$container_id = $transaksi["container_id"];

/* =========================
   FILTER SUPPLIER BY REGION
========================= */
$region  = $_SESSION["region"] ?? null;

if ($region) {
  $stmtSupplier = $conn->prepare("
    SELECT id, name 
    FROM suppliers 
    WHERE region_name = ?
    ORDER BY name ASC
  ");
  $stmtSupplier->bind_param("s", $region);
  $stmtSupplier->execute();
  $supplier_result = $stmtSupplier->get_result();
} else {
  $supplier_result = $conn->query("
    SELECT id, name 
    FROM suppliers 
    ORDER BY name ASC
  ");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $date = $_POST["tanggal"];
  $driver_name = $_POST["nama_driver"];
  $driver_phone = $_POST["no_telp_driver"];
  $vehicle_plate = $_POST["plat_nomor"];
  $sack_count = $_POST["jumlah_karung"];
  $weight = $_POST["berat"];
  $price_per_kg = $_POST["harga_per_kg"];
  $total_price = $_POST["total_harga"];
  $fee_per_kg = $_POST["fee_per_kg"];
  $total_fee = $_POST["total_fee"];
  $grand_total = $_POST["grand_total"];
  $notes = $_POST["catatan"];

  $supplier_id = intval($_POST["supplier"]);

  $stmt = $conn->prepare("UPDATE transactions SET transaction_date=?, driver_name=?, driver_phone=?, vehicle_plate=?, sack_count=?, weight_kg=?, price_per_kg=?, fee_per_kg=?, total_price=?, total_fee=?, grand_total=?, notes=?, supplier_id=? WHERE id=?");
  $stmt->bind_param("ssssiiidddisii", $date, $driver_name, $driver_phone, $vehicle_plate, $sack_count, $weight, $price_per_kg, $fee_per_kg, $total_price, $total_fee, $grand_total, $notes, $supplier_id, $transaction_id);

  if ($stmt->execute()) {
    header("Location: riwayat-kontainer.php?id=" . $container_id);
    exit();
  } else {
    echo "Gagal memperbarui transaksi.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Transaksi - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-icons text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Edit Transaksi</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">

    <div>
      <label class="block text-sm font-medium">Tanggal</label>
      <input type="datetime-local" name="tanggal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= date('Y-m-d\TH:i', strtotime($transaksi['transaction_date'])) ?>" />
    </div>

    <div>
      <label class="block text-sm font-medium">Nama Petani / Supplier</label>
      <div class="flex gap-2">
        <select name="supplier" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md">
          <option value="">-- Pilih Nama Petani --</option>
          <?php while ($row = $supplier_result->fetch_assoc()): ?>
            <option value="<?= $row["id"] ?>" <?= $row["id"] == $transaksi['supplier_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($row["name"]) ?>
            </option>
          <?php endwhile; ?>
        </select>
        <a href="tambah-supplier" class="bg-gray-800 hover:bg-yellow-400 text-white rounded-md px-3 flex items-center justify-center transition">
          <span class="material-icons text-yellow-400 group-hover:text-gray-800 transition">add</span>
        </a>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium">Nama Driver</label>
      <input type="text" name="nama_driver" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($transaksi['driver_name']) ?>" />
    </div>

    <div>
      <label class="block text-sm font-medium">No. Telp. Driver</label>
      <input type="text" name="no_telp_driver" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($transaksi['driver_phone']) ?>" />
    </div>

    <div>
      <label class="block text-sm font-medium">Plat Nomor</label>
      <input type="text" name="plat_nomor" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($transaksi['vehicle_plate']) ?>" />
    </div>

    <div>
      <label class="block text-sm font-medium">Jumlah Karung</label>
      <input type="text" id="jumlah_karung_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($transaksi['sack_count'], 0, ',', '.') ?>" />
      <input type="hidden" id="jumlah_karung" name="jumlah_karung" value="<?= $transaksi['sack_count'] ?>"/>
    </div>

    <div>
      <label class="block text-sm font-medium">Total Berat (kg)</label>
      <input type="text" id="berat_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($transaksi['weight_kg'], 0, ',', '.') ?>" />
      <input type="hidden" id="berat" name="berat" value="<?= $transaksi['weight_kg'] ?>"/>
    </div>

    <div hidden>
      <input type="hidden" id="harga_per_kg" name="harga_per_kg" value="<?= $transaksi['price_per_kg'] ?>"/>
      <input type="hidden" id="total_harga" name="total_harga" value="<?= $transaksi['total_price'] ?>"/>
      <input type="hidden" id="fee_per_kg" name="fee_per_kg" value="<?= $transaksi['fee_per_kg'] ?>"/>
      <input type="hidden" id="total_fee" name="total_fee" value="<?= $transaksi['total_fee'] ?>"/>
      <input type="hidden" id="grand_total" name="grand_total" value="<?= $transaksi['grand_total'] ?>"/>
    </div>

    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <textarea name="catatan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($transaksi['notes']) ?></textarea>
    </div>

    <div>
      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white py-2 rounded-md">Perbarui Transaksi</button>
    </div>

  </form>
</main>

<script>
const formatter = new Intl.NumberFormat("id-ID");

function parseRibuan(str) {
  return parseInt(str.replace(/\./g, "")) || 0;
}

function updateFormattedInput(display, hidden) {
  const value = parseRibuan(display.value);
  hidden.value = value;
  display.value = value ? formatter.format(value) : "";
}

const jumlahKarungDisplay = document.getElementById("jumlah_karung_display");
const jumlahKarung = document.getElementById("jumlah_karung");

const beratDisplay = document.getElementById("berat_display");
const berat = document.getElementById("berat");

jumlahKarungDisplay.addEventListener("input", function () {
  updateFormattedInput(jumlahKarungDisplay, jumlahKarung);
});

beratDisplay.addEventListener("input", function () {
  updateFormattedInput(beratDisplay, berat);
});
</script>

</body>
</html>