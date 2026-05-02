<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

$invoice = $_GET['invoice'] ?? null;
if (!$invoice) {
  header("Location: transaksi-produk");
  exit();
}

/* ============================
   Ambil Data Transaksi
============================ */
$stmt = $conn->prepare("
SELECT 
  s.*,
  ps.product_name,
  ps.quantity AS stock,
  u.symbol
FROM selling_products s
LEFT JOIN product_stocks ps ON s.product_id = ps.id
LEFT JOIN units u ON ps.unit_id = u.id
WHERE s.invoice_number = ?
");

$stmt->bind_param("s", $invoice);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
  echo "Data transaksi tidak ditemukan.";
  exit();
}

$items = [];
$total = 0;
$dp = 0;

while ($row = $result->fetch_assoc()) {
  $items[] = $row;
  $total += $row['total_selling'];
  $dp = $row['dp'];
}
$stmt->close();
$remaining = $total - $dp;

/* ============================
   Ambil Daftar Produk
============================ */
$products = [];
$res = $conn->query("
SELECT 
ps.id,
ps.product_name,
ps.quantity,
u.symbol
FROM product_stocks ps
LEFT JOIN units u ON ps.unit_id = u.id
ORDER BY ps.product_name ASC
");

while($row = $res->fetch_assoc()){
$products[] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Transaksi</title>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">

<div class="flex justify-between items-center">
<a href="transaksi-rincian?id=<?= $items[0]['buyer_id'] ?? '' ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
<span class="material-symbols-outlined text-base">chevron_left</span>
<span class="hidden lg:inline">Kembali</span>
</a>

<h1 class="text-lg font-semibold">Edit Transaksi</h1>
</div>
</header>

<main class="pt-20 px-4 pb-32 max-w-5xl mx-auto space-y-6">
<section class="bg-white p-6 rounded-lg shadow">
<h2 class="text-lg font-semibold mb-4">
Invoice : <?= htmlspecialchars($invoice) ?>
</h2>


<form method="POST" action="transaksi-rincian-update.php" id="editForm">
<input type="hidden" name="deleted_items" id="deleted_items">
<input type="hidden" name="invoice" value="<?= htmlspecialchars($invoice) ?>">
<input type="hidden" name="buyer_id" value="<?= $items[0]['buyer_id'] ?>">

<div id="orderItems" class="space-y-4">
<?php foreach ($items as $item): ?>
<div class="grid grid-cols-12 gap-2 items-center order-row">
<input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
<input type="hidden" name="product_id[]" value="<?= $item['product_id'] ?>">

<div class="col-span-12 md:col-span-4">
<input type="text" value="<?= htmlspecialchars($item['product_name']) ?>" class="w-full border rounded px-2 py-1 bg-gray-100" readonly>
</div>

<div class="col-span-3 md:col-span-2">
<input type="text" name="qty[]" data-stock="<?= $item['stock'] ?>" class="w-full border rounded px-2 py-1 qty text-right" value="<?= number_format($item['qty'],0,',','.') ?>">
</div>

<div class="col-span-4 md:col-span-2">
<input type="text" name="price[]" class="w-full border rounded px-2 py-1 price text-right" value="<?= number_format($item['price'],0,',','.') ?>">
</div>

<div class="col-span-4 md:col-span-3">
<input type="text" class="w-full border rounded px-2 py-1 subtotal text-right" readonly>
</div>

<div class="col-span-1 text-center">
<button type="button" class="deleteItem text-red-500">
<span class="material-symbols-outlined">delete</span>
</button>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="text-left mt-4">
<button type="button" id="addProductBtn" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">Tambah Produk</button>
</div>

<div class="flex justify-between items-start mt-6">
<div>
</div>

<div class="w-56 space-y-2">
<div class="text-right font-semibold">Total : <span id="grandTotal">0</span>
</div>

<div class="text-right">
<label class="text-sm font-semibold mr-2">DP:</label>

<input type="text" id="dpInput" name="dp" class="border rounded px-2 py-1 w-40 text-right" value="<?= number_format($dp,0,',','.') ?>">
</div>

<div class="text-right font-semibold">
Remaining :
<span id="remaining">0</span>
</div>
</div>
</div>

<div class="mt-6 text-right">

<button
type="submit"
class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded">
Update Transaksi
</button>
</div>
</form>
</section>
</main>



<script>
function parseNum(val){
if(!val) return 0;
let clean = val.toString().replace(/\./g,'').replace(',', '.');
let num = parseFloat(clean);
return isNaN(num) ? 0 : num;
}

function formatIDR(n){
return n.toLocaleString("id-ID");
}

function calculateRow(row){
let qty = parseNum(row.querySelector(".qty").value);
let price = parseNum(row.querySelector(".price").value);
let subtotal = qty * price / 1000;
row.querySelector(".subtotal").value = formatIDR(subtotal);
return subtotal;
}


function calculateTotal(){
let total = 0;
document.querySelectorAll(".order-row").forEach(r=>{
total += calculateRow(r);
});
let dp = parseNum(document.getElementById("dpInput").value);
let remaining = Math.max(total - dp,0);
document.getElementById("grandTotal").textContent = formatIDR(total);
document.getElementById("remaining").textContent = formatIDR(remaining);
}

document.addEventListener("input",(e)=>{
if(e.target.classList.contains("qty") || e.target.classList.contains("price") || e.target.id==="dpInput"){
let raw = e.target.value.replace(/\./g,'');
if(raw) e.target.value = formatIDR(parseInt(raw));
calculateTotal();
}
});

/* ============================
   Validasi Stok
============================ */
document.addEventListener("input",(e)=>{
if(e.target.classList.contains("qty")){
const row = e.target.closest(".order-row");
const stock = parseNum(e.target.dataset.stock);
let qty = parseNum(e.target.value);
if(qty > stock){
e.target.value = formatIDR(stock);
alert("Qty melebihi stok tersedia.");
}
}
});

/* ============================
   Tambah Produk
============================ */
document.getElementById("addProductBtn").addEventListener("click",()=>{
let template = `
<div class="grid grid-cols-12 gap-2 items-center order-row">
<input type="hidden" name="item_id[]" value="new">
<div class="col-span-12 md:col-span-4">
<select name="product_id[]" class="productSelect w-full border rounded px-2 py-1">
<option value="">Pilih Produk</option>
<?php foreach($products as $p): ?>
<option value="<?= $p['id'] ?>" data-stock="<?= $p['quantity'] ?>">
<?= htmlspecialchars($p['product_name']) ?>
</option>
<?php endforeach; ?>
</select>

</div>
<div class="col-span-3 md:col-span-2">
<input type="text"
name="qty[]"
data-stock="0"
class="w-full border rounded px-2 py-1 qty text-right"
value="0">
</div>
<div class="col-span-4 md:col-span-2">
<input type="text"
name="price[]"
class="w-full border rounded px-2 py-1 price text-right"
value="0">
</div>
<div class="col-span-4 md:col-span-3">
<input type="text"
class="w-full border rounded px-2 py-1 subtotal text-right"
readonly>
</div>
<div class="col-span-1 text-center">
<button type="button" class="removeRow text-red-500">
<span class="material-symbols-outlined">delete</span>
</button>
</div>
</div>
`;
document.getElementById("orderItems").insertAdjacentHTML("beforeend",template);
calculateTotal();
});

/* ============================
   Hapus Row
============================ */
document.addEventListener("click",(e)=>{
if(e.target.closest(".removeRow")){
e.target.closest(".order-row").remove();
calculateTotal();
}
});

/* ============================
   Hapus Item Lama (kembalikan stok)
============================ */
let deletedItems = [];
document.addEventListener("click",(e)=>{
if(e.target.closest(".deleteItem")){
let row = e.target.closest(".order-row");
let itemIdInput = row.querySelector('input[name="item_id[]"]');
let itemId = itemIdInput ? itemIdInput.value : null;

/* jika item lama dari database */
if(itemId && itemId !== "new"){
deletedItems.push(itemId);
document.getElementById("deleted_items").value = deletedItems.join(",");
}
/* hapus dari UI */
row.remove();
calculateTotal();
}
});

/* ============================
   Set Stock Saat Produk Dipilih
============================ */
document.addEventListener("change",(e)=>{
if(e.target.classList.contains("productSelect")){
let option = e.target.selectedOptions[0];
let stock = option.dataset.stock || 0;
let row = e.target.closest(".order-row");
row.querySelector(".qty").dataset.stock = stock;
}
});

/* ============================
   Bersihkan Format Saat Submit
============================ */
document.getElementById("editForm").addEventListener("submit",()=>{
document.querySelectorAll(".qty,.price,#dpInput").forEach(input=>{
input.value = parseNum(input.value);
});
});
window.addEventListener("DOMContentLoaded",calculateTotal);
</script>
</body>
</html>