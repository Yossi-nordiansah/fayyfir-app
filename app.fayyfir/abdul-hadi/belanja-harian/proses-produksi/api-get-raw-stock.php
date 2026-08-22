<?php
session_start();
require "../../config.php";
$conn = get_conn2(); // Lazy loader — tidak buka koneksi ganda

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || !isset($_GET['id_bahan'])) {
    echo json_encode([]);
    exit();
}

$id_bahan = (int)$_GET['id_bahan'];

$batches    = [];
$sources    = [];
$total_stok = 0;
$satuan     = 'Kg';

// 1. Ambil Stok Mandiri (Per Supplier)
// PENTING: exclude bb_proses_detail yang status='batal' agar stok yang dibatalkan kembali dihitung sebagai tersedia
$query_mandiri = "
    SELECT 
        pa.id as id_pembelian,
        pa.kode_batch,
        s.id as id_supplier,
        s.nama_supplier,
        pa.berat_awal,
        bm.satuan,
        IFNULL(pd_agg.terpakai_produksi, 0) as terpakai_produksi,
        IFNULL(pnd_agg.terpakai_penampungan, 0) as terpakai_penampungan
    FROM bb_pembelian_awal pa
    JOIN bb_supplier s ON s.id = pa.id_supplier
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN (
        SELECT id_pembelian, SUM(berat_masuk) as terpakai_produksi
        FROM bb_proses_detail
        WHERE tahap_ke = 0 AND status != 'batal'
        GROUP BY id_pembelian
    ) pd_agg ON pd_agg.id_pembelian = pa.id
    LEFT JOIN (
        SELECT id_pembelian, SUM(berat_masuk) as terpakai_penampungan
        FROM bb_penampungan_detail
        GROUP BY id_pembelian
    ) pnd_agg ON pnd_agg.id_pembelian = pa.id
    WHERE pa.id_bahan = ? AND (pa.status IS NULL OR pa.status != 'selesai_siap_jual')
    ORDER BY pa.tanggal_pembelian ASC
";

$stmt = $conn->prepare($query_mandiri);
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $stok   = max(0, round($row['berat_awal'] - $row['terpakai_produksi'] - $row['terpakai_penampungan'], 2));
    $satuan = $row['satuan'];
    $total_stok += $stok;

    $key = 's' . $row['id_supplier'];
    if (!isset($sources[$key])) {
        $sources[$key] = [
            'id'          => $key,
            'nama'        => $row['nama_supplier'],
            'total_stok'  => 0,
            'is_gabungan' => false
        ];
    }
    $sources[$key]['total_stok'] += $stok;
}

// 2. Ambil Stok Gabungan (Penampungan)
// PENTING: exclude bb_proses_detail yang status='batal' agar stok yang dibatalkan kembali dihitung sebagai tersedia
$query_gabungan = "
    SELECT 
        pn.id,
        pn.nama_penampungan,
        bm.satuan,
        IFNULL(pnd_agg.total_masuk, 0) as total_masuk,
        IFNULL(pd_agg.terpakai, 0) as terpakai
    FROM bb_penampungan pn
    JOIN bb_bahan_master bm ON bm.id = pn.id_bahan
    LEFT JOIN (
        SELECT id_penampungan, SUM(berat_masuk) as total_masuk
        FROM bb_penampungan_detail
        GROUP BY id_penampungan
    ) pnd_agg ON pnd_agg.id_penampungan = pn.id
    LEFT JOIN (
        SELECT id_penampungan, SUM(berat_masuk) as terpakai
        FROM bb_proses_detail
        WHERE tahap_ke = 0 AND status != 'batal'
        GROUP BY id_penampungan
    ) pd_agg ON pd_agg.id_penampungan = pn.id
    WHERE pn.id_bahan = ?
    ORDER BY pn.created_at ASC
";
$stmt = $conn->prepare($query_gabungan);
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $stok       = max(0, round($row['total_masuk'] - $row['terpakai'], 2));
    $total_stok += $stok;

    $key          = 'p' . $row['id'];
    $sources[$key] = [
        'id'          => $key,
        'nama'        => $row['nama_penampungan'] . ' [GABUNGAN]',
        'total_stok'  => $stok,
        'is_gabungan' => true
    ];
}

$filtered_sources = array_filter(array_values($sources), function($s) {
    return $s['total_stok'] > 0;
});

echo json_encode([
    'satuan'     => $satuan,
    'total_stok' => round($total_stok, 2),
    'suppliers'  => array_values($filtered_sources)
]);
