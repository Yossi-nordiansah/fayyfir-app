<?php    
session_start();    
if (!isset($_SESSION["user_id"])) {    
  header("Location: login");    
  exit();    
}    
    
require "config.php";    
    
if ($_SERVER["REQUEST_METHOD"] === "POST") {    
  $buyer_id    = intval($_POST["buyer_id"] ?? 0);    
  $product_ids = $_POST["product_id"] ?? [];    
  $qtys        = $_POST["qty"] ?? [];    
  $prices      = $_POST["price"] ?? [];    
  $dps         = $_POST["dpInput"] ?? [];    
    
  if (!$buyer_id || empty($product_ids)) die("Data tidak valid.");    

  /* =======================================================
     🚨 CEK STOK PRODUK SEBELUM SIMPAN
     ======================================================= */
  foreach ($product_ids as $i => $pid) {
      $pid = intval($pid);
      $qty = floatval($qtys[$i] ?? 0);

      if ($pid <= 0 || $qty <= 0) continue;

      $stmt = $conn->prepare("SELECT quantity FROM product_stocks WHERE id = ?");
      $stmt->bind_param("i", $pid);
      $stmt->execute();
      $stmt->bind_result($stok_tersisa);
      $stmt->fetch();
      $stmt->close();

      if ($stok_tersisa <= 0) {
          die("<script>alert('Produk dengan stok kosong tidak bisa dipesan!');history.back();</script>");
      }
      if ($qty > $stok_tersisa) {
          die("<script>alert('Qty melebihi stok tersedia!');history.back();</script>");
      }
  }    

  /* =======================================================
     GENERATE NOMOR INVOICE
     ======================================================= */
  $today = date("Ymd");    
  $prefix = "INV" . $today;    
    
  $result = $conn->query("SELECT invoice_number FROM selling_products WHERE invoice_number LIKE '{$prefix}-%' ORDER BY id DESC LIMIT 1");    
  if ($result && $row = $result->fetch_assoc()) {    
    $last_number = (int)substr($row["invoice_number"], -4);    
    $seq = str_pad($last_number + 1, 4, "0", STR_PAD_LEFT);    
  } else {    
    $seq = "0001";    
  }    
  $invoice_number = "{$prefix}-{$seq}";    
    
  /* =======================================================
     SIMPAN TRANSAKSI
     ======================================================= */
  $stmt = $conn->prepare("    
    INSERT INTO selling_products    
      (selling_date, invoice_number, product_id, buyer_id, qty, price, total_selling, dp, status)    
    VALUES    
      (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)    
  ");    
  if (!$stmt) die("Prepare gagal: " . $conn->error);    
    
  $pph_default = 0.0;    
  $dp_default  = 0.0;    
  $grand_total = 0;    
    
  for ($i = 0; $i < count($product_ids); $i++) {    
    $pid   = intval($product_ids[$i]);    
    $qty   = (float)($qtys[$i] ?? 0);    
    $price = (float)($prices[$i] ?? 0);    
    $dp    = (float)($dps[$i] ?? 0);    
    
    if ($pid <= 0 || $qty <= 0 || $price <= 0) continue;    
    
    $total = $qty * $price / 1000;    
    $grand_total += $total;    
    $pph = $grand_total * 0.0025;    
    
    // Tentukan status berdasarkan nilai DP    
    if ($dp === null || $dp <= 0) {    
      $status = "Lunas";    
    } else {    
      $status = "DP";    
    }    
    
    $stmt->bind_param(    
      "siidddss",    
      $invoice_number, $pid, $buyer_id, $qty, $price, $total, $dp, $status    
    );    
    
    if (!$stmt->execute()) die("Gagal simpan: " . $stmt->error);    
    
    // 🔽 Update stok produk  
    $updateStock = $conn->prepare("UPDATE product_stocks SET quantity = quantity - ? WHERE id = ?");  
    if ($updateStock) {  
        $updateStock->bind_param("di", $qty, $pid);  
        $updateStock->execute();  
        $updateStock->close();  
    }  
  }    
    
  $stmt->close();    
  header("Location: transaksi-rincian.php?id=" . $buyer_id);    
  exit();    
}    
    
header("Location: transaksi-produk");    
exit();