<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$production_id = (int) $_GET["id"];
$name = htmlspecialchars($_GET["name"] ?? '');
$materials = $conn->query("SELECT id, name FROM materials ORDER BY name ASC");

$error = "";
$harga_options = [];
$available_stock = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["material_id"], $_POST["unit_price"], $_POST["quantity"])) {
  $material_id = (int) $_POST["material_id"];
  $quantity = floatval(str_replace('.', '', $_POST['quantity']));
  $unit_price  = floatval($_POST["unit_price"]);

  if ($material_id && $quantity > 0 && $unit_price >= 0) {
    // Cek stok bahan
    $checkStock = $conn->prepare("SELECT quantity FROM material_stocks WHERE material_id = ?");
    $checkStock->bind_param("i", $material_id);
    $checkStock->execute();
    $checkStock->bind_result($available_stock);
    $stock_found = $checkStock->fetch();
    $checkStock->close();

    if ($stock_found && $available_stock >= $quantity) {
      $total_price = $quantity * $unit_price;

      // Simpan ke production_materials (tanpa unit_id)
      $stmt = $conn->prepare("INSERT INTO production_materials (production_id, material_id, quantity_used, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("iiddd", $production_id, $material_id, $quantity, $unit_price, $total_price);
      $stmt->execute();
      $stmt->close();

      // Log stock movement (OUT)
      $note = "Produksi $name pada " . date('d-m-Y');
      $logStmt = $conn->prepare("INSERT INTO stock_movements (material_id, change_type, quantity, unit_price, amount, note, created_at) VALUES (?, 'OUT', ?, ?, ?, ?, NOW())");
      $logStmt->bind_param("iddds", $material_id, $quantity, $unit_price, $total_price, $note);
      $logStmt->execute();
      $logStmt->close();

      // Kurangi stok bahan
      $updateStock = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
      $updateStock->bind_param("di", $quantity, $material_id);
      $updateStock->execute();
      $updateStock->close();

      header("Location: produksi-proses.php?id=" . $production_id . "&name=" . urlencode($name));
      exit();
    } else {
      $error = "Stok bahan baku tidak mencukupi. Silakan tambahkan stok terlebih dahulu.";
    }
  }
}

// Ambil harga & stok jika ada material_id
if (isset($_POST["material_id"]) && $_POST["material_id"] !== '') {
  $material_id = (int) $_POST["material_id"];

  // Ambil harga
  $stmt = $conn->prepare("SELECT MAX(unit_price), MIN(unit_price), AVG(unit_price) FROM material_purchases WHERE material_id = ?");
  $stmt->bind_param("i", $material_id);
  $stmt->execute();
  $stmt->bind_result($max_price, $min_price, $avg_price);
  $stmt->fetch();
  $stmt->close();

  if ($max_price !== null) {
    $harga_options = [
      'Tertinggi' => $max_price,
      'Terendah'  => $min_price,
      'Rata-rata'=> round($avg_price),
    ];
  }

  // Ambil stok tersedia
  $stokStmt = $conn->prepare("SELECT quantity FROM material_stocks WHERE material_id = ?");
  $stokStmt->bind_param("i", $material_id);
  $stokStmt->execute();
  $stokStmt->bind_result($available_stock);
  $stokStmt->fetch();
  $stokStmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tambah Bahan Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="produksi-proses.php?id=<?= $production_id ?>&name=<?= $name ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Tambah Bahan</h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-xl mx-auto">
  <?php if (!empty($error)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 border border-red-300 rounded">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white shadow-md rounded-lg p-6 space-y-6">
    <div>
      <label for="material_id" class="block text-sm font-medium mb-1">Nama Bahan</label>
      <select name="material_id" id="material_id" required class="w-full px-3 py-2 border rounded-lg" onchange="this.form.submit()">
        <option value="">-- Pilih Bahan --</option>
        <?php while ($row = $materials->fetch_assoc()): ?>
          <option value="<?= $row['id'] ?>" <?= isset($_POST['material_id']) && $_POST['material_id'] == $row['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($row['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <?php if (!empty($harga_options)): ?>
    <div>
      <label for="unit_price" class="block text-sm font-medium mb-1">Harga/gram</label>
      <select name="unit_price" id="unit_price" required class="w-full px-3 py-2 border rounded-lg">
        <option value="">-- Pilih Harga --</option>
        <?php foreach ($harga_options as $label => $value): ?>
          <option value="<?= $value ?>" <?= isset($_POST['unit_price']) && $_POST['unit_price'] == $value ? 'selected' : '' ?>>
            Rp <?= number_format($value, 0, ',', '.') ?> (<?= $label ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <?php if (!is_null($available_stock)): ?>
      <div class="text-sm text-red-700 bg-red-50 border border-red-200 px-3 py-2 rounded">
        <span class="font-semibold">Stok tersedia:</span>
        <?= number_format($available_stock, 0, ',', '.') ?> gram
      </div>
    <?php endif; ?>

    <div>
      <label for="quantity" class="block text-sm font-medium mb-1">Jumlah Digunakan (gram)</label>
      <input type="text" name="quantity" id="quantity" inputmode="decimal" value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" required class="w-full px-3 py-2 border rounded-lg" />
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan</span>
      </button>
    </div>
  </form>
</main>

<script>    
const formatter = new Intl.NumberFormat("id-ID");

function formatRibuan(input) {    
  const v = input.value.replace(/\D/g, "");    
  input.value = formatter.format(v);    
}    

function unformat(v) {    
  return parseInt(v.replace(/\./g,"")) || 0;    
}    

const qtyInput = document.getElementById("quantity");

qtyInput.addEventListener("input", () => {    
  formatRibuan(qtyInput);
});
</script>

</body>
</html>