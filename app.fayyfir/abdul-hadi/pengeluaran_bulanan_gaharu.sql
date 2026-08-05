-- ================================================================
-- MIGRATION: Fitur Pengeluaran Bulanan Gaharu
-- Database : yossinor_ahadi
-- Tanggal  : 2026-08-04
-- ================================================================
-- Jalankan file ini SATU KALI di database yossinor_ahadi
-- (via phpMyAdmin atau terminal)
-- ================================================================

CREATE TABLE IF NOT EXISTS `gaharu_monthly_expenses` (
  `id`               INT            NOT NULL AUTO_INCREMENT,
  `bulan`            VARCHAR(7)     NOT NULL COMMENT 'Format: YYYY-MM, contoh: 2026-08',
  `nama_pengeluaran` VARCHAR(150)   NOT NULL,
  `jenis`            ENUM('fix','tidak_fix') NOT NULL DEFAULT 'tidak_fix'
                     COMMENT 'fix = biaya tetap (mis. gaji), tidak_fix = biaya variabel',
  `jumlah`           DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `keterangan`       TEXT           NULL,
  `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bulan` (`bulan`),
  KEY `idx_jenis` (`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
