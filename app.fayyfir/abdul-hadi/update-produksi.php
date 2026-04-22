<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

require "config.php";

// Pastikan method POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil data dari POST
    $id           = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $cost_kg      = isset($_POST['cost_kg']) ? (float) $_POST['cost_kg'] : 0;
    $profit_kg    = isset($_POST['profit_kg']) ? (float) $_POST['profit_kg'] : 0;
    $price_weight = isset($_POST['price_weight']) ? (float) $_POST['price_weight'] : 0;
    $fix_price    = isset($_POST['fix_price']) ? (float) $_POST['fix_price'] : 0;
    $status = 'Terhitung';

    if ($id > 0) {
        $stmt = $conn->prepare("
            UPDATE productions 
            SET 
                status = ?,
                price_weight = ?, 
                fix_price = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sddi", $status, $price_weight, $fix_price, $id);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "error: invalid id";
    }
} else {
    echo "error: invalid request";
}

$conn->close();