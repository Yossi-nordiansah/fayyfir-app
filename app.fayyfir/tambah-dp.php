<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = (int) $_GET["supplier_id"] ?? 0;

$stmt = $conn->prepare("SELECT name FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();

if (!$supplier) {
  echo "Supplier tidak ditemukan.";
  exit();
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $date = $_POST["deposit_date"];
  $debit = str_replace(".", "", $_POST["debit"] ?? "");
  $description = $_POST["description"];

  if ($date && $debit > 0) {
    $stmt = $conn->prepare(
      "INSERT INTO deposits_supplier (supplier_id, deposit_date, description, debit, credit) VALUES (?, ?, ?, ?, 0)"
    );
    $stmt->bind_param("issi", $id, $date, $description, $debit);
    if ($stmt->execute()) {
      header("Location: rincian-dp-supplier.php?id=" . $id);
      exit();
    } else {
      $error = "Gagal menyimpan data.";
    }
    $stmt->close();
  } else {
    $error = "Tanggal dan jumlah DP wajib diisi dan valid.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah DP - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="rincian-dp-supplier.php?id=<?= $id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah DP Supplier</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-6 bg-white shadow rounded-lg p-6">
    <div>
      <label class="block text-sm font-medium">Nama Supplier</label>
      <input type="text" value="<?= htmlspecialchars(
        $supplier["name"]
      ) ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed">
    </div>
    <div>
      <label class="block text-sm font-medium">Tanggal</label>
      <input type="datetime-local" name="deposit_date" 
       value="<?= date('Y-m-d\TH:i') ?>" 
       class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>
    <div>
      <label class="block text-sm font-medium">Jumlah DP (Rp)</label>
      <input type="text" name="debit" min="1000" step="1000" class="w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRupiah(this)" />
    </div>
    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <select name="description" id="descriptionSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" onchange="toggleCustomDesc()">
        <option value="Tambah DP">Tambah DP</option>
        <option value="Pembelian">Pembelian</option>
        <option value="manual">Input manual...</option>
      </select>
    </div>
    
    <div id="customDescriptionWrapper" class="mt-2 hidden">
      <input type="text" name="custom_description" id="customDescriptionInput" placeholder="Tulis keterangan manual..." class="w-full px-3 py-2 border border-gray-300 rounded-md" />
    </div>

    <button type="submit" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full">
      <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">save</span>
      <span>Simpan</span>
    </button>
  </form>
</main>

  <script>
    function formatRupiah(input) {
      let value = input.value.replace(/\D/g, "");
      input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
  </script>

<script>
  function toggleCustomDesc() {
    const select = document.getElementById("descriptionSelect");
    const customInput = document.getElementById("customDescriptionWrapper");
    if (select.value === "manual") {
      customInput.classList.remove("hidden");
    } else {
      customInput.classList.add("hidden");
    }
  }

  // Pastikan juga value input manual ditimpa ke name=description sebelum submit
  document.querySelector("form").addEventListener("submit", function (e) {
    const select = document.getElementById("descriptionSelect");
    const input = document.getElementById("customDescriptionInput");
    if (select.value === "manual" && input.value.trim() !== "") {
      // buat hidden input untuk override
      const hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "description";
      hidden.value = input.value;
      this.appendChild(hidden);
    }
  });
</script>
</body>
</html>