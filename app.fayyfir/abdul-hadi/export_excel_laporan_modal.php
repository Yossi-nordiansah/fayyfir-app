<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require_once __DIR__ . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// header kolom
$sheet->setCellValue('A1', 'Tanggal');
$sheet->setCellValue('B1', 'Deskripsi');
$sheet->setCellValue('C1', 'Debit');
$sheet->setCellValue('D1', 'Kredit');
$sheet->setCellValue('E1', 'Saldo Akhir');

// auto size
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ambil data sama persis
$logs = [];

$result_modal = $conn->query("SELECT date, description, amount FROM modal_log ORDER BY date ASC");
while ($row = $result_modal->fetch_assoc()) {
  $logs[] = [
    'date' => $row['date'],
    'desc' => $row['description'],
    'debit' => 0,
    'credit' => $row['amount'],
  ];
}

$result_aset = $conn->query("SELECT created_at AS date, name AS description, value FROM assets ORDER BY created_at ASC");
while ($row = $result_aset->fetch_assoc()) {
  $logs[] = [
    'date' => $row['date'],
    'desc' => 'Pembelian Aset: ' . $row['description'],
    'debit' => $row['value'],
    'credit' => 0,
  ];
}

$result_exp = $conn->query("SELECT expense_date AS date, expense_type, amount FROM expenses ORDER BY expense_date ASC");
while ($row = $result_exp->fetch_assoc()) {
  $logs[] = [
    'date' => $row['date'],
    'desc' => 'Biaya: ' . $row['expense_type'],
    'debit' => $row['amount'],
    'credit' => 0,
  ];
}

$result_kontainer = $conn->query("SELECT c.updated_at AS date, c.container_number, c.selling_price FROM containers c WHERE c.status = 'verified' AND c.selling_price IS NOT NULL ORDER BY c.updated_at ASC");
while ($row = $result_kontainer->fetch_assoc()) {
  $logs[] = [
    'date' => $row['date'],
    'desc' => 'Margin Kontainer ' . $row['container_number'],
    'debit' => 0,
    'credit' => $row['selling_price'],
  ];
}

usort($logs, function($a, $b) {
  return strtotime($a['date']) - strtotime($b['date']);
});

// saldo
$runningSaldo = 0;
foreach ($logs as $index => $log) {
  $runningSaldo += $log['credit'] - $log['debit'];
  $logs[$index]['saldo'] = $runningSaldo;
}

// masukkan
$rowIndex = 2;
foreach ($logs as $log) {
  $sheet->setCellValue('A' . $rowIndex, date("d/m/Y", strtotime($log['date'])));
  $sheet->setCellValue('B' . $rowIndex, $log['desc']);

  // debit
  if ($log['debit'] > 0) {
    $sheet->setCellValue('C' . $rowIndex, $log['debit']);
    $sheet->getStyle('C' . $rowIndex)
          ->getNumberFormat()
          ->setFormatCode('#,##0');
  } else {
    $sheet->setCellValue('C' . $rowIndex, '-');
  }

  // kredit
  if ($log['credit'] > 0) {
    $sheet->setCellValue('D' . $rowIndex, $log['credit']);
    $sheet->getStyle('D' . $rowIndex)
          ->getNumberFormat()
          ->setFormatCode('#,##0');
  } else {
    $sheet->setCellValue('D' . $rowIndex, '-');
  }

  // saldo
  $sheet->setCellValue('E' . $rowIndex, $log['saldo']);
  $sheet->getStyle('E' . $rowIndex)
        ->getNumberFormat()
        ->setFormatCode('#,##0');

  $rowIndex++;
}

// download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="laporan.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;