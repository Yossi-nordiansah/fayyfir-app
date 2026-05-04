-- ============================================================
-- Migration: Create/Update View bb_v_stok_bahan
-- Database  : yossinor_ahadi
-- Created   : 2026-05-02
-- Notes     : View ini menghitung stok tersedia berdasarkan
--             total pembelian dikurangi total bahan yang masuk
--             ke proses produksi tahap pertama.
--
-- PERBEDAAN LOKAL vs HOSTING:
--   - Lokal : kolom `status` TIDAK ADA di bb_proses_detail
--             → filter `pd.status = 'aktif'` DIHAPUS
--   - Hosting: kolom `status` ADA → gunakan file SQL versi hosting
-- ============================================================

-- Versi LOKAL (tanpa filter pd.status):
CREATE OR REPLACE VIEW `bb_v_stok_bahan` AS
SELECT
    `bm`.`id`          AS `id_bahan`,
    `bm`.`nama_bahan`  AS `nama_bahan`,
    `bm`.`satuan`      AS `satuan`,

    -- Total berat yang sudah dibeli
    (
        SELECT COALESCE(SUM(`pa`.`berat_awal`), 0)
        FROM `bb_pembelian_awal` `pa`
        WHERE `pa`.`id_bahan` = `bm`.`id`
    ) AS `total_beli`,

    -- Total berat yang sudah masuk ke proses tahap pertama
    COALESCE(
        (
            SELECT SUM(`pd`.`berat_masuk`)
            FROM `bb_proses_detail` `pd`
            JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id`
            WHERE `pm`.`id_bahan` = `bm`.`id`
              AND `pm`.`urutan_tahap` = (
                  SELECT MIN(`urutan_tahap`)
                  FROM `bb_proses_master`
                  WHERE `id_bahan` = `bm`.`id`
              )
        )
    , 0) AS `total_proses`,

    -- Stok tersedia = total beli - total proses
    (
        (
            SELECT COALESCE(SUM(`pa`.`berat_awal`), 0)
            FROM `bb_pembelian_awal` `pa`
            WHERE `pa`.`id_bahan` = `bm`.`id`
        ) -
        COALESCE(
            (
                SELECT SUM(`pd`.`berat_masuk`)
                FROM `bb_proses_detail` `pd`
                JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id`
                WHERE `pm`.`id_bahan` = `bm`.`id`
                  AND `pm`.`urutan_tahap` = (
                      SELECT MIN(`urutan_tahap`)
                      FROM `bb_proses_master`
                      WHERE `id_bahan` = `bm`.`id`
                  )
            )
        , 0)
    ) AS `stok_tersedia`

FROM `bb_bahan_master` `bm`
WHERE `bm`.`deleted_at` IS NULL;


-- ============================================================
-- Versi HOSTING (dengan filter pd.status = 'aktif'):
-- Jalankan ini di hosting, bukan di lokal!
-- ============================================================
/*
CREATE OR REPLACE VIEW `bb_v_stok_bahan` AS
SELECT
    `bm`.`id`          AS `id_bahan`,
    `bm`.`nama_bahan`  AS `nama_bahan`,
    `bm`.`satuan`      AS `satuan`,
    (
        SELECT COALESCE(SUM(`pa`.`berat_awal`), 0)
        FROM `bb_pembelian_awal` `pa`
        WHERE `pa`.`id_bahan` = `bm`.`id`
    ) AS `total_beli`,
    COALESCE(
        (
            SELECT SUM(`pd`.`berat_masuk`)
            FROM `bb_proses_detail` `pd`
            JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id`
            WHERE `pm`.`id_bahan` = `bm`.`id`
              AND `pd`.`status` = 'aktif'
              AND `pm`.`urutan_tahap` = (
                  SELECT MIN(`urutan_tahap`)
                  FROM `bb_proses_master`
                  WHERE `id_bahan` = `bm`.`id`
              )
        )
    , 0) AS `total_proses`,
    (
        (
            SELECT COALESCE(SUM(`pa`.`berat_awal`), 0)
            FROM `bb_pembelian_awal` `pa`
            WHERE `pa`.`id_bahan` = `bm`.`id`
        ) -
        COALESCE(
            (
                SELECT SUM(`pd`.`berat_masuk`)
                FROM `bb_proses_detail` `pd`
                JOIN `bb_proses_master` `pm` ON `pd`.`id_proses_master` = `pm`.`id`
                WHERE `pm`.`id_bahan` = `bm`.`id`
                  AND `pd`.`status` = 'aktif'
                  AND `pm`.`urutan_tahap` = (
                      SELECT MIN(`urutan_tahap`)
                      FROM `bb_proses_master`
                      WHERE `id_bahan` = `bm`.`id`
                  )
            )
        , 0)
    ) AS `stok_tersedia`
FROM `bb_bahan_master` `bm`
WHERE `bm`.`deleted_at` IS NULL;
*/
