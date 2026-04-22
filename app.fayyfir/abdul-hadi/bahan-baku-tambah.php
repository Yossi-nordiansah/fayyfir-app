<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil kategori yang sudah ada
$material_result = $conn->query("SELECT DISTINCT category FROM materials WHERE category IS NOT NULL");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nama_bahan = trim($_POST["nama_bahan"]);
  $category = trim($_POST["kategori"]);
  $deskripsi_bahan = trim($_POST["deskripsi_bahan"]);
  $min_stock = isset($_POST["minimum_quantity"]) ? floatval($_POST["minimum_quantity"]) : 0;

  // unit_id selalu 1 (gram)
  $unit_id = 1;

  // Insert ke tabel materials
  $stmt = $conn->prepare("
    INSERT INTO materials (
      name, category, description, unit_id, created_at
    ) VALUES (?, ?, ?, ?, NOW())
  ");
  $stmt->bind_param("sssi", $nama_bahan, $category, $deskripsi_bahan, $unit_id);

  if ($stmt->execute()) {
    $material_id = $stmt->insert_id;
    $stmt->close();

    // Insert ke tabel material_stocks
    $insert_stock_stmt = $conn->prepare("
      INSERT INTO material_stocks (material_id, quantity, minimum_quantity, unit_id)
      VALUES (?, 0, ?, ?)
    ");
    $insert_stock_stmt->bind_param("idi", $material_id, $min_stock, $unit_id);
    
    if ($insert_stock_stmt->execute()) {
      $insert_stock_stmt->close();
      header("Location: bahan-baku");
      exit();
    } else {
      echo "Gagal menyimpan stok: " . $insert_stock_stmt->error;
    }
  } else {
    echo "Gagal menyimpan data: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Bahan Baku</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="bahan-baku" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Bahan Baku</h1>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Nama</label>
      <input type="text" name="nama_bahan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Nama Bahan Baku..." />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Kategori</label>
      <select name="kategori" id="categorySelect" class="mt-1 w-full border px-3 py-2 rounded">
        <option value="">-- Kategori Bahan --</option>
        <?php while($r = $material_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($r['category']) ?>"><?= htmlspecialchars($r['category']) ?></option>
        <?php endwhile; ?>
        <option value="lainnya2">Tambah baru...</option>
      </select>
      <input type="text" name="kategori_lainnya" id="categoryOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Kategori baru…" />
    </div>
    
    <div>
      <label class="block text-sm font-medium">Min Stok (Gram)</label>
      <input type="text" id="minStockInput" name="minimum_quantity" inputmode="decimal"
             class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" 
             placeholder="Contoh: 1.000">
    </div>
    
    <div>
      <label class="block text-sm font-medium">Deskripsi</label>
      <textarea name="deskripsi_bahan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Deskripsi Bahan Baku..."></textarea>
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan</span>
      </button>
    </div>
  </form>
</main>

<script>
document.getElementById("categorySelect").addEventListener("change", e => {
  document.getElementById("categoryOther").classList.toggle("hidden", e.target.value !== "lainnya2");
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