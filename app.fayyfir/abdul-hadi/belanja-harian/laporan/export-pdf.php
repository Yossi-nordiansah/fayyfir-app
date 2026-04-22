<?php
session_start();
require "../../config.php";
require "../includes/helpers.php";
require '../../tcpdf/tcpdf.php'; // pastikan TCPDF sudah diinstall via composer

use TCPDF;

if(!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Ambil parameter
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Pilih query & judul
switch($type) {
    case 'laba':
        $sql = "
        SELECT 
            pa.kode_batch,
            b.nama_bahan,
            s.nama_supplier,
            pa.total_modal,
            IFNULL(SUM(p.total_penjualan), 0) AS total_penjualan,
            IFNULL(SUM(p.laba_bersih), 0) AS laba_bersih
        FROM bb_pembelian_awal pa
        JOIN bb_bahan_master b ON pa.id_bahan = b.id
        JOIN bb_supplier s ON pa.id_supplier = s.id
        LEFT JOIN bb_penjualan p ON pa.id = p.id_pembelian
        " . ($id ? "WHERE pa.kode_batch = '$id'" : "") . "
        GROUP BY pa.id
        ORDER BY pa.kode_batch ASC
        ";
        $title = "Laporan Laba Rugi";
        break;

    case 'hpp':
        $sql = "
        SELECT 
            v.id,
            v.nama_bahan,
            v.nama_supplier,
            v.berat_awal,
            v.harga_per_kg,
            v.total_modal,
            v.hpp_per_kg
        FROM bb_v_hpp_awal v
        " . ($id ? "WHERE v.id = '$id'" : "") . "
        ORDER BY v.id ASC
        ";
        $title = "Laporan Ringkasan Modal / HPP";
        break;

    case 'penyusutan':
        $sql = "
        SELECT 
            v.kode_batch,
            v.penyusutan_jemur,
            v.penyusutan_kupas,
            v.penyusutan_total
        FROM bb_v_penyusutan_tahap v
        " . ($id ? "WHERE v.kode_batch = '$id'" : "") . "
        ORDER BY v.kode_batch ASC
        ";
        $title = "Laporan Penyusutan Per Batch";
        break;

    default:
        die("Type laporan tidak dikenali.");
}

// Eksekusi query
$result = $conn->query($sql);
if(!$result) {
    die("Query gagal: ".$conn->error);
}

// Buat PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Fayyfir');
$pdf->SetAuthor('Fayyfir');
$pdf->SetTitle($title);
$pdf->SetMargins(10, 15, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Judul
$pdf->Cell(0, 10, $title, 0, 1, 'C');

// Table header
$columns = array_keys($result->fetch_assoc() ?: []);
$result->data_seek(0); // reset pointer

$html = '<table border="1" cellpadding="4"><thead><tr style="background-color:#f2f2f2;">';
foreach ($columns as $col) {
    $html .= '<th>'.ucfirst(str_replace('_',' ',$col)).'</th>';
}
$html .= '</tr></thead><tbody>';

// Table rows
while($row = $result->fetch_assoc()) {
    $html .= '<tr>';
    foreach($columns as $col) {
        $html .= '<td>'.$row[$col].'</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF ke browser
$pdf->Output($title.'.pdf', 'I');
exit();