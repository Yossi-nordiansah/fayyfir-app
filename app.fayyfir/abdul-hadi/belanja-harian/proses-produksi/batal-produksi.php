<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$kode_produksi = $_POST['kode_produksi'] ?? '';
$id_pembelian = (int)($_POST['id_pembelian'] ?? 0);

if (empty($kode_produksi) && $id_pembelian <= 0) {
    die("Error: Parameter tidak valid.");
}

$conn->begin_transaction();

try {
    // 1. Dapatkan semua id_pembelian yang terlibat dalam produksi ini
    $ids_to_revert = [];
    if (!empty($kode_produksi)) {
        $sql = "SELECT DISTINCT id_pembelian FROM bb_proses_detail WHERE kode_produksi = ? AND status = 'aktif'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $kode_produksi);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ids_to_revert[] = $row['id_pembelian'];
        }
    } else {
        $ids_to_revert[] = $id_pembelian;
    }

    if (empty($ids_to_revert)) {
        throw new Exception("Tidak ada produksi aktif yang ditemukan untuk dibatalkan.");
    }

    // 2. Tandai semua bb_proses_detail sebagai 'batal'
    if (!empty($kode_produksi)) {
        $sqlUpdate = "UPDATE bb_proses_detail SET status = 'batal' WHERE kode_produksi = ? AND status = 'aktif'";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("s", $kode_produksi);
        $stmtUpdate->execute();
    } else {
        $sqlUpdate = "UPDATE bb_proses_detail SET status = 'batal' WHERE id_pembelian = ? AND status = 'aktif'";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id_pembelian);
        $stmtUpdate->execute();
    }

    // 3. Revert status bb_pembelian_awal
    // Kita asumsikan dikembalikan ke 'uang_terbayar' karena itu status sebelum 'proses'
    // Atau kita bisa cek jika ada record lain yang masih aktif, tapi biasanya batal membatalkan seluruh alur.
    foreach ($ids_to_revert as $id) {
        $conn->query("UPDATE bb_pembelian_awal SET status = 'uang_terbayar' WHERE id = $id");
    }

    $conn->commit();
    $_SESSION['success'] = "Produksi berhasil dibatalkan.";
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: index.php");
exit();
