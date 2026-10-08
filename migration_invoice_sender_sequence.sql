-- =====================================================================
-- Migration: Penambahan Kolom last_sequence pada Tabel invoice_senders
-- Database: MySQL 8.0+ / MariaDB
-- File ini aman di-import berulang kali (Idempotent)
-- =====================================================================

SET @dbname = DATABASE();
SET @tablename = "invoice_senders";
SET @columnname = "last_sequence";

-- 1. Tambahkan kolom last_sequence jika belum ada
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `last_sequence` INT NOT NULL DEFAULT 0 AFTER `code`;"
));
PREPARE alterColLastSeq FROM @preparedStatement;
EXECUTE alterColLastSeq;
DEALLOCATE PREPARE alterColLastSeq;

-- 2. Inisialisasi nomor urut terakhir (last_sequence) yang sudah terpakai
UPDATE `invoice_senders` SET `last_sequence` = 158 WHERE TRIM(`name`) = 'Abdulaziz Yahya Sagheer Darwesh' AND `last_sequence` = 0;
UPDATE `invoice_senders` SET `last_sequence` = 182 WHERE TRIM(`name`) = 'PT ALSHARIF GROUP INDONESIA' AND `last_sequence` = 0;
UPDATE `invoice_senders` SET `last_sequence` = 13 WHERE TRIM(`name`) = 'PT GLOBAL GATEWAY TRADING' AND `last_sequence` = 0;
UPDATE `invoice_senders` SET `last_sequence` = 32 WHERE TRIM(`name`) = 'Anggam Abdullah' AND `last_sequence` = 0;
UPDATE `invoice_senders` SET `last_sequence` = 3 WHERE TRIM(`name`) = 'PT ALANOOD INTERNATIONAL TRADING' AND `last_sequence` = 0;
