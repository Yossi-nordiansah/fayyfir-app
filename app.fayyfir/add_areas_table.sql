-- ====================================================
-- Migration: Tambah Tabel `areas`
-- Berlaku untuk database: yossinor_db & yossinor_ahadi
-- Tanggal: 2026-07-29
-- ====================================================

CREATE TABLE IF NOT EXISTS `areas` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `region_name` VARCHAR(150) NOT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_region_name` (`region_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrasi data dari tabel users
INSERT IGNORE INTO `areas` (`region_name`)
SELECT DISTINCT `region_name`
FROM `users`
WHERE `region_name` IS NOT NULL AND `region_name` <> '';

-- Migrasi dari tabel containers
INSERT IGNORE INTO `areas` (`region_name`)
SELECT DISTINCT `region_name`
FROM `containers`
WHERE `region_name` IS NOT NULL AND `region_name` <> '';
