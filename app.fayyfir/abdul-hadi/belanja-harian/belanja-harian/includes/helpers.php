<?php
// helpers.php

/**
 * Format angka
 */
function format_angka($angka, $decimals = 0) {
    return number_format((float)($angka ?? 0), $decimals, ',', '.');
}

/**
 * Format angka ke Rupiah
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)($angka ?? 0), 0, ',', '.');
}

/**
 * Format angka ke persen
 */
function format_persen($angka) {
    return number_format((float)($angka ?? 0), 2, ',', '.') . '%';
}

/**
 * Format tanggal (YYYY-MM-DD) ke DD/MM/YYYY
 */
function format_tanggal($tanggal) {
    if (!$tanggal) return '-';
    $d = new DateTime($tanggal);
    return $d->format('d/m/Y');
}