-- ================================================================
-- MIGRATION: Fitur Pencatatan Penyusutan Bobot (Timbang Ulang)
-- Tabel    : product_shrinkage_logs
-- Tanggal  : 2026-09-16
-- ================================================================

CREATE TABLE IF NOT EXISTS `product_shrinkage_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `ref_no` VARCHAR(100) NOT NULL,
  `adjustment_date` DATETIME NOT NULL,
  `qty_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `qty_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `unsold_cost_at_time` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `hpp_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `hpp_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_adjustment_date` (`adjustment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
