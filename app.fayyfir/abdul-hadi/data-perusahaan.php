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

// Cek keberadaan kolom 'label' pada tabel perusahaan_master agar kompatibel jika tabel belum di-alter di hosting
$has_label_col = false;
try {
  $col_check = $db->query("SHOW COLUMNS FROM `perusahaan_master` LIKE 'label'");
  if ($col_check && $col_check->num_rows > 0) {
    $has_label_col = true;
  } else {
    // Coba tambahkan kolom secara otomatis jika user database memiliki hak akses ALTER
    $alter = $db->query("ALTER TABLE `perusahaan_master` ADD COLUMN `label` varchar(255) DEFAULT '' AFTER `tipe`");
    if ($alter) {
      $has_label_col = true;
    }
  }
} catch (Throwable $e) {
  $has_label_col = false;
}

$success = $_SESSION['flash_success'] ?? "";
unset($_SESSION['flash_success']);
$error   = $_SESSION['flash_error'] ?? "";
unset($_SESSION['flash_error']);

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

/* =========================================================
   AJAX: tambah label baru
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
   AJAX: update nama tipe
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'update_tipe') {
  header('Content-Type: application/json');
  $old_tipe = trim($_POST['old_tipe'] ?? '');
  $new_tipe = trim($_POST['new_tipe'] ?? '');

  if ($old_tipe === '' || $new_tipe === '' || $new_tipe === '__new__') {
    echo json_encode(['ok' => false, 'msg' => 'Nama tipe tidak valid']);
    exit;
  }

  // Update di tabel master
  $st = $db->prepare("UPDATE perusahaan_tipe_master SET nama_tipe=? WHERE nama_tipe=?");
  $st->bind_param("ss", $new_tipe, $old_tipe);
  $st->execute();

  // Pastikan new_tipe ada
  ensureTipeMaster($db, $new_tipe);

  // Update juga seluruh data perusahaan yang sedang memakai tipe lama agar tetap konsisten
  $st2 = $db->prepare("UPDATE perusahaan_master SET tipe=? WHERE tipe=?");
  $st2->bind_param("ss", $new_tipe, $old_tipe);
  $st2->execute();

  echo json_encode(['ok' => true, 'old_tipe' => $old_tipe, 'new_tipe' => $new_tipe]);
  exit;
}

/* =========================================================
   AJAX: delete tipe
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete_tipe') {
  header('Content-Type: application/json');
  $tipe = trim($_POST['tipe'] ?? '');

  if ($tipe === '' || $tipe === '__new__') {
    echo json_encode(['ok' => false, 'msg' => 'Tipe tidak valid']);
    exit;
  }

  // Hapus dari tabel master
  $st = $db->prepare("DELETE FROM perusahaan_tipe_master WHERE nama_tipe=?");
  $st->bind_param("s", $tipe);
  $st->execute();

  echo json_encode(['ok' => true, 'tipe' => $tipe]);
  exit;
}

/* =========================================================
   AJAX: add tipe baru
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'add_tipe') {
  header('Content-Type: application/json');
  $nama = trim($_POST['nama_tipe'] ?? '');

  if ($nama === '' || $nama === '__new__') {
    echo json_encode(['ok' => false, 'msg' => 'Nama tipe tidak valid']);
    exit;
  }

  ensureTipeMaster($db, $nama);
  echo json_encode(['ok' => true, 'nama' => $nama]);
  exit;
}

/* =========================================================
   POST HANDLER
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  /* --- Create Master --- */
  if ($action === 'create_master') {
    $nama = trim($_POST['nama'] ?? '');
    $tipe = trim($_POST['tipe'] ?? '');
    $label = trim($_POST['label'] ?? '');
    if ($tipe === '__new__') {
      $tipe = trim($_POST['new_tipe'] ?? '');
    }
    if ($tipe === '') {
      $tipe = 'PT / CV';
    }
    ensureTipeMaster($db, $tipe);

    if ($nama === '') {
      $error = "Nama master data wajib diisi.";
      goto render;
    }

    if ($label !== '') {
      ensureLabelMaster($db, $label);
    }

    if ($has_label_col) {
      $st = $db->prepare("INSERT INTO perusahaan_master (nama, tipe, label, created_by) VALUES (?,?,?,?)");
      $st->bind_param("sssi", $nama, $tipe, $label, $user_id);
    } else {
      $st = $db->prepare("INSERT INTO perusahaan_master (nama, tipe, created_by) VALUES (?,?,?)");
      $st->bind_param("ssi", $nama, $tipe, $user_id);
    }
    $st->execute();
    $id_master = $db->insert_id;

    $labels = $_POST['data_label'] ?? [];
    $values = $_POST['data_value'] ?? [];
    foreach ($labels as $i => $lbl) {
      $lbl = trim($lbl);
      if ($lbl === '' || $lbl === '__new__') continue;
      ensureLabelMaster($db, $lbl);

      $val = $values[$i] ?? '';
      $st2 = $db->prepare("INSERT INTO perusahaan_data (id_master, label, value) VALUES (?,?,?)");
      $st2->bind_param("iss", $id_master, $lbl, $val);
      $st2->execute();
      $id_data = $db->insert_id;

      $sub_labels = $_POST["sub_label_$i"] ?? [];
      $sub_values = $_POST["sub_value_$i"] ?? [];
      foreach ($sub_labels as $j => $slbl) {
        $slbl = trim($slbl);
        if ($slbl === '' || $slbl === '__new__') continue;
        ensureLabelMaster($db, $slbl);

        $sv  = $sub_values[$j] ?? '';
        $st3 = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
        $st3->bind_param("iss", $id_data, $slbl, $sv);
        $st3->execute();
      }
    }
    $_SESSION['flash_success'] = "Master data berhasil ditambahkan.";
    header("Location: data-perusahaan");
    exit;
  }

  /* --- Delete Master --- */
  if ($action === 'delete_master') {
    $id = intval($_POST['id'] ?? 0);
    $st = $db->prepare("DELETE FROM perusahaan_master WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
    $_SESSION['flash_success'] = "Master data berhasil dihapus.";
    header("Location: data-perusahaan");
    exit;
  }

  /* --- Delete Data --- */
  if ($action === 'delete_data') {
    $id = intval($_POST['id'] ?? 0);
    $st = $db->prepare("DELETE FROM perusahaan_data WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
    $success = "Data berhasil dihapus.";
  }

  /* --- Delete Sub-data --- */
  if ($action === 'delete_subdata') {
    $id = intval($_POST['id'] ?? 0);
    $st = $db->prepare("DELETE FROM perusahaan_subdata WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
    $success = "Sub-data berhasil dihapus.";
  }

  /* --- Update (edit modal) --- */
  if ($action === 'update_master') {
    $id_master = intval($_POST['id_master'] ?? 0);
    $nama      = trim($_POST['nama'] ?? '');
    $tipe      = trim($_POST['tipe'] ?? '');
    if ($tipe === '__new__') {
      $tipe = trim($_POST['new_tipe'] ?? '');
    }
    if ($tipe === '') {
      $tipe = 'PT / CV';
    }
    ensureTipeMaster($db, $tipe);

    if ($nama === '') {
      $error = "Nama master data wajib diisi.";
      goto render;
    }

    $label = trim($_POST['label'] ?? '');
    if ($label !== '') {
      ensureLabelMaster($db, $label);
    }

    if ($has_label_col) {
      $st = $db->prepare("UPDATE perusahaan_master SET nama=?, tipe=?, label=? WHERE id=?");
      $st->bind_param("sssi", $nama, $tipe, $label, $id_master);
    } else {
      $st = $db->prepare("UPDATE perusahaan_master SET nama=?, tipe=? WHERE id=?");
      $st->bind_param("ssi", $nama, $tipe, $id_master);
    }
    $st->execute();

    // Update existing data rows
    $ex_ids    = $_POST['ex_data_id']    ?? [];
    $ex_labels = $_POST['ex_data_label'] ?? [];
    $ex_values = $_POST['ex_data_value'] ?? [];
    foreach ($ex_ids as $i => $eid) {
      $eid = intval($eid);
      if (!$eid) continue;
      $el  = trim($ex_labels[$i] ?? '');
      if ($el !== '' && $el !== '__new__') {
        ensureLabelMaster($db, $el);
        $ev  = $ex_values[$i] ?? '';
        $st2 = $db->prepare("UPDATE perusahaan_data SET label=?, value=? WHERE id=?");
        $st2->bind_param("ssi", $el, $ev, $eid);
        $st2->execute();
      }

      $ex_sids    = $_POST["ex_sub_id_$i"]    ?? [];
      $ex_slabels = $_POST["ex_sub_label_$i"] ?? [];
      $ex_svalues = $_POST["ex_sub_value_$i"] ?? [];
      foreach ($ex_sids as $j => $sid) {
        $sid = intval($sid);
        if (!$sid) continue;
        $sl  = trim($ex_slabels[$j] ?? '');
        if ($sl !== '' && $sl !== '__new__') {
          ensureLabelMaster($db, $sl);
          $sv  = $ex_svalues[$j] ?? '';
          $st3 = $db->prepare("UPDATE perusahaan_subdata SET label=?, value=? WHERE id=?");
          $st3->bind_param("ssi", $sl, $sv, $sid);
          $st3->execute();
        }
      }
      // New sub-data under existing data
      $ns_labels = $_POST["new_sub_label_{$eid}"] ?? [];
      $ns_values = $_POST["new_sub_value_{$eid}"] ?? [];
      foreach ($ns_labels as $j => $nsl) {
        $nsl = trim($nsl);
        if ($nsl === '' || $nsl === '__new__') continue;
        ensureLabelMaster($db, $nsl);
        $nsv = $ns_values[$j] ?? '';
        $st4 = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
        $st4->bind_param("iss", $eid, $nsl, $nsv);
        $st4->execute();
      }
    }

    // New data rows
    $new_labels = $_POST['new_data_label'] ?? [];
    $new_values = $_POST['new_data_value'] ?? [];
    foreach ($new_labels as $i => $lbl) {
      $lbl = trim($lbl);
      if ($lbl === '' || $lbl === '__new__') continue;
      ensureLabelMaster($db, $lbl);

      $val = $new_values[$i] ?? '';
      $st5 = $db->prepare("INSERT INTO perusahaan_data (id_master, label, value) VALUES (?,?,?)");
      $st5->bind_param("iss", $id_master, $lbl, $val);
      $st5->execute();
      $id_data = $db->insert_id;

      $ns_labels = $_POST["new_sub_label_new_$i"] ?? [];
      $ns_values = $_POST["new_sub_value_new_$i"] ?? [];
      foreach ($ns_labels as $j => $nsl) {
        $nsl = trim($nsl);
        if ($nsl === '' || $nsl === '__new__') continue;
        ensureLabelMaster($db, $nsl);
        $nsv = $ns_values[$j] ?? '';
        $st6 = $db->prepare("INSERT INTO perusahaan_subdata (id_data, label, value) VALUES (?,?,?)");
        $st6->bind_param("iss", $id_data, $nsl, $nsv);
        $st6->execute();
      }
    }
    $_SESSION['flash_success'] = "Perubahan berhasil disimpan.";
    header("Location: data-perusahaan");
    exit;
  }
}

render:
/* =========================================================
   FETCH DATA untuk tabel (master data, label data utama, & label sub-data)
   ========================================================= */
$search_q = trim($_GET['q'] ?? '');

$select_label_sql = $has_label_col
  ? "pm.label, COALESCE(NULLIF(pm.label, ''), GROUP_CONCAT(DISTINCT pd.label ORDER BY pd.id ASC SEPARATOR ', ')) AS display_label,"
  : "'' AS label, GROUP_CONCAT(DISTINCT pd.label ORDER BY pd.id ASC SEPARATOR ', ') AS display_label,";

$group_by_sql = $has_label_col
  ? "GROUP BY pm.id, pm.nama, pm.tipe, pm.label, pm.created_at"
  : "GROUP BY pm.id";

$masters = $db->query("
  SELECT pm.id, pm.nama, pm.tipe, pm.created_at,
         $select_label_sql
         GROUP_CONCAT(DISTINCT pd.label ORDER BY pd.id ASC SEPARATOR ', ') AS labels,
         (
           SELECT GROUP_CONCAT(DISTINCT psd.label ORDER BY psd.id ASC SEPARATOR ', ')
           FROM perusahaan_data pd2
           JOIN perusahaan_subdata psd ON psd.id_data = pd2.id
           WHERE pd2.id_master = pm.id AND psd.label != '' AND psd.label != '__new__'
         ) AS sub_labels
  FROM perusahaan_master pm
  LEFT JOIN perusahaan_data pd ON pd.id_master = pm.id AND pd.label != '' AND pd.label != '__new__'
  $group_by_sql
  ORDER BY pm.created_at DESC
");

/* =========================================================
   FETCH labels untuk autocomplete datalist
   ========================================================= */
$label_opts = $db->query("SELECT id, nama_label FROM perusahaan_label_master ORDER BY nama_label ASC");
$all_labels = [];
if ($label_opts) {
  while ($r = $label_opts->fetch_assoc()) $all_labels[] = $r;
}
$labels_json = json_encode($all_labels);

/* =========================================================
   REDIRECT GET detail_id ke halaman tersendiri
   ========================================================= */
if (isset($_GET['detail_id'])) {
  header("Location: detail-perusahaan?id=" . intval($_GET['detail_id']));
  exit;
}

/* =========================================================
   FETCH detail master untuk EDIT MODAL (via GET edit_id)
   ========================================================= */
$edit_master = null;
$edit_data   = [];
if (isset($_GET['edit_id'])) {
  $eid = intval($_GET['edit_id']);
  $r   = $db->query("SELECT * FROM perusahaan_master WHERE id=$eid");
  if ($r && $r->num_rows) {
    $edit_master = $r->fetch_assoc();
    $rd = $db->query("SELECT * FROM perusahaan_data WHERE id_master=$eid ORDER BY id ASC");
    while ($d = $rd->fetch_assoc()) {
      $rs   = $db->query("SELECT * FROM perusahaan_subdata WHERE id_data={$d['id']} ORDER BY id ASC");
      $subs = [];
      while ($s = $rs->fetch_assoc()) $subs[] = $s;
      $d['subs'] = $subs;
      $edit_data[] = $d;
    }
  }
}
$tipe_map = ['pt' => 'PT / CV', 'personal' => 'Personal', 'lainnya' => 'Lainnya'];

/* =========================================================
   FETCH tipe perusahaan untuk dropdown
   ========================================================= */
$tipe_opts = $db->query("SELECT id, nama_tipe FROM perusahaan_tipe_master ORDER BY id ASC");
$all_tipes = [];
if ($tipe_opts) {
  while ($r = $tipe_opts->fetch_assoc()) $all_tipes[] = $r['nama_tipe'];
}
if (empty($all_tipes)) {
  $all_tipes = ['PT / CV', 'Personal', 'Lainnya'];
}
$tipes_json = json_encode($all_tipes);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Perusahaan - Fayyfir</title>
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
    .detail-modal-body {
      font-size: 11px;
      line-height: 1.35;
    }

    .detail-modal-body .detail-label {
      font-size: 9.5px;
      font-weight: 700;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .detail-modal-body .detail-val {
      font-size: 11px;
      font-weight: 600;
      line-height: 1.35;
    }

    .detail-modal-body .detail-sub-label {
      font-size: 10px;
      font-weight: 600;
      line-height: 1.2;
    }

    .detail-modal-body .detail-sub-val {
      font-size: 11px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      line-height: 1.35;
    }

    .detail-modal-body .copy-btn .material-symbols-outlined {
      font-size: 13px !important;
      line-height: 1;
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
  <!-- Datalist Autocomplete untuk Semua Label (Data Utama & Sub-Data) -->
  <datalist id="labelDatalist">
    <?php foreach ($all_labels as $l): ?>
      <option value="<?= htmlspecialchars($l['nama_label']) ?>"></option>
    <?php endforeach; ?>
  </datalist>

  <div class="flex min-h-screen">

    <!-- ===== SIDEBAR (Abdul Hadi) ===== -->
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
                  <a href="lunas" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">check_box</span>
                    <span class="text-base">Kontainer Lunas</span>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($level == "2" || $level == "3"): ?>
            <a href="daftar-tim" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2">
              <span class="material-symbols-outlined text-sm">person</span>
              <span class="text-base">Team</span>
            </a>

            <!-- Petani / Supplier -->
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

                <!-- Kayu Gaharu -->
                <button onclick="toggleDropdown6()" class="w-full flex items-center justify-between text-gray-300 hover:text-white px-3 py-1 focus:outline-none">
                  <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-sm">warehouse</span>
                    <span class="text-base">Kayu Gaharu</span>
                  </div>
                  <span class="material-symbols-outlined text-sm">expand_more</span>
                </button>
                <div id="dropdownMenu6" class="hidden ml-8 mt-1 space-y-1">
                  <a href="bahan-baku" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">pallet</span>
                    <span class="text-base">Bahan Baku</span>
                  </a>
                  <a href="produksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">cycle</span>
                    <span class="text-base">Produksi</span>
                  </a>
                  <a href="hasil-produksi" class="flex items-center space-x-2 text-gray-300 hover:text-white px-2 py-1">
                    <span class="material-symbols-outlined text-sm">checklist</span>
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

                <!-- Data Perusahaan -->
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

    <!-- ===== MAIN ===== -->
    <div class="flex-1 flex flex-col">

      <!-- Topbar -->
      <header class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between shadow">
        <div class="flex items-center space-x-3">
          <button onclick="toggleSidebar()" class="lg:hidden">
            <span class="material-symbols-outlined">menu</span>
          </button>
          <h1 class="text-lg font-semibold">Data Perusahaan</h1>
        </div>
        <a href="logout" class="text-sm text-gray-300 hover:text-white flex items-center space-x-1">
          <span class="material-symbols-outlined text-sm">logout</span><span>Keluar</span>
        </a>
      </header>

      <!-- Content -->
      <main class="p-6">

        <!-- Alert -->
        <?php if ($success): ?>
          <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded flex items-center space-x-2">
            <span class="material-symbols-outlined">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded flex items-center space-x-2">
            <span class="material-symbols-outlined">error</span>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Header row -->
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-xl font-bold text-gray-700">Daftar Master Data Perusahaan &amp; Personal</h2>
            <p class="text-xs text-gray-500">Kelola master data perusahaan, nomor NPWP, email, akun, dan rincian data lainnya.</p>
          </div>
          <div class="flex items-center space-x-2">
            <button type="button" onclick="openManageAutocompleteModal()"
              class="bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 hover:border-purple-300 px-3.5 py-2 rounded shadow-sm flex items-center space-x-1.5 text-xs font-semibold transition"
              title="Kelola Saran Autocomplete Label">
              <span class="material-symbols-outlined text-sm text-purple-600">tune</span>
              <span>Kelola Autocomplete</span>
            </button>
            <button onclick="openCreateModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow flex items-center space-x-1 text-xs font-semibold">
              <span class="material-symbols-outlined text-sm">add</span>
              <span>Create Master Data</span>
            </button>
          </div>
        </div>

        <!-- Search Toolbar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-4 flex flex-wrap items-center justify-between gap-3">
          <div class="relative flex-1 min-w-[280px] max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-sm">search</span>
            <input type="text" id="searchMasterInput" oninput="filterMasterTable(this.value)"
              placeholder="Cari master data, label data, atau label sub data..."
              value="<?= htmlspecialchars($search_q ?? '') ?>"
              class="w-full pl-9 pr-8 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 focus:bg-white transition" />
            <button type="button" id="clearSearchBtn" onclick="clearMasterSearch()"
              class="<?= empty($search_q) ? 'hidden ' : '' ?>absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 p-0.5 rounded" title="Hapus pencarian">
              <span class="material-symbols-outlined text-sm">close</span>
            </button>
          </div>
          <div class="flex items-center space-x-2 text-xs text-gray-500">
            <span id="searchCountBadge" class="font-medium text-gray-600 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
              Total <?= $masters ? $masters->num_rows : 0 ?> master data
            </span>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded shadow overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-4 py-3 text-left w-12">#</th>
                <th class="px-4 py-3 text-left">Nama Master Data</th>
                <th class="px-4 py-3 text-left w-28">Tipe</th>
                <th class="px-4 py-3 text-left w-36">Tanggal Dibuat</th>
                <th class="px-4 py-3 text-left">Label</th>
                <th class="px-4 py-3 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody id="masterTableBody" class="divide-y divide-gray-100">
              <?php if ($masters && $masters->num_rows): $no = 1;
                while ($row = $masters->fetch_assoc()):
                  $cur_tipe = $tipe_map[$row['tipe']] ?? $row['tipe'];
                  $is_pt = ($cur_tipe === 'PT / CV' || $row['tipe'] === 'pt');
                  $is_pers = ($cur_tipe === 'Personal' || $row['tipe'] === 'personal');
                  $badge_cls = $is_pt ? 'bg-blue-100 text-blue-700' : ($is_pers ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700');
                  $cur_label_display = !empty($row['display_label']) ? $row['display_label'] : (!empty($row['label']) ? $row['label'] : '-');
                  $search_terms = strtolower($row['nama'] . ' ' . $cur_tipe . ' ' . $cur_label_display . ' ' . ($row['sub_labels'] ?? ''));
              ?>
                  <tr class="master-row hover:bg-gray-50 transition-colors"
                    data-nama="<?= htmlspecialchars(strtolower($row['nama'])) ?>"
                    data-labels="<?= htmlspecialchars(strtolower($cur_label_display)) ?>"
                    data-sublabels="<?= htmlspecialchars(strtolower($row['sub_labels'] ?? '')) ?>"
                    data-search="<?= htmlspecialchars($search_terms) ?>">
                    <td class="px-4 py-3 text-gray-500 row-number"><?= $no++ ?></td>
                    <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                    <td class="px-4 py-3">
                      <span class="badge-tipe px-2 py-0.5 rounded text-xs font-semibold <?= $badge_cls ?>">
                        <?= htmlspecialchars($cur_tipe) ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td class="px-4 py-3 text-gray-600 max-w-md">
                      <span class="truncate block" title="<?= htmlspecialchars($cur_label_display) ?>">
                        <?= htmlspecialchars($cur_label_display) ?>
                      </span>
                      <span class="sublabel-match-badge hidden mt-1 inline-flex items-center text-[10px] text-purple-700 bg-purple-50 border border-purple-200 px-1.5 py-0.5 rounded font-medium">
                        <span class="material-symbols-outlined text-xs mr-0.5" style="font-size: 11px;">subdirectory_arrow_right</span>
                        Cocok di label sub-data
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                      <!-- 👁 DETAIL (Halaman Tersendiri) -->
                      <a href="detail-perusahaan?id=<?= $row['id'] ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors p-1" title="Lihat Detail Data">
                        <span class="material-symbols-outlined text-lg">visibility</span>
                      </a>
                      <!-- 🗑 DELETE -->
                      <form method="POST" class="inline" onsubmit="return confirm('Hapus master data <?= htmlspecialchars($row['nama']) ?> beserta seluruh datanya?')">
                        <input type="hidden" name="action" value="delete_master" />
                        <input type="hidden" name="id" value="<?= $row['id'] ?>" />
                        <button type="submit" class="text-red-500 hover:text-red-700 inline-flex items-center transition-colors p-1" title="Hapus Master Data">
                          <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile;
              else: ?>
                <tr id="masterEmptyRow">
                  <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada master data perusahaan. Klik tombol "Create Master Data" di atas untuk menambah.</td>
                </tr>
              <?php endif; ?>
              <!-- Empty state row for search with no matches -->
              <tr id="masterEmptySearchRow" class="hidden">
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                  <div class="flex flex-col items-center justify-center space-y-1">
                    <span class="material-symbols-outlined text-2xl text-gray-300">search_off</span>
                    <span class="text-xs">Tidak ada data perusahaan yang cocok dengan kata kunci pencarian.</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
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
     MODAL CREATE MASTER DATA
     =================================================== -->
  <div id="modalCreate" onclick="if(event.target===this) closeCreateModal()" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl mx-auto modal-80vh overflow-y-auto" style="max-height: 80vh;">
      <div class="bg-indigo-600 text-white px-6 py-4 rounded-t-lg flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <h3 class="text-lg font-semibold flex items-center space-x-2">
          <span class="material-symbols-outlined">add_business</span>
          <span>Create Master Data</span>
        </h3>
        <button type="button" onclick="closeCreateModal()" class="hover:text-indigo-200">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="action" value="create_master" />
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Master Data <span class="text-red-500">*</span></label>
            <input type="text" name="nama" placeholder="Contoh: PT ABC / Nama Personal" required
              class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Tipe</label>
            <div class="relative" id="createTipeDropdownWrap">
              <input type="hidden" name="tipe" id="createTipeValue" value="<?= htmlspecialchars($all_tipes[0] ?? 'PT / CV') ?>" />
              <button type="button" id="createTipeTrigger" onclick="toggleTipeDropdown('create')"
                class="w-full flex items-center justify-between border border-gray-300 rounded px-3 py-2 text-xs bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-sm text-left">
                <span id="createTipeDisplay" class="font-medium truncate"><?= htmlspecialchars($all_tipes[0] ?? 'PT / CV') ?></span>
                <span class="material-symbols-outlined text-sm text-gray-500 ml-1">arrow_drop_down</span>
              </button>

              <!-- Dropdown Menu -->
              <div id="createTipeDropdownMenu" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 text-xs">
                <!-- List Option Rows with Edit & Delete beside each option -->
                <div id="createTipeOptionsContainer" class="max-h-52 overflow-y-auto divide-y divide-gray-100"></div>

                <!-- Add New Option Footer -->
                <div class="p-2 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                  <div id="createAddTipeWrap" class="hidden flex items-center space-x-1.5 mb-1.5">
                    <input type="text" id="createAddTipeInput" placeholder="Ketik nama tipe baru..."
                      class="flex-1 border border-indigo-400 rounded px-2.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white" />
                    <button type="button" onclick="submitAddTipe('create')"
                      class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm transition">
                      Simpan
                    </button>
                    <button type="button" onclick="cancelAddTipe('create')"
                      class="px-2 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition">
                      Batal
                    </button>
                  </div>
                  <button type="button" id="createBtnShowAdd" onclick="showAddTipeInput('create')"
                    class="w-full flex items-center justify-center space-x-1 text-indigo-600 hover:text-indigo-800 font-semibold py-1 rounded hover:bg-indigo-50 transition text-xs">
                    <span>+ Tambah Tipe Baru...</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Label</label>
            <input type="text" name="label" placeholder="Contoh: Perusahaan, Vendor, dsb..."
              class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
        </div>

        <div class="flex justify-end space-x-3 pt-3 border-t border-gray-100 sticky bottom-0 bg-white z-10 pb-1">
          <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800">Batal</button>
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded text-xs font-semibold shadow">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================
     MODAL EDIT / DETAIL MASTER DATA
     =================================================== -->
  <?php if ($edit_master): ?>
    <div id="modalEdit" onclick="if(event.target===this) window.location.href='data-perusahaan'" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl mx-auto modal-80vh overflow-y-auto" style="max-height: 80vh;">
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex items-center justify-between sticky top-0 z-10 shadow-sm">
          <h3 class="text-lg font-semibold flex items-center space-x-2">
            <span class="material-symbols-outlined">edit_note</span>
            <span>Edit Master Data: <?= htmlspecialchars($edit_master['nama']) ?></span>
          </h3>
          <a href="data-perusahaan" class="hover:text-gray-300">
            <span class="material-symbols-outlined">close</span>
          </a>
        </div>
        <form method="POST" action="data-perusahaan" class="p-6 space-y-4">
          <input type="hidden" name="action" value="update_master" />
          <input type="hidden" name="id_master" value="<?= $edit_master['id'] ?>" />
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Master Data <span class="text-red-500">*</span></label>
              <input type="text" name="nama" value="<?= htmlspecialchars($edit_master['nama']) ?>" required
                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Tipe</label>
              <div class="relative" id="editTipeDropdownWrap">
                <?php
                $cur_edit_tipe = $tipe_map[$edit_master['tipe']] ?? $edit_master['tipe'];
                if (empty($cur_edit_tipe)) $cur_edit_tipe = $all_tipes[0] ?? 'PT / CV';
                ?>
                <input type="hidden" name="tipe" id="editTipeValue" value="<?= htmlspecialchars($cur_edit_tipe) ?>" />
                <button type="button" id="editTipeTrigger" onclick="toggleTipeDropdown('edit')"
                  class="w-full flex items-center justify-between border border-gray-300 rounded px-3 py-2 text-xs bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-sm text-left">
                  <span id="editTipeDisplay" class="font-medium truncate"><?= htmlspecialchars($cur_edit_tipe) ?></span>
                  <span class="material-symbols-outlined text-sm text-gray-400 ml-1">arrow_drop_down</span>
                </button>

                <!-- Dropdown Menu -->
                <div id="editTipeDropdownMenu" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 text-xs">
                  <!-- List Option Rows with Edit & Delete beside each option -->
                  <div id="editTipeOptionsContainer" class="max-h-52 overflow-y-auto divide-y divide-gray-100"></div>

                  <!-- Add New Option Footer -->
                  <div class="p-2 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <div id="editAddTipeWrap" class="hidden flex items-center space-x-1.5 mb-1.5">
                      <input type="text" id="editAddTipeInput" placeholder="Ketik nama tipe baru..."
                        class="flex-1 border border-indigo-400 rounded px-2.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white" />
                      <button type="button" onclick="submitAddTipe('edit')"
                        class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm transition">
                        Simpan
                      </button>
                      <button type="button" onclick="cancelAddTipe('edit')"
                        class="px-2 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition">
                        Batal
                      </button>
                    </div>
                    <button type="button" id="editBtnShowAdd" onclick="showAddTipeInput('edit')"
                      class="w-full flex items-center justify-center space-x-1 text-indigo-600 hover:text-indigo-800 font-semibold py-1 rounded hover:bg-indigo-50 transition text-xs">
                      <span class="material-symbols-outlined" style="font-size: 15px;">add</span>
                      <span>+ Tambah Tipe Baru...</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Label</label>
              <input type="text" name="label" value="<?= htmlspecialchars($edit_master['label'] ?? '') ?>"
                placeholder="Contoh: Perusahaan, Vendor, dsb..."
                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            </div>
          </div>

          <!-- Existing data rows -->
          <?php foreach ($edit_data as $di => $d):
            $has_subs = !empty($d['subs']);
          ?>
            <div class="data-row bg-gray-50 p-3 rounded space-y-2 border border-gray-200">
              <input type="hidden" name="ex_data_id[]" value="<?= $d['id'] ?>" />
              <div class="flex items-start space-x-2">
                <div class="flex-1 grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Label Data (Autocomplete)</label>
                    <input type="text" name="ex_data_label[]" list="labelDatalist" autocomplete="off"
                      value="<?= htmlspecialchars($d['label'] ?? '') ?>"
                      placeholder="Ketik atau pilih label..."
                      onchange="handleLabelAutocomplete(this)"
                      class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
                  </div>
                  <div>
                    <div class="value-field-wrap <?= $has_subs ? 'hidden' : '' ?>">
                      <label class="block text-xs font-semibold text-gray-600 mb-1">Value</label>
                      <input type="text" name="ex_data_value[]" value="<?= htmlspecialchars($d['value'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
                    </div>
                    <div class="multiple-data-badge <?= $has_subs ? '' : 'hidden' ?> mb-1">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-semibold">
                        <span class="material-symbols-outlined text-xs mr-1">splitscreen</span> Multiple Data Aktif
                      </span>
                    </div>
                    <div class="mt-1">
                      <button type="button" onclick="toggleSubEdit(this, <?= $d['id'] ?>)"
                        class="btn-toggle-multiple text-xs <?= $has_subs ? 'text-red-600' : 'text-purple-600' ?> hover:underline font-medium inline-flex items-center">
                        <?= $has_subs ? '<span class="material-symbols-outlined text-xs mr-0.5">close</span> Batalkan Multiple Data' : '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data' ?>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="mt-5">
                  <button type="button" onclick="submitSingleDelete('delete_data', <?= $d['id'] ?>)"
                    class="text-red-400 hover:text-red-600 p-1" title="Hapus baris data">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </div>

              <!-- Sub Data Section -->
              <div id="subEdit_<?= $d['id'] ?>" class="<?= $has_subs ? '' : 'hidden' ?> ml-4 pl-3 border-l-2 border-purple-300 space-y-2 mt-2">
                <div class="text-xs font-semibold text-purple-700 mb-1">Sub Data (Multiple Data)</div>
                <?php foreach ($d['subs'] as $si => $s): ?>
                  <div class="subdata-row bg-white p-2.5 rounded shadow-sm flex items-start space-x-2 border border-purple-100">
                    <input type="hidden" name="ex_sub_id_<?= $di ?>[]" value="<?= $s['id'] ?>" />
                    <div class="flex-1 grid grid-cols-2 gap-2">
                      <div>
                        <label class="block text-xs text-gray-500 mb-1">Label Sub (Autocomplete)</label>
                        <input type="text" name="ex_sub_label_<?= $di ?>[]" list="labelDatalist" autocomplete="off"
                          value="<?= htmlspecialchars($s['label'] ?? '') ?>"
                          placeholder="Ketik atau pilih label..."
                          onchange="handleLabelAutocomplete(this)"
                          class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
                      </div>
                      <div>
                        <label class="block text-xs text-gray-500 mb-1">Value Sub</label>
                        <input type="text" name="ex_sub_value_<?= $di ?>[]" value="<?= htmlspecialchars($s['value'] ?? '') ?>"
                          class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
                      </div>
                    </div>
                    <button type="button" onclick="submitSingleDelete('delete_subdata', <?= $s['id'] ?>)"
                      class="text-red-400 hover:text-red-600 mt-5 p-1" title="Hapus sub-data">
                      <span class="material-symbols-outlined text-base">close</span>
                    </button>
                  </div>
                <?php endforeach; ?>
                <div id="newSubRows_<?= $d['id'] ?>" class="space-y-2"></div>
                <button type="button" onclick="addNewSubRow(<?= $d['id'] ?>)"
                  class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1 font-medium bg-purple-50 hover:bg-purple-100 px-2 py-1 rounded border border-purple-200">
                  <span class="material-symbols-outlined text-sm">add</span><span>Tambah sub-data</span>
                </button>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- New data rows in edit modal -->
          <div id="editNewDataRows" class="space-y-3"></div>
          <button type="button" onclick="addNewDataRowEdit()"
            class="flex items-center space-x-1 text-indigo-600 hover:text-indigo-800 text-xs font-semibold border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 rounded px-3 py-1.5 transition-colors">
            <span class="material-symbols-outlined text-sm">add</span>
            <span>+ Add Data Baru</span>
          </button>
          <div class="flex justify-end space-x-3 pt-3 border-t border-gray-100 sticky bottom-0 bg-white z-10 pb-1">
            <a href="data-perusahaan" class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800">Tutup</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded text-xs font-semibold shadow">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Form terpisah untuk single delete data/subdata agar tidak nested di dalam form edit -->
    <form id="singleDeleteForm" method="POST" style="display:none;">
      <input type="hidden" name="action" id="singleDeleteAction" value="" />
      <input type="hidden" name="id" id="singleDeleteId" value="" />
    </form>
  <?php endif; ?>

  <!-- ===================================================
     JAVASCRIPT
     =================================================== -->
  <script>
    let ALL_LABELS = <?= $labels_json ?>;

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
      const dls = [document.getElementById('labelDatalist')];
      dls.forEach(dl => {
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
      });
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
            const idx = ALL_LABELS.findIndex(l => (id && l.id === id) || l.nama_label.toLowerCase() === labelName.toLowerCase());
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
            showCopyToast(`Label "${labelName}" berhasil dihapus.`);
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
            showCopyToast(`Label "${json.nama}" berhasil ditambahkan.`);
          } else {
            alert(json.msg || 'Gagal menambahkan label.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi.');
        });
    }

    let ALL_TIPES = <?= $tipes_json ?>;
    let editingTipeIndex = {
      create: null,
      edit: null
    };

    function toggleTipeDropdown(context) {
      const menu = document.getElementById(context + 'TipeDropdownMenu');
      if (!menu) return;
      const isHidden = menu.classList.contains('hidden');
      // Tutup dropdown lain
      document.getElementById('createTipeDropdownMenu')?.classList.add('hidden');
      document.getElementById('editTipeDropdownMenu')?.classList.add('hidden');
      if (isHidden) {
        editingTipeIndex[context] = null;
        cancelAddTipe(context);
        renderTipeOptions(context);
        menu.classList.remove('hidden');
      } else {
        menu.classList.add('hidden');
      }
    }

    function renderTipeOptions(context) {
      const container = document.getElementById(context + 'TipeOptionsContainer');
      const valInput = document.getElementById(context + 'TipeValue');
      if (!container || !valInput) return;

      const currentVal = valInput.value;
      container.innerHTML = '';

      if (ALL_TIPES.length === 0) {
        container.innerHTML = '<div class="px-3 py-3 text-center text-gray-400 text-xs italic">Belum ada pilihan tipe</div>';
        return;
      }

      ALL_TIPES.forEach((tipe, idx) => {
        const isEditing = (editingTipeIndex[context] === idx);
        const isSelected = (tipe === currentVal);

        const row = document.createElement('div');
        row.className = 'tipe-option-item';

        if (isEditing) {
          // Mode Edit Option: Langsung di dalam baris option
          row.innerHTML = `
            <div class="flex items-center space-x-1.5 px-3 py-2 bg-amber-50 border-b border-amber-200">
              <input type="text" id="${context}_editInput_${idx}" value="${escHtml(tipe)}"
                class="flex-1 border border-amber-400 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-amber-500 bg-white" />
              <button type="button" onclick="event.stopPropagation(); saveEditedTipeRow('${context}', '${escHtml(tipe)}', ${idx})"
                class="p-1 bg-green-600 hover:bg-green-700 text-white rounded transition flex items-center justify-center shadow-sm" title="Simpan perubahan nama">
                <span class="material-symbols-outlined text-sm">check</span>
              </button>
              <button type="button" onclick="event.stopPropagation(); cancelEditTipeRow('${context}')"
                class="p-1 bg-gray-500 hover:bg-gray-600 text-white rounded transition flex items-center justify-center shadow-sm" title="Batal">
                <span class="material-symbols-outlined text-sm">close</span>
              </button>
            </div>
          `;
          setTimeout(() => {
            const inp = document.getElementById(`${context}_editInput_${idx}`);
            if (inp) {
              inp.focus();
              inp.select();
              inp.onkeydown = function(e) {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  saveEditedTipeRow(context, tipe, idx);
                } else if (e.key === 'Escape') {
                  e.preventDefault();
                  cancelEditTipeRow(context);
                }
              };
            }
          }, 50);
        } else {
          // Mode Tampilan Option: Label di kiri, button edit & delete di samping kanan di dalam option
          row.innerHTML = `
            <div class="group flex items-center justify-between px-3 py-2 hover:bg-indigo-50/80 cursor-pointer transition ${isSelected ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-gray-700'}"
                 onclick="selectTipeOption('${context}', '${escHtml(tipe)}')">
              <div class="flex items-center space-x-2 truncate flex-1 mr-2">
                <span class="material-symbols-outlined text-xs ${isSelected ? 'text-indigo-600' : 'text-transparent'}" style="font-size: 15px;">check</span>
                <span class="truncate">${escHtml(tipe)}</span>
              </div>
              <div class="flex items-center space-x-1 shrink-0" onclick="event.stopPropagation()">
                <button type="button" onclick="event.stopPropagation(); startEditTipeRow('${context}', ${idx})"
                  class="p-1 bg-green-500 hover:bg-green-600 text-white rounded shadow-sm transition flex items-center justify-center" title="Edit pilihan tipe ini">
                  <span class="material-symbols-outlined" style="font-size: 14px;">edit</span>
                </button>
                <button type="button" onclick="event.stopPropagation(); deleteTipeOption('${context}', '${escHtml(tipe)}')"
                  class="p-1 bg-red-500 hover:bg-red-600 text-white rounded shadow-sm transition flex items-center justify-center" title="Hapus pilihan tipe ini">
                  <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                </button>
              </div>
            </div>
          `;
        }
        container.appendChild(row);
      });
    }

    function selectTipeOption(context, tipe) {
      const valInput = document.getElementById(context + 'TipeValue');
      const display = document.getElementById(context + 'TipeDisplay');
      if (valInput) valInput.value = tipe;
      if (display) display.textContent = tipe;
      document.getElementById(context + 'TipeDropdownMenu')?.classList.add('hidden');
    }

    function startEditTipeRow(context, idx) {
      editingTipeIndex[context] = idx;
      renderTipeOptions(context);
    }

    function cancelEditTipeRow(context) {
      editingTipeIndex[context] = null;
      renderTipeOptions(context);
    }

    function saveEditedTipeRow(context, oldVal, idx) {
      const inp = document.getElementById(`${context}_editInput_${idx}`);
      if (!inp) return;
      const newVal = inp.value.trim();

      if (!newVal || newVal === '__new__') {
        alert('Nama tipe tidak boleh kosong.');
        return;
      }
      if (newVal === oldVal) {
        cancelEditTipeRow(context);
        return;
      }

      const fd = new FormData();
      fd.append('old_tipe', oldVal);
      fd.append('new_tipe', newVal);

      fetch('data-perusahaan?ajax=update_tipe', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            const i = ALL_TIPES.indexOf(oldVal);
            if (i !== -1) ALL_TIPES[i] = res.new_tipe;

            ['create', 'edit'].forEach(ctx => {
              const valInput = document.getElementById(ctx + 'TipeValue');
              const display = document.getElementById(ctx + 'TipeDisplay');
              if (valInput && valInput.value === oldVal) {
                valInput.value = res.new_tipe;
                if (display) display.textContent = res.new_tipe;
              }
            });

            document.querySelectorAll('.badge-tipe').forEach(b => {
              if (b.textContent.trim() === oldVal) {
                b.textContent = res.new_tipe;
              }
            });

            editingTipeIndex[context] = null;
            renderTipeOptions(context);
            showCopyToast('Tipe berhasil diubah menjadi: ' + res.new_tipe);
          } else {
            alert(res.msg || 'Gagal mengubah tipe.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi saat mengedit tipe.');
        });
    }

    function deleteTipeOption(context, tipe) {
      if (!confirm('Yakin ingin menghapus pilihan tipe "' + tipe + '" dari daftar?')) {
        return;
      }

      const fd = new FormData();
      fd.append('tipe', tipe);

      fetch('data-perusahaan?ajax=delete_tipe', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            const i = ALL_TIPES.indexOf(tipe);
            if (i !== -1) ALL_TIPES.splice(i, 1);

            ['create', 'edit'].forEach(ctx => {
              const valInput = document.getElementById(ctx + 'TipeValue');
              const display = document.getElementById(ctx + 'TipeDisplay');
              if (valInput && valInput.value === tipe) {
                const fallback = ALL_TIPES[0] || 'PT / CV';
                valInput.value = fallback;
                if (display) display.textContent = fallback;
              }
            });

            editingTipeIndex[context] = null;
            renderTipeOptions(context);
            showCopyToast('Tipe "' + tipe + '" berhasil dihapus dari daftar.');
          } else {
            alert(res.msg || 'Gagal menghapus tipe.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi saat menghapus tipe.');
        });
    }

    function showAddTipeInput(context) {
      const wrap = document.getElementById(context + 'AddTipeWrap');
      const btn = document.getElementById(context + 'BtnShowAdd');
      const inp = document.getElementById(context + 'AddTipeInput');
      if (wrap) wrap.classList.remove('hidden');
      if (btn) btn.classList.add('hidden');
      if (inp) {
        inp.value = '';
        inp.focus();
        inp.onkeydown = function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            submitAddTipe(context);
          } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddTipe(context);
          }
        };
      }
    }

    function cancelAddTipe(context) {
      const wrap = document.getElementById(context + 'AddTipeWrap');
      const btn = document.getElementById(context + 'BtnShowAdd');
      const inp = document.getElementById(context + 'AddTipeInput');
      if (wrap) wrap.classList.add('hidden');
      if (btn) btn.classList.remove('hidden');
      if (inp) inp.value = '';
    }

    function submitAddTipe(context) {
      const inp = document.getElementById(context + 'AddTipeInput');
      if (!inp) return;
      const nama = inp.value.trim();

      if (!nama || nama === '__new__') {
        alert('Silakan ketik nama tipe baru.');
        inp.focus();
        return;
      }

      const fd = new FormData();
      fd.append('nama_tipe', nama);

      fetch('data-perusahaan?ajax=add_tipe', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            if (!ALL_TIPES.includes(res.nama)) {
              ALL_TIPES.push(res.nama);
            }
            selectTipeOption(context, res.nama);
            cancelAddTipe(context);
            showCopyToast('Tipe baru "' + res.nama + '" berhasil ditambahkan.');
          } else {
            alert(res.msg || 'Gagal menambahkan tipe baru.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Terjadi kesalahan koneksi saat menambahkan tipe baru.');
        });
    }

    let createRowIdx = 0;

    function openCreateModal() {
      document.getElementById('modalCreate').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      renderTipeOptions('create');
    }

    function closeCreateModal() {
      document.getElementById('modalCreate').classList.add('hidden');
      document.body.style.overflow = '';
      document.getElementById('createTipeDropdownMenu')?.classList.add('hidden');
      cancelAddTipe('create');
      cancelEditTipeRow('create');
    }

    /* ---- Create Modal: Add Row ---- */
    function addDataRow(containerId) {
      const idx = createRowIdx++;
      const container = document.getElementById(containerId);
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
            <button type="button" onclick="toggleSubCreate(this, ${idx})"
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
    <div id="subCreate_${idx}" class="hidden ml-4 pl-3 border-l-2 border-purple-300 space-y-2 mt-2">
      <div class="text-xs font-semibold text-purple-700 mb-1">Sub Data (Multiple Data)</div>
      <div class="add-sub-rows space-y-2"></div>
      <button type="button" onclick="addSubRow(${idx})"
              class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1 font-medium bg-purple-50 hover:bg-purple-100 px-2 py-1 rounded border border-purple-200">
        <span class="material-symbols-outlined text-sm">add</span><span>Tambah sub-data</span>
      </button>
    </div>`;
      container.appendChild(div);
    }

    function toggleSubCreate(btn, idx) {
      const row = btn.closest('.data-row');
      const sub = document.getElementById('subCreate_' + idx);
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
          addSubRow(idx);
        }
      } else {
        valWrap.classList.remove('hidden');
        badgeWrap.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
        btn.classList.remove('text-red-600');
        btn.classList.add('text-purple-600');
      }
    }

    /* ---- Sub-Row with Autocomplete ---- */
    function addSubRow(idx) {
      const container = document.querySelector(`#subCreate_${idx} .add-sub-rows`);
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

    /* ---- Edit Modal: Toggle Sub ---- */
    function toggleSubEdit(btn, dataId) {
      const row = btn.closest('.data-row');
      const sub = document.getElementById('subEdit_' + dataId);
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
        if (sub.querySelectorAll('.subdata-row').length === 0) {
          addNewSubRow(dataId);
        }
      } else {
        valWrap.classList.remove('hidden');
        badgeWrap.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
        btn.classList.remove('text-red-600');
        btn.classList.add('text-purple-600');
      }
    }

    function addNewSubRow(dataId) {
      const container = document.getElementById('newSubRows_' + dataId);
      const div = document.createElement('div');
      div.className = 'subdata-row bg-white p-2.5 rounded shadow-sm flex items-start space-x-2 border border-purple-100';
      div.innerHTML = `
    <div class="flex-1 grid grid-cols-2 gap-2">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Label Sub (Autocomplete)</label>
        <input type="text" name="new_sub_label_${dataId}[]" list="labelDatalist" autocomplete="off"
               placeholder="Ketik atau pilih label..."
               onchange="handleLabelAutocomplete(this)"
               class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Value Sub</label>
        <input type="text" name="new_sub_value_${dataId}[]" placeholder="Isi value..."
               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"/>
      </div>
    </div>
    <button type="button" onclick="this.closest('.subdata-row').remove()"
            class="text-red-400 hover:text-red-600 mt-5 p-1" title="Hapus sub-data">
      <span class="material-symbols-outlined text-base">close</span>
    </button>`;
      container.appendChild(div);
    }

    let editNewIdx = 0;

    function addNewDataRowEdit() {
      const idx = editNewIdx++;
      const container = document.getElementById('editNewDataRows');
      const div = document.createElement('div');
      div.className = 'data-row bg-gray-50 p-3 rounded space-y-2 border border-gray-200';
      div.innerHTML = `
    <div class="flex items-start space-x-2">
      <div class="flex-1 grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Label Data (Autocomplete)</label>
          <input type="text" name="new_data_label[]" list="labelDatalist" autocomplete="off"
                 placeholder="Ketik atau pilih label..."
                 onchange="handleLabelAutocomplete(this)"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white" />
        </div>
        <div>
          <div class="value-field-wrap">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Value</label>
            <input type="text" name="new_data_value[]" placeholder="Isi value..."
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"/>
          </div>
          <div class="multiple-data-badge hidden mb-1">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-semibold">
              <span class="material-symbols-outlined text-xs mr-1">splitscreen</span> Multiple Data Aktif
            </span>
          </div>
          <div class="mt-1">
            <button type="button" onclick="toggleNewSubEdit(this, 'new_${idx}')"
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
    <div id="subEditNew_new_${idx}" class="hidden ml-4 pl-3 border-l-2 border-purple-300 space-y-2 mt-2">
      <div class="text-xs font-semibold text-purple-700 mb-1">Sub Data (Multiple Data)</div>
      <div class="new-sub-rows space-y-2"></div>
      <button type="button" onclick="addSubRowEditNew('new_${idx}')"
              class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1 font-medium bg-purple-50 hover:bg-purple-100 px-2 py-1 rounded border border-purple-200">
        <span class="material-symbols-outlined text-sm">add</span><span>Tambah sub-data</span>
      </button>
    </div>`;
      container.appendChild(div);
    }

    function toggleNewSubEdit(btn, uid) {
      const row = btn.closest('.data-row');
      const sub = document.getElementById('subEditNew_' + uid);
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
        if (sub.querySelector('.new-sub-rows').children.length === 0) {
          addSubRowEditNew(uid);
        }
      } else {
        valWrap.classList.remove('hidden');
        badgeWrap.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-xs mr-0.5">splitscreen</span> multiple data';
        btn.classList.remove('text-red-600');
        btn.classList.add('text-purple-600');
      }
    }

    function addSubRowEditNew(uid) {
      const container = document.querySelector(`#subEditNew_${uid} .new-sub-rows`);
      const div = document.createElement('div');
      div.className = 'subdata-row bg-white p-2.5 rounded shadow-sm flex items-start space-x-2 border border-purple-100';
      div.innerHTML = `
    <div class="flex-1 grid grid-cols-2 gap-2">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Label Sub (Autocomplete)</label>
        <input type="text" name="new_sub_label_${uid}[]" list="labelDatalist" autocomplete="off"
               placeholder="Ketik atau pilih label..."
               onchange="handleLabelAutocomplete(this)"
               class="w-full border border-purple-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white" />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Value Sub</label>
        <input type="text" name="new_sub_value_${uid}[]" placeholder="Isi value..."
               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400 bg-white"/>
      </div>
    </div>
    <button type="button" onclick="this.closest('.subdata-row').remove()"
            class="text-red-400 hover:text-red-600 mt-5 p-1" title="Hapus sub-data">
      <span class="material-symbols-outlined text-base">close</span>
    </button>`;
      container.appendChild(div);
    }

    function submitSingleDelete(action, id) {
      if (!confirm('Yakin ingin menghapus item ini?')) return;
      const f = document.getElementById('singleDeleteForm');
      document.getElementById('singleDeleteAction').value = action;
      document.getElementById('singleDeleteId').value = id;
      f.submit();
    }

    /* ---- Modal Detail Toast, Copy, & Search Filter ---- */
    let toastTimer = null;

    function showCopyToast(msg) {
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
        showCopyToast('Disalin: ' + (val.length > 25 ? val.substring(0, 25) + '...' : val));
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
      if (cnt) cnt.innerText = q ? `Ditemukan ${matched} data` : `Total ${cards.length} field`;
      const emptyMsg = document.getElementById('detailEmptySearch');
      if (emptyMsg) {
        emptyMsg.classList.toggle('hidden', matched > 0 || !q);
      }
    }

    /* ---- Search Filter Master Data (Master Nama, Label Data, & Label Sub-Data) ---- */
    function filterMasterTable(query) {
      query = (query || '').toLowerCase().trim();
      const rows = document.querySelectorAll('#masterTableBody tr.master-row');
      let matchedCount = 0;

      rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        const subLabels = row.getAttribute('data-sublabels') || '';
        const mainLabels = row.getAttribute('data-labels') || '';
        const nama = row.getAttribute('data-nama') || '';

        if (!query || searchData.includes(query)) {
          row.classList.remove('hidden');
          matchedCount++;
          const numCell = row.querySelector('.row-number');
          if (numCell) numCell.textContent = matchedCount;

          // Indikator jika pencarian cocok khusus pada label sub-data
          const subBadge = row.querySelector('.sublabel-match-badge');
          if (subBadge) {
            const isSubMatchOnly = query && subLabels.includes(query) && !nama.includes(query) && !mainLabels.includes(query);
            subBadge.classList.toggle('hidden', !isSubMatchOnly);
          }
        } else {
          row.classList.add('hidden');
        }
      });

      const emptyRow = document.getElementById('masterEmptySearchRow');
      if (emptyRow) {
        emptyRow.classList.toggle('hidden', matchedCount > 0 || rows.length === 0);
      }

      const countDisplay = document.getElementById('searchCountBadge');
      if (countDisplay) {
        if (query) {
          countDisplay.textContent = `Ditemukan ${matchedCount} dari ${rows.length} master data`;
        } else {
          countDisplay.textContent = `Total ${rows.length} master data`;
        }
      }

      const clearBtn = document.getElementById('clearSearchBtn');
      if (clearBtn) {
        clearBtn.classList.toggle('hidden', !query);
      }
    }

    function clearMasterSearch() {
      const inp = document.getElementById('searchMasterInput');
      if (inp) {
        inp.value = '';
        filterMasterTable('');
        inp.focus();
      }
    }

    // Ensure custom typed labels get saved into master even if submitted without onchange
    document.addEventListener('DOMContentLoaded', () => {
      renderTipeOptions('create');
      renderTipeOptions('edit');

      // Jalankan filter jika search input terisi saat load
      const searchInp = document.getElementById('searchMasterInput');
      if (searchInp && searchInp.value) {
        filterMasterTable(searchInp.value);
      }

      // Click outside listener untuk menutup custom dropdown
      document.addEventListener('click', (e) => {
        if (!e.target.closest('#createTipeDropdownWrap')) {
          document.getElementById('createTipeDropdownMenu')?.classList.add('hidden');
        }
        if (!e.target.closest('#editTipeDropdownWrap')) {
          document.getElementById('editTipeDropdownMenu')?.classList.add('hidden');
        }
      });

      document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
          const labelInps = form.querySelectorAll('input[name*="label"]');
          labelInps.forEach(inp => {
            const val = inp.value.trim();
            if (val) {
              updateDatalists(val);
              const fd = new FormData();
              fd.append('nama_label', val);
              fetch(window.location.pathname + '?ajax=add_label', {
                method: 'POST',
                body: fd
              });
            }
          });
        });
      });
    });
  </script>

  <!-- Toast Notification Container -->
  <div id="copyToast" class="fixed bottom-5 right-5 z-50 bg-gray-900 text-white text-[11px] px-3.5 py-2 rounded-lg shadow-xl transition-all duration-300 opacity-0 pointer-events-none flex items-center space-x-1.5 border border-gray-700">
    <span class="material-symbols-outlined text-sm text-green-400">check_circle</span>
    <span id="copyToastText">Disalin ke clipboard!</span>
  </div>
</body>

</html>