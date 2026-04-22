<?php
session_start();
require "config.php";

$email = null;

// 1. Ambil email dari session dulu, fallback ke GET
if (isset($_SESSION["email"])) {
  $email = $_SESSION["email"];
} elseif (isset($_GET["email"])) {
  $email = $_GET["email"];
}

if ($email) {
  // 2. Logout user dari DB2 (matikan session lama)
  $stmt = $conn2->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL 
                           WHERE email = ? OR phone = ?");
  $stmt->bind_param("ss", $email, $email);
  $stmt->execute();
  $stmt->close();

  // 3. Cek apakah user ada di DB1
  $stmt1 = $conn1->prepare("SELECT id, name, region_name, role_id FROM users 
                            WHERE email = ? OR phone = ?");
  $stmt1->bind_param("ss", $email, $email);
  $stmt1->execute();
  $result1 = $stmt1->get_result();

  if ($result1->num_rows === 1) {
    $user = $result1->fetch_assoc();

    // 4. Buat token baru di DB1
    $token = bin2hex(random_bytes(16));
    $token_expiry = date("Y-m-d H:i:s", time() + (60 * 60));

    $update_stmt = $conn1->prepare("UPDATE users SET session_token = ?, token_expiry = ?, is_online = 1 
                                    WHERE id = ?");
    $update_stmt->bind_param("ssi", $token, $token_expiry, $user["id"]);
    $update_stmt->execute();
    $update_stmt->close();

    // 5. Reset session lama, isi ulang untuk DB1
    session_unset();

    $_SESSION["db"] = "db1";
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["email"] = $email;
    $_SESSION["role_id"] = $user["role_id"];
    $_SESSION["region"] = $user["region_name"];
    $_SESSION["session_token"] = $token;

    // 6. Redirect ke halaman DB1 (../index)
    header("Location: ../index");
    exit();
  }
}

// Jika gagal (tidak ada email / user tidak ditemukan di DB1)
session_unset();
session_destroy();
header("Location: login.php?message=logout_success");
exit();
?>