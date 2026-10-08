-- =====================================================================
-- RANGKUMAN MIGRASI LENGKAP INVOICE & PENGIRIM (FROM)
-- Mencakup:
-- 1. Tabel invoice_templates (Template Invoice)
-- 2. Kolom template_id pada tabel invoice_info
-- 3. Tabel invoice_senders (Daftar Pengirim, Kode Invoice, No Urut Terakhir, & Info Rekening)
-- 4. Inisialisasi Data Pengirim (Payment Info dikosongkan agar diisi via sistem)
-- 5. Pembersihan Nilai Default Error pada invoice_info
--
-- Database : MySQL 5.7+ / 8.0+ / MariaDB
-- Catatan  : File ini aman di-eksekusi berulang kali (Idempotent)
-- =====================================================================

-- -------------------------------------------------------------
-- 1. Buat Tabel invoice_templates jika belum ada
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NULL DEFAULT '',
  `file_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pastikan kolom code bersifat nullable/opsional
ALTER TABLE `invoice_templates` MODIFY COLUMN `code` VARCHAR(50) NULL DEFAULT '';


-- -------------------------------------------------------------
-- 2. Tambahkan kolom template_id pada tabel invoice_info jika belum ada
-- -------------------------------------------------------------
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

-- Pastikan kolom container_no bertipe VARCHAR(255) (bukan TINYINT)
ALTER TABLE `invoice_info` MODIFY COLUMN `container_no` VARCHAR(255) NULL;


-- -------------------------------------------------------------
-- 3. Buat Tabel invoice_senders jika belum ada
-- -------------------------------------------------------------
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


-- -------------------------------------------------------------
-- 4. Pastikan kolom last_sequence & payment info ada pada invoice_senders
--    (Jika tabel invoice_senders sudah pernah dibuat sebelumnya)
-- -------------------------------------------------------------
-- Kolom last_sequence
SET @col_last_seq = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'last_sequence') > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `last_sequence` INT NOT NULL DEFAULT 0 AFTER `code`;"
));
PREPARE alterColsLastSeq FROM @col_last_seq;
EXECUTE alterColsLastSeq;
DEALLOCATE PREPARE alterColsLastSeq;

-- Kolom bank_name
SET @col_bank = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'bank_name') > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT '' AFTER `last_sequence`;"
));
PREPARE alterColBank FROM @col_bank;
EXECUTE alterColBank;
DEALLOCATE PREPARE alterColBank;

-- Kolom account_name
SET @col_acc_name = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'account_name') > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `account_name` VARCHAR(100) NULL DEFAULT '' AFTER `bank_name`;"
));
PREPARE alterColAccName FROM @col_acc_name;
EXECUTE alterColAccName;
DEALLOCATE PREPARE alterColAccName;

-- Kolom account_number
SET @col_acc_num = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoice_senders' AND COLUMN_NAME = 'account_number') > 0,
  "SELECT 1",
  "ALTER TABLE `invoice_senders` ADD COLUMN `account_number` VARCHAR(100) NULL DEFAULT '' AFTER `account_name`;"
));
PREPARE alterColAccNum FROM @col_acc_num;
EXECUTE alterColAccNum;
DEALLOCATE PREPARE alterColAccNum;


-- -------------------------------------------------------------
-- 5. Masukkan Data Pengirim Awal (Payment Info Dikosongkan)
-- -------------------------------------------------------------
INSERT INTO `invoice_senders` (`name`, `code`, `last_sequence`, `bank_name`, `account_name`, `account_number`) VALUES
('Abdulaziz Yahya Sagheer Darwesh', 'AYS', 158, '', '', ''),
('PT ALSHARIF GROUP INDONESIA', 'AGI', 182, '', '', ''),
('PT GLOBAL GATEWAY TRADING', 'GGT', 13, '', '', ''),
('Anggam Abdullah', 'AAM', 32, '', '', ''),
('PT ALANOOD INTERNATIONAL TRADING', 'AIT', 3, '', '', '')
ON DUPLICATE KEY UPDATE 
  `code` = VALUES(`code`),
  `last_sequence` = IF(`last_sequence` = 0, VALUES(`last_sequence`), `last_sequence`);


-- -------------------------------------------------------------
-- 6. Bersihkan data label error lama pada invoice_info (jika ada)
-- -------------------------------------------------------------
UPDATE `invoice_info` SET `account_number` = '' WHERE `account_number` = 'ANumLainnya';
UPDATE `invoice_info` SET `account_name` = '' WHERE `account_name` = 'ANLainnya';
UPDATE `invoice_info` SET `bank_name` = '' WHERE `bank_name` = 'BNLainnya';
UPDATE `invoice_info` SET `address` = '' WHERE `address` = 'ADLainnya';
UPDATE `invoice_info` SET `invoice_to` = '' WHERE `invoice_to` = 'ITLainnya';
