<?php
session_start();
require "../../config.php";
$conn = get_conn2(); // Lazy loader — tidak buka koneksi ganda
require "../includes/helpers.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

/** Helpers copied from pembelian-awal to maintain consistency **/
function get_proses_by_urutan($conn, $id_bahan, $urutan)
{
    $sql = "SELECT id, nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res;
}

function get_min_urutan($conn, $id_bahan)
{
    $sql = "SELECT MIN(urutan_tahap) as min_u FROM bb_proses_master WHERE id_bahan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bahan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['min_u'] !== null ? (int)$row['min_u'] : null;
}

function get_next_urutan($conn, $id_bahan, $urutan)
{
    $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap > ? ORDER BY urutan_tahap ASC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['urutan_tahap'] : null;
}

function get_prev_urutan($conn, $id_bahan, $urutan)
{
    $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap < ? ORDER BY urutan_tahap DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['urutan_tahap'] : null;
}

function calc_remaining_for_next_stage($conn, $idPembelian, $nextStage)
{
    $idPembelian = (int)$idPembelian;
    $nextStage = (int)$nextStage;
    if ($nextStage <= 0) return 0;

    // Ambil id_bahan
    $resBahan = $conn->query("SELECT id_bahan, berat_awal FROM bb_pembelian_awal WHERE id = $idPembelian");
    $rowBahan = $resBahan->fetch_assoc();
    $id_bahan = $rowBahan['id_bahan'];
    $berat_awal = (float)$rowBahan['berat_awal'];

    $min_urutan = get_min_urutan($conn, $id_bahan);

    if ($nextStage === $min_urutan) {
        $sql2 = "SELECT COALESCE(SUM(pd.berat_masuk),0) AS processed FROM bb_proses_detail pd
                 JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                 WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("ii", $idPembelian, $nextStage);
        $stmt->execute();
        $processed = (float)$stmt->get_result()->fetch_assoc()['processed'];
        return max(0, round($berat_awal - $processed, 2));
    } else {
        $prev = get_prev_urutan($conn, $id_bahan, $nextStage);
        if ($prev === null) return 0;

        $sqlPrevOut = "SELECT COALESCE(SUM(pd.berat_keluar),0) AS prev_out FROM bb_proses_detail pd
                       JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                       WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
        $stmt = $conn->prepare($sqlPrevOut);
        $stmt->bind_param("ii", $idPembelian, $prev);
        $stmt->execute();
        $prev_out = (float)$stmt->get_result()->fetch_assoc()['prev_out'];

        $sqlConsumed = "SELECT COALESCE(SUM(pd.berat_masuk),0) AS consumed FROM bb_proses_detail pd
                         JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                         WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
        $stmt = $conn->prepare($sqlConsumed);
        $stmt->bind_param("ii", $idPembelian, $nextStage);
        $stmt->execute();
        $consumed = (float)$stmt->get_result()->fetch_assoc()['consumed'];
        return max(0, round($prev_out - $consumed, 2));
    }
}

// Ambil list produksi (aktif, dihentikan, dan dibatalkan — kecuali yang selesai/siap_jual)
$queryProduksi = "
    SELECT 
        CASE 
            WHEN COALESCE(pd.metode_produksi, 'tertimbang') = 'belum_tertimbang' 
            THEN CASE 
                WHEN pd.id_penampungan IS NOT NULL AND pd.id_penampungan > 0 THEN CONCAT('GROUP-BT-PN-', pd.id_penampungan)
                ELSE CONCAT('GROUP-BT-PA-', pd.id_pembelian)
            END
            ELSE COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian))
        END as batch_key,
        MAX(pd.kode_produksi) as kode_produksi,
        MIN(pd.id_pembelian) as sample_id_pembelian,
        MAX(pa.kode_batch) as sample_batch_pembelian,
        MAX(bm.id) as id_bahan,
        MAX(bm.nama_bahan) as nama_bahan,
        MAX(bm.satuan) as satuan,
        COALESCE(MAX(pd.metode_produksi), 'tertimbang') as metode_produksi,
        COALESCE(MAX(pd.status_batch), 'berjalan') as status_batch,
        MAX(pd.hpp_temporary) as hpp_temporary,
        MAX(pd.hpp_final) as hpp_final,
        COALESCE(MAX(pm.urutan_tahap), 0) as current_tahap_urutan,
        MAX(CASE WHEN pd.status = 'dihentikan' THEN 1 ELSE 0 END) as is_dihentikan,
        (MIN(CASE WHEN pd.status IN ('aktif','dihentikan') THEN 1 ELSE 0 END) = 0
         AND MAX(CASE WHEN pd.status = 'batal' THEN 1 ELSE 0 END) = 1) as is_dibatalkan,
        SUM(CASE 
            WHEN pd.status = 'batal' THEN 0
            WHEN last_stage.max_urutan = 0 THEN pd.berat_masuk 
            WHEN COALESCE(pm.urutan_tahap, 0) = last_stage.max_urutan THEN pd.berat_keluar 
            ELSE 0 
        END) as total_berat_akhir_tahap_ini,
        COUNT(DISTINCT pd.id_pembelian) as total_suppliers,
        COUNT(DISTINCT pd.kode_produksi) as total_sub_batches,
        GROUP_CONCAT(DISTINCT s.nama_supplier SEPARATOR ', ') as supplier_names,
        GROUP_CONCAT(DISTINCT pn.nama_penampungan SEPARATOR ', ') as penampungan_names,
        MIN(pd.created_at) as batch_created_at
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_supplier s ON s.id = pa.id_supplier
    LEFT JOIN bb_penampungan pn ON pn.id = pd.id_penampungan
    LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
    LEFT JOIN (
        SELECT 
            CASE 
                WHEN COALESCE(pd3.metode_produksi, 'tertimbang') = 'belum_tertimbang' 
                THEN CASE 
                    WHEN pd3.id_penampungan IS NOT NULL AND pd3.id_penampungan > 0 THEN CONCAT('GROUP-BT-PN-', pd3.id_penampungan)
                    ELSE CONCAT('GROUP-BT-PA-', pd3.id_pembelian)
                END
                ELSE COALESCE(pd3.kode_produksi, CONCAT('SINGLE-', pd3.id_pembelian))
            END as bk3, 
            COALESCE(MAX(pm3.urutan_tahap), 0) as max_urutan
        FROM bb_proses_detail pd3
        LEFT JOIN bb_proses_master pm3 ON pm3.id = pd3.id_proses_master
        GROUP BY bk3
    ) last_stage ON (
        CASE 
            WHEN COALESCE(pd.metode_produksi, 'tertimbang') = 'belum_tertimbang' 
            THEN CASE 
                WHEN pd.id_penampungan IS NOT NULL AND pd.id_penampungan > 0 THEN CONCAT('GROUP-BT-PN-', pd.id_penampungan)
                ELSE CONCAT('GROUP-BT-PA-', pd.id_pembelian)
            END
            ELSE COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian))
        END
    ) = last_stage.bk3
    WHERE COALESCE(pd.status_batch, 'berjalan') != 'closed'
    GROUP BY batch_key
    ORDER BY MAX(pd.created_at) DESC
";
$resultProduksi = $conn->query($queryProduksi);

// Query produksi yang sudah SELESAI / CLOSING BATCH
$querySelesai = "
    SELECT 
        CASE 
            WHEN COALESCE(pd.metode_produksi, 'tertimbang') = 'belum_tertimbang' 
            THEN CASE 
                WHEN pd.id_penampungan IS NOT NULL AND pd.id_penampungan > 0 THEN CONCAT('GROUP-BT-PN-', pd.id_penampungan)
                ELSE CONCAT('GROUP-BT-PA-', pd.id_pembelian)
            END
            ELSE COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian))
        END as batch_key,
        MAX(pd.kode_produksi) as kode_produksi,
        MIN(pd.id_pembelian) as sample_id_pembelian,
        MAX(pa.kode_batch) as sample_batch_pembelian,
        MAX(bm.id) as id_bahan,
        MAX(bm.nama_bahan) as nama_bahan,
        MAX(bm.satuan) as satuan,
        COALESCE(MAX(pd.metode_produksi), 'tertimbang') as metode_produksi,
        COALESCE(MAX(pd.status_batch), 'closed') as status_batch,
        MAX(pd.hpp_final) as hpp_final,
        GROUP_CONCAT(DISTINCT s.nama_supplier SEPARATOR ', ') as supplier_names,
        GROUP_CONCAT(DISTINCT pn.nama_penampungan SEPARATOR ', ') as penampungan_names,
        COUNT(DISTINCT pd.kode_produksi) as total_sub_batches,
        MIN(pd.created_at) as batch_created_at,
        MAX(pd.created_at) as closed_at
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_supplier s ON s.id = pa.id_supplier
    LEFT JOIN bb_penampungan pn ON pn.id = pd.id_penampungan
    WHERE COALESCE(pd.status_batch, 'berjalan') = 'closed'
    GROUP BY batch_key
    ORDER BY MAX(pd.created_at) DESC
";
$resultSelesai = $conn->query($querySelesai);
?>

<?php
/** Helper to get current stage name inside the loop since subqueries in SELECT with GROUP BY can be tricky **/
function get_stage_name($conn, $id_bahan, $urutan)
{
    $stmt = $conn->prepare("SELECT nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1");
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['nama_proses'] ?? '-';
}
?>

<?php
$activeMenu = "productions";
$activeModule = "Dashboard Produksi";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Proses Produksi</h1>
            <p class="text-sm text-gray-500">Kelola alur produksi dari bahan mentah hingga siap jual.</p>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <a href="../data-tahap/index.php"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Master Tahap
            </a>
            <button onclick="openMulaiProduksi()"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Mulai Produksi
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="font-medium"><?= $_SESSION['success']; ?></div>
            </div>
            <?php if (isset($_SESSION['closing_detail_url'])): ?>
                <a href="<?= $_SESSION['closing_detail_url']; ?>" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition shrink-0 shadow-sm">
                    Lihat Detail HPP & Penyusutan
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>
        <?php
        unset($_SESSION['success']);
        unset($_SESSION['closing_detail_url']);
        ?>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <button id="tabBerjalanBtn" onclick="switchTab('berjalan')" class="py-3 px-1 border-b-2 font-bold text-sm text-blue-600 border-blue-600 flex items-center gap-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Produksi Berjalan (<?= $resultProduksi ? $resultProduksi->num_rows : 0 ?>)
            </button>
            <button id="tabSelesaiBtn" onclick="switchTab('selesai')" class="py-3 px-1 border-b-2 font-medium text-sm text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 flex items-center gap-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Produksi Selesai (<?= $resultSelesai ? $resultSelesai->num_rows : 0 ?>)
            </button>
        </nav>
    </div>

    <!-- Active Production Table Container -->
    <div id="sectionBerjalan">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center flex-wrap gap-4">
            <h3 class="font-semibold text-gray-800">Daftar Produksi Berjalan</h3>
            <div class="flex items-center gap-4 flex-1 max-w-2xl justify-end">
                <input id="searchInput" type="text" placeholder="Cari produksi atau bahan..."
                    class="w-full md:w-64 px-3 py-1.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                <div class="text-xs text-gray-500 whitespace-nowrap">
                    Tampilkan
                    <select id="rowsPerPage" class="border border-gray-300 rounded-lg px-1 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-800 text-yellow-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Produksi / Batch</th>
                        <th class="px-6 py-3">Bahan</th>
                        <th class="px-6 py-3">Tahap Saat Ini</th>
                        <th class="px-6 py-3 text-right">Tersisa</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="productionTable" class="divide-y divide-gray-100">
                    <?php
                    $total_tersisa_grand = 0;
                    if ($resultProduksi && $resultProduksi->num_rows > 0): ?>
                        <?php while ($row = $resultProduksi->fetch_assoc()):
                            $kode_produksi      = $row['kode_produksi'];
                            $id_pembelian       = $row['sample_id_pembelian'];
                            $id_bahan           = $row['id_bahan'];
                            $metode_produksi    = $row['metode_produksi'];
                            $is_dihentikan      = (bool)$row['is_dihentikan'];
                            $is_dibatalkan      = (bool)$row['is_dibatalkan'];
                            $is_stopped         = $is_dihentikan || $is_dibatalkan;
                            $current_tahap_nama = ($row['current_tahap_urutan'] == 0) ? 'Persiapan' : get_stage_name($conn, $id_bahan, $row['current_tahap_urutan']);

                            $next_urutan  = $is_stopped ? null : get_next_urutan($conn, $id_bahan, $row['current_tahap_urutan']);
                            $next_process = $next_urutan ? get_proses_by_urutan($conn, $id_bahan, $next_urutan) : null;

                            $remaining = (float)$row['total_berat_akhir_tahap_ini'];
                            if (!$is_dibatalkan) $total_tersisa_grand += $remaining;

                            $row_class = $is_dibatalkan ? 'bg-gray-100 opacity-75' : ($is_dihentikan ? 'bg-red-50' : 'hover:bg-gray-50');
                        ?>
                            <tr class="data-row <?= $row_class ?> transition" data-remaining="<?= $remaining ?>">
                                <?php if ($metode_produksi === 'belum_tertimbang' && !$is_dibatalkan): ?>
                                    <!-- Master Group Row untuk Belum Tertimbang -->
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            Kelompok Produksi (Belum Tertimbang)
                                        </div>
                                        <?php
                                        $sumber_info = !empty($row['penampungan_names'])
                                            ? 'Penampungan: ' . htmlspecialchars($row['penampungan_names'])
                                            : (!empty($row['supplier_names']) ? 'Supplier: ' . htmlspecialchars($row['supplier_names']) : $row['total_suppliers'] . ' Supplier');
                                        ?>
                                        <div class="text-xs text-gray-600 font-medium mt-0.5">
                                            <?= $sumber_info ?>
                                        </div>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                            <?= $row['total_sub_batches'] ?> Sub-Batch Terdaftar
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold uppercase">
                                            <?= $row['total_sub_batches'] ?> Sub-Batch Berjalan
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-amber-800 font-semibold italic">
                                        (Belum Tertimbang)
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2 flex-wrap">
                                            <a href="kelola-sub-batch.php?id_pembelian=<?= $id_pembelian ?>"
                                                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Kelola Sub-Batch (<?= $row['total_sub_batches'] ?>)
                                            </a>
                                            <?php if ($row['status_batch'] !== 'closed'): ?>
                                                <button onclick="openClosingModal('', <?= $id_pembelian ?>, '<?= htmlspecialchars(addslashes($row['nama_bahan'])) ?>', 0)"
                                                    class="px-3 py-1.5 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition" title="Gudang Habis - Hitung HPP Final Akurat & Opname">
                                                    Hitung Akurat (Gudang Habis)
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <!-- Baris Individual untuk Tertimbang Awal / Dibatalkan -->
                                    <td class="px-6 py-4">
                                        <div class="font-medium <?= $is_dibatalkan ? 'text-gray-400 line-through' : 'text-gray-900' ?>"><?= $kode_produksi ?: $row['sample_batch_pembelian'] ?></div>
                                        <?php
                                        $sumber_info = !empty($row['penampungan_names'])
                                            ? 'Penampungan: ' . htmlspecialchars($row['penampungan_names'])
                                            : (!empty($row['supplier_names']) ? 'Supplier: ' . htmlspecialchars($row['supplier_names']) : $row['total_suppliers'] . ' Supplier');
                                        ?>

                                        <div class="text-[11px] text-gray-600 font-medium mt-0.5">
                                            <?= $sumber_info ?>
                                        </div>

                                        <?php if ($is_dihentikan): ?>
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93" />
                                                </svg>
                                                Produksi Dihentikan
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_dibatalkan): ?>
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-500 border border-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Dibatalkan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 <?= $is_dibatalkan ? 'text-gray-400' : '' ?>"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($is_dibatalkan): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-400 text-[10px] font-semibold uppercase">Produksi Dibatalkan</span>
                                        <?php elseif ($is_dihentikan): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-semibold uppercase"><?= htmlspecialchars($current_tahap_nama) ?> (Dihentikan)</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold uppercase"><?= htmlspecialchars($current_tahap_nama) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold <?= $is_dibatalkan ? 'text-gray-400' : ($is_dihentikan ? 'text-red-600' : '') ?>">
                                        <?= $is_dibatalkan ? '-' : number_format($remaining, 0, ',', '.') . ' ' . htmlspecialchars($row['satuan']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2 flex-wrap">
                                            <?php if (!$is_stopped): ?>
                                                <?php if ($next_urutan): ?>
                                                    <button onclick="openProsesModal('<?= $kode_produksi ?>', <?= $id_pembelian ?>, <?= $next_urutan ?>, '<?= htmlspecialchars(addslashes($next_process['nama_proses'])) ?>', <?= $remaining ?>, '<?= htmlspecialchars($row['satuan']) ?>', '<?= $metode_produksi ?>')"
                                                        class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition">
                                                        Proses: <?= htmlspecialchars($next_process['nama_proses']) ?>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="confirmBatal('<?= $kode_produksi ?>', <?= $id_pembelian ?>)"
                                                    class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 transition">
                                                    Batal
                                                </button>
                                            <?php endif; ?>
                                            <a href="/app.fayyfir/abdul-hadi/belanja-harian/proses-produksi/detail-penyusutan.php?id=<?= $id_pembelian ?><?= $kode_produksi ? '&kode_produksi=' . $kode_produksi : '' ?>"
                                                class="px-3 py-1.5 <?= $is_dibatalkan ? 'bg-gray-400 hover:bg-gray-500' : 'bg-purple-600 hover:bg-purple-700' ?> text-white rounded-lg text-xs transition" title="Detail Penyusutan & HPP">
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada proses produksi yang berjalan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-gray-800 text-yellow-400 font-bold uppercase">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right">Grand Total Tersisa</td>
                        <td id="footerTotalRemaining" class="px-6 py-4 text-right"><?= number_format($total_tersisa_grand, 0, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
            <div id="totalRowsInfo" class="text-xs text-gray-500"></div>
            <div id="paginationControls" class="flex gap-1"></div>
        </div>
    </div>
    </div>
    <!-- End sectionBerjalan -->

    <!-- Section Produksi Selesai (Closed) -->
    <div id="sectionSelesai" class="hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-emerald-50/40 flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat Produksi Selesai (Closing Batch)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Daftar batch yang sudah di-opname & direvaluasi HPP Final-nya.</p>
                </div>
                <span class="text-xs text-emerald-700 font-semibold bg-emerald-100 px-3 py-1 rounded-full border border-emerald-200">
                    Total Selesai: <?= $resultSelesai ? $resultSelesai->num_rows : 0 ?> Batch
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-800 text-yellow-400 font-semibold uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-3">Produksi / Batch</th>
                            <th class="px-6 py-3">Bahan Baku</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">HPP Final Revaluasi</th>
                            <th class="px-6 py-3 text-center">Tanggal Selesai</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($resultSelesai && $resultSelesai->num_rows > 0): ?>
                            <?php while ($sel = $resultSelesai->fetch_assoc()):
                                $kode_sel        = $sel['kode_produksi'];
                                $id_p_sel        = $sel['sample_id_pembelian'];
                                $metode_sel      = $sel['metode_produksi'];
                                $sumber_sel      = !empty($sel['penampungan_names'])
                                    ? 'Penampungan: ' . htmlspecialchars($sel['penampungan_names'])
                                    : (!empty($sel['supplier_names']) ? 'Supplier: ' . htmlspecialchars($sel['supplier_names']) : 'Pembelian #' . $id_p_sel);
                                // belum_tertimbang → buka ringkasan gabungan (tanpa kode_produksi)
                                // tertimbang → buka detail sub-batch spesifik (dengan kode_produksi)
                                $detail_url_sel  = "detail-penyusutan.php?id=" . $id_p_sel . ($kode_sel && $metode_sel !== 'belum_tertimbang' ? "&kode_produksi=" . urlencode($kode_sel) : "");
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <?php if ($metode_sel === 'belum_tertimbang'): ?>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                                Kelompok Produksi (Belum Tertimbang)
                                            </div>
                                            <div class="text-xs text-gray-600 font-medium mt-0.5"><?= $sumber_sel ?></div>
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                <?= $sel['total_sub_batches'] ?> Sub-Batch Selesai
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($sel['nama_bahan']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold uppercase">
                                                Selesai Siap Jual
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-emerald-700">
                                            Rp <?= number_format((float)$sel['hpp_final'], 0, ',', '.') ?> / <?= htmlspecialchars($sel['satuan']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                                            <?= date('d M Y, H:i', strtotime($sel['closed_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center gap-2 flex-wrap">
                                                <a href="<?= $detail_url_sel ?>"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                                                    Lihat Detail HPP
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($kode_sel ?: ('ID #' . $id_p_sel)) ?></div>
                                            <div class="text-xs text-gray-500 font-medium mt-0.5"><?= $sumber_sel ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($sel['nama_bahan']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold uppercase">
                                                Selesai Siap Jual
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-emerald-700">
                                            Rp <?= number_format((float)$sel['hpp_final'], 0, ',', '.') ?> / <?= htmlspecialchars($sel['satuan']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                                            <?= date('d M Y, H:i', strtotime($sel['closed_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="<?= $detail_url_sel ?>"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                                                Lihat Detail HPP & Penyusutan
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada riwayat produksi yang selesai (closing batch).</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- End sectionSelesai -->
</main>

<!-- Modal Mulai Produksi -->
<div id="modalMulaiProduksi" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Mulai Produksi Baru</h3>
            <button onclick="closeMulaiProduksi()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="mulai-produksi.php" method="POST" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bahan Baku</label>
                    <select name="id_bahan" id="selectBahan" onchange="handleBahanChange(this.value)" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">-- Pilih Bahan --</option>
                        <?php
                        $resBahan = $conn->query("SELECT id, nama_bahan FROM bb_bahan_master ORDER BY nama_bahan ASC");
                        while ($b = $resBahan->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['nama_bahan']}</option>";
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Produksi</label>
                    <input type="date" name="tanggal_proses" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Metode Perhitungan Produksi & HPP</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="metode_produksi" value="tertimbang" checked onchange="handleMetodeProduksiChange()" class="mt-1 w-4 h-4 text-blue-600">
                        <div class="ml-2.5">
                            <div class="text-sm font-semibold text-gray-800">1. Tertimbang Awal</div>
                            <div class="text-[11px] text-gray-500">Bahan mentah ditimbang di awal. HPP & susut terhitung real-time.</div>
                        </div>
                    </label>
                    <label class="flex items-start p-3 border border-amber-200 bg-amber-50/40 rounded-xl cursor-pointer hover:bg-amber-50 transition">
                        <input type="radio" name="metode_produksi" value="belum_tertimbang" onchange="handleMetodeProduksiChange()" class="mt-1 w-4 h-4 text-amber-600">
                        <div class="ml-2.5">
                            <div class="text-sm font-semibold text-amber-900">2. Belum Tertimbang</div>
                            <div class="text-[11px] text-amber-700">Tidak menimbang di awal. HPP Sementara & Closing Revaluasi Batch.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div id="methodSelection" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pengambilan Stok</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="stok_method" value="all" checked onchange="toggleSupplierFilter(false)" class="w-4 h-4 text-blue-600">
                        <span class="text-sm text-gray-700">Ambil dari Stok Gabungan</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="stok_method" value="specific" onchange="toggleSupplierFilter(true)" class="w-4 h-4 text-blue-600">
                        <span class="text-sm text-gray-700">Berdasarkan Supplier Tertentu</span>
                    </label>
                </div>
            </div>

            <div id="supplierFilterDiv" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Supplier</label>
                <div id="supplierContainer" class="space-y-2">
                    <!-- Supplier dropdowns added here -->
                </div>
                <button type="button" onclick="addSupplierDropdown()" class="mt-2 text-blue-600 text-xs font-semibold hover:underline">+ Tambah Supplier</button>
            </div>

            <div id="rawStockList" class="mb-6 hidden">
                <h4 class="text-sm font-semibold text-gray-800 mb-3">Pilih Sumber Bahan (Supplier)</h4>

                <!-- View: Stok Gabungan (Penampungan) -->
                <div id="viewAllSuppliers" class="hidden">
                    <div id="penampunganRowContainer" class="space-y-3 max-h-64 overflow-y-auto pr-2">
                        <!-- Penampungan rows added here by JS -->
                    </div>
                    <p id="noPenampunganMsg" class="hidden text-xs text-gray-400 italic py-2">Belum ada stok gabungan. Gabungkan bahan dari halaman Stok Bahan terlebih dahulu.</p>
                </div>

                <!-- View: Supplier Tertentu -->
                <div id="viewSpecificSuppliers" class="space-y-3 hidden">
                    <div id="supplierRowContainer" class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                        <!-- Supplier rows added here -->
                    </div>
                    <button type="button" onclick="addSupplierRow()" class="inline-flex items-center gap-1.5 text-blue-600 text-xs font-bold hover:text-blue-800 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Supplier
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="catatan" placeholder="Catatan opsional..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMulaiProduksi()" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition">Batal</button>
                <button type="button" onclick="submitMulaiProduksi()" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition">Simpan Produksi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Proses (Next Stage) -->
<div id="modalProses" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 id="prosesTitle" class="text-xl font-bold text-gray-800">Proses Produksi</h3>
            <button onclick="closeProsesModal()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="proses-batch.php" method="POST" class="p-6">
            <input type="hidden" name="kode_produksi" id="proses_kode_produksi">
            <input type="hidden" name="id_pembelian" id="proses_id_pembelian">
            <input type="hidden" name="next_stage" id="proses_next_stage">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Proses</label>
                <input type="date" name="tanggal_proses" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Berat Masuk (<span class="unit-proses-label">Kg</span>)</label>
                <input type="text" name="berat_masuk" id="proses_berat_masuk" required readonly class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 outline-none font-medium text-gray-800">
                <p id="proses_berat_masuk_help" class="text-[10px] text-gray-500 mt-1">Total berat akhir dari tahap sebelumnya.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Berat Keluar (<span class="unit-proses-label">Kg</span>) <span class="text-red-500">*</span></label>
                <input type="text" name="berat_keluar" required placeholder="Masukan hasil proses..." class="format-number w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="catatan" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" rows="2"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeProsesModal()" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition">Simpan Hasil</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Closing Batch -->
<div id="modalClosingBatch" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-3 bg-amber-50">
            <div class="flex-1 min-w-0">
                <h3 class="text-lg sm:text-xl font-bold text-amber-900 leading-snug">Hitung Akurat HPP (Gudang Habis)</h3>
                <p class="text-xs text-amber-700 mt-1">Hitung susut aktual & HPP Final setelah tumpukan di gudang habis total.</p>
            </div>
            <button type="button" onclick="closeClosingModal()" class="shrink-0 p-1.5 text-amber-800 hover:text-amber-950 hover:bg-amber-100/80 rounded-xl transition" title="Tutup">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="closing-batch.php" method="POST" class="p-6">
            <input type="hidden" name="kode_produksi" id="closing_kode_produksi">
            <input type="hidden" name="id_pembelian" id="closing_id_pembelian">

            <div class="mb-4 bg-amber-50/50 p-4 rounded-xl border border-amber-100 space-y-2 text-xs text-gray-700">
                <div class="flex justify-between">
                    <span>Batch / Produksi:</span>
                    <strong id="closingBatchName">-</strong>
                </div>
                <div class="flex justify-between">
                    <span>Bahan Baku Terikat:</span>
                    <strong id="closingMaterialName">-</strong>
                </div>
                <div class="flex justify-between">
                    <span>Total Output Terkumpul:</span>
                    <strong id="closingOutputTotal" class="text-emerald-700 font-bold">0 Kg</strong>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Riwayat Batch & Tahap Dilalui:</label>
                <div id="closingBatchHistoryContainer" class="space-y-2 max-h-44 overflow-y-auto pr-1">
                    <div class="text-xs text-gray-400 italic py-1">Memuat riwayat batch...</div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Opname (Opsional)</label>
                <textarea name="catatan_opname" class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none" rows="3" placeholder="Catatan fisik gudang habis & kondisi susut..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeClosingModal()" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition">Batal</button>
                <button type="submit" onclick="return confirm('Apakah Anda yakin stok mentah di gudang sudah habis total? HPP Final akan direvaluasi secara akurat.')" class="px-6 py-2.5 bg-amber-600 text-white rounded-xl font-bold hover:bg-amber-700 transition">Hitung HPP Final Akurat</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/table-pagination.js"></script>
<script>
    function switchTab(tab) {
        const secBerjalan = document.getElementById('sectionBerjalan');
        const secSelesai  = document.getElementById('sectionSelesai');
        const tabB        = document.getElementById('tabBerjalanBtn');
        const tabS        = document.getElementById('tabSelesaiBtn');

        if (tab === 'berjalan') {
            secBerjalan.classList.remove('hidden');
            secSelesai.classList.add('hidden');
            tabB.className = 'py-3 px-1 border-b-2 font-bold text-sm text-blue-600 border-blue-600 flex items-center gap-2 transition';
            tabS.className = 'py-3 px-1 border-b-2 font-medium text-sm text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 flex items-center gap-2 transition';
        } else {
            secBerjalan.classList.add('hidden');
            secSelesai.classList.remove('hidden');
            tabS.className = 'py-3 px-1 border-b-2 font-bold text-sm text-emerald-600 border-emerald-600 flex items-center gap-2 transition';
            tabB.className = 'py-3 px-1 border-b-2 font-medium text-sm text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 flex items-center gap-2 transition';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        initTablePagination({
            tableId: "productionTable",
            rowsPerPageId: "rowsPerPage",
            searchInputId: "searchInput",
            paginationId: "paginationControls",
            infoId: "totalRowsInfo",
            onUpdate: function(visibleRows) {
                let total = 0;
                visibleRows.forEach(row => {
                    total += parseFloat(row.getAttribute('data-remaining')) || 0;
                });
                const footer = document.getElementById('footerTotalRemaining');
                if (footer) {
                    footer.textContent = new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(total);
                }
            }
        });
    });

    // Helper thousand separator
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatNumber(str) {
        return str.toString().replace(/\./g, "");
    }

    // Global listener for thousand separators and validation
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('format-number')) {
            let rawVal = unformatNumber(e.target.value);

            // Validation check if data-max is present
            if (e.target.dataset.max) {
                let maxVal = parseFloat(e.target.dataset.max);
                if (parseFloat(rawVal) > maxVal) {
                    alert("⚠️ Peringatan: Input (" + formatNumber(rawVal) + ") melebihi stok tersedia (" + formatNumber(Math.floor(maxVal)) + ")!");
                    rawVal = Math.floor(maxVal).toString();
                }
            }

            if (!isNaN(rawVal) && rawVal !== "") {
                e.target.value = formatNumber(rawVal);
            }
        }
    });

    function openMulaiProduksi() {
        document.getElementById('modalMulaiProduksi').classList.remove('hidden');
    }

    function closeMulaiProduksi() {
        document.getElementById('modalMulaiProduksi').classList.add('hidden');
    }

    let currentStockData = null;

    function handleBahanChange(idBahan) {
        const methodDiv = document.getElementById('methodSelection');
        const listDiv = document.getElementById('rawStockList');

        if (!idBahan) {
            methodDiv.classList.add('hidden');
            listDiv.classList.add('hidden');
            return;
        }

        fetch('api-get-raw-stock.php?id_bahan=' + idBahan)
            .then(response => response.json())
            .then(data => {
                currentStockData = data;

                // Update unit labels
                document.querySelectorAll('.unit-label').forEach(el => el.textContent = data.satuan);

                // Show method selection
                methodDiv.classList.remove('hidden');
                listDiv.classList.remove('hidden');

                // Reset to "Gabungan" by default
                document.querySelector('input[name="stok_method"][value="all"]').checked = true;
                toggleSupplierFilter(false);
            });
    }

    function toggleSupplierFilter(show) {
        const viewAll = document.getElementById('viewAllSuppliers');
        const viewSpecific = document.getElementById('viewSpecificSuppliers');
        const container = document.getElementById('supplierRowContainer');

        if (show) {
            viewAll.classList.add('hidden');
            viewSpecific.classList.remove('hidden');
            if (container.children.length === 0) {
                addSupplierRow();
            }
        } else {
            viewAll.classList.remove('hidden');
            viewSpecific.classList.add('hidden');
            container.innerHTML = '';
            renderPenampunganRows();
        }
    }

    function handleMetodeProduksiChange() {
        const isBelumTertimbang = document.querySelector('input[name="metode_produksi"]:checked')?.value === 'belum_tertimbang';

        // Toggle stok header / column visibility
        document.querySelectorAll('.stok-col').forEach(el => {
            if (isBelumTertimbang) {
                el.classList.add('hidden');
            } else {
                el.classList.remove('hidden');
            }
        });

        // Toggle input style & values for supplier_qty[]
        document.querySelectorAll('input[name="supplier_qty[]"]').forEach(input => {
            if (isBelumTertimbang) {
                input.value = "(jumlah tidak diketahui)";
                input.readOnly = true;
                input.classList.remove('format-number');
                input.className = 'w-full bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-sm outline-none font-semibold text-amber-900 cursor-not-allowed';
                delete input.dataset.max;
            } else {
                if (input.value === "(jumlah tidak diketahui)") {
                    input.value = "";
                }
                input.readOnly = false;
                input.placeholder = "0";
                input.classList.add('format-number');
                input.className = 'format-number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500';
            }
        });
    }

    function renderPenampunganRows() {
        if (!currentStockData) return;
        const container = document.getElementById('penampunganRowContainer');
        const noMsg = document.getElementById('noPenampunganMsg');
        container.innerHTML = '';

        // Filter hanya penampungan gabungan
        const penampunganList = currentStockData.suppliers.filter(s => s.is_gabungan);

        if (penampunganList.length === 0) {
            noMsg.classList.remove('hidden');
            return;
        }
        noMsg.classList.add('hidden');

        const isBelumTertimbang = document.querySelector('input[name="metode_produksi"]:checked')?.value === 'belum_tertimbang';

        penampunganList.forEach((p, idx) => {
            const radioId = `radio_penampungan_${idx}`;
            const div = document.createElement('div');
            div.className = 'border rounded-xl overflow-hidden transition-all';

            const stokFormatted = formatNumber(Math.floor(p.total_stok));
            const satuan = currentStockData.satuan;

            div.innerHTML = `
            <!-- Radio pilih penampungan -->
            <label for="${radioId}" class="flex items-center gap-3 p-3 cursor-pointer hover:bg-emerald-50 transition group">
                <input type="radio" id="${radioId}" name="pilih_penampungan" value="${p.id}"
                    class="w-4 h-4 text-emerald-600 accent-emerald-600"
                    onchange="onSelectPenampungan('${p.id}', ${p.total_stok}, ${JSON.stringify(isBelumTertimbang)})">
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] text-gray-500 uppercase font-semibold">Penampungan Gabungan</p>
                    <p class="text-sm font-bold text-emerald-700">${p.nama.replace(' [GABUNGAN]', '')}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[10px] text-gray-500 uppercase font-semibold">Stok Tersedia</p>
                    <p class="text-sm font-bold text-gray-800">${stokFormatted} <span class="text-gray-400 font-normal text-xs">${satuan}</span></p>
                </div>
            </label>
            <!-- Input qty — muncul setelah dipilih -->
            <div id="qty_area_${p.id}" class="hidden px-3 pb-3 bg-emerald-50/60 border-t border-emerald-100">
                <input type="hidden" name="supplier_ids[]" value="${p.id}" id="hidden_id_${p.id}" disabled>
                <label class="text-[10px] text-gray-600 uppercase font-semibold mt-2 block">Jumlah yang Digunakan (${satuan})</label>
                ${isBelumTertimbang
                    ? `<input type="text" name="supplier_qty[]" id="qty_input_${p.id}" disabled
                        value="(jumlah tidak diketahui)" readonly
                        class="w-full bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-sm outline-none font-semibold text-amber-900 cursor-not-allowed mt-1">`
                    : `<input type="text" name="supplier_qty[]" id="qty_input_${p.id}" disabled
                        placeholder="Masukkan jumlah..." data-max="${p.total_stok}"
                        class="format-number w-full border border-emerald-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500 mt-1">`
                }
            </div>
            `;
            div.style.borderColor = '#d1fae5';
            container.appendChild(div);
        });
    }

    function onSelectPenampungan(selectedId, maxStok, isBelumTertimbang) {
        // Disable semua hidden input & qty input di penampungan gabungan dulu
        document.querySelectorAll('#penampunganRowContainer [id^="qty_area_"]').forEach(area => {
            area.classList.add('hidden');
        });
        document.querySelectorAll('#penampunganRowContainer [id^="hidden_id_"]').forEach(inp => {
            inp.disabled = true;
        });
        document.querySelectorAll('#penampunganRowContainer [id^="qty_input_"]').forEach(inp => {
            inp.disabled = true;
        });

        // Aktifkan yang dipilih
        const qtyArea = document.getElementById(`qty_area_${selectedId}`);
        const hiddenId = document.getElementById(`hidden_id_${selectedId}`);
        const qtyInput = document.getElementById(`qty_input_${selectedId}`);

        if (qtyArea) qtyArea.classList.remove('hidden');
        if (hiddenId) hiddenId.disabled = false;
        if (qtyInput) qtyInput.disabled = false;

        // Fokus ke input qty
        if (!isBelumTertimbang && qtyInput) {
            setTimeout(() => qtyInput.focus(), 100);
        }
    }

    function submitMulaiProduksi() {
        const form = document.querySelector('#modalMulaiProduksi form');
        const stokMethod = document.querySelector('input[name="stok_method"]:checked')?.value;
        const metode = document.querySelector('input[name="metode_produksi"]:checked')?.value;

        // Validasi: jika mode Gabungan, pastikan penampungan sudah dipilih
        if (stokMethod === 'all') {
            const penampunganRadio = document.querySelector('input[name="pilih_penampungan"]:checked');
            if (!penampunganRadio) {
                alert('⚠️ Pilih penampungan gabungan yang akan digunakan terlebih dahulu.');
                return;
            }
            // Validasi: pastikan qty terisi (jika tertimbang)
            if (metode === 'tertimbang') {
                const selectedId = penampunganRadio.value;
                const qtyInput = document.getElementById(`qty_input_${selectedId}`);
                if (!qtyInput || !qtyInput.value.trim() || qtyInput.value.trim() === '0') {
                    alert('⚠️ Masukkan jumlah bahan yang akan digunakan dari penampungan ini.');
                    if (qtyInput) qtyInput.focus();
                    return;
                }
            }
        }

        form.submit();
    }

    function addSupplierRow() {
        if (!currentStockData) return;

        const isBelumTertimbang = document.querySelector('input[name="metode_produksi"]:checked')?.value === 'belum_tertimbang';
        const stokDisplayClass = isBelumTertimbang ? 'w-24 text-center stok-col hidden' : 'w-24 text-center stok-col';
        const inputStyle = isBelumTertimbang ?
            'w-full bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-sm outline-none font-semibold text-amber-900 cursor-not-allowed' :
            'format-number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500';
        const inputVal = isBelumTertimbang ? 'value="(jumlah tidak diketahui)" readonly' : 'placeholder="0"';

        const container = document.getElementById('supplierRowContainer');
        const div = document.createElement('div');
        div.className = 'bg-white border border-gray-200 rounded-xl p-3 flex flex-wrap sm:flex-nowrap items-center gap-3 shadow-sm';

        div.innerHTML = `
        <div class="flex-1 min-w-[150px]">
            <select name="supplier_ids[]" onchange="updateRowInfo(this); refreshSupplierOptions();" class="supplier-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                <!-- Options populated by refreshSupplierOptions -->
            </select>
        </div>
        <div class="${stokDisplayClass}">
            <p class="text-[10px] text-gray-500 uppercase font-semibold">Stok (<span class="unit-label">${currentStockData.satuan}</span>)</p>
            <p class="row-stok text-sm font-bold text-gray-800">0</p>
        </div>
        <div class="flex-1 min-w-[120px]">
            <input type="text" name="supplier_qty[]" ${inputVal} class="${inputStyle}">
        </div>
        <button type="button" onclick="this.parentElement.remove(); refreshSupplierOptions();" class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
        </button>
    `;
        container.appendChild(div);
        refreshSupplierOptions();
    }

    function refreshSupplierOptions() {
        if (!currentStockData) return;

        const selects = document.querySelectorAll('.supplier-select');
        const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== '');
        const mandiriSuppliers = currentStockData.suppliers.filter(s => !s.is_gabungan && s.total_stok > 0);

        selects.forEach(select => {
            const currentValue = select.value;
            let options = mandiriSuppliers.length > 0 
                ? '<option value="">-- Pilih Supplier --</option>'
                : '<option value="">-- Tidak ada supplier dengan stok mandiri --</option>';

            mandiriSuppliers.forEach(s => {
                if (s.id == currentValue || !selectedValues.includes(String(s.id))) {
                    options += `<option value="${s.id}" data-stok="${s.total_stok}" ${s.id == currentValue ? 'selected' : ''}>${s.nama}</option>`;
                }
            });

            select.innerHTML = options;
        });
    }

    function updateRowInfo(select) {
        const row = select.parentElement.parentElement;
        const option = select.options[select.selectedIndex];
        const stok = option.dataset.stok || 0;
        const rowStokElem = row.querySelector('.row-stok');
        if (rowStokElem) {
            rowStokElem.textContent = formatNumber(Math.floor(stok));
        }
        const isBelumTertimbang = document.querySelector('input[name="metode_produksi"]:checked')?.value === 'belum_tertimbang';
        const inputQty = row.querySelector('input[name="supplier_qty[]"]');
        if (inputQty && !isBelumTertimbang) {
            inputQty.dataset.max = stok;
        }
    }

    function openProsesModal(kodeProduksi, idPembelian, nextStage, nextName, remaining, unit, metodeProduksi) {
        document.getElementById('proses_kode_produksi').value = kodeProduksi;
        document.getElementById('proses_id_pembelian').value = idPembelian;
        document.getElementById('proses_next_stage').value = nextStage;
        document.getElementById('prosesTitle').textContent = "Proses: " + nextName;

        const beratMasukInput = document.getElementById('proses_berat_masuk');
        const beratMasukHelp = document.getElementById('proses_berat_masuk_help');

        if (metodeProduksi === 'belum_tertimbang' && (remaining <= 0 || nextStage <= 1)) {
            beratMasukInput.value = "(berat tidak diketahui)";
            if (beratMasukHelp) {
                beratMasukHelp.textContent = "Berat awal bahan mentah belum tertimbang (berat tidak diketahui).";
                beratMasukHelp.className = "text-[10px] text-amber-700 font-semibold mt-1";
            }
        } else {
            beratMasukInput.value = formatNumber(Math.floor(remaining));
            if (beratMasukHelp) {
                beratMasukHelp.textContent = "Total berat akhir dari tahap sebelumnya.";
                beratMasukHelp.className = "text-[10px] text-gray-500 mt-1";
            }
        }

        // Validation for output weight
        const beratKeluarInput = document.querySelector('input[name="berat_keluar"]');
        if (metodeProduksi === 'belum_tertimbang' && remaining <= 0) {
            delete beratKeluarInput.dataset.max;
        } else {
            beratKeluarInput.dataset.max = remaining;
        }
        beratKeluarInput.value = '';

        document.querySelectorAll('.unit-proses-label').forEach(el => el.textContent = unit || 'Kg');

        document.getElementById('modalProses').classList.remove('hidden');
    }

    function closeProsesModal() {
        document.getElementById('modalProses').classList.add('hidden');
    }

    function openClosingModal(kodeProduksi, idPembelian, namaBahan, totalOutput) {
        document.getElementById('closing_kode_produksi').value = kodeProduksi;
        document.getElementById('closing_id_pembelian').value = idPembelian;
        document.getElementById('closingBatchName').textContent = kodeProduksi || ("ID #" + idPembelian);
        document.getElementById('closingMaterialName').textContent = namaBahan;
        document.getElementById('closingOutputTotal').textContent = formatNumber(Math.floor(totalOutput)) + " Kg";

        const container = document.getElementById('closingBatchHistoryContainer');
        container.innerHTML = '<div class="text-xs text-gray-400 italic py-1">Memuat riwayat batch...</div>';

        fetch('api-get-batch-history.php?id_pembelian=' + idPembelian + '&kode_produksi=' + encodeURIComponent(kodeProduksi || ''))
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="text-xs text-gray-400 italic py-1">Belum ada riwayat batch.</div>';
                    return;
                }
                data.forEach(batch => {
                    const a = document.createElement('a');
                    a.href = batch.detail_url;
                    a.target = '_blank';
                    a.className = 'block bg-amber-50/70 hover:bg-amber-100/90 border border-amber-200/80 rounded-xl p-2.5 transition group';
                    a.innerHTML = `
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-amber-900 group-hover:text-amber-950 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                ${batch.kode_produksi || ('ID #' + batch.id_pembelian)}
                            </span>
                            <span class="text-[10px] text-amber-700 group-hover:underline font-semibold flex items-center gap-0.5 shrink-0">
                                Lihat Detail
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </span>
                        </div>
                    `;
                    container.appendChild(a);
                });
            })
            .catch(err => {
                container.innerHTML = '<div class="text-xs text-red-500 py-1">Gagal memuat riwayat batch.</div>';
            });

        document.getElementById('modalClosingBatch').classList.remove('hidden');
    }

    function closeClosingModal() {
        document.getElementById('modalClosingBatch').classList.add('hidden');
    }

    function confirmBatal(kodeProduksi, idPembelian) {
        if (confirm('Apakah Anda yakin ingin membatalkan produksi ini? Semua proses untuk batch ini akan dibatalkan dan stok akan dikembalikan.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'batal-produksi.php';

            const inputKode = document.createElement('input');
            inputKode.type = 'hidden';
            inputKode.name = 'kode_produksi';
            inputKode.value = kodeProduksi;
            form.appendChild(inputKode);

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_pembelian';
            inputId.value = idPembelian;
            form.appendChild(inputId);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php include "../partials/footer.php"; ?>