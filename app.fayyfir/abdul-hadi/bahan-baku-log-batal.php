<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$material_id = isset($_GET["material_id"]) ? (int)$_GET["material_id"] : 0;

if (!$id || !$material_id) {
    die("Parameter tidak valid.");
}

// Cek apakah log ada
$stmt = $conn->prepare("SELECT id, change_type, quantity FROM stock_movements WHERE id = ? AND material_id = ?");
$stmt->bind_param("ii", $id, $material_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $change_type = strtoupper($row['change_type']);
    $qty = (float)$row['quantity'];

    $conn->begin_transaction();

    try {
        
        $del_stmt = $conn->prepare("DELETE FROM stock_movements WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
        $del_stmt->close();

        // Kembalikan/Update material_stocks jika diperlukan
        if ($change_type === 'IN') {
            // Karena sebelumnya ditambah (IN), maka kita kurangi untuk undo
            $upd = $conn->prepare("UPDATE material_stocks SET quantity = quantity - ? WHERE material_id = ?");
            $upd->bind_param("di", $qty, $material_id);
            $upd->execute();
            $upd->close();
        } elseif ($change_type === 'OUT') {
            // Karena sebelumnya dikurangi (OUT), maka kita tambah untuk undo
            $upd = $conn->prepare("UPDATE material_stocks SET quantity = quantity + ? WHERE material_id = ?");
            $upd->bind_param("di", $qty, $material_id);
            $upd->execute();
            $upd->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Gagal membatalkan log: " . $e->getMessage());
    }
}

header("Location: bahan-baku-rincian.php?id=" . $material_id);
exit();
?>
