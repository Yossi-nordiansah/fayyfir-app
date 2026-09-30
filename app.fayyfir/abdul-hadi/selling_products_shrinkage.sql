-- ================================================================
-- MIGRATION: Penambahan Kolom Susut Pengiriman pada Penjualan Produk
-- Tabel    : selling_products
-- Fitur    : Opsi A - Susut Diserap ke HPP Penjualan (BPP)
-- Tanggal  : 2026-09-19
-- ================================================================

ALTER TABLE `selling_products`
ADD COLUMN `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `qty`;
