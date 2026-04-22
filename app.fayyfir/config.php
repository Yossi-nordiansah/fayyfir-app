<?php  
session_start();  
date_default_timezone_set('Asia/Jakarta');  
  
// Konfigurasi database utama  
$config1 = [  
  "DB_HOST" => "127.0.0.1",  
  "DB_PORT" => "3306",  
  "DB_NAME" => "alsz2632_db",  
  "DB_USER" => "alsz2632_user",  
  "DB_PASS" => "RahasiaBanget123",  
];  
  
// Konfigurasi database kedua  
$config2 = [  
  "DB_HOST" => "127.0.0.1",  
  "DB_PORT" => "3306",  
  "DB_NAME" => "alsz2632_ahadi",  
  "DB_USER" => "alsz2632_user",  
  "DB_PASS" => "RahasiaBanget123",  
];  
  
// Koneksi MySQLi  
$conn1 = new mysqli(  
  $config1["DB_HOST"], $config1["DB_USER"], $config1["DB_PASS"],  
  $config1["DB_NAME"], $config1["DB_PORT"]  
);  
  
$conn2 = new mysqli(  
  $config2["DB_HOST"], $config2["DB_USER"], $config2["DB_PASS"],  
  $config2["DB_NAME"], $config2["DB_PORT"]  
);  
  
// Cek koneksi  
if ($conn1->connect_error) {  
  die("Koneksi DB1 gagal: " . $conn1->connect_error);  
}  
if ($conn2->connect_error) {  
  die("Koneksi DB2 gagal: " . $conn2->connect_error);  
}  
  
// 🔐 Validasi token jika user login  
if (isset($_SESSION["user_id"], $_SESSION["session_token"], $_SESSION["db"])) {  
  $user_id = $_SESSION["user_id"];  
  $session_token = $_SESSION["session_token"];  
  $db_used = $_SESSION["db"];  
  
  // Pilih koneksi aktif  
  $conn_active = ($db_used === "db1") ? $conn1 : $conn2;  
  
  // Alias supaya file lain tetap bisa pakai $conn  
  $conn = $conn_active;  
  
  $stmt = $conn_active->prepare("SELECT session_token, token_expiry FROM users WHERE id = ?");  
  $stmt->bind_param("i", $user_id);  
  $stmt->execute();  
  $result = $stmt->get_result();  
  
  if ($row = $result->fetch_assoc()) {  
    $db_token = $row["session_token"];  
    $db_expiry = $row["token_expiry"];  
  
    if ($db_token !== $session_token || strtotime($db_expiry) < time()) {  
      // Token tidak cocok atau expired  
      session_unset();  
      session_destroy();  
      header("Location: login.php");  
      exit();  
    } else {  
      // Token valid: perpanjang token_expiry 1 jam  
      $new_expiry = date("Y-m-d H:i:s", time() + (60 * 60));  
      $update = $conn_active->prepare("UPDATE users SET token_expiry = ? WHERE id = ?");  
      $update->bind_param("si", $new_expiry, $user_id);  
      $update->execute();  
    }  
  } else {  
    // Tidak ditemukan user  
    session_unset();  
    session_destroy();  
    header("Location: login.php");  
    exit();  
  }  
} else {  
  // Jika belum login, default pakai DB1 (opsional)  
  $conn = $conn1;  
}  
?>