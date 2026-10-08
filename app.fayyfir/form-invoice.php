<?php
session_start();
require "config.php";

// Polyfill untuk PHP < 8.0 agar kompatibel dengan seluruh versi PHP
if (!function_exists('str_ends_with')) {
  function str_ends_with($haystack, $needle)
  {
    $haystack = (string)($haystack ?? '');
    $needle = (string)($needle ?? '');
    if ($needle === '') return true;
    return substr($haystack, -strlen($needle)) === $needle;
  }
}
if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle)
  {
    $haystack = (string)($haystack ?? '');
    $needle = (string)($needle ?? '');
    if ($needle === '') return true;
    return strncmp($haystack, $needle, strlen($needle)) === 0;
  }
}

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$container_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($container_id === 0) {
  echo "ID Kontainer tidak ditemukan.";
  exit();
}

// Ambil data kontainer
$container = $conn->query("SELECT c.*, p.name AS product_name 
    FROM containers c
    LEFT JOIN products p ON c.product_id = p.id 
    WHERE c.id = $container_id")->fetch_assoc();
if (!$container) {
  echo "Data kontainer tidak valid.";
  exit();
}

// Cek apakah sudah ada invoice
$invoice_existing = $conn->query("SELECT * FROM invoice_info WHERE container_id = $container_id LIMIT 1")->fetch_assoc();

// Helper: Dapatkan nomor urut berikutnya berdasarkan invoice_from
function get_next_sequence_by_from($conn, $invoice_from, $last_seq_hint = null)
{
  $invoice_from = trim($invoice_from ?? '');
  if ($invoice_from === '') {
    return '001';
  }

  // 1. Jika hint last_sequence sudah tersedia langsung
  if ($last_seq_hint !== null && is_numeric($last_seq_hint)) {
    $next = intval($last_seq_hint) + 1;
    return str_pad($next, 3, '0', STR_PAD_LEFT);
  }

  $from_esc = $conn->real_escape_string($invoice_from);

  // 2. Ambil last_sequence yang tersimpan di tabel invoice_senders
  try {
    $q_seq = $conn->query("SELECT last_sequence FROM invoice_senders WHERE TRIM(name) = '$from_esc' LIMIT 1");
    if ($q_seq && $r_s = $q_seq->fetch_assoc()) {
      if (isset($r_s['last_sequence'])) {
        $last_seq = intval($r_s['last_sequence']);
        $next = $last_seq + 1;
        return str_pad($next, 3, '0', STR_PAD_LEFT);
      }
    }
  } catch (Throwable $e) {
  }

  // 3. Fallback: Cari kode pengirim jika ada di invoice_senders
  $sender_code = '';
  try {
    $q_code = $conn->query("SELECT code FROM invoice_senders WHERE TRIM(name) = '$from_esc' LIMIT 1");
    if ($q_code && $r_c = $q_code->fetch_assoc()) {
      $sender_code = strtoupper(trim($r_c['code'] ?? ''));
    }
  } catch (Throwable $e) {
  }

  $sql = "SELECT invoice_no FROM invoice_info WHERE TRIM(invoice_from) = '$from_esc'";
  $last_num = 0;
  try {
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
      while ($row = $res->fetch_assoc()) {
        $inv = trim($row['invoice_no'] ?? '');
        if (empty($inv)) continue;

        // Jika sender_code diketahui dan format invoice {prefix}/{kode}/{seq},
        // abaikan nomor invoice yang kodenya tidak sesuai
        if (!empty($sender_code)) {
          $parts = explode('/', $inv);
          if (count($parts) === 3 && strtoupper(trim($parts[1])) !== $sender_code) {
            continue;
          }
        }

        if (preg_match('/(?:[\/\s])(\d+)$/', $inv, $m)) {
          $num = (int)$m[1];
          if ($num < 10000 && $num > $last_num) {
            $last_num = $num;
          }
        }
      }
    }
  } catch (Throwable $e) {
  }
  $next = $last_num + 1;
  return str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Auto-Migration & Self-Healing: Memastikan tabel & kolom selalu siap di hosting tanpa error 500
try {
  $conn->query("CREATE TABLE IF NOT EXISTS `invoice_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NULL DEFAULT '',
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}

try {
  $conn->query("CREATE TABLE IF NOT EXISTS `invoice_senders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `last_sequence` INT NOT NULL DEFAULT 0,
    `bank_name` VARCHAR(100) NULL DEFAULT '',
    `account_name` VARCHAR(100) NULL DEFAULT '',
    `account_number` VARCHAR(100) NULL DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_sender_name` (`name`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $chk_seq = $conn->query("SHOW COLUMNS FROM `invoice_senders` LIKE 'last_sequence'");
  if ($chk_seq && $chk_seq->num_rows === 0) {
    $conn->query("ALTER TABLE `invoice_senders` ADD COLUMN `last_sequence` INT NOT NULL DEFAULT 0 AFTER `code`");
    $conn->query("UPDATE `invoice_senders` SET `last_sequence` = 158 WHERE TRIM(`name`) = 'Abdulaziz Yahya Sagheer Darwesh'");
    $conn->query("UPDATE `invoice_senders` SET `last_sequence` = 182 WHERE TRIM(`name`) = 'PT ALSHARIF GROUP INDONESIA'");
    $conn->query("UPDATE `invoice_senders` SET `last_sequence` = 13 WHERE TRIM(`name`) = 'PT GLOBAL GATEWAY TRADING'");
    $conn->query("UPDATE `invoice_senders` SET `last_sequence` = 32 WHERE TRIM(`name`) = 'Anggam Abdullah'");
    $conn->query("UPDATE `invoice_senders` SET `last_sequence` = 3 WHERE TRIM(`name`) = 'PT ALANOOD INTERNATIONAL TRADING'");
  }

  $chk_cols = $conn->query("SHOW COLUMNS FROM `invoice_senders` LIKE 'bank_name'");
  if ($chk_cols && $chk_cols->num_rows === 0) {
    $conn->query("ALTER TABLE `invoice_senders` 
      ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT '' AFTER `last_sequence`,
      ADD COLUMN `account_name` VARCHAR(100) NULL DEFAULT '' AFTER `bank_name`,
      ADD COLUMN `account_number` VARCHAR(100) NULL DEFAULT '' AFTER `account_name`");
  }

  $chk_s = $conn->query("SELECT COUNT(*) AS total FROM invoice_senders");
  if ($chk_s && ($r_s = $chk_s->fetch_assoc()) && intval($r_s['total']) === 0) {
    $conn->query("INSERT IGNORE INTO `invoice_senders` (`name`, `code`, `last_sequence`, `bank_name`, `account_name`, `account_number`) VALUES
      ('Abdulaziz Yahya Sagheer Darwesh', 'AYS', 158, 'BRI', 'Abdulaziz Yahya Sagheer Darwesh', '408501000036562'),
      ('PT ALSHARIF GROUP INDONESIA', 'AGI', 182, 'BRI', 'PT ALSHARIF GROUP INDONESIA', '016701002496566'),
      ('PT GLOBAL GATEWAY TRADING', 'GGT', 13, 'BRI', 'GLOBAL GATEWAY TRADING', '010501222333306'),
      ('Anggam Abdullah', 'AAM', 32, 'BRI', 'Anggam Abdullah Muhammad Al sharif', '775301000133568'),
      ('PT ALANOOD INTERNATIONAL TRADING', 'AIT', 3, 'BRI', 'PT ALANOOD INTERNATIONAL TRADING', '324222312')");
  }
} catch (Throwable $e) {
}

try {
  $chk_col = $conn->query("SHOW COLUMNS FROM `invoice_info` LIKE 'template_id'");
  if ($chk_col && $chk_col->num_rows === 0) {
    $conn->query("ALTER TABLE `invoice_info` ADD COLUMN `template_id` INT NULL AFTER `container_id`");
  }
} catch (Throwable $e) {
}

// Ambil daftar template yang sudah tersimpan
$invoice_templates = [];
try {
  $templates_res = $conn->query("SELECT * FROM invoice_templates ORDER BY id ASC");
  if ($templates_res) {
    while ($tpl = $templates_res->fetch_assoc()) {
      $invoice_templates[] = $tpl;
    }
  }
} catch (Throwable $e) {
}

// Ambil daftar pengirim (FROM) yang tersimpan beserta kodenya & payment info dari invoice_senders
$invoice_senders = [];
$sender_codes = [];
$sender_payments = [];
try {
  $senders_res = $conn->query("
    SELECT * FROM invoice_senders 
    ORDER BY (CASE WHEN TRIM(name) = 'Abdulaziz Yahya Sagheer Darwesh' THEN 0 ELSE 1 END), id ASC
  ");
  if ($senders_res) {
    while ($s = $senders_res->fetch_assoc()) {
      $s_name = trim($s['name']);
      $invoice_senders[] = $s;
      $sender_codes[$s_name] = strtoupper(trim($s['code']));
      $sender_payments[$s_name] = [
        'bank_name' => trim($s['bank_name'] ?? ''),
        'account_name' => trim($s['account_name'] ?? ''),
        'account_number' => trim($s['account_number'] ?? '')
      ];
    }
  }
} catch (Throwable $e) {
}

// Fallback jika database belum ada data pengirim
if (empty($invoice_senders)) {
  $invoice_senders = [
    ['id' => 1, 'name' => 'Abdulaziz Yahya Sagheer Darwesh', 'code' => 'AYS', 'bank_name' => 'BRI', 'account_name' => 'Abdulaziz Yahya Sagheer Darwesh', 'account_number' => '408501000036562'],
    ['id' => 2, 'name' => 'PT ALSHARIF GROUP INDONESIA', 'code' => 'AGI', 'bank_name' => 'BRI', 'account_name' => 'PT ALSHARIF GROUP INDONESIA', 'account_number' => '016701002496566'],
    ['id' => 3, 'name' => 'PT GLOBAL GATEWAY TRADING', 'code' => 'GGT', 'bank_name' => 'BRI', 'account_name' => 'GLOBAL GATEWAY TRADING', 'account_number' => '010501222333306'],
    ['id' => 4, 'name' => 'Anggam Abdullah', 'code' => 'AAM', 'bank_name' => 'BRI', 'account_name' => 'Anggam Abdullah Muhammad Al sharif', 'account_number' => '775301000133568']
  ];
  foreach ($invoice_senders as $s) {
    $sender_codes[$s['name']] = $s['code'];
    $sender_payments[$s['name']] = [
      'bank_name' => $s['bank_name'],
      'account_name' => $s['account_name'],
      'account_number' => $s['account_number']
    ];
  }
}

// Ambil semua data unik untuk dropdown lainnya (Aman untuk PHP 7 / tanpa driver mysqlnd)
$material_data = [];
try {
  $material_result = $conn->query("
    SELECT DISTINCT invoice_from, invoice_to, address, account_name, account_number, bank_name
    FROM invoice_info
    WHERE invoice_from IS NOT NULL
       OR invoice_to IS NOT NULL
       OR address IS NOT NULL
       OR account_name IS NOT NULL
       OR account_number IS NOT NULL
       OR bank_name IS NOT NULL
  ");
  if ($material_result) {
    while ($row = $material_result->fetch_assoc()) {
      $material_data[] = $row;
    }
  }
} catch (Throwable $e) {
}

// Fungsi helper: ambil daftar unik dari kolom tertentu
function get_unique_column($rows, $col)
{
  $vals = [];
  foreach ($rows as $row) {
    if (!empty($row[$col])) {
      $val = trim($row[$col]);
      if ($val !== '' && substr($val, -7) !== 'Lainnya') {
        $vals[] = $val;
      }
    }
  }
  return array_values(array_unique($vals));
}

// Bersihkan data material dari nilai 'Lainnya' yang tidak valid
$material_data = array_values(array_filter($material_data, function ($row) {
  foreach ($row as $k => $v) {
    if ($v !== null && substr(trim($v), -7) === 'Lainnya') {
      return false;
    }
  }
  return true;
}));

$invoice_from_list_raw = get_unique_column($material_data, 'invoice_from');
$invoice_to_list       = get_unique_column($material_data, 'invoice_to');
$address_list          = get_unique_column($material_data, 'address');
$bank_name_list        = get_unique_column($material_data, 'bank_name');

// Pastikan data invoice_to dan address dari invoice yang sedang diedit tersedia di dropdown
if ($invoice_existing) {
  if (!empty($invoice_existing['invoice_to']) && !in_array($invoice_existing['invoice_to'], $invoice_to_list)) {
    $invoice_to_list[] = $invoice_existing['invoice_to'];
  }
  if (!empty($invoice_existing['address']) && !in_array($invoice_existing['address'], $address_list)) {
    $address_list[] = $invoice_existing['address'];
  }
}

// Pastikan data pengirim dari riwayat invoice_info tetap ada di $invoice_senders jika belum terdaftar
$known_sender_names = array_map(function ($s) {
  return trim($s['name']);
}, $invoice_senders);
foreach ($invoice_from_list_raw as $raw_from) {
  if (!empty($raw_from) && !in_array($raw_from, $known_sender_names)) {
    $auto_code = 'AYS';
    $invoice_senders[] = [
      'id' => 0,
      'name' => $raw_from,
      'code' => $auto_code,
      'bank_name' => '',
      'account_name' => '',
      'account_number' => ''
    ];
    $sender_codes[$raw_from] = $auto_code;
    $sender_payments[$raw_from] = [
      'bank_name' => '',
      'account_name' => '',
      'account_number' => ''
    ];
    $known_sender_names[] = $raw_from;
  }
}

// Nomor urut berikutnya per pengirim (FROM)
$from_sequences = [];
foreach ($invoice_senders as $snd) {
  $s_name = trim($snd['name']);
  $from_sequences[$s_name] = get_next_sequence_by_from($conn, $s_name, $snd['last_sequence'] ?? null);
}

// Tentukan pengirim terpilih saat ini (Default: Abdulaziz Yahya Sagheer Darwesh)
$default_sender_name = 'Abdulaziz Yahya Sagheer Darwesh';
$current_from_val = '';
if ($invoice_existing && !empty($invoice_existing['invoice_from'])) {
  $current_from_val = trim($invoice_existing['invoice_from']);
} else {
  $current_from_val = $default_sender_name;
}

$current_from_code = $sender_codes[$current_from_val] ?? 'AYS';
$current_from_seq = $from_sequences[$current_from_val] ?? '001';

// Tentukan payment info pengirim terpilih
$current_from_payment = $sender_payments[$current_from_val] ?? [
  'bank_name' => '',
  'account_name' => '',
  'account_number' => ''
];
if ($invoice_existing) {
  if (empty($current_from_payment['bank_name']) && !empty($invoice_existing['bank_name'])) {
    $current_from_payment['bank_name'] = $invoice_existing['bank_name'];
  }
  if (empty($current_from_payment['account_name']) && !empty($invoice_existing['account_name'])) {
    $current_from_payment['account_name'] = $invoice_existing['account_name'];
  }
  if (empty($current_from_payment['account_number']) && !empty($invoice_existing['account_number'])) {
    $current_from_payment['account_number'] = $invoice_existing['account_number'];
  }
}

// Generate nomor invoice otomatis
// Format: {container_number}/{KODE}/{NO_URUT}
// Digit ke-2 (KODE) dan digit ke-3 (NO_URUT) menempel pada pengirim (FROM)
$auto_invoice_no = '';
$selected_template_id = '';

if ($invoice_existing) {
  $selected_template_id = $invoice_existing['template_id'] ?? '';
  $auto_invoice_no = $invoice_existing['invoice_no'];

  // Pastikan kode tengah sinkron dengan pengirim terpilih jika formatnya {prefix}/{KODE}/{NO_URUT}
  $parts = explode('/', $auto_invoice_no);
  if (count($parts) === 3 && strtoupper(trim($parts[1])) !== strtoupper($current_from_code)) {
    $parts[1] = $current_from_code;
    $parts[2] = $current_from_seq;
    $auto_invoice_no = implode('/', $parts);
  }
} else {
  $selected_template_id = '';
  $prefix = !empty($container['number']) ? $container['number'] . '/' : '';
  $auto_invoice_no = $prefix . $current_from_code . '/' . $current_from_seq;
}

// Handle AJAX update template (edit nama dan file template saja, kode invoice sudah dipindah ke pengirim)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_template") {
  header('Content-Type: application/json');
  $tpl_id = intval($_POST["tpl_id"] ?? 0);
  $tpl_name = trim($_POST["tpl_name"] ?? "");

  if ($tpl_id <= 0 || empty($tpl_name)) {
    echo json_encode(["success" => false, "message" => "ID dan Nama Template wajib diisi."]);
    exit();
  }

  // Ambil data template lama
  $old_tpl = $conn->query("SELECT * FROM invoice_templates WHERE id = $tpl_id")->fetch_assoc();
  if (!$old_tpl) {
    echo json_encode(["success" => false, "message" => "Template tidak ditemukan."]);
    exit();
  }

  $file_path = $old_tpl['file_path'];

  // Cek jika ada upload file template baru
  if (isset($_FILES["tpl_file"]) && $_FILES["tpl_file"]["error"] === UPLOAD_ERR_OK) {
    $file = $_FILES["tpl_file"];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($ext !== "pdf") {
      echo json_encode(["success" => false, "message" => "Format file baru harus berupa PDF (.pdf)."]);
      exit();
    }
    $upload_dir = __DIR__ . "/uploads/invoice_templates/";
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    $safe_name = preg_replace('/[^A-Za-z0-9_-]/', '', $tpl_name);
    $filename = "template_" . time() . "_" . $safe_name . ".pdf";
    $target_path = $upload_dir . $filename;
    if (move_uploaded_file($file["tmp_name"], $target_path)) {
      $file_path = "uploads/invoice_templates/" . $filename;
    } else {
      echo json_encode(["success" => false, "message" => "Gagal mengunggah file PDF template baru."]);
      exit();
    }
  }

  $stmt_up = $conn->prepare("UPDATE invoice_templates SET name = ?, file_path = ? WHERE id = ?");
  $stmt_up->bind_param("ssi", $tpl_name, $file_path, $tpl_id);
  if ($stmt_up->execute()) {
    echo json_encode([
      "success" => true,
      "message" => "Template invoice berhasil diperbarui.",
      "template" => [
        "id" => $tpl_id,
        "name" => $tpl_name,
        "file_path" => $file_path
      ]
    ]);
    exit();
  } else {
    echo json_encode(["success" => false, "message" => "Gagal menyimpan perubahan ke database: " . $conn->error]);
    exit();
  }
}

// Handle AJAX reset nomor urut sender ke 001 di database
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "reset_sender_sequence") {
  header('Content-Type: application/json');
  $sender_id = intval($_POST["sender_id"] ?? 0);
  $sender_name = trim($_POST["sender_name"] ?? "");
  $sender_code = "";

  if ($sender_id > 0) {
    $conn->query("UPDATE invoice_senders SET last_sequence = 0 WHERE id = $sender_id");
    $chk = $conn->query("SELECT id, name, code FROM invoice_senders WHERE id = $sender_id LIMIT 1")->fetch_assoc();
    if ($chk) {
      $sender_name = $chk['name'] ?? $sender_name;
      $sender_code = $chk['code'] ?? '';
    }
  } elseif (!empty($sender_name)) {
    $s_esc = $conn->real_escape_string($sender_name);
    $conn->query("UPDATE invoice_senders SET last_sequence = 0 WHERE TRIM(name) = '$s_esc'");
    $chk = $conn->query("SELECT id, name, code FROM invoice_senders WHERE TRIM(name) = '$s_esc' LIMIT 1")->fetch_assoc();
    if ($chk) {
      $sender_id = $chk['id'] ?? 0;
      $sender_code = $chk['code'] ?? '';
    }
  } else {
    echo json_encode(["success" => false, "message" => "Data pengirim tidak valid."]);
    exit();
  }

  echo json_encode([
    "success" => true,
    "message" => "Nomor urut pengirim berhasil direset ke 001 di database.",
    "sender_id" => $sender_id,
    "sender_name" => $sender_name,
    "sender_code" => $sender_code,
    "last_sequence" => 0,
    "next_seq" => "001"
  ]);
  exit();
}

// Handle AJAX save/update pengirim (FROM) & Kode Invoice & Payment Info & Last Sequence
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_sender") {
  header('Content-Type: application/json');
  $sender_id = intval($_POST["sender_id"] ?? 0);
  $sender_name = trim($_POST["sender_name"] ?? "");
  $sender_code = strtoupper(trim($_POST["sender_code"] ?? ""));
  $sender_last_seq = (isset($_POST["last_sequence"]) && $_POST["last_sequence"] !== "") ? intval($_POST["last_sequence"]) : null;
  $bank_name = trim($_POST["bank_name"] ?? "");
  $account_name = trim($_POST["account_name"] ?? "");
  $account_number = trim($_POST["account_number"] ?? "");

  if (empty($sender_name) || empty($sender_code)) {
    echo json_encode(["success" => false, "message" => "Nama Pengirim dan Kode Invoice wajib diisi."]);
    exit();
  }

  if ($sender_id > 0) {
    // Update pengirim lama
    $old_sender = $conn->query("SELECT * FROM invoice_senders WHERE id = $sender_id")->fetch_assoc();
    if ($sender_last_seq !== null) {
      $stmt = $conn->prepare("UPDATE invoice_senders SET name = ?, code = ?, last_sequence = ?, bank_name = ?, account_name = ?, account_number = ? WHERE id = ?");
      $stmt->bind_param("ssisssi", $sender_name, $sender_code, $sender_last_seq, $bank_name, $account_name, $account_number, $sender_id);
    } else {
      $stmt = $conn->prepare("UPDATE invoice_senders SET name = ?, code = ?, bank_name = ?, account_name = ?, account_number = ? WHERE id = ?");
      $stmt->bind_param("sssssi", $sender_name, $sender_code, $bank_name, $account_name, $account_number, $sender_id);
    }
    if ($stmt->execute()) {
      if ($old_sender && trim($old_sender['name']) !== $sender_name) {
        $old_n = trim($old_sender['name']);
        $up_info = $conn->prepare("UPDATE invoice_info SET invoice_from = ? WHERE TRIM(invoice_from) = ?");
        $up_info->bind_param("ss", $sender_name, $old_n);
        $up_info->execute();
      }
      $q_chk = $conn->query("SELECT last_sequence FROM invoice_senders WHERE id = $sender_id LIMIT 1");
      $cur_last = ($q_chk && $r_cs = $q_chk->fetch_assoc()) ? intval($r_cs['last_sequence'] ?? 0) : 0;
      $seq = str_pad($cur_last + 1, 3, '0', STR_PAD_LEFT);
      $prefix = !empty($container['number']) ? $container['number'] . '/' : '';
      echo json_encode([
        "success" => true,
        "message" => "Data pengirim dan Payment Info berhasil diperbarui.",
        "sender" => [
          "id" => $sender_id,
          "name" => $sender_name,
          "code" => $sender_code,
          "last_sequence" => $cur_last,
          "bank_name" => $bank_name,
          "account_name" => $account_name,
          "account_number" => $account_number,
          "next_seq" => $seq,
          "next_invoice_no" => $prefix . $sender_code . '/' . $seq
        ]
      ]);
      exit();
    } else {
      echo json_encode(["success" => false, "message" => "Gagal memperbarui data pengirim: " . $conn->error]);
      exit();
    }
  } else {
    // Tambah pengirim baru
    $init_seq = ($sender_last_seq !== null) ? $sender_last_seq : 0;
    $stmt = $conn->prepare("INSERT INTO invoice_senders (name, code, last_sequence, bank_name, account_name, account_number) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code), last_sequence = VALUES(last_sequence), bank_name = VALUES(bank_name), account_name = VALUES(account_name), account_number = VALUES(account_number)");
    $stmt->bind_param("ssisss", $sender_name, $sender_code, $init_seq, $bank_name, $account_name, $account_number);
    if ($stmt->execute()) {
      $new_id = $stmt->insert_id;
      if (!$new_id) {
        $s_esc = $conn->real_escape_string($sender_name);
        $chk = $conn->query("SELECT id FROM invoice_senders WHERE name = '$s_esc'")->fetch_assoc();
        $new_id = $chk['id'] ?? 0;
      }
      $q_chk = $conn->query("SELECT last_sequence FROM invoice_senders WHERE id = $new_id LIMIT 1");
      $cur_last = ($q_chk && $r_cs = $q_chk->fetch_assoc()) ? intval($r_cs['last_sequence'] ?? 0) : $init_seq;
      $seq = str_pad($cur_last + 1, 3, '0', STR_PAD_LEFT);
      $prefix = !empty($container['number']) ? $container['number'] . '/' : '';
      echo json_encode([
        "success" => true,
        "message" => "Pengirim baru dan Payment Info berhasil disimpan.",
        "sender" => [
          "id" => $new_id,
          "name" => $sender_name,
          "code" => $sender_code,
          "last_sequence" => $cur_last,
          "bank_name" => $bank_name,
          "account_name" => $account_name,
          "account_number" => $account_number,
          "next_seq" => $seq,
          "next_invoice_no" => $prefix . $sender_code . '/' . $seq
        ]
      ]);
      exit();
    } else {
      echo json_encode(["success" => false, "message" => "Gagal menyimpan pengirim baru: " . $conn->error]);
      exit();
    }
  }
}

// Submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $template_id = $_POST["template_id"] ?? "";

  // Penanganan Tambah Template Invoice Baru
  if ($template_id === "tambah_template") {
    $new_tpl_name = trim($_POST["new_template_name"] ?? "");

    if (empty($new_tpl_name)) {
      $error = "❌ Nama Template wajib diisi.";
    } elseif (!isset($_FILES["new_template_file"]) || $_FILES["new_template_file"]["error"] !== UPLOAD_ERR_OK) {
      $error = "❌ File PDF template invoice wajib diunggah.";
    } else {
      $file = $_FILES["new_template_file"];
      $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

      if ($ext !== "pdf") {
        $error = "❌ Format file template harus berupa file PDF (.pdf).";
      } else {
        $upload_dir = __DIR__ . "/uploads/invoice_templates/";
        if (!is_dir($upload_dir)) {
          mkdir($upload_dir, 0755, true);
        }
        $safe_name = preg_replace('/[^A-Za-z0-9_-]/', '', $new_tpl_name);
        $filename = "template_" . time() . "_" . $safe_name . ".pdf";
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($file["tmp_name"], $target_path)) {
          $relative_path = "uploads/invoice_templates/" . $filename;
          $stmt_tpl = $conn->prepare("INSERT INTO invoice_templates (name, code, file_path) VALUES (?, '', ?)");
          $stmt_tpl->bind_param("ss", $new_tpl_name, $relative_path);
          if ($stmt_tpl->execute()) {
            $template_id = $stmt_tpl->insert_id;
          } else {
            $error = "❌ Gagal menyimpan template ke database: " . $conn->error;
          }
        } else {
          $error = "❌ Gagal mengunggah file template PDF.";
        }
      }
    }
  }

  $template_id_val = (!empty($template_id) && is_numeric($template_id)) ? intval($template_id) : null;

  $invoice_from = trim($_POST["invoice_from"] ?? "");
  if ($invoice_from === "IFLainnya") {
    $invoice_from = trim($_POST["if_lainnya"] ?? "");
  }

  // Payment Info: didapatkan dari form (terikat dengan pengirim terpilih) atau fallback ke database pengirim
  $bank_name      = trim($_POST["bank_name"] ?? "");
  $account_name   = trim($_POST["account_name"] ?? "");
  $account_number = trim($_POST["account_number"] ?? "");

  if ((empty($bank_name) || empty($account_name) || empty($account_number)) && !empty($invoice_from)) {
    $from_safe = $conn->real_escape_string($invoice_from);
    $q_snd_pay = $conn->query("SELECT bank_name, account_name, account_number FROM invoice_senders WHERE TRIM(name) = '$from_safe' LIMIT 1");
    if ($q_snd_pay && $r_sp = $q_snd_pay->fetch_assoc()) {
      if (empty($bank_name)) $bank_name = trim($r_sp['bank_name'] ?? '');
      if (empty($account_name)) $account_name = trim($r_sp['account_name'] ?? '');
      if (empty($account_number)) $account_number = trim($r_sp['account_number'] ?? '');
    }
  }

  // Simpan otomatis ke invoice_senders jika pengirim belum terdaftar
  if (!empty($invoice_from) && !isset($sender_codes[$invoice_from])) {
    $ins = $conn->prepare("INSERT IGNORE INTO invoice_senders (name, code, bank_name, account_name, account_number) VALUES (?, 'AYS', ?, ?, ?)");
    $ins->bind_param("ssss", $invoice_from, $bank_name, $account_name, $account_number);
    $ins->execute();
  }

  $invoice_to = trim($_POST["invoice_to"] ?? "");
  if ($invoice_to === "ITLainnya") {
    $invoice_to = trim($_POST["it_lainnya"] ?? "");
  }

  $address = trim($_POST["address"] ?? "");
  if ($address === "ADLainnya") {
    $address = trim($_POST["ad_lainnya"] ?? "");
  }

  $containers     = $_POST["containers"];
  $container_no   = $_POST["container_no"];
  $invoice_no     = trim($_POST["invoice_no"] ?? "");

  // Cari kode invoice dari pengirim (FROM) yang dipilih
  $sender_code = 'AYS';
  $sender_q = $conn->prepare("SELECT code FROM invoice_senders WHERE TRIM(name) = ? LIMIT 1");
  if ($sender_q) {
    $sender_q->bind_param("s", $invoice_from);
    $sender_q->execute();
    $res_s = $sender_q->get_result()->fetch_assoc();
    if (!empty($res_s['code'])) {
      $sender_code = strtoupper(trim($res_s['code']));
    }
  }
  $seq = get_next_sequence_by_from($conn, $invoice_from);
  $prefix = !empty($container['number']) ? $container['number'] . '/' : '';

  if (empty($invoice_no)) {
    $invoice_no = $prefix . $sender_code . '/' . $seq;
  } else {
    // Validasi agar kode tengah selalu sinkron dengan kode pengirim terpilih
    $parts = explode('/', $invoice_no);
    if (count($parts) === 3) {
      if (strtoupper(trim($parts[1])) !== $sender_code) {
        $parts[1] = $sender_code;
        $invoice_no = implode('/', $parts);
      }
    }
  }
  $invoice_date   = $_POST["invoice_date"];
  $description    = $_POST["description"];

  if (!isset($error)) {
    if ($invoice_existing) {
      // UPDATE
      $stmt = $conn->prepare("
        UPDATE invoice_info SET 
          template_id=?, invoice_from=?, invoice_to=?, address=?, containers=?, container_no=?, 
          invoice_no=?, invoice_date=?, account_name=?, account_number=?, bank_name=?, description=? 
        WHERE container_id=?
      ");
      $stmt->bind_param(
        "isssssssssssi",
        $template_id_val,
        $invoice_from,
        $invoice_to,
        $address,
        $containers,
        $container_no,
        $invoice_no,
        $invoice_date,
        $account_name,
        $account_number,
        $bank_name,
        $description,
        $container_id
      );
      if ($stmt->execute()) {
        // Ekstrak digit urutan dari invoice_no yang disimpan (misal 453/AIT/004 -> 4)
        $seq_num = 0;
        $parts = explode('/', $invoice_no);
        if (count($parts) === 3 && is_numeric($parts[2])) {
          $seq_num = intval($parts[2]);
        } elseif (preg_match('/(?:[\/\s])(\d+)$/', $invoice_no, $m_seq)) {
          $seq_num = intval($m_seq[1]);
        }

        // Update last_sequence di tabel invoice_senders untuk pengirim terpilih
        if ($seq_num > 0 && !empty($invoice_from)) {
          $from_esc = $conn->real_escape_string($invoice_from);
          $conn->query("UPDATE invoice_senders SET last_sequence = $seq_num WHERE TRIM(name) = '$from_esc'");
        }

        header("Location: invoice-pdf.php?invoice_id=" . $invoice_existing['id']);
        exit();
      } else {
        $error = "❌ Gagal memperbarui data invoice: " . $stmt->error;
      }
    } else {
      // INSERT baru
      $stmt = $conn->prepare("
        INSERT INTO invoice_info 
        (container_id, template_id, invoice_from, invoice_to, address, containers, container_no, invoice_no, invoice_date, account_name, account_number, bank_name, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        "iisssssssssss",
        $container_id,
        $template_id_val,
        $invoice_from,
        $invoice_to,
        $address,
        $containers,
        $container_no,
        $invoice_no,
        $invoice_date,
        $account_name,
        $account_number,
        $bank_name,
        $description
      );
      if ($stmt->execute()) {
        $new_inv_id = $stmt->insert_id;

        // Ekstrak digit urutan dari invoice_no yang disimpan (misal 453/AIT/004 -> 4)
        $seq_num = 0;
        $parts = explode('/', $invoice_no);
        if (count($parts) === 3 && is_numeric($parts[2])) {
          $seq_num = intval($parts[2]);
        } elseif (preg_match('/(?:[\/\s])(\d+)$/', $invoice_no, $m_seq)) {
          $seq_num = intval($m_seq[1]);
        }

        // Update last_sequence di tabel invoice_senders untuk pengirim terpilih
        if ($seq_num > 0 && !empty($invoice_from)) {
          $from_esc = $conn->real_escape_string($invoice_from);
          $conn->query("UPDATE invoice_senders SET last_sequence = $seq_num WHERE TRIM(name) = '$from_esc'");
        }

        header("Location: invoice-pdf.php?invoice_id=" . $new_inv_id);
        exit();
      } else {
        $error = "❌ Gagal menyimpan data invoice: " . $stmt->error;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Invoice - Fayyfir</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Informasi Invoice</h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded shadow space-y-6">
      <?php if (isset($error)): ?>
        <div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block text-sm font-medium">Invoice Date</label>
          <input type="date" name="invoice_date" value="<?= htmlspecialchars($invoice_existing['invoice_date'] ?? date('Y-m-d')) ?>" class="w-full px-3 py-2 border rounded" />
        </div>

        <!-- Template Invoice -->
        <div class="p-3 bg-gray-50 border border-gray-200 rounded space-y-3">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Template Invoice (PDF)</label>
            <p class="text-xs text-gray-500 mb-1.5">Pilih template PDF sebagai latar belakang kop surat invoice.</p>

            <!-- Hidden native select for form submit and state tracking -->
            <select name="template_id" id="TemplateSelect" class="hidden">
              <option value="" data-name="">-- Template Standar (Bawaan) --</option>
              <?php foreach ($invoice_templates as $tpl): ?>
                <option value="<?= $tpl['id'] ?>"
                  data-id="<?= $tpl['id'] ?>"
                  data-name="<?= htmlspecialchars($tpl['name']) ?>"
                  data-file="<?= htmlspecialchars($tpl['file_path']) ?>"
                  <?= ($selected_template_id == $tpl['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tpl['name']) ?>
                </option>
              <?php endforeach; ?>
              <option value="tambah_template" class="font-bold text-blue-600">+ Tambah Template Invoice</option>
            </select>

            <!-- Custom Dropdown Component -->
            <div class="relative" id="customTemplateDropdown">
              <!-- Trigger Box (Tampilan Dropdown Utama) -->
              <div id="customTemplateToggle" onclick="toggleCustomTemplateDropdown()" class="w-full border px-3 py-2 rounded bg-white text-left flex items-center justify-between hover:border-gray-400 cursor-pointer focus:outline-none focus:ring-1 focus:ring-yellow-500 shadow-sm transition">
                <!-- Bagian Kiri: Nama Template Terpilih -->
                <div id="customTemplateSelectedText" class="text-sm text-gray-800 truncate flex items-center gap-2 flex-1 mr-2">
                  <!-- Diisi otomatis oleh JS -->
                </div>
                <!-- Bagian Kanan: Tulisan Edit (jika template dipilih) & Panah Dropdown -->
                <div class="flex items-center gap-2 flex-shrink-0">
                  <button type="button"
                    id="customTemplateEditCurrentBtn"
                    onclick="event.stopPropagation(); openEditCurrentTemplate()"
                    class="hidden inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-500 hover:text-white border border-yellow-300 rounded transition shadow-xs"
                    title="Edit nama dan file template ini">
                    <span class="material-symbols-outlined text-sm">edit</span>
                    <span>Edit</span>
                  </button>
                  <span class="material-symbols-outlined text-gray-500 text-base transition-transform duration-200" id="customTemplateChevron">expand_more</span>
                </div>
              </div>

              <!-- Dropdown Menu List -->
              <div id="customTemplateList" class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-30 max-h-64 overflow-y-auto divide-y divide-gray-100">

                <!-- Option: Template Standar (Bawaan) -->
                <div class="template-item-row flex items-center justify-between px-3 py-2.5 hover:bg-gray-100 cursor-pointer transition" onclick="selectTemplateOption('')">
                  <span class="text-sm text-gray-600">-- Template Standar (Bawaan) --</span>
                </div>

                <!-- Loop Template Tersimpan -->
                <?php foreach ($invoice_templates as $tpl): ?>
                  <div class="template-item-row flex items-center justify-between px-3 py-2.5 hover:bg-yellow-50 cursor-pointer group transition"
                    id="tplRow_<?= $tpl['id'] ?>"
                    onclick="selectTemplateOption('<?= $tpl['id'] ?>')">
                    <!-- Bagian Kiri: Nama Template -->
                    <div class="flex items-center space-x-2 text-sm text-gray-800 flex-1 truncate pr-3">
                      <span class="material-symbols-outlined text-gray-400 group-hover:text-yellow-600 text-base flex-shrink-0">description</span>
                      <span class="truncate font-medium tpl-name-display"><?= htmlspecialchars($tpl['name']) ?></span>
                    </div>
                    <!-- Bagian Kanan: Tulisan / Tombol Edit -->
                    <button type="button"
                      onclick="event.stopPropagation(); openEditTemplateModalById('<?= $tpl['id'] ?>')"
                      class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-500 hover:text-white border border-yellow-300 rounded transition shadow-xs"
                      title="Edit nama dan file template ini">
                      <span class="material-symbols-outlined text-sm">edit</span>
                      <span>Edit</span>
                    </button>
                  </div>
                <?php endforeach; ?>

                <!-- Option Paling Bawah: Tambah Template Baru -->
                <div class="template-item-row flex items-center justify-between px-3 py-2.5 bg-blue-50 hover:bg-blue-100 cursor-pointer text-blue-700 font-semibold transition" onclick="selectTemplateOption('tambah_template')">
                  <div class="flex items-center space-x-2 text-sm">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    <span>+ Tambah Template Invoice</span>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Form Tambah Template Baru -->
          <div id="TambahTemplateBox" class="p-3 bg-blue-50 border border-blue-200 rounded space-y-3 hidden">
            <div class="text-xs font-bold text-blue-900 flex items-center">
              <span class="material-symbols-outlined text-sm mr-1">post_add</span>
              Tambah Template Invoice Baru
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700">Nama Template <span class="text-red-500">*</span></label>
              <input type="text" name="new_template_name" id="NewTemplateName" class="w-full mt-1 px-3 py-1.5 border rounded bg-white text-sm" placeholder="Contoh: Invoice Template PT ABC" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700">Unggah File PDF Template <span class="text-red-500">*</span></label>
              <input type="file" name="new_template_file" id="NewTemplateFile" accept=".pdf,application/pdf" class="w-full mt-1 px-2 py-1.5 border rounded bg-white text-xs" />
              <p class="text-[11px] text-gray-500 mt-1">Format file harus PDF (Kop / Background A4).</p>
            </div>
          </div>
        </div>

        <!-- Invoice From (Dengan Custom Dropdown & Tombol Edit Selalu Muncul) -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Invoice From</label>
          <p class="text-xs text-gray-500 mb-1.5">Pilih pengirim invoice. Kode invoice (bagian tengah) mengikuti kode pengirim ini.</p>

          <!-- Hidden native select for form submit and state tracking -->
          <select name="invoice_from" id="IFSelect" class="hidden">
            <?php foreach ($invoice_senders as $snd): ?>
              <option value="<?= htmlspecialchars($snd['name']) ?>"
                data-id="<?= $snd['id'] ?>"
                data-name="<?= htmlspecialchars($snd['name']) ?>"
                data-code="<?= htmlspecialchars($snd['code']) ?>"
                data-lastseq="<?= intval($snd['last_sequence'] ?? 0) ?>"
                data-nextseq="<?= htmlspecialchars($from_sequences[trim($snd['name'])] ?? '001') ?>"
                data-bank="<?= htmlspecialchars($snd['bank_name'] ?? '') ?>"
                data-accname="<?= htmlspecialchars($snd['account_name'] ?? '') ?>"
                data-accnum="<?= htmlspecialchars($snd['account_number'] ?? '') ?>"
                <?= ($current_from_val === trim($snd['name'])) ? 'selected' : '' ?>>
                <?= htmlspecialchars($snd['name']) ?> (Kode: <?= htmlspecialchars($snd['code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Custom Dropdown Component for Invoice From -->
          <div class="relative" id="customSenderDropdown">
            <!-- Trigger Box (Tampilan Dropdown Utama) -->
            <div id="customSenderToggle" onclick="toggleCustomSenderDropdown()" class="w-full border px-3 py-2 rounded bg-white text-left flex items-center justify-between hover:border-gray-400 cursor-pointer focus:outline-none focus:ring-1 focus:ring-yellow-500 shadow-sm transition">
              <!-- Bagian Kiri: Nama Pengirim & Kode Terpilih (Langsung di-render oleh PHP) -->
              <div id="customSenderSelectedText" class="text-sm text-gray-800 truncate flex items-center gap-2 flex-1 mr-2">
                <span class="material-symbols-outlined text-gray-500 text-base flex-shrink-0">business</span>
                <span class="font-medium text-gray-800 truncate"><?= htmlspecialchars($current_from_val) ?></span>
                <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded font-mono flex-shrink-0">Kode: <?= htmlspecialchars($current_from_code) ?></span>
              </div>
              <!-- Bagian Kanan: Tombol Edit (SELALU MUNCUL ADA MAUPUN TIDAK DATANYA) & Panah Dropdown -->
              <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button"
                  id="customSenderEditCurrentBtn"
                  onclick="event.stopPropagation(); openEditCurrentSender()"
                  class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-500 hover:text-white border border-yellow-300 rounded transition shadow-xs"
                  title="Edit atau isi data pengirim dan kode invoice">
                  <span class="material-symbols-outlined text-sm">edit</span>
                  <span>Edit</span>
                </button>
                <span class="material-symbols-outlined text-gray-500 text-base transition-transform duration-200" id="customSenderChevron">expand_more</span>
              </div>
            </div>

            <!-- Dropdown Menu List -->
            <div id="customSenderList" class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-30 max-h-64 overflow-y-auto divide-y divide-gray-100">

              <!-- Loop Senders -->
              <?php foreach ($invoice_senders as $snd): ?>
                <div class="sender-item-row flex items-center justify-between px-3 py-2.5 hover:bg-yellow-50 cursor-pointer group transition"
                  id="senderRow_<?= $snd['id'] ?>"
                  data-id="<?= $snd['id'] ?>"
                  data-name="<?= htmlspecialchars($snd['name']) ?>"
                  data-code="<?= htmlspecialchars($snd['code']) ?>"
                  data-lastseq="<?= intval($snd['last_sequence'] ?? 0) ?>"
                  data-nextseq="<?= htmlspecialchars($from_sequences[trim($snd['name'])] ?? '001') ?>"
                  data-bank="<?= htmlspecialchars($snd['bank_name'] ?? '') ?>"
                  data-accname="<?= htmlspecialchars($snd['account_name'] ?? '') ?>"
                  data-accnum="<?= htmlspecialchars($snd['account_number'] ?? '') ?>"
                  onclick="selectSenderOption(this.dataset.name)">
                  <!-- Bagian Kiri: Nama Pengirim & Kode -->
                  <div class="flex items-center space-x-2 text-sm text-gray-800 flex-1 truncate pr-3">
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-yellow-600 text-base flex-shrink-0">business</span>
                    <span class="truncate font-medium sender-name-display"><?= htmlspecialchars($snd['name']) ?></span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded font-mono flex-shrink-0 sender-code-display">Kode: <?= htmlspecialchars($snd['code']) ?></span>
                  </div>
                  <!-- Bagian Kanan: Tombol Edit Spesifik Baris Ini -->
                  <button type="button"
                    onclick="event.stopPropagation(); openEditSenderModalById('<?= $snd['id'] ?>')"
                    class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-500 hover:text-white border border-yellow-300 rounded transition shadow-xs"
                    title="Edit nama dan kode pengirim ini">
                    <span class="material-symbols-outlined text-sm">edit</span>
                    <span>Edit</span>
                  </button>
                </div>
              <?php endforeach; ?>

              <!-- Option Paling Bawah: Tambah / Isi Pengirim Baru -->
              <div class="sender-item-row flex items-center justify-between px-3 py-2.5 bg-blue-50 hover:bg-blue-100 cursor-pointer text-blue-700 font-semibold transition" onclick="openAddSenderModal(); closeCustomSenderDropdown();">
                <div class="flex items-center space-x-2 text-sm">
                  <span class="material-symbols-outlined text-base">person_add</span>
                  <span>+ Tambah / Isi Pengirim Baru</span>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Invoice To -->
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700">Invoice To</label>
            <!-- <button type="button" 
                    onclick="enableNewOption('ITSelect', 'ITOther', 'ITLainnya')" 
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center gap-0.5"
                    title="Ketik penerima invoice baru secara manual">
              <span class="material-symbols-outlined text-sm leading-none">add</span>
              <span>Ketik Penerima Baru</span>
            </button> -->
          </div>
          <select name="invoice_to" id="ITSelect" onchange="handleToggleOther(this, 'ITOther', 'ITLainnya')" class="mt-1 w-full border px-3 py-2 rounded toggle-input bg-white focus:ring focus:ring-yellow-300">
            <option value="">-- Invoice untuk --</option>
            <?php foreach ($invoice_to_list as $val): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= ($invoice_existing && $invoice_existing['invoice_to'] == $val) ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
            <?php endforeach; ?>
            <option value="ITLainnya">Tambah baru...</option>
          </select>
          <div id="ITOtherWrapper" class="<?= ($invoice_existing && $invoice_existing['invoice_to'] === 'ITLainnya') ? '' : 'hidden' ?> mt-2">
            <input type="text" name="it_lainnya" id="ITOther" 
                   class="w-full border-2 border-yellow-400 bg-yellow-50 px-3 py-2 rounded focus:ring-2 focus:ring-yellow-400 focus:bg-white text-sm" 
                   placeholder="Ketik nama penerima (Invoice To) baru…" />
          </div>
        </div>

        <!-- Address -->
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700">Address</label>
            <!-- <button type="button" 
                    onclick="enableNewOption('ADSelect', 'ADOther', 'ADLainnya')" 
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center gap-0.5"
                    title="Ketik alamat baru secara manual">
              <span class="material-symbols-outlined text-sm leading-none">add</span>
              <span>Ketik Alamat Baru</span>
            </button> -->
          </div>
          <select name="address" id="ADSelect" onchange="handleToggleOther(this, 'ADOther', 'ADLainnya')" class="mt-1 w-full border px-3 py-2 rounded toggle-input bg-white focus:ring focus:ring-yellow-300">
            <option value="">-- Pilih alamat --</option>
            <?php foreach ($address_list as $val): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= ($invoice_existing && $invoice_existing['address'] == $val) ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
            <?php endforeach; ?>
            <option value="ADLainnya">Tambah baru...</option>
          </select>
          <div id="ADOtherWrapper" class="<?= ($invoice_existing && $invoice_existing['address'] === 'ADLainnya') ? '' : 'hidden' ?> mt-2">
            <input type="text" name="ad_lainnya" id="ADOther" 
                   class="w-full border-2 border-yellow-400 bg-yellow-50 px-3 py-2 rounded focus:ring-2 focus:ring-yellow-400 focus:bg-white text-sm" 
                   placeholder="Ketik alamat baru…" />
          </div>
        </div>

        <!-- Containers & Container No -->
        <div>
          <label class="block text-sm font-medium">Containers</label>
          <input type="text" name="containers" class="w-full px-3 py-2 border rounded" placeholder="Kontainer..." value="<?= htmlspecialchars($invoice_existing['containers'] ?? $container['shipping_line'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-sm font-medium">Container No.</label>
          <input type="text" name="container_no" class="w-full px-3 py-2 border rounded" placeholder="Nomor kontainer..." value="<?= htmlspecialchars($invoice_existing['container_no'] ?? $container['container_number'] ?? '') ?>" />
        </div>

        <!-- Invoice No. dengan Tombol Reset di dalam input field -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Invoice No.</label>
          <div class="relative flex items-center">
            <input type="text"
              id="InvoiceNoInput"
              name="invoice_no"
              class="w-full pl-3 pr-28 py-2 border rounded focus:ring focus:ring-yellow-300 font-mono text-sm transition"
              placeholder="Nomor Invoice..."
              value="<?= htmlspecialchars($invoice_existing['invoice_no'] ?? $auto_invoice_no) ?>">
            <button type="button"
              id="btnResetInvoiceNo"
              onclick="resetInvoiceNoSequence()"
              class="absolute right-1.5 px-2 py-1 inline-flex items-center gap-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-400 hover:text-gray-900 border border-yellow-300 rounded transition shadow-xs cursor-pointer select-none"
              title="Reset digit ke-3 nomor urut kembali ke 001">
              <span class="material-symbols-outlined text-sm leading-none">restart_alt</span>
              <span class="leading-none">Reset (001)</span>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium">Item Description</label>
          <textarea name="description" class="w-full px-3 py-2 border rounded"><?= htmlspecialchars($invoice_existing['description'] ?? "40 Ft ({$container['container_number']}) {$container['region_name']} {$container['product_name']} cnt{$container['number']}") ?></textarea>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded">
            <?= $invoice_existing ? "Update & Cetak Invoice" : "Lanjut Cetak Invoice" ?>
          </button>
        </div>
      </form>
    </div>
  </main>

  <script>
    // Handler toggle input Tambah Baru pada Invoice To & Address
    function handleToggleOther(selectEl, otherId, lainnyaVal) {
      if (!selectEl) return;
      const otherEl = document.getElementById(otherId);
      const wrapperEl = document.getElementById(otherId + "Wrapper");
      if (!otherEl) return;

      if (selectEl.value === lainnyaVal) {
        if (wrapperEl) {
          wrapperEl.classList.remove("hidden");
          wrapperEl.style.display = "block";
        }
        otherEl.classList.remove("hidden");
        otherEl.style.display = "block";
        otherEl.required = true;
        setTimeout(() => otherEl.focus(), 50);
      } else {
        if (wrapperEl) {
          wrapperEl.classList.add("hidden");
          wrapperEl.style.display = "none";
        }
        otherEl.classList.add("hidden");
        otherEl.style.display = "none";
        otherEl.required = false;
        otherEl.value = "";
      }
    }

    // Tombol helper (+ Ketik Baru) untuk memilih opsi Tambah baru & membuka input
    function enableNewOption(selectId, otherId, lainnyaVal) {
      const sel = document.getElementById(selectId);
      if (sel) {
        sel.value = lainnyaVal;
        handleToggleOther(sel, otherId, lainnyaVal);
      }
    }

    const materialData = <?= json_encode($material_data) ?>;

    function fillOptions(selectId, options, defaultText, lainnyaVal, selectedVal = "") {
      const select = document.getElementById(selectId);
      if (!select) return;
      select.innerHTML = `<option value="">${defaultText}</option>`;
      const cleanOptions = [...new Set(options)].filter(opt => opt && !opt.endsWith("Lainnya"));
      cleanOptions.forEach(opt => {
        const o = document.createElement("option");
        o.value = opt;
        o.textContent = opt;
        if (selectedVal && opt === selectedVal) {
          o.selected = true;
        }
        select.appendChild(o);
      });
      if (lainnyaVal) {
        const o = document.createElement("option");
        o.value = lainnyaVal;
        o.textContent = "Tambah baru...";
        if (selectedVal && lainnyaVal === selectedVal) {
          o.selected = true;
        }
        select.appendChild(o);
      }
    }

    // Sender Payments data map
    const senderPayments = <?= json_encode($sender_payments) ?>;

    // Fungsi update tampilan dan hidden input Payment Info berdasarkan Pengirim (FROM)
    function updateSenderPaymentInfo(senderName) {
      const p = senderPayments[senderName] || {
        bank_name: '',
        account_name: '',
        account_number: ''
      };
      const dBank = document.getElementById("displayPaymentBank");
      const dName = document.getElementById("displayPaymentAccName");
      const dNum = document.getElementById("displayPaymentAccNum");
      const hBank = document.getElementById("hiddenPaymentBank");
      const hName = document.getElementById("hiddenPaymentAccName");
      const hNum = document.getElementById("hiddenPaymentAccNum");

      if (dBank) dBank.textContent = p.bank_name || "-";
      if (dName) dName.textContent = p.account_name || "-";
      if (dNum) dNum.textContent = p.account_number || "-";

      if (hBank) hBank.value = p.bank_name || "";
      if (hName) hName.value = p.account_name || "";
      if (hNum) hNum.value = p.account_number || "";
    }

    // Fungsi Reset digit ke-3 nomor invoice kembali ke 001 di database & tampilan
    async function resetInvoiceNoSequence() {
      const ifSelect = document.getElementById("IFSelect");
      const currentSender = (getCurrentFromValue() || "Abdulaziz Yahya Sagheer Darwesh").trim();
      let senderId = 0;
      let senderCode = getCurrentSenderCode();
      if (ifSelect) {
        const opt = ifSelect.querySelector(`option[value="${currentSender}"]`);
        if (opt && opt.dataset.id) {
          senderId = parseInt(opt.dataset.id);
        }
      }

      // Dialog konfirmasi ke pengguna sesuai permintaan
      const confirmMsg = `Apakah anda yakin reset no urut senders ${currentSender} menjadi 001?`;
      if (!confirm(confirmMsg)) {
        return;
      }

      const btnReset = document.getElementById("btnResetInvoiceNo");
      const originalBtnHtml = btnReset ? btnReset.innerHTML : "";
      if (btnReset) {
        btnReset.disabled = true;
        btnReset.innerHTML = `<span class="material-symbols-outlined text-sm leading-none animate-spin">refresh</span> <span class="leading-none">Mereset...</span>`;
      }

      try {
        const formData = new FormData();
        formData.append("action", "reset_sender_sequence");
        formData.append("sender_name", currentSender);
        if (senderId > 0) {
          formData.append("sender_id", senderId);
        }

        const res = await fetch(window.location.href, {
          method: "POST",
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          // Update cache nomor urut di JS
          fromSequences[currentSender] = "001";
          if (data.sender_code) {
            senderCodes[currentSender] = data.sender_code;
            senderCode = data.sender_code;
          }

          // Update dataset option pada IFSelect
          if (ifSelect) {
            const opt = ifSelect.querySelector(`option[value="${currentSender}"]`) || (senderId ? ifSelect.querySelector(`option[data-id="${senderId}"]`) : null);
            if (opt) {
              opt.dataset.lastseq = "0";
              opt.dataset.nextseq = "001";
            }
          }

          // Update dataset baris pengirim di custom list
          const sRow = document.getElementById(`senderRow_${data.sender_id || senderId}`);
          if (sRow) {
            sRow.dataset.lastseq = "0";
            sRow.dataset.nextseq = "001";
          }

          // Update input modal edit pengirim jika ada
          const editLastSeqInput = document.getElementById("edit_sender_last_seq");
          if (editLastSeqInput) {
            editLastSeqInput.value = "0";
          }

          // Update field Invoice No. menjadi digit ke-3 001
          const invInput = document.getElementById("InvoiceNoInput") || document.querySelector("input[name='invoice_no']");
          if (invInput) {
            const val = invInput.value.trim();
            if (!val) {
              const prefix = containerNumber ? containerNumber + "/" : "";
              invInput.value = prefix + senderCode + "/001";
            } else {
              const parts = val.split("/");
              if (parts.length === 3) {
                parts[1] = senderCode;
                parts[2] = "001";
                invInput.value = parts.join("/");
              } else if (/\/\d+$/.test(val)) {
                invInput.value = val.replace(/\/\d+$/, "/001");
              } else {
                invInput.value = val + "/001";
              }
            }

            // Animasi feedback visual
            invInput.classList.add("ring-2", "ring-yellow-400", "bg-yellow-50");
            setTimeout(() => {
              invInput.classList.remove("ring-2", "ring-yellow-400", "bg-yellow-50");
            }, 600);
          }

          alert(`✅ No. urut pengirim ${currentSender} berhasil direset menjadi 001 di database.`);
        } else {
          alert(`❌ Gagal reset: ${data.message || 'Terjadi kesalahan sistem'}`);
        }
      } catch (err) {
        console.error(err);
        alert("❌ Terjadi kesalahan jaringan saat mencoba mereset nomor urut.");
      } finally {
        if (btnReset) {
          btnReset.disabled = false;
          btnReset.innerHTML = originalBtnHtml;
        }
      }
    }

    // Template Invoice selection
    const templateSelect = document.getElementById("TemplateSelect");
    const tambahTemplateBox = document.getElementById("TambahTemplateBox");
    const newTemplateName = document.getElementById("NewTemplateName");
    const newTemplateFile = document.getElementById("NewTemplateFile");
    const invoiceNoInput = document.querySelector("input[name='invoice_no']");
    const containerNumber = <?= json_encode($container['number'] ?? '') ?>;
    const isExistingInvoice = <?= json_encode((bool)$invoice_existing) ?>;
    const fromSequences = <?= json_encode($from_sequences) ?>;
    const senderCodes = <?= json_encode($sender_codes) ?>;

    function getCurrentFromValue() {
      const ifSelect = document.getElementById("IFSelect");
      if (!ifSelect) return "";
      return ifSelect.value ? ifSelect.value.trim() : "";
    }

    function getSequenceForFrom(fromName) {
      const name = (fromName || "").trim();
      if (name && fromSequences[name]) {
        return fromSequences[name];
      }
      if (!name) {
        if (fromSequences["Abdulaziz Yahya Sagheer Darwesh"]) {
          return fromSequences["Abdulaziz Yahya Sagheer Darwesh"];
        }
      }
      return "001";
    }

    function getCurrentSenderCode() {
      const ifSelect = document.getElementById("IFSelect");
      if (!ifSelect || !ifSelect.value) return "AYS";
      const val = ifSelect.value.trim();
      let opt = null;
      for (let i = 0; i < ifSelect.options.length; i++) {
        if (ifSelect.options[i].value.trim() === val) {
          opt = ifSelect.options[i];
          break;
        }
      }
      if (opt && opt.dataset.code) {
        return opt.dataset.code;
      }
      return senderCodes[val] || "AYS";
    }

    function recomputeInvoiceNumber(forceUpdate = false) {
      if (!invoiceNoInput) return;
      const code = getCurrentSenderCode();
      const fromName = getCurrentFromValue();
      const seq = getSequenceForFrom(fromName);
      const curVal = invoiceNoInput.value.trim();
      const parts = curVal.split("/");

      if (forceUpdate) {
        // User secara eksplisit memilih/mengubah pengirim di dropdown atau modal
        let prefix = containerNumber || "";
        if (parts.length === 3 && parts[0]) {
          prefix = parts[0];
        }
        const prefixStr = prefix ? prefix + "/" : "";
        invoiceNoInput.value = prefixStr + code + "/" + seq;
      } else {
        // Passive check (saat inisialisasi awal)
        if (!isExistingInvoice) {
          const prefixStr = containerNumber ? containerNumber + "/" : "";
          invoiceNoInput.value = prefixStr + code + "/" + seq;
        } else {
          // Jika invoice sudah ada, tapi kode tengah tidak sinkron dengan pengirim saat ini
          if (parts.length === 3 && parts[1].toUpperCase() !== code.toUpperCase()) {
            const prefix = parts[0] || (containerNumber || "");
            const prefixStr = prefix ? prefix + "/" : "";
            invoiceNoInput.value = prefixStr + code + "/" + seq;
          }
        }
      }
    }

    function escapeHtml(text) {
      if (!text) return "";
      return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // --- TEMPLATE DROPDOWN LOGIC ---
    function toggleCustomTemplateDropdown() {
      const list = document.getElementById("customTemplateList");
      const chevron = document.getElementById("customTemplateChevron");
      if (!list) return;
      const isOpen = !list.classList.contains("hidden");
      if (isOpen) {
        closeCustomTemplateDropdown();
      } else {
        closeCustomSenderDropdown();
        list.classList.remove("hidden");
        if (chevron) chevron.classList.add("transform", "rotate-180");
      }
    }

    function closeCustomTemplateDropdown() {
      const list = document.getElementById("customTemplateList");
      const chevron = document.getElementById("customTemplateChevron");
      if (list) list.classList.add("hidden");
      if (chevron) chevron.classList.remove("transform", "rotate-180");
    }

    function selectTemplateOption(val) {
      templateSelect.value = val;
      updateTemplateSelection();
      closeCustomTemplateDropdown();
    }

    function updateTemplateSelection() {
      if (!templateSelect) return;
      const val = templateSelect.value;
      const selectedBox = document.getElementById("customTemplateSelectedText");

      if (val === "tambah_template") {
        tambahTemplateBox.classList.remove("hidden");
        newTemplateName.required = true;
        newTemplateFile.required = true;

        if (selectedBox) {
          selectedBox.innerHTML = `
            <span class="material-symbols-outlined text-blue-600 text-base">add_circle</span>
            <span class="font-semibold text-blue-600">+ Tambah Template Invoice Baru</span>
          `;
        }
      } else {
        tambahTemplateBox.classList.add("hidden");
        newTemplateName.required = false;
        newTemplateFile.required = false;

        if (val) {
          const selectedOption = templateSelect.options[templateSelect.selectedIndex];
          if (selectedOption) {
            const name = selectedOption.dataset.name || selectedOption.textContent;
            if (selectedBox) {
              selectedBox.innerHTML = `
                <span class="material-symbols-outlined text-gray-500 text-base">description</span>
                <span class="font-medium text-gray-800 truncate">${escapeHtml(name)}</span>
              `;
            }
          }
        } else {
          if (selectedBox) {
            selectedBox.innerHTML = `<span class="text-gray-500">-- Template Standar (Bawaan) --</span>`;
          }
        }
      }

      // Update tombol Edit di bagian kanan dropdown trigger template
      const editCurrentBtn = document.getElementById("customTemplateEditCurrentBtn");
      if (editCurrentBtn) {
        if (val && val !== "tambah_template") {
          editCurrentBtn.classList.remove("hidden");
          editCurrentBtn.dataset.tplId = val;
        } else {
          editCurrentBtn.classList.add("hidden");
          editCurrentBtn.dataset.tplId = "";
        }
      }
    }

    function openEditCurrentTemplate() {
      const editCurrentBtn = document.getElementById("customTemplateEditCurrentBtn");
      const id = editCurrentBtn ? editCurrentBtn.dataset.tplId : null;
      if (id) {
        openEditTemplateModalById(id);
      }
    }

    if (templateSelect) {
      templateSelect.addEventListener("change", updateTemplateSelection);
      updateTemplateSelection();
      if (templateSelect.value === "tambah_template") {
        tambahTemplateBox.classList.remove("hidden");
        newTemplateName.required = true;
        newTemplateFile.required = true;
      }
    }

    // Modal Edit Template
    function openEditTemplateModalById(id) {
      const select = document.getElementById("TemplateSelect");
      if (!select) return;
      const opt = select.querySelector(`option[value="${id}"]`);
      if (!opt || !opt.dataset.id) {
        alert("Template tidak ditemukan.");
        return;
      }

      document.getElementById("edit_tpl_id").value = opt.dataset.id;
      document.getElementById("edit_tpl_name").value = opt.dataset.name || "";
      document.getElementById("edit_tpl_file_current").textContent = opt.dataset.file || "-";
      document.getElementById("edit_tpl_file").value = "";

      const alertBox = document.getElementById("editTemplateAlert");
      alertBox.className = "hidden mb-4 p-3 rounded text-sm";
      alertBox.textContent = "";

      closeCustomTemplateDropdown();
      document.getElementById("editTemplateModal").classList.remove("hidden");
    }

    function closeEditTemplateModal() {
      document.getElementById("editTemplateModal").classList.add("hidden");
    }

    document.getElementById("editTemplateModal").addEventListener("click", function(e) {
      if (e.target === this) closeEditTemplateModal();
    });

    async function submitEditTemplate(e) {
      e.preventDefault();
      const btn = document.getElementById("btnSubmitEditTemplate");
      const alertBox = document.getElementById("editTemplateAlert");
      const form = document.getElementById("formEditTemplate");

      btn.disabled = true;
      btn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">refresh</span> <span>Menyimpan...</span>`;

      alertBox.className = "hidden mb-4 p-3 rounded text-sm";

      const formData = new FormData(form);
      formData.append("action", "update_template");

      try {
        const res = await fetch(window.location.href, {
          method: "POST",
          body: formData
        });
        const data = await res.json();

        if (data.success && data.template) {
          const t = data.template;
          const select = document.getElementById("TemplateSelect");
          const opt = select.querySelector(`option[value="${t.id}"]`);
          if (opt) {
            opt.textContent = t.name;
            opt.dataset.name = t.name;
            opt.dataset.file = t.file_path;
          }

          const row = document.getElementById(`tplRow_${t.id}`);
          if (row) {
            const nameEl = row.querySelector(".tpl-name-display");
            if (nameEl) nameEl.textContent = t.name;
          }

          if (select.value == t.id) {
            updateTemplateSelection();
          }

          alertBox.className = "mb-4 p-3 rounded text-sm bg-green-100 text-green-700 border border-green-300";
          alertBox.textContent = "✅ " + data.message;

          setTimeout(() => {
            closeEditTemplateModal();
          }, 700);
        } else {
          alertBox.className = "mb-4 p-3 rounded text-sm bg-red-100 text-red-700 border border-red-300";
          alertBox.textContent = "❌ " + (data.message || "Gagal memperbarui template.");
        }
      } catch (err) {
        alertBox.className = "mb-4 p-3 rounded text-sm bg-red-100 text-red-700 border border-red-300";
        alertBox.textContent = "❌ Terjadi kesalahan koneksi atau server.";
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">save</span> <span>Simpan Perubahan</span>`;
      }
    }


    // --- INVOICE FROM (SENDER) DROPDOWN LOGIC ---
    function toggleCustomSenderDropdown() {
      const list = document.getElementById("customSenderList");
      const chevron = document.getElementById("customSenderChevron");
      if (!list) return;
      const isOpen = !list.classList.contains("hidden");
      if (isOpen) {
        closeCustomSenderDropdown();
      } else {
        closeCustomTemplateDropdown();
        list.classList.remove("hidden");
        if (chevron) chevron.classList.add("transform", "rotate-180");
      }
    }

    function closeCustomSenderDropdown() {
      const list = document.getElementById("customSenderList");
      const chevron = document.getElementById("customSenderChevron");
      if (list) list.classList.add("hidden");
      if (chevron) chevron.classList.remove("transform", "rotate-180");
    }

    function selectSenderOption(val) {
      const ifSelect = document.getElementById("IFSelect");
      if (ifSelect) {
        const targetVal = (val || "Abdulaziz Yahya Sagheer Darwesh").trim();
        let matched = false;
        for (let i = 0; i < ifSelect.options.length; i++) {
          if (ifSelect.options[i].value.trim() === targetVal) {
            ifSelect.selectedIndex = i;
            matched = true;
            break;
          }
        }
        if (!matched && ifSelect.options.length > 0) {
          ifSelect.value = targetVal;
        }
      }
      updateSenderSelection(true);
      closeCustomSenderDropdown();
    }

    function updateSenderSelection(forceUpdate = false) {
      const ifSelect = document.getElementById("IFSelect");
      const selectedBox = document.getElementById("customSenderSelectedText");
      if (!ifSelect || !selectedBox) return;

      let val = ifSelect.value ? ifSelect.value.trim() : "";
      if (!val) {
        val = "Abdulaziz Yahya Sagheer Darwesh";
        ifSelect.value = val;
      }

      let opt = null;
      for (let i = 0; i < ifSelect.options.length; i++) {
        if (ifSelect.options[i].value.trim() === val) {
          opt = ifSelect.options[i];
          break;
        }
      }
      if (!opt && ifSelect.selectedIndex >= 0) {
        opt = ifSelect.options[ifSelect.selectedIndex];
      }
      const name = opt ? (opt.dataset.name || opt.value) : val;
      const code = opt ? (opt.dataset.code || "") : (senderCodes[val] || "AYS");
      selectedBox.innerHTML = `
        <span class="material-symbols-outlined text-gray-500 text-base flex-shrink-0">business</span>
        <span class="font-medium text-gray-800 truncate">${escapeHtml(name)}</span>
        ${code ? `<span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded font-mono flex-shrink-0">Kode: ${escapeHtml(code)}</span>` : ""}
      `;

      // Hitung ulang nomor invoice (digit 2 dari kode pengirim, digit 3 dari no urut pengirim)
      recomputeInvoiceNumber(forceUpdate);

      // Sinkronkan Payment Info (Bank Name, Account Name, Account Number)
      updateSenderPaymentInfo(val);
    }

    // Tombol Edit di dropdown trigger: selalu muncul, bisa untuk edit data terpilih ataupun isi data baru
    function openEditCurrentSender() {
      const ifSelect = document.getElementById("IFSelect");
      const currentVal = ifSelect ? ifSelect.value.trim() : "";
      if (currentVal) {
        const opt = ifSelect.querySelector(`option[value="${currentVal}"]`);
        const senderId = opt ? opt.dataset.id : "";
        if (senderId && senderId !== "0") {
          openEditSenderModalById(senderId);
          return;
        } else {
          // Jika ada nilai tapi belum memiliki senderId di tabel invoice_senders
          const pay = senderPayments[currentVal] || {};
          document.getElementById("edit_sender_id").value = "";
          document.getElementById("edit_sender_name").value = opt ? (opt.dataset.name || currentVal) : currentVal;
          document.getElementById("edit_sender_code").value = opt ? (opt.dataset.code || "AYS") : "AYS";
          const lastSeqEl1 = document.getElementById("edit_sender_last_seq");
          if (lastSeqEl1) lastSeqEl1.value = opt ? (opt.dataset.lastseq || "0") : "0";
          document.getElementById("edit_sender_bank_name").value = opt ? (opt.dataset.bank || pay.bank_name || "") : (pay.bank_name || "");
          document.getElementById("edit_sender_account_name").value = opt ? (opt.dataset.accname || pay.account_name || "") : (pay.account_name || "");
          document.getElementById("edit_sender_account_number").value = opt ? (opt.dataset.accnum || pay.account_number || "") : (pay.account_number || "");
          document.getElementById("senderModalTitle").textContent = "Edit Data Pengirim & Payment Info";
          document.getElementById("btnSwitchToAddSender").classList.remove("hidden");
          clearSenderAlert();
          closeCustomSenderDropdown();
          document.getElementById("editSenderModal").classList.remove("hidden");
          return;
        }
      }
      // Jika belum ada pengirim yang dipilih, buka modal untuk isi / tambah baru
      openAddSenderModal();
    }

    function openEditSenderModalById(id) {
      const ifSelect = document.getElementById("IFSelect");
      if (!ifSelect) return;
      const opt = ifSelect.querySelector(`option[data-id="${id}"]`);
      if (!opt) {
        alert("Data pengirim tidak ditemukan.");
        return;
      }

      const sName = opt.dataset.name || opt.value || "";
      const pay = senderPayments[sName] || {};

      document.getElementById("edit_sender_id").value = id;
      document.getElementById("edit_sender_name").value = sName;
      document.getElementById("edit_sender_code").value = opt.dataset.code || "";
      const lastSeqEl2 = document.getElementById("edit_sender_last_seq");
      if (lastSeqEl2) lastSeqEl2.value = opt.dataset.lastseq || "0";
      document.getElementById("edit_sender_bank_name").value = opt.dataset.bank || pay.bank_name || "";
      document.getElementById("edit_sender_account_name").value = opt.dataset.accname || pay.account_name || "";
      document.getElementById("edit_sender_account_number").value = opt.dataset.accnum || pay.account_number || "";
      document.getElementById("senderModalTitle").textContent = "Edit Data Pengirim & Payment Info";
      document.getElementById("btnSwitchToAddSender").classList.remove("hidden");
      clearSenderAlert();

      closeCustomSenderDropdown();
      document.getElementById("editSenderModal").classList.remove("hidden");
    }

    function openAddSenderModal() {
      document.getElementById("edit_sender_id").value = "";
      document.getElementById("edit_sender_name").value = "";
      document.getElementById("edit_sender_code").value = "";
      const lastSeqEl3 = document.getElementById("edit_sender_last_seq");
      if (lastSeqEl3) lastSeqEl3.value = "0";
      document.getElementById("edit_sender_bank_name").value = "";
      document.getElementById("edit_sender_account_name").value = "";
      document.getElementById("edit_sender_account_number").value = "";
      document.getElementById("senderModalTitle").textContent = "Isi / Tambah Pengirim Baru & Payment Info";
      document.getElementById("btnSwitchToAddSender").classList.add("hidden");
      clearSenderAlert();

      closeCustomSenderDropdown();
      document.getElementById("editSenderModal").classList.remove("hidden");
    }

    function closeEditSenderModal() {
      document.getElementById("editSenderModal").classList.add("hidden");
    }

    function clearSenderAlert() {
      const alertBox = document.getElementById("editSenderAlert");
      if (alertBox) {
        alertBox.className = "hidden mb-4 p-3 rounded text-sm";
        alertBox.textContent = "";
      }
    }

    document.getElementById("editSenderModal").addEventListener("click", function(e) {
      if (e.target === this) closeEditSenderModal();
    });

    const editSenderCodeInput = document.getElementById("edit_sender_code");
    if (editSenderCodeInput) {
      editSenderCodeInput.addEventListener("input", function() {
        this.value = this.value.toUpperCase();
      });
    }

    async function submitEditSender(e) {
      e.preventDefault();
      const btn = document.getElementById("btnSubmitEditSender");
      const alertBox = document.getElementById("editSenderAlert");
      const form = document.getElementById("formEditSender");

      btn.disabled = true;
      btn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">refresh</span> <span>Menyimpan...</span>`;
      clearSenderAlert();

      const formData = new FormData(form);
      formData.append("action", "save_sender");

      try {
        const res = await fetch(window.location.href, {
          method: "POST",
          body: formData
        });
        const data = await res.json();

        if (data.success && data.sender) {
          const s = data.sender;
          const ifSelect = document.getElementById("IFSelect");

          // Update cache payment
          senderPayments[s.name] = {
            bank_name: s.bank_name || '',
            account_name: s.account_name || '',
            account_number: s.account_number || ''
          };

          // Update atau tambahkan option di select
          let opt = ifSelect.querySelector(`option[data-id="${s.id}"]`);
          if (!opt) {
            opt = ifSelect.querySelector(`option[value="${s.name}"]`);
          }
          if (opt) {
            opt.value = s.name;
            opt.dataset.id = s.id;
            opt.dataset.name = s.name;
            opt.dataset.code = s.code;
            opt.dataset.lastseq = s.last_sequence || 0;
            opt.dataset.nextseq = s.next_seq || '001';
            opt.dataset.bank = s.bank_name || '';
            opt.dataset.accname = s.account_name || '';
            opt.dataset.accnum = s.account_number || '';
            opt.textContent = `${s.name} (Kode: ${s.code})`;
          } else {
            opt = document.createElement("option");
            opt.value = s.name;
            opt.dataset.id = s.id;
            opt.dataset.name = s.name;
            opt.dataset.code = s.code;
            opt.dataset.lastseq = s.last_sequence || 0;
            opt.dataset.nextseq = s.next_seq || '001';
            opt.dataset.bank = s.bank_name || '';
            opt.dataset.accname = s.account_name || '';
            opt.dataset.accnum = s.account_number || '';
            opt.textContent = `${s.name} (Kode: ${s.code})`;
            ifSelect.appendChild(opt);
          }

          // Update atau tambahkan baris di custom list
          let row = document.getElementById(`senderRow_${s.id}`);
          if (row) {
            const nameEl = row.querySelector(".sender-name-display");
            if (nameEl) nameEl.textContent = s.name;
            const codeEl = row.querySelector(".sender-code-display");
            if (codeEl) codeEl.textContent = "Kode: " + s.code;
            row.dataset.name = s.name;
            row.dataset.code = s.code;
            row.dataset.lastseq = s.last_sequence || 0;
            row.dataset.nextseq = s.next_seq || '001';
            row.dataset.bank = s.bank_name || '';
            row.dataset.accname = s.account_name || '';
            row.dataset.accnum = s.account_number || '';
          } else {
            const list = document.getElementById("customSenderList");
            const addRow = list.lastElementChild;
            const newRow = document.createElement("div");
            newRow.className = "sender-item-row flex items-center justify-between px-3 py-2.5 hover:bg-yellow-50 cursor-pointer group transition";
            newRow.id = `senderRow_${s.id}`;
            newRow.dataset.id = s.id;
            newRow.dataset.name = s.name;
            newRow.dataset.code = s.code;
            newRow.dataset.lastseq = s.last_sequence || 0;
            newRow.dataset.nextseq = s.next_seq || '001';
            newRow.dataset.bank = s.bank_name || '';
            newRow.dataset.accname = s.account_name || '';
            newRow.dataset.accnum = s.account_number || '';
            newRow.onclick = function() {
              selectSenderOption(this.dataset.name);
            };
            newRow.innerHTML = `
              <div class="flex items-center space-x-2 text-sm text-gray-800 flex-1 truncate pr-3">
                <span class="material-symbols-outlined text-gray-400 group-hover:text-yellow-600 text-base flex-shrink-0">business</span>
                <span class="truncate font-medium sender-name-display">${escapeHtml(s.name)}</span>
                <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded font-mono flex-shrink-0 sender-code-display">Kode: ${escapeHtml(s.code)}</span>
              </div>
              <button type="button" 
                      onclick="event.stopPropagation(); openEditSenderModalById('${s.id}')" 
                      class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 hover:bg-yellow-500 hover:text-white border border-yellow-300 rounded transition shadow-xs"
                      title="Edit nama, kode, dan payment info pengirim ini">
                <span class="material-symbols-outlined text-sm">edit</span>
                <span>Edit</span>
              </button>
            `;
            list.insertBefore(newRow, addRow);
          }

          // Update cache nomor urut & kode
          fromSequences[s.name] = s.next_seq;
          senderCodes[s.name] = s.code;

          // Pilih pengirim ini secara otomatis
          ifSelect.value = s.name;
          updateSenderSelection(true);

          alertBox.className = "mb-4 p-3 rounded text-sm bg-green-100 text-green-700 border border-green-300";
          alertBox.textContent = "✅ " + data.message;

          setTimeout(() => {
            closeEditSenderModal();
          }, 600);
        } else {
          alertBox.className = "mb-4 p-3 rounded text-sm bg-red-100 text-red-700 border border-red-300";
          alertBox.textContent = "❌ " + (data.message || "Gagal menyimpan pengirim.");
        }
      } catch (err) {
        alertBox.className = "mb-4 p-3 rounded text-sm bg-red-100 text-red-700 border border-red-300";
        alertBox.textContent = "❌ Terjadi kesalahan koneksi atau server.";
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">save</span> <span>Simpan Data Pengirim</span>`;
      }
    }

    // Klik di luar dropdown untuk menutup menu
    document.addEventListener("click", function(e) {
      const templateDd = document.getElementById("customTemplateDropdown");
      if (templateDd && !templateDd.contains(e.target)) {
        closeCustomTemplateDropdown();
      }
      const senderDd = document.getElementById("customSenderDropdown");
      if (senderDd && !senderDd.contains(e.target)) {
        closeCustomSenderDropdown();
      }
    });

    // Inisialisasi tampilan pengirim terpilih saat halaman dibuka (Default: Abdulaziz Yahya Sagheer Darwesh)
    const ifSelectInit = document.getElementById("IFSelect");
    if (ifSelectInit && !ifSelectInit.value) {
      ifSelectInit.value = "Abdulaziz Yahya Sagheer Darwesh";
    }
    updateSenderSelection();

    // Inisialisasi awal input tambahan pada Invoice To & Address saat halaman dimuat
    handleToggleOther(document.getElementById("ITSelect"), "ITOther", "ITLainnya");
    handleToggleOther(document.getElementById("ADSelect"), "ADOther", "ADLainnya");
  </script>

  <!-- Modal Edit Template Invoice (Tanpa Kode Invoice karena sudah dipindah ke Pengirim) -->
  <div id="editTemplateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 px-4">
    <div class="bg-white rounded-xl w-full max-w-lg p-6 relative shadow-2xl">
      <button type="button" onclick="closeEditTemplateModal()" class="absolute right-4 top-4 text-gray-400 hover:text-gray-700">
        <span class="material-symbols-outlined text-2xl">close</span>
      </button>
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-yellow-500 text-2xl">edit_document</span>
        <h2 class="text-base font-bold text-gray-800">Edit Template Invoice</h2>
      </div>

      <div id="editTemplateAlert" class="hidden mb-4 p-3 rounded text-sm"></div>

      <form id="formEditTemplate" onsubmit="submitEditTemplate(event)" class="space-y-4">
        <input type="hidden" id="edit_tpl_id" name="tpl_id" />

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Template <span class="text-red-500">*</span></label>
          <input type="text" id="edit_tpl_name" name="tpl_name" required class="w-full px-3 py-2 border rounded text-sm bg-white focus:ring focus:ring-yellow-300" placeholder="Contoh: Invoice Template PT ABC" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">File PDF Template</label>
          <div class="text-xs text-gray-600 bg-gray-100 p-2 rounded mb-2 border">
            <span class="font-medium">File saat ini:</span> <span id="edit_tpl_file_current" class="text-blue-600 font-mono break-all">-</span>
          </div>
          <label class="block text-xs text-gray-500 mb-1">Ganti File PDF Template (Opsional):</label>
          <input type="file" id="edit_tpl_file" name="tpl_file" accept=".pdf,application/pdf" class="w-full px-2 py-1.5 border rounded bg-white text-xs" />
          <p class="text-[11px] text-gray-400 mt-1">Biarkan kosong jika tidak ingin mengubah file PDF background.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t">
          <button type="button" onclick="closeEditTemplateModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded transition">
            Batal
          </button>
          <button type="submit" id="btnSubmitEditTemplate" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition inline-flex items-center gap-1 shadow">
            <span class="material-symbols-outlined text-base">save</span>
            <span>Simpan Perubahan</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit / Isi Pengirim Invoice (FROM) & Payment Info -->
  <div id="editSenderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 px-4">
    <div class="bg-white rounded-xl w-full max-w-lg p-6 relative shadow-2xl">
      <button type="button" onclick="closeEditSenderModal()" class="absolute right-4 top-4 text-gray-400 hover:text-gray-700">
        <span class="material-symbols-outlined text-2xl">close</span>
      </button>
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-yellow-500 text-2xl">domain</span>
        <h2 class="text-base font-bold text-gray-800" id="senderModalTitle">Pengirim (FROM) & Payment Info</h2>
      </div>

      <div id="editSenderAlert" class="hidden mb-4 p-3 rounded text-sm"></div>

      <form id="formEditSender" onsubmit="submitEditSender(event)" class="space-y-4">
        <input type="hidden" id="edit_sender_id" name="sender_id" />
        <input type="hidden" id="edit_sender_last_seq" name="last_sequence" />

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Pengirim (FROM) <span class="text-red-500">*</span></label>
          <input type="text" id="edit_sender_name" name="sender_name" required class="w-full px-3 py-2 border rounded text-sm bg-white focus:ring focus:ring-yellow-300" placeholder="Contoh: Abdulaziz Yahya Sagheer Darwesh" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Invoice <span class="text-red-500">*</span></label>
          <input type="text" id="edit_sender_code" name="sender_code" required class="w-full px-3 py-2 border rounded text-sm uppercase bg-white focus:ring focus:ring-yellow-300" placeholder="Contoh: AYS atau AGI" />
          <p class="text-[11px] text-gray-500 mt-1">Kode ini akan berada di tengah nomor invoice: <code><?= htmlspecialchars($container['number'] ?? '') ?>/[KODE]/XXX</code></p>
        </div>

        <!-- Section Payment Info di Modal Pengirim -->
        <div class="p-3 bg-yellow-50/60 border border-yellow-200 rounded-lg space-y-3">
          <div class="flex items-center gap-1.5 text-xs font-bold text-yellow-900 uppercase tracking-wider">
            <span class="material-symbols-outlined text-yellow-700 text-sm">payments</span>
            <span>Payment Info (Rekening Pembayaran)</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Bank Name</label>
            <input type="text" id="edit_sender_bank_name" name="bank_name" class="w-full px-3 py-2 border rounded text-sm bg-white focus:ring focus:ring-yellow-300" placeholder="Contoh: BRI, BCA, Mandiri" list="bankListSuggestions" />
            <datalist id="bankListSuggestions">
              <option value="BRI">
              <option value="BCA">
              <option value="Mandiri">
              <option value="BNI">
              <option value="BSI">
            </datalist>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Account Name</label>
            <input type="text" id="edit_sender_account_name" name="account_name" class="w-full px-3 py-2 border rounded text-sm bg-white focus:ring focus:ring-yellow-300" placeholder="Contoh: PT ALSHARIF GROUP INDONESIA" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Account Number</label>
            <input type="text" id="edit_sender_account_number" name="account_number" class="w-full px-3 py-2 border rounded text-sm bg-white focus:ring focus:ring-yellow-300 font-mono" placeholder="Contoh: 016701002496566" />
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t">
          <button type="button" id="btnSwitchToAddSender" onclick="openAddSenderModal()" class="text-xs text-blue-600 hover:underline flex items-center gap-1 hidden">
            <span class="material-symbols-outlined text-sm">add_circle</span>
            <span>+ Tambah Pengirim Baru Lainnya</span>
          </button>
          <div class="flex gap-2 ml-auto">
            <button type="button" onclick="closeEditSenderModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded transition">
              Batal
            </button>
            <button type="submit" id="btnSubmitEditSender" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded transition inline-flex items-center gap-1 shadow">
              <span class="material-symbols-outlined text-base">save</span>
              <span>Simpan Data Pengirim</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>

</html>