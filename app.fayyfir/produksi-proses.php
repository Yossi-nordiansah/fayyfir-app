<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

$id = (int) ($_GET["id"] ?? 0);
if (!$id) {
    header("Location: produksi");
    exit();
}

// === Ambil product_id & nama produk dari DB ===
$prod_stmt = $conn->prepare("
    SELECT p.product_id, ps.product_name 
    FROM productions p
    JOIN product_stocks ps ON p.product_id = ps.id
    WHERE p.id = ?
");
$prod_stmt->bind_param("i", $id);
$prod_stmt->execute();
$prod_result = $prod_stmt->get_result();
$prod_data = $prod_result->fetch_assoc();
$prod_stmt->close();

if (!$prod_data) {
    echo "<script>alert('Data produksi tidak ditemukan.'); location.href='produksi';</script>";
    exit();
}

$product_id = $prod_data['product_id'];
$name = $prod_data['product_name'];

// Ambil daftar unit
$units_result = $conn->query("SELECT id, symbol FROM units");

// === Ambil Data & Hitung Total Materials ===
$total_price_all = 0;
$total_weight = 0;
$materials_data = $conn->query("
    SELECT pm.id, pm.material_id, pm.quantity_used, pm.unit_price, pm.total_price, pm.created_at,
           m.name AS material_name
    FROM production_materials pm
    LEFT JOIN materials m ON pm.material_id = m.id
    WHERE pm.production_id = $id
    ORDER BY pm.created_at DESC
");

$materials_rows = [];
while ($row = $materials_data->fetch_assoc()) {
    $materials_rows[] = $row;
    $total_price_all += (float)$row['total_price'];
    $total_weight += (float)$row['quantity_used'];
}

// === Ambil Data & Hitung Total Expenses ===
$total_amount = 0;
$expenses_data = $conn->query("
    SELECT id, description, amount, created_at
    FROM production_expenses
    WHERE production_id = $id
    ORDER BY created_at DESC
");

$expenses_rows = [];
while ($row = $expenses_data->fetch_assoc()) {
    $expenses_rows[] = $row;
    $total_amount += (float)$row['amount'];
}

// === Proses Form Submit ===  
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_produksi"])) {  
    $jumlah = floatval(str_replace('.', '', $_POST['jumlah_produksi']));

    $status = 'Selesai';  
    $stmt = $conn->prepare("  
        UPDATE productions   
        SET total_output = ?,   
            total_weight = ?,  
            status = ?,   
            unit_id = 1,   
            total_pro_expenses = ?,   
            total_pro_materials = ?   
        WHERE id = ?  
    ");  
    $stmt->bind_param(  
        "ddsddi",  
        $jumlah,  
        $total_weight,  
        $status,  
        $total_amount,  
        $total_price_all,  
        $id  
    );  

    if ($stmt->execute()) {  
        // === Update stok produk ===  
        $stmtStock = $conn->prepare("UPDATE product_stocks SET quantity = quantity + ? WHERE id = ?");  
        $stmtStock->bind_param("di", $jumlah, $product_id);  
        $stmtStock->execute();  

        echo "<script>alert('Jumlah hasil produksi berhasil disimpan dan stok produk diperbarui.'); location.href='hasil-produksi';</script>";  
        exit();  
    } else {  
        echo "<script>alert('Gagal menyimpan jumlah produksi.');</script>";  
    }  
}

// Ambil daftar bahan baku
$materials = $conn->query("
  SELECT 
    pm.id AS id, 
    pm.*, 
    m.name AS material_name,
    u.symbol AS unit_symbol
  FROM production_materials pm
  LEFT JOIN materials m ON pm.material_id = m.id
  LEFT JOIN units u ON pm.unit_id = u.id
  WHERE pm.production_id = '$id'
  ORDER BY pm.created_at DESC
");

// Ambil daftar biaya tambahan
$expenses = $conn->query("
  SELECT * FROM production_expenses
  WHERE production_id = $id
  ORDER BY created_at DESC
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Proses Produksi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="produksi" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Produksi <?= $name ?></h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-8 max-w-6xl mx-auto space-y-6">
    
    <!-- Bahan Baku Digunakan -->
    <section>
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-md font-semibold">BAHAN PRODUKSI</h1>
        <a href="produksi-tambah-bahan?id=<?= $id ?>&name=<?= $name ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
          <span class="ml-2 group-hover:text-gray-800">Bahan</span>
        </a>
      </div>
      
      <div  class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400 text-sm">
            <tr>
              <th class="px-4 py-3 text-center whitespace-nowrap">Tanggal</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Nama Bahan</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Qty (gram)</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Harga/gram</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Total</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $total_quantity = 0;
              $total_price_all = 0;
              $units = '';
              while ($row = $materials->fetch_assoc()):
                $total_quantity += $row['quantity_used'];
                $total_price_all += $row['total_price'];
                $units = $row['unit_symbol'];
            ?>
              <tr class="border-t">
                <td class="px-4 py-2 text-center whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(
                  date("d/m/Y", strtotime($row['created_at']))
                ) ?></td>
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($row['material_name']) ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($row['quantity_used'], 0, ',', '.') ?>
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($row['unit_price'], 0, ',', '.') ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap">
                  <a href="produksi-edit-bahan.php?id=<?= $row['id'] ?>&production_id=<?= $id ?>&name=<?= urlencode($name) ?>" 
                     class="inline-block text-blue-500 hover:text-blue-700 mr-2 text-sm" 
                     title="Edit">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <a href="produksi-hapus-bahan.php?id=<?= $row['id'] ?>&production_id=<?= $id ?>&name=<?= urlencode($name) ?>" 
                     onclick="return confirm('Yakin ingin menghapus data ini?')" 
                     class="inline-block text-red-500 hover:text-red-700 text-sm" 
                     title="Hapus">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
              <tr class="border-t bg-gray-100 font-semibold">
                <td colspan="2" class="px-4 py-2 text-right whitespace-nowrap">TOTAL</td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($total_quantity, 0, ',', '.') ?></td>
                <td colspan="2" class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($total_price_all, 0, ',', '.') ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap"></td>
              </tr>
          </tbody>
        </table>
      </div>
      
    </section>
    
    <!-- Biaya Produksi Tambahan -->
    <section>
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-md font-semibold">BIAYA PRODUKSI</h1>
        <a href="produksi-tambah-biaya.php?id=<?= $id ?>&name=<?= urlencode($name) ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
          <span class="ml-2 group-hover:text-gray-800">Biaya</span>
        </a>
      </div>
      
      <div  class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400 text-sm">
            <tr>
              <th class="px-4 py-3 text-center whitespace-nowrap">Tanggal</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Deskripsi</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Jumlah</th>
              <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $total_amount = 0;
              while ($row = $expenses->fetch_assoc()):
                $total_amount += $row['amount'];
            ?>
              <tr class="border-t">
                <td class="px-4 py-2 text-center whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(
                  date("d/m/Y", strtotime($row['created_at']))
                ) ?></td>
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($row['description']) ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap">
                  <a href="produksi-edit-biaya.php?expense_id=<?= ($row['id']) ?>&id=<?= $id ?>&name=<?= urlencode($name) ?>" 
                     class="inline-block text-blue-500 hover:text-blue-700 mr-2 text-sm" 
                     title="Edit">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <a href="produksi-hapus-biaya.php?expense_id=<?= ($row['id']) ?>&id=<?= $id ?>&name=<?= urlencode($name) ?>" 
                     onclick="return confirm('Yakin ingin menghapus data ini?')" 
                     class="inline-block text-red-500 hover:text-red-700 text-sm" 
                     title="Hapus">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
              <tr class="border-t bg-gray-100 font-semibold">
                <td colspan="2" class="px-4 py-2 text-right whitespace-nowrap">TOTAL</td>
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($total_amount, 0, ',', '.') ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap"></td>
              </tr>
          </tbody>
        </table>
      </div>
    </section>
    <?php $total_biaya_produksi = $total_price_all + $total_amount; ?>
    
    <section class="mt-4">
      <div class="flex justify-end">
        <div class="bg-yellow-400 rounded-lg px-6 py-4 w-full md:w-1/2 lg:w-1/3">
          <div class="flex justify-between text-sm text-gray-800 border-b border-gray-800 pb-2 mb-2">
            <span>Total Biaya Produksi</span>
            <span class="text-right text-gray-800 font-semibold">Rp <?= number_format($total_biaya_produksi, 0, ',', '.') ?></span>
          </div>
        </div>
      </div>
    </section>

    <div class="flex justify-center md:justify-end items-center">
        <button id="btnSelesai" name="tombolSelesai" class="group flex justify-center items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded-lg text-sm transition w-1/2 md:w-1/4 lg:w-1/6">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">check</span>
          <span class="ml-2 group-hover:text-gray-800 font-semibold">Produksi Selesai</span>
        </button>
      </div>
  </main>

<!-- Modal -->
<div id="modalProduksi" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl p-6 w-11/12 max-w-md shadow-lg relative">
    <h2 class="text-md font-semibold mb-4">Jumlah Hasil Produksi (gram)</h2>
    <form method="POST">
      <input type="text" name="jumlah_produksi" id="quantity" inputmode="decimal" class="w-full border border-gray-300 px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Jumlah hasil Produksi...">
      <div class="flex justify-end space-x-2 mt-4">
        <button type="button" onclick="tutupModal()" class="flex justify-center items-center px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 text-sm">
          <span class="material-symbols-outlined texmr-2 mr-2">close</span>
          <span class="">Batal</span>
        </button>
        <button type="submit" name="submit_produksi" class="flex justify-center items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
          <span class="material-symbols-outlined text-sm text-yellow-400 hover:text-gray-800 mr-2">save</span>
          <span class="text-sm hover:text-gray-800">Simpan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById("modalProduksi");
const tombolSelesai = document.getElementById("btnSelesai");

tombolSelesai.addEventListener("click", () => {
  modal.classList.remove("hidden");
});

function tutupModal() {
  modal.classList.add("hidden");
}

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