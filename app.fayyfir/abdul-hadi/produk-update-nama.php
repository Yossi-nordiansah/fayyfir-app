<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
  exit;
}

require "config.php";

$input = json_decode(file_get_contents("php://input"), true);

$id = intval($input['id'] ?? 0);
$name = trim($input['name'] ?? '');

if ($id <= 0 || $name === '') {
  echo json_encode(["success"=>false,"message"=>"Data tidak valid"]);
  exit;
}

$stmt = $conn->prepare("UPDATE product_stocks SET product_name=? WHERE id=?");
$stmt->bind_param("si", $name, $id);

if ($stmt->execute()) {
  echo json_encode(["success"=>true]);
} else {
  echo json_encode(["success"=>false,"message"=>"DB error"]);
}

$stmt->close();
$conn->close();