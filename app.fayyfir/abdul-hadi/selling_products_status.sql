-- ==============================================================
-- Migrasi Status Pembayaran Transaksi Penjualan
-- Tabel  : selling_products
-- Tujuan : Menambahkan opsi 'Belum Lunas' pada kolom status
--
-- Sebelum : ENUM('DP','Lunas')
-- Sesudah : ENUM('Belum Lunas','DP','Lunas')
--
-- Jalankan di database: yossinor_db   (DB1)
--                   dan yossinor_ahadi (DB2)
-- ==============================================================

ALTER TABLE `selling_products` 
MODIFY COLUMN `status` ENUM('Belum Lunas', 'DP', 'Lunas') NOT NULL DEFAULT 'Belum Lunas';
