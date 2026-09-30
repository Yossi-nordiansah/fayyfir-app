-- ============================================================
-- Tabel: invoice_payments
-- Menyimpan setiap angsuran/pembayaran bertahap per invoice
-- DP awal tetap di selling_products.dp (tidak diubah)
-- Angsuran berikutnya masuk ke tabel ini
-- 
-- total_dibayar = selling_products.dp + SUM(invoice_payments.jumlah)
-- sisa_tagihan  = total_selling - total_dibayar
-- ============================================================

CREATE TABLE IF NOT EXISTS `invoice_payments` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(100) NOT NULL,
  `payment_date`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jumlah`         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `keterangan`     TEXT NULL,
  `created_by`     INT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_payment_date`   (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
