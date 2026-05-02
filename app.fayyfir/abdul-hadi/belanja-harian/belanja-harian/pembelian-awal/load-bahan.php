<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil daftar bahan & supplier
$bahan_result = $conn->query("SELECT MAX(id) as id, nama_bahan, MAX(satuan) as satuan FROM bb_bahan_master WHERE deleted_at IS NULL GROUP BY nama_bahan ORDER BY nama_bahan ASC");
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
  $berat_raw = str_replace('.', '', $_POST["berat"]);
  $berat = floatval($berat_raw);
  $harga_raw = str_replace('.', '', $_POST["harga_per_kg"]);
  $harga_per_kg = floatval($harga_raw);
  $catatan = isset($_POST["catatan"]) ? trim($_POST["catatan"]) : '';

  if ($bahan_id && $supplier_id && $berat > 0 && $harga_per_kg > 0 && !empty($tanggal_pembelian)) {
    $total_modal = $berat * $harga_per_kg;
    $status_pembayaran = $_POST["status_pembayaran"];
    $nominal_raw = str_replace('.', '', $_POST["nominal_bayar"]);
    $nominal_bayar = floatval($nominal_raw);

    $stmt = $conn->prepare("
      INSERT INTO bb_pembelian_awal 
      (kode_batch, tanggal_pembelian, id_bahan, id_supplier, berat_awal, harga_per_kg, total_modal, catatan, status, status_pembayaran, nominal_bayar, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uang_terbayar', ?, ?, NOW())
    ");
    $stmt->bind_param("ssiidddssd", $kode_batch_post, $tanggal_pembelian, $bahan_id, $supplier_id, $berat, $harga_per_kg, $total_modal, $catatan, $status_pembayaran, $nominal_bayar);
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
      <div class="grid grid-cols-1 sm:grid-cols-5 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bahan</label>
          <select name="bahan_id" id="bahan_id" required
            class="w-full border border-gray-300 rounded-xl px-2 py-2.5 text-sm">
            <option value="">--Pilih Bahan--</option>
            <?php while ($row = $bahan_result->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>" data-satuan="<?= htmlspecialchars($row['satuan']) ?>"><?= htmlspecialchars($row['nama_bahan']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
 
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Supplier</label>
          <select name="supplier_id" id="supplier_id" required
            class="w-full border border-gray-300 rounded-xl px-2 py-2.5 text-sm">
            <option value="">--Pilih Supplier--</option>
            <?php while ($row = $supplier_result->fetch_assoc()): ?>  
              <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_supplier']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Berat (<span class="unit-label">Kg</span>)</label>
          <input type="text" name="berat" id="berat" required
            class="format-number w-full border border-gray-300 rounded-xl px-4 py-2.5" placeholder="0">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli (Rp/<span class="unit-label">Kg</span>)</label>
          <input type="text" name="harga_per_kg" id="harga_per_kg" required
            class="format-number w-full border border-gray-300 rounded-xl px-4 py-2.5" placeholder="0">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Total Harga (Rp)</label>
          <input type="text" id="total_harga_display" readonly
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50 font-bold text-gray-800" placeholder="0">
        </div>
      </div>

      <!-- Pembayaran -->
      <div class="p-5 bg-blue-50 rounded-2xl border border-blue-100 space-y-4">
        <label class="block text-sm font-bold text-blue-800 uppercase tracking-wider">Status Pembayaran</label>
        <div class="flex flex-wrap gap-6">
          <label class="flex items-center gap-2 cursor-pointer group">
            <input type="radio" name="status_pembayaran" value="belum_dibayar" checked class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition">Belum Dibayar</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer group">
            <input type="radio" name="status_pembayaran" value="dp" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition">DP (Uang Muka)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer group">
            <input type="radio" name="status_pembayaran" value="lunas" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition">Lunas</span>
          </label>
        </div>

        <div id="nominal_bayar_wrapper" class="max-w-xs mt-3">
            <label class="block text-xs font-semibold text-blue-600 mb-1">Nominal yang Dibayarkan (Rp)</label>
            <input type="text" name="nominal_bayar" id="nominal_bayar" value="0" 
                class="format-number w-full border border-blue-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none transition">
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
  const beratInput = document.getElementById('berat');
  const hargaInput = document.getElementById('harga_per_kg');
  const totalDisplay = document.getElementById('total_harga_display');
  const nominalInput = document.getElementById('nominal_bayar');
  const statusRadios = document.querySelectorAll('input[name="status_pembayaran"]');

  function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function unformatNumber(str) {
    return str.toString().replace(/\./g, "");
  }

  function calculateTotal() {
    const berat = parseFloat(unformatNumber(beratInput.value)) || 0;
    const harga = parseFloat(unformatNumber(hargaInput.value)) || 0;
    const total = berat * harga;
    totalDisplay.value = formatNumber(Math.floor(total));
    return total;
  }

  function syncNominalLunas() {
    const activeRadio = document.querySelector('input[name="status_pembayaran"]:checked');
    if (activeRadio && activeRadio.value === 'lunas') {
      const total = calculateTotal();
      nominalInput.value = formatNumber(Math.floor(total));
    }
  }

  // Global listener for thousand separators
  document.addEventListener('input', function(e) {
    if (e.target.classList.contains('format-number')) {
      let val = unformatNumber(e.target.value);
      if (!isNaN(val) && val !== "") {
        e.target.value = formatNumber(val);
      }
      
      if (e.target === beratInput || e.target === hargaInput) {
        calculateTotal();
        syncNominalLunas();
      }
    }
  });

  function updateUnit() {
    const selectedOption = bahanSelect.options[bahanSelect.selectedIndex];
    const unit = selectedOption.getAttribute('data-satuan') || 'Kg';
    document.querySelectorAll('.unit-label').forEach(el => {
      el.textContent = unit;
    });
  }

  bahanSelect.addEventListener('change', updateUnit);

  statusRadios.forEach(radio => {
      radio.addEventListener('change', () => {
          if (radio.value === 'lunas') {
              const total = calculateTotal();
              nominalInput.value = formatNumber(Math.floor(total));
              nominalInput.readOnly = true;
              nominalInput.classList.add('bg-gray-50');
          } else if (radio.value === 'belum_dibayar') {
              nominalInput.value = 0;
              nominalInput.readOnly = true;
              nominalInput.classList.add('bg-gray-50');
          } else {
              nominalInput.readOnly = false;
              nominalInput.classList.remove('bg-gray-50');
              nominalInput.focus();
          }
      });
  });

  // Initialize
  updateUnit();
  nominalInput.readOnly = true;
  nominalInput.classList.add('bg-gray-50');
});
</script>

<?php include "../partials/footer.php"; ?>