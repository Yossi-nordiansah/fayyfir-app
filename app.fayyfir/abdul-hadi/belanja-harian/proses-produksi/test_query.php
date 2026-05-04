<?php
require 'c:/laragon/www/fayyfirnew/app.fayyfir/abdul-hadi/config.php';
$conn = get_conn2();
$queryProduksi = "
    SELECT 
        COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) as batch_key,
        MAX(pd.kode_produksi) as kode_produksi,
        MIN(pd.id_pembelian) as sample_id_pembelian,
        MAX(pa.kode_batch) as sample_batch_pembelian,
        MAX(bm.id) as id_bahan,
        MAX(bm.nama_bahan) as nama_bahan,
        MAX(bm.satuan) as satuan,
        COALESCE(MAX(pm.urutan_tahap), 0) as current_tahap_urutan,
        SUM(CASE 
            WHEN last_stage.max_urutan = 0 THEN pd.berat_masuk 
            WHEN COALESCE(pm.urutan_tahap, 0) = last_stage.max_urutan THEN pd.berat_keluar 
            ELSE 0 
        END) as total_berat_akhir_tahap_ini,
        COUNT(DISTINCT pd.id_pembelian) as total_suppliers
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
    JOIN (
        SELECT COALESCE(pd3.kode_produksi, CONCAT('SINGLE-', pd3.id_pembelian)) as bk3, COALESCE(MAX(pm3.urutan_tahap), 0) as max_urutan
        FROM bb_proses_detail pd3
        LEFT JOIN bb_proses_master pm3 ON pm3.id = pd3.id_proses_master
        GROUP BY bk3
    ) last_stage ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_stage.bk3
    WHERE pa.status != 'selesai_siap_jual'
    GROUP BY batch_key
    ORDER BY MAX(pd.created_at) DESC
";
$res = $conn->query($queryProduksi);
if ($res && $res->num_rows > 0) {
    while($row=$res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "EMPTY\n";
    if ($conn->error) echo "ERROR: " . $conn->error;
}
