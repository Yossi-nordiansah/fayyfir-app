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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_sender_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Masukkan data awal pengirim beserta kode invoice-nya
INSERT INTO `invoice_senders` (`name`, `code`) VALUES
('Abdulaziz Yahya Sagheer Darwesh', 'AYS'),
('PT ALSHARIF GROUP INDONESIA', 'AGI'),
('PT GLOBAL GATEWAY TRADING', 'GGT'),
('Anggam Abdullah', 'AAM')
ON DUPLICATE KEY UPDATE `code` = VALUES(`code`);

-- 5. Pembersihan teks 'ANumLainnya' / 'Lainnya' jika ada data error sebelumnya
UPDATE `invoice_info` SET `account_number` = '' WHERE `account_number` = 'ANumLainnya';
UPDATE `invoice_info` SET `account_name` = '' WHERE `account_name` = 'ANLainnya';
UPDATE `invoice_info` SET `bank_name` = '' WHERE `bank_name` = 'BNLainnya';
UPDATE `invoice_info` SET `address` = '' WHERE `address` = 'ADLainnya';
UPDATE `invoice_info` SET `invoice_to` = '' WHERE `invoice_to` = 'ITLainnya';

