<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pembelian_id = isset($_GET['pembelian_id']) ? intval($_GET['pembelian_id']) : 0;

if ($id > 0) {
    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // Hapus log proses
        $stmt_del = $conn->prepare("DELETE FROM bb_proses_detail WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();

        // Cari tahap terbaru yang tersisa
        $stmt_latest = $conn->prepare("SELECT tahap_ke FROM bb_proses_detail WHERE id_pembelian = ? ORDER BY tahap_ke DESC LIMIT 1");
        $stmt_latest->bind_param("i", $pembelian_id);
        $stmt_latest->execute();
        $res_latest = $stmt_latest->get_result();
        
        if ($res_latest->num_rows > 0) {
            $row = $res_latest->fetch_assoc();
            $new_status = "tahap" . $row['tahap_ke'];
        } else {
            // Jika tidak ada tahap lagi, kembali ke status 'uang_terbayar' atau 'load'
            // Kita asumsikan kembali ke 'uang_terbayar' jika sudah pernah bayar, atau default 'load'
            $new_status = 'uang_terbayar'; 
        }
        $stmt_latest->close();

        // Update status di bb_pembelian_awal
        $stmt_upd = $conn->prepare("UPDATE bb_pembelian_awal SET status = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $new_status, $pembelian_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $conn->commit();
        header("Location: detail-penyusutan?id=$pembelian_id&success=deleted");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: detail-penyusutan?id=$pembelian_id&error=deletefailed");
    }
} else {
    header("Location: index");
}
?>
