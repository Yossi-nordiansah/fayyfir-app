<?php
// functions.php

/**
 * Hitung HPP per kg
 * @param float $total_modal
 * @param float $berat_awal
 * @return float
 */
function hitung_hpp($total_modal, $berat_awal) {
    return $berat_awal != 0 ? $total_modal / $berat_awal : 0;
}

/**
 * Hitung penyusutan %
 * @param float $berat_awal
 * @param float $berat_akhir
 * @return float
 */
function hitung_penyusutan($berat_awal, $berat_akhir) {
    return $berat_awal != 0 ? (($berat_awal - $berat_akhir) / $berat_awal) * 100 : 0;
}

/**
 * Hitung margin harga jual
 * @param float $hpp
 * @param float $margin_percent
 * @return float
 */
function hitung_harga_jual($hpp, $margin_percent) {
    return $hpp * (1 + ($margin_percent / 100));
}

/**
 * Hitung laba bersih
 * @param float $total_penjualan
 * @param float $total_modal
 * @return float
 */
function hitung_laba_bersih($total_penjualan, $total_modal) {
    return $total_penjualan - $total_modal;
}