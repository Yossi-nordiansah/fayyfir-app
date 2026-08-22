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

// Detect apakah ini Penampungan Gabungan
$id_penampungan_target = 0;
if ($kode_produksi) {
  // Jika membuka spesifik sub-batch kode_produksi, cek apakah sub-batch tersebut dari Penampungan Gabungan
  $resPnd = $conn->query("SELECT id_penampungan FROM bb_proses_detail WHERE kode_produksi = '" . $conn->real_escape_string($kode_produksi) . "' AND id_penampungan IS NOT NULL AND id_penampungan > 0 LIMIT 1");
  if ($resPnd && $rPd = $resPnd->fetch_assoc()) {
    $id_penampungan_target = (int)$rPd['id_penampungan'];
  }
} else if ($id > 0) {
  // Jika membuka ringkasan umum pembelian tanpa kode_produksi spesifik
  $resPnd = $conn->query("SELECT id_penampungan FROM bb_penampungan_detail WHERE id_pembelian = $id LIMIT 1");
  if ($resPnd && $rPd = $resPnd->fetch_assoc()) {
    $id_penampungan_target = (int)$rPd['id_penampungan'];
  }
}

// Data Pembelian / Penampungan Gabungan (WAC selalu konsisten berdasarkan stok bahan mentah fisik)
if ($id_penampungan_target > 0) {
  $resAgg = $conn->query("
        SELECT 
            MAX(pn.nama_penampungan) as nama_penampungan,
            MAX(bm.id) as id_bahan,
            MAX(bm.nama_bahan) as bahan_nama,
            MAX(bm.satuan) as bahan_satuan,
            SUM(pa.berat_awal) as berat_awal,
            SUM(pa.berat_awal * pa.harga_per_kg) as total_modal,
            SUM(pa.berat_awal * pa.harga_per_kg) / SUM(pa.berat_awal) as harga_per_kg,
            COUNT(DISTINCT pa.id_supplier) as total_supplier
        FROM bb_pembelian_awal pa
        JOIN bb_penampungan_detail pnd ON pnd.id_pembelian = pa.id
        LEFT JOIN bb_penampungan pn ON pn.id = pnd.id_penampungan
        JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
        WHERE pnd.id_penampungan = $id_penampungan_target
    ");
  $data = $resAgg ? $resAgg->fetch_assoc() : null;
  $title_display = !empty($data['nama_penampungan']) ? $data['nama_penampungan'] . ' [GABUNGAN]' : 'Penampungan Gabungan';
} else {
  $query_pembelian = $conn->prepare("
        SELECT p.*, s.nama_supplier AS supplier_nama, bm.nama_bahan AS bahan_nama, bm.satuan AS bahan_satuan
        FROM bb_pembelian_awal p
        LEFT JOIN bb_supplier s ON p.id_supplier = s.id
        LEFT JOIN bb_bahan_master bm ON p.id_bahan = bm.id
        WHERE p.id = ?
    ");
  $query_pembelian->bind_param("i", $id);
  $query_pembelian->execute();
  $res = $query_pembelian->get_result();
  $data = $res ? $res->fetch_assoc() : null;
  $title_display = !empty($data['supplier_nama']) ? 'Supplier: ' . $data['supplier_nama'] : ($data['kode_batch'] ?? ('Pembelian #' . $id));
}

if (!$data) {
  header("Location: index?error=notfound");
  exit();
}

if (!function_exists('get_stage_name')) {
  function get_stage_name($conn, $id_bahan, $urutan)
  {
    $stmt = $conn->prepare("SELECT nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1");
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['nama_proses'] ?? '-';
  }
}

// Fetch list sub-batch di kelompok ini untuk tabel bawah
// Tampilkan jika penampungan gabungan diketahui (baik di ringkasan maupun sub-batch spesifik)
// Jangan tampilkan jika hanya pembelian tunggal tanpa gabungan dan tanpa kode_produksi
$querySubBatches = null;
if ($id_penampungan_target > 0 || (!$kode_produksi && $id > 0)) {
  $whereSub = ($id_penampungan_target > 0)
    ? "pd.id_penampungan = $id_penampungan_target"
    : "pd.id_pembelian = $id";

  $sqlSubBatches = "
        SELECT 
            COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) as batch_key,
            MAX(pd.kode_produksi) as kode_produksi,
            MIN(pd.id_pembelian) as sample_id_pembelian,
            MAX(bm.id) as id_bahan,
            MAX(bm.nama_bahan) as nama_bahan,
            MAX(bm.satuan) as satuan,
            COALESCE(MAX(pd.metode_produksi), 'belum_tertimbang') as metode_produksi,
            COALESCE(MAX(pd.status_batch), 'berjalan') as status_batch,
            MAX(pd.hpp_temporary) as hpp_temporary,
            MAX(pd.hpp_final) as hpp_final,
            COALESCE(MAX(pm.urutan_tahap), 0) as current_tahap_urutan,
            MAX(CASE WHEN pd.status = 'dihentikan' THEN 1 ELSE 0 END) as is_dihentikan,
            (MIN(CASE WHEN pd.status IN ('aktif','dihentikan') THEN 1 ELSE 0 END) = 0
             AND MAX(CASE WHEN pd.status = 'batal' THEN 1 ELSE 0 END) = 1) as is_dibatalkan,
            SUM(CASE 
                WHEN pd.status = 'batal' THEN 0
                WHEN last_stage.max_urutan = 0 THEN pd.berat_masuk 
                WHEN COALESCE(pm.urutan_tahap, 0) = last_stage.max_urutan THEN pd.berat_keluar 
                ELSE 0 
            END) as total_berat_akhir_tahap_ini,
            MIN(pd.created_at) as created_at
        FROM bb_proses_detail pd
        JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
        JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
        LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
        LEFT JOIN (
            SELECT COALESCE(pd3.kode_produksi, CONCAT('SINGLE-', pd3.id_pembelian)) as bk3, COALESCE(MAX(pm3.urutan_tahap), 0) as max_urutan
            FROM bb_proses_detail pd3
            LEFT JOIN bb_proses_master pm3 ON pm3.id = pd3.id_proses_master
            GROUP BY bk3
        ) last_stage ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_stage.bk3
        WHERE $whereSub
        GROUP BY batch_key
        ORDER BY MIN(pd.created_at) ASC
    ";
  $querySubBatches = $conn->query($sqlSubBatches);
  if ($querySubBatches && $querySubBatches->num_rows > 0) {
    while ($sbRow = $querySubBatches->fetch_assoc()) {
      $subBatchesList[] = $sbRow;
    }
  }
}

// Data Log Proses & Info Metode Batch
$batch_metode = 'tertimbang';
$batch_status = 'berjalan';
$hpp_temp_val = 0;
$hpp_final_val = 0;
$susut_final_val = 0;

if ($kode_produksi) {
  // Jika membuka spesifik sub-batch kode_produksi
  $query_log = $conn->prepare("
        SELECT 
            COALESCE(MAX(pm.nama_proses), 'Persiapan') as nama_proses,
            MAX(pd.tanggal_proses) as tanggal_proses,
            pd.tahap_ke,
            SUM(pd.berat_masuk) as berat_masuk,
            SUM(pd.berat_keluar) as berat_keluar,
            SUM(pd.penyusutan) as penyusutan,
            MAX(pd.metode_produksi) as metode_produksi,
            MAX(pd.status_batch) as status_batch,
            MAX(pd.hpp_temporary) as hpp_temporary,
            MAX(pd.hpp_final) as hpp_final,
            MAX(pd.penyusutan_final) as penyusutan_final,
            GROUP_CONCAT(DISTINCT pd.catatan SEPARATOR '; ') as catatan,
            GROUP_CONCAT(pd.id) as ids,
            MAX(pd.id_penampungan) as has_penampungan
        FROM bb_proses_detail pd
        LEFT JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        WHERE pd.kode_produksi = ? AND pd.status IN ('aktif','batal','dihentikan')
        GROUP BY pd.tahap_ke
        ORDER BY pd.tahap_ke ASC
    ");
  $query_log->bind_param("s", $kode_produksi);
} elseif ($id_penampungan_target > 0) {
  // Jika membuka ringkasan seluruh Penampungan Gabungan
  $query_log = $conn->prepare("
        SELECT 
            COALESCE(MAX(pm.nama_proses), 'Persiapan') as nama_proses,
            MAX(pd.tanggal_proses) as tanggal_proses,
            pd.tahap_ke,
            SUM(pd.berat_masuk) as berat_masuk,
            SUM(pd.berat_keluar) as berat_keluar,
            SUM(pd.penyusutan) as penyusutan,
            MAX(pd.metode_produksi) as metode_produksi,
            MAX(pd.status_batch) as status_batch,
            MAX(pd.hpp_temporary) as hpp_temporary,
            MAX(pd.hpp_final) as hpp_final,
            MAX(pd.penyusutan_final) as penyusutan_final,
            GROUP_CONCAT(DISTINCT pd.catatan SEPARATOR '; ') as catatan,
            GROUP_CONCAT(pd.id) as ids,
            1 as has_penampungan
        FROM bb_proses_detail pd
        LEFT JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        WHERE pd.id_penampungan = ?
          AND pd.status IN ('aktif','batal','dihentikan')
        GROUP BY pd.tahap_ke
        ORDER BY pd.tahap_ke ASC
    ");
  $query_log->bind_param("i", $id_penampungan_target);
} else {
  $query_log = $conn->prepare("
        SELECT pd.*, pm.nama_proses, pd.id as ids 
        FROM bb_proses_detail pd
        JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        WHERE pd.id_pembelian = ? AND pd.status IN ('aktif','batal','dihentikan')
        ORDER BY pd.tahap_ke ASC, pd.id ASC
    ");
  $query_log->bind_param("i", $id);
}
$query_log->execute();
$result_log = $query_log->get_result();

// Hitung total otomatis dari supplier & logs
$total_penyusutan = 0;
$is_all_suppliers = false;
$logs = [];
$batch_status_record = 'aktif'; // default

$latest_output_weight = 0;
$max_tahap_log = 0;
$sub_batch_berat_awal = 0;

while ($row = $result_log->fetch_assoc()) {
  if (!empty($row['metode_produksi'])) $batch_metode = $row['metode_produksi'];
  if (!empty($row['status_batch'])) $batch_status = $row['status_batch'];
  if (!empty($row['hpp_temporary'])) $hpp_temp_val = (float)$row['hpp_temporary'];
  if (!empty($row['hpp_final'])) $hpp_final_val = (float)$row['hpp_final'];
  if (!empty($row['penyusutan_final'])) $susut_final_val = (float)$row['penyusutan_final'];

  if (strpos($row['catatan'], '[ALL_SUPPLIERS]') !== false || !empty($row['has_penampungan']) || !empty($row['id_penampungan'])) {
    $is_all_suppliers = true;
    $row['catatan'] = trim(str_replace('[ALL_SUPPLIERS]', '', $row['catatan']));
  }

  $tKe = (int)$row['tahap_ke'];
  $bKeluar = (float)$row['berat_keluar'];
  $bMasuk = (float)$row['berat_masuk'];

  if ($tKe === 0) {
    $sub_batch_berat_awal = ($bKeluar > 0) ? $bKeluar : $bMasuk;
  }

  if ($tKe > 0 && $bKeluar > 0 && $tKe >= $max_tahap_log) {
    $max_tahap_log = $tKe;
    $latest_output_weight = $bKeluar;
  }

  // Hitung akumulasi penyusutan per tahap (untuk tahap yang diketahui susutnya: tKe >= 2 atau metode tertimbang)
  $isBelum = ($batch_metode === 'belum_tertimbang');
  if (!($isBelum && $tKe <= 1)) {
    $sVal = ((float)$row['penyusutan'] > 0) ? (float)$row['penyusutan'] : max(0, $bMasuk - $bKeluar);
    $total_penyusutan += $sVal;
  }

  $logs[] = $row;
}

// Jika ini sub-batch spesifik, gunakan berat_awal milik sub-batch ini jika tersedia
if (!empty($kode_produksi) && $sub_batch_berat_awal > 0) {
  $data['berat_awal'] = $sub_batch_berat_awal;
}
$berat_awal = (float)($data['berat_awal'] ?? 0);

// Untuk Ringkasan Penampungan Gabungan (tanpa kode_produksi spesifik), hitung total output berjalan dari seluruh sub-batch
if (empty($kode_produksi) && $id_penampungan_target > 0 && !empty($subBatchesList)) {
  $total_sub_output = 0;
  $has_valid_sub_output = false;
  foreach ($subBatchesList as $sbItem) {
    if (empty($sbItem['is_dibatalkan'])) {
      $outW = (float)$sbItem['total_berat_akhir_tahap_ini'];
      if ($outW > 0 && (int)$sbItem['current_tahap_urutan'] > 0) {
        $total_sub_output += $outW;
        $has_valid_sub_output = true;
      }
    }
  }
  if ($has_valid_sub_output) {
    $latest_output_weight = $total_sub_output;
  }
}

// Supplier & Total Modal Sub-Batch
$suppliers = [];
$total_modal_sub = 0;
if ($id_penampungan_target > 0 && !$kode_produksi) {
  $query_suppliers = $conn->query("
        SELECT 
            s.nama_supplier,
            SUM(pa.berat_awal) as berat_digunakan,
            pa.harga_per_kg,
            SUM(pa.berat_awal * pa.harga_per_kg) as total_harga
        FROM bb_pembelian_awal pa
        JOIN bb_penampungan_detail pnd ON pnd.id_pembelian = pa.id
        JOIN bb_supplier s ON s.id = pa.id_supplier
        WHERE pnd.id_penampungan = $id_penampungan_target
        GROUP BY pa.id_supplier, pa.harga_per_kg
    ");
  while ($row_s = $query_suppliers->fetch_assoc()) {
    $suppliers[] = $row_s;
    $total_modal_sub += (float)$row_s['total_harga'];
  }
} elseif ($kode_produksi) {
  // Cek apakah sub-batch ini berasal dari Penampungan Gabungan
  $resPenCheck = $conn->query("SELECT MAX(id_penampungan) as id_penampungan FROM bb_proses_detail WHERE kode_produksi = '" . $conn->real_escape_string($kode_produksi) . "' AND id_penampungan IS NOT NULL AND id_penampungan > 0");
  $sub_pen_id = (int)($resPenCheck ? ($resPenCheck->fetch_assoc()['id_penampungan'] ?? 0) : 0);

  if ($sub_pen_id > 0) {
    // Ambil total intake tahap 0 untuk sub-batch ini
    $resInt = $conn->query("SELECT SUM(berat_masuk) as total_intake FROM bb_proses_detail WHERE kode_produksi = '" . $conn->real_escape_string($kode_produksi) . "' AND tahap_ke = 0 AND status IN ('aktif','batal','dihentikan')");
    $sub_intake = (float)($resInt ? ($resInt->fetch_assoc()['total_intake'] ?? 0) : 0);

    // Ambil total isi penampungan gabungan
    $resPenTot = $conn->query("SELECT SUM(berat_masuk) as total_pen_masuk FROM bb_penampungan_detail WHERE id_penampungan = $sub_pen_id");
    $total_pen_masuk = (float)($resPenTot ? ($resPenTot->fetch_assoc()['total_pen_masuk'] ?? 0) : 0);

    if ($total_pen_masuk > 0 && $sub_intake > 0) {
      $query_suppliers = $conn->query("
        SELECT 
            s.nama_supplier,
            pnd.berat_masuk as pen_berat_masuk,
            pa.harga_per_kg
        FROM bb_penampungan_detail pnd
        JOIN bb_pembelian_awal pa ON pa.id = pnd.id_pembelian
        JOIN bb_supplier s ON s.id = pa.id_supplier
        WHERE pnd.id_penampungan = $sub_pen_id
      ");
      while ($row_s = $query_suppliers->fetch_assoc()) {
        $prop = (float)$row_s['pen_berat_masuk'] / $total_pen_masuk;
        $berat_digunakan = round($sub_intake * $prop, 2);
        $total_harga = round($berat_digunakan * (float)$row_s['harga_per_kg'], 2);

        $suppliers[] = [
          'nama_supplier'   => $row_s['nama_supplier'],
          'berat_digunakan' => $berat_digunakan,
          'harga_per_kg'    => (float)$row_s['harga_per_kg'],
          'total_harga'     => $total_harga
        ];
        $total_modal_sub += $total_harga;
      }
    }
  }

  if (empty($suppliers)) {
    $query_suppliers = $conn->prepare("
        SELECT 
            s.nama_supplier,
            SUM(pd.berat_masuk) as berat_digunakan,
            pa.harga_per_kg,
            (SUM(pd.berat_masuk) * pa.harga_per_kg) as total_harga
        FROM bb_proses_detail pd
        JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
        JOIN bb_supplier s ON pa.id_supplier = s.id
        WHERE pd.kode_produksi = ? AND pd.status IN ('aktif','batal','dihentikan')
          AND pd.tahap_ke = 0
        GROUP BY pa.id_supplier, pa.harga_per_kg
    ");
    $query_suppliers->bind_param("s", $kode_produksi);
    $query_suppliers->execute();
    $res_suppliers = $query_suppliers->get_result();
    while ($row_s = $res_suppliers->fetch_assoc()) {
      $suppliers[] = $row_s;
      $total_modal_sub += (float)$row_s['total_harga'];
    }
  }
}

if ($total_modal_sub > 0) {
  $total_modal = $total_modal_sub;
} else {
  $total_modal = isset($data["total_modal"]) ? (float)$data["total_modal"] : ($berat_awal * (float)($data["harga_per_kg"] ?? 0));
}

$harga_beli = ($berat_awal > 0) ? ($total_modal / $berat_awal) : (float)($data["harga_per_kg"] ?? 0);

// Deteksi apakah batch ini dibatalkan atau dihentikan dari record status
if ($kode_produksi) {
  $status_check = $conn->prepare("SELECT MAX(CASE WHEN status='aktif' THEN 1 ELSE 0 END) as has_aktif, MAX(CASE WHEN status='dihentikan' THEN 1 ELSE 0 END) as has_dihentikan, MAX(CASE WHEN status='batal' THEN 1 ELSE 0 END) as has_batal FROM bb_proses_detail WHERE kode_produksi=?");
  $status_check->bind_param("s", $kode_produksi);
  $status_check->execute();
  $sc = $status_check->get_result()->fetch_assoc();
  if ($sc['has_aktif'] == 0 && $sc['has_dihentikan'] == 1) $batch_status_record = 'dihentikan';
  elseif ($sc['has_aktif'] == 0 && $sc['has_dihentikan'] == 0 && $sc['has_batal'] == 1) $batch_status_record = 'batal';
}

// Final calculation for summary cards
if ($batch_status === 'closed' && $susut_final_val > 0) {
  $total_penyusutan = $susut_final_val;
  $berat_bersih = max(0, $berat_awal - $total_penyusutan);
  $hpp_satuan = ($hpp_final_val > 0) ? $hpp_final_val : ($berat_bersih > 0 ? ($total_modal / $berat_bersih) : $harga_beli);
  $penyusutan_hpp = $hpp_satuan - $harga_beli;
} else {
  // Batch masih berjalan (aktif / belum closed)
  $berat_bersih = ($latest_output_weight > 0) ? $latest_output_weight : $berat_awal;
  if ($id_penampungan_target > 0 && empty($kode_produksi) && $berat_awal > 0) {
    $total_penyusutan = max(0, $berat_awal - $berat_bersih);
  }
  $hpp_satuan = ($berat_bersih > 0) ? ($total_modal / $berat_bersih) : $harga_beli;
  $penyusutan_hpp = max(0, $hpp_satuan - $harga_beli);
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
      <a href="<?= ($id > 0) ? 'kelola-sub-batch.php?id_pembelian=' . $id : 'index.php' ?>" class="text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </a>
      <h2 class="text-2xl font-semibold text-gray-800">Detail Penyusutan: <?= htmlspecialchars($title_display) ?></h2>
    </div>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      <span><?= $_SESSION['success'];
            unset($_SESSION['success']); ?></span>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      <span><?= $_SESSION['error'];
            unset($_SESSION['error']); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($batch_metode === 'belum_tertimbang'): ?>
    <div class="mb-6 p-4 rounded-xl border <?= ($batch_status === 'closed') ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' ?> flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase <?= ($batch_status === 'closed') ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' ?>">
          <?= ($batch_status === 'closed') ? 'Revaluasi HPP Final (Batch Closed)' : 'HPP Sementara (Batch Active)' ?>
        </span>
        <span class="text-sm font-medium">
          Metode Produksi: <strong>Belum Tertimbang</strong>
        </span>
      </div>
      <?php if ($batch_status === 'closed'): ?>
        <span class="text-xs font-semibold">Total Susut Final: <?= number_format($susut_final_val, 0, ',', '.') ?> Kg</span>
      <?php else: ?>
        <span class="text-xs italic text-amber-700">*Nilai HPP Final akan di-revaluasi otomatis setelah Closing Batch.</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($batch_status_record === 'batal'): ?>
    <div class="mb-6 p-4 rounded-xl border border-gray-300 bg-gray-100 flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      <div>
        <span class="font-bold text-gray-600 text-sm">Produksi Dibatalkan</span>
        <p class="text-xs text-gray-500 mt-0.5">Batch ini dibatalkan sebelum memasuki tahap proses. Seluruh stok bahan baku telah dikembalikan ke gudang.</p>
      </div>
    </div>
  <?php elseif ($batch_status_record === 'dihentikan'): ?>
    <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636" />
      </svg>
      <div>
        <span class="font-bold text-red-700 text-sm">Produksi Dihentikan</span>
        <p class="text-xs text-red-600 mt-0.5">Produksi ini dihentikan setelah melewati beberapa tahap proses. Bahan baku yang sudah diproses tercatat di bawah ini.</p>
      </div>
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
      <p class="text-xl font-bold <?= ($batch_metode === 'belum_tertimbang' && $batch_status !== 'closed') ? 'text-amber-600 text-sm font-semibold' : (($penyusutan_hpp > 0) ? 'text-red-600' : 'text-gray-900') ?>">
        <?= ($batch_metode === 'belum_tertimbang' && $batch_status !== 'closed') ? '(Belum Diketahui)' : '+ Rp ' . number_format(max(0, $penyusutan_hpp), 0, ',', '.') . ' / ' . htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>
      </p>
    </div>
    <div class="bg-emerald-600 p-6 rounded-2xl shadow-sm border border-emerald-700 text-white">
      <p class="text-sm opacity-80 mb-1"><?= ($batch_status === 'closed') ? 'Total HPP Akhir (WAC)' : 'HPP Sementara' ?></p>
      <p class="text-2xl font-bold">
        <?= ($batch_metode === 'belum_tertimbang' && $batch_status !== 'closed') ? '<span class="text-amber-200 text-xl font-semibold">(Belum Diketahui)</span>' : 'Rp ' . number_format($hpp_satuan, 0, ',', '.') . ' / ' . htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>
      </p>
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
      <p class="text-sm text-gray-500 mb-1">Total Penyusutan <?= ($batch_status === 'closed') ? 'Final' : '(Saat Ini)' ?></p>
      <p class="text-xl font-bold text-red-600">
        <?= number_format($total_penyusutan, 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>
      </p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
      <p class="text-sm text-gray-500 mb-1">Berat Bersih <?= ($batch_status === 'closed') ? 'Akhir' : '(Saat Ini)' ?></p>
      <p class="text-xl font-bold text-emerald-600">
        <?= number_format($berat_bersih, 0, ',', '.') ?> <?= htmlspecialchars($data['bahan_satuan'] ?? 'Kg') ?>
      </p>
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

  <!-- Tabel Tracking Penyusutan Tiap Tahap Proses (Sembunyikan pada ringkasan Penampungan Gabungan & ringkasan Belum Tertimbang) -->
  <?php if (count($logs) > 0 && !(empty($kode_produksi) && ($id_penampungan_target > 0 || $batch_metode === 'belum_tertimbang'))): ?>
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200 mb-8">
      <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800 text-sm flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Tracking Penyusutan Tiap Tahap Proses
        </h3>
        <span class="text-xs text-gray-500 font-medium">Total: <?= count($logs) ?> Tahapan</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
          <thead class="bg-gray-800 text-yellow-400 font-semibold uppercase tracking-wider text-xs">
            <tr>
              <th class="px-6 py-3">No / Tahap</th>
              <th class="px-6 py-3">Nama Proses</th>
              <th class="px-6 py-3">Tanggal</th>
              <th class="px-6 py-3 text-right">Berat Masuk</th>
              <th class="px-6 py-3 text-right">Hasil (Keluar)</th>
              <th class="px-6 py-3 text-right">Susut</th>
              <th class="px-6 py-3 text-right">% Susut</th>
              <th class="px-6 py-3">Catatan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php
            foreach ($logs as $log):
              $tahapKe = (int)$log['tahap_ke'];
              $isBelumTertimbang = ($batch_metode === 'belum_tertimbang');
              $masukVal = (float)$log['berat_masuk'];
              $keluarVal = (float)$log['berat_keluar'];

              // Format Berat Masuk
              if ($isBelumTertimbang && $tahapKe <= 1) {
                $displayMasuk = '<span class="text-gray-400 italic text-xs">(Belum Tertimbang)</span>';
              } else {
                $displayMasuk = number_format($masukVal, 0, ',', '.') . ' ' . htmlspecialchars($data['bahan_satuan'] ?? 'Kg');
              }

              // Format Berat Keluar
              $displayKeluar = number_format($keluarVal, 0, ',', '.') . ' ' . htmlspecialchars($data['bahan_satuan'] ?? 'Kg');

              // Format Susut & % Susut
              if ($isBelumTertimbang && $tahapKe <= 1) {
                $displaySusut = '<span class="text-amber-600 text-xs font-semibold">(Tidak Diketahui)</span>';
                $displayPct = '-';
              } else {
                $susutVal = ((float)$log['penyusutan'] > 0) ? (float)$log['penyusutan'] : max(0, $masukVal - $keluarVal);
                $displaySusut = '<span class="text-red-600 font-bold">' . number_format($susutVal, 0, ',', '.') . ' ' . htmlspecialchars($data['bahan_satuan'] ?? 'Kg') . '</span>';

                if ($masukVal > 0 && $susutVal > 0) {
                  $pct = ($susutVal / $masukVal) * 100;
                  $displayPct = '<span class="text-red-500 font-semibold">' . number_format($pct, 1, ',', '.') . '%</span>';
                } else {
                  $displayPct = '0%';
                }
              }

              // Tanggal Format
              $tglDisplay = '-';
              if (!empty($log['tanggal_proses'])) {
                $tglDisplay = date('d/m/Y', strtotime($log['tanggal_proses']));
              }
            ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-bold text-gray-900">
                  <?= ($tahapKe === 0) ? 'Persiapan' : 'Tahap ' . $tahapKe ?>
                </td>
                <td class="px-6 py-4 font-semibold text-gray-800">
                  <?= htmlspecialchars($log['nama_proses'] ?? 'Persiapan') ?>
                </td>
                <td class="px-6 py-4 text-xs text-gray-500">
                  <?= $tglDisplay ?>
                </td>
                <td class="px-6 py-4 text-right font-medium">
                  <?= $displayMasuk ?>
                </td>
                <td class="px-6 py-4 text-right font-medium">
                  <?= $displayKeluar ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <?= $displaySusut ?>
                </td>
                <td class="px-6 py-4 text-right text-xs">
                  <?= $displayPct ?>
                </td>
                <td class="px-6 py-4 text-xs text-gray-500 italic">
                  <?= htmlspecialchars($log['catatan'] ?? '-') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($subBatchesList)): ?>
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200 mb-8">
      <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800 text-sm">Daftar Sub-Batch di Kelompok Ini</h3>
        <span class="text-xs text-gray-500 font-medium">Total: <?= count($subBatchesList) ?> Sub-Batch</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
          <thead class="bg-gray-800 text-yellow-400 font-semibold uppercase tracking-wider text-xs">
            <tr>
              <th class="px-6 py-3">Kode Sub-Batch</th>
              <th class="px-6 py-3">Tanggal Dibuat</th>
              <th class="px-6 py-3">Tahap Saat Ini</th>
              <th class="px-6 py-3 text-right">Hasil Tahap (Terakhir)</th>
              <th class="px-6 py-3 text-center">Status</th>
              <th class="px-6 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($subBatchesList as $sub):
              $kode_sub        = $sub['kode_produksi'];
              $is_dihentikan   = (bool)$sub['is_dihentikan'];
              $is_dibatalkan   = (bool)$sub['is_dibatalkan'];
              $tahap_nama      = ($sub['current_tahap_urutan'] == 0) ? 'Persiapan' : get_stage_name($conn, $data['id_bahan'] ?? $sub['id_bahan'], $sub['current_tahap_urutan']);
              $remaining       = (float)$sub['total_berat_akhir_tahap_ini'];
              $row_class       = $is_dibatalkan ? 'bg-gray-100 opacity-75' : ($is_dihentikan ? 'bg-red-50' : 'hover:bg-gray-50');
              // URL: untuk gabungan gunakan sample_id_pembelian dari sub-batch agar id tidak tergantung $id aktif
              $detail_sub_url  = "detail-penyusutan.php?id=" . $sub['sample_id_pembelian'] . "&kode_produksi=" . urlencode($kode_sub);
            ?>
              <tr class="<?= $row_class ?> transition">
                <td class="px-6 py-4 font-bold text-gray-900">
                  <?= htmlspecialchars($kode_sub ?: ('ID #' . $sub['sample_id_pembelian'])) ?>
                </td>
                <td class="px-6 py-4 text-xs text-gray-500">
                  <?= date('d M Y, H:i', strtotime($sub['created_at'])) ?>
                </td>
                <td class="px-6 py-4">
                  <?php if ($is_dibatalkan): ?>
                    <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-400 text-[10px] font-semibold uppercase">Dibatalkan</span>
                  <?php elseif ($is_dihentikan): ?>
                    <span class="px-2.5 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-semibold uppercase"><?= htmlspecialchars($tahap_nama) ?> (Dihentikan)</span>
                  <?php else: ?>
                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold uppercase"><?= htmlspecialchars($tahap_nama) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right font-bold">
                  <?= ($sub['current_tahap_urutan'] == 0 || $remaining <= 0) ? '(berat belum diketahui)' : number_format($remaining, 0, ',', '.') . ' ' . htmlspecialchars($sub['satuan']) ?>
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= ($sub['status_batch'] === 'closed') ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' ?>">
                    <?= ($sub['status_batch'] === 'closed') ? 'Selesai' : 'Berjalan' ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <button type="button" onclick="openDetailModal('<?= htmlspecialchars(addslashes($kode_sub)) ?>')"
                    class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1">
                    Detail
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</main>

<!-- Modal Detail Riwayat Tahapan Sub-Batch -->
<div id="modalDetailSubBatch" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden transform transition-all border border-gray-100">
    <!-- Modal Header -->
    <div class="px-6 py-4 bg-gray-800 text-white flex justify-between items-center border-b border-gray-700">
      <div>
        <h3 class="text-base font-bold text-yellow-400 flex items-center gap-2" id="modalSubBatchTitle">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          Riwayat Tahapan Proses
        </h3>
        <p class="text-xs text-gray-300 mt-0.5" id="modalSubBatchSubtitle">Rincian alur timbangan & susut per tahap produksi</p>
      </div>
      <button type="button" onclick="closeDetailModal()" class="text-gray-400 hover:text-white transition">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <!-- Modal Body -->
    <div class="p-6">
      <div id="modalLoading" class="text-center py-10 text-gray-500 flex flex-col items-center gap-3">
        <svg class="animate-spin h-7 w-7 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs font-semibold text-gray-600">Memuat riwayat tahapan proses sub-batch...</span>
      </div>
      <div id="modalContent" class="hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-800 text-yellow-400 text-center font-semibold text-xs">
            <tr>
              <th class="px-4 py-3 w-12">No</th>
              <th class="px-4 py-3">Tahap</th>
              <th class="px-4 py-3">Tanggal</th>
              <th class="px-4 py-3 text-right">Berat Masuk</th>
              <th class="px-4 py-3 text-right">Hasil (Keluar)</th>
              <th class="px-4 py-3 text-right text-red-400">Susut</th>
              <th class="px-4 py-3">Catatan</th>
            </tr>
          </thead>
          <tbody id="modalTableBody" class="divide-y divide-gray-100 text-center">
          </tbody>
        </table>
      </div>
    </div>
    <!-- Modal Footer -->
    <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
      <span class="text-xs text-gray-500 font-medium">*Klik di luar modal atau tombol Tutup untuk keluar.</span>
      <button type="button" onclick="closeDetailModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl transition shadow-sm">
        Tutup
      </button>
    </div>
  </div>
</div>

<script>
  function openDetailModal(kodeProduksi) {
    const modal = document.getElementById('modalDetailSubBatch');
    const loading = document.getElementById('modalLoading');
    const content = document.getElementById('modalContent');
    const title = document.getElementById('modalSubBatchTitle');
    const subtitle = document.getElementById('modalSubBatchSubtitle');
    const tbody = document.getElementById('modalTableBody');

    title.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg> Riwayat Tahapan Proses: ${kodeProduksi}`;
    subtitle.innerText = 'Rincian alur timbangan & susut per tahap untuk Sub-Batch ' + kodeProduksi;
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    modal.classList.remove('hidden');

    fetch('api-get-sub-batch-detail.php?kode_produksi=' + encodeURIComponent(kodeProduksi))
      .then(res => res.json())
      .then(data => {
        loading.classList.add('hidden');
        content.classList.remove('hidden');
        tbody.innerHTML = '';

        if (data.success && data.logs && data.logs.length > 0) {
          let no = 1;
          data.logs.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';

            let isBelumTertimbang = (data.metode_produksi === 'belum_tertimbang');
            let tahapKe = parseInt(row.tahap_ke);
            let masukVal = parseFloat(row.berat_masuk) || 0;
            let keluarVal = parseFloat(row.berat_keluar) || 0;

            let beratMasuk = (isBelumTertimbang && tahapKe <= 1) ?
              '<span class="text-amber-700 italic text-xs">(Belum Tertimbang)</span>' :
              new Intl.NumberFormat('id-ID').format(masukVal) + ' ' + data.satuan;

            let beratKeluar = (isBelumTertimbang && tahapKe === 0) ?
              '<span class="text-amber-700 italic text-xs">(Belum Tertimbang)</span>' :
              new Intl.NumberFormat('id-ID').format(keluarVal) + ' ' + data.satuan;

            let susutDisplay;
            if (isBelumTertimbang && tahapKe <= 1) {
              susutDisplay = '<span class="text-amber-600 text-xs font-semibold">(Tidak Diketahui)</span>';
            } else {
              let susutVal = (parseFloat(row.penyusutan) > 0) ? parseFloat(row.penyusutan) : Math.max(0, masukVal - keluarVal);
              susutDisplay = '<span class="text-red-600 font-bold">' + new Intl.NumberFormat('id-ID').format(susutVal) + ' ' + data.satuan + '</span>';
            }

            let tglStr = '-';
            if (row.tanggal_proses) {
              const parts = row.tanggal_proses.split('-');
              if (parts.length === 3) {
                tglStr = `${parts[2]}/${parts[1]}/${parts[0]}`;
              } else {
                tglStr = row.tanggal_proses;
              }
            }

            tr.innerHTML = `
                        <td class="px-4 py-3 text-gray-700 font-medium">${no++}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">${row.nama_proses || 'Persiapan'}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">${tglStr}</td>
                        <td class="px-4 py-3 text-right font-medium">${beratMasuk}</td>
                        <td class="px-4 py-3 text-right font-medium">${beratKeluar}</td>
                        <td class="px-4 py-3 text-right">${susutDisplay}</td>
                        <td class="px-4 py-3 text-gray-500 italic text-left text-xs">${row.catatan || '-'}</td>
                    `;
            tbody.appendChild(tr);
          });
        } else {
          tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada riwayat tahapan proses.</td></tr>`;
        }
      })
      .catch(err => {
        loading.classList.add('hidden');
        content.classList.remove('hidden');
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-red-500 font-medium">Gagal memuat data: ${err.message}</td></tr>`;
      });
  }

  function closeDetailModal() {
    document.getElementById('modalDetailSubBatch').classList.add('hidden');
  }

  // Tutup modal jika user mengklik backdrop luar
  document.getElementById('modalDetailSubBatch')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeDetailModal();
    }
  });
</script>

<?php include "../partials/footer.php"; ?>