-- ============================================================
-- Migration: Combined Schema Updates & View Fixes
-- File      : 004_combined_updates.sql
-- Tanggal   : 2026-08-13
-- Deskripsi : Penggabungan perbaikan schema & view bb_v_stok_bahan
-- ============================================================

-- 1. Tambah kolom metode produksi & revaluasi HPP pada bb_proses_detail
-- CATATAN: Jika muncul #1060 (Duplicate column), artinya kolom-kolom ini SUDAH BERHASIL ditambahkan sebelumnya.
-- Dalam hal ini, Anda bisa mengabaikan / melewati Bagian 1 ini dan langsung jalankan Bagian 2 s/d 4.
ALTER TABLE `bb_proses_detail` 
    ADD COLUMN IF NOT EXISTS `metode_produksi` ENUM('tertimbang', 'belum_tertimbang') NULL DEFAULT 'tertimbang' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `status_batch` ENUM('berjalan', 'closed') NULL DEFAULT 'berjalan' AFTER `metode_produksi`,
    ADD COLUMN IF NOT EXISTS `hpp_temporary` DECIMAL(15,2) NULL AFTER `status_batch`,
    ADD COLUMN IF NOT EXISTS `hpp_final` DECIMAL(15,2) NULL AFTER `hpp_temporary`,
    ADD COLUMN IF NOT EXISTS `penyusutan_final` DECIMAL(10,2) NULL AFTER `hpp_final`,
    ADD COLUMN IF NOT EXISTS `closed_at` DATETIME NULL AFTER `penyusutan_final`;

-- 2. Update ENUM status pada bb_proses_detail (tambah opsi 'dihentikan')
ALTER TABLE `bb_proses_detail`
    MODIFY COLUMN `status` ENUM('aktif','batal','dihentikan') NOT NULL DEFAULT 'aktif';

-- 3. Perbaiki data lama yang status-nya NULL atau kosong
UPDATE `bb_proses_detail`
    SET `status` = 'aktif'
    WHERE `status` IS NULL OR `status` = '';

-- 4. Recreate View bb_v_stok_bahan dengan kalkulasi stok & status terbaru
DROP TABLE IF EXISTS `bb_v_stok_bahan`;
DROP VIEW IF EXISTS `bb_v_stok_bahan`;

CREATE OR REPLACE VIEW `bb_v_stok_bahan` AS 
SELECT 
    `bm`.`id` AS `id_bahan`,
    `bm`.`nama_bahan` AS `nama_bahan`,
    `bm`.`satuan` AS `satuan`,
    (SELECT COALESCE(SUM(`pa`.`berat_awal`), 0) 
     FROM `bb_pembelian_awal` `pa` 
     WHERE (`pa`.`id_bahan` = `bm`.`id`)) AS `total_beli`,
    COALESCE((
        SELECT SUM(`pa`.`berat_awal`) 
        FROM `bb_pembelian_awal` `pa` 
        WHERE `pa`.`id_bahan` = `bm`.`id` AND `pa`.`status` = 'selesai_siap_jual'
    ), 0)
    +
    COALESCE((
        SELECT SUM(`pd`.`berat_masuk`) 
        FROM `bb_proses_detail` `pd` 
        JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id` 
        JOIN `bb_pembelian_awal` `pa` ON `pd`.`id_pembelian` = `pa`.`id`
        WHERE `pa`.`id_bahan` = `bm`.`id` 
          AND `pd`.`status` != 'batal'
          AND COALESCE(`pd`.`metode_produksi`, 'tertimbang') = 'tertimbang'
          AND (`pa`.`status` IS NULL OR `pa`.`status` != 'selesai_siap_jual')
          AND `pm`.`urutan_tahap` = (
              SELECT MIN(`urutan_tahap`) 
              FROM `bb_proses_master` 
              WHERE `id_bahan` = `bm`.`id`
          )
    ), 0) AS `total_proses`,
    (SELECT COALESCE(SUM(`pa`.`berat_awal`), 0) 
     FROM `bb_pembelian_awal` `pa` 
     WHERE `pa`.`id_bahan` = `bm`.`id` 
       AND (`pa`.`status` IS NULL OR `pa`.`status` != 'selesai_siap_jual')
    )
    -
    COALESCE((
        SELECT SUM(`pd`.`berat_masuk`) 
        FROM `bb_proses_detail` `pd` 
        JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id` 
        JOIN `bb_pembelian_awal` `pa` ON `pd`.`id_pembelian` = `pa`.`id`
        WHERE `pa`.`id_bahan` = `bm`.`id` 
          AND `pd`.`status` != 'batal'
          AND COALESCE(`pd`.`metode_produksi`, 'tertimbang') = 'tertimbang'
          AND (`pa`.`status` IS NULL OR `pa`.`status` != 'selesai_siap_jual')
          AND `pm`.`urutan_tahap` = (
              SELECT MIN(`urutan_tahap`) 
              FROM `bb_proses_master` 
              WHERE `id_bahan` = `bm`.`id`
          )
    ), 0) AS `stok_tersedia` 
FROM `bb_bahan_master` `bm`;
