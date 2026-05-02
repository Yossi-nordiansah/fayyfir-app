SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `bb_bahan_master` (
  `id` int(11) NOT NULL,
  `nama_bahan` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_buyer` (
  `id` int(11) NOT NULL,
  `nama_buyer` varchar(100) NOT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_pembelian_awal` (
  `id` int(11) NOT NULL,
  `kode_batch` varchar(30) DEFAULT NULL,
  `tanggal_pembelian` date NOT NULL,
  `id_bahan` int(11) DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `berat_awal` decimal(10,2) DEFAULT NULL,
  `harga_per_kg` decimal(12,2) DEFAULT NULL,
  `total_modal` decimal(14,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'proses',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_pengeluaran` (
  `id` int(11) NOT NULL,
  `id_pembelian` int(11) DEFAULT NULL,
  `id_penjualan` int(11) DEFAULT NULL,
  `deskripsi_exp` varchar(100) DEFAULT NULL,
  `biaya_exp` decimal(12,2) DEFAULT NULL,
  `tanggal_exp` date DEFAULT NULL,
  `catatan_exp` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_penjualan` (
  `id` int(11) NOT NULL,
  `no_invoice` varchar(30) DEFAULT NULL,
  `id_pembelian` int(11) DEFAULT NULL,
  `id_buyer` int(11) DEFAULT NULL,
  `tanggal_jual` date DEFAULT NULL,
  `berat_jual` decimal(10,2) DEFAULT NULL,
  `harga_jual_per_kg` decimal(12,2) DEFAULT NULL,
  `total_penjualan` decimal(14,2) DEFAULT NULL,
  `laba_bersih` decimal(14,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_proses_detail` (
  `id` int(11) NOT NULL,
  `id_pembelian` int(11) NOT NULL,
  `id_proses_master` int(11) NOT NULL,
  `tahap_ke` int(11) NOT NULL,
  `tanggal_proses` date NOT NULL,
  `berat_masuk` decimal(10,2) NOT NULL,
  `berat_keluar` decimal(10,2) NOT NULL,
  `penyusutan` decimal(10,2) GENERATED ALWAYS AS (`berat_masuk` - `berat_keluar`) STORED,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_proses_master` (
  `id` int(11) NOT NULL,
  `nama_proses` varchar(50) NOT NULL,
  `urutan_tahap` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_supplier` (
  `id` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `bb_v_hpp_awal` (
`id` int(11)
,`nama_bahan` varchar(100)
,`nama_supplier` varchar(100)
,`tanggal_pembelian` date
,`berat_awal` decimal(10,2)
,`harga_per_kg` decimal(12,2)
,`total_modal` decimal(14,2)
,`hpp_per_kg` decimal(17,2)
);

CREATE TABLE `bb_v_penyusutan_akumulasi` (
`id_pembelian` int(11)
,`kode_batch` varchar(30)
,`berat_awal` decimal(10,2)
,`berat_akhir` decimal(10,2)
,`total_penyusutan` decimal(11,2)
);

CREATE TABLE `bb_v_penyusutan_per_tahap` (
`id_proses_detail` int(11)
,`id_pembelian` int(11)
,`kode_batch` varchar(30)
,`nama_proses` varchar(50)
,`urutan_tahap` int(11)
,`tanggal_proses` date
,`berat_masuk` decimal(10,2)
,`berat_keluar` decimal(10,2)
,`penyusutan` decimal(10,2)
);

CREATE TABLE `bb_v_stock_akhir_per_bahan` (
`id_bahan` int(11)
,`nama_bahan` varchar(100)
,`total_berat_siap_jual` decimal(32,2)
,`total_terjual` decimal(32,2)
,`stock_final` decimal(33,2)
);

ALTER TABLE `bb_bahan_master`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_buyer`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_pembelian_awal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_batch` (`kode_batch`),
  ADD KEY `id_bahan` (`id_bahan`),
  ADD KEY `id_supplier` (`id_supplier`);

ALTER TABLE `bb_pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bb_pengeluaran_ibfk_1` (`id_penjualan`);

ALTER TABLE `bb_penjualan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_invoice` (`no_invoice`),
  ADD KEY `id_buyer` (`id_buyer`),
  ADD KEY `bb_penjualan_ibfk_1` (`id_pembelian`);

ALTER TABLE `bb_proses_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pembelian` (`id_pembelian`),
  ADD KEY `id_proses_master` (`id_proses_master`);

ALTER TABLE `bb_proses_master`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_supplier`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_bahan_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_buyer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_pembelian_awal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_penjualan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_proses_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_proses_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DROP TABLE IF EXISTS `bb_v_hpp_awal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`alsz2632`@`localhost` SQL SECURITY DEFINER VIEW `bb_v_hpp_awal`  AS SELECT `p`.`id` AS `id`, `b`.`nama_bahan` AS `nama_bahan`, `s`.`nama_supplier` AS `nama_supplier`, `p`.`tanggal_pembelian` AS `tanggal_pembelian`, `p`.`berat_awal` AS `berat_awal`, `p`.`harga_per_kg` AS `harga_per_kg`, `p`.`total_modal` AS `total_modal`, round(`p`.`total_modal` / nullif(`p`.`berat_awal`,0),2) AS `hpp_per_kg` FROM ((`bb_pembelian_awal` `p` join `bb_bahan_master` `b` on(`p`.`id_bahan` = `b`.`id`)) join `bb_supplier` `s` on(`p`.`id_supplier` = `s`.`id`)) ;

DROP TABLE IF EXISTS `bb_v_penyusutan_akumulasi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`alsz2632`@`localhost` SQL SECURITY DEFINER VIEW `bb_v_penyusutan_akumulasi`  AS SELECT `pa`.`id` AS `id_pembelian`, `pa`.`kode_batch` AS `kode_batch`, `pa`.`berat_awal` AS `berat_awal`, (select `pd`.`berat_keluar` from `bb_proses_detail` `pd` where `pd`.`id_pembelian` = `pa`.`id` order by `pd`.`tanggal_proses` desc,`pd`.`id` desc limit 1) AS `berat_akhir`, `pa`.`berat_awal`- (select `pd`.`berat_keluar` from `bb_proses_detail` `pd` where `pd`.`id_pembelian` = `pa`.`id` order by `pd`.`tanggal_proses` desc,`pd`.`id` desc limit 1) AS `total_penyusutan` FROM `bb_pembelian_awal` AS `pa` ;

DROP TABLE IF EXISTS `bb_v_penyusutan_per_tahap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`alsz2632`@`localhost` SQL SECURITY DEFINER VIEW `bb_v_penyusutan_per_tahap`  AS SELECT `pd`.`id` AS `id_proses_detail`, `pd`.`id_pembelian` AS `id_pembelian`, `pa`.`kode_batch` AS `kode_batch`, `pm`.`nama_proses` AS `nama_proses`, `pm`.`urutan_tahap` AS `urutan_tahap`, `pd`.`tanggal_proses` AS `tanggal_proses`, `pd`.`berat_masuk` AS `berat_masuk`, `pd`.`berat_keluar` AS `berat_keluar`, `pd`.`penyusutan` AS `penyusutan` FROM ((`bb_proses_detail` `pd` join `bb_pembelian_awal` `pa` on(`pa`.`id` = `pd`.`id_pembelian`)) join `bb_proses_master` `pm` on(`pm`.`id` = `pd`.`id_proses_master`)) ORDER BY `pd`.`id_pembelian` ASC, `pm`.`urutan_tahap` ASC, `pd`.`tanggal_proses` ASC ;

DROP TABLE IF EXISTS `bb_v_stock_akhir_per_bahan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`alsz2632`@`localhost` SQL SECURITY DEFINER VIEW `bb_v_stock_akhir_per_bahan`  AS SELECT `bm`.`id` AS `id_bahan`, `bm`.`nama_bahan` AS `nama_bahan`, sum((select `pd`.`berat_keluar` from `bb_proses_detail` `pd` where `pd`.`id_pembelian` = `pa`.`id` order by `pd`.`tanggal_proses` desc,`pd`.`id` desc limit 1)) AS `total_berat_siap_jual`, (select coalesce(sum(`pj`.`berat_jual`),0) from `bb_penjualan` `pj` where `pj`.`id_pembelian` in (select `bb_pembelian_awal`.`id` from `bb_pembelian_awal` where `bb_pembelian_awal`.`id_bahan` = `bm`.`id`)) AS `total_terjual`, sum((select `pd`.`berat_keluar` from `bb_proses_detail` `pd` where `pd`.`id_pembelian` = `pa`.`id` order by `pd`.`tanggal_proses` desc,`pd`.`id` desc limit 1)) - (select coalesce(sum(`pj`.`berat_jual`),0) from `bb_penjualan` `pj` where `pj`.`id_pembelian` in (select `bb_pembelian_awal`.`id` from `bb_pembelian_awal` where `bb_pembelian_awal`.`id_bahan` = `bm`.`id`)) AS `stock_final` FROM (`bb_bahan_master` `bm` left join `bb_pembelian_awal` `pa` on(`pa`.`id_bahan` = `bm`.`id`)) GROUP BY `bm`.`id`, `bm`.`nama_bahan` ;

ALTER TABLE `bb_pembelian_awal`
  ADD CONSTRAINT `bb_pembelian_awal_ibfk_1` FOREIGN KEY (`id_bahan`) REFERENCES `bb_bahan_master` (`id`),
  ADD CONSTRAINT `bb_pembelian_awal_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `bb_supplier` (`id`);

ALTER TABLE `bb_pengeluaran`
  ADD CONSTRAINT `bb_pengeluaran_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `bb_penjualan` (`id`);

ALTER TABLE `bb_penjualan`
  ADD CONSTRAINT `bb_penjualan_ibfk_1` FOREIGN KEY (`id_pembelian`) REFERENCES `bb_pembelian_awal` (`id`),
  ADD CONSTRAINT `bb_penjualan_ibfk_2` FOREIGN KEY (`id_buyer`) REFERENCES `bb_buyer` (`id`);

ALTER TABLE `bb_proses_detail`
  ADD CONSTRAINT `bb_proses_detail_ibfk_1` FOREIGN KEY (`id_pembelian`) REFERENCES `bb_pembelian_awal` (`id`),
  ADD CONSTRAINT `bb_proses_detail_ibfk_2` FOREIGN KEY (`id_proses_master`) REFERENCES `bb_proses_master` (`id`);
COMMIT;
