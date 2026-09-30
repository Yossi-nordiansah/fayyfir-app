<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : 0;
  $raw_weight = trim($_POST["new_weight"] ?? '0');
  $raw_weight = str_replace(['.', ' '], '', $raw_weight); // Hapus titik ribuan
  $raw_weight = str_replace(',', '.', $raw_weight);       // Desimal koma -> titik
  $new_weight = (float)$raw_weight;
  $adj_date_raw = trim($_POST["adjustment_date"] ?? '');
  $notes = trim($_POST["notes"] ?? '');

  if ($product_id <= 0 || $new_weight < 0) {
    $_SESSION['error_msg'] = "Data penyesuaian bobot tidak valid.";
    header("Location: hasil-produksi.php");
    exit();
  }

  // Format tanggal penimbangan
  if (!empty($adj_date_raw)) {
    if (strlen($adj_date_raw) === 10) {
      $adjustment_date = $adj_date_raw . ' ' . date('H:i:s');
    } else {
      $adjustment_date = date('Y-m-d H:i:s', strtotime($adj_date_raw));
    }
  } else {
    $adjustment_date = date('Y-m-d H:i:s');
  }

  // Ambil data produk saat ini
  $stmt = $conn->prepare("SELECT id, product_name, quantity FROM product_stocks WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $prod = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$prod) {
    $_SESSION['error_msg'] = "Produk tidak ditemukan.";
    header("Location: hasil-produksi.php");
    exit();
  }

  $qty_before = (float)$prod['quantity'];
  $qty_after = $new_weight;
  $qty_shrinkage = $qty_before - $qty_after;

  // Hitung Biaya Belum Terjual (FIFO Unsold Cost) saat ini untuk produk ini
  // 1. Total penjualan produk
  $stmt_s = $conn->prepare("SELECT COALESCE(SUM(qty), 0) AS total_sold FROM selling_products WHERE product_id = ?");
  $stmt_s->bind_param("i", $product_id);
  $stmt_s->execute();
  $sold_row = $stmt_s->get_result()->fetch_assoc();
  $total_sold = (float)($sold_row['total_sold'] ?? 0);
  $stmt_s->close();

  // 2. Batch produksi produk (urut FIFO: production_date ASC, id ASC)
  $stmt_b = $conn->prepare("
    SELECT 
      COALESCE(total_output, 0) AS total_output,
      (COALESCE(total_pro_materials, 0) + COALESCE(total_pro_expenses, 0)) AS total_cost
    FROM productions 
    WHERE product_id = ? AND status != 'Proses' AND COALESCE(total_output, 0) > 0
    ORDER BY COALESCE(production_date, DATE(created_at)) ASC, id ASC
  ");
  $stmt_b->bind_param("i", $product_id);
  $stmt_b->execute();
  $res_b = $stmt_b->get_result();

  $rem_sold = $total_sold;
  $unsold_cost = 0.0;

  while ($b = $res_b->fetch_assoc()) {
    $b_out = (float)$b['total_output'];
    $b_cost = (float)$b['total_cost'];
    $b_uc = $b_out > 0 ? ($b_cost / $b_out) : 0;

    if ($rem_sold >= $b_out) {
      $rem_sold -= $b_out;
    } elseif ($rem_sold > 0) {
      $b_s_qty = $rem_sold;
      $b_s_cost = $b_s_qty * $b_uc;
      $unsold_cost += max(0.0, $b_cost - $b_s_cost);
      $rem_sold = 0.0;
    } else {
      $unsold_cost += $b_cost;
    }
  }
  $stmt_b->close();

  // Hitung HPP Sebelum & Sesudah
  $hpp_before = $qty_before > 0 ? ($unsold_cost / $qty_before) : 0.0;
  $hpp_after = $qty_after > 0 ? ($unsold_cost / $qty_after) : 0.0;

  // Pastikan tabel product_shrinkage_logs sudah ada
  $conn->query("
    CREATE TABLE IF NOT EXISTS `product_shrinkage_logs` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `product_id` INT NOT NULL,
      `ref_no` VARCHAR(100) NOT NULL,
      `adjustment_date` DATETIME NOT NULL,
      `qty_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `qty_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `unsold_cost_at_time` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `hpp_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `hpp_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `notes` TEXT NULL,
      `created_by` INT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_product_id` (`product_id`),
      KEY `idx_adjustment_date` (`adjustment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // Generate ref_no (SST-YYYYMMDD-XXXX)
  $today_prefix = "SST-" . date("Ymd");
  $res_seq = $conn->query("SELECT ref_no FROM product_shrinkage_logs WHERE ref_no LIKE '{$today_prefix}%' ORDER BY id DESC LIMIT 1");
  if ($res_seq && $r_seq = $res_seq->fetch_assoc()) {
    $last_num = (int)substr($r_seq["ref_no"], -4);
    $seq = str_pad($last_num + 1, 4, "0", STR_PAD_LEFT);
  } else {
    $seq = "0001";
  }
  $ref_no = "{$today_prefix}-{$seq}";

  $user_id = $_SESSION["user_id"] ?? null;

  // 1. Simpan ke log penyusutan
  $stmt_ins = $conn->prepare("
    INSERT INTO product_shrinkage_logs 
      (product_id, ref_no, adjustment_date, qty_before, qty_after, qty_shrinkage, unsold_cost_at_time, hpp_before, hpp_after, notes, created_by)
    VALUES 
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt_ins->bind_param(
    "issddddddsi",
    $product_id,
    $ref_no,
    $adjustment_date,
    $qty_before,
    $qty_after,
    $qty_shrinkage,
    $unsold_cost,
    $hpp_before,
    $hpp_after,
    $notes,
    $user_id
  );
  $stmt_ins->execute();
  $stmt_ins->close();

  // 2. Update stok produk di product_stocks
  $stmt_upd = $conn->prepare("UPDATE product_stocks SET quantity = ? WHERE id = ?");
  $stmt_upd->bind_param("di", $qty_after, $product_id);
  $stmt_upd->execute();
  $stmt_upd->close();

  $_SESSION['success_msg'] = "Penyesuaian bobot ({$prod['product_name']}) berhasil disimpan. Stok menjadi " . number_format($qty_after, 0, ',', '.') . " g dengan estimasi HPP sisa Rp " . number_format($hpp_after, 0, ',', '.') . "/g.";
  header("Location: hasil-produksi.php");
  exit();
}

header("Location: hasil-produksi.php");
exit();
