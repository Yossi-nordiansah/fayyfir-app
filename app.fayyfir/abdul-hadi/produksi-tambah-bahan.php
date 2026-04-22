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

/* ==========================
   HANDLE SUBMIT
========================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["material_id"], $_POST["unit_price"], $_POST["quantity"])) {

  $material_id = (int) $_POST["material_id"];
  $quantity = floatval(str_replace('.', '', $_POST['quantity']));
  $unit_price  = floatval($_POST["unit_price"]);

  if ($material_id && $quantity > 0 && $unit_price >= 0) {

    $conn->begin_transaction();

    try {

      /* ==========================
         CEK STOK DARI LOG
      ========================== */

      $checkStock = $conn->prepare("
        SELECT COALESCE(SUM(
          CASE 
            WHEN change_type = 'IN' THEN quantity
            WHEN change_type = 'OUT' THEN -quantity
            ELSE 0
          END
        ), 0)
        FROM stock_movements
        WHERE material_id = ?
        FOR UPDATE
      ");

      $checkStock->bind_param("i", $material_id);
      $checkStock->execute();
      $checkStock->bind_result($available_stock);
      $checkStock->fetch();
      $checkStock->close();

      if ($available_stock < $quantity) {
        throw new Exception("Stok bahan baku tidak mencukupi.");
      }

      $total_price = $quantity * $unit_price;

      /* ==========================
         INSERT PRODUKSI
      ========================== */

      $stmt = $conn->prepare("
        INSERT INTO production_materials 
        (production_id, material_id, quantity_used, unit_price, total_price) 
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("iiddd", $production_id, $material_id, $quantity, $unit_price, $total_price);
      $stmt->execute();
      $stmt->close();

      /* ==========================
         LOG STOCK (OUT)
      ========================== */

      $note = "Produksi $name pada " . date('d-m-Y');

      $logStmt = $conn->prepare("
        INSERT INTO stock_movements 
        (material_id, change_type, quantity, unit_price, amount, note, created_at) 
        VALUES (?, 'OUT', ?, ?, ?, ?, NOW())
      ");
      $logStmt->bind_param("iddds", $material_id, $quantity, $unit_price, $total_price, $note);
      $logStmt->execute();
      $logStmt->close();

      $conn->commit();

      header("Location: produksi-proses.php?id=" . $production_id . "&name=" . urlencode($name));
      exit();

    } catch (Exception $e) {

      $conn->rollback();
      $error = $e->getMessage();
    }
  }
}

/* ==========================
   AMBIL HARGA & STOK
========================== */

if (isset($_POST["material_id"]) && $_POST["material_id"] !== '') {

  $material_id = (int) $_POST["material_id"];

  // Ambil 3 harga terakhir berbeda
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

  // Ambil stok dari stock_movements
  $stokStmt = $conn->prepare("
    SELECT COALESCE(SUM(
      CASE 
        WHEN change_type = 'IN' THEN quantity
        WHEN change_type = 'OUT' THEN -quantity
        ELSE 0
      END
    ), 0)
    FROM stock_movements 
    WHERE material_id = ?
  ");
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
      <label class="block text-sm font-medium mb-1">Nama Bahan</label>
      <select name="material_id" required class="w-full px-3 py-2 border rounded-lg" onchange="this.form.submit()">
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
      <label class="block text-sm font-medium mb-1">Harga/gram</label>
      <select name="unit_price" required class="w-full px-3 py-2 border rounded-lg">
        <option value="">-- Pilih Harga --</option>
        <?php foreach ($harga_options as $index => $value): ?>
          <?php $label = ($index == 0) ? "Terbaru" : "Sebelumnya"; ?>
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
        <span id="stockValue"><?= number_format($available_stock, 0, ',', '.') ?></span> gram
      </div>
    <?php endif; ?>

    <div>
      <label class="block text-sm font-medium mb-1">Jumlah Digunakan (gram)</label>
      <input type="text" name="quantity" id="quantity" inputmode="decimal"
        value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>"
        required class="w-full px-3 py-2 border rounded-lg" />
    </div>

    <button type="submit" class="w-full bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
      Simpan
    </button>

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
const stockText = document.getElementById("stockValue");
const submitBtn = document.querySelector("button[type='submit']");

let originalStock = <?= (int)$available_stock ?>;

let warningEl = document.createElement("div");
warningEl.className = "text-sm mt-2 text-red-600 hidden";
warningEl.innerText = "Jumlah melebihi stok tersedia!";
qtyInput.parentNode.appendChild(warningEl);

qtyInput.addEventListener("input", () => {

  formatRibuan(qtyInput);

  const qty = unformat(qtyInput.value);
  let sisa = originalStock - qty;

  if (qty <= 0) {
    submitBtn.disabled = true;
    warningEl.classList.add("hidden");

    stockText.innerText = formatter.format(originalStock);
    stockText.classList.remove("text-red-600");
    return;
  }

  if (qty > originalStock) {
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