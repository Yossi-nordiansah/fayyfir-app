<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$id_pembelian  = intval($_POST['id_pembelian']);
$id_supplier   = intval($_POST['id_supplier']);
$berat_raw     = str_replace('.', '', $_POST['berat_digunakan'] ?? '');
$redirect_url  = $_POST['redirect_url'] ?? 'index.php';
$berat         = floatval($berat_raw);

if ($id_pembelian > 0 && $id_supplier > 0 && $berat > 0) {
    $conn->begin_transaction();
    try {
        // Ambil kode_produksi dari redirect_url untuk exclude batch ini dari cek stok
        $kode_produksi_url = '';
        $parts = parse_url($redirect_url);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $qp);
            $kode_produksi_url = $qp['kode_produksi'] ?? '';
        }

        // 1. Cek stok tersedia: berat_awal - pemakaian batch LAIN (bukan batch ini)
        $rowOld = $conn->query("SELECT berat_awal, harga_per_kg FROM bb_pembelian_awal WHERE id = $id_pembelian")->fetch_assoc();
        $berat_awal_lama = (float)$rowOld['berat_awal'];
        $harga  = (float)$rowOld['harga_per_kg'];

        // Hitung pemakaian oleh batch lain (bukan kode_produksi saat ini)
        $stmtOther = $conn->prepare("SELECT COALESCE(SUM(pd.berat_masuk), 0) as other_usage FROM bb_proses_detail pd WHERE pd.id_pembelian = ? AND pd.tahap_ke = 0 AND pd.status = 'aktif' AND pd.kode_produksi != ?");
        $stmtOther->bind_param("is", $id_pembelian, $kode_produksi_url);
        $stmtOther->execute();
        $other_usage = (float)$stmtOther->get_result()->fetch_assoc()['other_usage'];

        $max_tersedia = $berat_awal_lama - $other_usage;

        if ($berat > $max_tersedia) {
            $_SESSION['error'] = "Kuantitas ($berat Kg) melebihi stok tersedia dari supplier ini (" . number_format($max_tersedia, 0, ',', '.') . " Kg).";
            header("Location: " . $redirect_url);
            exit();
        }
        $total_modal = $berat * $harga;

        // 2. Update berat_awal & supplier di tabel pembelian awal
        //    Ini yang menjadi dasar perhitungan stok (total_beli di view)
        $stmt = $conn->prepare("UPDATE bb_pembelian_awal SET id_supplier = ?, berat_awal = ?, total_modal = ? WHERE id = ?");
        $stmt->bind_param("iddi", $id_supplier, $berat, $total_modal, $id_pembelian);
        if (!$stmt->execute()) throw new Exception("Gagal update pembelian: " . $stmt->error);

        // 3. Update berat_masuk di bb_proses_detail tahap 0 (Persiapan)
        //    Hanya untuk kode_produksi batch ini saja agar batch lain tidak ikut berubah
        if (!empty($kode_produksi_url)) {
            $stmtPd = $conn->prepare("UPDATE bb_proses_detail SET berat_masuk = ?, berat_keluar = ? WHERE id_pembelian = ? AND kode_produksi = ? AND tahap_ke = 0 AND status = 'aktif'");
            $stmtPd->bind_param("ddis", $berat, $berat, $id_pembelian, $kode_produksi_url);
            $stmtPd->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Rincian supplier berhasil diperbarui. Stok otomatis menyesuaikan.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Gagal memperbarui: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Data tidak lengkap (ID pembelian=$id_pembelian, supplier=$id_supplier, berat=$berat).";
}

header("Location: " . $redirect_url);
exit();
