-- ============================================================
-- Migration: Fix View bb_v_stok_bahan Calculation
-- File      : 005_fix_stok_bahan_view.sql
-- Tanggal   : 2026-08-18
-- Dibuat    : Antigravity AI
-- Deskripsi : Memperbaiki kalkulasi stok_tersedia dan total_proses
--             agar akurat & konsisten dengan detail stok mandiri
--             maupun stok gabungan penampungan.
-- ============================================================

CREATE OR REPLACE VIEW `bb_v_stok_bahan` AS
SELECT
    bm.id AS id_bahan,
    bm.nama_bahan AS nama_bahan,
    bm.satuan AS satuan,

    -- 1. Total Seluruh Pembelian Awal
    COALESCE((
        SELECT SUM(pa.berat_awal)
        FROM bb_pembelian_awal pa
        WHERE pa.id_bahan = bm.id
    ), 0) AS total_beli,

    -- 2. Stok Tersedia (Stok Mandiri + Stok Penampungan)
    (
        -- Stok Mandiri (per pembelian yang belum selesai_siap_jual)
        COALESCE((
            SELECT SUM(
                pa.berat_awal 
                - COALESCE(pd_agg.terpakai_prod, 0) 
                - COALESCE(pnd_agg.terpakai_penampungan, 0)
            )
            FROM bb_pembelian_awal pa
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) AS terpakai_prod
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND id_penampungan IS NULL AND status != 'batal'
                GROUP BY id_pembelian
            ) pd_agg ON pd_agg.id_pembelian = pa.id
            LEFT JOIN (
                SELECT id_pembelian, SUM(berat_masuk) AS terpakai_penampungan
                FROM bb_penampungan_detail
                GROUP BY id_pembelian
            ) pnd_agg ON pnd_agg.id_pembelian = pa.id
            WHERE pa.id_bahan = bm.id 
              AND (pa.status IS NULL OR pa.status != 'selesai_siap_jual')
              AND (pa.berat_awal - COALESCE(pd_agg.terpakai_prod, 0) - COALESCE(pnd_agg.terpakai_penampungan, 0)) > 0
        ), 0)
        +
        -- Stok Gabungan (Penampungan)
        COALESCE((
            SELECT SUM(
                COALESCE(pnd_agg.total_masuk, 0) - COALESCE(pd_agg.terpakai_prod, 0)
            )
            FROM bb_penampungan pn
            LEFT JOIN (
                SELECT id_penampungan, SUM(berat_masuk) AS total_masuk
                FROM bb_penampungan_detail
                GROUP BY id_penampungan
            ) pnd_agg ON pnd_agg.id_penampungan = pn.id
            LEFT JOIN (
                SELECT id_penampungan, SUM(berat_masuk) AS terpakai_prod
                FROM bb_proses_detail
                WHERE tahap_ke = 0 AND status != 'batal'
                GROUP BY id_penampungan
            ) pd_agg ON pd_agg.id_penampungan = pn.id
            WHERE pn.id_bahan = bm.id
              AND (COALESCE(pnd_agg.total_masuk, 0) - COALESCE(pd_agg.terpakai_prod, 0)) > 0
        ), 0)
    ) AS stok_tersedia,

    -- 3. Total Berat yang Sudah Masuk Proses Produksi
    (
        COALESCE((
            SELECT SUM(pa.berat_awal)
            FROM bb_pembelian_awal pa
            WHERE pa.id_bahan = bm.id
        ), 0)
        -
        (
            COALESCE((
                SELECT SUM(
                    pa.berat_awal 
                    - COALESCE(pd_agg.terpakai_prod, 0) 
                    - COALESCE(pnd_agg.terpakai_penampungan, 0)
                )
                FROM bb_pembelian_awal pa
                LEFT JOIN (
                    SELECT id_pembelian, SUM(berat_masuk) AS terpakai_prod
                    FROM bb_proses_detail
                    WHERE tahap_ke = 0 AND id_penampungan IS NULL AND status != 'batal'
                    GROUP BY id_pembelian
                ) pd_agg ON pd_agg.id_pembelian = pa.id
                LEFT JOIN (
                    SELECT id_pembelian, SUM(berat_masuk) AS terpakai_penampungan
                    FROM bb_penampungan_detail
                    GROUP BY id_pembelian
                ) pnd_agg ON pnd_agg.id_pembelian = pa.id
                WHERE pa.id_bahan = bm.id 
                  AND (pa.status IS NULL OR pa.status != 'selesai_siap_jual')
                  AND (pa.berat_awal - COALESCE(pd_agg.terpakai_prod, 0) - COALESCE(pnd_agg.terpakai_penampungan, 0)) > 0
            ), 0)
            +
            COALESCE((
                SELECT SUM(
                    COALESCE(pnd_agg.total_masuk, 0) - COALESCE(pd_agg.terpakai_prod, 0)
                )
                FROM bb_penampungan pn
                LEFT JOIN (
                    SELECT id_penampungan, SUM(berat_masuk) AS total_masuk
                    FROM bb_penampungan_detail
                    GROUP BY id_penampungan
                ) pnd_agg ON pnd_agg.id_penampungan = pn.id
                LEFT JOIN (
                    SELECT id_penampungan, SUM(berat_masuk) AS terpakai_prod
                    FROM bb_proses_detail
                    WHERE tahap_ke = 0 AND status != 'batal'
                    GROUP BY id_penampungan
                ) pd_agg ON pd_agg.id_penampungan = pn.id
                WHERE pn.id_bahan = bm.id
                  AND (COALESCE(pnd_agg.total_masuk, 0) - COALESCE(pd_agg.terpakai_prod, 0)) > 0
            ), 0)
        )
    ) AS total_proses

FROM bb_bahan_master bm
ORDER BY bm.nama_bahan ASC;
