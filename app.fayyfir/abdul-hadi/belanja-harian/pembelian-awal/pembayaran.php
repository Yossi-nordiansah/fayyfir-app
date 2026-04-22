<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID
$id_load_bahan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data existing
$data = null;
if ($id_load_bahan > 0) {
  $stmt = $conn->prepare("SELECT * FROM bb_pembelian_awal WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $id_load_bahan);
  $stmt->execute();
  $data = $stmt->get_result()->fetch_assoc();

  if (!$data) {
    die("Data tidak ditemukan.");
  }
}

// ==========================
// TAMBAH BAHAN BARU
// ==========================
if (isset($_POST["nama_bahan_baru"])) {
  $nama = trim($_POST["nama_bahan_baru"]);

  if ($nama !== "") {
    $stmt = $conn->prepare("INSERT INTO bb_bahan_master (nama_bahan) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
  }

  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

// ==========================
// TAMBAH SUPPLIER BARU
// ==========================
if (isset($_POST["nama_supplier_baru"])) {
  $nama = trim($_POST["nama_supplier_baru"]);

  if ($nama !== "") {
    $stmt = $conn->prepare("INSERT INTO bb_supplier (nama_supplier) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
  }

  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

// ==========================
// GENERATE KODE BATCH
// ==========================
$tanggal = date('Ymd');
$query = $conn->query("
  SELECT kode_batch 
  FROM bb_pembelian_awal 
  WHERE kode_batch LIKE 'BCH-$tanggal%' 
  ORDER BY kode_batch DESC 
  LIMIT 1
");

if ($query->num_rows > 0) {
  $last_id = $query->fetch_assoc()['kode_batch'];
  $last_number = intval(substr($last_id, -3));
  $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
} else {
  $new_number = '001';
}

$kode_batch = "BCH-$tanggal-$new_number";

// ==========================
// SIMPAN PEMBELIAN
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["kode_batch"])) {

  $tanggal_pembelian = $_POST["tanggal_pembelian"];
  $bahan_id = intval($_POST["bahan_id"]);
  $supplier_id = intval($_POST["supplier_id"]);
  $berat = floatval($_POST["berat"]);
  $harga_kg = floatval($_POST["harga_kg"]);
  $catatan = trim($_POST["catatan"]);
  $total = $berat * $harga_kg;

  if ($bahan_id && $supplier_id && $berat > 0 && $harga_kg > 0 && !empty($tanggal_pembelian)) {

    $stmt = $conn->prepare("
      UPDATE bb_pembelian_awal SET tanggal_pembelian = ?, id_bahan = ?, id_supplier = ?, berat_awal = ?, harga_per_kg = ?, total_modal = ?, catatan = ?, status = 'uang_terbayar', updated_at = NOW()
      WHERE id = ?
    ");

    $stmt->bind_param(
      "siidddsi", $tanggal_pembelian, $bahan_id, $supplier_id, $berat, $harga_kg, $total, $catatan, $id_load_bahan
    );

    if ($stmt->execute()) {
      echo "<script>
        alert('✅ Data pembelian berhasil disimpan!');
        window.location.href='index';
      </script>";
      exit();
    } else {
      $error = 'Gagal menyimpan data: ' . $conn->error;
    }

  } else {
    $error = "Pastikan semua data terisi dengan benar!";
  }
}

// ==========================
// LOAD DATA DROPDOWN
// ==========================
$bahan_result = $conn->query("SELECT id, nama_bahan FROM bb_bahan_master ORDER BY nama_bahan ASC");
$supplier_result = $conn->query("SELECT id, nama_supplier FROM bb_supplier ORDER BY nama_supplier ASC");

$activeMenu = "purchases";
$activeModule = "Pembayaran Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
      Pembayaran Bahan
    </h1>

    <?php if (!empty($error)): ?>
      <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        <strong>❌ <?= htmlspecialchars($error) ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" id="formPembelian" class="space-y-6">

      <!-- Row 1 -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Kode Batch</label>
          <input type="text" name="kode_batch" readonly
            value="<?= $data['kode_batch'] ?? $kode_batch ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembelian</label>
          <input type="date" name="tanggal_pembelian" required
            value="<?= $data['tanggal_pembelian'] ?? date('Y-m-d') ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
        </div>
      </div>

      <!-- Row 2 -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bahan</label>
          <select name="bahan_id" id="bahan_id" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
            <option value="">-- Pilih Bahan --</option>
            <?php while ($row = $bahan_result->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>"
  <?= ($data && $data['id_bahan'] == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nama_bahan']); ?></option>
            <?php endwhile; ?>
            <option value="add_new">Tambah Bahan Baru</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Supplier</label>
          <select name="supplier_id" id="supplier_id" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
            <option value="">-- Pilih Supplier --</option>
            <?php while ($row = $supplier_result->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>"
  <?= ($data && $data['id_supplier'] == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nama_supplier']); ?></option>
            <?php endwhile; ?>
            <option value="add_new">Tambah Supplier Baru</option>
          </select>
        </div>
      </div>

      <!-- Row 3 -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Berat (Kg)</label>
          <input type="number" name="berat" id="berat" step="0.01" min="0" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5" value="<?= $data['berat_awal'] ?? '' ?>">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Harga per Kg (Rp)</label>
          <input type="number" name="harga_kg" id="harga_kg" step="0.01" min="0" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5" value="<?= $data['harga_per_kg'] ?? '' ?>">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Total (Rp)</label>
          <input type="text" id="total" readonly
            class="w-full border border-gray-200 rounded-xl bg-gray-100 p-2.5 font-medium">
        </div>
      </div>

      <!-- Catatan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
        <textarea name="catatan" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5"></textarea>
      </div>

      <!-- Tombol Aksi -->  
      <div class="flex flex-col sm:flex-row justify-between gap-3">  
        <button type="submit"  
          class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition">  
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"  
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">  
            <path stroke-linecap="round" stroke-linejoin="round"  
              d="M5 13l4 4L19 7" />  
          </svg>  
          Simpan Pembelian  
        </button>  
          
        <a href="index"  
          class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">  
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"  
            stroke-width="2" stroke="currentColor">  
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />  
          </svg>  
          Batal  
        </a>  
      </div>
    </form>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const berat = document.getElementById('berat');
  const harga = document.getElementById('harga_kg');
  const total = document.getElementById('total');
  const bahanSelect = document.getElementById('bahan_id');
  const supplierSelect = document.getElementById('supplier_id');

  const hitungTotal = () => {
    const b = parseFloat(berat.value) || 0;
    const h = parseFloat(harga.value) || 0;
    total.value = b && h ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(b * h) : '';
  };
  berat.addEventListener('input', hitungTotal);
  harga.addEventListener('input', hitungTotal);

  bahanSelect.addEventListener('change', function() {
    if (this.value === 'add_new') {
      const namaBaru = prompt("Masukkan nama bahan baru:");
      if (namaBaru) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="nama_bahan_baru" value="${namaBaru}">`;
        document.body.appendChild(form);
        form.submit();
      } else {
        this.value = '';
      }
    }
  });

  supplierSelect.addEventListener('change', function() {
    if (this.value === 'add_new') {
      const namaBaru = prompt("Masukkan nama supplier baru:");
      if (namaBaru) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="nama_supplier_baru" value="${namaBaru}">`;
        document.body.appendChild(form);
        form.submit();
      } else {
        this.value = '';
      }
    }
  });
});
</script>

<?php include "../partials/footer.php"; ?>