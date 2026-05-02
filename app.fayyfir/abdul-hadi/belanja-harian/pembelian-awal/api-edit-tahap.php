<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$ids_str = $_POST['ids'] ?? '';
$berat_keluar_raw = str_replace('.', '', $_POST['berat_keluar'] ?? '0');
$berat_keluar_raw = str_replace(',', '.', $berat_keluar_raw);

$catatan = $_POST['catatan'] ?? '';
$redirect_url = $_POST['redirect_url'] ?? 'index.php';

$total_berat_keluar_baru = floatval($berat_keluar_raw);
$ids = array_filter(explode(',', $ids_str), 'is_numeric');

if (!empty($ids)) {
    $conn->begin_transaction();
    try {
        // 1. Ambil info dasar batch
        $ids_list = implode(',', $ids);
        $sqlInfo = "SELECT SUM(berat_masuk) as total_masuk, MAX(tahap_ke) as current_tahap, MAX(kode_produksi) as kp FROM bb_proses_detail WHERE id IN ($ids_list)";
        $resInfo = $conn->query($sqlInfo)->fetch_assoc();
        
        if (!$resInfo) throw new Exception("Data tahapan tidak ditemukan di database.");

        $total_berat_masuk = (float)$resInfo['total_masuk'];
        $current_tahap = (int)$resInfo['current_tahap'];
        $kode_produksi = $resInfo['kp'];

        // 2. Update setiap record secara proporsional
        foreach ($ids as $id) {
            $stmtRow = $conn->prepare("SELECT berat_masuk, id_pembelian FROM bb_proses_detail WHERE id = ?");
            $stmtRow->bind_param("i", $id);
            $stmtRow->execute();
            $row = $stmtRow->get_result()->fetch_assoc();
            
            if (!$row) continue;

            $berat_masuk = (float)$row['berat_masuk'];
            $id_pembelian = $row['id_pembelian'];
            
            // Proporsi: (berat_masuk / total_masuk_batch) * total_keluar_baru
            $new_keluar = ($total_berat_masuk > 0) ? ($berat_masuk / $total_berat_masuk) * $total_berat_keluar_baru : 0;
            $new_susut = $berat_masuk - $new_keluar;

            $stmt = $conn->prepare("UPDATE bb_proses_detail SET berat_keluar = ?, penyusutan = ?, catatan = ? WHERE id = ?");
            $stmt->bind_param("ddsi", $new_keluar, $new_susut, $catatan, $id);
            if (!$stmt->execute()) throw new Exception("Gagal update record ID $id: " . $stmt->error);

            // 3. Propagasi ke tahap berikutnya jika ada
            $next_tahap = $current_tahap + 1;
            
            // Query propagasi berbeda jika kode_produksi NULL
            if (empty($kode_produksi)) {
                $stmtNext = $conn->prepare("UPDATE bb_proses_detail SET berat_masuk = ? WHERE id_pembelian = ? AND kode_produksi IS NULL AND tahap_ke = ? AND status = 'aktif'");
                $stmtNext->bind_param("dii", $new_keluar, $id_pembelian, $next_tahap);
            } else {
                $stmtNext = $conn->prepare("UPDATE bb_proses_detail SET berat_masuk = ? WHERE id_pembelian = ? AND kode_produksi = ? AND tahap_ke = ? AND status = 'aktif'");
                $stmtNext->bind_param("disi", $new_keluar, $id_pembelian, $kode_produksi, $next_tahap);
            }
            $stmtNext->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Riwayat tahapan berhasil diperbarui.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Gagal memperbarui tahapan: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Data tidak valid (ID kosong). Pastikan Anda memilih tahapan yang benar.";
}

header("Location: " . $redirect_url);
exit();
