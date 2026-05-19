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
$supplier_result = $conn->query("SELECT id, name FROM suppliers");    

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

  if ($_POST["supplier"] == "lainnya" && !empty($_POST["supplierBaru"])) {    
    $new_supplier = $_POST["supplierBaru"];    
    $stmt = $conn->prepare("INSERT INTO suppliers (name) VALUES (?)");    
    $stmt->bind_param("s", $new_supplier);    
    $stmt->execute();    
    $supplier_id = $stmt->insert_id;    
  } else {    
    $supplier_id = intval($_POST["supplier"]);    
  }    

  $stmt = $conn->prepare("UPDATE transactions SET transaction_date=?, driver_name=?, driver_phone=?, vehicle_plate=?, sack_count=?, weight_kg=?, price_per_kg=?, fee_per_kg=?, total_price=?, total_fee=?, grand_total=?, notes=?, supplier_id=? WHERE id=?");    
  $stmt->bind_param("ssssiiidddisii", $date, $driver_name, $driver_phone, $vehicle_plate, $sack_count, $weight, $price_per_kg, $fee_per_kg, $total_price, $total_fee, $grand_total, $notes, $supplier_id, $transaction_id);    

  if ($stmt->execute()) {    
    header("Location: riwayat-kontainer2.php?id=" . $container_id);    
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
    <a href="riwayat-kontainer2.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">  
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
      <label class="block text-sm font-medium mb-1">Nama Petani / Supplier</label> 
      <div class="flex gap-2">
        <select name="supplier" id="supplierSelect" class="w-full">  
          <option value="">-- Pilih Nama Petani --</option>  
          <?php while ($row = $supplier_result->fetch_assoc()): ?>  
            <option value="<?= $row["id"] ?>" <?= $row["id"] == $transaksi['supplier_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($row["name"]) ?>
            </option>  
          <?php endwhile; ?>  
          <option value="lainnya">+ Tambah Baru</option>
        </select>  
        <a href="tambah-supplier" class="bg-gray-800 hover:bg-yellow-400 text-white rounded-md px-3 flex items-center justify-center transition shrink-0 h-[38px] w-[38px]">
          <span class="material-icons text-yellow-400 group-hover:text-gray-800 transition">add</span>
        </a>
      </div>
      <input type="text" id="supplierBaru" name="supplierBaru" placeholder="Masukkan Nama Petani Baru"  
             class="mt-2 w-full px-3 py-2 border border-yellow-400 rounded-md hidden text-sm" />
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

    <div>  
      <label class="block text-sm font-medium">Harga per Kg</label>  
      <input type="text" id="harga_per_kg_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($transaksi['price_per_kg'], 0, ',', '.') ?>" />  
      <input type="hidden" id="harga_per_kg" name="harga_per_kg" value="<?= $transaksi['price_per_kg'] ?>"/>
    </div>  

    <div>  
      <label class="block text-sm font-medium">Total Harga</label>  
      <input type="text" id="total_harga_display" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" value="<?= number_format($transaksi['total_price'], 0, ',', '.') ?>" readonly />
      <input type="hidden" id="total_harga" name="total_harga" value="<?= $transaksi['total_price'] ?>"/>
    </div>
    
    <div>  
      <label class="block text-sm font-medium">Fee per Kg</label>  
      <input type="text" id="fee_per_kg_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($transaksi['fee_per_kg'], 0, ',', '.') ?>" />  
      <input type="hidden" id="fee_per_kg" name="fee_per_kg" value="<?= $transaksi['fee_per_kg'] ?>"/>
    </div>  

    <div>  
      <label class="block text-sm font-medium">Total Fee</label>  
      <input type="text" id="total_fee_display" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" value="<?= number_format($transaksi['total_fee'], 0, ',', '.') ?>" readonly />
      <input type="hidden" id="total_fee" name="total_fee" value="<?= $transaksi['total_fee'] ?>"/>
    </div>
    
    <div>  
      <label class="block text-sm font-medium">Grand Total</label>  
      <input type="text" id="grand_total_display" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" value="<?= number_format($transaksi['grand_total'], 0, ',', '.') ?>" readonly />
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
    const $supplierBaru = $('#supplierBaru');

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

    // Handle event change pada Select2
    $supplierSelect.on('change', function() {
      var lainnya = this.value === "lainnya";
      $supplierBaru.toggleClass('hidden', !lainnya);
    });

    // Inisialisasi status input nama baru jika pas load sudah terpilih 'lainnya'
    var lainnyaInit = $supplierSelect.val() === "lainnya";
    $supplierBaru.toggleClass('hidden', !lainnyaInit);
  });

  const formatter = new Intl.NumberFormat("id-ID");

  // Fungsi untuk membersihkan format ribuan (misalnya "1.234" => 1234)
  function parseRibuan(str) {
    return parseInt(str.replace(/\./g, "")) || 0;
  }

  function updateFormattedInput(display, hidden) {
    const value = parseRibuan(display.value);
    hidden.value = value;
    display.value = value ? formatter.format(value) : "";
  }

  // Ambil elemen-elemen DOM
  const beratDisplay = document.getElementById("berat_display");
  const berat = document.getElementById("berat");

  const hargaDisplay = document.getElementById("harga_per_kg_display");
  const harga = document.getElementById("harga_per_kg");

  const totalDisplay = document.getElementById("total_harga_display");
  const total = document.getElementById("total_harga");

  const feeDisplay = document.getElementById("fee_per_kg_display");
  const fee = document.getElementById("fee_per_kg");

  const totalFeeDisplay = document.getElementById("total_fee_display");
  const totalFee = document.getElementById("total_fee");

  const grandTotalDisplay = document.getElementById("grand_total_display");
  const grandTotal = document.getElementById("grand_total");

  const jumlahKarungDisplay = document.getElementById("jumlah_karung_display");
  const jumlahKarung = document.getElementById("jumlah_karung");

  function updateTotalHarga() {
    const beratVal = parseRibuan(beratDisplay.value);
    const hargaVal = parseRibuan(hargaDisplay.value);
    const totalVal = beratVal * hargaVal;

    total.value = totalVal;
    totalDisplay.value = formatter.format(totalVal);
    updateGrandTotal();
  }

  function updateTotalFee() {
    const beratVal = parseRibuan(beratDisplay.value);
    const feeVal = parseRibuan(feeDisplay.value);
    const totalVal = beratVal * feeVal;

    totalFee.value = totalVal;
    totalFeeDisplay.value = formatter.format(totalVal);
    updateGrandTotal();
  }

  function updateGrandTotal() {
    const totalHargaVal = parseRibuan(totalDisplay.value);
    const totalFeeVal = parseRibuan(totalFeeDisplay.value);
    const grandTotalVal = totalHargaVal + totalFeeVal;

    grandTotal.value = grandTotalVal;
    grandTotalDisplay.value = formatter.format(grandTotalVal);
  }

  // Event listener untuk input real-time
  jumlahKarungDisplay.addEventListener("input", function () {
    updateFormattedInput(jumlahKarungDisplay, jumlahKarung);
  });

  beratDisplay.addEventListener("input", function () {
    updateFormattedInput(beratDisplay, berat);
    updateTotalHarga();
    updateTotalFee();
  });

  hargaDisplay.addEventListener("input", function () {
    updateFormattedInput(hargaDisplay, harga);
    updateTotalHarga();
  });

  feeDisplay.addEventListener("input", function () {
    updateFormattedInput(feeDisplay, fee);
    updateTotalFee();
  });

  // Inisialisasi saat pertama kali halaman dimuat
  updateTotalHarga();
  updateTotalFee();
  updateGrandTotal();
</script>
  
</body>  
</html>