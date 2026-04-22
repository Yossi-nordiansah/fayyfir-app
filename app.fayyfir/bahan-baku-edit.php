<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = intval($_GET['id'] ?? 0);

// Ambil data lama
$stmt = $conn->prepare("
  SELECT m.name, m.category, m.description, m.unit_id, s.minimum_quantity 
  FROM materials m 
  LEFT JOIN material_stocks s ON m.id = s.material_id 
  WHERE m.id = ?
");
if(!$stmt){
  die("Query Error: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nama_bahan_lama, $kategori_lama, $deskripsi_lama, $unit_id_lama, $min_stock_lama);
$stmt->fetch();
$stmt->close();

// Format awal untuk tampilan (gunakan titik sebagai pemisah ribuan)
$min_stock_display = $min_stock_lama !== null ? number_format($min_stock_lama, 0, ',', '.') : '';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nama_bahan = trim($_POST["nama_bahan"]);
  $deskripsi_bahan = trim($_POST["deskripsi_bahan"]);
  $min_stock = str_replace(",", ".", $_POST["minimum_quantity"]);
  $min_stock = floatval($min_stock);
  
  // Proses kategori
  $kategori = $_POST["kategori"] ?? "";
  if ($kategori === "lainnya") {
    $kategori = trim($_POST["kategori_lainnya"]);
    if ($kategori === "") {
      $errors[] = "Kategori baru harus diisi.";
    }
  }

  // Kalau tidak ada error, simpan
  if (empty($errors)) {
    $update_stmt = $conn->prepare("UPDATE materials SET name = ?, category = ?, description = ?, created_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("sssi", $nama_bahan, $kategori, $deskripsi_bahan, $id);

    if ($update_stmt->execute()) {
      $update_stock_stmt = $conn->prepare("UPDATE material_stocks SET minimum_quantity = ? WHERE material_id = ?");
      $update_stock_stmt->bind_param("di", $min_stock, $id); // <- gunakan "d" untuk float
      $update_stock_stmt->execute();

      header("Location: bahan-baku-rincian?id=" . $id);
      exit();
    } else {
      $errors[] = "Gagal mengupdate data: " . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Bahan Baku</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="bahan-baku-rincian?id=<?= $id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Edit <?= htmlspecialchars($nama_bahan_lama) ?></h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
      <ul class="list-disc ml-5">
        <?php foreach($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Nama</label>
      <input type="text" name="nama_bahan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($_POST['nama_bahan'] ?? $nama_bahan_lama) ?>" required />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Kategori</label>
      <select name="kategori" id="categorySelect" class="mt-1 w-full border px-3 py-2 rounded">
        <option value="">-- Pilih Kategori --</option>
        <?php
        $categories_result = $conn->query("SELECT DISTINCT category FROM materials WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        if ($categories_result) {
          while($row = $categories_result->fetch_assoc()):
            $selected = ($row["category"] == ($kategori_lama ?? "")) ? "selected" : "";
        ?>
            <option value="<?= htmlspecialchars($row["category"]) ?>" <?= $selected ?>>
              <?= htmlspecialchars($row["category"]) ?>
            </option>
        <?php
          endwhile;
        }
        ?>
        <option value="lainnya">Tambah baru...</option>
      </select>
      <input type="text" name="kategori_lainnya" class="kategori-other hidden mt-2 w-full border px-3 py-2 rounded" placeholder="Nama kategori baru" />
    </div>

    <div>
      <label class="block text-sm font-medium">Min Stok (Gram)</label>
      <input type="text" id="minStockInput" name="minimum_quantity" inputmode="decimal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($min_stock_display) ?>" />
    </div>

    <div>
      <label class="block text-sm font-medium">Deskripsi</label>
      <textarea name="deskripsi_bahan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($_POST['deskripsi_bahan'] ?? $deskripsi_lama) ?></textarea>
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan Perubahan</span>
      </button>
    </div>
  </form>
</main>

<script>
  function toggleOtherInputs(selectId, className) {
    const select = document.getElementById(selectId);
    const otherInputs = document.querySelectorAll(className);
    select.addEventListener("change", () => {
      if (select.value === "lainnya") {
        otherInputs.forEach(input => input.classList.remove("hidden"));
      } else {
        otherInputs.forEach(input => input.classList.add("hidden"));
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    toggleOtherInputs("categorySelect", ".kategori-other");
  });
  
// format ribuan saat user mengetik
function formatRibuan(angka) {
  return angka.replace(/\D/g, "") // hapus non digit
              .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// unformat ke angka murni
function unformatRibuan(angka) {
  return angka.replace(/\./g, "");
}

const minStockInput = document.getElementById("minStockInput");

// ketika user mengetik
minStockInput.addEventListener("input", function(e) {
  const cursorPos = this.selectionStart;
  const nilaiAwal = this.value;
  this.value = formatRibuan(this.value);
  // atur ulang posisi kursor supaya tidak lompat ke akhir
  const selisih = this.value.length - nilaiAwal.length;
  this.setSelectionRange(cursorPos + selisih, cursorPos + selisih);
});

// sebelum form submit → ubah ke angka murni
document.querySelector("form").addEventListener("submit", function() {
  minStockInput.value = unformatRibuan(minStockInput.value);
});
</script>
</body>
</html>