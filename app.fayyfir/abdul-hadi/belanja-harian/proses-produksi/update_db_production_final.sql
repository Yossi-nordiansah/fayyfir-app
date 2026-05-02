-- Pembaruan Skema Database Fayyfir (Abdul Hadi)
-- Tanggal: 2026-04-27

USE alsz2632_ahadi;

-- 1. Menambahkan status 'belum_lunas' pada manajemen pembayaran pembelian awal
-- Digunakan untuk menandai batch yang total modalnya meningkat setelah diedit namun pembayaran belum disesuaikan.
ALTER TABLE bb_pembelian_awal 
MODIFY COLUMN status_pembayaran ENUM('belum_dibayar', 'dp', 'belum_lunas', 'lunas') DEFAULT 'belum_dibayar';

-- 2. Mengubah id_proses_master menjadi NULL pada detail proses produksi
-- Hal ini dilakukan agar sistem dapat menyimpan data "Persiapan" (tahap 0) sebelum pemrosesan fisik dimulai.
ALTER TABLE bb_proses_detail 
MODIFY COLUMN id_proses_master INT NULL;

-- Catatan:
-- Pastikan untuk menjalankan perintah ini pada database production agar fitur status 'Belum Lunas' 
-- dan alur pendaftaran produksi baru (Persiapan) berjalan dengan lancar.
