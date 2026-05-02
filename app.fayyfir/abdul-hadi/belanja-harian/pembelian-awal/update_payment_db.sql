-- Update database untuk sistem pembayaran pembelian awal
ALTER TABLE bb_pembelian_awal 
ADD COLUMN status_pembayaran ENUM('belum_dibayar', 'dp', 'lunas') DEFAULT 'belum_dibayar' AFTER status,
ADD COLUMN nominal_bayar DECIMAL(14,2) DEFAULT 0 AFTER status_pembayaran;
