-- ==============================================================================
-- FILE AUDIT & REFERENSI QUERY SQL: LABA RUGI KAYU GAHARU (BPP BERBASIS FIFO)
-- Modul Abdul Hadi - Fayyfir App
-- ==============================================================================
--
-- CATATAN MIGRASI DATABASE:
-- [STATUS: ZERO BREAKING DB MIGRATION]
-- Perhitungan BPP berbasis FIFO diimplementasikan secara dinamis melalui antrean
-- batch produksi (productions) dan transaksi penjualan (selling_products).
-- Tidak ada perubahan atau ALTER kolom tabel yang merusak.
-- 
-- Query safeguard ini memastikan tabel `gaharu_monthly_expenses` siap digunakan
-- jika di server production belum dijalankan sebelumnya.
-- ==============================================================================

-- 1. SAFEGUARD: TABEL PENGELUARAN BULANAN GAHARU
CREATE TABLE IF NOT EXISTS `gaharu_monthly_expenses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `bulan` VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM contoh: 2026-03',
  `nama_pengeluaran` VARCHAR(255) NOT NULL,
  `jenis` ENUM('fix', 'tidak_fix') NOT NULL DEFAULT 'tidak_fix',
  `jumlah` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bulan` (`bulan`),
  KEY `idx_jenis` (`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. QUERY AUDIT BATCH PRODUKSI & HPP PER UNIT UNTUK ALOKASI FIFO
-- ------------------------------------------------------------------------------
SELECT 
    p.id AS batch_id,
    p.production_number AS no_batch,
    COALESCE(p.production_date, DATE(p.created_at)) AS tgl_produksi,
    ps.product_name AS nama_produk,
    COALESCE(u.symbol, 'g') AS satuan,
    p.total_output AS total_output_batch,
    (COALESCE(p.total_pro_materials, 0) + COALESCE(p.total_pro_expenses, 0)) AS total_biaya_batch,
    CASE 
        WHEN COALESCE(p.total_output, 0) > 0 THEN 
            ROUND((COALESCE(p.total_pro_materials, 0) + COALESCE(p.total_pro_expenses, 0)) / p.total_output, 2)
        ELSE 0 
    END AS hpp_per_satuan,
    p.status AS status_batch
FROM productions p
JOIN product_stocks ps ON p.product_id = ps.id
LEFT JOIN units u ON ps.unit_id = u.id
WHERE p.status != 'Proses' AND COALESCE(p.total_output, 0) > 0
ORDER BY COALESCE(p.production_date, DATE(p.created_at)) ASC, p.id ASC;

-- ------------------------------------------------------------------------------
-- 3. QUERY AUDIT REKAP PENJUALAN PER BULAN (OMZET PENDAPATAN)
-- ------------------------------------------------------------------------------
SELECT 
    DATE_FORMAT(s.selling_date, '%Y-%m') AS periode_bulan,
    COUNT(DISTINCT s.invoice_number) AS total_invoice,
    SUM(s.qty) AS total_qty_terjual,
    SUM(s.total_selling) AS total_pendapatan_kotor
FROM selling_products s
GROUP BY DATE_FORMAT(s.selling_date, '%Y-%m')
ORDER BY periode_bulan DESC;

-- ------------------------------------------------------------------------------
-- 4. QUERY AUDIT PENGELUARAN BULANAN GAHARU (BEBAN OPERASIONAL)
-- ------------------------------------------------------------------------------
SELECT 
    bulan AS periode_bulan,
    SUM(CASE WHEN jenis = 'fix' THEN jumlah ELSE 0 END) AS total_beban_tetap,
    SUM(CASE WHEN jenis = 'tidak_fix' THEN jumlah ELSE 0 END) AS total_beban_variatif,
    SUM(jumlah) AS total_beban_operasional
FROM gaharu_monthly_expenses
GROUP BY bulan
ORDER BY bulan DESC;
