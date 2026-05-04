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
function get_proses_by_urutan($conn, $id_bahan, $urutan) {
  $sql = "SELECT id, nama_proses FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id_bahan, $urutan);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  return $res;
}

function get_min_urutan($conn, $id_bahan) {
  $sql = "SELECT MIN(urutan_tahap) as min_u FROM bb_proses_master WHERE id_bahan = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id_bahan);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return $row['min_u'] !== null ? (int)$row['min_u'] : null;
}

function get_next_urutan($conn, $id_bahan, $urutan) {
  $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap > ? ORDER BY urutan_tahap ASC LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id_bahan, $urutan);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return $row ? (int)$row['urutan_tahap'] : null;
}

function get_prev_urutan($conn, $id_bahan, $urutan) {
    $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap < ? ORDER BY urutan_tahap DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_bahan, $urutan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['urutan_tahap'] : null;
}

function calc_remaining_for_next_stage($conn, $idPembelian, $nextStage) {
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

// Ambil list produksi aktif (yang sudah masuk bb_proses_detail tapi belum selesai)
$queryProduksi = "
    SELECT 
        COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) as batch_key,
        MAX(pd.kode_produksi) as kode_produksi,
        MIN(pd.id_pembelian) as sample_id_pembelian,
        MAX(pa.kode_batch) as sample_batch_pembelian,
        MAX(bm.id) as id_bahan,
        MAX(bm.nama_bahan) as nama_bahan,
        MAX(bm.satuan) as satuan,
        COALESCE(MAX(pm.urutan_tahap), 0) as current_tahap_urutan,
        -- Total Berat Akhir dari tahap TERAKHIR
        SUM(CASE 
            WHEN last_stage.max_urutan = 0 THEN pd.berat_masuk 
            WHEN COALESCE(pm.urutan_tahap, 0) = last_stage.max_urutan THEN pd.berat_keluar 
            ELSE 0 
        END) as total_berat_akhir_tahap_ini,
        COUNT(DISTINCT pd.id_pembelian) as total_suppliers
    FROM bb_proses_detail pd
    JOIN bb_pembelian_awal pa ON pa.id = pd.id_pembelian
    JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
    LEFT JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
    JOIN (
        SELECT COALESCE(pd3.kode_produksi, CONCAT('SINGLE-', pd3.id_pembelian)) as bk3, COALESCE(MAX(pm3.urutan_tahap), 0) as max_urutan
        FROM bb_proses_detail pd3
        LEFT JOIN bb_proses_master pm3 ON pm3.id = pd3.id_proses_master
        GROUP BY bk3
    ) last_stage ON COALESCE(pd.kode_produksi, CONCAT('SINGLE-', pd.id_pembelian)) = last_stage.bk3
    WHERE pa.status != 'selesai_siap_jual'
    GROUP BY batch_key
    ORDER BY MAX(pd.created_at) DESC
";
$resultProduksi = $conn->query($queryProduksi);
?>

<?php
/** Helper to get current stage name inside the loop since subqueries in SELECT with GROUP BY can be tricky **/
function get_stage_name($conn, $id_bahan, $urutan) {
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
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Active Production Table -->
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
                            $kode_produksi = $row['kode_produksi'];
                            $id_pembelian = $row['sample_id_pembelian'];
                            $id_bahan = $row['id_bahan'];
                            $current_tahap_nama = ($row['current_tahap_urutan'] == 0) ? 'Persiapan' : get_stage_name($conn, $id_bahan, $row['current_tahap_urutan']);
                            
                            $next_urutan = get_next_urutan($conn, $id_bahan, $row['current_tahap_urutan']);
                            $next_process = $next_urutan ? get_proses_by_urutan($conn, $id_bahan, $next_urutan) : null;
                            
                            // Remaining is total_berat_akhir_tahap_ini
                            $remaining = (float)$row['total_berat_akhir_tahap_ini'];
                            $total_tersisa_grand += $remaining;
                        ?>
                            <tr class="data-row hover:bg-gray-50 transition" data-remaining="<?= $remaining ?>">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= $kode_produksi ?: $row['sample_batch_pembelian'] ?></div>
                                    <div class="text-[10px] text-gray-500">
                                        <?= $row['total_suppliers'] ?> Supplier | <?= $kode_produksi ? 'Batch Produksi' : 'Single Purchase' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold uppercase">
                                        <?= htmlspecialchars($current_tahap_nama) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold"><?= number_format($remaining, 0, ',', '.') ?> <?= htmlspecialchars($row['satuan']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <?php if ($next_urutan): ?>
                                            <button onclick="openProsesModal('<?= $kode_produksi ?>', <?= $id_pembelian ?>, <?= $next_urutan ?>, '<?= htmlspecialchars(addslashes($next_process['nama_proses'])) ?>', <?= $remaining ?>, '<?= htmlspecialchars($row['satuan']) ?>')"
                                                class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition">
                                                Proses: <?= htmlspecialchars($next_process['nama_proses']) ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="/app.fayyfir/abdul-hadi/belanja-harian/proses-produksi/detail-penyusutan.php?id=<?= $id_pembelian ?><?= $kode_produksi ? '&kode_produksi='.$kode_produksi : '' ?>"
                                            class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs hover:bg-purple-700 transition" title="Detail Penyusutan & HPP">
                                            Detail
                                        </a>
                                        <button onclick="confirmBatal('<?= $kode_produksi ?>', <?= $id_pembelian ?>)"
                                            class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 transition">
                                            Batal
                                        </button>
                                    </div>
                                </td>
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
                        $resBahan = $conn->query("SELECT id, nama_bahan FROM bb_bahan_master WHERE deleted_at IS NULL");
                        while($b = $resBahan->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['nama_bahan']}</option>";
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Produksi</label>
                    <input type="date" name="tanggal_proses" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
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
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition">Simpan Produksi</button>
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
                <input type="text" name="berat_masuk" id="proses_berat_masuk" required readonly class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 outline-none">
                <p class="text-[10px] text-gray-500 mt-1">Total berat akhir dari tahap sebelumnya.</p>
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

<script src="../assets/js/table-pagination.js"></script>
<script>
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

    penampunganList.forEach(p => {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 border border-emerald-200 rounded-xl p-3 flex flex-wrap sm:flex-nowrap items-center gap-3';
        div.innerHTML = `
            <div class="flex-1 min-w-[150px]">
                <p class="text-xs text-gray-500 uppercase font-semibold">Penampungan</p>
                <p class="text-sm font-bold text-emerald-700">${p.nama.replace(' [GABUNGAN]', '')}</p>
                <input type="hidden" name="supplier_ids[]" value="${p.id}">
            </div>
            <div class="w-28 text-center">
                <p class="text-[10px] text-gray-500 uppercase font-semibold">Stok (<span class="unit-label">${currentStockData.satuan}</span>)</p>
                <p class="text-sm font-bold text-gray-800">${formatNumber(Math.floor(p.total_stok))}</p>
            </div>
            <div class="flex-1 min-w-[100px]">
                <label class="text-[10px] text-gray-500 uppercase font-semibold">Gunakan</label>
                <input type="text" name="supplier_qty[]" placeholder="0"
                    data-max="${p.total_stok}"
                    class="format-number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
        `;
        container.appendChild(div);
    });
}

function addSupplierRow() {
    if (!currentStockData) return;
    
    const container = document.getElementById('supplierRowContainer');
    const div = document.createElement('div');
    div.className = 'bg-white border border-gray-200 rounded-xl p-3 flex flex-wrap sm:flex-nowrap items-center gap-3 shadow-sm';
    
    div.innerHTML = `
        <div class="flex-1 min-w-[150px]">
            <select name="supplier_ids[]" onchange="updateRowInfo(this); refreshSupplierOptions();" class="supplier-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                <!-- Options populated by refreshSupplierOptions -->
            </select>
        </div>
        <div class="w-24 text-center">
            <p class="text-[10px] text-gray-500 uppercase font-semibold">Stok (<span class="unit-label">${currentStockData.satuan}</span>)</p>
            <p class="row-stok text-sm font-bold text-gray-800">0</p>
        </div>
        <div class="flex-1 min-w-[100px]">
            <input type="text" name="supplier_qty[]" placeholder="0" class="format-number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
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

    selects.forEach(select => {
        const currentValue = select.value;
        let options = '<option value="">-- Pilih Supplier --</option>';
        
        currentStockData.suppliers.forEach(s => {
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
    row.querySelector('.row-stok').textContent = formatNumber(Math.floor(stok));
    row.querySelector('input[name="supplier_qty[]"]').dataset.max = stok;
}

function openProsesModal(kodeProduksi, idPembelian, nextStage, nextName, remaining, unit) {
    document.getElementById('proses_kode_produksi').value = kodeProduksi;
    document.getElementById('proses_id_pembelian').value = idPembelian;
    document.getElementById('proses_next_stage').value = nextStage;
    document.getElementById('proses_berat_masuk').value = formatNumber(Math.floor(remaining));
    document.getElementById('prosesTitle').textContent = "Proses: " + nextName;
    
    // Validation for output weight
    const beratKeluarInput = document.querySelector('input[name="berat_keluar"]');
    beratKeluarInput.dataset.max = remaining;
    beratKeluarInput.value = ''; 
    
    document.querySelectorAll('.unit-proses-label').forEach(el => el.textContent = unit || 'Kg');
    
    document.getElementById('modalProses').classList.remove('hidden');
}
function closeProsesModal() {
    document.getElementById('modalProses').classList.add('hidden');
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