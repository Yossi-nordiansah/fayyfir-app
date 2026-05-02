-- Recreate view bb_v_stok_bahan to filter by status = 'aktif'
CREATE OR REPLACE VIEW `bb_v_stok_bahan` AS 
select 
    `bm`.`id` AS `id_bahan`,
    `bm`.`nama_bahan` AS `nama_bahan`,
    `bm`.`satuan` AS `satuan`,
    (select coalesce(sum(`pa`.`berat_awal`),0) from `bb_pembelian_awal` `pa` where (`pa`.`id_bahan` = `bm`.`id`)) AS `total_beli`,
    coalesce(
        (select sum(`pd`.`berat_masuk`) 
         from `bb_proses_detail` `pd` 
         join `bb_proses_master` `pm` on `pd`.`id_proses_master` = `pm`.`id` 
         where `pm`.`id_bahan` = `bm`.`id` 
         and `pd`.`status` = 'aktif'
         and `pm`.`urutan_tahap` = (select min(`urutan_tahap`) from `bb_proses_master` where `id_bahan` = `bm`.`id`))
    ,0) AS `total_proses`,
    ((select coalesce(sum(`pa`.`berat_awal`),0) from `bb_pembelian_awal` `pa` where (`pa`.`id_bahan` = `bm`.`id`)) - 
     coalesce(
        (select sum(`pd`.`berat_masuk`) 
         from `bb_proses_detail` `pd` 
         join `bb_proses_master` `pm` on `pd`.`id_proses_master` = `pm`.`id` 
         where `pm`.`id_bahan` = `bm`.`id` 
         and `pd`.`status` = 'aktif'
         and `pm`.`urutan_tahap` = (select min(`urutan_tahap`) from `bb_proses_master` where `id_bahan` = `bm`.`id`))
    ,0)) AS `stok_tersedia` 
from `bb_bahan_master` `bm` 
where `bm`.`deleted_at` is null;
