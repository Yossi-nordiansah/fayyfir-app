<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$kode_produksi = $_POST['kode_produksi'] ?? '';
$id_pembelian_fallback = (int)$_POST['id_pembelian']; // Used if kode_produksi is empty
$next_stage = (int)$_POST['next_stage'];
$total_raw = str_replace('.', '', $_POST['berat_keluar']);
$total_berat_keluar = (float)$total_raw;
$tanggal_proses = $_POST['tanggal_proses'] ?: date('Y-m-d');
$catatan = $_POST['catatan'];

$conn->begin_transaction();

try {
    // 1. Ambil list item yang ada di tahap SEBELUMNYA untuk batch ini
    if ($kode_produksi) {
        $sqlItems = "
            SELECT pd.*, COALESCE(pm.urutan_tahap, 0) as urutan_tahap 
            FROM bb_proses_detail pd
            LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
            WHERE pd.kode_produksi = ? 
            AND COALESCE(pm.urutan_tahap, 0) = (
                SELECT COALESCE(MAX(pm2.urutan_tahap), 0) 
                FROM bb_proses_detail pd2 
                LEFT JOIN bb_proses_master pm2 ON pm2.id = pd2.id_proses_master
                WHERE pd2.kode_produksi = ? AND pd2.status = 'aktif'
            ) AND pd.status = 'aktif'
        ";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("ss", $kode_produksi, $kode_produksi);
    } else {
        $sqlItems = "
            SELECT pd.*, COALESCE(pm.urutan_tahap, 0) as urutan_tahap 
            FROM bb_proses_detail pd
            LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
            WHERE pd.id_pembelian = ? 
            AND COALESCE(pm.urutan_tahap, 0) = (
                SELECT COALESCE(MAX(pm2.urutan_tahap), 0) 
                FROM bb_proses_detail pd2 
                LEFT JOIN bb_proses_master pm2 ON pm2.id = pd2.id_proses_master
                WHERE pd2.id_pembelian = ? AND pd2.status = 'aktif'
            ) AND pd.status = 'aktif'
        ";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("ii", $id_pembelian_fallback, $id_pembelian_fallback);
    }

    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($items)) {
        throw new Exception("Tidak ada item ditemukan untuk diproses.");
    }

    $total_berat_masuk_batch = 0;
    foreach ($items as $item) {
        $total_berat_masuk_batch += (float)$item['berat_keluar'];
    }

    if ($total_berat_masuk_batch <= 0) {
        throw new Exception("Total berat masuk batch 0.");
    }

    // 2. Ambil id_proses_master untuk NEXT STAGE
    // Kita ambil id_bahan dari salah satu item
    $sample_pa = $conn->query("SELECT id_bahan FROM bb_pembelian_awal WHERE id = " . $items[0]['id_pembelian'])->fetch_assoc();
    $id_bahan = $sample_pa['id_bahan'];

    $sqlMaster = "SELECT id FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ?";
    $stmtMaster = $conn->prepare($sqlMaster);
    $stmtMaster->bind_param("ii", $id_bahan, $next_stage);
    $stmtMaster->execute();
    $id_proses_master = $stmtMaster->get_result()->fetch_assoc()['id'];

    if (!$id_proses_master) {
        throw new Exception("Master tahap $next_stage tidak ditemukan.");
    }

    // 3. Insert ke bb_proses_detail (Proportional distribution)
    foreach ($items as $item) {
        $berat_masuk = (float)$item['berat_keluar'];
        $berat_keluar = ($berat_masuk / $total_berat_masuk_batch) * $total_berat_keluar;
        $id_pembelian = $item['id_pembelian'];

        $sqlInsert = "INSERT INTO bb_proses_detail (kode_produksi, id_pembelian, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("siiisdds", $kode_produksi, $id_pembelian, $id_proses_master, $next_stage, $tanggal_proses, $berat_masuk, $berat_keluar, $catatan);
        $stmtInsert->execute();

        // Update status pembelian
        $new_status = "tahap" . $next_stage;
        $conn->query("UPDATE bb_pembelian_awal SET status = '$new_status' WHERE id = $id_pembelian");
    }

    $conn->commit();
    $_SESSION['success'] = "Berhasil memproses ke tahap $next_stage.";

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: index.php");
exit();
