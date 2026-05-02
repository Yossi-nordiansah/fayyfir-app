<?php
session_start(); // Harus di awal

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi database
$config = [
  "DB_HOST" => "127.0.0.1",
  "DB_PORT" => "3306",
  "DB_NAME" => "alsz2632_db",
  "DB_USER" => "alsz2632_user",
  "DB_PASS" => "RahasiaBanget123",
];

// Koneksi MySQLi
$conn = new mysqli(
  $config["DB_HOST"],
  $config["DB_USER"],
  $config["DB_PASS"],
  $config["DB_NAME"],
  $config["DB_PORT"]
);

// Cek koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Validasi token jika user login
if (isset($_SESSION["user_id"], $_SESSION["session_token"])) {
  $user_id = $_SESSION["user_id"];
  $session_token = $_SESSION["session_token"];

  $stmt = $conn->prepare("SELECT session_token, token_expiry FROM users WHERE id = ?");
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
      header("Location: login");
      exit();
    } else {
      // Token valid: perpanjang token_expiry 1 jam dari sekarang
      $new_expiry = date("Y-m-d H:i:s", time() + (60 * 60)); // 15 menit
      $update = $conn->prepare("UPDATE users SET token_expiry = ? WHERE id = ?");
      $update->bind_param("si", $new_expiry, $user_id);
      $update->execute();
    }
  } else {
    // Tidak ditemukan di database
    session_unset();
    session_destroy();
    header("Location: login");
    exit();
  }
}
?>