<?php
/**
 * Helper Perhitungan Laba Rugi Kayu Gaharu Berbasis BPP FIFO
 * Modul Abdul Hadi - Fayyfir App
 */

if (!function_exists('getLabaRugiGaharuData')) {
    function getLabaRugiGaharuData($conn, $filter_tahun = null)
    {
        // 1. Safeguard tabel gaharu_monthly_expenses
        $conn->query("
            CREATE TABLE IF NOT EXISTS `gaharu_monthly_expenses` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `bulan` VARCHAR(7) NOT NULL,
              `nama_pengeluaran` VARCHAR(255) NOT NULL,
              `jenis` ENUM('fix', 'tidak_fix') NOT NULL DEFAULT 'tidak_fix',
              `jumlah` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              `keterangan` TEXT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_bulan` (`bulan`),
              KEY `idx_jenis` (`jenis`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Ambil semua batch produksi yang sudah selesai/terhitung (urut kronologis ASC untuk antrean FIFO)
        $sql_batches = "
            SELECT 
                p.id AS batch_id,
                p.product_id,
                p.production_number,
                COALESCE(p.production_date, DATE(p.created_at)) AS prod_date,
                COALESCE(p.total_output, 0) AS total_output,
                (COALESCE(p.total_pro_materials, 0) + COALESCE(p.total_pro_expenses, 0)) AS total_cost,
                ps.product_name,
                COALESCE(u.symbol, 'g') AS unit_symbol
            FROM productions p
            JOIN product_stocks ps ON p.product_id = ps.id
            LEFT JOIN units u ON ps.unit_id = u.id
            WHERE p.status != 'Proses' AND COALESCE(p.total_output, 0) > 0
            ORDER BY COALESCE(p.production_date, DATE(p.created_at)) ASC, p.id ASC
        ";
        $res_b = $conn->query($sql_batches);
        $batches_by_prod = [];
        if ($res_b) {
            while ($b = $res_b->fetch_assoc()) {
                $pid = (int)$b['product_id'];
                $out = (float)$b['total_output'];
                $cost = (float)$b['total_cost'];
                $ucost = $out > 0 ? ($cost / $out) : 0.0;

                $batches_by_prod[$pid][] = [
                    'batch_id'          => (int)$b['batch_id'],
                    'production_number' => $b['production_number'],
                    'prod_date'         => $b['prod_date'],
                    'product_name'      => $b['product_name'],
                    'unit_symbol'       => $b['unit_symbol'],
                    'total_output'      => $out,
                    'total_cost'        => $cost,
                    'unit_cost'         => $ucost,
                    'remaining_qty'     => $out
                ];
            }
        }

        // 3. Ambil semua transaksi penjualan terurut kronologis ASC
        $sql_sales = "
            SELECT 
                s.id AS sale_id,
                s.selling_date,
                s.invoice_number,
                s.buyer_id,
                COALESCE(b.name, 'Umum') AS buyer_name,
                s.product_id,
                COALESCE(ps.product_name, 'Produk') AS product_name,
                COALESCE(u.symbol, 'g') AS unit_symbol,
                COALESCE(s.qty, 0) AS qty,
                COALESCE(s.price, 0) AS price,
                COALESCE(s.total_selling, 0) AS total_selling,
                COALESCE(s.dp, 0) AS dp,
                COALESCE(s.status, 'Lunas') AS status
            FROM selling_products s
            LEFT JOIN buyer_products b ON s.buyer_id = b.id
            LEFT JOIN product_stocks ps ON s.product_id = ps.id
            LEFT JOIN units u ON ps.unit_id = u.id
            ORDER BY s.selling_date ASC, s.id ASC
        ";
        $res_s = $conn->query($sql_sales);

        $pendapatan_per_bulan      = [];
        $bpp_per_bulan             = [];
        $sales_by_month            = [];
        $batches_used_by_month     = [];
        $all_processed_sales       = [];

        if ($res_s) {
            while ($s = $res_s->fetch_assoc()) {
                $pid = (int)$s['product_id'];
                $qty_needed = (float)$s['qty'];
                $sale_bpp = 0.0;
                $sale_batches = [];

                if (isset($batches_by_prod[$pid]) && !empty($batches_by_prod[$pid])) {
                    while ($qty_needed > 0.00001 && !empty($batches_by_prod[$pid])) {
                        $batch = &$batches_by_prod[$pid][0];
                        $take = min($qty_needed, (float)$batch['remaining_qty']);
                        $cost = $take * (float)$batch['unit_cost'];

                        $sale_bpp += $cost;
                        $batch['remaining_qty'] -= $take;
                        $qty_needed -= $take;

                        $sale_batches[] = [
                            'batch_id'          => $batch['batch_id'],
                            'production_number' => $batch['production_number'],
                            'prod_date'         => $batch['prod_date'],
                            'product_name'      => $batch['product_name'],
                            'unit_symbol'       => $batch['unit_symbol'],
                            'qty_used'          => $take,
                            'unit_cost'         => $batch['unit_cost'],
                            'total_cost'        => $cost
                        ];

                        if ($batch['remaining_qty'] <= 0.00001) {
                            array_shift($batches_by_prod[$pid]);
                        }
                    }
                    unset($batch);
                }

                $s_date = !empty($s['selling_date']) ? $s['selling_date'] : date('Y-m-d');
                $m_key = date('Y-m', strtotime($s_date));
                $tot_sell = (float)$s['total_selling'];
                $margin = $tot_sell - $sale_bpp;

                $pendapatan_per_bulan[$m_key] = ($pendapatan_per_bulan[$m_key] ?? 0.0) + $tot_sell;
                $bpp_per_bulan[$m_key]        = ($bpp_per_bulan[$m_key] ?? 0.0) + $sale_bpp;

                $sale_item = $s;
                $sale_item['bpp']          = $sale_bpp;
                $sale_item['margin']       = $margin;
                $sale_item['batches_used'] = $sale_batches;
                $sale_item['month_key']    = $m_key;

                $sales_by_month[$m_key][] = $sale_item;
                $all_processed_sales[]    = $sale_item;

                // Rekap pemakaian batch per bulan
                foreach ($sale_batches as $sb) {
                    $bid = $sb['batch_id'];
                    if (!isset($batches_used_by_month[$m_key][$bid])) {
                        $batches_used_by_month[$m_key][$bid] = $sb;
                    } else {
                        $batches_used_by_month[$m_key][$bid]['qty_used']   += $sb['qty_used'];
                        $batches_used_by_month[$m_key][$bid]['total_cost'] += $sb['total_cost'];
                    }
                }
            }
        }

        // 3b. Hitung total nilai biaya produksi yang belum terjual (sisa stok gudang)
        $total_biaya_belum_terjual = 0.0;
        foreach ($batches_by_prod as $pid => $b_list) {
            foreach ($b_list as $b) {
                $total_biaya_belum_terjual += ((float)$b['remaining_qty'] * (float)$b['unit_cost']);
            }
        }

        // 4. Ambil beban operasional bulanan gaharu (gaharu_monthly_expenses)
        $operasional_per_bulan       = [];
        $operasional_items_by_month  = [];
        $res_op = $conn->query("
            SELECT id, bulan, nama_pengeluaran, jenis, jumlah, keterangan, created_at 
            FROM gaharu_monthly_expenses 
            ORDER BY bulan DESC, id ASC
        ");
        if ($res_op) {
            while ($op = $res_op->fetch_assoc()) {
                $b = trim($op['bulan']);
                if (preg_match('/^\d{4}-\d{2}$/', $b)) {
                    $operasional_per_bulan[$b] = ($operasional_per_bulan[$b] ?? 0.0) + (float)$op['jumlah'];
                    $operasional_items_by_month[$b][] = $op;
                }
            }
        }

        // 5. Kumpulkan semua tahun dari data
        $semua_tahun = [];
        $all_months = array_unique(array_merge(
            array_keys($pendapatan_per_bulan),
            array_keys($bpp_per_bulan),
            array_keys($operasional_per_bulan)
        ));

        foreach ($all_months as $m) {
            $thn = substr($m, 0, 4);
            if (!empty($thn)) $semua_tahun[$thn] = true;
        }
        $current_year_str = date('Y');
        $semua_tahun[$current_year_str] = true;
        krsort($semua_tahun);

        // Jika filter_tahun tidak diset, default ke tahun ini
        if ($filter_tahun === null || $filter_tahun === '') {
            $filter_tahun = (int)$current_year_str;
        } else {
            $filter_tahun = (int)$filter_tahun;
        }

        // 6. Hitung Laba Kotor dan Laba Bersih per bulan
        $laba_kotor_per_bulan  = [];
        $laba_bersih_per_bulan = [];

        foreach ($all_months as $m) {
            $pen = (float)($pendapatan_per_bulan[$m] ?? 0.0);
            $bpp = (float)($bpp_per_bulan[$m] ?? 0.0);
            $op  = (float)($operasional_per_bulan[$m] ?? 0.0);

            $kotor  = $pen - $bpp;
            $bersih = $kotor - $op;

            $laba_kotor_per_bulan[$m]  = $kotor;
            $laba_bersih_per_bulan[$m] = $bersih;
        }

        // 7. Filter daftar bulan berdasarkan tahun aktif
        $filtered_months = [];
        foreach ($all_months as $m) {
            if ((int)substr($m, 0, 4) === $filter_tahun) {
                $filtered_months[] = $m;
            }
        }
        rsort($filtered_months);

        return [
            'filter_tahun'               => $filter_tahun,
            'semua_tahun'                => $semua_tahun,
            'all_months'                 => $all_months,
            'filtered_months'            => $filtered_months,
            'pendapatan_per_bulan'       => $pendapatan_per_bulan,
            'bpp_per_bulan'              => $bpp_per_bulan,
            'laba_kotor_per_bulan'       => $laba_kotor_per_bulan,
            'operasional_per_bulan'      => $operasional_per_bulan,
            'laba_bersih_per_bulan'      => $laba_bersih_per_bulan,
            'sales_by_month'             => $sales_by_month,
            'batches_used_by_month'      => $batches_used_by_month,
            'operasional_items_by_month' => $operasional_items_by_month,
            'all_processed_sales'        => $all_processed_sales,
            'total_biaya_belum_terjual'  => $total_biaya_belum_terjual
        ];
    }
}
