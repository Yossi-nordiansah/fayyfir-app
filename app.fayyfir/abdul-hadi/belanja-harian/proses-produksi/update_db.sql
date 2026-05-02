-- Add status column to bb_proses_detail to support cancellation
ALTER TABLE bb_proses_detail ADD COLUMN status ENUM('aktif', 'batal') DEFAULT 'aktif' AFTER catatan;
