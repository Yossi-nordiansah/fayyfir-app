<?php
session_start();
require "config.php";

header('Content-Type: application/json');

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$region_name = trim($_POST["region_name"] ?? "");

if ($region_name === "") {
    echo json_encode(["success" => false, "message" => "Nama area tidak boleh kosong"]);
    exit();
}

// Cek apakah area sudah ada
$check = $conn->prepare("SELECT id FROM areas WHERE region_name = ?");
$check->bind_param("s", $region_name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Area sudah ada, kembalikan sukses agar bisa dipilih
    echo json_encode(["success" => true, "region_name" => $region_name, "already_exists" => true]);
    $check->close();
    exit();
}
$check->close();

// Simpan area baru
$stmt = $conn->prepare("INSERT INTO areas (region_name) VALUES (?)");
$stmt->bind_param("s", $region_name);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "region_name" => $region_name, "already_exists" => false]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menyimpan area: " . $conn->error]);
}
$stmt->close();
?>
