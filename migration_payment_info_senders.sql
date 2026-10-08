-- =====================================================================
-- Migration: Penambahan Kolom Payment Info pada Tabel invoice_senders
-- Database: MySQL 8.0+ / MariaDB
-- File ini aman di-import berulang kali (Idempotent)
-- =====================================================================

-- 1. Tambahkan kolom bank_name, account_name, account_number pada invoice_senders jika belum ada
SET @dbname = DATABASE();
SET @tablename = "invoice_senders";

-- bank_name
SET @columnname = "bank_name";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT '' AFTER `code`;"
));
PREPARE alterCol1 FROM @preparedStatement;
EXECUTE alterCol1;
DEALLOCATE PREPARE alterCol1;

-- account_name
SET @columnname = "account_name";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `account_name` VARCHAR(100) NULL DEFAULT '' AFTER `bank_name`;"
));
PREPARE alterCol2 FROM @preparedStatement;
EXECUTE alterCol2;
DEALLOCATE PREPARE alterCol2;

-- account_number
SET @columnname = "account_number";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `account_number` VARCHAR(100) NULL DEFAULT '' AFTER `account_name`;"
));
PREPARE alterCol3 FROM @preparedStatement;
EXECUTE alterCol3;
DEALLOCATE PREPARE alterCol3;

-- 2. Sinkronisasi Data Rekening Default untuk Pengirim
UPDATE `invoice_senders` 
SET `bank_name` = 'BRI', 
    `account_name` = 'Abdulaziz Yahya Sagheer Darwesh', 
    `account_number` = '408501000036562' 
WHERE TRIM(`name`) = 'Abdulaziz Yahya Sagheer Darwesh' AND (`account_number` IS NULL OR `account_number` = '');

UPDATE `invoice_senders` 
SET `bank_name` = 'BRI', 
    `account_name` = 'PT ALSHARIF GROUP INDONESIA', 
    `account_number` = '016701002496566' 
WHERE TRIM(`name`) = 'PT ALSHARIF GROUP INDONESIA' AND (`account_number` IS NULL OR `account_number` = '');

UPDATE `invoice_senders` 
SET `bank_name` = 'BRI', 
    `account_name` = 'GLOBAL GATEWAY TRADING', 
    `account_number` = '010501222333306' 
WHERE TRIM(`name`) = 'PT GLOBAL GATEWAY TRADING' AND (`account_number` IS NULL OR `account_number` = '');

UPDATE `invoice_senders` 
SET `bank_name` = 'BRI', 
    `account_name` = 'Anggam Abdullah Muhammad Al sharif', 
    `account_number` = '775301000133568' 
WHERE TRIM(`name`) = 'Anggam Abdullah' AND (`account_number` IS NULL OR `account_number` = '');

UPDATE `invoice_senders` 
SET `bank_name` = 'BRI', 
    `account_name` = 'PT ALANOOD INTERNATIONAL TRADING', 
    `account_number` = '324222312' 
WHERE TRIM(`name`) = 'PT ALANOOD INTERNATIONAL TRADING' AND (`account_number` IS NULL OR `account_number` = '');

UPDATE `invoice_senders` 
SET `bank_name` = 'BCA', 
    `account_name` = 'PT FAYYFIR SUKSES BERSAMA', 
    `account_number` = '8572882475' 
WHERE TRIM(`name`) = 'PT FAYYFIR SUKSES BERSAMA' AND (`account_number` IS NULL OR `account_number` = '');
