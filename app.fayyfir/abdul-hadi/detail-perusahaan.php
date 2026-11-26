<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}
require_once "config.php";

$level   = $_SESSION["role_id"] ?? "";
$region  = $_SESSION["region"] ?? "";
$user_id = $_SESSION["user_id"] ?? 0;
// Halaman ini mengutamakan get_conn1() (yossinor_db) atau fallback ke $conn
$db = function_exists('get_conn1') ? get_conn1() : ($conn ?? null);
if (!$db || $db->connect_error) {
  http_response_code(500);
  die("<div style='font-family:sans-serif;padding:24px;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;max-width:650px;margin:50px auto;'>
    <h3 style='margin-top:0;'>Gagal Menghubungkan ke Database</h3>
    <p>Sistem tidak dapat membuka koneksi database: <strong>" . htmlspecialchars($db ? $db->connect_error : "Koneksi tidak terdefinisi") . "</strong></p>
    <p style='font-size:12px;color:#6b7280;'>Pastikan konfigurasi database di <code>config.php</code> server hosting sudah tepat.</p>
  </div>");
}

if (!function_exists('ensureLabelMaster')) {
  function ensureLabelMaster(mysqli $db, string $label): void
  {
    $label = trim($label);
    if ($label === '' || $label === '__new__') return;
    $st = $db->prepare("INSERT IGNORE INTO perusahaan_label_master (nama_label) VALUES (?)");
    if ($st) {
      $st->bind_param("s", $label);
      $st->execute();
    }
  }
}

if (!function_exists('ensureTipeMaster')) {
  function ensureTipeMaster(mysqli $db, string $tipe): void
  {
    $tipe = trim($tipe);
    if ($tipe === '' || $tipe === '__new__') return;
    $st = $db->prepare("INSERT IGNORE INTO perusahaan_tipe_master (nama_tipe) VALUES (?)");
    if ($st) {
      $st->bind_param("s", $tipe);
      $st->execute();
    }
  }
}

$id_master = intval($_GET['id'] ?? 0);
if (!$id_master) {
  header("Location: data-perusahaan");
  exit;
}

// Fetch master data perusahaan
$st = $db->prepare("SELECT * FROM perusahaan_master WHERE id=?");
$st->bind_param("i", $id_master);
$st->execute();
$master = $st->get_result()->fetch_assoc();
if (!$master) {
  header("Location: data-perusahaan");
  exit;
}

$success = $_SESSION['flash_success'] ?? "";
unset($_SESSION['flash_success']);
$error   = $_SESSION['flash_error'] ?? "";
unset($_SESSION['flash_error']);

/* =========================================================
   AJAX: Inline Update (Double click edit)
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'inline_update') {
  header('Content-Type: application/json');
  $type = $_POST['type'] ?? '';
  $id   = intval($_POST['id'] ?? 0);
  $val  = trim($_POST['val'] ?? '');

  if (!$id || !$type) {
    echo json_encode(['ok' => false, 'msg' => 'Parameter tidak valid']);
    exit;
  }

  // Edit Label Data Utama
  if ($type === 'data_label') {
    if ($val === '') {
      echo json_encode(['ok' => false, 'msg' => 'Label tidak boleh kosong']);
      exit;
    }
    ensureLabelMaster($db, $val);
    $st = $db->prepare("UPDATE perusahaan_data SET label=? WHERE id=? AND id_master=?");
    $st->bind_param("sii", $val, $id, $id_master);
    $st->execute();
    echo json_encode(['ok' => true, 'val' => $val]);
    exit;
  }

  // Edit Value Data Utama
  if ($type === 'data_value') {
    $st = $db->prepare("UPDATE perusahaan_data SET value=? WHERE id=? AND id_master=?");
    $st->bind_param("sii", $val, $id, $id_master);
    $st->execute();
    echo json_encode(['ok' => true, 'val' => $val]);
    exit;
  }

  // Edit Label Sub-Data (Multiple Data)
  if ($type === 'sub_label') {
    if ($val === '') {
      echo json_encode(['ok' => false, 'msg' => 'Label sub tidak boleh kosong']);
      exit;
    }
    ensureLabelMaster($db, $val);
    $st = $db->prepare("UPDATE perusahaan_subdata SET label=? WHERE id=?");
    $st->bind_param("si", $val, $id);
    $st->execute();
    echo json_encode(['ok' => true, 'val' => $val]);
    exit;
  }

  // Edit Value Sub-Data (Multiple Data)
  if ($type === 'sub_value') {
    $st = $db->prepare("UPDATE perusahaan_subdata SET value=? WHERE id=?");
    $st->bind_param("si", $val, $id);
    $st->execute();
    echo json_encode(['ok' => true, 'val' => $val]);
    exit;
  }

  // Edit Nama Master
  if ($type === 'master_nama') {
    if ($val === '') {
      echo json_encode(['ok' => false, 'msg' => 'Nama master tidak boleh kosong']);
      exit;
    }
    $st = $db->prepare("UPDATE perusahaan_master SET nama=? WHERE id=?");
    $st->bind_param("si", $val, $id_master);
    $st->execute();
    echo json_encode(['ok' => true, 'val' => $val]);
    exit;
  }

  echo json_encode(['ok' => false, 'msg' => 'Tipe edit tidak dikenal']);
  exit;
}

/* =========================================================
   AJAX: Delete Item (Single Data / Sub-Data)
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete_item') {
  header('Content-Type: application/json');
  $type = $_POST['type'] ?? '';
  $id   = intval($_POST['id'] ?? 0);

  if ($type === 'data') {
    $st = $db->prepare("DELETE FROM perusahaan_data WHERE id=? AND id_master=?");
    $st->bind_param("ii", $id, $id_master);
    $st->execute();
    echo json_encode(['ok' => true]);
    exit;
  }
  if ($type === 'subdata') {
    $st = $db->prepare("DELETE FROM perusahaan_subdata WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
    echo json_encode(['ok' => true]);
    exit;
  }
  echo json_encode(['ok' => false]);
  exit;
}

/* =========================================================
   AJAX: Add Single Sub-data to Existing Data
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'add_sub_direct') {
  header('Content-Type: application/json');
  $id_data = intval($_POST['id_data'] ?? 0);
  $slbl    = trim($_POST['label'] ?? '');
  $sval    = trim($_POST['value'] ?? '');

  if (!$id_data || $slbl === '') {
    echo json_encode(['ok' => false, 'msg' => 'Label sub wajib diisi']);
    exit;
  }

  $chk = $db->prepare("SELECT id FROM perusahaan_data WHERE id=? AND id_master=?");
  $chk->bind_param("ii", $id_data, $id_master);
  $chk->execute();
  if (!$chk->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'msg' => 'Data tidak valid']);
    exit;
  }

  ensureLabelMaster($db, $slbl);
  $st = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
  $st->bind_param("iss", $id_data, $slbl, $sval);
  $st->execute();
  $new_id = $db->insert_id;

  echo json_encode(['ok' => true, 'id' => $new_id, 'label' => $slbl, 'value' => $sval]);
  exit;
}

/* =========================================================
   AJAX: Add Label Master (autocomplete helper)
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'add_label') {
  header('Content-Type: application/json');
  $nama = trim($_POST['nama_label'] ?? '');
  if ($nama === '' || $nama === '__new__') {
    echo json_encode(['ok' => false, 'msg' => 'Nama label tidak valid']);
    exit;
  }
  ensureLabelMaster($db, $nama);
  $r  = $db->query("SELECT id, nama_label FROM perusahaan_label_master WHERE nama_label='" . $db->real_escape_string($nama) . "' LIMIT 1");
  $row = $r ? $r->fetch_assoc() : null;
  $id = $row ? $row['id'] : 0;
  echo json_encode(['ok' => true, 'id' => $id, 'nama' => $nama]);
  exit;
}

/* =========================================================
   AJAX: hapus label dari autocomplete
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete_label') {
  header('Content-Type: application/json');
  $id = intval($_POST['id'] ?? 0);
  $nama = trim($_POST['nama_label'] ?? '');

  if ($id <= 0 && $nama === '') {
    echo json_encode(['ok' => false, 'msg' => 'Parameter label tidak valid']);
    exit;
  }

  if ($id > 0) {
    $st = $db->prepare("DELETE FROM perusahaan_label_master WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
  } else {
    $st = $db->prepare("DELETE FROM perusahaan_label_master WHERE nama_label=?");
    $st->bind_param("s", $nama);
    $st->execute();
  }

  echo json_encode(['ok' => true, 'id' => $id, 'nama' => $nama]);
  exit;
}

/* =========================================================
   POST: Edit Single Data (dari modal Edit di Card Data)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_single_data') {
  $data_id = intval($_POST['data_id'] ?? 0);
  $new_label = trim($_POST['data_label'] ?? '');
  $new_value = trim($_POST['data_value'] ?? '');

  if ($data_id > 0 && $new_label !== '') {
    ensureLabelMaster($db, $new_label);
    $st = $db->prepare("UPDATE perusahaan_data SET label=?, value=? WHERE id=? AND id_master=?");
    $st->bind_param("ssii", $new_label, $new_value, $data_id, $id_master);
    $st->execute();

    // Jika user mengaktifkan multiple data dan menambahkan sub-data
    $sub_labels = $_POST['edit_sub_label'] ?? [];
    $sub_values = $_POST['edit_sub_value'] ?? [];
    if (!empty($sub_labels)) {
      foreach ($sub_labels as $j => $slbl) {
        $slbl = trim($slbl);
        if ($slbl === '' || $slbl === '__new__') continue;
        ensureLabelMaster($db, $slbl);

        $sv = $sub_values[$j] ?? '';
        $st_sub = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
        $st_sub->bind_param("iss", $data_id, $slbl, $sv);
        $st_sub->execute();
      }
    }
    $_SESSION['flash_success'] = "Data \"$new_label\" berhasil diperbarui.";
  }
  header("Location: detail-perusahaan?id=" . $id_master);
  exit;
}

/* =========================================================
   POST: Add Data (Requirement 3: from Modal Add Data)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_data') {
  $labels = $_POST['data_label'] ?? [];
  $values = $_POST['data_value'] ?? [];
  $added_count = 0;

  foreach ($labels as $i => $lbl) {
    $lbl = trim($lbl);
    if ($lbl === '' || $lbl === '__new__') continue;
    ensureLabelMaster($db, $lbl);

    $val = $values[$i] ?? '';
    $st = $db->prepare("INSERT INTO perusahaan_data (id_master, label, value) VALUES (?,?,?)");
    $st->bind_param("iss", $id_master, $lbl, $val);
    $st->execute();
    $id_data = $db->insert_id;
    $added_count++;

    $sub_labels = $_POST["sub_label_$i"] ?? [];
    $sub_values = $_POST["sub_value_$i"] ?? [];
    foreach ($sub_labels as $j => $slbl) {
      $slbl = trim($slbl);
      if ($slbl === '' || $slbl === '__new__') continue;
      ensureLabelMaster($db, $slbl);

      $sv  = $sub_values[$j] ?? '';
      $st2 = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
      $st2->bind_param("iss", $id_data, $slbl, $sv);
      $st2->execute();
    }
  }

  if ($added_count > 0) {
    $_SESSION['flash_success'] = "$added_count data baru berhasil ditambahkan.";
  }
  header("Location: detail-perusahaan?id=" . $id_master);
  exit;
}

/* =========================================================
   FETCH: Data & Sub-data for this company
   ========================================================= */
$detail_data = [];
$st2 = $db->prepare("SELECT * FROM perusahaan_data WHERE id_master=? ORDER BY id ASC");
$st2->bind_param("i", $id_master);
$st2->execute();
$res2 = $st2->get_result();
while ($r2 = $res2->fetch_assoc()) {
  $st3 = $db->prepare("SELECT * FROM perusahaan_subdata WHERE id_data=? ORDER BY id ASC");
  $st3->bind_param("i", $r2['id']);
  $st3->execute();
  $r2['subs'] = $st3->get_result()->fetch_all(MYSQLI_ASSOC);
  $detail_data[] = $r2;
}

/* =========================================================
   FETCH: labels for autocomplete datalist
   ========================================================= */
$label_opts = $db->query("SELECT id, nama_label FROM perusahaan_label_master ORDER BY nama_label ASC");
$all_labels = [];
if ($label_opts) {
  while ($r = $label_opts->fetch_assoc()) $all_labels[] = $r;
}
$labels_json = json_encode($all_labels);

$tipe_map = ['pt' => 'PT / CV', 'personal' => 'Personal', 'lainnya' => 'Lainnya'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detail: <?= htmlspecialchars($master['nama']) ?> - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .modal-overlay {
      background: rgba(0, 0, 0, .55);
    }

    .data-row {
      border-left: 4px solid #4f46e5;
    }

    .subdata-row {
      border-left: 3px solid #7c3aed;
    }

    .modal-80vh {
      max-height: 80vh !important;
      overflow-y: auto !important;
    }

    .modal-80vh::-webkit-scrollbar {
      width: 6px;
    }

    .modal-80vh::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 4px;
    }

    .modal-80vh::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }

    .modal-80vh::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* Modal Detail Typography & Compact Density */
    .detail-body {
      font-size: 11px;
      line-height: 1.35;
    }

    .detail-body .detail-label {
      font-size: 9.5px;
      font-weight: 700;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .detail-body .detail-val {
      font-size: 11px;
      font-weight: 600;
      line-height: 1.35;
    }

    .detail-body .detail-sub-label {
      font-size: 10px;
      font-weight: 600;
      line-height: 1.2;
    }

    .detail-body .detail-sub-val {
      font-size: 11px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      line-height: 1.35;
    }

    .detail-body .copy-btn .material-symbols-outlined {
      font-size: 13px !important;
      line-height: 1;
    }

    /* Double Click Editable Style */
    .editable-field {
      cursor: pointer;
      border-radius: 3px;
      transition: background-color 0.15s, box-shadow 0.15s;
    }

    .editable-field:hover {
      background-color: #fef3c7;
      outline: 1px dashed #f59e0b;
    }

    .editable-label:hover {
      outline: 1px dashed #6366f1;
      background-color: #e0e7ff;
    }
  </style>
  <script>
    function toggleDropdown1() {
      document.getElementById('dropdownMenu1').classList.toggle('hidden');
    }

    function toggleDropdown2() {
      document.getElementById('dropdownMenu2').classList.toggle('hidden');
    }

    function toggleDropdown3() {
      document.getElementById('dropdownMenu3').classList.toggle('hidden');
    }

    function toggleDropdown4() {
      document.getElementById('dropdownMenu4').classList.toggle('hidden');
    }

    function toggleDropdown5() {
      document.getElementById('dropdownMenu5').classList.toggle('hidden');
    }

    function toggleDropdown6() {
      document.getElementById('dropdownMenu6').classList.toggle('hidden');
    }

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('-translate-x-full');
    }
  </script>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <!-- Datalist Autocomplete untuk Semua Label -->
  <datalist id="labelDatalist">
    <?php foreach ($all_labels as $l): ?>
      <option value="<?= htmlspecialchars($l['nama_label']) ?>"></option>
    <?php endforeach; ?>
  </datalist>

  <div class="flex min-h-screen">

    <!-- ===== SIDEBAR ===== -->
    <aside id="sidebar" class="bg-gray-800 w-72 min-h-screen space-y-6 py-7 px-2 fixed inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition duration-200 ease-in-out z-50 overflow-y-auto">
      <div class="flex items-center justify-between px-4">
        <img src="assets/logo-fayyfir3.png" alt="Logo Fayyfir" class="h-8" />
        <button onclick="toggleSidebar()" class="lg:hidden text-white">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <nav class="px-4 space-y-2">

        <?php if ($level == "5"): ?>
          <!-- Khusus Role admin_data_perusahaan: Hanya Akses Menu Data Perusahaan -->
          <a href="data-perusahaan" class="flex items-center space-x-2 text-indigo-300 hover:text-white px-3 py-2 font-semibold bg-gray-900 rounded">
            <span class="material-symbols-outlined text-sm">business</span>
            <span class="text-base">Data Perusahaan</span>
          </a>
        <?php else: ?>
          <!-- Dashboard -->
          <?php if (!($level == "1" && $region == "Gaharu")): ?>
            <div>
              <button onclick="toggleDropdown4()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">
                <div class="flex items-center space-x-2">
                  <span class="material-symbols-outlined text-sm">home</span>
                  <span class="text-base">Dashboard</span>
                </div>
                <span class="material-symbols-outlined text-sm">expand_more</span>
              </button>
              <div id="dropdownMenu4" class="hidden ml-8 mt-1 space-y-1">
                <a href="tambah-kontainer" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">battery_0_bar</span>
                  <span class="text-base">Kontainer Baru</span>
                </a>
                <?php if ($level == "2" || $level == "3"): ?>
                  <a href="full" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">battery_full</span>
                    <span class="text-base">Kontainer Full</span>
                  </a>
                  <a href="verifikasi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">verified_user</span>
                    <span class="text-base">Kontainer Verified</span>
                  </a>
                  <a href="sudah-diterima" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">checklist</span>
                    <span class="text-base">Kontainer Diterima</span>
                  </a>
                  <a href="riwayat-kontainer" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">history</span>
                    <span class="text-base">Riwayat Kontainer</span>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Gaharu / Belanja / Menu -->
          <?php if ($region == "Gaharu" || $level == "3"): ?>
            <div>
              <button onclick="toggleDropdown6()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">
                <div class="flex items-center space-x-2">
                  <span class="material-symbols-outlined text-sm">eco</span>
                  <span class="text-base">Gaharu</span>
                </div>
                <span class="material-symbols-outlined text-sm">expand_more</span>
              </button>
              <div id="dropdownMenu6" class="hidden ml-8 mt-1 space-y-1">
                <a href="belanja-harian/index.php" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">receipt_long</span>
                  <span class="text-base">Belanja Harian</span>
                </a>
                <a href="belanja-harian/proses-produksi/index.php" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                  <span class="text-base">Proses Produksi</span>
                </a>
                <a href="belanja-harian/hasil-produksi/index.php" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">inventory_2</span>
                  <span class="text-base">Hasil Produksi</span>
                </a>
                <a href="belanja-harian/stok-bahan/index.php" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">warehouse</span>
                  <span class="text-base">Gudang Bahan Baku</span>
                </a>
              </div>
            </div>

            <!-- Petani/Supplier -->
            <div>
              <button onclick="toggleDropdown2()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">
                <div class="flex items-center space-x-2">
                  <span class="material-symbols-outlined text-sm">groups</span>
                  <span class="text-base">Petani/ Supplier</span>
                </div>
                <span class="material-symbols-outlined text-sm">expand_more</span>
              </button>
              <div id="dropdownMenu2" class="hidden ml-8 mt-1 space-y-1">
                <a href="daftar-supplier" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">groups</span>
                  <span class="text-base">Data Petani/ Supplier</span>
                </a>
                <a href="riwayat-dp-supplier" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">credit_score</span>
                  <span class="text-base">DP Petani/ Supplier</span>
                </a>
              </div>
            </div>

            <a href="riwayat-operasional" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">
              <span class="material-symbols-outlined text-sm">payments</span>
              <span class="text-base">Cost Operasional</span>
            </a>
            <a href="modal-dan-aset" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">
              <span class="material-symbols-outlined text-sm">account_balance</span>
              <span class="text-base">Modal &amp; Aset</span>
            </a>

            <!-- Laporan -->
            <div>
              <button onclick="toggleDropdown3()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">
                <div class="flex items-center space-x-2">
                  <span class="material-symbols-outlined text-sm">article</span>
                  <span class="text-base">Laporan</span>
                </div>
                <span class="material-symbols-outlined text-sm">expand_more</span>
              </button>
              <div id="dropdownMenu3" class="hidden ml-8 mt-1 space-y-1">
                <a href="laporan-transaksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">receipt_long</span>
                  <span class="text-base">Laporan Transaksi</span>
                </a>
                <a href="laporan-modal" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                  <span class="text-base">Laporan Modal</span>
                </a>
                <a href="laporan-laba-rugi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">bar_chart</span>
                  <span class="text-base">Laporan Laba Rugi</span>
                </a>
              </div>
            </div>

            <!-- Abdul Hadi -->
            <div>
              <button onclick="toggleDropdown5()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-2 focus:outline-none">
                <div class="flex items-center space-x-2">
                  <span class="material-symbols-outlined text-sm">person</span>
                  <span class="text-base">Abdul Hadi</span>
                </div>
                <span class="material-symbols-outlined text-sm">expand_more</span>
              </button>
              <div id="dropdownMenu5" class="ml-8 mt-1 space-y-1">
                <a href="utang-piutang" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">partner_exchange</span>
                  <span class="text-base">Utang Piutang</span>
                </a>
                <a href="belanja-harian/dashboard.php" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                  <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                  <span class="text-base">Belanja Bahan</span>
                </a>
                <button onclick="toggleDropdown1()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-2 py-1 focus:outline-none">
                  <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-sm">forest</span>
                    <span class="text-base">Produksi Gaharu</span>
                  </div>
                  <span class="material-symbols-outlined text-sm">expand_more</span>
                </button>
                <div id="dropdownMenu1" class="hidden ml-4 mt-1 space-y-1">
                  <a href="bahan-baku" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">inventory</span>
                    <span class="text-base">Bahan Baku</span>
                  </a>
                  <a href="produksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                    <span class="text-base">Proses Produksi</span>
                  </a>
                  <a href="hasil-produksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">inventory_2</span>
                    <span class="text-base">Hasil Produksi</span>
                  </a>
                  <a href="transaksi-produk" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">hand_package</span>
                    <span class="text-base">Transaksi Produk</span>
                  </a>
                  <a href="pengeluaran-bulanan-gaharu" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span class="text-base">Pengeluaran Bulanan</span>
                  </a>
                  <a href="data-laporan" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">bar_chart</span>
                    <span class="text-base">Rincian Produk</span>
                  </a>
                  <a href="laporan-laba-rugi-gaharu" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">trending_up</span>
                    <span class="text-base">Laporan Laba Rugi</span>
                  </a>
                </div>

                <!-- Data Perusahaan Active -->
                <a href="data-perusahaan" class="flex items-center space-x-2 text-indigo-300 hover:text-white px-2 py-1 font-semibold">
                  <span class="material-symbols-outlined text-sm">business</span>
                  <span class="text-base">Data Perusahaan</span>
                </a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Kembali ke Utama -->
          <div>
            <a href="switch-2db1.php" class="flex items-center space-x-2 text-yellow-400 hover:text-yellow-500 px-3 py-2 mt-12">
              <span class="material-symbols-outlined text-sm">chevron_left</span>
              <span class="text-base">Kembali ke Utama</span>
            </a>
          </div>
        <?php endif; ?>
      </nav>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 flex flex-col min-w-0">

      <!-- Topbar -->
      <header class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between shadow">
        <div class="flex items-center space-x-3">
          <button onclick="toggleSidebar()" class="lg:hidden">
            <span class="material-symbols-outlined">menu</span>
          </button>
          <a href="data-perusahaan" class="text-gray-400 hover:text-white inline-flex items-center space-x-1 text-sm mr-2 transition">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali</span>
          </a>
          <h1 class="text-lg font-semibold truncate">Detail Perusahaan: <?= htmlspecialchars($master['nama']) ?></h1>
        </div>
        <a href="logout" class="text-sm text-gray-300 hover:text-white flex items-center space-x-1">
          <span class="material-symbols-outlined text-sm">logout</span><span>Keluar</span>
        </a>
      </header>

      <!-- Content -->
      <main class="p-4 sm:p-6 flex-1 detail-body">

        <!-- Alerts -->
        <?php if ($success): ?>
          <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded flex items-center space-x-2 text-xs">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded flex items-center space-x-2 text-xs">
            <span class="material-symbols-outlined text-base">error</span>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Master Info Banner -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-3 min-w-0">
              <div class="p-2.5 bg-indigo-50 text-indigo-700 rounded-lg shrink-0">
                <span class="material-symbols-outlined text-2xl">apartment</span>
              </div>
              <div class="min-w-0">
                <div class="flex items-center space-x-2">
                  <h2 class="text-base font-bold text-gray-800 editable-field px-1"
                    title="Double-click untuk edit nama"
                    ondblclick="startInlineEdit(this, 'master_nama', <?= $master['id'] ?>)">
                    <?= htmlspecialchars($master['nama']) ?>
                  </h2>
                  <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-100 text-indigo-800 font-semibold uppercase tracking-wider shrink-0">
                    <?= $tipe_map[$master['tipe']] ?? $master['tipe'] ?>
                  </span>
                </div>
                <div class="text-[10px] text-gray-500 mt-1 flex items-center space-x-2">
                  <span>Dibuat: <?= date('d M Y, H:i', strtotime($master['created_at'])) ?></span>
                  <span>•</span>
                  <span class="font-semibold text-gray-700" id="totalFieldCount"><?= count($detail_data) ?> Field Data</span>
                  <span>•</span>
                  <span class="text-amber-600 font-medium italic"><span class="material-symbols-outlined align-middle" style="font-size: 11px;">touch_app</span> Double-click teks untuk edit data</span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
              <!-- <button type="button" onclick="openManageAutocompleteModal()"
                class="bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 hover:border-purple-300 px-3 py-2 rounded text-xs font-semibold shadow-sm flex items-center space-x-1.5 transition"
                title="Kelola Saran Autocomplete Label">
                <span class="material-symbols-outlined text-sm text-purple-600">tune</span>
                <span>Kelola Autocomplete</span>
              </button> -->
              <a href="data-perusahaan-pdf?id=<?= $id_master ?>" target="_blank"
                class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded text-xs font-semibold shadow flex items-center space-x-1.5 transition">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                <span>Download PDF</span>
              </a>
              <button type="button" onclick="openAddDataModal()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 rounded text-xs font-semibold shadow flex items-center space-x-1.5 transition">
                <span>+ Add Data</span>
              </button>
            </div>
          </div>

          <!-- Quick Search Toolbar -->
          <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <div class="relative w-full sm:w-72">
              <span class="material-symbols-outlined absolute left-2.5 top-1.5 text-gray-400" style="font-size: 14px;">search</span>
              <input type="text" id="detailSearchInput" onkeyup="filterDetailCards(this.value)"
                placeholder="Cari label / nilai data..."
                class="w-full pl-7 pr-3 py-1 text-[11px] border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white" />
            </div>
            <div class="text-[10px] text-gray-500">
              <span id="detailMatchCount">Menampilkan <?= count($detail_data) ?> field</span>
            </div>
          </div>
        </div>

        <!-- Data Grid -->
        <div id="detailDataContainer">
          <?php if (empty($detail_data)): ?>
            <div class="bg-white rounded-xl border border-gray-200 text-center py-12 text-gray-400 shadow-sm">
              <span class="material-symbols-outlined text-4xl mb-1 text-gray-300">info</span>
              <p class="text-xs mb-3">Belum ada rincian data untuk perusahaan ini.</p>
              <button onclick="openAddDataModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-xs font-semibold shadow inline-flex items-center space-x-1">
                <span>+ Tambah Data Sekarang</span>
              </button>
            </div>
          <?php else: ?>
            <div id="detailDataGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
              <?php foreach ($detail_data as $di => $d):
                $has_subs = !empty($d['subs']);
                if ($has_subs):
                  $sub_search_arr = [];
                  foreach ($d['subs'] as $s) {
                    $sub_search_arr[] = $s['label'] . ' ' . $s['value'];
                  }
                  $sub_search_str = implode(' ', $sub_search_arr);
              ?>
                  <!-- MULTIPLE DATA CARD (Full width) -->
                  <div class="detail-card col-span-full bg-white border border-purple-200 rounded p-2.5 shadow-sm"
                    id="dataCard_<?= $d['id'] ?>"
                    data-search="<?= strtolower(htmlspecialchars($d['label'] . ' ' . $sub_search_str, ENT_QUOTES, 'UTF-8')) ?>">
                    <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-purple-100">
                      <div class="flex items-center space-x-2">
                        <span class="detail-label text-purple-800 bg-purple-50 border border-purple-200 rounded px-1.5 py-0.5 inline-block editable-field editable-label"
                          title="Double-click untuk edit label data"
                          ondblclick="startInlineEdit(this, 'data_label', <?= $d['id'] ?>)">
                          <?= htmlspecialchars($d['label']) ?>
                        </span>
                      </div>
                      <div class="flex items-center space-x-1.5">
                        <a href="data-perusahaan-pdf?id=<?= $id_master ?>&data_id=<?= $d['id'] ?>" target="_blank"
                          class="text-gray-400 hover:text-red-600 p-0.5 rounded hover:bg-red-50 transition inline-flex items-center justify-center" title="Download PDF Label Data Ini">
                          <span class="material-symbols-outlined" style="font-size: 14px;">picture_as_pdf</span>
                        </a>
                        <button type="button" onclick="promptAddSubDirect(<?= $d['id'] ?>)"
                          class="text-purple-600 hover:text-purple-800 text-[10px] font-semibold bg-purple-50 hover:bg-purple-100 px-2 py-0.5 rounded border border-purple-200 inline-flex items-center space-x-0.5 transition"
                          title="Tambah Sub-Data ke grup ini">
                          <span class="material-symbols-outlined" style="font-size: 12px;">add</span>
                          <span>Tambah Sub</span>
                        </button>
                        <button type="button" onclick="deleteItem('data', <?= $d['id'] ?>, 'Hapus seluruh grup data <?= htmlspecialchars(addslashes($d['label'])) ?>?')"
                          class="text-gray-400 hover:text-red-600 p-0.5 rounded hover:bg-red-50 transition"
                          title="Hapus Grup Data">
                          <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                        </button>
                      </div>
                    </div>

                    <!-- Sub-data list -->
                    <div class="overflow-x-auto">
                      <table class="min-w-full">
                        <tbody id="subList_<?= $d['id'] ?>" class="divide-y divide-purple-50">
                          <?php foreach ($d['subs'] as $s): ?>
                            <tr class="hover:bg-purple-50 hover:bg-opacity-50" id="subRow_<?= $s['id'] ?>">
                              <td class="py-1 px-2 detail-sub-label text-gray-600 w-1/4 whitespace-nowrap align-top">
                                <span class="editable-field px-1"
                                  title="Double-click untuk edit label sub"
                                  ondblclick="startInlineEdit(this, 'sub_label', <?= $s['id'] ?>)">
                                  <?= htmlspecialchars($s['label']) ?>
                                </span>
                              </td>
                              <td class="py-1 px-2 detail-sub-val text-gray-900 align-top break-all select-all">
                                <span class="editable-field px-1 inline-block w-full"
                                  title="Double-click untuk edit value sub"
                                  ondblclick="startInlineEdit(this, 'sub_value', <?= $s['id'] ?>)">
                                  <?= !empty($s['value']) ? nl2br(htmlspecialchars($s['value'])) : '<span class="text-gray-400 italic font-normal">-</span>' ?>
                                </span>
                              </td>
                              <td class="py-1 px-1 text-right align-top w-14 copy-btn whitespace-nowrap">
                                <?php if (!empty($s['value'])): ?>
                                  <button type="button" onclick="copyDetailText(<?= htmlspecialchars(json_encode($s['value']), ENT_QUOTES, 'UTF-8') ?>, this)"
                                    class="text-gray-400 hover:text-purple-600 p-0.5 rounded hover:bg-purple-100 transition inline-flex items-center justify-center mr-1" title="Copy">
                                    <span class="material-symbols-outlined">content_copy</span>
                                  </button>
                                <?php endif; ?>
                                <button type="button" onclick="deleteItem('subdata', <?= $s['id'] ?>, 'Hapus sub-data ini?')"
                                  class="text-gray-300 hover:text-red-500 p-0.5 rounded hover:bg-red-50 transition inline-flex items-center justify-center" title="Hapus sub-data">
                                  <span class="material-symbols-outlined" style="font-size: 13px;">delete</span>
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                <?php else:
                  $val = $d['value'] ?? '';
                  $is_long = strlen($val) > 60 || strpos($val, "\n") !== false;
                  $col_span = $is_long ? 'col-span-1 sm:col-span-2' : 'col-span-1';
                ?>
                  <!-- SINGLE DATA CARD -->
                  <div class="detail-card <?= $col_span ?> bg-white border border-gray-200 hover:border-indigo-300 rounded p-2 shadow-sm transition flex flex-col justify-between"
                    id="dataCard_<?= $d['id'] ?>"
                    data-search="<?= strtolower(htmlspecialchars($d['label'] . ' ' . $val, ENT_QUOTES, 'UTF-8')) ?>">
                    <div>
                      <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="detail-label text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded inline-block truncate max-w-[75%] editable-field editable-label"
                          title="Double-click untuk edit label data"
                          ondblclick="startInlineEdit(this, 'data_label', <?= $d['id'] ?>)">
                          <?= htmlspecialchars($d['label']) ?>
                        </span>
                        <div class="flex items-center space-x-1 shrink-0">
                          <button type="button" onclick="openEditSingleDataModal(<?= $d['id'] ?>, <?= htmlspecialchars(json_encode($d['label']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8') ?>)"
                            class="text-gray-400 hover:text-indigo-600 p-0.5 rounded hover:bg-indigo-50 transition inline-flex items-center justify-center" title="Edit Data Ini">
                            <span class="material-symbols-outlined" style="font-size: 13px;">edit</span>
                          </button>
                          <a href="data-perusahaan-pdf?id=<?= $id_master ?>&data_id=<?= $d['id'] ?>" target="_blank"
                            class="text-gray-400 hover:text-red-600 p-0.5 rounded hover:bg-red-50 transition inline-flex items-center justify-center" title="Download PDF Label Data Ini">
                            <span class="material-symbols-outlined" style="font-size: 13px;">picture_as_pdf</span>
                          </a>
                          <?php if (!empty($val)): ?>
                            <div class="copy-btn">
                              <button type="button" onclick="copyDetailText(<?= htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8') ?>, this)"
                                class="text-gray-400 hover:text-indigo-600 p-0.5 rounded hover:bg-indigo-50 transition inline-flex items-center justify-center" title="Copy">
                                <span class="material-symbols-outlined">content_copy</span>
                              </button>
                            </div>
                          <?php endif; ?>
                          <button type="button" onclick="deleteItem('data', <?= $d['id'] ?>, 'Hapus data <?= htmlspecialchars(addslashes($d['label'])) ?>?')"
                            class="text-gray-300 hover:text-red-500 p-0.5 rounded hover:bg-red-50 transition inline-flex items-center justify-center" title="Hapus Data">
                            <span class="material-symbols-outlined" style="font-size: 13px;">delete</span>
                          </button>
                        </div>
                      </div>
                      <div class="detail-val text-gray-800 break-words select-all px-0.5 mt-1">
                        <span class="editable-field px-1 inline-block w-full"
                          title="Double-click untuk edit nilai"
                          ondblclick="startInlineEdit(this, 'data_value', <?= $d['id'] ?>)">
                          <?= !empty($val) ? nl2br(htmlspecialchars($val)) : '<span class="text-gray-400 font-normal italic" style="font-size: 10px;">-</span>' ?>
                        </span>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>

              <div id="detailEmptySearch" class="hidden col-span-full text-center py-6 text-gray-400" style="font-size: 11px;">
                Tidak ada data yang cocok dengan pencarian.
              </div>
            </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <!-- ===================================================
     MODAL ADD DATA (Requirement 3: Sama seperti create data)
     =================================================== -->
  <div id="modalAddData" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4" onclick="if(event.target===this) closeAddDataModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl mx-auto modal-80vh overflow-y-auto" style="max-height: 80vh;">
      <div class="bg-indigo-600 text-white px-5 py-3 rounded-t-lg flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <h3 class="text-base font-semibold flex items-center space-x-2">
          <span class="material-symbols-outlined text-lg">add_circle</span>
          <span>Add Data untuk: <?= htmlspecialchars($master['nama']) ?></span>
        </h3>
        <button type="button" onclick="closeAddDataModal()" class="hover:text-indigo-200">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <form method="POST" action="detail-perusahaan?id=<?= $id_master ?>" class="p-5 space-y-3">
        <input type="hidden" name="action" value="add_data" />

        <!-- Container baris-baris data baru -->
        <div id="addDataRows" class="space-y-3"></div>

        <button type="button" onclick="addNewDataRow()"
          class="flex items-center space-x-1 text-indigo-600 hover:text-indigo-800 text-xs font-semibold border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 rounded px-3 py-1.5 transition-colors">
          <span>+ Add Data Row Lainnya</span>
        </button>

        <div class="flex justify-end space-x-3 pt-3 border-t border-gray-100 sticky bottom-0 bg-white z-10 pb-1">
          <button type="button" onclick="closeAddDataModal()" class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800">Batal</button>
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded text-xs font-semibold shadow">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================
     MODAL EDIT DATA TUNGGAL (Layout sama seperti modal Add Data)
     =================================================== -->
  <div id="modalEditSingleData" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4" onclick="if(event.target===this) closeEditSingleDataModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-auto modal-80vh overflow-y-auto" style="max-height: 80vh;">
      <div class="bg-indigo-600 text-white px-5 py-3 rounded-t-lg flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <h3 class="text-base font-semibold flex items-center space-x-2">
          <span class="material-symbols-outlined text-lg">edit</span>
          <span id="editSingleModalTitle">Edit Data</span>
        </h3>
        <button type="button" onclick="closeEditSingleDataModal()" class="hover:text-indigo-200">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <form id="formEditSingleData" method="POST" action="detail-perusahaan?id=<?= $id_master ?>" class="p-5 space-y-3" onsubmit="return handleEditSingleFormSubmit(event)">
        <input type="hidden" name="action" value="edit_single_data" />
        <input type="hidden" name="data_id" id="editSingleDataId" value="" />

        <div class="data-row bg-gray-50 p-3 rounded space-y-2 border border-gray-200">
          <div class="flex items-start space-x-2">
            <div class="flex-1 grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Label Data (Autocomplete)</label>
                <input type="text" name="data_label" id="editSingleDataLabel" list="labelDatalist" autocomplete="off"
                  placeholder="Ketik atau pilih label..."
                  onchange="handleLabelAutocomplete(this)"
                  class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" required />
              </div>
              <div>
                <div class="value-field-wrap" id="editSingleValueWrap">
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Value</label>
                  <input type="text" name="data_value" id="editSingleDataValue" placeholder="Isi value..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
                </div>
                <div class="multiple-data-badge hidden mb-1" id="editSingleMultipleBadge">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-semibold">
                    <span class="material-symbols-outlined text-xs mr-1">splitscreen</span> Multiple Data Aktif
                  </span>
                </div>
                <div class="mt-1">
                  <button type="button" id="btnToggleEditSingleMultiple" onclick="toggleSubEditSingle()"
                    class="btn-toggle-multiple text-xs text-purple-600 hover:text-purple-800 font-medium hover:underline inline-flex items-center">
                    <span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div id="subEditSingleContainer" class="hidden ml-4 pl-3 border-l-2 border-purple-300 space-y-2 mt-2">
            <div class="text-xs font-semibold text-purple-700 mb-1">Sub Data (Multiple Data)</div>
            <div id="editSingleSubRows" class="space-y-2"></div>
            <button type="button" onclick="addSubRowToEditSingle()"
              class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1 font-medium bg-purple-50 hover:bg-purple-100 px-2 py-1 rounded border border-purple-200">
              <span class="material-symbols-outlined text-sm">add</span><span>Tambah sub-data</span>
            </button>
          </div>
        </div>

        <div class="flex justify-end space-x-3 pt-3 border-t border-gray-100 sticky bottom-0 bg-white z-10 pb-1">
          <button type="button" onclick="closeEditSingleDataModal()" class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800">Batal</button>
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded text-xs font-semibold shadow">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================
     MODAL KELOLA AUTOCOMPLETE LABEL
     =================================================== -->
  <div id="modalManageAutocomplete" onclick="if(event.target===this) closeManageAutocompleteModal()"
    class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto modal-80vh overflow-y-auto" style="max-height: 85vh;">
      <div class="bg-purple-700 text-white px-5 py-3.5 rounded-t-lg flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <h3 class="text-sm font-semibold flex items-center space-x-2">
          <span class="material-symbols-outlined text-lg">tune</span>
          <span>Kelola Autocomplete Label</span>
        </h3>
        <button type="button" onclick="closeManageAutocompleteModal()" class="hover:text-purple-200">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <div class="p-5 space-y-3.5">
        <p class="text-xs text-gray-500 leading-relaxed">
          Daftar label yang tersimpan untuk saran autocomplete pada input label perusahaan dan rincian data.
        </p>

        <!-- Search / Filter input -->
        <div class="relative">
          <span class="material-symbols-outlined absolute left-2.5 top-2 text-gray-400 text-sm">search</span>
          <input type="text" id="searchAutocompleteInput" oninput="filterAutocompleteList(this.value)"
            placeholder="Cari label tersimpan..."
            class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500 bg-gray-50 focus:bg-white transition" />
        </div>

        <!-- Add new label directly in modal -->
        <div class="flex items-center space-x-1.5 bg-purple-50 p-2 rounded-lg border border-purple-100">
          <input type="text" id="inputNewAutocompleteLabel" placeholder="Tambah label baru..."
            class="flex-1 border border-purple-300 rounded px-2.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
          <button type="button" onclick="submitAddAutocompleteLabel()"
            class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-semibold shadow-sm transition flex items-center space-x-1 shrink-0">
            <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
            <span>Tambah</span>
          </button>
        </div>

        <!-- Label list container -->
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
          <div class="bg-gray-50 px-3 py-2 text-[11px] font-semibold text-gray-600 flex justify-between items-center border-b border-gray-200">
            <span>Daftar Label Autocomplete</span>
            <span id="autocompleteCountBadge" class="text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full font-bold">0 label</span>
          </div>
          <div id="autocompleteListContainer" class="max-h-64 overflow-y-auto divide-y divide-gray-100">
            <!-- Rendered by JS -->
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-gray-100">
          <button type="button" onclick="closeManageAutocompleteModal()"
            class="px-4 py-2 text-xs font-medium text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================================================
     MODAL TAMBAH SUB-DATA LANGSUNG KE GRUP TERTENTU
     =================================================== -->
  <div id="modalAddSubDirect" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4" onclick="if(event.target===this) closeAddSubDirectModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto p-4 space-y-3">
      <div class="flex items-center justify-between pb-2 border-b border-gray-200">
        <h4 class="text-xs font-bold text-gray-800 flex items-center space-x-1">
          <span class="material-symbols-outlined text-purple-600 text-base">add_circle</span>
          <span>Tambah Sub-Data</span>
        </h4>
        <button type="button" onclick="closeAddSubDirectModal()" class="text-gray-400 hover:text-gray-600">
          <span class="material-symbols-outlined text-base">close</span>
        </button>
      </div>
      <input type="hidden" id="directSubDataId" value="" />
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Label Sub (Autocomplete)</label>
        <input type="text" id="directSubLabel" list="labelDatalist" autocomplete="off"
          placeholder="Contoh: Nomor Rekening / Password..."
          onchange="handleLabelAutocomplete(this)"
          class="w-full border border-purple-300 rounded px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Value Sub</label>
        <input type="text" id="directSubValue" placeholder="Isi value..."
          class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
      </div>
      <div class="flex justify-end space-x-2 pt-2 border-t border-gray-100">
        <button type="button" onclick="closeAddSubDirectModal()" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800">Batal</button>
        <button type="button" onclick="submitAddSubDirect()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-1.5 rounded text-xs font-semibold shadow">Simpan Sub-Data</button>
      </div>
    </div>
  </div>

  <!-- ===================================================
     MODAL KONFIRMASI EDIT DATA (PREVENTIF)
     =================================================== -->
  <div id="modalConfirmEdit" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4" onclick="if(event.target===this) closeConfirmEditModal()">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto p-5 space-y-4 border border-gray-100">
      <!-- Header -->
      <div class="flex items-center space-x-3 pb-3 border-b border-gray-100">
        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">help</span>
        </div>
        <div>
          <h4 class="text-sm font-bold text-gray-800">Konfirmasi Perubahan Data</h4>
          <p class="text-[11px] text-gray-500">Tinjau kembali perubahan sebelum disimpan</p>
        </div>
      </div>

      <!-- Tampilan Data Sebelum & Sesudah -->
      <div class="space-y-2 bg-gray-50 rounded-lg p-3 border border-gray-200 text-xs">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 mb-1 flex items-center space-x-1">
            <span class="material-symbols-outlined" style="font-size: 13px;">history</span>
            <span>Data Sebelum:</span>
          </span>
          <div id="confirmSebelumBox" class="bg-red-50 text-red-900 border border-red-200 rounded px-2.5 py-1.5 break-words max-h-24 overflow-y-auto font-medium whitespace-pre-wrap"></div>
        </div>

        <div class="flex justify-center text-gray-400 my-0.5">
          <span class="material-symbols-outlined text-base">arrow_downward</span>
        </div>

        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-green-600 mb-1 flex items-center space-x-1">
            <span class="material-symbols-outlined" style="font-size: 13px;">update</span>
            <span>Data Sesudah:</span>
          </span>
          <div id="confirmSesudahBox" class="bg-green-50 text-green-900 border border-green-200 rounded px-2.5 py-1.5 break-words max-h-24 overflow-y-auto font-semibold whitespace-pre-wrap"></div>
        </div>
      </div>

      <!-- Pertanyaan Konfirmasi -->
      <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3">
        <p id="confirmPromptText" class="text-xs text-indigo-950 leading-relaxed text-center font-medium"></p>
      </div>

      <!-- Tombol Aksi -->
      <div class="flex justify-end space-x-2 pt-2 border-t border-gray-100">
        <button type="button" onclick="closeConfirmEditModal()"
          class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-md font-medium transition">
          Batal
        </button>
        <button type="button" id="btnConfirmExecute" onclick="confirmExecuteHandler()"
          class="px-4 py-2 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded-md font-semibold shadow transition inline-flex items-center space-x-1">
          <span class="material-symbols-outlined text-sm">check</span>
          <span>Ya, Ubah</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="copyToast" class="fixed bottom-5 right-5 z-50 bg-gray-900 text-white text-[11px] px-3.5 py-2 rounded-lg shadow-xl transition-all duration-300 opacity-0 pointer-events-none flex items-center space-x-1.5 border border-gray-700">
    <span class="material-symbols-outlined text-sm text-green-400">check_circle</span>
    <span id="copyToastText">Disalin ke clipboard!</span>
  </div>

  <!-- ===================================================
     JAVASCRIPT: Double-Click Inline Edit & Modals
     =================================================== -->
  <script>
    let ALL_LABELS = <?= $labels_json ?>;
    const MASTER_ID = <?= $id_master ?>;

    /* ---- Double Click Inline Editing (Requirement 2) ---- */
    let activeInlineEditor = null;

    function startInlineEdit(element, type, id) {
      if (activeInlineEditor) {
        // Cancel active one before opening a new one
        cancelInlineEdit();
      }

      const origText = element.innerText.trim();
      const rawVal = (origText === '-' || origText === 'Tidak ada nilai') ? '' : origText;

      activeInlineEditor = {
        element: element,
        type: type,
        id: id,
        originalHtml: element.innerHTML,
        originalText: origText,
        rawVal: rawVal
      };

      // Buat container inline edit
      const wrap = document.createElement('div');
      wrap.className = 'inline-edit-wrapper flex items-center space-x-1 w-full my-0.5';

      // Input field
      const input = document.createElement('input');
      input.type = 'text';
      input.value = rawVal;
      input.className = 'inline-edit-input flex-1 border border-indigo-400 rounded px-1.5 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white shadow-inner';

      // Jika label, pasang datalist autocomplete
      if (type.includes('label')) {
        input.setAttribute('list', 'labelDatalist');
        input.setAttribute('autocomplete', 'off');
        input.onchange = function() {
          handleLabelAutocomplete(this);
        };
      }

      // Tombol Centang (Save)
      const btnSave = document.createElement('button');
      btnSave.type = 'button';
      btnSave.title = 'Simpan Perubahan';
      btnSave.className = 'btn-save-inline bg-green-600 hover:bg-green-700 text-white p-1 rounded shadow-xs flex items-center justify-center';
      btnSave.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; line-height: 1;">check</span>';
      btnSave.onclick = function(e) {
        e.stopPropagation();
        requestInlineEdit(input.value);
      };

      // Tombol Cancel
      const btnCancel = document.createElement('button');
      btnCancel.type = 'button';
      btnCancel.title = 'Batal Edit';
      btnCancel.className = 'btn-cancel-inline bg-gray-200 hover:bg-gray-300 text-gray-700 p-1 rounded shadow-xs flex items-center justify-center';
      btnCancel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; line-height: 1;">close</span>';
      btnCancel.onclick = function(e) {
        e.stopPropagation();
        cancelInlineEdit();
      };

      // Keyboard listeners
      input.onkeydown = function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          requestInlineEdit(input.value);
        } else if (e.key === 'Escape') {
          e.preventDefault();
          cancelInlineEdit();
        }
      };

      wrap.appendChild(input);
      wrap.appendChild(btnSave);
      wrap.appendChild(btnCancel);

      element.style.display = 'none';
      element.parentNode.insertBefore(wrap, element);
      input.focus();
      input.select();
    }

    function cancelInlineEdit() {
      if (!activeInlineEditor) return;
      const wrap = activeInlineEditor.element.parentNode.querySelector('.inline-edit-wrapper');
      if (wrap) wrap.remove();
      activeInlineEditor.element.style.display = '';
      activeInlineEditor = null;
    }

    function escapeHtml(text) {
      if (!text) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    let onConfirmExecute = null;
    let pendingInlineUpdate = null;

    function confirmExecuteHandler() {
      if (typeof onConfirmExecute === 'function') {
        onConfirmExecute();
      } else {
        executeInlineEdit();
      }
    }

    function closeConfirmEditModal() {
      const modal = document.getElementById('modalConfirmEdit');
      if (modal) modal.classList.add('hidden');
      pendingInlineUpdate = null;
      onConfirmExecute = null;
      if (activeInlineEditor && activeInlineEditor.element) {
        const inp = activeInlineEditor.element.parentNode.querySelector('.inline-edit-wrapper input');
        if (inp) inp.focus();
      }
    }

    /* ---- Preventif Konfirmasi Perubahan Data ---- */
    function requestInlineEdit(newVal) {
      if (!activeInlineEditor) return;
      const cleanNewVal = newVal.trim();
      const orig = activeInlineEditor.rawVal;

      if (cleanNewVal === orig) {
        cancelInlineEdit();
        return;
      }

      if ((activeInlineEditor.type.includes('label') || activeInlineEditor.type === 'master_nama') && cleanNewVal === '') {
        alert('Label / Nama tidak boleh kosong.');
        return;
      }

      const displayBefore = orig !== '' ? orig : '(Kosong)';
      const displayAfter = cleanNewVal !== '' ? cleanNewVal : '(Kosong)';

      pendingInlineUpdate = {
        element: activeInlineEditor.element,
        type: activeInlineEditor.type,
        id: activeInlineEditor.id,
        newVal: cleanNewVal,
        oldVal: orig
      };

      document.getElementById('confirmSebelumBox').textContent = displayBefore;
      document.getElementById('confirmSesudahBox').textContent = displayAfter;
      document.getElementById('confirmPromptText').innerHTML =
        `Apakah anda yakin mengubah "<span class="text-red-600 font-bold">${escapeHtml(displayBefore)}</span>" menjadi "<span class="text-green-600 font-bold">${escapeHtml(displayAfter)}</span>"?`;

      onConfirmExecute = executeInlineEdit;

      document.getElementById('modalConfirmEdit').classList.remove('hidden');
      setTimeout(() => {
        const btn = document.getElementById('btnConfirmExecute');
        if (btn) btn.focus();
      }, 50);
    }

    function executeInlineEdit() {
      if (!pendingInlineUpdate) return;
      const {
        element,
        type,
        id,
        newVal
      } = pendingInlineUpdate;

      const btnConfirm = document.getElementById('btnConfirmExecute');
      const origBtnHtml = btnConfirm ? btnConfirm.innerHTML : '';
      if (btnConfirm) {
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> <span>Menyimpan...</span>';
      }

      const fd = new FormData();
      fd.append('type', type);
      fd.append('id', id);
      fd.append('val', newVal);

      fetch(`detail-perusahaan?id=${MASTER_ID}&ajax=inline_update`, {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = origBtnHtml;
          }
          closeConfirmEditModal();

          if (res.ok) {
            const wrap = element.parentNode.querySelector('.inline-edit-wrapper');
            if (wrap) wrap.remove();
            element.innerText = res.val || '-';
            element.style.display = '';

            // Flash green highlight
            element.classList.add('bg-green-100');
            setTimeout(() => element.classList.remove('bg-green-100'), 1200);
            showToast('Perubahan berhasil disimpan');

            // If label, register in datalist
            if (type.includes('label') && res.val) {
              updateDatalists(res.val);
            }
            activeInlineEditor = null;
          } else {
            alert(res.msg || 'Gagal menyimpan perubahan');
          }
        })
        .catch(err => {
          console.error(err);
          if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = origBtnHtml;
          }
          closeConfirmEditModal();
          alert('Terjadi kesalahan koneksi.');
        });
    }

    /* ---- Modal Edit Single Data Card ---- */
    let editSingleOrigData = {
      id: 0,
      label: '',
      value: ''
    };

    function openEditSingleDataModal(id, label, value) {
      editSingleOrigData = {
        id,
        label,
        value: value || ''
      };
      document.getElementById('editSingleDataId').value = id;
      document.getElementById('editSingleDataLabel').value = label;
      document.getElementById('editSingleDataValue').value = value || '';
      document.getElementById('editSingleModalTitle').textContent = 'Edit Data: ' + label;

      const sub = document.getElementById('subEditSingleContainer');
      const valWrap = document.getElementById('editSingleValueWrap');
      const badgeWrap = document.getElementById('editSingleMultipleBadge');
      const btn = document.getElementById('btnToggleEditSingleMultiple');
      const subRows = document.getElementById('editSingleSubRows');

      sub.classList.add('hidden');
      valWrap.classList.remove('hidden');
      badgeWrap.classList.add('hidden');
      btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
      btn.classList.remove('text-red-600');
      btn.classList.add('text-purple-600');
      subRows.innerHTML = '';

      document.getElementById('modalEditSingleData').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeEditSingleDataModal() {
      document.getElementById('modalEditSingleData').classList.add('hidden');
      document.body.style.overflow = '';
    }

    function toggleSubEditSingle() {
      const sub = document.getElementById('subEditSingleContainer');
      const valWrap = document.getElementById('editSingleValueWrap');
      const valInp = document.getElementById('editSingleDataValue');
      const badgeWrap = document.getElementById('editSingleMultipleBadge');
      const btn = document.getElementById('btnToggleEditSingleMultiple');

      sub.classList.toggle('hidden');
      const isMultiple = !sub.classList.contains('hidden');

      if (isMultiple) {
        valWrap.classList.add('hidden');
        valInp.value = '';
        badgeWrap.classList.remove('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">close</span> Batalkan Multiple Data';
        btn.classList.add('text-red-600');
        btn.classList.remove('text-purple-600');
        if (document.getElementById('editSingleSubRows').children.length === 0) {
          addSubRowToEditSingle();
        }
      } else {
        valWrap.classList.remove('hidden');
        badgeWrap.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
        btn.classList.remove('text-red-600');
        btn.classList.add('text-purple-600');
      }
    }

    function addSubRowToEditSingle() {
      const container = document.getElementById('editSingleSubRows');
      const div = document.createElement('div');
      div.className = 'subdata-row bg-white p-2.5 rounded shadow-sm flex items-start space-x-2 border border-purple-100';
      div.innerHTML = `
        <div class="flex-1 grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Label Sub (Autocomplete)</label>
            <input type="text" name="edit_sub_label[]" list="labelDatalist" autocomplete="off"
                   placeholder="Ketik atau pilih label..."
                   onchange="handleLabelAutocomplete(this)"
                   class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Value Sub</label>
            <input type="text" name="edit_sub_value[]" placeholder="Isi value..."
                   class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
          </div>
        </div>
        <button type="button" onclick="this.closest('.subdata-row').remove()"
                class="text-red-400 hover:text-red-600 mt-5 p-1" title="Hapus sub-data">
          <span class="material-symbols-outlined text-base">close</span>
        </button>`;
      container.appendChild(div);
    }

    function handleEditSingleFormSubmit(e) {
      e.preventDefault();
      const newLabel = document.getElementById('editSingleDataLabel').value.trim();
      const newValue = document.getElementById('editSingleDataValue').value.trim();
      const isMultiple = !document.getElementById('subEditSingleContainer').classList.contains('hidden');

      if (!newLabel) {
        alert('Label data tidak boleh kosong.');
        return false;
      }

      // Jika tidak ada perubahan dan tidak multiple
      if (!isMultiple && newLabel === editSingleOrigData.label && newValue === editSingleOrigData.value) {
        closeEditSingleDataModal();
        return false;
      }

      let displayBefore = '';
      let displayAfter = '';

      if (newLabel !== editSingleOrigData.label && newValue !== editSingleOrigData.value) {
        displayBefore = editSingleOrigData.label + ' : ' + (editSingleOrigData.value || '(Kosong)');
        displayAfter = newLabel + ' : ' + (newValue || '(Kosong)');
      } else if (newLabel !== editSingleOrigData.label) {
        displayBefore = editSingleOrigData.label;
        displayAfter = newLabel;
      } else if (isMultiple) {
        displayBefore = editSingleOrigData.value || '(Data Tunggal)';
        displayAfter = 'Dikonversi ke Multiple Data';
      } else {
        displayBefore = editSingleOrigData.value !== '' ? editSingleOrigData.value : '(Kosong)';
        displayAfter = newValue !== '' ? newValue : '(Kosong)';
      }

      document.getElementById('confirmSebelumBox').textContent = displayBefore;
      document.getElementById('confirmSesudahBox').textContent = displayAfter;
      document.getElementById('confirmPromptText').innerHTML =
        `Apakah anda yakin mengubah "<span class="text-red-600 font-bold">${escapeHtml(displayBefore)}</span>" menjadi "<span class="text-green-600 font-bold">${escapeHtml(displayAfter)}</span>"?`;

      onConfirmExecute = function() {
        document.getElementById('formEditSingleData').submit();
      };

      document.getElementById('modalConfirmEdit').classList.remove('hidden');
      setTimeout(() => {
        const btn = document.getElementById('btnConfirmExecute');
        if (btn) btn.focus();
      }, 50);

      return false;
    }

    /* ---- Delete Item (Single Data / Sub-Data) ---- */
    function deleteItem(type, id, confirmMsg) {
      if (!confirm(confirmMsg || 'Yakin ingin menghapus item ini?')) return;
      const fd = new FormData();
      fd.append('type', type);
      fd.append('id', id);

      fetch(`detail-perusahaan?id=${MASTER_ID}&ajax=delete_item`, {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            if (type === 'data') {
              const card = document.getElementById('dataCard_' + id);
              if (card) card.remove();
            } else if (type === 'subdata') {
              const row = document.getElementById('subRow_' + id);
              if (row) row.remove();
            }
            showToast('Data berhasil dihapus');
          } else {
            alert('Gagal menghapus data.');
          }
        })
        .catch(console.error);
    }

    /* ---- Quick Add Sub Direct ---- */
    function promptAddSubDirect(dataId) {
      document.getElementById('directSubDataId').value = dataId;
      document.getElementById('directSubLabel').value = '';
      document.getElementById('directSubValue').value = '';
      document.getElementById('modalAddSubDirect').classList.remove('hidden');
      setTimeout(() => document.getElementById('directSubLabel').focus(), 100);
    }

    function closeAddSubDirectModal() {
      document.getElementById('modalAddSubDirect').classList.add('hidden');
    }

    function submitAddSubDirect() {
      const idData = document.getElementById('directSubDataId').value;
      const slbl = document.getElementById('directSubLabel').value.trim();
      const sval = document.getElementById('directSubValue').value.trim();

      if (!slbl) {
        alert('Label sub wajib diisi');
        return;
      }

      const fd = new FormData();
      fd.append('id_data', idData);
      fd.append('label', slbl);
      fd.append('value', sval);

      fetch(`detail-perusahaan?id=${MASTER_ID}&ajax=add_sub_direct`, {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            closeAddSubDirectModal();
            showToast('Sub-data berhasil ditambahkan');
            // Reload page to re-render cleanly
            window.location.reload();
          } else {
            alert(res.msg || 'Gagal menambahkan sub-data');
          }
        })
        .catch(console.error);
    }

    /* ---- Autocomplete & Master Label Helper ---- */
    function handleLabelAutocomplete(inp) {
      const val = inp.value.trim();
      if (!val) return;
      if (!ALL_LABELS.some(l => l.nama_label.toLowerCase() === val.toLowerCase())) {
        const fd = new FormData();
        fd.append('nama_label', val);
        fetch(window.location.pathname + '?ajax=add_label', {
            method: 'POST',
            body: fd
          })
          .then(r => r.json())
          .then(json => {
            if (json.ok) {
              ALL_LABELS.push({
                id: json.id,
                nama_label: json.nama
              });
              updateDatalists(json.nama);
            }
          })
          .catch(console.error);
      }
    }

    function updateDatalists(labelName) {
      const dl = document.getElementById('labelDatalist');
      if (dl) {
        let exists = false;
        for (let opt of dl.options) {
          if (opt.value.toLowerCase() === labelName.toLowerCase()) {
            exists = true;
            break;
          }
        }
        if (!exists) {
          const opt = document.createElement('option');
          opt.value = labelName;
          dl.appendChild(opt);
        }
      }
    }

    function escHtml(str) {
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ---- Modal Kelola Autocomplete Label ---- */
    function openManageAutocompleteModal() {
      renderAutocompleteList();
      const inp = document.getElementById('searchAutocompleteInput');
      if (inp) inp.value = '';
      const newInp = document.getElementById('inputNewAutocompleteLabel');
      if (newInp) newInp.value = '';
      document.getElementById('modalManageAutocomplete').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeManageAutocompleteModal() {
      document.getElementById('modalManageAutocomplete').classList.add('hidden');
      document.body.style.overflow = '';
    }

    function filterAutocompleteList(keyword) {
      renderAutocompleteList(keyword);
    }

    function renderAutocompleteList(filterText = '') {
      const container = document.getElementById('autocompleteListContainer');
      const countBadge = document.getElementById('autocompleteCountBadge');
      if (!container) return;
      container.innerHTML = '';

      filterText = (filterText || '').toLowerCase().trim();
      const filtered = ALL_LABELS.filter(l => (l.nama_label || '').toLowerCase().includes(filterText));

      if (countBadge) {
        countBadge.textContent = `${filtered.length} label`;
      }

      if (filtered.length === 0) {
        container.innerHTML = `
          <div class="px-4 py-8 text-center text-gray-400 text-xs">
            ${filterText ? 'Tidak ada label yang cocok dengan pencarian.' : 'Belum ada label tersimpan.'}
          </div>
        `;
        return;
      }

      const sorted = [...filtered].sort((a, b) => (a.nama_label || '').localeCompare(b.nama_label || ''));

      sorted.forEach(item => {
        const row = document.createElement('div');
        row.className = 'px-3.5 py-2 flex items-center justify-between hover:bg-purple-50 hover:bg-opacity-40 transition-colors text-xs';

        const labelText = escHtml(item.nama_label || '');
        const escapedForOnClick = String(item.nama_label || '').replace(/'/g, "\\'");
        row.innerHTML = `
          <div class="flex items-center space-x-2 font-medium text-gray-700">
            <span class="material-symbols-outlined text-purple-400 text-sm">label</span>
            <span>${labelText}</span>
          </div>
          <button type="button" onclick="deleteAutocompleteLabel(${item.id || 0}, '${escapedForOnClick}')"
            class="p-1 bg-red-500 hover:bg-red-600 text-white rounded shadow-sm transition flex items-center justify-center"
            title="Hapus label '${labelText}' dari autocomplete">
            <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
          </button>
        `;
        container.appendChild(row);
      });
    }

    function deleteAutocompleteLabel(id, labelName) {
      if (!confirm(`Hapus label "${labelName}" dari daftar autocomplete?`)) return;

      const fd = new FormData();
      if (id) fd.append('id', id);
      fd.append('nama_label', labelName);

      fetch(window.location.pathname + '?ajax=delete_label', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            const idx = ALL_LABELS.findIndex(l => (id && l.id === id) || (l.nama_label || '').toLowerCase() === labelName.toLowerCase());
            if (idx !== -1) {
              ALL_LABELS.splice(idx, 1);
            }

            const dl = document.getElementById('labelDatalist');
            if (dl) {
              for (let i = dl.options.length - 1; i >= 0; i--) {
                if (dl.options[i].value.toLowerCase() === labelName.toLowerCase()) {
                  dl.options[i].remove();
                }
              }
            }

            const currentFilter = document.getElementById('searchAutocompleteInput')?.value || '';
            renderAutocompleteList(currentFilter);
            showToast(`Label "${labelName}" berhasil dihapus.`);
          } else {
            alert(res.msg || 'Gagal menghapus label.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi saat menghapus label.');
        });
    }

    function submitAddAutocompleteLabel() {
      const inp = document.getElementById('inputNewAutocompleteLabel');
      if (!inp) return;
      const val = inp.value.trim();
      if (!val || val === '__new__') {
        alert('Silakan ketik nama label yang ingin ditambahkan.');
        inp.focus();
        return;
      }

      if (ALL_LABELS.some(l => (l.nama_label || '').toLowerCase() === val.toLowerCase())) {
        alert('Label ini sudah ada di daftar autocomplete.');
        inp.focus();
        return;
      }

      const fd = new FormData();
      fd.append('nama_label', val);

      fetch(window.location.pathname + '?ajax=add_label', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(json => {
          if (json.ok) {
            ALL_LABELS.push({
              id: json.id,
              nama_label: json.nama
            });
            updateDatalists(json.nama);
            inp.value = '';
            const currentFilter = document.getElementById('searchAutocompleteInput')?.value || '';
            renderAutocompleteList(currentFilter);
            showToast(`Label "${json.nama}" berhasil ditambahkan.`);
          } else {
            alert(json.msg || 'Gagal menambahkan label.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi.');
        });
    }

    /* ---- Add Data Modal (Requirement 3) ---- */
    let addRowIdx = 0;

    function openAddDataModal() {
      document.getElementById('modalAddData').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      if (document.getElementById('addDataRows').children.length === 0) {
        addNewDataRow();
      }
    }

    function closeAddDataModal() {
      document.getElementById('modalAddData').classList.add('hidden');
      document.body.style.overflow = '';
    }

    function addNewDataRow() {
      const idx = addRowIdx++;
      const container = document.getElementById('addDataRows');
      const div = document.createElement('div');
      div.className = 'data-row bg-gray-50 p-3 rounded space-y-2 border border-gray-200';
      div.innerHTML = `
    <div class="flex items-start space-x-2">
      <div class="flex-1 grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Label Data (Autocomplete)</label>
          <input type="text" name="data_label[]" list="labelDatalist" autocomplete="off"
                 placeholder="Ketik atau pilih label..."
                 onchange="handleLabelAutocomplete(this)"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
        </div>
        <div>
          <div class="value-field-wrap">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Value</label>
            <input type="text" name="data_value[]" placeholder="Isi value..."
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"/>
          </div>
          <div class="multiple-data-badge hidden mb-1">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-semibold">
              <span class="material-symbols-outlined text-xs mr-1">splitscreen</span> Multiple Data Aktif
            </span>
          </div>
          <div class="mt-1">
            <button type="button" onclick="toggleSubAdd(this, ${idx})"
                    class="btn-toggle-multiple text-xs text-purple-600 hover:text-purple-800 font-medium hover:underline inline-flex items-center">
              <span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data
            </button>
          </div>
        </div>
      </div>
      <div class="mt-5">
        <button type="button" onclick="this.closest('.data-row').remove()"
                class="text-red-400 hover:text-red-600 p-1" title="Hapus baris data">
          <span class="material-symbols-outlined text-base">delete</span>
        </button>
      </div>
    </div>
    <div id="subAdd_${idx}" class="hidden ml-4 pl-3 border-l-2 border-purple-300 space-y-2 mt-2">
      <div class="text-xs font-semibold text-purple-700 mb-1">Sub Data (Multiple Data)</div>
      <div class="add-sub-rows space-y-2"></div>
      <button type="button" onclick="addSubRowToModal(${idx})"
              class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1 font-medium bg-purple-50 hover:bg-purple-100 px-2 py-1 rounded border border-purple-200">
        <span class="material-symbols-outlined text-sm">add</span><span>Tambah sub-data</span>
      </button>
    </div>`;
      container.appendChild(div);
    }

    function toggleSubAdd(btn, idx) {
      const row = btn.closest('.data-row');
      const sub = document.getElementById('subAdd_' + idx);
      const valWrap = row.querySelector('.value-field-wrap');
      const valInp = valWrap.querySelector('input');
      const badgeWrap = row.querySelector('.multiple-data-badge');

      sub.classList.toggle('hidden');
      const isMultiple = !sub.classList.contains('hidden');

      if (isMultiple) {
        valWrap.classList.add('hidden');
        valInp.value = '';
        badgeWrap.classList.remove('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">close</span> Batalkan Multiple Data';
        btn.classList.add('text-red-600');
        btn.classList.remove('text-purple-600');
        if (sub.querySelector('.add-sub-rows').children.length === 0) {
          addSubRowToModal(idx);
        }
      } else {
        valWrap.classList.remove('hidden');
        badgeWrap.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
        btn.classList.remove('text-red-600');
        btn.classList.add('text-purple-600');
      }
    }

    function addSubRowToModal(idx) {
      const container = document.querySelector(`#subAdd_${idx} .add-sub-rows`);
      const div = document.createElement('div');
      div.className = 'subdata-row bg-white p-2.5 rounded shadow-sm flex items-start space-x-2 border border-purple-100';
      div.innerHTML = `
    <div class="flex-1 grid grid-cols-2 gap-2">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Label Sub (Autocomplete)</label>
        <input type="text" name="sub_label_${idx}[]" list="labelDatalist" autocomplete="off"
               placeholder="Ketik atau pilih label..."
               onchange="handleLabelAutocomplete(this)"
               class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Value Sub</label>
        <input type="text" name="sub_value_${idx}[]" placeholder="Isi value..."
               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"/>
      </div>
    </div>
    <button type="button" onclick="this.closest('.subdata-row').remove()"
            class="text-red-400 hover:text-red-600 mt-5 p-1" title="Hapus sub-data">
      <span class="material-symbols-outlined text-base">close</span>
    </button>`;
      container.appendChild(div);
    }

    /* ---- Toast & Copy Helper ---- */
    let toastTimer = null;

    function showToast(msg) {
      const toast = document.getElementById('copyToast');
      const txt = document.getElementById('copyToastText');
      if (!toast || !txt) return;
      txt.innerText = msg || 'Disalin ke clipboard!';
      toast.classList.remove('opacity-0', 'pointer-events-none');
      toast.classList.add('opacity-100');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0', 'pointer-events-none');
      }, 2000);
    }

    function copyDetailText(val, btn) {
      if (!val) return;
      const onSuccess = () => {
        showToast('Disalin: ' + (val.length > 25 ? val.substring(0, 25) + '...' : val));
        if (btn) {
          const orig = btn.innerHTML;
          btn.innerHTML = '<span class="material-symbols-outlined text-[13px] text-green-600">check</span>';
          setTimeout(() => {
            btn.innerHTML = orig;
          }, 1200);
        }
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(val).then(onSuccess).catch(() => fallbackCopy(val, onSuccess));
      } else {
        fallbackCopy(val, onSuccess);
      }
    }

    function fallbackCopy(val, cb) {
      const ta = document.createElement('textarea');
      ta.value = val;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        if (cb) cb();
      } catch (e) {
        prompt('Salin manual dengan Ctrl+C:', val);
      }
      document.body.removeChild(ta);
    }

    /* ---- Filter Search Cards ---- */
    function filterDetailCards(q) {
      q = (q || '').toLowerCase().trim();
      const cards = document.querySelectorAll('.detail-card');
      let matched = 0;
      cards.forEach(card => {
        const text = (card.getAttribute('data-search') || card.innerText || '').toLowerCase();
        if (!q || text.includes(q)) {
          card.classList.remove('hidden');
          matched++;
        } else {
          card.classList.add('hidden');
        }
      });
      const cnt = document.getElementById('detailMatchCount');
      if (cnt) cnt.innerText = q ? `Ditemukan ${matched} data` : `Menampilkan ${cards.length} data`;
      const emptyMsg = document.getElementById('detailEmptySearch');
      if (emptyMsg) {
        emptyMsg.classList.toggle('hidden', matched > 0 || !q);
      }
    }
  </script>
</body>

</html>