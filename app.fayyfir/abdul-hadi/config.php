<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

$config1 = [
    "DB_HOST" => "127.0.0.1",
    "DB_PORT" => "3308",
    "DB_NAME" => "yossinor_db",
    "DB_USER" => "root",
    "DB_PASS" => "",
];

$config2 = [
    "DB_HOST" => "127.0.0.1",
    "DB_PORT" => "3308",
    "DB_NAME" => "yossinor_ahadi",
    "DB_USER" => "root",
    "DB_PASS" => "",
];


$db_to_use = $_SESSION["db"] ?? "db1";

if ($db_to_use === "db1") {
    $conn1 = new mysqli($config1["DB_HOST"], $config1["DB_USER"], $config1["DB_PASS"], $config1["DB_NAME"], $config1["DB_PORT"]);
    $conn_active = $conn1;
    $conn2 = null;
} else {
    $conn2 = new mysqli($config2["DB_HOST"], $config2["DB_USER"], $config2["DB_PASS"], $config2["DB_NAME"], $config2["DB_PORT"]);
    $conn_active = $conn2;
    $conn1 = null;
}

$conn = $conn_active;

/**
 * Lazy loader untuk koneksi ke yossinor_ahadi (db2).
 * Gunakan fungsi ini di halaman yang SELALU butuh db2,
 * tanpa membuka koneksi kedua jika db2 sudah tersedia.
 *
 * Ini menghindari DOUBLE DB CONNECTION bottleneck.
 */
function get_conn2(): mysqli {
    global $conn2, $config2;
    if (!$conn2 || $conn2->connect_error) {
        $conn2 = new mysqli(
            $config2["DB_HOST"],
            $config2["DB_USER"],
            $config2["DB_PASS"],
            $config2["DB_NAME"],
            $config2["DB_PORT"]
        );
    }
    return $conn2;
}


// Validasi Token
if (isset($_SESSION["user_id"], $_SESSION["session_token"])) {
    $user_id = $_SESSION["user_id"];
    $session_token = $_SESSION["session_token"];

    $last_validation = $_SESSION["last_validation"] ?? 0;
    if (time() - $last_validation > 300) {
        if ($conn_active && !$conn_active->connect_error) {
            $stmt = $conn_active->prepare("SELECT session_token, token_expiry FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row["session_token"] !== $session_token || strtotime($row["token_expiry"]) < time()) {
                        session_destroy();
                        header("Location: ../login.php"); // Kembali ke login parent
                        exit();
                    } else {
                        $new_expiry = date("Y-m-d H:i:s", time() + 3600);
                        $upd = $conn_active->prepare("UPDATE users SET token_expiry = ? WHERE id = ?");
                        $upd->bind_param("si", $new_expiry, $user_id);
                        $upd->execute();
                        $_SESSION["last_validation"] = time();
                    }
                }
            }
        }
    }

    // Anti Double Submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hash = md5(serialize($_POST) . $_SERVER['PHP_SELF']);
        if (($hash === ($_SESSION['last_post_hash'] ?? '')) && (time() - ($_SESSION['last_post_time'] ?? 0) < 2)) {
            die("Mohon tunggu...");
        }
        $_SESSION['last_post_hash'] = $hash;
        $_SESSION['last_post_time'] = time();
    }
}
