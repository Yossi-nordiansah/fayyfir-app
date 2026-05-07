-- SQL untuk mengubah tipe data kolom transaction_date dari DATE menjadi DATETIME
-- Jalankan pada database yossinor_db dan yossinor_ahadi

ALTER TABLE `transactions` MODIFY COLUMN `transaction_date` DATETIME NOT NULL;

ALTER TABLE `containers` MODIFY COLUMN `fill_date` DATETIME NOT NULL;
