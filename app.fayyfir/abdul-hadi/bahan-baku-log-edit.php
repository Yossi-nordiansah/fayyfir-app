<?php
session_start();
require "config.php";
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$material_id = isset($_GET["material_id"]) ? (int)$_GET["material_id"] : 0;

if (!$id || !$material_id) {
    die("Parameter tidak valid.");
}

// Ambil data log bahan
$stmt = $conn->prepare("SELECT * FROM stock_movements WHERE id = ? AND material_id = ?");
$stmt->bind_param("ii", $id, $material_id);
$stmt->execute();
$res = $stmt->get_result();
$log = $res->fetch_assoc();

if (!$log) {
    die("Data tidak ditemukan.");
}

// Ambil nama material
$stmt2 = $conn->prepare("SELECT name FROM materials WHERE id = ?");
$stmt2->bind_param("i", $material_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$material = $res2->fetch_assoc();
$name = $material['name'] ?? '-';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    
    $change_type = $_POST['change_type'];
    $quantity = floatval(str_replace('.', '', $_POST['quantity']));
    $unit_price = intval(str_replace('.', '', $_POST['unit_price']));
    $amount = intval(str_replace('.', '', $_POST['amount']));
    $note = trim($_POST['note']);

    if ($quantity < 0) {
        $error = "Quantity tidak boleh negatif.";
    }

    if ($error === "") {
        $conn->begin_transaction();

        try {
            // 1. Reverse old stock impact
            $old_change_type = strtolower($log['change_type']);
            $old_qty = (float)$log['quantity'];

            if ($old_change_type === 'in') {
                $upd1 = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
                $upd1->bind_param("di", $old_qty, $material_id);
                $upd1->execute();
            } elseif ($old_change_type === 'out') {
                $upd1 = $conn->prepare("UPDATE material_stocks SET quantity = quantity + ? WHERE material_id = ?");
                $upd1->bind_param("di", $old_qty, $material_id);
                $upd1->execute();
            }

            // 2. Update the log entry
            $stmtUpdate = $conn->prepare("
                UPDATE stock_movements 
                SET change_type=?, quantity=?, unit_price=?, amount=?, note=?, created_at=?
                WHERE id=? AND material_id=?
            ");
            $tanggal_w_time = date('Y-m-d H:i:s', strtotime($tanggal));
            
            $stmtUpdate->bind_param(
                "sdidssii",
                $change_type,
                $quantity,
                $unit_price,
                $amount,
                $note,
                $tanggal_w_time,
                $id,
                $material_id
            );
            $stmtUpdate->execute();

            // 3. Apply new stock impact
            $new_change_type = strtolower($change_type);
            
            if ($new_change_type === 'in') {
                $upd2 = $conn->prepare("UPDATE material_stocks SET quantity = quantity + ? WHERE material_id = ?");
                $upd2->bind_param("di", $quantity, $material_id);
                $upd2->execute();
            } elseif ($new_change_type === 'out') {
                $upd2 = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
                $upd2->bind_param("di", $quantity, $material_id);
                $upd2->execute();
            }

            $conn->commit();
            header("Location: bahan-baku-rincian?id=$material_id");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal update data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Log Bahan</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">
    <a href="bahan-baku-rincian?id=<?= $material_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali</span>
    </a>
    <span class="text-lg font-semibold text-sm">Edit Log <?= htmlspecialchars($name) ?></span>
  </div>
</header>

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">

  <?php if(!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
    <div>
      <label class="block text-sm font-medium">Tanggal</label>
      <input type="datetime-local" name="tanggal" value="<?= date('Y-m-d\TH:i', strtotime($log['created_at'])) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" required />
    </div>

    <div>
      <label class="block text-sm font-medium">Tipe Log</label>
      <select name="change_type" class="mt-1 w-full border px-3 py-2 rounded">
        <option value="in" <?= strtolower($log['change_type']) === 'in' ? 'selected' : '' ?>>IN (Masuk)</option>
        <option value="out" <?= strtolower($log['change_type']) === 'out' ? 'selected' : '' ?>>OUT (Keluar)</option>
        <option value="EDIT" <?= strtoupper($log['change_type']) === 'EDIT' ? 'selected' : '' ?>>EDIT</option>
        <option value="<?= htmlspecialchars($log['change_type']) ?>" <?= !in_array(strtolower($log['change_type']), ['in', 'out', 'edit']) ? 'selected' : 'hidden' ?>><?= htmlspecialchars($log['change_type']) ?></option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Quantity (gram)</label>
      <input type="text" id="quantity" name="quantity" value="<?= number_format($log['quantity'], 0, ',', '.') ?>" class="mt-1 border px-3 py-2 rounded w-full" required />
    </div>

    <div>
      <label class="block text-sm font-medium">Harga/gram</label>
      <input type="text" id="unit_price" name="unit_price" value="<?= number_format($log['unit_price'], 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" oninput="formatRibuan(this)" />
    </div>

    <div>
      <label class="block text-sm font-medium">Jumlah Total</label>
      <input type="text" id="amount" name="amount" value="<?= number_format($log['amount'], 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly />
    </div>

    <div>
      <label class="block text-sm font-medium">Keterangan</label>
      <textarea name="note" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($log['note']) ?></textarea>
    </div>

    <div>
      <button type="submit" class="flex justify-center w-full group items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">save</span>
        <span class="ml-2 group-hover:text-gray-800">Update Log</span>
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
      hargaInput = document.getElementById("unit_price"),
      amountInput = document.getElementById("amount");

function updateTotal() {
  const qty   = unformat(qtyInput.value);
  const harga = unformat(hargaInput.value);
  const total = qty * harga;
  amountInput.value = formatter.format(total);
}

qtyInput.addEventListener("input", () => {
  formatRibuan(qtyInput);
  updateTotal();
});

hargaInput.addEventListener("input", () => {
  formatRibuan(hargaInput);
  updateTotal();
});
</script>

</body>
</html>
