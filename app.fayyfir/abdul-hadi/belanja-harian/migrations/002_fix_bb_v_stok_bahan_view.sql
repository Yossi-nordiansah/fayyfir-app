-- ============================================================
-- Migration: Fix View bb_v_stok_bahan
-- File      : 002_fix_bb_v_stok_bahan_view.sql
-- Tanggal   : 2026-08-07
-- Dibuat    : Antigravity AI
--
-- MASALAH SEBELUMNYA:
--   1. WHERE bm.deleted_at IS NULL
--      -> kolom `deleted_at` TIDAK ADA di tabel bb_bahan_master
--      -> menyebabkan view error, tidak ada data tampil
--
--   2. WHERE pm.id_bahan = bm.id
--      -> kolom `id_bahan` TIDAK ADA di tabel bb_proses_master
--      -> query subquery total_proses selalu gagal / error
--
-- SOLUSI:
--   - Hapus filter deleted_at
--   - Hubungkan bb_proses_master ke bb_bahan_master
--     melalui bb_pembelian_awal (JOIN pa ON pd.id_pembelian = pa.id)
-- ============================================================

DROP VIEW IF EXISTS `bb_v_stok_bahan`;

CREATE VIEW `bb_v_stok_bahan` AS
SELECT
    bm.id           AS id_bahan,
    bm.nama_bahan   AS nama_bahan,
    bm.satuan       AS satuan,

    -- Total berat yang sudah dibeli dari semua batch
    COALESCE((
        SELECT SUM(pa.berat_awal)
        FROM bb_pembelian_awal pa
        WHERE pa.id_bahan = bm.id
    ), 0) AS total_beli,

    -- Total berat yang sudah masuk ke proses tahap pertama
    -- Dihubungkan lewat bb_pembelian_awal karena bb_proses_master
    -- tidak memiliki kolom id_bahan
    COALESCE((
        SELECT SUM(pd.berat_masuk)
        FROM bb_proses_detail pd
        JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
        WHERE pa.id_bahan = bm.id
          AND pm.urutan_tahap = (
              SELECT MIN(urutan_tahap)
              FROM bb_proses_master
          )
    ), 0) AS total_proses,

    -- Stok tersedia = total beli - total yang sudah masuk proses tahap 1
    COALESCE((
        SELECT SUM(pa.berat_awal)
        FROM bb_pembelian_awal pa
        WHERE pa.id_bahan = bm.id
    ), 0)
    -
    COALESCE((
        SELECT SUM(pd.berat_masuk)
        FROM bb_proses_detail pd
        JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
        JOIN bb_pembelian_awal pa ON pd.id_pembelian = pa.id
        WHERE pa.id_bahan = bm.id
          AND pm.urutan_tahap = (
              SELECT MIN(urutan_tahap)
              FROM bb_proses_master
          )
    ), 0) AS stok_tersedia

FROM bb_bahan_master bm
ORDER BY bm.nama_bahan ASC;
