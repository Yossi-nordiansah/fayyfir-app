<?php
session_start();
require "../../config.php";
$conn = $conn2;

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || !isset($_GET['id'])) {
    echo json_encode([]);
    exit();
}

$id_penampungan = (int)$_GET['id'];

$query = "
    SELECT 
        pnd.*,
        s.nama_supplier,
        pa.kode_batch,
        (SELECT COALESCE(SUM(pd.berat_masuk), 0) FROM bb_proses_detail pd 
         WHERE pd.id_pembelian = pnd.id_pembelian AND pd.id_penampungan = pnd.id_penampungan 
         AND pd.tahap_ke = 0 AND pd.status = 'aktif') as terpakai
    FROM bb_penampungan_detail pnd
    JOIN bb_pembelian_awal pa ON pnd.id_pembelian = pa.id
    JOIN bb_supplier s ON pa.id_supplier = s.id
    WHERE pnd.id_penampungan = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_penampungan);
$stmt->execute();
$result = $stmt->get_result();

$details = [];
while ($row = $result->fetch_assoc()) {
    $details[] = [
        'nama_supplier' => $row['nama_supplier'],
        'kode_batch' => $row['kode_batch'],
        'berat_masuk' => (float)$row['berat_masuk'],
        'terpakai' => (float)$row['terpakai'],
        'sisa' => (float)$row['berat_masuk'] - (float)$row['terpakai']
    ];
}

echo json_encode($details);
