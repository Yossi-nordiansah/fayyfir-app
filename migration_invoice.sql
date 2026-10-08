-- =====================================================================
-- Migration Lengkap Template Invoice & Pengirim (FROM)
-- Database: MySQL 8.0+ / MariaDB
-- File ini aman di-import berulang kali (Idempotent)
-- =====================================================================

-- 1. Buat Tabel invoice_templates jika belum ada
CREATE TABLE IF NOT EXISTS `invoice_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NULL DEFAULT '',
  `file_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modifikasi kolom code pada invoice_templates agar menjadi opsional (nullable)
ALTER TABLE `invoice_templates` MODIFY COLUMN `code` VARCHAR(50) NULL DEFAULT '';

-- 2. Tambahkan kolom template_id pada tabel invoice_info jika belum ada
SET @dbname = DATABASE();
SET @tablename = "invoice_info";
SET @columnname = "template_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_info` ADD COLUMN `template_id` INT NULL AFTER `container_id`, ADD INDEX `idx_template_id` (`template_id`);"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Buat Tabel invoice_senders untuk menyimpan pengirim (FROM) dan kodenya
CREATE TABLE IF NOT EXISTS `invoice_senders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `last_sequence` INT NOT NULL DEFAULT 0,
  `bank_name` VARCHAR(100) NULL DEFAULT '',
  `account_name` VARCHAR(100) NULL DEFAULT '',
  `account_number` VARCHAR(100) NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_sender_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan kolom last_sequence dan payment info pada invoice_senders jika tabel sudah ada sebelumnya
SET @col_last_seq = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'last_sequence') > 0, "SELECT 1", "ALTER TABLE `invoice_senders` ADD COLUMN `last_sequence` INT NOT NULL DEFAULT 0 AFTER `code`;"));
PREPARE alterColsLastSeq FROM @col_last_seq;
EXECUTE alterColsLastSeq;
DEALLOCATE PREPARE alterColsLastSeq;

SET @col_bank = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'bank_name') > 0, "SELECT 1", "ALTER TABLE `invoice_senders` ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT '' AFTER `last_sequence`, ADD COLUMN `account_name` VARCHAR(100) NULL DEFAULT '' AFTER `bank_name`, ADD COLUMN `account_number` VARCHAR(100) NULL DEFAULT '' AFTER `account_name`;"));
PREPARE alterColsSender FROM @col_bank;
EXECUTE alterColsSender;
DEALLOCATE PREPARE alterColsSender;

-- 4. Masukkan data awal pengirim beserta kode invoice & rekening
INSERT INTO `invoice_senders` (`name`, `code`, `last_sequence`, `bank_name`, `account_name`, `account_number`) VALUES
('Abdulaziz Yahya Sagheer Darwesh', 'AYS', 158, 'BRI', 'Abdulaziz Yahya Sagheer Darwesh', '408501000036562'),
('PT ALSHARIF GROUP INDONESIA', 'AGI', 182, 'BRI', 'PT ALSHARIF GROUP INDONESIA', '016701002496566'),
('PT GLOBAL GATEWAY TRADING', 'GGT', 13, 'BRI', 'GLOBAL GATEWAY TRADING', '010501222333306'),
('Anggam Abdullah', 'AAM', 32, 'BRI', 'Anggam Abdullah Muhammad Al sharif', '775301000133568'),
('PT ALANOOD INTERNATIONAL TRADING', 'AIT', 3, 'BRI', 'PT ALANOOD INTERNATIONAL TRADING', '324222312')
ON DUPLICATE KEY UPDATE 
  `code` = VALUES(`code`),
  `last_sequence` = IF(`last_sequence` = 0, VALUES(`last_sequence`), `last_sequence`),
  `bank_name` = IF(`bank_name` = '' OR `bank_name` IS NULL, VALUES(`bank_name`), `bank_name`),
  `account_name` = IF(`account_name` = '' OR `account_name` IS NULL, VALUES(`account_name`), `account_name`),
  `account_number` = IF(`account_number` = '' OR `account_number` IS NULL, VALUES(`account_number`), `account_number`);

-- 5. Pembersihan teks 'ANumLainnya' / 'Lainnya' jika ada data error sebelumnya
UPDATE `invoice_info` SET `account_number` = '' WHERE `account_number` = 'ANumLainnya';
UPDATE `invoice_info` SET `account_name` = '' WHERE `account_name` = 'ANLainnya';
UPDATE `invoice_info` SET `bank_name` = '' WHERE `bank_name` = 'BNLainnya';
UPDATE `invoice_info` SET `address` = '' WHERE `address` = 'ADLainnya';
UPDATE `invoice_info` SET `invoice_to` = '' WHERE `invoice_to` = 'ITLainnya';

