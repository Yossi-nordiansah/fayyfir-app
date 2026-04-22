<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$user_id = $_SESSION["user_id"];
$role_id = $_SESSION["role_id"] ?? null;
$region  = $_SESSION["region"] ?? null;

$container_id = isset($_GET["container_id"]) ? intval($_GET["container_id"]) : 0;
if ($container_id === 0) {
  echo "Kontainer tidak ditemukan.";
  exit();
}

/* =========================
   FILTER SUPPLIER BY REGION
========================= */
if ($region) {
  $stmt = $conn->prepare("
    SELECT id, name, region_name 
    FROM suppliers 
    WHERE region_name = ?
    ORDER BY name ASC
  ");
  $stmt->bind_param("s", $region);
  $stmt->execute();
  $supplier_result = $stmt->get_result();
} else {
  // fallback (kalau session region kosong)
  $supplier_result = $conn->query("
    SELECT id, name, region_name 
    FROM suppliers 
    ORDER BY name ASC
  ");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $date = $_POST["tanggal"];
  $driver_name = $_POST["nama_driver"];
  $driver_phone = $_POST["no_telp_driver"];
  $vehicle_plate = $_POST["plat_nomor"];
  $sack_count = (int) str_replace('.', '', $_POST["jumlah_karung"]);
  $weight = (float) str_replace('.', '', $_POST["berat"]);
  $notes = $_POST["catatan"];
  $created_by = $user_id;
  $supplier_id = intval($_POST["supplier"]);

  $stmt = $conn->prepare("
    INSERT INTO transactions (
      container_id, transaction_date, driver_name, driver_phone, vehicle_plate,
      sack_count, weight_kg, notes, supplier_id, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param(
    "issssissii",
    $container_id,
    $date,
    $driver_name,
    $driver_phone,
    $vehicle_plate,
    $sack_count,
    $weight,
    $notes,
    $supplier_id,
    $created_by
  );

  if ($stmt->execute()) {
    if ($role_id == 1) {
      $update_fillby = $conn->prepare("UPDATE containers SET filled_by = ? WHERE id = ?");
      $update_fillby->bind_param("ii", $user_id, $container_id);
      $update_fillby->execute();
    }
    header("Location: riwayat-kontainer.php?id=" . $container_id);
    exit();
  } else {
    echo "Gagal menyimpan transaksi.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Transaksi - Fayyfir</title>
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
    <h1 class="text-lg font-semibold">Tambah Transaksi</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
<form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">

  <div>
    <label class="block text-sm font-medium">Tanggal</label>
    <input type="date" name="tanggal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= date("Y-m-d") ?>" />
  </div>

  <!-- SUPPLIER PICKER -->
  <div>
    <label class="block text-sm font-medium">Nama Petani / Supplier</label>

    <button type="button" id="openSupplierModal"
      class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md bg-white text-left">
      <span id="supplierLabel" class="text-gray-400">Pilih Nama Petani</span>
    </button>

    <input type="hidden" name="supplier" id="supplier_id" required />
  </div>

  <div>
    <label class="block text-sm font-medium">Nama Driver</label>
    <input type="text" name="nama_driver" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
  </div>

  <div>
    <label class="block text-sm font-medium">No. Telp. Driver</label>
    <input type="text" name="no_telp_driver" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
  </div>

  <div>
    <label class="block text-sm font-medium">Plat Nomor</label>
    <input type="text" name="plat_nomor" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
  </div>

  <div>
    <label class="block text-sm font-medium">Jumlah Karung</label>
    <input type="text" id="jumlah_karung_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
    <input type="hidden" id="jumlah_karung" name="jumlah_karung" />
  </div>

  <div>
    <label class="block text-sm font-medium">Total Berat (kg)</label>
    <input type="text" id="berat_display" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
    <input type="hidden" id="berat" name="berat" />
  </div>

  <div>
    <label class="block text-sm font-medium">Keterangan</label>
    <textarea name="catatan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
  </div>

  <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white py-2 rounded-md">
    Simpan Transaksi
  </button>

</form>
</main>

<!-- MODAL PILIH SUPPLIER -->
<div id="supplierModal" class="fixed inset-0 bg-white z-50 hidden flex flex-col">
  <div class="p-4 border-b flex justify-between items-center">
    <h2 class="text-lg font-semibold">Pilih Petani</h2>
    <button id="closeSupplierModal" class="material-icons">close</button>
  </div>

  <div class="p-4">
    <input type="text" id="supplierSearch" placeholder="Cari nama petani..."
      class="w-full px-3 py-2 border border-gray-300 rounded-md" />
  </div>
  
  <div id="addSupplierBox" class="hidden px-4 pb-2">
    <button
      type="button"
      id="addSupplierBtn"
      class="w-full text-left text-sm text-blue-600 py-2 border rounded-md">
      + Tambah supplier
    </button>
  </div>

  <div class="flex-1 overflow-y-auto px-4">
    <?php
    $supplier_result->data_seek(0);
    while ($row = $supplier_result->fetch_assoc()):
    ?>
    <button type="button"
      class="supplier-item w-full text-left py-3 border-b"
      data-id="<?= $row["id"] ?>"
      data-name="<?= htmlspecialchars($row["name"]) ?>">
      <?= htmlspecialchars($row["name"]) ?>
    </button>
    <?php endwhile; ?>
  </div>
</div>

<script>
const formatter = new Intl.NumberFormat("id-ID");

function cleanNumber(str) {
  return parseInt(str.replace(/\D/g, ""), 10) || 0;
}
function updateFormattedInput(display, hidden) {
  const value = cleanNumber(display.value);
  hidden.value = value;
  display.value = value ? formatter.format(value) : "";
}

document.getElementById("jumlah_karung_display").addEventListener("input", function () {
  updateFormattedInput(this, document.getElementById("jumlah_karung"));
});
document.getElementById("berat_display").addEventListener("input", function () {
  updateFormattedInput(this, document.getElementById("berat"));
});

const supplierModal = document.getElementById("supplierModal");

document.getElementById("openSupplierModal").onclick = () => {
  supplierModal.classList.remove("hidden");
};
document.getElementById("closeSupplierModal").onclick = () => {
  supplierModal.classList.add("hidden");
};

document.querySelectorAll(".supplier-item").forEach(item => {
  item.onclick = () => {
    document.getElementById("supplier_id").value = item.dataset.id;
    document.getElementById("supplierLabel").innerText = item.dataset.name;
    document.getElementById("supplierLabel").classList.remove("text-gray-400");
    supplierModal.classList.add("hidden");
  };
});

const supplierSearch = document.getElementById("supplierSearch");
const addBox = document.getElementById("addSupplierBox");
const addBtn = document.getElementById("addSupplierBtn");

supplierSearch.addEventListener("input", function () {
  const keyword = this.value.toLowerCase().trim();
  let found = false;

  document.querySelectorAll(".supplier-item").forEach(item => {
    const match = item.dataset.name.toLowerCase().includes(keyword);
    item.style.display = match ? "block" : "none";
    if (match) found = true;
  });

  if (!found && keyword.length >= 2) {
    addBox.classList.remove("hidden");
    addBtn.innerText = `+ Tambah supplier: "${this.value}"`;
  } else {
    addBox.classList.add("hidden");
  }
});

addBtn.onclick = async () => {
  const name = supplierSearch.value.trim();
  if (!name) return;

  addBtn.innerText = "Menyimpan...";
  addBtn.disabled = true;

  const res = await fetch("ajax-add-supplier.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "name=" + encodeURIComponent(name)
  });

  const data = await res.json();

  document.getElementById("supplier_id").value = data.id;
  document.getElementById("supplierLabel").innerText = data.name;
  document.getElementById("supplierLabel").classList.remove("text-gray-400");

  supplierModal.classList.add("hidden");
  addBtn.disabled = false;
};
</script>

</body>
</html>