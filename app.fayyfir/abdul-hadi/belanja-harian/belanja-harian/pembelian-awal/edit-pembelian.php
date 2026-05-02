<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data pembelian lama
$stmt = $conn->prepare("SELECT * FROM bb_pembelian_awal WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data_lama = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data_lama) {
    die("Error: Data pembelian tidak ditemukan.");
}

// Ambil daftar bahan & supplier
$bahan_result = $conn->query("SELECT id, nama_bahan, satuan FROM bb_bahan_master WHERE deleted_at IS NULL ORDER BY nama_bahan ASC");
$supplier_result = $conn->query("SELECT id, nama_supplier FROM bb_supplier ORDER BY nama_supplier ASC");

// --- Proses simpan data pembelian ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
  $id_post = intval($_POST["id"]);
  $tanggal_pembelian = $_POST["tanggal_pembelian"];
  $bahan_id = intval($_POST["bahan_id"]);
  $supplier_id = intval($_POST["supplier_id"]);
  $berat = floatval($_POST["berat"]);
  $harga_per_kg = floatval($_POST["harga_per_kg"]);
  $catatan = isset($_POST["catatan"]) ? trim($_POST["catatan"]) : '';

  if ($id_post && $bahan_id && $supplier_id && $berat > 0 && $harga_per_kg > 0 && !empty($tanggal_pembelian)) {
    $total_modal = $berat * $harga_per_kg;
    $status_pembayaran_baru = $data_lama['status_pembayaran'];
    $nominal_bayar = (float)$data_lama['nominal_bayar'];

    // Jika total meningkat dan sebelumnya lunas, turunkan status ke belum_lunas
    if ($total_modal > $nominal_bayar && $status_pembayaran_baru === 'lunas') {
        $status_pembayaran_baru = 'belum_lunas';
    }

    $stmt = $conn->prepare("
      UPDATE bb_pembelian_awal 
      SET tanggal_pembelian = ?, id_bahan = ?, id_supplier = ?, berat_awal = ?, harga_per_kg = ?, total_modal = ?, catatan = ?, status_pembayaran = ?
      WHERE id = ?
    ");
    $stmt->bind_param("siidddssi", $tanggal_pembelian, $bahan_id, $supplier_id, $berat, $harga_per_kg, $total_modal, $catatan, $status_pembayaran_baru, $id_post);
    if ($stmt->execute()) {
      echo "<script>
        alert('✅ Data berhasil diperbarui!');
        window.location.href='index.php';
      </script>";
      exit();
    } else {
      $error = 'Gagal memperbarui data: ' . $conn->error;
    }
  } else {
    $error = "Pastikan semua data terisi dengan benar!";
  }
}

$activeMenu = "purchases";
$activeModule = "Edit Load Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4 px-6 sm:px-8">
    <button onclick="window.history.back()" class="inline-flex items-center text-gray-600 hover:text-gray-800 text-sm transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Kembali
    </button>
      <h2 class="text-2xl font-semibold text-gray-800 mt-4 sm:mt-0">Edit Pembelian Bahan</h2>
  </div>
    
  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-md border border-gray-100 p-6 sm:p-8">

    <?php if (!empty($error)): ?>
      <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        <strong>❌ <?= htmlspecialchars($error) ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" id="formPembelian" class="space-y-6">
      <input type="hidden" name="id" value="<?= $id ?>">

      <!-- Row 1 -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Kode Batch</label>
          <input type="text" readonly value="<?= htmlspecialchars($data_lama['kode_batch']) ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembelian</label>
          <input type="date" name="tanggal_pembelian" required
            value="<?= htmlspecialchars($data_lama['tanggal_pembelian']) ?>"
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
            <?php 
            mysqli_data_seek($bahan_result, 0);
            while ($row = $bahan_result->fetch_assoc()): 
            ?>
              <option value="<?= $row['id'] ?>" data-satuan="<?= htmlspecialchars($row['satuan']) ?>" <?= $row['id'] == $data_lama['id_bahan'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['nama_bahan']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
 
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Supplier</label>
          <select name="supplier_id" id="supplier_id" required
            class="w-full border border-gray-300 rounded-xl px-2 py-2.5 text-sm">
            <option value="">--Pilih Supplier--</option>
            <?php 
            mysqli_data_seek($supplier_result, 0);
            while ($row = $supplier_result->fetch_assoc()): 
            ?>  
              <option value="<?= $row['id'] ?>" <?= $row['id'] == $data_lama['id_supplier'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['nama_supplier']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Berat (<span class="unit-label">Kg</span>)</label>
          <input type="number" name="berat" id="berat" step="0.01" min="0.01" required
            value="<?= (float)$data_lama['berat_awal'] ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli (Rp/<span class="unit-label">Kg</span>)</label>
          <input type="number" name="harga_per_kg" id="harga_per_kg" step="0.01" min="0.01" required
            value="<?= (float)$data_lama['harga_per_kg'] ?>"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Total Harga (Rp)</label>
          <input type="text" id="total_harga_display" readonly
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50 font-bold text-gray-800">
        </div>
      </div>

      <!-- Catatan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
        <textarea name="catatan" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5"><?= htmlspecialchars($data_lama['catatan']) ?></textarea>
      </div>

      <!-- Tombol Aksi -->  
      <div class="flex flex-col sm:flex-row justify-between gap-3">  
        <button type="submit"  
          class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 font-medium transition">  
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"  
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">  
            <path stroke-linecap="round" stroke-linejoin="round"  
              d="M5 13l4 4L19 7" />  
          </svg>  
          Perbarui Data  
        </button>  
          
        <button type="button" onclick="window.history.back()"
          class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">  
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"  
            stroke-width="2" stroke="currentColor">  
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />  
          </svg>  
          Batal  
        </button>  
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

  function calculateTotal() {
    const berat = parseFloat(beratInput.value) || 0;
    const harga = parseFloat(hargaInput.value) || 0;
    const total = berat * harga;
    totalDisplay.value = new Intl.NumberFormat('id-ID').format(total);
  }

  function updateUnit() {
    const selectedOption = bahanSelect.options[bahanSelect.selectedIndex];
    const unit = selectedOption.getAttribute('data-satuan') || 'Kg';
    document.querySelectorAll('.unit-label').forEach(el => {
      el.textContent = unit;
    });
  }

  bahanSelect.addEventListener('change', updateUnit);
  beratInput.addEventListener('input', calculateTotal);
  hargaInput.addEventListener('input', calculateTotal);

  // Initialize
  updateUnit();
  calculateTotal();
});
</script>

<?php include "../partials/footer.php"; ?>
