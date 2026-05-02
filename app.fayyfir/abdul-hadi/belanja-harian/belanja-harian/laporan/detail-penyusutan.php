<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$kode_produksi = isset($_GET["kode_produksi"]) ? $_GET["kode_produksi"] : null;

if ($id <= 0 && !$kode_produksi) {
  header("Location: index?error=notfound");
  exit();
}

// Data Pembelian (atau Ringkasan Batch Produksi)
if ($kode_produksi) {
    // Jika batch produksi, kita ambil data agregat
    $query_pembelian = $conn->prepare("
        SELECT 
            MAX(bm.nama_bahan) as bahan_nama,
            MAX(bm.satuan) as bahan_satuan,
            SUM(pd.berat_masuk) as berat_awal,
            SUM(pd.berat_masuk * pa.harga_per_kg) / SUM(pd.berat_masuk) as harga_per_kg,
            COUNT(DISTINCT pa.id_supplier) as total_supplier
        FROM bb_proses_detail pd
        JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
        JOIN bb_bahan_master bm ON pa.id_bahan = bm.id
        WHERE pd.kode_produksi = ? AND pd.status = 'aktif' AND pd.tahap_ke = 0
        GROUP BY bm.id
    ");
    $query_pembelian->bind_param("s", $kode_produksi);
} else {
    $query_pembelian = $conn->prepare("
        SELECT p.*, s.nama_supplier AS supplier_nama, bm.nama_bahan AS bahan_nama, bm.satuan AS bahan_satuan
        FROM bb_pembelian_awal p
        LEFT JOIN bb_supplier s ON p.id_supplier = s.id
        LEFT JOIN bb_bahan_master bm ON p.id_bahan = bm.id
        WHERE p.id = ?
    ");
    $query_pembelian->bind_param("i", $id);
}

$query_pembelian->execute();
$res = $query_pembelian->get_result();
$data = $res ? $res->fetch_assoc() : null;

if (!$data) {
  header("Location: index?error=notfound");
  exit();
}

// Data Log Proses
if ($kode_produksi) {
    $query_log = $conn->prepare("
        SELECT 
            COALESCE(MAX(pm.nama_proses), 'Persiapan') as nama_proses,
            MAX(pd.tanggal_proses) as tanggal_proses,
            pd.tahap_ke,
            SUM(pd.berat_masuk) as berat_masuk,
            SUM(pd.berat_keluar) as berat_keluar,
            SUM(pd.penyusutan) as penyusutan,
            GROUP_CONCAT(DISTINCT pd.catatan SEPARATOR '; ') as catatan,
            GROUP_CONCAT(pd.id) as ids,
            MAX(pd.id_penampungan) as has_penampungan
        FROM bb_proses_detail pd
        LEFT JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        WHERE pd.kode_produksi = ? AND pd.status = 'aktif'
        GROUP BY pd.tahap_ke
        ORDER BY pd.tahap_ke ASC
    ");
    $query_log->bind_param("s", $kode_produksi);
} else {
    $query_log = $conn->prepare("
        SELECT pd.*, pm.nama_proses, pd.id as ids 
        FROM bb_proses_detail pd
        JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        WHERE pd.id_pembelian = ? AND pd.status = 'aktif'
        ORDER BY pd.tahap_ke ASC, pd.id ASC
    ");
    $query_log->bind_param("i", $id);
}
$query_log->execute();
$result_log = $query_log->get_result();

// Hitung total otomatis
$total_modal = $data["berat_awal"] * $data["harga_per_kg"];
$harga_beli = $data["harga_per_kg"];

// Hitung total penyusutan
$total_penyusutan = 0;
$is_all_suppliers = false;
$logs = [];
while ($row = $result_log->fetch_assoc()) {
    if (strpos($row['catatan'], '[ALL_SUPPLIERS]') !== false || !empty($row['has_penampungan']) || !empty($row['id_penampungan'])) {
        $is_all_suppliers = true;
        $row['catatan'] = trim(str_replace('[ALL_SUPPLIERS]', '', $row['catatan']));
    }
    $total_penyusutan += (float)$row['penyusutan'];
    $logs[] = $row;
}

$berat_bersih = $data["berat_awal"] - $total_penyusutan;
$hpp_satuan = $berat_bersih > 0 ? $total_modal / $berat_bersih : $harga_beli;
$penyusutan_hpp = $hpp_satuan - $harga_beli;

// Data Supplier (Khusus untuk Batch Produksi)
$suppliers = [];
if ($kode_produksi) {
    $query_suppliers = $conn->prepare("
        SELECT 
            s.nama_supplier,
            SUM(pd.berat_masuk) as berat_digunakan,
            pa.harga_per_kg,
            (SUM(pd.berat_masuk) * pa.harga_per_kg) as total_harga
        FROM bb_proses_detail pd
        JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
        JOIN bb_supplier s ON pa.id_supplier = s.id
        WHERE pd.kode_produksi = ? AND pd.status = 'aktif'
          AND pd.tahap_ke = 0
        GROUP BY pa.id_supplier, pa.harga_per_kg
    ");
    $query_suppliers->bind_param("s", $kode_produksi);
    $query_suppliers->execute();
    $res_suppliers = $query_suppliers->get_result();
    while ($row_s = $res_suppliers->fetch_assoc()) {
        $suppliers[] = $row_s;
    }
}

// Hapus pengambilan supplier_list karena tidak lagi digunakan
$supplier_list = [];


$activeMenu = "purchases";
$activeModule = "Detail Penyusutan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">

  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <div class="flex items-center gap-4">
        <a href="<?= $kode_produksi ? '/app.fayyfir/abdul-hadi/belanja-harian/laporan/penyusutan-tahap' : 'detail-pembelian?id='.$id ?>" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h2 class="text-2xl font-semibold text-gray-800">Detail Penyusutan: <?= htmlspecialchars($kode_produksi ?: ($data['kode_batch'] ?? '-')) ?></h2>
    </div>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
    </div>
  <?php endif; ?>

  <!-- Ringkasan HPP -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Harga Beli Awal (Rata-rata)</p>
          <p class="text-xl font-bold text-gray-900">Rp <?= number_format($harga_beli, 0, ',', '.') ?> / <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Kenaikan HPP (Akibat Susut)</p>
          <p class="text-xl font-bold text-red-600">+ Rp <?= number_format($penyusutan_hpp, 0, ',', '.') ?> / <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
      <div class="bg-emerald-600 p-6 rounded-2xl shadow-sm border border-emerald-700 text-white">
          <p class="text-sm opacity-80 mb-1">Total HPP Akhir (WAC)</p>
          <p class="text-2xl font-bold">Rp <?= number_format($hpp_satuan, 0, ',', '.') ?> / <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Total Modal Bahan</p>
          <p class="text-xl font-bold text-gray-900">Rp <?= number_format($total_modal, 0, ',', '.') ?></p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Total Berat Awal</p>
          <p class="text-xl font-bold text-gray-900"><?= number_format($data['berat_awal'], 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Total Penyusutan</p>
          <p class="text-xl font-bold text-red-600"><?= number_format($total_penyusutan, 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500 mb-1">Berat Bersih Akhir</p>
          <p class="text-xl font-bold text-emerald-600"><?= number_format($berat_bersih, 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></p>
      </div>
  </div>

  <!-- Rincian Bahan Baku (Khusus Batch) -->
  <?php if ($kode_produksi && count($suppliers) > 0 && !$is_all_suppliers): ?>
  <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200 mb-8">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Rincian Bahan Baku (Berdasarkan Supplier)</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
          <tr>
            <th class="px-6 py-3 text-left">Supplier</th>
            <th class="px-6 py-3 text-right">Kuantitas Digunakan (<?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>)</th>
            <th class="px-6 py-3 text-right">Harga Beli / <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></th>
            <th class="px-6 py-3 text-right">Subtotal Modal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($suppliers as $s): ?>
          <tr>
            <td class="px-6 py-4 text-gray-900"><?= htmlspecialchars($s['nama_supplier']) ?></td>
            <td class="px-6 py-4 text-right font-medium"><?= number_format($s['berat_digunakan'], 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?></td>
            <td class="px-6 py-4 text-right">Rp <?= number_format($s['harga_per_kg'], 0, ',', '.') ?></td>
            <td class="px-6 py-4 text-right font-bold text-gray-900">Rp <?= number_format($s['total_harga'], 0, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabel Log Proses -->
  <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Riwayat Tahapan Proses</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400 text-center font-semibold">
          <tr>
            <th class="px-4 py-3 w-16">No</th>
            <th class="px-4 py-3">Tahap</th>
            <th class="px-4 py-3">Tanggal</th>
            <th class="px-4 py-3 text-right">Berat Masuk (<?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>)</th>
            <th class="px-4 py-3 text-right">Berat Keluar (<?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>)</th>
            <th class="px-4 py-3 text-right text-red-400">Susut (<?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>)</th>
            <th class="px-4 py-3">Catatan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-center">
          <?php if (count($logs) > 0): ?>
            <?php $no = 1; foreach ($logs as $row): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-700"><?= $no++; ?></td>
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($row["nama_proses"]); ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(date("d/m/Y", strtotime($row["tanggal_proses"]))); ?></td>
                <td class="px-4 py-3 text-right font-medium"><?= number_format($row["berat_masuk"], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right font-medium"><?= number_format($row["berat_keluar"], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right font-bold text-red-600"><?= number_format($row["penyusutan"], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-gray-500 italic text-left"><?= htmlspecialchars($row["catatan"] ?: '-'); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                Belum ada data tahapan proses untuk batch ini.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>
