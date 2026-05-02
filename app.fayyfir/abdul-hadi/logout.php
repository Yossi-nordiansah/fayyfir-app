<?php
session_start();
require "config.php";

if (isset($_SESSION["user_id"], $_SESSION["db"])) {
    $user_id = $_SESSION["user_id"];
    $db_used = $_SESSION["db"];

    // Pilih koneksi aktif sesuai DB yang dipakai user
    $conn_active = ($db_used === "db1") ? $conn1 : $conn2;

    $stmt = $conn_active->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} elseif (isset($_GET["email"])) {
    $email = $_GET["email"];

    // Default update ke DB1 (atau bisa tentukan logika lain kalau email ada di DB2)
    $stmt = $conn1->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $stmt->close();

    $stmt2 = $conn2->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE email = ? OR phone = ?");
    $stmt2->bind_param("ss", $email, $email);
    $stmt2->execute();
    $stmt2->close();
}

// 🚨 baru hapus session setelah update DB
session_unset();
session_destroy();

header("Location: ../login?message=logout_success");
exit();
?>