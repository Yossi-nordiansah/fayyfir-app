<?php    
session_start();    
require "config.php";    
date_default_timezone_set('Asia/Jakarta');    

if (!isset($_SESSION["user_id"])) {    
    header("Location: login");    
    exit();    
}    

$id = intval($_GET['id']);    

// Ambil material    
$stmt = $conn->prepare("SELECT name FROM materials WHERE id = ?");    
$stmt->bind_param("i",$id);    
$stmt->execute();    
$res = $stmt->get_result();    
$material = $res->fetch_assoc();    
$name = $material['name'] ?? '-';    

// supplier    
$suppliers = $conn->query("SELECT DISTINCT supplier_name FROM material_purchases WHERE supplier_name IS NOT NULL");    

$error = "";    

if ($_SERVER['REQUEST_METHOD']=='POST') {    
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
    $quantity = floatval(str_replace('.', '', $_POST['quantity']));  // gram    
    $unit_id = 1; // fix pakai gram    

    $unit_price = intval(str_replace('.', '', $_POST['harga']));    
    $total = intval(str_replace('.', '', $_POST['total']));    
    $catatan = trim($_POST['catatan']);    

    // ===== Eksekusi Insert kalau tidak ada error =====    
    if ($error === "") {    
        $stmt2 = $conn->prepare("  
            INSERT INTO material_purchases   
            (material_id, unit_id, quantity, unit_price, total_price, purchase_date, supplier_name, note)   
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)  
        ");  
        $stmt2->bind_param(  
            "iididsss",   
            $id,   
            $unit_id,   
            $quantity,   
            $unit_price,  
            $total,   
            $tanggal,   
            $supplier,   
            $catatan  
        );  

        if ($stmt2->execute()) {    
            // Catat pergerakan stok    
            $conn->query("INSERT INTO stock_movements (material_id, change_type, quantity, unit_price, amount, note) VALUES ($id, 'IN', $quantity, $unit_price, $total, 'Tambah stok $name')");    

            $updatedAt = date('Y-m-d H:i:s');    

            // Periksa apakah material sudah ada di material_stocks    
            $checkStock = $conn->prepare("SELECT id FROM material_stocks WHERE material_id = ?");    
            $checkStock->bind_param("i", $id);    
            $checkStock->execute();    
            $checkStockRes = $checkStock->get_result();    

            if ($checkStockRes->num_rows > 0) {    
                // Update stok    
                $stmtUpdate = $conn->prepare("UPDATE material_stocks SET quantity = quantity + ?, updated_at = ? WHERE material_id = ?");    
                $stmtUpdate->bind_param("dsi", $quantity, $updatedAt, $id);    
                $stmtUpdate->execute();    
            } else {    
                // Insert stok baru    
                $minimum_quantity = 0;    
                $stmtInsert = $conn->prepare("INSERT INTO material_stocks (material_id, quantity, minimum_quantity, updated_at) VALUES (?, ?, ?, ?)");    
                $stmtInsert->bind_param("idis", $id, $quantity, $minimum_quantity, $updatedAt);    
                $stmtInsert->execute();    
            }    

            header("Location: bahan-baku-rincian?id=$id");    
            exit();    
        } else {    
            $error = "Gagal menyimpan data: " . $stmt2->error;    
        }    
    }    
}    
?>    

<!DOCTYPE html>    
<html lang="id">    
<head>    
  <meta charset="UTF-8" />    
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />    
  <title>Tambah Stock</title>    
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
    <span class="text-lg font-semibold text-sm">Tambah Stock <?= htmlspecialchars($material['name'] ?? '-') ?></span>    
  </div>    
</header>    

<main class="pt-24 px-6 pb-32 max-w-xl mx-auto">    
      
  <!-- Peringatan -->    
  <?php if(!empty($error)): ?>    
    <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>    
  <?php endif; ?>    
      
  <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">    
    <div>    
      <label class="block text-sm font-medium">Tanggal</label>    
      <input type="date" name="tanggal" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" value="<?= date("Y-m-d") ?>" />    
    </div>    
      
    <div>    
      <label class="block text-sm font-medium">Supplier/ Merk</label>    
      <select name="supplier" id="supplierSelect" class="mt-1 w-full border px-3 py-2 rounded">    
        <option value="">-- Pilih Salah Satu --</option>    
        <?php while($r=$suppliers->fetch_assoc()): ?>    
          <option value="<?= htmlspecialchars($r['supplier_name']) ?>"><?= htmlspecialchars($r['supplier_name']) ?></option>    
        <?php endwhile; ?>    
        <option value="lainnya">Tambah baru...</option>    
      </select>    
      <input type="text" name="supplier_lainnya" id="supplierOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Masukkan supplier baru…" />    
    </div>    
      
    <!-- Bagian input Quantity (Gram) -->    
    <div>  
      <label class="block text-sm font-medium">Quantity (gram)</label>  
      <input type="text" id="quantity" name="quantity" inputmode="decimal" class="mt-1 border px-3 py-2 rounded w-full" placeholder="Jumlah gram…" required />  
    </div>    
    
    <div>    
      <label class="block text-sm font-medium">Harga/gram</label>    
      <input type="text" id="harga" name="harga" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Harga..." oninput="formatRibuan(this)" />    
    </div>    
    
    <div>    
      <label class="block text-sm font-medium">Total</label>    
      <input type="text" id="total" name="total" class="mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" placeholder="Total..." readonly />    
    </div>    
    
    <div>    
      <label class="block text-sm font-medium">Catatan</label>    
      <Textarea name="catatan" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Catatan..."></Textarea>    
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