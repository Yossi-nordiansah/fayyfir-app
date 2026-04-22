<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$container_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($container_id <= 0) {
  echo "Kontainer tidak ditemukan.";
  exit();
}

// Ambil data kontainer
$kontainer_stmt = $conn->prepare("SELECT * FROM containers WHERE id = ?");
$kontainer_stmt->bind_param("i", $container_id);
$kontainer_stmt->execute();
$kontainer_result = $kontainer_stmt->get_result();
$kontainer_data = $kontainer_result->fetch_assoc();

if (!$kontainer_data) {
  echo "Data kontainer tidak ditemukan.";
  exit();
}

// Ambil data produk untuk selection
$product_query = "SELECT * FROM products";
$product_result = $conn->query($product_query);

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $container_number = $_POST["kontainer"];
  $fill_date = $_POST["tanggal"];
  $expedition = $_POST["ekspedisi"];
  $shipping_line = $_POST["pelayaran"];
  $description = $_POST["keterangan"];
  $product_id = $_POST["produk"];
  $selling_price = str_replace('.', '', $_POST["harga_jual"]);

  $stmt = $conn->prepare(
    "UPDATE containers SET container_number=?, fill_date=?, expedition=?, shipping_line=?, description=?, product_id=?, selling_price=? WHERE id=?"
  );
  $stmt->bind_param(
    "sssssiis",
    $container_number,
    $fill_date,
    $expedition,
    $shipping_line,
    $description,
    $product_id,
    $selling_price,
    $container_id
  );

  if ($stmt->execute()) {
    header("Location: riwayat-kontainer.php?id=$container_id");
    exit();
  } else {
    echo "Gagal memperbarui data kontainer.";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Kontainer - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Riwayat</span>
      </a>
      <h1 class="text-lg font-semibold">Edit Kontainer</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
      <div>
        <label class="block text-sm font-medium">Nomor Kontainer</label>
        <input type="text" name="kontainer" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($kontainer_data["container_number"]) ?>" />
      </div>

      <div>
        <label class="block text-sm font-medium">Tanggal Release</label>
        <input type="date" name="tanggal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= $kontainer_data["fill_date"] ?>" />
      </div>

      <!-- Jenis Produk + Tombol -->
      <div>
        <label class="block text-sm font-medium mb-1">Jenis Produk</label>
        <div class="flex gap-2">
          <select name="produk" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <option value="">Pilih Jenis Produk</option>
            <?php while ($product = $product_result->fetch_assoc()): ?>
              <option value="<?= $product["id"] ?>" <?= $product["id"] == $kontainer_data["product_id"] ? "selected" : "" ?>>
                <?= htmlspecialchars($product["name"]) ?>
              </option>
            <?php endwhile; ?>
          </select>
          <a href="tambah-jenis-produk" class="bg-gray-800 hover:bg-yellow-400 text-white rounded-md px-3 flex items-center justify-center transition">
            <span class="material-symbols-outlined text-yellow-400 group-hover:text-gray-800 transition">add</span>
          </a>
        </div>
      </div>

      <!-- Harga Jual Produk -->
      <div hidden="">
        <label class="block text-sm font-medium">Harga Jual Produk</label>
        <input type="text" name="harga_jual" id="harga_jual" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= number_format($kontainer_data["selling_price"], 0, ',', '.') ?>" />
      </div>

      <div>
        <label class="block text-sm font-medium">Ekspedisi</label>
        <input type="text" name="ekspedisi" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($kontainer_data["expedition"]) ?>" />
      </div>

      <div>
        <label class="block text-sm font-medium">Pelayaran</label>
        <input type="text" name="pelayaran" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($kontainer_data["shipping_line"]) ?>" />
      </div>

      <div>
        <label class="block text-sm font-medium">Keterangan</label>
        <textarea name="keterangan" rows="2" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($kontainer_data["description"]) ?></textarea>
      </div>

      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md transition flex items-center justify-center space-x-2">
        <span class="material-symbols-outlined">check_circle</span>
        <span>Perbarui Kontainer</span>
      </button>
    </form>
  </main>

  <!-- Bottom Padding for Mobile -->
  <div class="lg:hidden h-24"></div>

  <script>
    // Format angka ribuan di input harga
    document.getElementById("harga_jual").addEventListener("input", function(e) {
      let value = e.target.value.replace(/\./g, "").replace(/\D/g, "");
      if (value !== "") {
        e.target.value = parseInt(value).toLocaleString("id-ID");
      } else {
        e.target.value = "";
      }
    });
  </script>
</body>
</html>