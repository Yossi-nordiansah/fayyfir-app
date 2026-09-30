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
        // akumulasi total_selling
        $grand_total += $row['total_selling'];  
        // Ambil DP sekali saja (karena di DB terduplikasi di tiap baris invoice)
        if ($dp === 0) $dp = $row['dp'];  
        $db_status = $row['status'] ?? 'Lunas';
      ?>  
      <tr>  
        <td class="px-4 py-2 whitespace-nowrap border"><?= htmlspecialchars($row['product_name']) ?></td>  
        <td class="px-4 py-2 whitespace-nowrap border text-right">
          <?php if (!empty($row['qty_shrinkage']) && (float)$row['qty_shrinkage'] > 0): ?>
            <div><?= number_format($row['qty'] - $row['qty_shrinkage'], 0, ',', '.') ?> <?= htmlspecialchars($row['symbol'] ?? 'gr') ?></div>
            <div class="text-xs text-red-500">(Kirim: <?= number_format($row['qty'], 0, ',', '.') ?>, Susut: <?= number_format($row['qty_shrinkage'], 0, ',', '.') ?>)</div>
          <?php else: ?>
            <?= number_format($row['qty'], 0, ',', '.') ?> <?= htmlspecialchars($row['symbol'] ?? 'gr') ?>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 whitespace-nowrap border text-right">Rp <?= number_format($row['price'],0,',','.') ?></td>  
        <td class="px-4 py-2 whitespace-nowrap border text-right">Rp <?= number_format($row['total_selling'],0,',','.') ?></td>  
      </tr>  
    <?php endwhile; ?>  

    <?php
    // Ambil riwayat angsuran dari invoice_payments
    $stmt_pay = $conn->prepare("
        SELECT payment_date, jumlah, keterangan
        FROM invoice_payments
        WHERE invoice_number = ?
        ORDER BY payment_date ASC, id ASC
    ");
    $stmt_pay->bind_param("s", $invoice);
    $stmt_pay->execute();
    $payments_res = $stmt_pay->get_result();
    $payments = [];
    $total_angsuran = 0;
    while ($p = $payments_res->fetch_assoc()) {
        $payments[] = $p;
        $total_angsuran += (float)$p['jumlah'];
    }
    $stmt_pay->close();

    $total_dibayar = (float)$dp + $total_angsuran;
    $raw_remaining = max(0, (float)$grand_total - $total_dibayar);
    $is_lunas = (strcasecmp($db_status ?? '', 'lunas') === 0) || ($raw_remaining <= 0.01);

    if ($is_lunas) {
        $status = 'lunas';
        $remaining = 0;
        if ((float)$dp <= 0 && $total_angsuran <= 0) {
            $total_dibayar = (float)$grand_total;
        }
    } else {
        $status = 'dp';
        $remaining = $raw_remaining;
    }
    ?>

      <tr class="font-semibold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2 text-gray-700">Total Tagihan</td>  
        <td class="bg-gray-100 text-right px-4 py-2 whitespace-nowrap font-bold">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>  
      </tr>
      <?php if ($dp > 0): ?>
      <tr>  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2 text-gray-600">
          DP Awal
          <a href="transaksi-angsuran-invoice.php?invoice=<?= urlencode($invoice) ?>&type=dp" target="_blank" class="ml-1 text-blue-600 hover:text-blue-800 inline-flex items-center align-middle" title="Cetak Bukti DP">
            <span class="material-symbols-outlined text-sm">print</span>
          </a>
        </td>  
        <td class="bg-gray-50 text-right px-4 py-2 whitespace-nowrap">Rp <?= number_format($dp, 0, ',', '.') ?></td>  
      </tr>
      <?php endif; ?>
      <?php foreach ($payments as $i => $pay): ?>
      <tr>  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2 text-blue-700 text-xs">
          Angsuran <?= $i + 1 ?> <?= !empty($pay['keterangan']) ? '(' . htmlspecialchars($pay['keterangan']) . ')' : '' ?>
          <a href="transaksi-angsuran-invoice.php?invoice=<?= urlencode($invoice) ?>&payment_id=<?= (int)$pay['id'] ?>" target="_blank" class="ml-1 text-blue-600 hover:text-blue-800 inline-flex items-center align-middle" title="Cetak Bukti Angsuran ke-<?= $i + 1 ?>">
            <span class="material-symbols-outlined text-sm">print</span>
          </a>
          <span class="block text-gray-400 text-[10px]"><?= date('d/m/Y H:i', strtotime($pay['payment_date'])) ?></span>
        </td>  
        <td class="bg-blue-50 text-right px-4 py-2 whitespace-nowrap text-blue-800 font-medium">Rp <?= number_format($pay['jumlah'], 0, ',', '.') ?></td>  
      </tr>
      <?php endforeach; ?>
      <?php if ($total_dibayar > 0 || $is_lunas): ?>
      <tr class="font-semibold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2 text-gray-700">Total Dibayar</td>  
        <td class="bg-blue-100 text-right px-4 py-2 whitespace-nowrap text-blue-900">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></td>  
      </tr>
      <?php endif; ?>
      <tr class="font-bold">  
        <td colspan="2" class="text-right px-4 py-2"></td>  
        <td class="text-right px-4 py-2 <?= $remaining > 0 ? 'text-red-600' : 'text-green-700' ?>">Sisa Tagihan</td>  
        <td class="<?= $remaining > 0 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-700' ?> text-right px-4 py-2 whitespace-nowrap">Rp <?= number_format($remaining, 0, ',', '.') ?></td>  
      </tr>
  </tbody>  
</table>

<input type="hidden" id="invoiceStatus" value="<?= htmlspecialchars($status) ?>">
<input type="hidden" id="invoiceNumber" value="<?= htmlspecialchars($invoice) ?>">
</div>