<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Gunakan transaksi untuk menjaga integritas data
    $conn->begin_transaction();

    try {
        // 1. Hapus rincian proses terkait
        $stmt_detail = $conn->prepare("DELETE FROM bb_proses_detail WHERE id_pembelian = ?");
        $stmt_detail->bind_param("i", $id);
        $stmt_detail->execute();
        $stmt_detail->close();

        // 2. Hapus rincian penampungan terkait (Stok Gabungan)
        $stmt_pool = $conn->prepare("DELETE FROM bb_penampungan_detail WHERE id_pembelian = ?");
        $stmt_pool->bind_param("i", $id);
        $stmt_pool->execute();
        $stmt_pool->close();

        // 3. Hapus rincian penjualan terkait (jika ada)
        $stmt_sale = $conn->prepare("DELETE FROM bb_penjualan WHERE id_pembelian = ?");
        $stmt_sale->bind_param("i", $id);
        $stmt_sale->execute();
        $stmt_sale->close();

        // 4. Hapus pengeluaran terkait
        $stmt_exp = $conn->prepare("DELETE FROM bb_pengeluaran WHERE id_pembelian = ?");
        $stmt_exp->bind_param("i", $id);
        $stmt_exp->execute();
        $stmt_exp->close();

        // 5. Akhirnya hapus data pembelian awal
        $stmt_main = $conn->prepare("DELETE FROM bb_pembelian_awal WHERE id = ?");
        $stmt_main->bind_param("i", $id);
        $stmt_main->execute();
        $stmt_main->close();

        $conn->commit();
        header("Location: index.php?success=deleted");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: index.php?error=deletefailed");
    }
} else {
    header("Location: index");
}
exit();
?>
