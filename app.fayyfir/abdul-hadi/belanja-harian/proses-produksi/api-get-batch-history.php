<?php
session_start();
require "../../config.php";
$conn = get_conn2();

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode([]);
    exit();
}

$id_pembelian = (int)($_GET['id_pembelian'] ?? 0);
$kode_produksi = trim($_GET['kode_produksi'] ?? '');

if ($id_pembelian <= 0 && empty($kode_produksi)) {
    echo json_encode([]);
    exit();
}

// Ambil riwayat seluruh batch & tahap yang sudah dilalui oleh sumber ini
$query = "
    SELECT 
        pd.kode_produksi,
        pd.id_pembelian,
        pd.metode_produksi,
        pd.status_batch,
        GROUP_CONCAT(DISTINCT COALESCE(pm.nama_proses, 'Persiapan') ORDER BY COALESCE(pm.urutan_tahap, 0) ASC SEPARATOR ' ➔ ') as stages_list,
        MAX(pd.created_at) as last_updated
    FROM bb_proses_detail pd
    LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
    WHERE (pd.id_pembelian = ? OR (? != '' AND pd.kode_produksi = ?))
    AND pd.status != 'batal'
    GROUP BY pd.kode_produksi, pd.id_pembelian
    ORDER BY MIN(pd.created_at) ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $id_pembelian, $kode_produksi, $kode_produksi);
$stmt->execute();
$res = $stmt->get_result();

$batches = [];
while ($row = $res->fetch_assoc()) {
    $kp = $row['kode_produksi'];
    $idp = $row['id_pembelian'];
    $url = "/app.fayyfir/abdul-hadi/belanja-harian/proses-produksi/detail-penyusutan.php?id=" . $idp . ($kp ? "&kode_produksi=" . urlencode($kp) : "");

    $batches[] = [
        'kode_produksi' => $kp,
        'id_pembelian'  => $idp,
        'stages_list'   => $row['stages_list'] ?: 'Persiapan',
        'status_batch'  => $row['status_batch'],
        'detail_url'    => $url
    ];
}

echo json_encode($batches);
