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

if ($region && $region !== 'Gaharu') {
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
  $notes = $_POST["catatan"];
  $supplier_id = intval($_POST["supplier"]);

  $role_id = $_SESSION["role_id"] ?? null;

  // Logic for weight input preservation
  if ($role_id == 1 && ($transaksi["weight_input_by_role"] == 3 || $transaksi["weight_input_by_role"] == 2)) {
    $sack_count = $transaksi["sack_count"];
    $weight = $transaksi["weight_kg"];
    $weight_input_by_role = $transaksi["weight_input_by_role"];
  } else {
    $sack_count = $_POST["jumlah_karung"];
    $weight = $_POST["berat"];
    $weight_input_by_role = $role_id;
  }

  // Logic for price input preservation
  if ($role_id == 1 && ($transaksi["price_input_by_role"] == 3 || $transaksi["price_input_by_role"] == 2)) {
    $price_per_kg = $transaksi["price_per_kg"];
    $total_price = $transaksi["total_price"];
    $fee_per_kg = $transaksi["fee_per_kg"];
    $total_fee = $transaksi["total_fee"];
    $grand_total = $transaksi["grand_total"];
    $price_input_by_role = $transaksi["price_input_by_role"];
  } else {
    $price_per_kg = $_POST["harga_per_kg"];
    $total_price = $_POST["total_harga"];
    $fee_per_kg = $_POST["fee_per_kg"];
    $total_fee = $_POST["total_fee"];
    $grand_total = $_POST["grand_total"];
    $price_input_by_role = $role_id;
  }

  $stmt = $conn->prepare("UPDATE transactions SET transaction_date=?, driver_name=?, driver_phone=?, vehicle_plate=?, sack_count=?, weight_kg=?, price_per_kg=?, fee_per_kg=?, total_price=?, total_fee=?, grand_total=?, notes=?, supplier_id=?, weight_input_by_role=?, price_input_by_role=? WHERE id=?");
  $stmt->bind_param("ssssiiidddisiiii", $date, $driver_name, $driver_phone, $vehicle_plate, $sack_count, $weight, $price_per_kg, $fee_per_kg, $total_price, $total_fee, $grand_total, $notes, $supplier_id, $weight_input_by_role, $price_input_by_role, $transaction_id);

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
  <!-- Select2 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    /* Premium Select2 Styling Overrides to match Tailwind Form controls */
    .select2-container .select2-selection--single {
      height: 38px !important;
      border: 1px solid #d1d5db !important;
      border-radius: 0.375rem !important;
      display: flex !important;
      align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 36px !important;
      padding-left: 12px !important;
      color: #1f2937 !important;
      font-size: 0.875rem !important;
    }
    .select2-dropdown {
      border: 1px solid #d1d5db !important;
      border-radius: 0.375rem !important;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
      z-index: 9999 !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border: 1px solid #d1d5db !important;
      border-radius: 0.375rem !important;
      padding: 6px 12px !important;
      outline: none !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
      border-color: #fbbf24 !important; /* Tailwind's yellow-400 */
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background-color: #fbbf24 !important; /* Tailwind's yellow-400 */
      color: #1f2937 !important;
    }
    .select2-container--default .select2-results__option[aria-selected="true"] {
      background-color: #f3f4f6 !important;
      color: #1f2937 !important;
    }
  </style>
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
        <div class="flex-grow">
          <select name="supplier" id="supplierSelect" class="w-full">
            <option value="">-- Pilih Nama Petani --</option>
            <?php while ($row = $supplier_result->fetch_assoc()): ?>
              <option value="<?= $row["id"] ?>" <?= $row["id"] == $transaksi['supplier_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row["name"]) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <a href="tambah-supplier" class="bg-gray-800 hover:bg-yellow-400 text-white rounded-md px-3 flex items-center justify-center transition shrink-0 h-[38px] w-[38px]">
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

    <?php
    $session_role_id = $_SESSION["role_id"] ?? null;
    $is_weight_readonly = ($session_role_id == 1 && ($transaksi["weight_input_by_role"] == 3 || $transaksi["weight_input_by_role"] == 2));
    ?>

    <div>
      <label class="block text-sm font-medium">Jumlah Karung</label>
      <input type="text" id="jumlah_karung_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md <?= $is_weight_readonly ? 'bg-gray-100' : '' ?>" value="<?= number_format($transaksi['sack_count'], 0, ',', '.') ?>" <?= $is_weight_readonly ? 'readonly' : '' ?> />
      <input type="hidden" id="jumlah_karung" name="jumlah_karung" value="<?= $transaksi['sack_count'] ?>"/>
    </div>

    <div>
      <label class="block text-sm font-medium">Total Berat (kg)</label>
      <input type="text" id="berat_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md <?= $is_weight_readonly ? 'bg-gray-100' : '' ?>" value="<?= number_format($transaksi['weight_kg'], 0, ',', '.') ?>" <?= $is_weight_readonly ? 'readonly' : '' ?> />
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

<!-- jQuery and Select2 JS CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
  const $supplierSelect = $('#supplierSelect');

  // Inisialisasi Select2
  $supplierSelect.select2({
    placeholder: "-- Pilih Nama Petani --",
    width: '100%'
  });

  // Tambahkan placeholder ke kolom input pencarian Select2 saat dibuka
  $supplierSelect.on('select2:open', function() {
    setTimeout(function() {
      const searchField = document.querySelector('.select2-search__field');
      if (searchField) {
        searchField.placeholder = "Cari nama supplier/petani...";
      }
    }, 0);
  });
});

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