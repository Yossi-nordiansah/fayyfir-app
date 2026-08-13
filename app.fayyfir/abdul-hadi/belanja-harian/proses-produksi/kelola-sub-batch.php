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

$id_pembelian = (int)($_GET['id_pembelian'] ?? 0);
if ($id_pembelian <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch info kelompok pembelian bahan baku
$sqlGroup = "
    SELECT 
        pa.id as id_pembelian,
        pa.tanggal_pembelian,
        pa.berat_awal,
        pa.harga_per_kg,
        pa.status as status_pembelian,
        bm.id as id_bahan,
        bm.nama_bahan,
        bm.satuan,
        s.nama_supplier,
        pn.nama_penampungan
    FROM bb_pembelian_awal pa
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_supplier s ON s.id = pa.id_supplier
    LEFT JOIN bb_penampungan_detail pnd ON pnd.id_pembelian = pa.id
    LEFT JOIN bb_penampungan pn ON pn.id = pnd.id_penampungan
    WHERE pa.id = ?
    LIMIT 1
";
$stmtG = $conn->prepare($sqlGroup);
$stmtG->bind_param("i", $id_pembelian);
$stmtG->execute();
$groupInfo = $stmtG->get_result()->fetch_assoc();

if (!$groupInfo) {
    header("Location: index.php");
    exit();
}

/** Helpers copied for consistency **/
function get_proses_by_urutan($conn, $id_bahan, $urutan)
{
    $sql = "SELECT id, nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
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

function get_stage_name($conn, $id_bahan, $urutan)
{
    $stmt = $conn->prepare("SELECT nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1");
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['nama_proses'] ?? '-';
}

// Fetch list sub-batch untuk kelompok id_pembelian ini
$querySubBatches = "
    SELECT 
        COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) as batch_key,
        MAX(pd.kode_produksi) as kode_produksi,
        MIN(pd.id_pembelian) as sample_id_pembelian,
        MAX(bm.id) as id_bahan,
        MAX(bm.nama_bahan) as nama_bahan,
        MAX(bm.satuan) as satuan,
        COALESCE(MAX(pd.metode_produksi), 'belum_tertimbang') as metode_produksi,
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
        MIN(pd.created_at) as created_at
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
    LEFT JOIN (
        SELECT COALESCE(pd3.kode_produksi, CONCAT('SINGLE-', pd3.id_pembelian)) as bk3, COALESCE(MAX(pm3.urutan_tahap), 0) as max_urutan
        FROM bb_proses_detail pd3
        LEFT JOIN bb_proses_master pm3 ON pm3.id = pd3.id_proses_master
        GROUP BY bk3
    ) last_stage ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_stage.bk3
    WHERE pd.id_pembelian = ?
    GROUP BY batch_key
    ORDER BY MIN(pd.created_at) ASC
";
$stmtSB = $conn->prepare($querySubBatches);
$stmtSB->bind_param("i", $id_pembelian);
$stmtSB->execute();
$resultSubBatches = $stmtSB->get_result();

$sumber_nama = !empty($groupInfo['nama_penampungan']) 
    ? 'Penampungan: ' . htmlspecialchars($groupInfo['nama_penampungan']) . ' [GABUNGAN]'
    : (!empty($groupInfo['nama_supplier']) ? 'Supplier: ' . htmlspecialchars($groupInfo['nama_supplier']) : 'Pembelian #' . $id_pembelian);
?>

<?php
$activeMenu = "productions";
$activeModule = "Kelola Sub-Batch Belum Tertimbang";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
    <!-- Breadcrumb & Nav Header -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-gray-800 transition mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Dashboard Produksi
        </a>
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Kelola Sub-Batch Produksi</h1>
                <p class="text-xs text-amber-800 font-medium flex items-center gap-1.5 mt-1">
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        Belum Tertimbang (Group)
                    </span>
                    <?= $sumber_nama ?>
                </p>
            </div>
            <div class="flex gap-2 flex-wrap sm:flex-nowrap">
                <button onclick="openTambahSubBatchModal()"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    + Tambah Sub-Batch
                </button>
                <button onclick="openClosingModal('', <?= $id_pembelian ?>, '<?= htmlspecialchars(addslashes($groupInfo['nama_bahan'])) ?>', 0)"
                    class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-sm transition" title="Gudang Habis - Hitung HPP Final Akurat & Opname">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Hitung Akurat (Gudang Habis)
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Information Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
            <p class="text-gray-400 font-medium">Bahan Baku:</p>
            <p class="text-sm font-bold text-gray-800 mt-0.5"><?= htmlspecialchars($groupInfo['nama_bahan']) ?></p>
        </div>
        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
            <p class="text-gray-400 font-medium">Sumber / Asal Bahan:</p>
            <p class="text-sm font-bold text-gray-800 mt-0.5"><?= $sumber_nama ?></p>
        </div>
        <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-100">
            <p class="text-amber-700 font-medium">Status Stok Mentah:</p>
            <p class="text-sm font-bold text-amber-900 mt-0.5">Tumpukan Gudang Berjalan (Belum Tertimbang)</p>
        </div>
    </div>

    <!-- Table Sub-Batches -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800 text-sm">Daftar Sub-Batch di Kelompok Ini</h3>
            <span class="text-xs text-gray-500 font-medium">Total: <?= $resultSubBatches ? $resultSubBatches->num_rows : 0 ?> Sub-Batch</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-800 text-yellow-400 font-semibold uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-3">Kode Sub-Batch</th>
                        <th class="px-6 py-3">Tanggal Dibuat</th>
                        <th class="px-6 py-3">Tahap Saat Ini</th>
                        <th class="px-6 py-3 text-right">Hasil Tahap (Terakhir)</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($resultSubBatches && $resultSubBatches->num_rows > 0): ?>
                        <?php while ($sub = $resultSubBatches->fetch_assoc()):
                            $kode_sub        = $sub['kode_produksi'];
                            $is_dihentikan   = (bool)$sub['is_dihentikan'];
                            $is_dibatalkan   = (bool)$sub['is_dibatalkan'];
                            $is_stopped      = $is_dihentikan || $is_dibatalkan;
                            $tahap_nama      = ($sub['current_tahap_urutan'] == 0) ? 'Persiapan' : get_stage_name($conn, $groupInfo['id_bahan'], $sub['current_tahap_urutan']);
                            $next_urutan     = $is_stopped ? null : get_next_urutan($conn, $groupInfo['id_bahan'], $sub['current_tahap_urutan']);
                            $next_process    = $next_urutan ? get_proses_by_urutan($conn, $groupInfo['id_bahan'], $next_urutan) : null;
                            $remaining       = (float)$sub['total_berat_akhir_tahap_ini'];
                            $row_class       = $is_dibatalkan ? 'bg-gray-100 opacity-75' : ($is_dihentikan ? 'bg-red-50' : 'hover:bg-gray-50');
                        ?>
                            <tr class="<?= $row_class ?> transition">
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    <?= htmlspecialchars($kode_sub ?: ('ID #' . $sub['sample_id_pembelian'])) ?>
                                    <?php if ($is_dihentikan): ?>
                                        <span class="block text-[10px] text-red-600 font-bold">Dihentikan</span>
                                    <?php endif; ?>
                                    <?php if ($is_dibatalkan): ?>
                                        <span class="block text-[10px] text-gray-400 line-through">Dibatalkan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <?= date('d M Y, H:i', strtotime($sub['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($is_dibatalkan): ?>
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-400 text-[10px] font-semibold uppercase">Dibatalkan</span>
                                    <?php elseif ($is_dihentikan): ?>
                                        <span class="px-2.5 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-semibold uppercase"><?= htmlspecialchars($tahap_nama) ?> (Dihentikan)</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold uppercase"><?= htmlspecialchars($tahap_nama) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right font-bold">
                                    <?= ($sub['current_tahap_urutan'] == 0 || $remaining <= 0) ? '(berat belum diketahui)' : number_format($remaining, 0, ',', '.') . ' ' . htmlspecialchars($sub['satuan']) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2 flex-wrap">
                                        <?php if (!$is_stopped): ?>
                                            <?php if ($next_urutan): ?>
                                                <button onclick="openProsesModal('<?= $kode_sub ?>', <?= $id_pembelian ?>, <?= $next_urutan ?>, '<?= htmlspecialchars(addslashes($next_process['nama_proses'])) ?>', <?= $remaining ?>, '<?= htmlspecialchars($sub['satuan']) ?>', 'belum_tertimbang')"
                                                    class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition">
                                                    Proses: <?= htmlspecialchars($next_process['nama_proses']) ?>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="confirmBatal('<?= $kode_sub ?>', <?= $id_pembelian ?>)"
                                                class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 transition">
                                                Batal
                                            </button>
                                        <?php endif; ?>
                                        <a href="detail-penyusutan.php?id=<?= $id_pembelian ?>&kode_produksi=<?= urlencode($kode_sub) ?>"
                                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs transition">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada sub-batch di kelompok produksi ini. Tekan tombol <strong>+ Tambah Sub-Batch</strong> untuk memulai sub-batch baru.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Tambah Sub-Batch Baru -->
<div id="modalTambahSubBatch" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50">
            <div>
                <h3 class="text-lg font-bold text-blue-900">+ Tambah Sub-Batch Baru</h3>
                <p class="text-xs text-blue-700">Sub-batch baru untuk kelompok <?= $sumber_nama ?></p>
            </div>
            <button onclick="closeTambahSubBatchModal()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="tambah-sub-batch.php" method="POST" class="p-6">
            <input type="hidden" name="id_pembelian" value="<?= $id_pembelian ?>">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Sub-Batch</label>
                <input type="date" name="tanggal_proses" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="mb-4 bg-amber-50 p-3 rounded-xl border border-amber-200 text-xs text-amber-900">
                📌 <strong>Metode: Belum Tertimbang</strong><br>
                Sub-batch ini akan langsung terdaftar ke tahap awal (Persiapan) menggunakan stok mentah dari kelompok ini.
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Sub-Batch (Opsional)</label>
                <textarea name="catatan" class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" rows="2" placeholder="Catatan opsional..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeTambahSubBatchModal()" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition">Buat Sub-Batch</button>
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
            <input type="hidden" name="redirect_to" value="kelola-sub-batch.php?id_pembelian=<?= $id_pembelian ?>">

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
                    <span>Kelompok / Sumber:</span>
                    <strong id="closingBatchName"><?= $sumber_nama ?></strong>
                </div>
                <div class="flex justify-between">
                    <span>Bahan Baku Terikat:</span>
                    <strong id="closingMaterialName"><?= htmlspecialchars($groupInfo['nama_bahan']) ?></strong>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Riwayat Sub-Batch Dilalui:</label>
                <div id="closingBatchHistoryContainer" class="space-y-2 max-h-44 overflow-y-auto pr-1">
                    <div class="text-xs text-gray-400 italic py-1">Memuat riwayat sub-batch...</div>
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

<script>
    // Format number helper
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    function unformatNumber(str) {
        return str.toString().replace(/\./g, "");
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('format-number')) {
            let rawVal = unformatNumber(e.target.value);
            if (!isNaN(rawVal) && rawVal !== "") {
                e.target.value = formatNumber(rawVal);
            }
        }
    });

    function openTambahSubBatchModal() {
        document.getElementById('modalTambahSubBatch').classList.remove('hidden');
    }
    function closeTambahSubBatchModal() {
        document.getElementById('modalTambahSubBatch').classList.add('hidden');
    }

    function openProsesModal(kodeProduksi, idPembelian, nextStage, nextName, remaining, unit, metodeProduksi) {
        document.getElementById('proses_kode_produksi').value = kodeProduksi;
        document.getElementById('proses_id_pembelian').value = idPembelian;
        document.getElementById('proses_next_stage').value = nextStage;
        document.getElementById('prosesTitle').textContent = "Proses: " + nextName;

        const beratMasukInput = document.getElementById('proses_berat_masuk');
        const beratMasukHelp  = document.getElementById('proses_berat_masuk_help');

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

        const container = document.getElementById('closingBatchHistoryContainer');
        container.innerHTML = '<div class="text-xs text-gray-400 italic py-1">Memuat riwayat sub-batch...</div>';

        fetch('api-get-batch-history.php?id_pembelian=' + idPembelian)
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="text-xs text-gray-400 italic py-1">Belum ada riwayat sub-batch.</div>';
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
                container.innerHTML = '<div class="text-xs text-red-500 py-1">Gagal memuat riwayat sub-batch.</div>';
            });

        document.getElementById('modalClosingBatch').classList.remove('hidden');
    }

    function closeClosingModal() {
        document.getElementById('modalClosingBatch').classList.add('hidden');
    }

    function confirmBatal(kodeProduksi, idPembelian) {
        if (confirm('Apakah Anda yakin ingin membatalkan sub-batch ini?')) {
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

            const inputRedirect = document.createElement('input');
            inputRedirect.type = 'hidden';
            inputRedirect.name = 'redirect_to';
            inputRedirect.value = 'kelola-sub-batch.php?id_pembelian=' + idPembelian;
            form.appendChild(inputRedirect);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php include "../partials/footer.php"; ?>
