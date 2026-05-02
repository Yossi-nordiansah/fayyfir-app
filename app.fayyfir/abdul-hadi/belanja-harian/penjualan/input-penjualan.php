<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

/* -----------------------------------------------------
   GENERATE NO INVOICE BARU
----------------------------------------------------- */
$tanggal = date('Ymd');
$query = $conn->query("
    SELECT no_invoice 
    FROM bb_penjualan 
    WHERE no_invoice LIKE 'INV-$tanggal%' 
    ORDER BY no_invoice DESC 
    LIMIT 1
");

if ($query && $query->num_rows > 0) {
    $last_number = intval(substr($query->fetch_assoc()['no_invoice'], -3)) + 1;
    $new_number = str_pad($last_number, 3, '0', STR_PAD_LEFT);
} else {
    $new_number = '001';
}

$no_invoice = "INV-$tanggal-$new_number";

/* -----------------------------------------------------
   TAMBAH BUYER BARU
----------------------------------------------------- */
if (!empty($_POST["nama_buyer_baru"])) {
    $nama_buyer_baru = trim($_POST["nama_buyer_baru"]);
    $stmt = $conn->prepare("INSERT INTO bb_buyer (nama_buyer) VALUES (?)");
    $stmt->bind_param("s", $nama_buyer_baru);
    if ($stmt->execute()) {
        echo "<script>localStorage.setItem('buyerBaruId', '{$conn->insert_id}');</script>";
    }
}

$buyers = $conn->query("SELECT id, nama_buyer FROM bb_buyer ORDER BY nama_buyer ASC");

/* -----------------------------------------------------
   PROSES SIMPAN PENJUALAN
----------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST["id_buyer"])) {

    $id_buyer = intval($_POST['id_buyer']);
    $no_invoice_post = $_POST["no_invoice"];
    $tanggal_jual = $_POST['tanggal_jual'];
    $berat_jual = floatval($_POST['berat_jual']);
    $harga_jual_per_kg = floatval($_POST['harga_jual_per_kg']);

    $hpp = 0; // HPP default jika tidak ada batch
    $total_penjualan = $berat_jual * $harga_jual_per_kg;
    $laba_bersih = $total_penjualan - ($hpp * $berat_jual);

    $stmt = $conn->prepare("
        INSERT INTO bb_penjualan
        (id_buyer, no_invoice, tanggal_jual, berat_jual, harga_jual_per_kg, total_penjualan, laba_bersih)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issdddd",
        $id_buyer,
        $no_invoice_post,
        $tanggal_jual,
        $berat_jual,
        $harga_jual_per_kg,
        $total_penjualan,
        $laba_bersih
    );

    if ($stmt->execute()) {
        echo "<script>alert('Data penjualan berhasil disimpan!'); window.location.href='index';</script>";
        exit();
    } else {
        die("Terjadi kesalahan saat menyimpan data: " . $stmt->error);
    }
}

$activeMenu = "sales";
$activeModule = "Input Penjualan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <div class="max-w-4xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
      <a href="javascript:history.back()" class="inline-flex items-center text-gray-600 hover:text-gray-800 text-sm transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Kembali
      </a>
      <h2 class="text-2xl font-semibold text-gray-800 mt-4 sm:mt-0">Input Penjualan</h2>
    </div>

    <form method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">No. Invoice</label>
          <input type="text" name="no_invoice" readonly value="<?= $no_invoice ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Buyer</label>
          <select name="id_buyer" id="id_buyer" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
            <option value="">-- Pilih Buyer --</option>
            <?php while ($b = $buyers->fetch_assoc()): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_buyer']); ?></option>
            <?php endwhile; ?>
            <option value="add_new">Tambah Buyer Baru</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penjualan</label>
          <input id="tanggal_jual" type="date" name="tanggal_jual" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Berat Jual (kg)</label>
          <input id="berat_jual" type="number" step="0.01" name="berat_jual" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" required>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual per kg (Rp)</label>
          <input id="harga_jual_per_kg" type="number" step="0.01" name="harga_jual_per_kg" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Total Penjualan</label>
          <input id="total_penjualan" type="text" readonly class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Laba Bersih</label>
        <input id="laba_bersih" type="text" readonly class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
      </div>

      <div class="flex justify-end gap-3 pt-4">
        <a href="index" class="px-5 py-2.5 bg-gray-200 rounded-lg">Batal</a>
        <button type="submit" class="px-5 py-2.5 bg-yellow-600 text-white rounded-lg">Simpan Penjualan</button>
      </div>

    </form>
  </div>
</main>

<script>
const beratInput = document.getElementById('berat_jual');
const hargaInput = document.getElementById('harga_jual_per_kg');
const totalOutput = document.getElementById('total_penjualan');
const labaOutput = document.getElementById('laba_bersih');
const buyerSelect = document.getElementById('id_buyer');

function hitungTotal() {
    const berat = parseFloat(beratInput.value) || 0;
    const harga = parseFloat(hargaInput.value) || 0;
    const hpp = 0; // HPP default
    const total = berat * harga;
    const laba = total - (berat * hpp);

    totalOutput.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total);
    labaOutput.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(laba);
}

beratInput.addEventListener('input', hitungTotal);
hargaInput.addEventListener('input', hitungTotal);
window.addEventListener('load', hitungTotal);

buyerSelect.addEventListener('change', function() {
    if (this.value === 'add_new') {
        const namaBaru = prompt("Masukkan nama buyer baru:");
        if (namaBaru) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="nama_buyer_baru" value="${namaBaru}">`;
            document.body.appendChild(form);
            form.submit();
        } else {
            this.value = '';
        }
    }
});
</script>

<?php include "../partials/footer.php"; ?>