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
  
// Ambil stok dari stock_movements + ambil satuan dari materials
$stokStmt = $conn->prepare("
  SELECT 
    COALESCE(SUM(
      CASE 
        WHEN sm.change_type = 'IN' THEN sm.quantity
        WHEN sm.change_type = 'OUT' THEN -sm.quantity
        ELSE 0
      END
    ), 0) as stock,
    u.symbol
  FROM stock_movements sm
  LEFT JOIN materials m ON sm.material_id = m.id
  LEFT JOIN units u ON m.unit_id = u.id
  WHERE sm.material_id = ?
");
$stokStmt->bind_param("i", $material_id);  
$stokStmt->execute();  
$stokStmt->bind_result($available_stock, $stock_unit_symbol);  
$stokStmt->fetch();  
$stokStmt->close();  
  
// Ambil semua satuan  
$unit_options = $conn->query("SELECT id, name, symbol FROM units ORDER BY name ASC");  
  
/* ==========================  
   AMBIL HARGA DINAMIS  
========================== */  
  
$harga_options = [];  
  
$stmt = $conn->prepare("  
  SELECT unit_price, MAX(created_at) as last_used  
  FROM material_purchases  
  WHERE material_id = ?  
  GROUP BY unit_price  
  ORDER BY last_used DESC  
  LIMIT 3  
");  
$stmt->bind_param("i", $material_id);  
$stmt->execute();  
$result = $stmt->get_result();  
  
while ($row = $result->fetch_assoc()) {  
  $harga_options[] = $row['unit_price'];  
}  
$stmt->close();  
  
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quantity"], $_POST["unit_id"], $_POST["unit_price"])) {  
  $new_quantity = floatval($_POST["quantity"]);  
  $new_unit_id = (int) $_POST["unit_id"];  
  $new_unit_price = floatval($_POST["unit_price"]);  
  $stok_asli = $available_stock + $existing_quantity;  
  
  if ($new_quantity > 0 && $new_quantity <= $stok_asli) {  
    $total_price = $new_quantity * $new_unit_price;  
  
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
  
    $stmt = $conn->prepare("UPDATE production_materials SET quantity_used = ?, unit_price = ?, total_price = ?, unit_id = ? WHERE id = ?");  
    $stmt->bind_param("dddii", $new_quantity, $new_unit_price, $total_price, $new_unit_id, $pm_id);  
    $stmt->execute();  
    $stmt->close();  
  
    $difference = $new_quantity - $existing_quantity;  
    $updateStock = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");  
    $updateStock->bind_param("di", $difference, $material_id);  
    $updateStock->execute();  
    $updateStock->close();  
  
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
  <label class="block text-sm font-medium mb-1">Harga Satuan (Rp)</label>  
  <select name="unit_price" required class="w-full px-3 py-2 border rounded-lg">  
    <option value="">-- Pilih Harga --</option>  
    <?php foreach ($harga_options as $index => $value): ?>  
      <?php $label = ($index == 0) ? "Terbaru" : "Sebelumnya"; ?>  
      <option value="<?= $value ?>" <?= ($unit_price == $value) ? 'selected' : '' ?>>  
        Rp <?= number_format($value, 0, ',', '.') ?> (<?= $label ?>)  
      </option>  
    <?php endforeach; ?>  
  </select>  
</div>  
  
<?php if (!is_null($available_stock)): ?>  
  <div class="text-sm text-red-700 bg-red-50 border border-red-200 px-3 py-2 rounded">  
    <span class="font-semibold">Stok tersedia:</span>  
    <span id="stockValue"><?= number_format($available_stock, 2, ',', '.') ?></span>  
    <?= $stock_unit_symbol ? htmlspecialchars($stock_unit_symbol) : '' ?>  
  </div>  
<?php endif; ?>  
  
<div>  
  <label class="block text-sm font-medium mb-1">Jumlah Digunakan <?= $stock_unit_symbol ? htmlspecialchars($stock_unit_symbol) : '' ?></label>  
  <input type="number" step="0.01" name="quantity" id="quantity" value="<?= htmlspecialchars($existing_quantity) ?>" required class="w-full px-3 py-2 border rounded-lg" />  
</div>  
  
<button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">  
  <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>  
  <span class="ml-2 group-hover:text-gray-800">Simpan Perubahan</span>  
</button>  
  
</form>  
</main>  

<script>
const formatter = new Intl.NumberFormat("id-ID");

const qtyInput = document.getElementById("quantity");
const stockText = document.getElementById("stockValue");
const submitBtn = document.querySelector("button[type='submit']");

let realStock = <?= (float)$available_stock + (float)$existing_quantity ?>;

// Elemen warning
let warningEl = document.createElement("div");
warningEl.className = "text-sm mt-2 text-red-600 hidden";
warningEl.innerText = "Jumlah melebihi stok tersedia!";
qtyInput.parentNode.appendChild(warningEl);

// Format ribuan (opsional biar konsisten UX)
function formatRibuan(input) {
  const v = input.value.replace(/\D/g, "");
  input.value = formatter.format(v);
}

// Ambil angka asli
function unformat(v) {
  return parseInt(v.replace(/\./g,"")) || 0;
}

qtyInput.addEventListener("input", () => {

  // format input
  formatRibuan(qtyInput);

  const qty = unformat(qtyInput.value);
  let sisa = realStock - qty;

  // kondisi invalid (0 atau kosong)
  if (qty <= 0) {
    submitBtn.disabled = true;
    warningEl.classList.add("hidden");

    stockText.innerText = formatter.format(realStock);
    stockText.classList.remove("text-red-600");
    return;
  }

  // melebihi stok
  if (qty > realStock) {
    submitBtn.disabled = true;
    warningEl.classList.remove("hidden");

    stockText.innerText = "0";
    stockText.classList.add("text-red-600");
  } else {
    submitBtn.disabled = false;
    warningEl.classList.add("hidden");

    stockText.innerText = formatter.format(sisa);
    stockText.classList.remove("text-red-600");
  }

});
</script>

</body>  
</html>