<?php
session_start();
require "../../config.php";
$conn = $conn2; // koneksi database aktif
require "../includes/helpers.php";

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

/**
 * Helper: ambil nama proses berdasarkan urutan tahap
 */
function get_proses_by_urutan($conn, $urutan)
{
  $urutan = (int)$urutan;
  $sql = "SELECT id, nama_proses FROM bb_proses_master WHERE urutan_tahap = ? LIMIT 1";
  $id = 0;
  $nama = "";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $urutan);
    $stmt->execute();
    $stmt->bind_result($id, $nama);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? ['id' => $id, 'nama_proses' => $nama] : null;
  }
  return null;
}

/**
 * Helper: ekstrak nomor tahap dari status 'tahapN' -> N (int)
 * status 'uang_terbayar' / 'load' => 0
 */
function get_stage_from_status($status)
{
  if (!$status) return 0;
  if (preg_match('/^tahap(\d+)$/', $status, $m)) {
    return (int)$m[1];
  }
  return 0;
}

function get_min_urutan($conn)
{
  $res = $conn->query("SELECT MIN(urutan_tahap) as min_u FROM bb_proses_master");
  if ($res && $row = $res->fetch_assoc()) {
    return $row['min_u'] !== null ? (int)$row['min_u'] : null;
  }
  return null;
}

function get_prev_urutan($conn, $urutan)
{
  $urutan = (int)$urutan;
  $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE urutan_tahap < ? ORDER BY urutan_tahap DESC LIMIT 1";
  $u = null;
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $urutan);
    $stmt->execute();
    $stmt->bind_result($u);
    $stmt->fetch();
    $stmt->close();
  }
  return $u;
}

function get_next_urutan($conn, $urutan)
{
  $urutan = (int)$urutan;
  $sql = "SELECT urutan_tahap FROM bb_proses_master WHERE urutan_tahap > ? ORDER BY urutan_tahap ASC LIMIT 1";
  $u = null;
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $urutan);
    $stmt->execute();
    $stmt->bind_result($u);
    $stmt->fetch();
    $stmt->close();
  }
  return $u;
}

function get_last_recorded_urutan($conn, $idPembelian)
{
  $idPembelian = (int)$idPembelian;
  $sql = "SELECT MAX(pm.urutan_tahap) as max_u FROM bb_proses_detail pd
            JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
            WHERE pd.id_pembelian = ?";
  $u = null;
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $idPembelian);
    $stmt->execute();
    $stmt->bind_result($u);
    $stmt->fetch();
    $stmt->close();
  }
  return $u;
}

/**
 * Hitung sisa available untuk diproses di tahap berikutnya
 * Logic:
 *  - next_stage = current_stage + 1
 *  - if next_stage == 1:
 *       remaining = berat_awal - SUM(berat_masuk WHERE urutan_tahap = 1)
 *  - else:
 *       previous_stage_output = SUM(berat_keluar WHERE urutan_tahap = next_stage-1)
 *       next_stage_already_consumed = SUM(berat_masuk WHERE urutan_tahap = next_stage)
 *       remaining = previous_stage_output - next_stage_already_consumed
 */
function calc_remaining_for_next_stage($conn, $idPembelian, $nextStage)
{
  $idPembelian = (int)$idPembelian;
  $nextStage = (int)$nextStage;
  if ($nextStage <= 0) return 0;

  $min_urutan = get_min_urutan($conn);

  if ($nextStage === $min_urutan) {
    // Jika tahap ini adalah tahap paling awal, gunakan berat_awal sebagai input
    $sql = "SELECT COALESCE(berat_awal,0) AS berat_awal FROM bb_pembelian_awal WHERE id = ? LIMIT 1";
    $berat_awal = 0;
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("i", $idPembelian);
      $stmt->execute();
      $stmt->bind_result($ba);
      if ($stmt->fetch()) $berat_awal = (float)$ba;
      $stmt->close();
    }
    // total yang sudah masuk ke tahap ini
    $sql2 = "SELECT COALESCE(SUM(pd.berat_masuk),0) AS processed FROM bb_proses_detail pd
                 JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                 WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
    $processed = 0;
    if ($stmt = $conn->prepare($sql2)) {
      $stmt->bind_param("ii", $idPembelian, $nextStage);
      $stmt->execute();
      $stmt->bind_result($pr);
      if ($stmt->fetch()) $processed = (float)$pr;
      $stmt->close();
    }
    $remaining = $berat_awal - $processed;
    return max(0, round($remaining, 2));
  } else {
    // previous stage output
    $prev = get_prev_urutan($conn, $nextStage);
    if ($prev === null) return 0;

    $sqlPrevOut = "SELECT COALESCE(SUM(pd.berat_keluar),0) AS prev_out FROM bb_proses_detail pd
                       JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                       WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
    $prev_out = 0;
    if ($stmt = $conn->prepare($sqlPrevOut)) {
      $stmt->bind_param("ii", $idPembelian, $prev);
      $stmt->execute();
      $stmt->bind_result($po);
      if ($stmt->fetch()) $prev_out = (float)$po;
      $stmt->close();
    }
    // already consumed in nextStage
    $sqlConsumed = "SELECT COALESCE(SUM(pd.berat_masuk),0) AS consumed FROM bb_proses_detail pd
                        JOIN bb_proses_master pm ON pm.id = pd.id_proses_master
                        WHERE pd.id_pembelian = ? AND pm.urutan_tahap = ?";
    $consumed = 0;
    if ($stmt = $conn->prepare($sqlConsumed)) {
      $stmt->bind_param("ii", $idPembelian, $nextStage);
      $stmt->execute();
      $stmt->bind_result($co);
      if ($stmt->fetch()) $consumed = (float)$co;
      $stmt->close();
    }
    $remaining = $prev_out - $consumed;
    return max(0, round($remaining, 2));
  }
}

// Ambil data pembelian awal (tampilkan semua status kecuali selesai_siap_jual)
$query = "SELECT p.*, s.nama_supplier AS supplier_nama, bm.nama_bahan AS bahan_nama, bm.satuan AS bahan_satuan
          FROM bb_pembelian_awal p
          LEFT JOIN bb_supplier s ON p.id_supplier = s.id
          LEFT JOIN bb_bahan_master bm ON p.id_bahan = bm.id
          WHERE p.status IS NULL OR p.status != 'selesai_siap_jual'
          ORDER BY p.tanggal_pembelian DESC, p.id DESC";
$result = $conn->query($query);

// Ambil urutan tahap tertinggi untuk menentukan kapan proses selesai
$res_max = $conn->query("SELECT MAX(urutan_tahap) as max_tahap FROM bb_proses_master");
$max_tahap = $res_max ? (int)$res_max->fetch_assoc()['max_tahap'] : 0;

$activeMenu = "purchases";
$activeModule = "Daftar Pembelian Awal";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="mb-4 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 text-sm rounded shadow-sm">
      ✅ Data pembelian dan seluruh riwayat terkait berhasil dihapus.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'deletefailed'): ?>
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm rounded shadow-sm">
      ❌ Gagal menghapus data. Data kemungkinan sedang digunakan dalam proses lain yang tidak bisa dihapus otomatis.
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m10-9l2 9m-6-9v9" />
      </svg>
      Daftar Pembelian Awal
    </h1>

    <div class="flex flex-col sm:flex-row gap-2 mt-4 sm:mt-0">
      <a href="load-bahan"
        class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-yellow-400 px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
          stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Bahan
      </a>
    </div>
  </div>

  <!-- Card -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <div class="p-4 flex justify-between items-center flex-wrap gap-2">
      <input id="searchInput" type="text" placeholder="Cari di sini..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">
      <div class="text-sm">
        Tampilkan
        <select id="rowsPerPage" class="border border-gray-300 rounded px-2 py-1">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
        baris
      </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
      <div class="overflow-x-auto pb-8">
        <table class="w-full text-sm text-gray-700">
          <thead class="bg-gray-800 text-yellow-400 text-center text-lg tracking-wider">
            <tr class="whitespace-nowrap">
              <th class="px-6 py-3 font-semibold">#</th>
              <th class="px-6 py-3 font-semibold">Tanggal</th>
              <th class="px-6 py-3 font-semibold">Batch</th>
              <th class="px-6 py-3 font-semibold">Supplier</th>
              <th class="px-6 py-3 font-semibold">Bahan</th>
              <th class="px-6 py-3 font-semibold text-center">Satuan</th>
              <th class="px-6 py-3 font-semibold text-center">Berat</th>
              <th class="px-6 py-3 font-semibold text-center">Harga/Satuan (Rp)</th>
              <th class="px-6 py-3 font-semibold text-center">Total (Rp)</th>
              <th class="px-6 py-3 font-semibold">Status</th>
              <th class="px-6 py-3 font-semibold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody id="materialTable" class="divide-y divide-gray-100">
            <?php
            $no = 1;
            $grand_total = 0.0; // inisialisasi total keseluruhan
            $total_berat = 0.0;
            while ($row = $result->fetch_assoc()):
              // safety cast
              $id = (int)$row['id'];
              $berat_awal = isset($row["berat_awal"]) ? (float)$row["berat_awal"] : 0.0;
              $harga_per_kg = isset($row["harga_per_kg"]) ? (float)$row["harga_per_kg"] : 0.0;

              $total = $berat_awal * $harga_per_kg;
              $total_berat += $berat_awal;
              $grand_total += $total; // akumulasi total di sini

              // status mapping text
              $status = $row['status'] ?? 'load';

              // DETEKSI TAHAP AKTUAL berdasarkan LOG
              $actual_current_urutan = get_last_recorded_urutan($conn, $id);
              $is_new_batch = ($actual_current_urutan === null);

              $status_label = '';
              if ($status === 'selesai_siap_jual') {
                $status_label = 'Selesai / Siap Jual';
              } elseif ($is_new_batch) {
                $status_label = ($status === 'load') ? 'Load' : 'Uang Dibayar';
              } else {
                $stage_info = get_proses_by_urutan($conn, $actual_current_urutan);
                $status_label = $stage_info ? $stage_info['nama_proses'] : ('Tahap ' . $actual_current_urutan);
              }

              // tentukan next stage
              if ($is_new_batch) {
                $next_stage = get_min_urutan($conn);
              } else {
                $next_stage = get_next_urutan($conn, $actual_current_urutan);
              }

              // cari proses master untuk next_stage (jika ada)
              $next_process = $next_stage ? get_proses_by_urutan($conn, $next_stage) : null;
              $next_process_name = $next_process['nama_proses'] ?? ($next_stage ? ('Tahap ' . $next_stage) : '-');

              // hitung remaining untuk next stage
              $remaining_for_next = $next_stage ? calc_remaining_for_next_stage($conn, $id, $next_stage) : 0;
            ?>
              <tr class="data-row hover:bg-gray-50 whitespace-nowrap transition" 
                  data-berat="<?= $berat_awal ?>" 
                  data-total="<?= $total ?>">
                <td class="px-6 py-3 font-medium text-gray-800"><?= $no++ ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars(format_tanggal($row['tanggal_pembelian'])) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["kode_batch"] ?? "-") ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["supplier_nama"] ?? "-") ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row["bahan_nama"] ?? "-") ?></td>
                <td class="px-6 py-3 text-center"><?= htmlspecialchars($row["bahan_satuan"] ?? "-") ?></td>
                <td class="px-6 py-3 text-center"><?= number_format($berat_awal, 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-center"><?= number_format($harga_per_kg, 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-center font-semibold text-emerald-700"><?= number_format($total, 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-center font-medium">
                  <!-- Payment Status Only -->
                  <?php 
                    $pStatus = $row['status_pembayaran'] ?? 'belum_dibayar';
                    if ($pStatus === 'lunas'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-[10px] uppercase font-bold bg-emerald-600 text-white">Lunas</span>
                  <?php elseif ($pStatus === 'dp'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-[10px] uppercase font-bold bg-orange-500 text-white">DP</span>
                  <?php elseif ($pStatus === 'belum_lunas'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-[10px] uppercase font-bold bg-red-600 text-white">Belum Lunas</span>
                  <?php else: ?>
                    <span class="inline-block px-3 py-1 rounded-full text-[10px] uppercase font-bold bg-red-500 text-white">Belum Bayar</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="detail-pembelian.php?id=<?= $id ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition" title="Detail">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </a>

                    <a href="edit-pembelian.php?id=<?= $id ?>"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-lg transition" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </a>

                    <!-- Tombol hapus -->
                    <a href="hapus-pembelian.php?id=<?= $id ?>"
                      onclick="return confirm('Apakah Anda yakin ingin menghapus data pembelian ini? Seluruh riwayat proses terkait juga akan dihapus.')"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>

          <tfoot class="bg-gray-800 text-yellow-400 text-center text-lg tracking-wider font-semibold">
            <tr class="whitespace-nowrap">
              <td colspan="6" class="px-6 py-3 text-right">Grand Total</td>
              <td id="footerBeratTotal" class="px-6 py-3 text-right"><?= number_format($total_berat, 0, ',', '.') ?></td>
              <td></td>
              <td id="footerGrandTotal" class="px-6 py-3 text-right">
                <?= number_format($grand_total, 0, ',', '.') ?>
              </td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="p-10 text-center text-gray-500 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3 text-gray-400" fill="none"
          viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 6v6l4 2m6 4a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Belum ada data pembelian awal yang tercatat.
      </div>
    <?php endif; ?>

    <div class="p-4 flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>

  </div>
</main>

<!-- Overlay -->
<div id="modalOverlay" class="hidden fixed inset-0 bg-black bg-opacity-40 z-40"></div>

<!-- MODAL GENERIK PROSES -->
<div id="prosesModal"
  class="hidden fixed inset-0 flex items-center justify-center z-50 px-4">

  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

    <h2 id="modalTitle" class="text-lg font-semibold mb-4">Berat yang akan diproses</h2>

    <form id="formProses" method="POST" action="proses-ke-tahap.php">

      <input type="hidden" name="id_pembelian" id="id_pembelian" value="">
      <input type="hidden" name="next_stage" id="next_stage" value="">
      <input type="hidden" name="available_for_next" id="available_for_next" value="0">

      <label class="block mb-2 font-medium">Berat yang akan diproses (Kg)</label>
      <input
        type="number"
        step="0.01"
        name="berat_masuk"
        id="berat_masuk"
        placeholder="Masukan berat dalam Kg"
        required
        min="0.01"
        class="w-full border rounded px-3 py-2 mb-3">

      <label class="block mb-2 font-medium">Berat setelah proses (Kg)</label>
      <input
        type="number"
        step="0.01"
        name="berat_keluar"
        id="berat_keluar"
        placeholder="Masukan berat setelah proses (hasil)"
        required
        min="0"
        class="w-full border rounded px-3 py-2 mb-3">

      <label class="block mb-2 font-medium">Catatan (opsional)</label>
      <textarea name="catatan" class="w-full border rounded px-3 py-2 mb-3" rows="3" placeholder="Catatan proses..."></textarea>

      <div class="text-sm text-gray-600 mb-3">
        <strong>Available untuk diproses:</strong> <span id="availableText">0.00</span> Kg
      </div>

      <div class="flex justify-end gap-3">
        <button type="button"
          onclick="closeModal()"
          class="px-4 py-2 bg-gray-300 rounded">
          Batal
        </button>

        <button type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded">
          Simpan Proses
        </button>
      </div>
    </form>

  </div>
</div>

<script src="../assets/js/table-pagination.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    initTablePagination({
      tableId: "materialTable",
      rowsPerPageId: "rowsPerPage",
      searchInputId: "searchInput",
      paginationId: "paginationControls",
      infoId: "totalRowsInfo",
      onUpdate: function(visibleRows) {
        let totalBerat = 0;
        let grandTotal = 0;

        visibleRows.forEach(row => {
            totalBerat += parseFloat(row.getAttribute('data-berat')) || 0;
            grandTotal += parseFloat(row.getAttribute('data-total')) || 0;
        });

        document.getElementById('footerBeratTotal').textContent = new Intl.NumberFormat('id-ID').format(totalBerat);
        document.getElementById('footerGrandTotal').textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
      }
    });
  });

  // openModal now accepts nextStage & remaining available
  function openModal(id, nextStage, nextProcessName, available) {
    document.getElementById("id_pembelian").value = parseInt(id, 10);
    document.getElementById("next_stage").value = parseInt(nextStage, 10);
    document.getElementById("available_for_next").value = parseFloat(available);
    document.getElementById("modalTitle").textContent = "Proses: " + nextProcessName;
    document.getElementById("availableText").textContent = parseFloat(available).toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    // Otomatis isikan data yang tersedia (Available) ke field berat_masuk
    document.getElementById("berat_masuk").value = parseFloat(available);
    document.getElementById("berat_keluar").value = '';

    document.getElementById("modalOverlay").classList.remove("hidden");
    document.getElementById("prosesModal").classList.remove("hidden");
  }

  function closeModal() {
    document.getElementById("modalOverlay").classList.add("hidden");
    document.getElementById("prosesModal").classList.add("hidden");
  }

  // Client-side validation: jangan submit jika berat_masuk > available
  document.getElementById("formProses").addEventListener("submit", function(e) {
    var available = parseFloat(document.getElementById("available_for_next").value) || 0;
    var masuk = parseFloat(document.getElementById("berat_masuk").value) || 0;
    var keluar = parseFloat(document.getElementById("berat_keluar").value);

    if (masuk <= 0) {
      e.preventDefault();
      alert("Masukan berat yang akan diproses (lebih dari 0).");
      return false;
    }
    if (masuk > available) {
      e.preventDefault();
      alert("Berat yang akan diproses melebihi available untuk tahap ini (" + available.toFixed(2) + " Kg).");
      return false;
    }
    if (isNaN(keluar) || keluar < 0) {
      e.preventDefault();
      alert("Berat setelah proses tidak boleh kosong atau negatif.");
      return false;
    }
    if (keluar > masuk) {
      // Warning jika berat bertambah setelah proses
      if (!confirm("⚠️ PERINGATAN: Berat hasil proses (" + keluar + " Kg) lebih besar dari berat masuk (" + masuk + " Kg). Biasanya berat akan menyusut setelah proses. Apakah Anda yakin data ini sudah benar?")) {
        e.preventDefault();
        return false;
      }
    }
    return true;
  });
</script>

<?php include "../partials/footer.php"; ?>