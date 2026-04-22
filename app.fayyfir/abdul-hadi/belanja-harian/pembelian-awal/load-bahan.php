<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// --- Proses tambah bahan baru ---
if (isset($_POST["nama_bahan_baru"]) && !empty(trim($_POST["nama_bahan_baru"]))) {
  $nama_bahan_baru = trim($_POST["nama_bahan_baru"]);
  $stmt = $conn->prepare("INSERT INTO bb_bahan_master (nama_bahan) VALUES (?)");
  $stmt->bind_param("s", $nama_bahan_baru);
  $stmt->execute();
  $bahan_baru_id = $conn->insert_id;
  echo "<script>localStorage.setItem('bahanBaruId', '$bahan_baru_id');</script>";
}

// --- Proses tambah supplier baru ---
if (isset($_POST["nama_supplier_baru"]) && !empty(trim($_POST["nama_supplier_baru"]))) {
  $nama_supplier_baru = trim($_POST["nama_supplier_baru"]);
  $stmt = $conn->prepare("INSERT INTO bb_supplier (nama_supplier) VALUES (?)");
  $stmt->bind_param("s", $nama_supplier_baru);
  $stmt->execute();
  $supplier_baru_id = $conn->insert_id;
  echo "<script>localStorage.setItem('supplierBaruId', '$supplier_baru_id');</script>";
}

// Ambil daftar bahan & supplier
$bahan_result = $conn->query("SELECT id, nama_bahan FROM bb_bahan_master ORDER BY nama_bahan ASC");
$supplier_result = $conn->query("SELECT id, nama_supplier FROM bb_supplier ORDER BY nama_supplier ASC");

// --- Generate Kode Batch Otomatis BCH-YYYYMMDD-XXX ---
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

// --- Proses simpan data pembelian ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["kode_batch"])) {
  $kode_batch_post = $_POST["kode_batch"]; // tetap pakai input dari form jika ada override
  $tanggal_pembelian = $_POST["tanggal_pembelian"];
  $bahan_id = intval($_POST["bahan_id"]);
  $supplier_id = intval($_POST["supplier_id"]);
  $berat = floatval($_POST["berat"]);

  if ($bahan_id && $supplier_id && $berat > 0 && !empty($tanggal_pembelian)) {
    $stmt = $conn->prepare("
      INSERT INTO bb_pembelian_awal 
      (kode_batch, tanggal_pembelian, id_bahan, id_supplier, berat_awal, catatan, status, created_at)
      VALUES (?, ?, ?, ?, ?, ?, 'load', NOW())
    ");
    $stmt->bind_param("ssiids", $kode_batch_post, $tanggal_pembelian, $bahan_id, $supplier_id, $berat, $catatan);
    if ($stmt->execute()) {
      echo "<script>
        alert('✅ Data berhasil disimpan!');
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

$activeMenu = "purchases";
$activeModule = "Input Load Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4 px-6 sm:px-8">
    <a href="index" class="inline-flex items-center text-gray-600 hover:text-gray-800 text-sm transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Kembali
    </a>
      <h2 class="text-2xl font-semibold text-gray-800 mt-4 sm:mt-0">Input Load Bahan</h2>
  </div>
    
  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 p-6 sm:p-8">

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
            value="<?= $kode_batch ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembelian</label>
          <input type="date" name="tanggal_pembelian" required
            value="<?= date('Y-m-d') ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
        </div>
      </div>

      <!-- Row 2 -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bahan</label>
          <select name="bahan_id" id="bahan_id" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
            <option value="">-- Pilih Bahan --</option>
            <?php while ($row = $bahan_result->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_bahan']); ?></option>
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
              <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_supplier']); ?></option>
            <?php endwhile; ?>
            <option value="add_new">Tambah Supplier Baru</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Berat (Kg)</label>
          <input type="number" name="berat" id="berat" step="0.01" min="0" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
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
  const bahanSelect = document.getElementById('bahan_id');
  const supplierSelect = document.getElementById('supplier_id');

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