-- ============================================================
-- Migration: Add missing columns to bb_proses_detail
-- Database  : yossinor_ahadi
-- Created   : 2026-05-02
-- Notes     : Kolom kode_produksi dan status ada di hosting
--             tapi belum ada di database lokal.
--             Jalankan file ini sekali di database yossinor_ahadi.
-- ============================================================

ALTER TABLE `bb_proses_detail`
    ADD COLUMN `kode_produksi` VARCHAR(50) NULL DEFAULT NULL AFTER `id`,
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'aktif' AFTER `catatan`;

-- Tambahkan index untuk performa query GROUP BY kode_produksi
ALTER TABLE `bb_proses_detail`
    ADD INDEX `idx_kode_produksi` (`kode_produksi`),
    ADD INDEX `idx_status` (`status`);

ALTER TABLE bb_proses_detail MODIFY COLUMN id_proses_master INT NULL DEFAULT NULL;