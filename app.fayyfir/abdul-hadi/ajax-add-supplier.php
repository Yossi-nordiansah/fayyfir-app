<?php
require "config.php";
session_start();

if (!isset($_SESSION["user_id"])) {
  http_response_code(401);
  exit("Unauthorized");
}

$region = $_SESSION["region"] ?? null;
$name = trim($_POST["name"] ?? "");

if ($name === "") {
  http_response_code(400);
  exit("Nama kosong");
}

/* =========================
   CEK DUPLIKASI PER REGION
========================= */
$check = $conn->prepare("
  SELECT id 
  FROM suppliers 
  WHERE name = ? AND (
    (region_name IS NULL AND ? IS NULL) OR 
    region_name = ?
  )
");
$check->bind_param("sss", $name, $region, $region);
$check->execute();
$res = $check->get_result();

if ($row = $res->fetch_assoc()) {
  echo json_encode([
    "status" => "exists",
    "id" => $row["id"],
    "name" => $name
  ]);
  exit();
}

/* =========================
   INSERT BARU
========================= */
$stmt = $conn->prepare("
  INSERT INTO suppliers (name, region_name) 
  VALUES (?, ?)
");
$stmt->bind_param("ss", $name, $region);
$stmt->execute();

echo json_encode([
  "status" => "created",
  "id" => $stmt->insert_id,
  "name" => $name
]);