<?php
// queries.php

/**
 * Ambil semua bahan
 */
function get_all_bahan($conn) {
    return $conn->query("SELECT * FROM bb_bahan_master ORDER BY nama_bahan ASC");
}

/**
 * Ambil semua supplier
 */
function get_all_supplier($conn) {
    return $conn->query("SELECT * FROM bb_supplier ORDER BY nama_supplier ASC");
}

/**
 * Ambil semua buyer
 */
function get_all_buyer($conn) {
    return $conn->query("SELECT * FROM bb_buyer ORDER BY nama_buyer ASC");
}

/**
 * Ambil HPP awal + penyusutan
 */
function get_hpp_dan_penyusutan($conn) {
    $sql = "
        SELECT hpp.id, hpp.nama_bahan, hpp.nama_supplier, hpp.tanggal_pembelian,
               hpp.berat_awal, hpp.total_modal, hpp.hpp_per_kg,
               penyusutan.penyusutan_jemur, penyusutan.penyusutan_kupas, penyusutan.penyusutan_total
        FROM bb_v_hpp_awal hpp
        LEFT JOIN bb_v_penyusutan_tahap penyusutan
          ON hpp.id = penyusutan.id_pembelian
        ORDER BY hpp.tanggal_pembelian DESC
    ";
    return $conn->query($sql);
}

/**
 * Ambil ringkasan laba rugi per batch
 */
function get_laba_rugi($conn) {
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
    GROUP BY pa.id
    ORDER BY pa.kode_batch ASC
    ";
    return $conn->query($sql);
}