<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$pm_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$production_id = isset($_GET['production_id']) ? (int) $_GET['production_id'] : 0;
$name = htmlspecialchars($_GET["name"] ?? '');

$error = "";
$available_stock = null;
$stock_unit_symbol = null;
$existing_quantity = 0;
$unit_price = 0;
$material_name = "";
$material_id = 0;
$current_unit_id = null;

// Ambil data dari production_materials
$stmt = $conn->prepare("
  SELECT pm.material_id, pm.quantity_used, pm.unit_price, m.name, pm.unit_id
  FROM production_materials pm
  JOIN materials m ON pm.material_id = m.id
  WHERE pm.id = ?
");
$stmt->bind_param("i", $pm_id);
$stmt->execute();
$stmt->bind_result($material_id, $existing_quantity, $unit_price, $material_name, $current_unit_id);

if (!$stmt->fetch()) {
  $stmt->close();
  die("Data bahan tidak ditemukan.");
}
$stmt->close();

// Ambil stok tersedia + satuan
$stokStmt = $conn->prepare("
  SELECT ms.quantity, u.symbol
  FROM material_stocks ms
  LEFT JOIN units u ON ms.unit_id = u.id
  WHERE ms.material_id = ?
");
$stokStmt->bind_param("i", $material_id);
$stokStmt->execute();
$stokStmt->bind_result($available_stock, $stock_unit_symbol);
$stokStmt->fetch();
$stokStmt->close();

// Ambil semua satuan
$unit_options = $conn->query("SELECT id, name, symbol FROM units ORDER BY name ASC");

// Ambil harga tertinggi, terendah, rata-rata dari material_purchases
$stmt = $conn->prepare("SELECT MAX(unit_price), MIN(unit_price), AVG(unit_price) FROM material_purchases WHERE material_id=?");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$stmt->bind_result($max_price, $min_price, $avg_price);
$stmt->fetch();
$stmt->close();

$avg_price = round($avg_price, 2);

// Tentukan selected
$selected_max = ($unit_price == $max_price) ? 'selected' : '';
$selected_min = ($unit_price == $min_price) ? 'selected' : '';
$selected_avg = ($unit_price == $avg_price) ? 'selected' : '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quantity"], $_POST["unit_id"], $_POST["unit_price"])) {
  $new_quantity = floatval($_POST["quantity"]);
  $new_unit_id = (int) $_POST["unit_id"];
  $new_unit_price = floatval($_POST["unit_price"]);
  $stok_asli = $available_stock + $existing_quantity;

  if ($new_quantity > 0 && $new_quantity <= $stok_asli) {
    $total_price = $new_quantity * $new_unit_price;

    // Jika satuan berubah → update semua tabel terkait
    if ($new_unit_id != $current_unit_id) {
      $upMat = $conn->prepare("UPDATE materials SET unit_id = ? WHERE id = ?");
      $upMat->bind_param("ii", $new_unit_id, $material_id);
      $upMat->execute();
      $upMat->close();

      $upPur = $conn->prepare("UPDATE material_purchases SET unit_id = ? WHERE material_id = ?");
      $upPur->bind_param("ii", $new_unit_id, $material_id);
      $upPur->execute();
      $upPur->close();
    }

    // Update production_materials
    $stmt = $conn->prepare("UPDATE production_materials SET quantity_used = ?, unit_price = ?, total_price = ?, unit_id = ? WHERE id = ?");
    $stmt->bind_param("dddii", $new_quantity, $new_unit_price, $total_price, $new_unit_id, $pm_id);
    $stmt->execute();
    $stmt->close();

    // Update stok
    $difference = $new_quantity - $existing_quantity;
    $updateStock = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
    $updateStock->bind_param("di", $difference, $material_id);
    $updateStock->execute();
    $updateStock->close();

    // Log perubahan stok
    $note = "Edit bahan produksi $name pada " . date('d-m-Y');
    $change_type = $difference > 0 ? 'OUT' : 'IN';
    $qty_logged = abs($difference);
    $amount = $qty_logged * $new_unit_price;
    $logStmt = $conn->prepare("INSERT INTO stock_movements (material_id, change_type, quantity, unit_price, amount, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $logStmt->bind_param("issdds", $material_id, $change_type, $qty_logged, $new_unit_price, $amount, $note);
    $logStmt->execute();
    $logStmt->close();

    header("Location: produksi-proses.php?id=$production_id&name=" . urlencode($name));
    exit();
  } else {
    $error = "Jumlah digunakan tidak valid atau melebihi stok.";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Bahan Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="produksi-proses.php?id=<?= $production_id ?>&name=<?= urlencode($name) ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <h1 class="text-lg font-semibold">Edit Bahan</h1>
  </div>
</header>

<main class="pt-24 px-4 pb-32 max-w-xl mx-auto">
  <?php if ($error): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 border border-red-300">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white shadow-md rounded-lg p-6 space-y-6">
    <div>
      <label class="block text-sm font-medium mb-1">Nama Bahan</label>
      <p class="px-3 py-2 bg-gray-100 border rounded"><?= htmlspecialchars($material_name) ?></p>
    </div>

    <div>
      <label for="unit_price" class="block text-sm font-medium mb-1">Harga Satuan (Rp)</label>
      <select name="unit_price" id="unit_price" required class="w-full px-3 py-2 border rounded-lg">
        <option value="<?= $max_price ?>" <?= $selected_max ?>>Harga Tertinggi (Rp <?= number_format($max_price, 0, ',', '.') ?>)</option>
        <option value="<?= $min_price ?>" <?= $selected_min ?>>Harga Terendah (Rp <?= number_format($min_price, 0, ',', '.') ?>)</option>
        <option value="<?= $avg_price ?>" <?= $selected_avg ?>>Harga Rata-rata (Rp <?= number_format($avg_price, 0, ',', '.') ?>)</option>
      </select>
    </div>

    <div>
      <label for="unit_id" class="block text-sm font-medium mb-1">Satuan Unit</label>
      <select name="unit_id" id="unit_id" required class="w-full px-3 py-2 border rounded-lg">
        <option value="">-- Pilih Satuan --</option>
        <?php while ($u = $unit_options->fetch_assoc()): ?>
          <option value="<?= $u['id'] ?>" <?= $current_unit_id == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['symbol']) ?> (<?= htmlspecialchars($u['name']) ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <?php if (!is_null($available_stock)): ?>
      <div class="text-sm text-red-700 bg-red-50 border border-red-200 px-3 py-2 rounded">
        <span class="font-semibold">Stok tersedia:</span>
        <?= number_format($available_stock, 2, ',', '.') ?>
        <?= $stock_unit_symbol ? htmlspecialchars($stock_unit_symbol) : '' ?>
      </div>
    <?php endif; ?>

    <div>
      <label for="quantity" class="block text-sm font-medium mb-1">Jumlah Digunakan <?= $stock_unit_symbol ? htmlspecialchars($stock_unit_symbol) : '' ?></label>
      <input type="number" step="0.01" name="quantity" id="quantity" value="<?= htmlspecialchars($existing_quantity) ?>" required class="w-full px-3 py-2 border rounded-lg" />
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Simpan Perubahan</span>
      </button>
    </div>
  </form>
</main>

</body>
</html>