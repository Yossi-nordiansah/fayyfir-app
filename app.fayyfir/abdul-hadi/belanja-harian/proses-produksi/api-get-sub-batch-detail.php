<?php
session_start();
require "../../config.php";
$conn = get_conn2();

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || empty($_GET['kode_produksi'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit();
}

$kode_produksi = trim($_GET['kode_produksi']);

// Query log proses untuk kode_produksi spesifik ini
$query_log = $conn->prepare("
    SELECT 
        COALESCE(MAX(pm.nama_proses), 'Persiapan') as nama_proses,
        MAX(pd.tanggal_proses) as tanggal_proses,
        pd.tahap_ke,
        SUM(pd.berat_masuk) as berat_masuk,
        SUM(pd.berat_keluar) as berat_keluar,
        SUM(pd.penyusutan) as penyusutan,
        MAX(pd.metode_produksi) as metode_produksi,
        MAX(pd.status_batch) as status_batch,
        MAX(pd.hpp_temporary) as hpp_temporary,
        MAX(pd.hpp_final) as hpp_final,
        MAX(pd.penyusutan_final) as penyusutan_final,
        GROUP_CONCAT(DISTINCT pd.catatan SEPARATOR '; ') as catatan,
        MAX(bm.satuan) as satuan
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
    WHERE pd.kode_produksi = ? AND pd.status IN ('aktif','batal','dihentikan')
    GROUP BY pd.tahap_ke
    ORDER BY pd.tahap_ke ASC
");
$query_log->bind_param("s", $kode_produksi);
$query_log->execute();
$result_log = $query_log->get_result();

$logs = [];
$batch_metode = 'tertimbang';
$batch_status = 'berjalan';
$satuan = 'Kg';

while ($row = $result_log->fetch_assoc()) {
    if (!empty($row['metode_produksi'])) $batch_metode = $row['metode_produksi'];
    if (!empty($row['status_batch'])) $batch_status = $row['status_batch'];
    if (!empty($row['satuan'])) $satuan = $row['satuan'];

    $row['catatan'] = trim(str_replace('[ALL_SUPPLIERS]', '', $row['catatan']));
    $logs[] = $row;
}

echo json_encode([
    'success' => true,
    'kode_produksi' => $kode_produksi,
    'metode_produksi' => $batch_metode,
    'status_batch' => $batch_status,
    'satuan' => $satuan,
    'logs' => $logs
]);
