-- ============================================================
-- Migration: Allow NULL for id_proses_master in bb_proses_detail
-- Database  : yossinor_ahadi
-- Created   : 2026-05-02
-- Notes     : Pada tahap persiapan (tahap_ke = 0), id_proses_master
--             bernilai NULL. Query ini mengubah struktur tabel di
--             database lokal agar sinkron dengan struktur di hosting.
-- ============================================================

ALTER TABLE `bb_proses_detail` MODIFY COLUMN `id_proses_master` INT NULL DEFAULT NULL;
