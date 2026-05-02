<?php        
session_start();        
if (!isset($_SESSION["user_id"])) {        
  header("Location: login");        
  exit();        
}        
      
require "config.php";        
      
$buyer_id = $_GET['id'] ?? null;        
if (!$buyer_id) {        
  header("Location: transaksi-produk");        
  exit();        
}        
      
// Ambil data buyer        
$stmt = $conn->prepare("SELECT * FROM buyer_products WHERE id = ?");      
$stmt->bind_param("i", $buyer_id);      
$stmt->execute();      
$buyer = $stmt->get_result()->fetch_assoc();      
$stmt->close();      
      
// Ambil history pembelian buyer        
$history = $conn->query("      
  SELECT       
    s.invoice_number,      
    s.buyer_id,      
    s.dp,      
    s.qty,
    s.status,      
    MAX(s.selling_date) AS selling_date,       
    SUM(s.total_selling) AS total_selling,      
    GROUP_CONCAT(ps.product_name SEPARATOR ', ') AS product_list,      
    GROUP_CONCAT(CONCAT(p.fix_price, ' ', u.symbol) SEPARATOR ', ') AS price_list      
  FROM selling_products s      
  LEFT JOIN productions p ON s.product_id = p.id      
  LEFT JOIN product_stocks ps ON p.product_id = ps.id
  LEFT JOIN units u ON p.unit_id = u.id      
  WHERE s.buyer_id = {$buyer_id}      
  GROUP BY s.invoice_number, s.buyer_id      
  ORDER BY selling_date DESC      
");      
      
// Ambil daftar produk produksi untuk form pemesanan
$productions = $conn->query("
SELECT 
    ps.id AS stock_id,
    ps.product_name,
    ps.quantity AS stok_tersisa,
    p.price_weight,
    u.symbol,
    MAX(p.id) AS last_production_id, 
    MAX(p.production_date) AS last_production_date
FROM product_stocks ps
LEFT JOIN productions p 
    ON p.product_id = ps.id 
   AND p.status IN ('Selesai','Terhitung','Terjual')
LEFT JOIN units u ON ps.unit_id = u.id
LEFT JOIN selling_products sp ON sp.product_id = p.id
WHERE ps.quantity > 0
GROUP BY ps.id, ps.product_name, ps.quantity, p.price_weight, u.symbol
ORDER BY ps.product_name ASC;
");
?>
      
<!DOCTYPE html>        
<html lang="id">        
<head>        
  <meta charset="UTF-8" />        
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />        
  <title>Rincian Transaksi</title>        
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />        
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">        
</head>        
<body class="bg-gray-100 text-gray-800 min-h-screen">        
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">        
    <div class="flex justify-between items-center">        
      <a href="transaksi-produk" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">        
        <span class="material-symbols-outlined text-base">chevron_left</span>        
        <span class="hidden lg:inline">Kembali</span>        
      </a>        
      <h1 class="text-lg font-semibold">Rincian Transaksi</h1>        
    </div>        
  </header>        
      
  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">            
      
    <!-- Biodata Buyer -->        
    <section class="bg-white p-6 rounded-lg shadow">        
      <h2 class="text-lg font-semibold mb-4">Biodata Buyer</h2>        
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
        <div class="grid grid-cols-4 md:grid-cols-5">
          <span class="font-semibold">Nama</span>
          <span class="col-span-2">: <?= htmlspecialchars($buyer['name']) ?></span>
        </div>
        <div class="grid grid-cols-4 md:grid-cols-5">
          <span class="font-semibold">Kontak</span>
          <span class="col-span-2">: <?= htmlspecialchars($buyer['contact']) ?></span>
        </div>
        <div class="grid grid-cols-4 md:grid-cols-5">
          <span class="font-semibold">Alamat</span>
          <span class="col-span-2">: <?= htmlspecialchars($buyer['address']) ?></span>
        </div>
        <div class="grid grid-cols-4 md:grid-cols-5">
          <span class="font-semibold">Terdaftar</span>
          <span class="col-span-2">: <?= date("d M Y", strtotime($buyer['created_at'])) ?></span>
        </div>
      </div>
      
      <!-- Tombol Aksi -->
      <div class="mt-6 flex justify-end space-x-3">
        <a href="buyer-edit?id=<?= htmlspecialchars($buyer['id']) ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit</a>
        <a href="buyer-hapus?id=<?= htmlspecialchars($buyer['id']) ?>" onclick="return confirm('Yakin ingin menghapus data buyer?\nMenghapus data buyer berarti turut menghapus semua data transaksinya.')" 
           class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm" 
           title="Hapus">Hapus</a>
      </div>
    </section>        
      
    <!-- Form Pemesanan -->        
    <section class="bg-white p-6 rounded-lg shadow text-sm md:text-md">        
      <h2 class="text-lg font-semibold mb-4">Form Pemesanan</h2>        
      <form id="orderForm" method="post" action="transaksi-simpan.php">        
        <input type="hidden" name="buyer_id" value="<?= $buyer_id ?>">        
        <div id="orderItems" class="space-y-4">        
          <div class="grid grid-cols-12 gap-2 items-center order-row">        
            <div class="col-span-12 md:col-span-4">        
              <select name="product_id[]" class="w-full border rounded px-2 py-1">        
                <option value="">-- Pilih Produk --</option>        
                <?php while($p = $productions->fetch_assoc()): ?>        
                  <option value="<?= $p['stock_id'] ?>" 
                          data-price="<?= $p['price_weight'] ?>" 
                          data-symbol="<?= $p['symbol'] ?>" 
                          data-stock="<?= $p['stok_tersisa'] ?>">        
                    <?= $p['product_name'] ?>        
                  </option>        
                <?php endwhile; ?>        
              </select>        
            </div>        
            <div class="col-span-3 md:col-span-2">
              <input type="text" name="qty[]" step="0.01" class="w-full border rounded px-2 py-1 qty" placeholder="Qty...">
            </div>        
            <div class="col-span-4 md:col-span-2"><input type="text" name="price[]" step="0.01" class="w-full border rounded px-2 py-1 price" placeholder="Harga/Kg..."></div>        
            <div class="col-span-4 md:col-span-3"><input type="text" class="w-full border rounded px-2 py-1 subtotal" readonly></div>        
            <div class="col-span-1 text-center">        
              <button type="button" class="remove-row text-red-500">✕</button>        
            </div>
            <div class="col-span-12">
              <small class="text-red-500 text-xs stock-warning hidden"></small>
            </div>
          </div>        
        </div>        
        <div class="flex justify-between items-start mt-4">        
          <button type="button" id="addRow" class="bg-gray-800 text-white px-4 py-2 rounded">+ Tambah</button>
          <div class="w-50">      
            <div class="text-right font-semibold">Total: <span id="grandTotal">0</span></div>      
            <div class="text-right mt-2">      
              <label class="text-sm font-semibold mr-2">DP:</label>      
              <input id="dpInput" name="dpInput[]" type="text" class="border text-right rounded px-2 py-1 w-40" step="0.01" value="0">      
            </div>      
            <div class="text-right font-semibold mt-2">Remaining: <span id="remaining">0</span></div>      
          </div>      
        </div>      
        <div class="mt-4 text-right">        
          <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded">Simpan Pesanan</button>        
        </div>        
      </form>        
    </section>        
      
    <!-- History Pembelian -->        
    <section class="bg-white p-6 rounded-lg shadow">        
      <h2 class="text-lg font-semibold mb-4">History Pembelian</h2>        
      <div class="overflow-x-auto">        
        <table class="min-w-full text-sm divide-y divide-gray-200">        
          <thead class="bg-gray-800 text-yellow-400">        
            <tr>        
              <th class="px-4 py-2 text-center whitespace-nowrap">Tanggal</th>        
              <th class="px-4 py-2 text-center whitespace-nowrap">Invoice</th>      
              <th class="px-4 py-2 text-center whitespace-nowrap">Qty (Kg)</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Total</th>        
              <th class="px-4 py-2 text-center whitespace-nowrap">DP</th>      
              <th class="px-4 py-2 text-center whitespace-nowrap">Remaining</th>      
              <th class="px-4 py-2 text-center whitespace-nowrap">Status</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Aksi</th>
            </tr>        
          </thead>        
          <tbody class="divide-y divide-gray-200">        
            <?php if ($history->num_rows > 0): ?>        
              <?php while($h = $history->fetch_assoc()): ?>        
                <?php
                  // Remaining = total - dp      
                  $qty = $h['qty'] / 1000;
                  $remaining = $h['total_selling'] - $h['dp']; 
                  // Tambahkan class merah kalau status DP  
                  $rowClass = (strtolower($h['status']) === 'dp') ? 'text-red-600' : '';
                ?>        
                <tr class="<?= $rowClass ?>">        
                  <td class="px-4 py-2 whitespace-nowrap"><?= date("d M Y", strtotime($h['selling_date'])) ?></td>        
                  <td class="px-4 py-2 whitespace-nowrap"><?= htmlspecialchars($h['invoice_number']) ?></td>        
                  <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($qty,2,',','.') ?></td>
                  <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($h['total_selling'],0,',','.') ?></td>        
                  <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($h['dp'],0,',','.') ?></td>        
                  <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($remaining,0,',','.') ?></td>        
                  <td class="px-4 py-2 text-center whitespace-nowrap">        
                    <span class="px-2 py-1 rounded text-xs <?= $h['status']=='Lunas' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">        
                      <?= htmlspecialchars($h['status']) ?>        
                    </span>        
                  </td>
                  <td class="px-4 py-2 text-center whitespace-nowrap">
                    <div class="flex justify-between items-center gap-2">
                      <button 
                        class="<?= $h['status']=='Lunas' ? 'text-blue-500 hover:text-blue-700' : 'text-red-600 hover:text-red-800' ?> view-invoice" 
                        data-invoice="<?= htmlspecialchars($h['invoice_number']) ?>"
                      >
                        <span class="material-symbols-outlined">visibility</span>
                      </button>
                      <a href="transaksi-invoice.php?invoice=<?= urlencode($h['invoice_number']) ?>" class="text-green-600 hover:text-green-800" target="_blank">
                        <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                      </a>
                    </div>
                  </td>
                </tr>        
              <?php endwhile; ?>        
            <?php else: ?>        
              <tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada pembelian.</td></tr>        
            <?php endif; ?>        
          </tbody>      
        </table>        
      </div>        
    </section>      
    
    <!-- Modal Detail Transaksi -->
    <div id="invoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
      <div class="bg-white w-full max-w-2xl rounded-lg shadow-lg overflow-hidden py-2">
        <div class="flex justify-between items-center px-4 py-2 border-b">
          <h3 class="text-lg font-semibold">Detail Transaksi</h3>
          <button id="closeModal" class="text-gray-500 hover:text-gray-800">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="p-4 space-y-3 text-sm" id="invoiceContent">
          <!-- konten detail transaksi akan dimuat via AJAX -->
          <p class="text-gray-500">Memuat data...</p>
        </div>
        <div class="px-4 py-4 border-t text-right">
          <button id="closeModalFooter" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">Tutup</button>
        </div>
      </div>
    </div>
  </main>        

<!-- Modal Pelunasan -->
<div id="pelunasanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-lg shadow-lg w-96 p-6">
    <h2 class="text-lg font-semibold mb-4">Pelunasan</h2>
    <label class="block text-sm font-medium mb-1">Jumlah Pelunasan</label>
    <input type="text" id="pelunasanInput" class="w-full border px-3 py-2 rounded-md mb-4" placeholder="Masukkan jumlah">

    <div class="flex justify-end gap-2">
      <button id="closePelunasan" class="px-4 py-2 rounded-md bg-gray-300 hover:bg-gray-400">Batal</button>
      <button id="simpanPelunasan" class="px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">Simpan</button>
    </div>
  </div>
</div>
<script>
function parseNum(val) {
  if (!val) return 0;
  let clean = val.toString().replace(/\./g, '').replace(',', '.');
  let num = parseFloat(clean);
  return isNaN(num) ? 0 : num;
}

function formatIDR(n) {
  return n.toLocaleString("id-ID");
}

function calculateRow(row) {
  let qty = parseNum(row.querySelector(".qty")?.value);
  let price = parseNum(row.querySelector(".price")?.value);
  let subtotal = qty * price / 1000;
  let subEl = row.querySelector(".subtotal");
  if (subEl) subEl.value = formatIDR(subtotal);
  return subtotal;
}

function getDP() {
  const dpInput = document.getElementById("dpInput");
  return dpInput ? parseNum(dpInput.value) : 0;
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = formatIDR(value);
}

function calculateTotal() {
  let total = 0;
  document.querySelectorAll(".order-row").forEach(r => {
    total += calculateRow(r);
  });

  const dp = getDP();
  const remaining = Math.max(total - dp, 0);

  setText("grandTotal", total);
  setText("remaining", remaining);
}

// ===================== Event Listeners ======================
document.addEventListener("change", (e) => {
  if (e.target.matches('select[name="product_id[]"]')) {
    const opt = e.target.selectedOptions[0];
    const price = opt ? parseFloat(opt.getAttribute("data-price")) : 0;
    const row = e.target.closest(".order-row");
    const priceInput = row.querySelector(".price");
    if (priceInput) priceInput.value = formatIDR(price);
    calculateTotal();
  }
});

document.addEventListener("input", (e) => {
  if (e.target.classList.contains("price") || e.target.classList.contains("qty")) {
    let raw = e.target.value.replace(/\./g, '');
    if (raw) e.target.value = formatIDR(parseInt(raw));
  }
  if (e.target.classList.contains("qty") || 
      e.target.classList.contains("price") || 
      e.target.id === "dpInput") {
    calculateTotal();
  }
});

document.getElementById("dpInput").addEventListener("input", (e) => {
  let raw = e.target.value.replace(/\./g, '');
  if (raw) e.target.value = formatIDR(parseInt(raw));
  calculateTotal();
});

document.getElementById("addRow").addEventListener("click", () => {
  const container = document.getElementById("orderItems");
  const firstRow = container.querySelector(".order-row");
  const clone = firstRow.cloneNode(true);

  // reset semua input
  clone.querySelectorAll("input").forEach(i => i.value = "");
  
  // reset dropdown produk
  const sel = clone.querySelector('select[name="product_id[]"]');
  if (sel) sel.selectedIndex = 0;

  // pastikan stock-warning hidden
  const stockWarning = clone.querySelector(".stock-warning");
  if (stockWarning) {
    stockWarning.classList.add("hidden");
    stockWarning.textContent = "";
  }

  container.appendChild(clone);
  clone.scrollIntoView({ behavior: "smooth", block: "center" });
});

document.addEventListener("click", (e) => {
  if (e.target.classList.contains("remove-row")) {
    const rows = document.querySelectorAll(".order-row");
    const row = e.target.closest(".order-row");

    // pastikan stock-warning ikut hidden sebelum hapus/reset
    const stockWarning = row.querySelector(".stock-warning");
    if (stockWarning) {
      stockWarning.classList.add("hidden");
      stockWarning.textContent = "";
    }

    if (rows.length > 1) {
      row.remove();
    } else {
      row.querySelectorAll("input").forEach(i => i.value = "");
      const sel = row.querySelector('select[name="product_id[]"]');
      if (sel) sel.selectedIndex = 0;
    }
    calculateTotal();
  }
});

window.addEventListener("DOMContentLoaded", calculateTotal);

// cek stok & cegah produk duplikat saat pilih produk
document.addEventListener("change", (e) => {
  if (e.target.matches('select[name="product_id[]"]')) {
    const opt = e.target.selectedOptions[0];
    const row = e.target.closest(".order-row");
    const priceInput = row.querySelector(".price");
    const qtyInput = row.querySelector(".qty");
    const stockWarning = row.querySelector(".stock-warning");

    const currentValue = opt ? opt.value : "";

    // 🚫 Cek produk duplikat
    if (currentValue) {
      const allSelects = document.querySelectorAll('select[name="product_id[]"]');
      let duplicate = false;
      allSelects.forEach(sel => {
        if (sel !== e.target && sel.value === currentValue) {
          duplicate = true;
        }
      });

      if (duplicate) {
        stockWarning.textContent = "❌ Produk ini sudah dipilih, silakan pilih yang lain.";
        stockWarning.classList.remove("hidden");
        e.target.value = ""; // reset
        if (priceInput) priceInput.value = "";
        if (qtyInput) qtyInput.value = "";
        calculateTotal();
        return;
      }
    }

    // ambil produk sebelumnya dari atribut custom
    const lastValue = e.target.getAttribute("data-last-value");

    // jika produk berubah, qty dikosongkan
    if (lastValue !== currentValue && qtyInput) {
      qtyInput.value = "";
    }

    // update nilai terakhir
    e.target.setAttribute("data-last-value", currentValue);

    if (opt && currentValue) {
      // kalau ada produk dipilih
      const price = parseFloat(opt.getAttribute("data-price")) || 0;
      if (priceInput) priceInput.value = formatIDR(price);

      // tampilkan info stok
      const stock = parseFloat(opt.getAttribute("data-stock")) || 0;
      if (stock > 0) {
        stockWarning.textContent = "Stok tersedia: " + stock.toLocaleString("id-ID") + " gram";
        qtyInput.removeAttribute("disabled"); // qty bisa diisi
      } else {
        stockWarning.textContent = "tok kosong, tidak bisa dipesan!";
        qtyInput.value = "";
        qtyInput.setAttribute("disabled", "disabled"); // qty tidak bisa diisi
      }
      stockWarning.classList.remove("hidden");
    } else {
      if (priceInput) priceInput.value = "";
      if (qtyInput) qtyInput.value = "";
      stockWarning.textContent = "";
      stockWarning.classList.add("hidden");
    }

    calculateTotal();
  }
});

// validasi qty terhadap stok
document.addEventListener("input", (e) => {
  if (e.target.classList.contains("qty")) {
    const row = e.target.closest(".order-row");
    const sel = row.querySelector('select[name="product_id[]"]');
    const stockWarning = row.querySelector(".stock-warning");

    const stock = sel && sel.selectedOptions[0] 
                  ? parseFloat(sel.selectedOptions[0].getAttribute("data-stock")) || 0 
                  : 0;
    let qty = parseNum(e.target.value);

    if (qty > stock) {
      e.target.value = stock.toLocaleString("id-ID", { minimumFractionDigits: 0, maximumFractionDigits: 2 });

      if (stockWarning) {
        stockWarning.textContent = "Maksimal stok: " + stock.toLocaleString("id-ID") + " Kg";
        stockWarning.classList.remove("hidden");
      }
    }
    calculateTotal();
  }
});

document.querySelector("form").addEventListener("submit", () => {
  document.querySelectorAll(".price, .qty, #dpInput").forEach(input => {
    input.value = parseNum(input.value);
  });
});

// ============ Pelunasan Modal ============
function unformatIDR(val) {
  return parseInt(val.replace(/\./g, "")) || 0;
}

document.getElementById("pelunasanInput").addEventListener("input", function() {
  let raw = this.value.replace(/\D/g, "");
  if (raw) this.value = formatIDR(parseInt(raw));
});

// Modals Popup Rincian  
document.addEventListener("click", async (e) => {  
  if (e.target.closest(".view-invoice")) {  
    const btn = e.target.closest(".view-invoice");  
    const invoice = btn.dataset.invoice;  

    const modal = document.getElementById("invoiceModal");  
    modal.classList.remove("hidden");  
    modal.classList.add("flex");  

    const content = document.getElementById("invoiceContent");  
    content.innerHTML = "<p class='text-gray-500'>Memuat data...</p>";  

    try {  
      const res = await fetch("transaksi-detail.php?invoice=" + encodeURIComponent(invoice));  
      const html = await res.text();  
      content.innerHTML = html;  

      const statusEl = document.getElementById("invoiceStatus");  
      const invoiceEl = document.getElementById("invoiceNumber");  
      const footer = modal.querySelector(".border-t.text-right");  

      const oldBtn = footer.querySelector("#markAsLunas");
      if (oldBtn) oldBtn.remove();

      if (statusEl && statusEl.value === "dp") {
        const btnLunas = document.createElement("button");
        btnLunas.id = "markAsLunas";
        btnLunas.className = "bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm mr-4";
        btnLunas.textContent = "Lunas";
      
        btnLunas.addEventListener("click", () => {
          // 1️⃣ Tutup modal invoice dulu
          modal.classList.add("hidden");
          modal.classList.remove("flex");
          // 2️⃣ Buka modal pelunasan
          document.getElementById("pelunasanModal").classList.remove("hidden");
          document.getElementById("pelunasanInput").value = "";
          document.getElementById("pelunasanInput").focus();
        });
      
        footer.insertBefore(btnLunas, footer.querySelector("#closeModalFooter"));
      }
      
      document.getElementById("closePelunasan").addEventListener("click", () => {
        document.getElementById("pelunasanModal").classList.add("hidden");
      });
      
      document.getElementById("simpanPelunasan").addEventListener("click", async () => {
        const nilaiPelunasan = unformatIDR(document.getElementById("pelunasanInput").value);
        if (!nilaiPelunasan || nilaiPelunasan <= 0) {
          alert("Masukkan jumlah pelunasan yang valid.");
          return;
        }
      
        try {
          const res = await fetch("update-status.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "invoice=" + encodeURIComponent(invoiceEl.value) +
                  "&pelunasan=" + encodeURIComponent(nilaiPelunasan)
          });
          const result = await res.text();
          alert(result);
          location.reload();
        } catch (err) {
          alert("Gagal melakukan pelunasan.");
        }
      });
    } catch (err) {  
      content.innerHTML = "<p class='text-red-500'>Gagal memuat data.</p>";  
    }  
  }  

  if (e.target.closest("#closeModal") || e.target.closest("#closeModalFooter")) {  
    const modal = document.getElementById("invoiceModal");  
    modal.classList.add("hidden");  
    modal.classList.remove("flex");  
  }  
});
</script>
</body>        
</html>