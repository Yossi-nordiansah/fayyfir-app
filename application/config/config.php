<?php
if (!defined('BASEPATH')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

date_default_timezone_set('Asia/Jakarta');

$config1 = [
    "DB_HOST" => "127.0.0.1",
    "DB_PORT" => "3308",
    "DB_NAME" => "yossinor_db",
    "DB_USER" => "root",
    "DB_PASS" => "",
];

// Konfigurasi database kedua  
$config2 = [
    "DB_HOST" => "127.0.0.1",
    "DB_PORT" => "3308",
    "DB_NAME" => "yossinor_ahadi",
    "DB_USER" => "root",
    "DB_PASS" => "",
];

$conn1 = null;
$conn2 = null;
$db_to_use = (isset($_SESSION["db"])) ? $_SESSION["db"] : "db1";

if ($db_to_use === "db1") {
    $conn1 = new mysqli($config1["DB_HOST"], $config1["DB_USER"], $config1["DB_PASS"], $config1["DB_NAME"], $config1["DB_PORT"]);
    $conn_active = $conn1;
} else {
    $conn2 = new mysqli($config2["DB_HOST"], $config2["DB_USER"], $config2["DB_PASS"], $config2["DB_NAME"], $config2["DB_PORT"]);
    $conn_active = $conn2;
}

$conn = $conn_active;

if (isset($_SESSION["user_id"], $_SESSION["session_token"])) {
    $user_id = $_SESSION["user_id"];
    $session_token = $_SESSION["session_token"];
    $last_validation = (isset($_SESSION["last_validation"])) ? $_SESSION["last_validation"] : 0;
    if (time() - $last_validation > 300) {
        if ($conn_active && !$conn_active->connect_error) {
            $stmt = $conn_active->prepare("SELECT session_token, token_expiry FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row["session_token"] !== $session_token || strtotime($row["token_expiry"]) < time()) {
                        if (session_status() === PHP_SESSION_NONE) session_start();
                        session_destroy();
                        header("Location: login.php");
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hash = md5(serialize($_POST) . $_SERVER['PHP_SELF']);
        if (($hash === (isset($_SESSION['last_post_hash']) ? $_SESSION['last_post_hash'] : '')) && (time() - (isset($_SESSION['last_post_time']) ? $_SESSION['last_post_time'] : 0) < 2)) {
            die("Mohon tunggu...");
        }
        $_SESSION['last_post_hash'] = $hash;
        $_SESSION['last_post_time'] = time();
    }
    if (!defined('BASEPATH')) {
        session_write_close();
    }
} else {
    $conn = $conn1;
}

if (defined('BASEPATH')) {
    $config['base_url'] = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    $config['index_page'] = '';
    $config['uri_protocol'] = 'REQUEST_URI';
    $config['url_suffix'] = '';
    $config['language'] = 'english';
    $config['charset'] = 'UTF-8';
    $config['enable_hooks'] = FALSE;
    $config['subclass_prefix'] = 'MY_';
    $config['composer_autoload'] = "vendor/autoload.php";
    $config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-&';
    $config['enable_query_strings'] = FALSE;
    $config['controller_trigger'] = 'c';
    $config['function_trigger'] = 'm';
    $config['directory_trigger'] = 'd';
    $config['allow_get_array'] = TRUE;
    $config['log_threshold'] = 0;
    $config['log_path'] = '';
    $config['log_file_extension'] = '';
    $config['log_file_permissions'] = 0644;
    $config['log_date_format'] = 'Y-m-d H:i:s';
    $config['error_views_path'] = '';
    $config['cache_path'] = '';
    $config['cache_query_string'] = FALSE;
    $config['encryption_key'] = '';
    $config['sess_driver'] = 'files';
    $config['sess_cookie_name'] = 'ci_session';
    $config['sess_expiration'] = 3600;
    $config['sess_save_path'] = NULL;
    $config['sess_match_ip'] = FALSE;
    $config['sess_time_to_update'] = 300;
    $config['sess_regenerate_destroy'] = FALSE;
    $config['cookie_prefix'] = '';
    $config['cookie_domain'] = '';
    $config['cookie_path'] = '/';
    $config['cookie_secure'] = FALSE;
    $config['cookie_httponly'] = FALSE;
    $config['standardize_newlines'] = FALSE;
    $config['global_xss_filtering'] = FALSE;
    $config['csrf_protection'] = FALSE;
    $config['csrf_token_name'] = 'csrf_test_name';
    $config['csrf_cookie_name'] = 'csrf_cookie_name';
    $config['csrf_expire'] = 7200;
    $config['csrf_regenerate'] = TRUE;
    $config['csrf_exclude_uris'] = array();
    $config['compress_output'] = FALSE;
    $config['time_reference'] = 'local';
    $config['rewrite_short_tags'] = FALSE;
    $config['proxy_ips'] = '';
}
