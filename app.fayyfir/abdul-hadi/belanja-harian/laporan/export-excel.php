<?php
session_start();
require "../../config.php";
require "../includes/helpers.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require '../../vendor/autoload.php'; // pastikan PhpSpreadsheet sudah diinstall via composer

if(!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Ambil parameter
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Pilih query sesuai type
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
        $filename = "Laporan_Laba_Rugi.xlsx";
        break;

    case 'hpp':
        $sql = "
        SELECT 
            v.kode_batch,
            v.nama_bahan,
            v.nama_supplier,
            v.berat_awal,
            v.harga_per_kg,
            v.total_modal,
            v.hpp_per_kg
        FROM bb_v_hpp_awal v
        " . ($id ? "WHERE v.kode_batch = '$id'" : "") . "
        ORDER BY v.kode_batch ASC
        ";
        $filename = "Laporan_HPP.xlsx";
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
        $filename = "Laporan_Penyusutan.xlsx";
        break;

    default:
        die("Type laporan tidak dikenali.");
}

// Eksekusi query
$result = $conn->query($sql);

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header kolom
$columns = array_keys($result->fetch_assoc() ?: []);
$result->data_seek(0); // reset pointer

$colIndex = 1;
foreach ($columns as $col) {
    $sheet->setCellValueByColumnAndRow($colIndex, 1, ucfirst(str_replace('_', ' ', $col)));
    $colIndex++;
}

// Data baris
$rowIndex = 2;
while($row = $result->fetch_assoc()) {
    $colIndex = 1;
    foreach($columns as $col) {
        $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $row[$col]);
        $colIndex++;
    }
    $rowIndex++;
}

// Header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();