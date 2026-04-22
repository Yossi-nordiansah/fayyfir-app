<?php
session_start();
require "config.php";

// Redirect jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$user_result = $conn->query("SELECT DISTINCT region_name FROM users WHERE region_name IS NOT NULL");

$success = "";
$error = "";

// Proses form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"]);
  $phone = trim($_POST["phone"]);
  $region_name = trim($_POST["area"]);
  $address = trim($_POST["address"]);
  $notes = trim($_POST["notes"]);
  $province_id = $_POST["province"];
  $regency_id = $_POST["regency"];
  $district_id = $_POST["district"];
  $village_id = $_POST["village"];

  if ($name) {
    $stmt = $conn->prepare(
      "INSERT INTO suppliers (name, phone, region_name, address, notes, province_id, regency_id, district_id, village_id, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->bind_param(
      "sssssssss",
      $name,
      $phone,
      $region_name,
      $address,
      $notes,
      $province_id,
      $regency_id,
      $district_id,
      $village_id
    );

    if ($stmt->execute()) {
      header("Location: daftar-supplier");
      exit();
    } else {
      $error = "Gagal menyimpan data. Silakan coba lagi.";
    }
    $stmt->close();
  } else {
    $error = "Field 'Nama' wajib diisi.";
  }
}

// Ambil data wilayah untuk dropdown
$provinces = $conn->query("SELECT id, name FROM reg_provinces ORDER BY name");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Supplier - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="javascript:history.back()" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Tambah Supplier</h1>
    </div>
  </header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" class="bg-white shadow p-6 rounded-lg space-y-4">
    
    <div>
      <label class="block text-sm font-medium">Nama</label>
      <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>

    <div>
      <label class="block text-sm font-medium">Nomor HP</label>
      <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
    </div>

    <!-- AREA -->
    <div>
      <label class="block text-sm font-medium">Area</label>
      <select name="area" class="mt-1 w-full border px-3 py-2 rounded">
        <option value="">-- Pilih Area --</option>
        <?php while($r = $user_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($r['region_name']) ?>">
            <?= htmlspecialchars($r['region_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    
    <!-- Wilayah -->
    <div>
      <label class="block text-sm font-medium">Alamat</label>
      <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium">Provinsi</label>
      <select name="province" id="province" class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <option value="">-- Pilih Provinsi --</option>
        <?php while ($p = $provinces->fetch_assoc()): ?>
          <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["name"]) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Kabupaten/Kota</label>
      <select name="regency" id="regency" class="w-full px-3 py-2 border border-gray-300 rounded-md"></select>
    </div>

    <div>
      <label class="block text-sm font-medium">Kecamatan</label>
      <select name="district" id="district" class="w-full px-3 py-2 border border-gray-300 rounded-md"></select>
    </div>

    <div>
      <label class="block text-sm font-medium">Desa</label>
      <select name="village" id="village" class="w-full px-3 py-2 border border-gray-300 rounded-md"></select>
    </div>

    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
    </div>

    <button type="submit" class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full">
        <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">Save</span>
        <span>Simpan</span>
    </button>

  </form>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $("#province").on("change", function () {
    var provinceId = $(this).val();
    $.get("ajax/get_regencies.php?province_id=" + provinceId, function (data) {
      $("#regency").html(data);
      $("#district").html("<option value=''>-- Pilih Kecamatan --</option>");
      $("#village").html("<option value=''>-- Pilih Desa --</option>");
    });
  });

  $("#regency").on("change", function () {
    var regencyId = $(this).val();
    $.get("ajax/get_districts.php?regency_id=" + regencyId, function (data) {
      $("#district").html(data);
      $("#village").html("<option value=''>-- Pilih Desa --</option>");
    });
  });

  $("#district").on("change", function () {
    var districtId = $(this).val();
    $.get("ajax/get_villages.php?district_id=" + districtId, function (data) {
      $("#village").html(data);
    });
  });
</script>

</body>
</html>