<?php
require 'c:/laragon/www/fayyfirnew/app.fayyfir/abdul-hadi/config.php';
$conn = get_conn2();
$sqlInsert = "INSERT INTO bb_proses_detail (kode_produksi, id_pembelian, id_penampungan, id_proses_master, tahap_ke, tanggal_proses, berat_masuk, berat_keluar, catatan) VALUES (?, ?, ?, NULL, 0, ?, ?, ?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);
if (!$stmtInsert) {
    die("PREPARE ERROR: " . $conn->error);
}
$k = 'TEST-2';
$p = 38;
$n = null;
$t = '2026-05-02';
$b = 100;
$c = 'test';
$stmtInsert->bind_param('siisdds', $k, $p, $n, $t, $b, $b, $c);
if (!$stmtInsert->execute()) {
    echo 'EXECUTE ERROR: ' . $stmtInsert->error;
} else {
    echo 'SUCCESS';
}
