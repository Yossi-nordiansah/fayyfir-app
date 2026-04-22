<?php
// validation.php

/**
 * Validasi field required
 */
function validate_required($value, $field_name) {
    if (trim($value) === '') {
        return "$field_name wajib diisi.";
    }
    return '';
}

/**
 * Validasi angka
 */
function validate_numeric($value, $field_name) {
    if (!is_numeric($value)) {
        return "$field_name harus berupa angka.";
    }
    return '';
}

/**
 * Validasi tanggal format YYYY-MM-DD
 */
function validate_date($value, $field_name) {
    $d = DateTime::createFromFormat('Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return "$field_name tidak valid (format: YYYY-MM-DD).";
    }
    return '';
}

/**
 * Gabungkan semua error menjadi string
 */
function display_errors($errors) {
    return implode('<br>', array_filter($errors));
}