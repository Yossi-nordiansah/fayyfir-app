<?php
session_start();
require "../../config.php";
$conn = get_conn2();

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$kode_produksi = $_POST['kode_produksi'] ?? '';
$id_pembelian  = (int)($_POST['id_pembelian'] ?? 0);
$redirect_to   = $_POST['redirect_to'] ?? 'index.php';

if (empty($kode_produksi) && $id_pembelian <= 0) {
    die("Error: Parameter tidak valid.");
}

$conn->begin_transaction();

try {
    // 1. Cek apakah batch sudah melewati tahap proses nyata (tahap_ke > 0 dengan status aktif)
    $has_process = false;
    if (!empty($kode_produksi)) {
        $sqlCheck = "SELECT COUNT(*) as total FROM bb_proses_detail WHERE kode_produksi = ? AND tahap_ke > 0 AND status = 'aktif'";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $kode_produksi);
    } else {
        $sqlCheck = "SELECT COUNT(*) as total FROM bb_proses_detail WHERE id_pembelian = ? AND kode_produksi IS NULL AND tahap_ke > 0 AND status = 'aktif'";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $id_pembelian);
    }
    $stmtCheck->execute();
    $checkResult = $stmtCheck->get_result()->fetch_assoc();
    $has_process = ($checkResult['total'] > 0);

    // 2. Dapatkan semua id_pembelian yang terlibat dalam batch ini
    $ids_to_revert = [];
    if (!empty($kode_produksi)) {
        $sql  = "SELECT DISTINCT id_pembelian FROM bb_proses_detail WHERE kode_produksi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $kode_produksi);
    } else {
        $sql  = "SELECT DISTINCT id_pembelian FROM bb_proses_detail WHERE id_pembelian = ? AND kode_produksi IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pembelian);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ids_to_revert[] = (int)$row['id_pembelian'];
    }

    if (empty($ids_to_revert)) {
        // Fallback untuk single tanpa kode_produksi
        if ($id_pembelian > 0) {
            $ids_to_revert[] = $id_pembelian;
        } else {
            throw new Exception("Tidak ada data produksi yang ditemukan untuk dibatalkan.");
        }
    }

    if ($has_process) {
        // ===== SKENARIO A: Sudah ada tahap proses nyata =====
        // Tandai sebagai 'dihentikan' — stok TIDAK dikembalikan karena bahan sudah diproses
        if (!empty($kode_produksi)) {
            $sqlUpdate = "UPDATE bb_proses_detail SET status = 'dihentikan' WHERE kode_produksi = ? AND status = 'aktif'";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("s", $kode_produksi);
        } else {
            $sqlUpdate = "UPDATE bb_proses_detail SET status = 'dihentikan' WHERE id_pembelian = ? AND kode_produksi IS NULL AND status = 'aktif'";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("i", $id_pembelian);
        }
        $stmtUpdate->execute();

        // Status bb_pembelian_awal dibiarkan tetap 'proses' (bahan sudah terpakai sebagian)
        $conn->commit();
        $_SESSION['success'] = "Produksi dihentikan. Bahan yang sudah diproses tetap tercatat dalam riwayat batch.";
    } else {
        // ===== SKENARIO B: Hanya tahap_ke=0, belum ada proses nyata =====
        // Batal total — semua dikembalikan ke stok
        if (!empty($kode_produksi)) {
            $sqlUpdate = "UPDATE bb_proses_detail SET status = 'batal' WHERE kode_produksi = ? AND status = 'aktif'";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("s", $kode_produksi);
        } else {
            $sqlUpdate = "UPDATE bb_proses_detail SET status = 'batal' WHERE id_pembelian = ? AND kode_produksi IS NULL AND status = 'aktif'";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("i", $id_pembelian);
        }
        $stmtUpdate->execute();

        // Kembalikan status bb_pembelian_awal ke sebelumnya
        foreach ($ids_to_revert as $id) {
            $sqlRevert = "UPDATE bb_pembelian_awal SET status = 'uang_terbayar' WHERE id = ? AND status = 'proses'";
            $stmtRevert = $conn->prepare($sqlRevert);
            $stmtRevert->bind_param("i", $id);
            $stmtRevert->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Produksi berhasil dibatalkan. Seluruh stok dikembalikan.";
    }
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: " . $redirect_to);
exit();
