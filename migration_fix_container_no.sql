-- =====================================================================
-- PERBAIKAN TIPE DATA KOLOM CONTAINER_NO PADA TABEL INVOICE_INFO
-- Penyebab Container No bernilai 0:
-- Di database Fayyfir 2, kolom `container_no` sebelumnya bertipe TINYINT,
-- sehingga teks nomor kontainer (seperti MRTU9614355, SBS-09088) otomatis
-- terkonversi menjadi angka 0.
--
-- Jalankan query ini di database Fayyfir 2 (atau Fayyfir 1):
-- =====================================================================

-- 1. Ubah tipe kolom container_no menjadi VARCHAR(255)
ALTER TABLE `invoice_info` MODIFY COLUMN `container_no` VARCHAR(255) NULL;

-- 2. Perbaiki data invoice lama yang saat ini terlanjur bernilai '0'
--    dengan mengambil nomor kontainer dari tabel containers
UPDATE `invoice_info` ii
JOIN `containers` c ON ii.container_id = c.id
SET ii.container_no = c.container_number
WHERE ii.container_no = '0' OR ii.container_no IS NULL OR ii.container_no = '';
