<?php  
session_start();  
require "config.php";  
  
if (!isset($_SESSION["user_id"])) {  
  header("Location: login");  
  exit();  
}  
  
if (!isset($_GET["id"])) {  
  header("Location: daftar-supplier");  
  exit();  
}  
  
$id = (int) $_GET["id"];  
$success = "";  
$error = "";  
  
// Ambil data supplier  
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");  
$stmt->bind_param("i", $id);  
$stmt->execute();  
$result = $stmt->get_result();  
if ($result->num_rows === 0) {  
  header("Location: daftar-supplier");  
  exit();  
}  
$supplier = $result->fetch_assoc();  
$stmt->close();  
  
// Proses update  
if ($_SERVER["REQUEST_METHOD"] === "POST") {  
  $name = trim($_POST["name"]);  
  $phone = trim($_POST["phone"]);  
  $address = trim($_POST["address"]);  
  $notes = trim($_POST["notes"]);  
  $province_id = $_POST["province"];  
  $regency_id = $_POST["regency"];  
  $district_id = $_POST["district"];  
  $village_id = $_POST["village"];  
  
  if ($name) {  
    $stmt = $conn->prepare(  
      "UPDATE suppliers SET name = ?, phone = ?, address = ?, notes = ?, province_id = ?, regency_id = ?, district_id = ?, village_id = ?, updated_at = NOW() WHERE id = ?"  
    );  
    $stmt->bind_param(  
      "ssssssssi",  
      $name,  
      $phone,  
      $address,  
      $notes,  
      $province_id,  
      $regency_id,  
      $district_id,  
      $village_id,  
      $id  
    );  
  
    if ($stmt->execute()) {  
      header("Location: daftar-supplier");  
      exit();  
    } else {  
      $error = "Gagal memperbarui data.";  
    }  
    $stmt->close();  
  } else {  
    $error = "Semua field wajib diisi.";  
  }  
}  
  
$provinces = $conn->query("SELECT id, name FROM reg_provinces ORDER BY name");  
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="UTF-8">  
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  
  <title>Edit Supplier - Fayyfir</title>  
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">  
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">  
</head>  
<body class="bg-gray-100 text-gray-800 min-h-screen">  
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">  
    <div class="flex justify-between items-center">  
      <a href="daftar-supplier" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">  
        <span class="material-symbols-outlined text-base">chevron_left</span>  
        <span class="hidden lg:inline">Kembali</span>  
      </a>  
      <h1 class="text-lg font-semibold">Edit Supplier</h1>  
    </div>  
  </header>  
  
<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">  
  <?php if ($success): ?>  
    <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $success ?></div>  
  <?php elseif ($error): ?>  
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>  
  <?php endif; ?>  
  
  <form method="POST" class="bg-white shadow p-6 rounded-lg space-y-4">  
    <div>  
      <label class="block text-sm font-medium">Nama</label>  
      <input type="text" name="name" value="<?= htmlspecialchars(  
        $supplier["name"]  
      ) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Nomor HP</label>  
      <input type="tel" name="phone" value="<?= htmlspecialchars(  
        $supplier["phone"]  
      ) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Alamat</label>  
      <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars(  
        $supplier["address"]  
      ) ?></textarea>  
    </div>  
  
    <div>  
      <label class="block text-sm font-medium">Provinsi</label>  
      <select name="province" id="province" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
        <option value="">-- Pilih Provinsi --</option>  
        <?php while ($p = $provinces->fetch_assoc()): ?>  
          <option value="<?= $p["id"] ?>" <?= $p["id"] ==  
$supplier["province_id"]  
  ? "selected"  
  : "" ?>><?= htmlspecialchars($p["name"]) ?></option>  
        <?php endwhile; ?>  
      </select>  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Kabupaten/Kota</label>  
      <select name="regency" id="regency" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
        <option value="<?= $supplier[  
          "regency_id"  
        ] ?>">(Data akan dimuat via JS)</option>  
      </select>  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Kecamatan</label>  
      <select name="district" id="district" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
        <option value="<?= $supplier[  
          "district_id"  
        ] ?>">(Data akan dimuat via JS)</option>  
      </select>  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Desa</label>  
      <select name="village" id="village" class="w-full px-3 py-2 border border-gray-300 rounded-md">  
        <option value="<?= $supplier[  
          "village_id"  
        ] ?>">(Data akan dimuat via JS)</option>  
      </select>  
    </div>  
    <div>  
      <label class="block text-sm font-medium">Keterangan</label>  
      <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars(  
        $supplier["notes"]  
      ) ?></textarea>  
    </div>  
  
    <button type="submit" class="w-full bg-gray-800 hover:bg-yellow-400 text-white font-medium py-2 px-4 rounded-md flex justify-center items-center space-x-2">  
      <span class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition">save</span>  
      <span>Simpan Perubahan</span>  
    </button>  
  </form>  
</main>  
  
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>  
<script>  
  const regencySelected = <?= json_encode($supplier["regency_id"]) ?>;  
  const districtSelected = <?= json_encode($supplier["district_id"]) ?>;  
  const villageSelected = <?= json_encode($supplier["village_id"]) ?>;  
  
  function loadRegencies(provinceId) {  
    $.get("ajax/get_regencies.php?province_id=" + provinceId, function(data) {  
      $("#regency").html(data).val(regencySelected).trigger("change");  
    });  
  }  
  
  function loadDistricts(regencyId) {  
    $.get("ajax/get_districts.php?regency_id=" + regencyId, function(data) {  
      $("#district").html(data).val(districtSelected).trigger("change");  
    });  
  }  
  
  function loadVillages(districtId) {  
    $.get("ajax/get_villages.php?district_id=" + districtId, function(data) {  
      $("#village").html(data).val(villageSelected);  
    });  
  }  
  
  $("#province").on("change", function() {  
    loadRegencies(this.value);  
  });  
  
  $("#regency").on("change", function() {  
    loadDistricts(this.value);  
  });  
  
  $("#district").on("change", function() {  
    loadVillages(this.value);  
  });  
  
  $(document).ready(function () {  
    loadRegencies($("#province").val());  
  });  
</script>  
</body>  
</html>