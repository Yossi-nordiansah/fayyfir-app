<?php  
require "config.php";  
$invoice = $_GET['invoice'] ?? '';  
if ($invoice === '') {  
  echo "<p class='text-red-500'>Invoice tidak valid.</p>";  
  exit;  
}  

// ambil data transaksi dengan relasi ke product_stocks & units
$stmt = $conn->prepare("  
  SELECT s.*, ps.product_name, u.symbol   
  FROM selling_products s  
  LEFT JOIN productions pr ON s.product_id = pr.id  
  LEFT JOIN product_stocks ps ON s.product_id = ps.id  
  LEFT JOIN units u ON ps.unit_id = u.id  
  WHERE s.invoice_number = ?  
");  
$stmt->bind_param("s", $invoice);  
$stmt->execute();  
$result = $stmt->get_result();  

if ($result->num_rows === 0) {  
  echo "<p class='text-gray-500'>Data tidak ditemukan.</p>";  
  exit;  
}  

// inisialisasi total
$grand_total = 0;  
$dp = 0;  
?>  

<div class="overflow-x-auto">  
<table class="w-full text-sm">  
  <thead class="bg-gray-100">  
    <tr>  
      <th class="px-4 py-2 border">Produk</th>  
      <th class="px-4 py-2 border">Qty</th>  
      <th class="px-4 py-2 border">Harga</th>  
      <th class="px-4 py-2 border">Subtotal</th>  
    </tr>  
  </thead>  
  <tbody>  
    <?php while($row = $result->fetch_assoc()): ?>  
      <?php  
        // akumulasi total_selling dan dp  
        $grand_total += $row['total_selling'];  
        $dp += $row['dp'];  
      ?>  
      <tr>  
        <td class="px-4 py-2 whitespace-nowrap border"><?= htmlspecialchars($row['product_name']) ?></td>  
        <td class="px-4 py-2 whitespace-nowrap border text-right"><?= number_format($row['qty'],0,',','.') ?> <?= htmlspecialchars($row['symbol']) ?></td>  
        <td class="px-4 py-2 whitespace-nowrap border text-right">Rp <?= number_format($row['price'],0,',','.') ?></td>  
        <td class="px-4 py-2 whitespace-nowrap border text-right">Rp <?= number_format($row['total_selling'],0,',','.') ?></td>  
      </tr>  
    <?php endwhile; ?>  

    <?php $remaining = $grand_total - $dp; ?>  

      <tr class="font-semibold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2">Total</td>  
        <td class=" bg-gray-100 text-right px-4 py-2 whitespace-nowrap">Rp <?= number_format($grand_total,0,',','.') ?></td>  
      </tr>
      <tr class="font-semibold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2">DP</td>  
        <td class=" bg-gray-50 text-right px-4 py-2 whitespace-nowrap">Rp <?= number_format($dp,0,',','.') ?></td>  
      </tr>
      <tr class="font-semibold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2">Remaining</td>  
        <td class=" bg-gray-100 text-right px-4 py-2 whitespace-nowrap">Rp <?= number_format($remaining,0,',','.') ?></td>  
      </tr>
  </tbody>  
</table>

<?php
// ambil status terakhir transaksi berdasarkan invoice
$statusStmt = $conn->prepare("SELECT status FROM selling_products WHERE invoice_number = ? LIMIT 1");
$statusStmt->bind_param("s", $invoice);
$statusStmt->execute();
$statusRes = $statusStmt->get_result();
$statusRow = $statusRes->fetch_assoc();
$status = strtolower($statusRow['status'] ?? '');
?>
<input type="hidden" id="invoiceStatus" value="<?= htmlspecialchars($status) ?>">
<input type="hidden" id="invoiceNumber" value="<?= htmlspecialchars($invoice) ?>">
</div>