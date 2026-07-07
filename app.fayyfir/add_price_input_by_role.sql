-- Kueri SQL untuk memperbarui database di hosting.
-- Jalankan kueri ini pada database hosting Anda (di phpMyAdmin atau terminal database).

-- 1. Menambahkan kolom weight_input_by_role jika belum ada (dari perubahan sebelumnya)
ALTER TABLE transactions 
ADD COLUMN weight_input_by_role INT NULL AFTER created_by;

-- 2. Menambahkan kolom price_input_by_role untuk melacak penginput harga per kg
ALTER TABLE transactions 
ADD COLUMN price_input_by_role INT NULL AFTER weight_input_by_role;
