<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

require "config.php";

$invoice = $_GET['invoice'] ?? null;

if (!$invoice) {
    die("Invoice tidak valid.");
}

$conn->begin_transaction();

try {

    /* ==========================
       Ambil semua item invoice
    ========================== */
    $stmt = $conn->prepare("
        SELECT id, product_id, qty
        FROM selling_products
        WHERE invoice_number = ?
        FOR UPDATE
    ");
    $stmt->bind_param("s", $invoice);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        throw new Exception("Data tidak ditemukan.");
    }

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    /* ==========================
       Kembalikan stok
    ========================== */
    $stmt_stock = $conn->prepare("
        UPDATE product_stocks
        SET quantity = quantity + ?
        WHERE id = ?
    ");

    foreach ($items as $item) {
        $qty = floatval($item['qty']);
        $product_id = intval($item['product_id']);

        $stmt_stock->bind_param("di", $qty, $product_id);
        $stmt_stock->execute();
    }
    $stmt_stock->close();

    /* ==========================
       Hapus transaksi
    ========================== */
    $stmt_delete = $conn->prepare("
        DELETE FROM selling_products
        WHERE invoice_number = ?
    ");
    $stmt_delete->bind_param("s", $invoice);
    $stmt_delete->execute();
    $stmt_delete->close();

    /* ==========================
       Commit
    ========================== */
    $conn->commit();

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();

} catch (Exception $e) {

    $conn->rollback();

    echo "<h2>Gagal hapus transaksi</h2>";
    echo "<p>".$e->getMessage()."</p>";
}