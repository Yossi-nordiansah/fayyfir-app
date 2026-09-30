-- ==============================================================================
-- FILE AUDIT & REFERENSI QUERY FIFO BIAYA PRODUKSI (BELUM TERJUAL)
-- Aplikasi Fayyfir App - Abdul Hadi
-- ==============================================================================
-- 
-- STATUS STRUKTUR DATABASE:
-- [TIDAK PERLU MIGRASI / ALTER TABLE (ZERO DB MIGRATION)]
-- Perhitungan FIFO (First-In, First-Out) diimplementasikan secara dinamis langsung
-- dari data riwayat batch produksi (`productions`) dan penjualan (`selling_products`).
-- Kolom dan tabel yang ada sudah lengkap dan tidak perlu diubah.
--
-- File SQL ini disediakan sebagai referensi bagi Administrator / Developer untuk:
-- 1. Mengaudit antrean batch produksi dan HPP per gram per batch.
-- 2. Mengaudit total barang yang telah terjual per produk.
-- 3. Menjalankan query verifikasi manual langsung di phpMyAdmin / HeidiSQL / MySQL Workbench.
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. QUERY AUDIT BATCH PRODUKSI & HPP PER UNIT / GRAM
-- Menampilkan semua batch produksi selesai/terhitung, diurutkan FIFO (tanggal terlama lebih dulu)
-- ------------------------------------------------------------------------------
SELECT 
    p.id AS batch_id,
    p.production_number AS no_batch,
    COALESCE(p.production_date, DATE(p.created_at)) AS tgl_produksi,
    ps.id AS product_id,
    ps.product_name AS nama_produk,
    COALESCE(u.symbol, 'g') AS satuan,
    p.total_output AS hasil_output,
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
ORDER BY ps.id ASC, COALESCE(p.production_date, DATE(p.created_at)) ASC, p.id ASC;

-- ------------------------------------------------------------------------------
-- 2. QUERY AUDIT AKUMULASI PENJUALAN PER PRODUK
-- Menghitung total kuantitas (gram) produk yang sudah terjual
-- ------------------------------------------------------------------------------
SELECT 
    ps.id AS product_id,
    ps.product_name AS nama_produk,
    COALESCE(u.symbol, 'g') AS satuan,
    COALESCE(SUM(s.qty), 0) AS total_kuantitas_terjual,
    COALESCE(SUM(s.total_selling), 0) AS total_omzet_penjualan
FROM product_stocks ps
LEFT JOIN units u ON ps.unit_id = u.id
LEFT JOIN selling_products s ON ps.id = s.product_id
GROUP BY ps.id, ps.product_name, u.symbol
ORDER BY ps.product_name ASC;

-- ------------------------------------------------------------------------------
-- 3. QUERY REKAP STOK GUDANG & PERBANDINGAN OUTPUT VS TERJUAL
-- ------------------------------------------------------------------------------
SELECT 
    ps.id AS product_id,
    ps.product_name AS nama_produk,
    COALESCE(u.symbol, 'g') AS satuan,
    COALESCE(p.total_output, 0) AS total_output_produksi,
    COALESCE(sp.total_sold, 0) AS total_terjual,
    GREATEST(0, COALESCE(p.total_output, 0) - COALESCE(sp.total_sold, 0)) AS sisa_stok_gudang
FROM product_stocks ps
LEFT JOIN (
    SELECT product_id, SUM(total_output) AS total_output
    FROM productions
    WHERE status != 'Proses'
    GROUP BY product_id
) p ON p.product_id = ps.id
LEFT JOIN (
    SELECT product_id, SUM(qty) AS total_sold
    FROM selling_products
    GROUP BY product_id
) sp ON sp.product_id = ps.id
LEFT JOIN units u ON ps.unit_id = u.id
ORDER BY ps.product_name ASC;

-- ------------------------------------------------------------------------------
-- 4. CONTOH QUERY AUDIT TRANSAKSI PENJUALAN LUNAS (UNTUK ARUS KAS BERSIH)
-- ------------------------------------------------------------------------------
SELECT 
    s.id,
    s.selling_date,
    s.invoice_number,
    b.name AS nama_buyer,
    ps.product_name,
    s.qty,
    s.price,
    s.total_selling,
    s.status
FROM selling_products s
JOIN product_stocks ps ON s.product_id = ps.id
LEFT JOIN buyer_products b ON s.buyer_id = b.id
WHERE s.status = 'Lunas' AND s.invoice_number IS NOT NULL AND s.invoice_number != ''
ORDER BY s.selling_date DESC;
