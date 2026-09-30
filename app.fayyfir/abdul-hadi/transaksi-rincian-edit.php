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

// AUTO-CREATE tabel invoice_payments jika belum ada
$conn->query("
    CREATE TABLE IF NOT EXISTS `invoice_payments` (
      `id`             INT NOT NULL AUTO_INCREMENT,
      `invoice_number` VARCHAR(100) NOT NULL,
      `payment_date`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `jumlah`         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `keterangan`     TEXT NULL,
      `created_by`     INT NULL,
      `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_invoice_number` (`invoice_number`),
      KEY `idx_payment_date`   (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

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
$selling_date = null;

while ($row = $result->fetch_assoc()) {
   $items[] = $row;
   $total += $row['total_selling'];
   $dp = $row['dp'];
   if (!$selling_date && !empty($row['selling_date'])) {
      $selling_date = $row['selling_date'];
   }
}
$stmt->close();

$selling_date_val = !empty($selling_date) ? date('Y-m-d\TH:i', strtotime($selling_date)) : date('Y-m-d\TH:i');

/* ============================
   Ambil Riwayat Angsuran
============================ */
$stmt_pay = $conn->prepare("
    SELECT id, payment_date, jumlah, keterangan
    FROM invoice_payments
    WHERE invoice_number = ?
    ORDER BY payment_date ASC, id ASC
");
$stmt_pay->bind_param('s', $invoice);
$stmt_pay->execute();
$payments_result = $stmt_pay->get_result();
$payments = [];
$total_angsuran = 0;
while ($p = $payments_result->fetch_assoc()) {
   $payments[] = $p;
   $total_angsuran += (float)$p['jumlah'];
}
$stmt_pay->close();

$raw_status    = $items[0]['status'] ?? 'Lunas';
$total_dibayar = (float)$dp + $total_angsuran;
$raw_sisa      = max(0, (float)$total - $total_dibayar);
$is_lunas      = (strcasecmp($raw_status, 'lunas') === 0) || ($raw_sisa <= 0.01);

if ($is_lunas) {
   $status_inv   = 'Lunas';
   $sisa_tagihan = 0;
   if ((float)$dp <= 0 && $total_angsuran <= 0) {
      $total_dibayar = (float)$total;
   }
} else {
   $status_inv   = 'DP';
   $sisa_tagihan = $raw_sisa;
}

// Tentukan URL Cetak Tagihan (transaksi pembayaran terbaru/terakhir)
$latest_print_url = null;
if (!empty($payments)) {
   $last_pay = $payments[count($payments) - 1];
   $latest_print_url = "transaksi-angsuran-invoice.php?invoice=" . urlencode($invoice) . "&payment_id=" . (int)$last_pay['id'];
} elseif ((float)$dp > 0) {
   $latest_print_url = "transaksi-angsuran-invoice.php?invoice=" . urlencode($invoice) . "&type=dp";
} else {
   $latest_print_url = "transaksi-invoice.php?invoice=" . urlencode($invoice);
}

/* ============================
   Ambil Daftar Produk
============================ */
$products = [];
$res = $conn->query("
SELECT 
    ps.id,
    ps.product_name,
    ps.quantity,
    u.symbol,
    (SELECT p2.price_weight FROM productions p2 
     WHERE p2.product_id = ps.id 
     AND p2.status IN ('Selesai','Terhitung','Terjual') 
     ORDER BY p2.id DESC LIMIT 1) as price_weight
FROM product_stocks ps
LEFT JOIN units u ON ps.unit_id = u.id
WHERE ps.quantity > 0
ORDER BY ps.product_name ASC
");

if ($res) {
   while ($row = $res->fetch_assoc()) {
      $products[] = $row;
   }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Transaksi – <?= htmlspecialchars($invoice) ?></title>
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
         <div class="w-20"></div>
      </div>
   </header>

   <main class="pt-20 px-4 pb-32 max-w-5xl mx-auto space-y-6">

      <!-- ===== FORM EDIT PRODUK / TRANSAKSI ===== -->
      <section class="bg-white p-6 rounded-lg shadow">
         <form method="POST" action="transaksi-rincian-update.php" id="editForm">
            <input type="hidden" name="deleted_items" id="deleted_items">
            <input type="hidden" name="invoice" value="<?= htmlspecialchars($invoice) ?>">
            <input type="hidden" name="buyer_id" value="<?= $items[0]['buyer_id'] ?>">

            <div class="flex flex-wrap justify-between items-center mb-6 pb-4 border-b gap-4">
               <h2 class="text-lg font-semibold text-gray-800">
                  Invoice : <?= htmlspecialchars($invoice) ?>
               </h2>
               <div class="flex items-center gap-2">
                  <label class="text-sm font-semibold text-gray-700">Tanggal Transaksi:</label>
                  <input type="datetime-local" name="selling_date" value="<?= $selling_date_val ?>" class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-800 focus:outline-none focus:ring focus:ring-yellow-300" required>
               </div>
            </div>

            <div id="orderItems" class="space-y-4">
               <?php foreach ($items as $item): ?>
                  <?php $avail_stock = (float)($item['stock'] ?? 0) + (float)($item['qty'] ?? 0); ?>
                  <div class="grid grid-cols-12 gap-2 items-center order-row">
                     <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
                     <input type="hidden" name="product_id[]" value="<?= $item['product_id'] ?>">

                     <div class="col-span-12 md:col-span-3">
                        <label class="block text-xs text-gray-500 mb-0.5 md:hidden">Produk</label>
                        <input type="text" value="<?= htmlspecialchars($item['product_name']) ?>" class="w-full border rounded px-2 py-1 bg-gray-100" readonly>
                     </div>

                     <div class="col-span-4 md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5 md:hidden">Qty Kirim (g)</label>
                        <input type="text" name="qty[]" data-stock="<?= $avail_stock ?>" class="w-full border rounded px-2 py-1 qty text-right" value="<?= number_format($item['qty'], 0, ',', '.') ?>" title="Bobot barang keluar dari gudang (gram)">
                     </div>

                     <div class="col-span-4 md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5 md:hidden">Susut (g)</label>
                        <input type="text" name="qty_shrinkage[]" class="w-full border rounded px-2 py-1 qty-shrinkage text-right text-red-600 font-medium" value="<?= number_format($item['qty_shrinkage'] ?? 0, 0, ',', '.') ?>" placeholder="Susut (g)..." title="Susut pengiriman yang ditanggung penjual (gram)">
                     </div>

                     <div class="col-span-4 md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5 md:hidden">Harga / Kg</label>
                        <input type="text" name="price[]" class="w-full border rounded px-2 py-1 price text-right" value="<?= number_format($item['price'], 0, ',', '.') ?>">
                     </div>

                     <div class="col-span-11 md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5 md:hidden">Subtotal</label>
                        <input type="text" class="w-full border rounded px-2 py-1 subtotal text-right bg-gray-50 font-semibold" readonly>
                     </div>

                     <div class="col-span-1 text-center">
                        <button type="button" class="deleteItem text-red-500 hover:text-red-700 font-bold" title="Hapus Produk">
                           <span class="material-symbols-outlined">delete</span>
                        </button>
                     </div>

                     <div class="col-span-12 -mt-1 mb-1">
                        <small class="text-blue-600 text-xs stock-warning">Stok tersedia: <?= number_format($avail_stock, 0, ',', '.') ?> <?= htmlspecialchars($item['symbol'] ?? 'gram') ?> (Sisa gudang: <?= number_format($item['stock'] ?? 0, 0, ',', '.') ?> <?= htmlspecialchars($item['symbol'] ?? 'gram') ?>)</small>
                     </div>
                  </div>
               <?php endforeach; ?>
            </div>

            <div class="text-left mt-4">
               <button type="button" id="addProductBtn" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">Tambah Produk</button>
            </div>

            <div class="flex justify-between items-start mt-6">
               <div></div>
               <div class="w-64 space-y-2">
                  <div class="text-right font-semibold">Total : <span id="grandTotal">0</span></div>
                  <div class="text-right">
                     <label class="text-sm font-semibold mr-2">DP Awal:</label>
                     <input type="text" id="dpInput" name="dp" class="border rounded px-2 py-1 w-40 text-right" value="<?= number_format($dp, 0, ',', '.') ?>">
                  </div>
                  <div class="text-right font-semibold text-gray-600">Remaining : <span id="remaining">0</span></div>
               </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end items-center gap-3 pt-4 border-t">
               <button type="submit" id="btnUpdateTransaksi" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-6 py-2.5 rounded-lg shadow flex items-center gap-2 transition hover:shadow-md">
                  <span class="material-symbols-outlined text-xl">save</span>
                  Update Transaksi
               </button>
            </div>
         </form>
      </section>

      <!-- ===== RINGKASAN & RIWAYAT ANGSURAN ===== -->
      <section class="bg-white p-6 rounded-lg shadow">
         <div class="flex flex-wrap justify-between items-center mb-4 gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Riwayat Pembayaran</h2>
            <div class="flex flex-wrap items-center gap-2">
               <a href="<?= $latest_print_url ?>"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded text-sm font-medium transition shadow"
                  title="Cetak Tagihan / Bukti Pembayaran Terakhir">
                  <span class="material-symbols-outlined text-sm">print</span>
                  Cetak Tagihan
               </a>
               <?php if ($sisa_tagihan > 0.01): ?>
                  <button id="btnTambahAngsuran"
                     class="group flex items-center gap-2 bg-gray-800 hover:bg-yellow-400 text-white hover:text-gray-900 px-4 py-2 rounded text-sm font-medium transition shadow">
                     <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-900">add_circle</span>
                     Tambah Angsuran
                  </button>
               <?php else: ?>
                  <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">
                     <span class="material-symbols-outlined text-sm">check_circle</span>
                     Lunas
                  </span>
               <?php endif; ?>
            </div>
         </div>

         <!-- Ringkasan 3 Kartu -->
         <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
               <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Tagihan</p>
               <p class="text-lg font-bold text-gray-800 mt-0.5">Rp <?= number_format($total, 0, ',', '.') ?></p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
               <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Total Dibayar</p>
               <p class="text-lg font-bold text-blue-700 mt-0.5" id="summaryDibayar">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></p>
            </div>
            <div class="<?= $sisa_tagihan > 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' ?> rounded-lg p-3 border">
               <p class="text-xs <?= $sisa_tagihan > 0 ? 'text-red-500' : 'text-green-600' ?> font-medium uppercase tracking-wide">Sisa Tagihan</p>
               <p class="text-lg font-bold <?= $sisa_tagihan > 0 ? 'text-red-600' : 'text-green-700' ?> mt-0.5" id="summarySisa">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></p>
            </div>
         </div>

         <!-- Tabel Riwayat -->
         <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200" id="tabelAngsuran">
               <thead class="bg-gray-800 text-yellow-400 text-xs uppercase tracking-wider">
                  <tr>
                     <th class="px-4 py-2 text-center whitespace-nowrap">No</th>
                     <th class="px-4 py-2 text-center whitespace-nowrap">Tanggal</th>
                     <th class="px-4 py-2 text-right whitespace-nowrap">Jumlah</th>
                     <th class="px-4 py-2 text-left whitespace-nowrap">Keterangan</th>
                     <th class="px-4 py-2 text-center whitespace-nowrap">Aksi</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-gray-100" id="bodyAngsuran">
                  <!-- Baris DP Awal -->
                  <?php if ($dp > 0): ?>
                     <tr class="bg-yellow-50">
                        <td class="px-4 py-2 text-center font-semibold">1</td>
                        <td class="px-4 py-2 text-center whitespace-nowrap"><?= date('d M Y', strtotime($selling_date)) ?></td>
                        <td class="px-4 py-2 text-right whitespace-nowrap font-semibold text-gray-800">Rp <?= number_format($dp, 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-left text-gray-500 italic">DP Awal</td>
                        <td class="px-4 py-2 text-center whitespace-nowrap">
                           <button type="button"
                              class="btn-edit-pembayaran inline-flex items-center gap-1 bg-yellow-500 hover:bg-yellow-600 text-white px-2.5 py-1 rounded text-xs transition shadow font-medium"
                              data-type="dp"
                              data-id="0"
                              data-nomor="1 (DP Awal)"
                              data-tanggal="<?= date('Y-m-d\TH:i', strtotime($selling_date)) ?>"
                              data-jumlah="<?= (float)$dp ?>"
                              data-keterangan="DP Awal"
                              title="Edit DP Awal">
                              <span class="material-symbols-outlined text-xs">edit</span>
                              Edit
                           </button>
                        </td>
                     </tr>
                  <?php endif; ?>
                  <!-- Angsuran dari invoice_payments -->
                  <?php foreach ($payments as $i => $pay): ?>
                     <tr>
                        <td class="px-4 py-2 text-center"><?= $i + 1 ?></td>
                        <td class="px-4 py-2 text-center whitespace-nowrap"><?= date('d M Y H:i', strtotime($pay['payment_date'])) ?></td>
                        <td class="px-4 py-2 text-right whitespace-nowrap font-semibold text-blue-700">Rp <?= number_format($pay['jumlah'], 0, ',', '.') ?></td>
                        <?php
                        $nomor_urut_angsuran = $i + 1;
                        $ket_val = trim($pay['keterangan'] ?? '');
                        $ket_tampil = ($ket_val !== '' && $ket_val !== '-') ? $ket_val : "Angsuran ke-{$nomor_urut_angsuran}";
                        ?>
                        <td class="px-4 py-2 text-left text-gray-600"><?= htmlspecialchars($ket_tampil) ?></td>
                        <td class="px-4 py-2 text-center whitespace-nowrap">
                           <button type="button"
                              class="btn-edit-pembayaran inline-flex items-center gap-1 bg-yellow-500 hover:bg-yellow-600 text-white px-2.5 py-1 rounded text-xs transition shadow font-medium"
                              data-type="angsuran"
                              data-id="<?= (int)$pay['id'] ?>"
                              data-nomor="<?= $i + 1 ?>"
                              data-tanggal="<?= date('Y-m-d\TH:i', strtotime($pay['payment_date'])) ?>"
                              data-jumlah="<?= (float)$pay['jumlah'] ?>"
                              data-keterangan="<?= htmlspecialchars($pay['keterangan'] ?? '') ?>"
                              title="Edit Angsuran">
                              <span class="material-symbols-outlined text-xs">edit</span>
                              Edit
                           </button>
                        </td>
                     </tr>
                  <?php endforeach; ?>
                  <?php if ($dp <= 0 && empty($payments)): ?>
                     <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                           <span class="material-symbols-outlined block text-3xl mb-1">payments</span>
                           Belum ada riwayat pembayaran.
                        </td>
                     </tr>
                  <?php endif; ?>
               </tbody>
               <?php if ($total_dibayar > 0): ?>
                  <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-300 text-sm">
                     <tr>
                        <td colspan="2" class="px-4 py-2 text-right text-gray-700">Total Dibayar</td>
                        <td class="px-4 py-2 text-right text-blue-800">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></td>
                        <td colspan="2"></td>
                     </tr>
                     <tr>
                        <td colspan="2" class="px-4 py-2 text-right text-gray-700">Sisa Tagihan</td>
                        <td class="px-4 py-2 text-right <?= $sisa_tagihan > 0 ? 'text-red-600' : 'text-green-700' ?>">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></td>
                        <td colspan="2"></td>
                     </tr>
                  </tfoot>
               <?php endif; ?>
            </table>
         </div>
      </section>
   </main>

   <!-- ===== MODAL TAMBAH ANGSURAN ===== -->
   <div id="modalAngsuran" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all">
         <!-- Header -->
         <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
               <span class="material-symbols-outlined text-yellow-500">payments</span>
               Tambah Pembayaran
            </h3>
            <button id="closeModalAngsuran" class="text-gray-400 hover:text-gray-700 focus:outline-none">
               <span class="material-symbols-outlined">close</span>
            </button>
         </div>
         <!-- Body -->
         <div class="px-6 py-5 space-y-4">
            <!-- Info Tagihan -->
            <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1 border border-gray-200">
               <div class="flex justify-between">
                  <span class="text-gray-500">Total Tagihan</span>
                  <span class="font-semibold text-gray-800">Rp <?= number_format($total, 0, ',', '.') ?></span>
               </div>
               <div class="flex justify-between">
                  <span class="text-gray-500">Sudah Dibayar</span>
                  <span class="font-semibold text-blue-700">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></span>
               </div>
               <div class="flex justify-between border-t pt-1 mt-1">
                  <span class="font-bold text-red-600">Sisa Tagihan</span>
                  <span class="font-bold text-red-600" id="sisaTagihanModal">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></span>
               </div>
            </div>
            <!-- Input Jumlah -->
            <div>
               <label for="inputJumlahAngsuran" class="block text-sm font-bold text-gray-700 mb-1">
                  Jumlah Pembayaran <span class="text-red-500">*</span>
               </label>
               <input type="text" id="inputJumlahAngsuran"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-right text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-yellow-400"
                  placeholder="0" autocomplete="off">
            </div>
            <!-- Input Keterangan -->
            <div>
               <label for="inputKeteranganAngsuran" class="block text-sm font-bold text-gray-700 mb-1">
                  Keterangan <span class="text-gray-400 font-normal">(opsional)</span>
               </label>
               <input type="text" id="inputKeteranganAngsuran"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                  placeholder="Contoh: Angsuran ke-<?= count($payments) + 1 ?>, Transfer BCA...">
            </div>
            <!-- Preview Sisa -->
            <div id="previewSisa" class="hidden bg-blue-50 rounded-lg p-3 text-sm border border-blue-200">
               <div class="flex justify-between items-center">
                  <span class="text-blue-700 font-semibold">Sisa Tagihan:</span>
                  <span id="previewSisaVal" class="font-bold text-blue-900 text-base">Rp 0</span>
               </div>
            </div>
            <!-- Pesan Error -->
            <div id="angsuranError" class="hidden bg-red-50 text-red-600 text-sm rounded p-2 border border-red-200"></div>
         </div>
         <!-- Footer -->
         <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button id="batalAngsuran" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-sm transition">Batal</button>
            <button id="simpanAngsuran" class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold text-sm transition flex items-center gap-1">
               <span class="material-symbols-outlined text-sm">save</span>
               Simpan Pembayaran
            </button>
         </div>
      </div>
   </div>

   <!-- ===== MODAL EDIT PEMBAYARAN ===== -->
   <div id="modalEditPembayaran" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all">
         <!-- Header -->
         <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
               <span class="material-symbols-outlined text-yellow-600">edit_calendar</span>
               <span>Edit Pembayaran</span>
               <span id="editBadgeNomor" class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full font-semibold"></span>
            </h3>
            <button id="closeModalEditPembayaran" class="text-gray-400 hover:text-gray-700 focus:outline-none">
               <span class="material-symbols-outlined">close</span>
            </button>
         </div>
         <!-- Body -->
         <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="editPaymentId" value="">
            <input type="hidden" id="editPaymentType" value="">
            <input type="hidden" id="editOldJumlah" value="0">

            <!-- Info Total Tagihan -->
            <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1 border border-gray-200">
               <div class="flex justify-between">
                  <span class="text-gray-500">Total Tagihan:</span>
                  <span class="font-semibold text-gray-800">Rp <?= number_format($total, 0, ',', '.') ?></span>
               </div>
               <div class="flex justify-between border-t pt-1 mt-1">
                  <span class="text-gray-500">Maks. Pembayaran Ini:</span>
                  <span class="font-semibold text-blue-700" id="editMaxAllowed">Rp 0</span>
               </div>
            </div>

            <!-- Input Tanggal -->
            <div>
               <label for="editTanggalPembayaran" class="block text-sm font-bold text-gray-700 mb-1">
                  Tanggal Pembayaran <span class="text-red-500">*</span>
               </label>
               <input type="datetime-local" id="editTanggalPembayaran"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-yellow-400" required>
            </div>

            <!-- Input Jumlah -->
            <div>
               <label for="editJumlahPembayaran" class="block text-sm font-bold text-gray-700 mb-1">
                  Jumlah Pembayaran <span class="text-red-500">*</span>
               </label>
               <input type="text" id="editJumlahPembayaran"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-right text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-yellow-400"
                  placeholder="0" autocomplete="off" required>
            </div>

            <!-- Input Keterangan -->
            <div>
               <label for="editKeteranganPembayaran" class="block text-sm font-bold text-gray-700 mb-1">
                  Keterangan <span class="text-gray-400 font-normal">(opsional)</span>
               </label>
               <input type="text" id="editKeteranganPembayaran"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                  placeholder="Contoh: Angsuran ke-2, Pelunasan, Transfer BCA...">
            </div>

            <!-- Preview Sisa -->
            <div id="previewEditSisa" class="bg-blue-50 rounded-lg p-3 text-sm border border-blue-200">
               <div class="flex justify-between items-center">
                  <span class="text-blue-700 font-semibold">Estimasi Sisa Tagihan:</span>
                  <span id="previewEditSisaVal" class="font-bold text-blue-900 text-base">Rp 0</span>
               </div>
            </div>

            <!-- Pesan Error -->
            <div id="editPembayaranError" class="hidden bg-red-50 text-red-600 text-sm rounded p-2 border border-red-200"></div>
         </div>
         <!-- Footer -->
         <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button id="batalEditPembayaran" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-sm transition">Batal</button>
            <button id="simpanEditPembayaran" class="px-5 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white font-semibold text-sm transition flex items-center gap-1 shadow">
               <span class="material-symbols-outlined text-sm">save</span>
               Simpan Perubahan
            </button>
         </div>
      </div>
   </div>

   <script>
      /* ==================================================
         Utilitas Format Angka
      ================================================== */
      function parseNum(val) {
         if (!val) return 0;
         let clean = val.toString().replace(/\./g, '').replace(',', '.');
         let num = parseFloat(clean);
         return isNaN(num) ? 0 : num;
      }

      function formatIDR(n) {
         return n.toLocaleString("id-ID");
      }

      /* ==================================================
         Kalkulasi Form Edit Produk
      ================================================== */
      function calculateRow(row) {
         let qty = parseNum(row.querySelector(".qty")?.value);
         let shrinkage = parseNum(row.querySelector(".qty-shrinkage")?.value);
         let price = parseNum(row.querySelector(".price")?.value);
         let billedQty = Math.max(0, qty - shrinkage);
         let subtotal = billedQty * price / 1000;
         let subEl = row.querySelector(".subtotal");
         if (subEl) subEl.value = formatIDR(subtotal);
         return subtotal;
      }

      const isOriginalLunas = <?= json_encode($is_lunas) ?>;
      const totalAngsuran = <?= json_encode($total_angsuran) ?>;

      function calculateTotal() {
         let total = 0;
         document.querySelectorAll(".order-row").forEach(r => {
            total += calculateRow(r);
         });
         let dp = parseNum(document.getElementById("dpInput").value);
         let remaining = 0;
         if (isOriginalLunas && dp <= 0 && totalAngsuran <= 0) {
            remaining = 0;
         } else {
            remaining = Math.max(total - dp - totalAngsuran, 0);
         }
         document.getElementById("grandTotal").textContent = formatIDR(total);
         document.getElementById("remaining").textContent = formatIDR(remaining);
      }

      document.addEventListener("input", (e) => {
         if (e.target.classList.contains("qty") || e.target.classList.contains("qty-shrinkage") || e.target.classList.contains("price") || e.target.id === "dpInput") {
            let raw = e.target.value.replace(/\./g, '');
            if (raw) e.target.value = formatIDR(parseInt(raw));
            calculateTotal();
         }
      });

      /* ==================================================
         Validasi Stok Saat Input Qty
      ================================================== */
      document.addEventListener("input", (e) => {
         if (e.target.classList.contains("qty")) {
            const row = e.target.closest(".order-row");
            const stock = parseNum(e.target.dataset.stock);
            let qty = parseNum(e.target.value);
            const stockWarning = row.querySelector(".stock-warning");

            if (stock > 0 && qty > stock) {
               e.target.value = formatIDR(stock);
               if (stockWarning) {
                  stockWarning.textContent = "Maksimal stok: " + formatIDR(stock) + " gram";
                  stockWarning.className = "text-red-500 text-xs stock-warning font-semibold";
                  stockWarning.classList.remove("hidden");
               }
            } else if (stockWarning && stock > 0) {
               stockWarning.textContent = "Stok tersedia: " + formatIDR(stock) + " gram";
               stockWarning.className = "text-blue-600 text-xs stock-warning";
               stockWarning.classList.remove("hidden");
            }
            calculateTotal();
         }
      });

      /* ==================================================
         Tambah Produk Baru
      ================================================== */
      document.getElementById("addProductBtn").addEventListener("click", () => {
         let template = `
<div class="grid grid-cols-12 gap-2 items-center order-row">
<input type="hidden" name="item_id[]" value="new">
<div class="col-span-12 md:col-span-3">
<select name="product_id[]" class="productSelect w-full border rounded px-2 py-1">
<option value="">-- Pilih Produk --</option>
<?php foreach ($products as $p): ?>
<option value="<?= $p['id'] ?>" data-stock="<?= $p['quantity'] ?>" data-price="<?= $p['price_weight'] ?? 0 ?>" data-symbol="<?= $p['symbol'] ?>">
<?= htmlspecialchars($p['product_name']) ?> (Sisa: <?= number_format($p['quantity'], 0, ',', '.') ?> <?= htmlspecialchars($p['symbol'] ?? 'g') ?>)
</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-span-4 md:col-span-2">
<input type="text" name="qty[]" data-stock="0" class="w-full border rounded px-2 py-1 qty text-right" placeholder="Qty Kirim..." value="0">
</div>
<div class="col-span-4 md:col-span-2">
<input type="text" name="qty_shrinkage[]" class="w-full border rounded px-2 py-1 qty-shrinkage text-right text-red-600 font-medium" placeholder="Susut..." value="0">
</div>
<div class="col-span-4 md:col-span-2">
<input type="text" name="price[]" class="w-full border rounded px-2 py-1 price text-right" placeholder="Harga/Kg..." value="0">
</div>
<div class="col-span-11 md:col-span-2">
<input type="text" class="w-full border rounded px-2 py-1 subtotal text-right bg-gray-50 font-semibold" readonly>
</div>
<div class="col-span-1 text-center">
<button type="button" class="removeRow text-red-500 hover:text-red-700 font-bold">
<span class="material-symbols-outlined">delete</span>
</button>
</div>
<div class="col-span-12 -mt-1 mb-1">
<small class="text-blue-600 text-xs stock-warning hidden"></small>
</div>
</div>
`;
         document.getElementById("orderItems").insertAdjacentHTML("beforeend", template);
         calculateTotal();
      });

      /* ==================================================
         Hapus Row Baru
      ================================================== */
      document.addEventListener("click", (e) => {
         if (e.target.closest(".removeRow")) {
            e.target.closest(".order-row").remove();
            calculateTotal();
         }
      });

      /* ==================================================
         Hapus Item Lama (kembalikan stok)
      ================================================== */
      let deletedItems = [];
      document.addEventListener("click", (e) => {
         if (e.target.closest(".deleteItem")) {
            let row = e.target.closest(".order-row");
            let itemIdInput = row.querySelector('input[name="item_id[]"]');
            let itemId = itemIdInput ? itemIdInput.value : null;
            if (itemId && itemId !== "new") {
               deletedItems.push(itemId);
               document.getElementById("deleted_items").value = deletedItems.join(",");
            }
            row.remove();
            calculateTotal();
         }
      });

      /* ==================================================
         Set Stock Saat Produk Dipilih
      ================================================== */
      document.addEventListener("change", (e) => {
         if (e.target.classList.contains("productSelect")) {
            let option = e.target.selectedOptions[0];
            let stock = option ? (parseFloat(option.dataset.stock) || 0) : 0;
            let price = option ? (parseFloat(option.dataset.price) || 0) : 0;
            let symbol = option ? (option.dataset.symbol || 'gram') : 'gram';
            let row = e.target.closest(".order-row");
            let qtyEl = row.querySelector(".qty");
            let priceEl = row.querySelector(".price");
            let stockWarning = row.querySelector(".stock-warning");

            if (qtyEl) {
               qtyEl.dataset.stock = stock;
               if (stock <= 0 && option.value !== "") {
                  qtyEl.value = "";
                  qtyEl.setAttribute("disabled", "disabled");
               } else {
                  qtyEl.removeAttribute("disabled");
               }
            }
            if (priceEl && price > 0) {
               priceEl.value = formatIDR(price);
            }

            if (stockWarning) {
               if (option && option.value) {
                  stockWarning.textContent = "Stok tersedia: " + formatIDR(stock) + " " + symbol;
                  stockWarning.className = "text-blue-600 text-xs stock-warning";
                  stockWarning.classList.remove("hidden");
               } else {
                  stockWarning.textContent = "";
                  stockWarning.classList.add("hidden");
               }
            }
            calculateTotal();
         }
      });

      /* ==================================================
         Bersihkan Format & Validasi Saat Submit
      ================================================== */
      document.getElementById("editForm").addEventListener("submit", (e) => {
         let invalid = false;
         document.querySelectorAll(".order-row").forEach(row => {
            const prodSelect = row.querySelector(".productSelect");
            const qtyVal = parseNum(row.querySelector(".qty")?.value);
            if (prodSelect && prodSelect.value === "" && qtyVal > 0) {
               alert("Silakan pilih produk pada baris yang baru ditambahkan.");
               prodSelect.focus();
               invalid = true;
            }
         });
         if (invalid) {
            e.preventDefault();
            return false;
         }

         document.querySelectorAll(".qty,.qty-shrinkage,.price,#dpInput").forEach(input => {
            input.value = parseNum(input.value);
         });
      });

      window.addEventListener("DOMContentLoaded", calculateTotal);

      /* ==================================================
         MODAL ANGSURAN
      ================================================== */
      const totalTagihan = <?= $total ?>;
      const totalDibayarAwal = <?= $total_dibayar ?>;
      let sisaTagihanNow = <?= $sisa_tagihan ?>;

      const modalAngsuran = document.getElementById('modalAngsuran');
      const btnBuka = document.getElementById('btnTambahAngsuran');
      const btnTutup = document.getElementById('closeModalAngsuran');
      const btnBatal = document.getElementById('batalAngsuran');
      const btnSimpan = document.getElementById('simpanAngsuran');
      const inputJumlah = document.getElementById('inputJumlahAngsuran');
      const inputKet = document.getElementById('inputKeteranganAngsuran');
      const previewSisa = document.getElementById('previewSisa');
      const previewSisaVal = document.getElementById('previewSisaVal');
      const angsuranError = document.getElementById('angsuranError');

      function bukaModal() {
         inputJumlah.value = '';
         inputKet.value = '';
         previewSisa.classList.add('hidden');
         angsuranError.classList.add('hidden');
         modalAngsuran.classList.remove('hidden');
         setTimeout(() => inputJumlah.focus(), 100);
      }

      function tutupModal() {
         modalAngsuran.classList.add('hidden');
      }

      if (btnBuka) btnBuka.addEventListener('click', bukaModal);
      if (btnTutup) btnTutup.addEventListener('click', tutupModal);
      if (btnBatal) btnBatal.addEventListener('click', tutupModal);

      // Klik di luar modal
      modalAngsuran.addEventListener('click', (e) => {
         if (e.target === modalAngsuran) tutupModal();
      });

      // Format input jumlah & preview real-time
      inputJumlah.addEventListener('input', () => {
         let raw = inputJumlah.value.replace(/\./g, '').replace(/\D/g, '');
         if (raw) {
            inputJumlah.value = formatIDR(parseInt(raw));
            let nominal = parseInt(raw);
            let sisaSetelah = Math.max(0, sisaTagihanNow - nominal);
            previewSisaVal.textContent = 'Rp ' + formatIDR(sisaSetelah);
            previewSisa.classList.remove('hidden');

            if (nominal > sisaTagihanNow) {
               previewSisa.classList.add('hidden');
               angsuranError.textContent = '⚠ Jumlah melebihi sisa tagihan (Rp ' + formatIDR(sisaTagihanNow) + ').';
               angsuranError.classList.remove('hidden');
            } else {
               angsuranError.classList.add('hidden');
            }
         } else {
            previewSisa.classList.add('hidden');
            angsuranError.classList.add('hidden');
         }
      });

      // Simpan Angsuran via AJAX
      btnSimpan.addEventListener('click', async () => {
         const jumlah = parseNum(inputJumlah.value);
         const keterangan = inputKet.value.trim();

         if (jumlah <= 0) {
            angsuranError.textContent = 'Masukkan jumlah pembayaran yang valid.';
            angsuranError.classList.remove('hidden');
            inputJumlah.focus();
            return;
         }
         if (jumlah > sisaTagihanNow + 0.01) {
            angsuranError.textContent = '⚠ Jumlah melebihi sisa tagihan.';
            angsuranError.classList.remove('hidden');
            return;
         }

         btnSimpan.disabled = true;
         btnSimpan.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">autorenew</span> Menyimpan...';

         try {
            const res = await fetch('angsuran-simpan.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
               },
               body: new URLSearchParams({
                  invoice: '<?= addslashes($invoice) ?>',
                  jumlah: jumlah,
                  keterangan: keterangan
               })
            });
            const json = await res.json();

            if (json.success) {
               tutupModal();
               // Reload halaman agar tabel dan ringkasan terbaru
               window.location.reload();
            } else {
               angsuranError.textContent = json.message || 'Gagal menyimpan.';
               angsuranError.classList.remove('hidden');
               btnSimpan.disabled = false;
               btnSimpan.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan Pembayaran';
            }
         } catch (err) {
            angsuranError.textContent = 'Terjadi kesalahan koneksi.';
            angsuranError.classList.remove('hidden');
            btnSimpan.disabled = false;
            btnSimpan.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan Pembayaran';
         }
      });

      /* ==================================================
         MODAL EDIT PEMBAYARAN
      ================================================== */
      const modalEdit = document.getElementById('modalEditPembayaran');
      const btnCloseEdit = document.getElementById('closeModalEditPembayaran');
      const btnBatalEdit = document.getElementById('batalEditPembayaran');
      const btnSimpanEdit = document.getElementById('simpanEditPembayaran');

      const editPaymentId = document.getElementById('editPaymentId');
      const editPaymentType = document.getElementById('editPaymentType');
      const editOldJumlah = document.getElementById('editOldJumlah');
      const editBadgeNomor = document.getElementById('editBadgeNomor');
      const editMaxAllowed = document.getElementById('editMaxAllowed');
      const editTanggal = document.getElementById('editTanggalPembayaran');
      const editJumlah = document.getElementById('editJumlahPembayaran');
      const editKet = document.getElementById('editKeteranganPembayaran');
      const previewEditSisa = document.getElementById('previewEditSisa');
      const previewEditSisaVal = document.getElementById('previewEditSisaVal');
      const editError = document.getElementById('editPembayaranError');

      let maxAllowedForCurrentEdit = 0;

      function bukaModalEdit(btn) {
         const type = btn.dataset.type;
         const id = btn.dataset.id;
         const nomor = btn.dataset.nomor;
         const tanggal = btn.dataset.tanggal;
         const jumlah = parseFloat(btn.dataset.jumlah) || 0;
         const keterangan = btn.dataset.keterangan || '';

         editPaymentType.value = type;
         editPaymentId.value = id;
         editOldJumlah.value = jumlah;
         editBadgeNomor.textContent = type === 'dp' ? 'DP Awal' : 'Angsuran ke-' + nomor;
         editTanggal.value = tanggal;
         editJumlah.value = formatIDR(jumlah);
         editKet.value = keterangan;
         editError.classList.add('hidden');

         // Maksimal yang boleh dibayarkan = jumlah lama + sisa tagihan saat ini
         maxAllowedForCurrentEdit = jumlah + sisaTagihanNow;
         editMaxAllowed.textContent = 'Rp ' + formatIDR(maxAllowedForCurrentEdit);

         hitungEstimasiSisaEdit();
         modalEdit.classList.remove('hidden');
      }

      function tutupModalEdit() {
         modalEdit.classList.add('hidden');
      }

      function hitungEstimasiSisaEdit() {
         const nominal = parseNum(editJumlah.value);
         const sisaBaru = Math.max(0, maxAllowedForCurrentEdit - nominal);
         previewEditSisaVal.textContent = 'Rp ' + formatIDR(sisaBaru);

         if (nominal > maxAllowedForCurrentEdit + 0.01) {
            editError.textContent = '⚠ Jumlah melebihi batas maksimal (Rp ' + formatIDR(maxAllowedForCurrentEdit) + ').';
            editError.classList.remove('hidden');
         } else {
            editError.classList.add('hidden');
         }
      }

      document.querySelectorAll('.btn-edit-pembayaran').forEach(btn => {
         btn.addEventListener('click', () => bukaModalEdit(btn));
      });

      if (btnCloseEdit) btnCloseEdit.addEventListener('click', tutupModalEdit);
      if (btnBatalEdit) btnBatalEdit.addEventListener('click', tutupModalEdit);
      modalEdit.addEventListener('click', (e) => {
         if (e.target === modalEdit) tutupModalEdit();
      });

      editJumlah.addEventListener('input', () => {
         let raw = editJumlah.value.replace(/\./g, '').replace(/\D/g, '');
         if (raw) {
            editJumlah.value = formatIDR(parseInt(raw));
         }
         hitungEstimasiSisaEdit();
      });

      btnSimpanEdit.addEventListener('click', async () => {
         const type = editPaymentType.value;
         const paymentId = editPaymentId.value;
         const tanggal = editTanggal.value;
         const jumlah = parseNum(editJumlah.value);
         const keterangan = editKet.value.trim();

         if (!tanggal) {
            editError.textContent = 'Tanggal pembayaran wajib diisi.';
            editError.classList.remove('hidden');
            editTanggal.focus();
            return;
         }

         if (jumlah < 0) {
            editError.textContent = 'Jumlah pembayaran tidak valid.';
            editError.classList.remove('hidden');
            editJumlah.focus();
            return;
         }

         if (jumlah > maxAllowedForCurrentEdit + 0.01) {
            editError.textContent = '⚠ Jumlah melebihi batas maksimal (Rp ' + formatIDR(maxAllowedForCurrentEdit) + ').';
            editError.classList.remove('hidden');
            return;
         }

         btnSimpanEdit.disabled = true;
         btnSimpanEdit.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">autorenew</span> Menyimpan...';

         try {
            const res = await fetch('angsuran-update.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
               },
               body: new URLSearchParams({
                  invoice: '<?= addslashes($invoice) ?>',
                  payment_id: paymentId,
                  type: type,
                  tanggal: tanggal,
                  jumlah: jumlah,
                  keterangan: keterangan
               })
            });
            const json = await res.json();

            if (json.success) {
               tutupModalEdit();
               window.location.reload();
            } else {
               editError.textContent = json.message || 'Gagal menyimpan perubahan.';
               editError.classList.remove('hidden');
               btnSimpanEdit.disabled = false;
               btnSimpanEdit.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan Perubahan';
            }
         } catch (err) {
            editError.textContent = 'Terjadi kesalahan koneksi.';
            editError.classList.remove('hidden');
            btnSimpanEdit.disabled = false;
            btnSimpanEdit.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan Perubahan';
         }
      });
   </script>
</body>

</html>