<?php
session_start();
require "config.php";
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

$purchase_id = intval($_GET['id']); // id pembelian
$material_id = intval($_GET['supplier_id']); // id material

// Ambil data pembelian
$stmt = $conn->prepare("SELECT * FROM material_purchases WHERE id = ?");
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$res = $stmt->get_result();
$purchase = $res->fetch_assoc();

if (!$purchase) {
    die("Data tidak ditemukan.");
}

// Ambil nama material
$stmt2 = $conn->prepare("SELECT name FROM materials WHERE id = ?");
$stmt2->bind_param("i", $material_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$material = $res2->fetch_assoc();
$name = $material['name'] ?? '-';

// Ambil daftar supplier
$suppliers = $conn->query("SELECT DISTINCT supplier_name FROM material_purchases WHERE supplier_name IS NOT NULL");

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];

    // ===== Validasi Supplier =====
    $supplier = $_POST['supplier'];
    $supplier_lain = trim($_POST['supplier_lainnya'] ?? '');

    if ($supplier === 'lainnya') {
        if ($supplier_lain === '') {
            $error = "Supplier baru harus diisi jika memilih 'Tambah baru...'";
        } else {
            $supplier = $supplier_lain;
        }
    }

    if (empty($supplier) && $error === '') {
        $error = "Supplier harus dipilih atau diisi.";
    }

    // ===== Ambil Quantity & Harga =====
    $quantity   = floatval(str_replace('.', '', $_POST['quantity']));
    $unit_price = intval(str_replace('.', '', $_POST['harga']));
    $total      = intval(str_replace('.', '', $_POST['total']));
    $catatan    = trim($_POST['catatan']);
    $unit_id    = 1;

    if ($error === "") {
        // Hitung selisih quantity lama & baru untuk update stok
        $old_quantity  = $purchase['quantity'];
        $diff_quantity = $quantity - $old_quantity;

        // Update material_purchases
        $stmtUpdate = $conn->prepare("
            UPDATE material_purchases 
            SET unit_id=?, quantity=?, unit_price=?, total_price=?, purchase_date=?, supplier_name=?, note=?
            WHERE id=?");
        $stmtUpdate->bind_param(
            "ididsssi",
            $unit_id,
            $quantity,
            $unit_price,
            $total,
            $tanggal,
            $supplier,
            $catatan,
            $purchase_id
        );

        if ($stmtUpdate->execute()) {
            // INSERT baru ke stock_movements (sebagai log)
            $stmtLog = $conn->prepare("
                INSERT INTO stock_movements 
                (material_id, change_type, quantity, unit_price, amount, note, created_at) 
                VALUES (?, 'IN', ?, ?, ?, ?, NOW())
            ");
            $logNote = "Edit stok $name";
            $stmtLog->bind_param("idids", $material_id, $quantity, $unit_price, $total, $logNote);
            $stmtLog->execute();

            // Update stok di material_stocks (dengan selisih)
            $updatedAt = date('Y-m-d H:i:s');
            $stmtStock = $conn->prepare("
                UPDATE material_stocks 
                SET quantity = quantity + ?, updated_at = ? 
                WHERE material_id = ?
            ");
            $stmtStock->bind_param("dsi", $diff_quantity, $updatedAt, $material_id);
            $stmtStock->execute();

            header("Location: bahan-baku-rincian?id=$material_id");
            exit();
        } else {
            $error = "Gagal update data: " . $stmtUpdate->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Stock</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="javascript:history.back()" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <span class="text-lg font-semibold text-sm">Edit Stock <?= htmlspecialchars($name) ?></span>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">

  <?php if(!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Tanggal</label>
      <input type="date" name="tanggal" value="<?= htmlspecialchars($purchase['purchase_date']) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" />
    </div>

    <div>
      <label class="block text-sm font-medium">Supplier/ Merk</label>
      <select name="supplier" id="supplierSelect" class="mt-1 w-full border px-3 py-2 rounded">
        <option value="">-- Pilih Salah Satu --</option>
        <?php while($r=$suppliers->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($r['supplier_name']) ?>" <?= $purchase['supplier_name']===$r['supplier_name']?'selected':'' ?>><?= htmlspecialchars($r['supplier_name']) ?></option>
        <?php endwhile; ?>
        <option value="lainnya">Tambah baru...</option>
      </select>
      <input type="text" name="supplier_lainnya" id="supplierOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Masukkan supplier baru…" />
    </div>

    <div>
      <label class="block text-sm font-medium">Quantity (gram)</label>
      <input type="text" id="quantity" name="quantity" value="<?= number_format($purchase['quantity'], 0, ',', '.') ?>" class="mt-1 border px-3 py-2 rounded w-full" required />
    </div>

    <div>
      <label class="block text-sm font-medium">Harga/gram</label>
      <input type="text" id="harga" name="harga" value="<?= number_format($purchase['unit_price'], 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRibuan(this)" />
    </div>

    <div>
      <label class="block text-sm font-medium">Total</label>
      <input type="text" id="total" name="total" value="<?= number_format($purchase['total_price'], 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly />
    </div>

    <div>
      <label class="block text-sm font-medium">Catatan</label>
      <Textarea name="catatan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($purchase['note']) ?></Textarea>
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Update</span>
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

const qtyInput = document.getElementById("quantity"),
      hargaInput = document.getElementById("harga"),
      totalInput = document.getElementById("total");

function updateTotal() {
  const qty   = unformat(qtyInput.value);
  const harga = unformat(hargaInput.value);
  const total = qty * harga;
  totalInput.value = formatter.format(total);
}

qtyInput.addEventListener("input", () => {
  formatRibuan(qtyInput);
  updateTotal();
});

hargaInput.addEventListener("input", () => {
  formatRibuan(hargaInput);
  updateTotal();
});

document.getElementById("supplierSelect").addEventListener("change", e => {
  document.getElementById("supplierOther").classList.toggle("hidden", e.target.value !== "lainnya");
});
</script>

</body>
</html>