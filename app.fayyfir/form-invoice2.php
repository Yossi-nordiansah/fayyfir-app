<?php
session_start();
require "config.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$container_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($container_id === 0) {
  echo "ID Kontainer tidak ditemukan.";
  exit();
}

$container = $conn->query("SELECT c.*, p.name AS product_name 
    FROM containers c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.id = $container_id")->fetch_assoc();
if (!$container) {
  echo "Data kontainer tidak valid.";
  exit();
}

// Ambil semua data unik
$material_result = $conn->query("
  SELECT DISTINCT invoice_from, invoice_to, address, account_name, account_number, bank_name
  FROM invoice_info
  WHERE invoice_from IS NOT NULL
     OR invoice_to IS NOT NULL
     OR address IS NOT NULL
     OR account_name IS NOT NULL
     OR account_number IS NOT NULL
     OR bank_name IS NOT NULL
");
$material_data = $material_result->fetch_all(MYSQLI_ASSOC);

// Fungsi helper: ambil daftar unik dari kolom tertentu
function get_unique_column($rows, $col) {
  $vals = [];
  foreach ($rows as $row) {
    if (!empty($row[$col])) $vals[] = $row[$col];
  }
  return array_unique($vals);
}
$invoice_from_list  = get_unique_column($material_data, 'invoice_from');
$invoice_to_list    = get_unique_column($material_data, 'invoice_to');
$address_list       = get_unique_column($material_data, 'address');
$bank_name_list     = get_unique_column($material_data, 'bank_name');

// Submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $invoice_from = $_POST["invoice_from"];
  if ($invoice_from === "IFLainnya" && !empty($_POST["if_lainnya"])) {
    $invoice_from = trim($_POST["if_lainnya"]);
  }

  $invoice_to = $_POST["invoice_to"];
  if ($invoice_to === "ITLainnya" && !empty($_POST["it_lainnya"])) {
    $invoice_to = trim($_POST["it_lainnya"]);
  }

  $address = $_POST["address"];
  if ($address === "ADLainnya" && !empty($_POST["ad_lainnya"])) {
    $address = trim($_POST["ad_lainnya"]);
  }

  $account_name = $_POST["account_name"];
  if ($account_name === "ANLainnya" && !empty($_POST["an_lainnya"])) {
    $account_name = trim($_POST["an_lainnya"]);
  }

  $containers     = $_POST["containers"];
  $container_no   = $_POST["container_no"];
  $invoice_no     = $_POST["invoice_no"];
  $invoice_date   = $_POST["invoice_date"];
  $account_number = $_POST["account_number"];
  $bank_name      = $_POST["bank_name"];
  $description    = $_POST["description"];
  if ($bank_name === "BNLainnya" && !empty($_POST["bn_lainnya"])) {
    $bank_name = trim($_POST["bn_lainnya"]);
  }
  $user_id        = $_SESSION["user_id"];

  // Cek duplicate
  $check = $conn->prepare("SELECT id FROM invoice_info WHERE container_id = ?");
  $check->bind_param("i", $container_id);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    $error = "❌ Invoice untuk kontainer ini sudah dibuat, tidak bisa input ganda.";
  } else {
    $stmt = $conn->prepare("
      INSERT INTO invoice_info 
      (container_id, invoice_from, invoice_to, address, containers, container_no, invoice_no, invoice_date, account_name, account_number, bank_name, description) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
      "isssssssssss",
      $container_id, $invoice_from, $invoice_to, $address,
      $containers, $container_no, $invoice_no, $invoice_date,
      $account_name, $account_number, $bank_name, $description
    );

    if ($stmt->execute()) {
      header("Location: invoice-pdf.php?invoice_id=" . $stmt->insert_id);
      exit();
    } else {
      $error = "❌ Gagal menyimpan data invoice.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice - Fayyfir</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Informasi Invoice</h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-2xl mx-auto">
  <div class="bg-white p-6 rounded shadow space-y-6">
    <?php if (isset($error)): ?>
      <div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Invoice Date</label>
        <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border rounded" />
      </div>
      
      <!-- Invoice From -->
      <div>
        <label class="block text-sm font-medium">Invoice From</label>
        <select name="invoice_from" id="IFSelect" class="mt-1 w-full border px-3 py-2 rounded toggle-input">
          <option value="">-- Invoice dari --</option>
          <?php foreach ($invoice_from_list as $val): ?>
            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
          <?php endforeach; ?>
          <option value="IFLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="if_lainnya" id="IFOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>

      <!-- Invoice To -->
      <div>
        <label class="block text-sm font-medium">Invoice To</label>
        <select name="invoice_to" id="ITSelect" class="mt-1 w-full border px-3 py-2 rounded toggle-input">
          <option value="">-- Invoice untuk --</option>
          <?php foreach ($invoice_to_list as $val): ?>
            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
          <?php endforeach; ?>
          <option value="ITLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="it_lainnya" id="ITOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>

      <!-- Address -->
      <div>
        <label class="block text-sm font-medium">Address</label>
        <select name="address" id="ADSelect" class="mt-1 w-full border px-3 py-2 rounded toggle-input">
          <option value="">-- Pilih alamat --</option>
          <?php foreach ($address_list as $val): ?>
            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
          <?php endforeach; ?>
          <option value="ADLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="ad_lainnya" id="ADOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>

      <!-- Sisa field -->
      <div>
        <label class="block text-sm font-medium">Containers</label>
        <input type="text" name="containers" class="w-full px-3 py-2 border rounded" placeholder="Kontainer..." value="<?= htmlspecialchars($container['shipping_line'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm font-medium">Container No.</label>
        <input type="text" name="container_no" class="w-full px-3 py-2 border rounded" placeholder="Nomor kontainer..." value="<?= htmlspecialchars($container['container_number'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm font-medium">Invoice No.</label>
        <input type="text" name="invoice_no" class="w-full px-3 py-2 border rounded" placeholder="Nomor Invoice..." value="<?= htmlspecialchars($container['number'] ?? '') ?>/AGI ..." />
      </div>

      <!-- Bank Name -->
      <div>
        <label class="block text-sm font-medium">Bank Name</label>
        <select name="bank_name" id="BNSelect" class="mt-1 w-full border px-3 py-2 rounded">
          <option value="">-- Pilih bank --</option>
          <?php foreach ($bank_name_list as $val): ?>
            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
          <?php endforeach; ?>
          <option value="BNLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="bn_lainnya" id="BNOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>

      <!-- Account Name -->
      <div>
        <label class="block text-sm font-medium">Account Name</label>
        <select name="account_name" id="ANSelect" class="mt-1 w-full border px-3 py-2 rounded">
          <option value="">-- Pilih nama rekening --</option>
          <option value="ANLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="an_lainnya" id="ANOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>
      
      <!-- Account Number -->
      <div>
        <label class="block text-sm font-medium">Account Number</label>
        <select name="account_number" id="ANumSelect" class="mt-1 w-full border px-3 py-2 rounded">
          <option value="">-- Pilih nomor rekening --</option>
          <option value="ANumLainnya">Tambah baru...</option>
        </select>
        <input type="text" name="anum_lainnya" id="ANumOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Tambah baru…" />
      </div>
      
      <div>
        <label class="block text-sm font-medium">Item Description</label>
        <textarea name="description" class="w-full px-3 py-2 border rounded" placeholder="Nomor Invoice..." />40 Ft (<?= htmlspecialchars($container['container_number'] ?? '') ?>) <?= htmlspecialchars($container['region_name'] ?? '') ?> <?= htmlspecialchars($container['product_name'] ?? '') ?> cnt<?= htmlspecialchars($container['number'] ?? '') ?></textarea>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded">
          Lanjut Cetak Invoice
        </button>
      </div>
    </form>
  </div>
</main>

<script>
const materialData = <?= json_encode($material_data) ?>;

// Helper isi select
function fillOptions(selectId, options, defaultText, lainnyaVal) {
  const select = document.getElementById(selectId);
  select.innerHTML = `<option value="">${defaultText}</option>`;
  [...new Set(options)].forEach(opt => {
    const o = document.createElement("option");
    o.value = opt;
    o.textContent = opt;
    select.appendChild(o);
  });
  if (lainnyaVal) {
    const o = document.createElement("option");
    o.value = lainnyaVal;
    o.textContent = "Tambah baru...";
    select.appendChild(o);
  }
}

// Saat pilih Bank → filter Account Name
document.getElementById("BNSelect").addEventListener("change", function() {
  const bank = this.value;
  if (bank && !bank.endsWith("Lainnya")) {
    const names = materialData
      .filter(d => d.bank_name === bank && d.account_name)
      .map(d => d.account_name);
    fillOptions("ANSelect", names, "-- Pilih nama rekening --", "ANLainnya");
    fillOptions("ANumSelect", [], "-- Pilih nomor rekening --", "ANumLainnya");
  } else {
    fillOptions("ANSelect", [], "-- Pilih nama rekening --", "ANLainnya");
    fillOptions("ANumSelect", [], "-- Pilih nomor rekening --", "ANumLainnya");
  }
});

// Saat pilih Account Name → filter Account Number
document.getElementById("ANSelect").addEventListener("change", function() {
  const accName = this.value;
  const bank = document.getElementById("BNSelect").value;
  if (accName && !accName.endsWith("Lainnya")) {
    const nums = materialData
      .filter(d => d.bank_name === bank && d.account_name === accName && d.account_number)
      .map(d => d.account_number);
    fillOptions("ANumSelect", nums, "-- Pilih nomor rekening --", "ANumLainnya");
  } else {
    fillOptions("ANumSelect", [], "-- Pilih nomor rekening --", "ANumLainnya");
  }
});

// Toggle input tambahan (Lainnya)
document.querySelectorAll("select").forEach(select => {
  select.addEventListener("change", e => {
    const id = e.target.id.replace("Select", "Other");
    if (document.getElementById(id)) {
      document.getElementById(id).classList.toggle("hidden", !e.target.value.endsWith("Lainnya"));
    }
  });
});
</script>
</body>
</html>