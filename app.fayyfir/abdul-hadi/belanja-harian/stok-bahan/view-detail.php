<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

$id_bahan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil info bahan
$stmt = $conn->prepare("SELECT nama_bahan, satuan FROM bb_bahan_master WHERE id = ?");
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$info_bahan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$info_bahan) {
  echo "Data bahan tidak ditemukan.";
  exit();
}

// Query untuk Tabel 1: Stok Mandiri (Belum Tercampur)
$query_mandiri = "
    SELECT 
        pa.*, 
        s.nama_supplier,
        IFNULL(pd_agg.terpakai_produksi, 0) AS terpakai_produksi,
        IFNULL(pnd_agg.terpakai_penampungan, 0) AS terpakai_penampungan
    FROM bb_pembelian_awal pa
    LEFT JOIN bb_supplier s ON pa.id_supplier = s.id
    LEFT JOIN (
        SELECT id_pembelian, SUM(berat_masuk) AS terpakai_produksi
        FROM bb_proses_detail
        WHERE tahap_ke = 0 AND status = 'aktif' AND id_penampungan IS NULL
        GROUP BY id_pembelian
    ) pd_agg ON pd_agg.id_pembelian = pa.id
    LEFT JOIN (
        SELECT id_pembelian, SUM(berat_masuk) AS terpakai_penampungan
        FROM bb_penampungan_detail
        GROUP BY id_pembelian
    ) pnd_agg ON pnd_agg.id_pembelian = pa.id
    WHERE pa.id_bahan = ?
    AND (pa.berat_awal - IFNULL(pd_agg.terpakai_produksi, 0) - IFNULL(pnd_agg.terpakai_penampungan, 0)) > 0
    ORDER BY pa.tanggal_pembelian DESC, pa.id DESC
";

$stmt = $conn->prepare($query_mandiri);
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$result_mandiri = $stmt->get_result();
$stmt->close();

// Query untuk Tabel 2: Stok Gabungan (Penampungan)
$query_gabungan = "
    SELECT 
        pn.*,
        IFNULL(pnd_agg.total_masuk, 0) as total_masuk,
        IFNULL(pd_agg.terpakai, 0) as terpakai
    FROM bb_penampungan pn
    LEFT JOIN (
        SELECT id_penampungan, SUM(berat_masuk) as total_masuk
        FROM bb_penampungan_detail
        GROUP BY id_penampungan
    ) pnd_agg ON pnd_agg.id_penampungan = pn.id
    LEFT JOIN (
        SELECT id_penampungan, SUM(berat_masuk) as terpakai
        FROM bb_proses_detail
        WHERE tahap_ke = 0 AND status = 'aktif'
        GROUP BY id_penampungan
    ) pd_agg ON pd_agg.id_penampungan = pn.id
    WHERE pn.id_bahan = ?
    AND (IFNULL(pnd_agg.total_masuk, 0) - IFNULL(pd_agg.terpakai, 0)) > 0
    ORDER BY pn.created_at DESC
";
$stmt = $conn->prepare($query_gabungan);
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$result_gabungan = $stmt->get_result();
$stmt->close();

$activeMenu = "stok-bahan";
$activeModule = "Detail Stok Bahan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
  <!-- Breadcrumb & Back Button -->
  <div class="mb-6">
    <a href="index.php" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Kembali ke Stok Bahan
    </a>
  </div>

  <!-- Header -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="p-3 bg-yellow-100 rounded-2xl">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($info_bahan['nama_bahan']) ?></h1>
          <p class="text-gray-500">Rincian Pembelian dan Sisa Stok Mentah (Satuan: <?= htmlspecialchars($info_bahan['satuan']) ?>)</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Section Tables -->
  <div class="grid grid-cols-1 gap-12">
    
    <!-- Tabel 1: Stok Mandiri (Per Supplier) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h3 class="font-bold text-gray-800 text-lg">1. Stok Mandiri (Per Supplier)</h3>
            <p class="text-xs text-gray-500">Bahan yang masih terpisah berdasarkan invoice pembelian/supplier.</p>
        </div>
        <button onclick="openGabungModal()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            GABUNGKAN BAHAN
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider">Tanggal Beli</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider">Supplier</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Berat Awal</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Terpakai (Prod)</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Dipindah (Gabungan)</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Sisa Stok</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if ($result_mandiri && $result_mandiri->num_rows > 0): ?>
              <?php while ($row = $result_mandiri->fetch_assoc()): 
                  $sisa = max(0, $row['berat_awal'] - $row['terpakai_produksi'] - $row['terpakai_penampungan']);
              ?>
                <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                  <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars(format_tanggal($row['tanggal_pembelian'])) ?></td>
                  <td class="px-6 py-4">
                      <div class="text-gray-900 font-medium"><?= htmlspecialchars($row['nama_supplier'] ?: '-') ?></div>
                      <div class="text-[10px] text-gray-400"><?= htmlspecialchars($row['kode_batch'] ?: '-') ?></div>
                  </td>
                  <td class="px-6 py-4 text-right text-gray-600"><?= number_format($row['berat_awal'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-right text-amber-600"><?= number_format($row['terpakai_produksi'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-right text-emerald-600"><?= number_format($row['terpakai_penampungan'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-right">
                    <span class="px-2 py-1 rounded font-bold bg-blue-50 text-blue-700">
                      <?= number_format($sisa, 0, ',', '.') ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center">
                      <a href="../pembelian-awal/edit-pembelian.php?id=<?= $row['id'] ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-10 text-center text-gray-400 italic">Tidak ada stok mandiri yang tersedia.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tabel 2: Stok Gabungan (Penampungan) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 bg-gray-50 border-b border-gray-100">
          <h3 class="font-bold text-gray-800 text-lg">2. Stok Gabungan (Penampungan)</h3>
          <p class="text-xs text-gray-500">Bahan yang telah dicampur ke dalam wadah penampungan.</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-800 text-yellow-400">
            <tr>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider">Nama Penampungan</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider">Tgl Dibuat</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Total Masuk</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Terpakai (Prod)</th>
              <th class="px-6 py-4 font-semibold uppercase tracking-wider text-right">Sisa Stok</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if ($result_gabungan && $result_gabungan->num_rows > 0): ?>
              <?php while ($row = $result_gabungan->fetch_assoc()): 
                  $sisa = max(0, $row['total_masuk'] - $row['terpakai']);
              ?>
                <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                  <td class="px-6 py-4 font-bold text-emerald-700"><?= htmlspecialchars($row['nama_penampungan']) ?></td>
                  <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                  <td class="px-6 py-4 text-right text-gray-600"><?= number_format($row['total_masuk'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-right text-amber-600"><?= number_format($row['terpakai'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-right">
                    <span class="px-2 py-1 rounded font-bold bg-emerald-50 text-emerald-700">
                      <?= number_format($sisa, 0, ',', '.') ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">Belum ada bahan yang digabungkan.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Summary Cards -->
  <?php
    $stmt = $conn->prepare("SELECT total_beli, total_proses, stok_tersedia FROM bb_v_stok_bahan WHERE id_bahan = ?");
    $stmt->bind_param("i", $id_bahan);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  ?>
  <?php if ($summary): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 bg-emerald-50/30 mt-12">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-600 mb-1">Total Stok Tersedia Saat Ini (Gabungan + Mandiri)</p>
                <h4 class="text-3xl font-bold text-emerald-700"><?= number_format($summary['stok_tersedia'], 0, ',', '.') ?> <span class="text-lg font-normal text-emerald-600/60"><?= htmlspecialchars($info_bahan['satuan']) ?></span></h4>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-xs text-gray-400">Total Beli: <?= number_format($summary['total_beli'], 0, ',', '.') ?></p>
                <p class="text-xs text-gray-400">Total Produksi: <?= number_format($summary['total_proses'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
  <?php endif; ?>

</main>

<script src="../assets/js/table-pagination.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  initTablePagination({
    tableId: "materialTable",
    searchInputId: "searchInput",
    rowsPerPage: 50
  });
});

// Data suppliers & penampungan untuk modal (diambil dari PHP untuk JS)
<?php
$suppliers_data = [];
$stmt = $conn->prepare($query_mandiri);
$stmt->bind_param("i", $id_bahan);
$stmt->execute();
$res = $stmt->get_result();
while($r = $res->fetch_assoc()) {
    $r['sisa'] = (float)$r['berat_awal'] - (float)$r['terpakai_produksi'] - (float)$r['terpakai_penampungan'];
    $suppliers_data[] = $r;
}
$stmt->close();

// Ambil penampungan yang sudah ada untuk bahan ini
$existing_penampungan = [];
$stmt_pn = $conn->prepare("
    SELECT 
        pn.id, 
        pn.nama_penampungan,
        COALESCE((SELECT SUM(pnd.berat_masuk) FROM bb_penampungan_detail pnd WHERE pnd.id_penampungan = pn.id), 0) as total_masuk,
        COALESCE((SELECT SUM(pd.berat_masuk) FROM bb_proses_detail pd WHERE pd.id_penampungan = pn.id AND pd.tahap_ke = 0 AND pd.status = 'aktif'), 0) as terpakai
    FROM bb_penampungan pn 
    WHERE pn.id_bahan = ? 
    HAVING (total_masuk - terpakai) > 0
    ORDER BY pn.created_at DESC
");
$stmt_pn->bind_param("i", $id_bahan);
$stmt_pn->execute();
$res_pn = $stmt_pn->get_result();
while($pn = $res_pn->fetch_assoc()) {
    $existing_penampungan[] = $pn;
}
$stmt_pn->close();
?>
const suppliersData = <?= json_encode($suppliers_data) ?>;
const existingPenampungan = <?= json_encode($existing_penampungan) ?>;

function openGabungModal() {
    document.getElementById('modalGabung').classList.remove('hidden');
    // Reset form ke mode baru
    setGabungMode('baru');
    document.getElementById('nama_penampungan').value = '';
    document.getElementById('gabungItemsContainer').innerHTML = '';
    addGabungRow();
}

function closeGabungModal() {
    document.getElementById('modalGabung').classList.add('hidden');
}

function setGabungMode(mode) {
    const sectionBaru = document.getElementById('section_nama_baru');
    const sectionExist = document.getElementById('section_nama_exist');
    const btnBaru = document.getElementById('tab_baru');
    const btnExist = document.getElementById('tab_exist');

    if (mode === 'baru') {
        sectionBaru.classList.remove('hidden');
        sectionExist.classList.add('hidden');
        btnBaru.classList.add('bg-emerald-600', 'text-white');
        btnBaru.classList.remove('bg-gray-100', 'text-gray-600');
        btnExist.classList.remove('bg-emerald-600', 'text-white');
        btnExist.classList.add('bg-gray-100', 'text-gray-600');
    } else {
        sectionBaru.classList.add('hidden');
        sectionExist.classList.remove('hidden');
        btnExist.classList.add('bg-emerald-600', 'text-white');
        btnExist.classList.remove('bg-gray-100', 'text-gray-600');
        btnBaru.classList.remove('bg-emerald-600', 'text-white');
        btnBaru.classList.add('bg-gray-100', 'text-gray-600');
    }
}

// Mengambil semua ID supplier yang sudah dipilih di baris manapun
function getSelectedSupplierIds() {
    return Array.from(document.querySelectorAll('.supplier-select'))
        .map(s => s.value)
        .filter(v => v !== "");
}

function addGabungRow() {
    const container = document.getElementById('gabungItemsContainer');
    const alreadySelected = getSelectedSupplierIds();

    // Build options — hanya supplier yang belum dipilih & sisa > 0
    let options = '<option value="">-- Pilih Supplier --</option>';
    suppliersData.forEach(s => {
        if (s.sisa > 0 && !alreadySelected.includes(s.id.toString())) {
            options += `<option value="${s.id}" data-max="${s.sisa}">${s.nama_supplier} (${s.kode_batch}) - Stok: ${Number(s.sisa).toLocaleString('id-ID')}</option>`;
        }
    });

    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 bg-gray-50 p-3 rounded-xl border border-gray-200 gabung-row';
    div.innerHTML = `
        <div class="flex-1">
            <select name="pembelian_id[]" onchange="handleSupplierChange(this)" required
                class="supplier-select w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                ${options}
            </select>
        </div>
        <div class="w-32">
            <input type="number" name="qty[]" step="0.01" placeholder="Qty" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
        </div>
        <button type="button" onclick="removeGabungRow(this)" class="text-red-500 hover:bg-red-50 p-1 rounded-lg flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
        </button>
    `;
    container.appendChild(div);
}

function removeGabungRow(btn) {
    const row = btn.closest('.gabung-row');
    const removedValue = row.querySelector('.supplier-select').value;
    row.remove();

    // Setelah dihapus, tambahkan kembali supplier yang dihapus ke baris lain
    if (removedValue) {
        document.querySelectorAll('.supplier-select').forEach(select => {
            const supplier = suppliersData.find(s => s.id.toString() === removedValue);
            if (supplier && supplier.sisa > 0) {
                const opt = document.createElement('option');
                opt.value = supplier.id;
                opt.dataset.max = supplier.sisa;
                opt.textContent = `${supplier.nama_supplier} (${supplier.kode_batch}) - Stok: ${Number(supplier.sisa).toLocaleString('id-ID')}`;
                select.appendChild(opt);
            }
        });
    }
}

function handleSupplierChange(select) {
    const prevValue = select.dataset.prevValue || '';
    const newValue = select.value;

    // Hapus nilai baru dari semua baris lain
    document.querySelectorAll('.supplier-select').forEach(other => {
        if (other === select) return;
        // Tambahkan kembali supplier lama jika ada
        if (prevValue) {
            const oldSupplier = suppliersData.find(s => s.id.toString() === prevValue);
            if (oldSupplier && oldSupplier.sisa > 0) {
                const exists = Array.from(other.options).some(o => o.value === prevValue);
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = oldSupplier.id;
                    opt.dataset.max = oldSupplier.sisa;
                    opt.textContent = `${oldSupplier.nama_supplier} (${oldSupplier.kode_batch}) - Stok: ${Number(oldSupplier.sisa).toLocaleString('id-ID')}`;
                    other.appendChild(opt);
                }
            }
        }
        // Hapus supplier baru dari baris lain
        if (newValue) {
            const optToRemove = Array.from(other.options).find(o => o.value === newValue);
            if (optToRemove) other.removeChild(optToRemove);
        }
    });

    select.dataset.prevValue = newValue;
    updateMaxQty(select);
}

function updateMaxQty(select) {
    const row = select.closest('.gabung-row');
    if (!row) return;
    const qtyInput = row.querySelector('input[name="qty[]"]');
    const max = select.options[select.selectedIndex]?.dataset.max || 0;
    qtyInput.max = max;
    qtyInput.placeholder = max > 0 ? `Max: ${Number(max).toLocaleString('id-ID')}` : 'Qty';
}

function submitGabung() {
    const isExistMode = !document.getElementById('section_nama_exist').classList.contains('hidden');
    let nama = '';
    let existingId = null;

    if (isExistMode) {
        const sel = document.getElementById('existing_penampungan_id');
        existingId = sel.value;
        nama = sel.options[sel.selectedIndex]?.text || '';
        if (!existingId) return alert('Pilih penampungan yang sudah ada.');
    } else {
        nama = document.getElementById('nama_penampungan').value.trim();
        if (!nama) return alert('Harap isi nama penampungan baru.');
    }

    const itemIds = document.querySelectorAll('select[name="pembelian_id[]"]');
    const itemQtys = document.querySelectorAll('input[name="qty[]"]');
    const items = [];
    let validationError = null;

    itemIds.forEach((el, idx) => {
        if (!el.value) return;
        const qty = parseFloat(itemQtys[idx].value);
        const max = parseFloat(el.options[el.selectedIndex]?.dataset.max || 0);
        const supplierName = el.options[el.selectedIndex]?.text?.split(' (')[0] || 'Supplier';

        if (!qty || qty <= 0) return;

        if (qty > max) {
            validationError = `Qty untuk ${supplierName} (${qty.toLocaleString('id-ID')}) melebihi stok tersedia (${max.toLocaleString('id-ID')}).`;
        }

        items.push({ id_pembelian: el.value, qty: qty });
    });

    if (validationError) return alert('❌ ' + validationError);
    if (items.length === 0) return alert('Pilih minimal 1 supplier dengan quantity valid.');

    const payload = {
        id_bahan: <?= $id_bahan ?>,
        nama_penampungan: nama,
        items: JSON.stringify(items)
    };
    if (existingId) payload.existing_penampungan_id = existingId;

    const btnSubmit = event.target; // Ambil tombol dari event jika perlu, atau gunakan ID
    const originalText = "Simpan Campuran";
    
    // Matikan tombol agar tidak double submit
    const submitBtn = document.querySelector('button[onclick="submitGabung()"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
    }

    fetch('api-gabungkan-bahan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
            // Aktifkan kembali jika gagal
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function showPenampunganDetail(id, nama) {
    document.getElementById('modalDetailPenampungan').classList.remove('hidden');
    document.getElementById('detailTitle').innerText = nama;
    const container = document.getElementById('detailContent');
    container.innerHTML = '<div class="p-8 text-center text-gray-400">Memuat detail...</div>';

    fetch(`api-get-penampungan-detail.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.length === 0) {
            container.innerHTML = '<div class="p-8 text-center text-gray-400">Tidak ada data detail.</div>';
            return;
        }

        let html = `
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold">
                    <tr>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3 text-right">Murni Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
        `;

        data.forEach(item => {
            html += `
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-bold text-gray-800">${item.nama_supplier}</div>
                        <div class="text-[10px] text-gray-400">${item.kode_batch}</div>
                    </td>
                    <td class="px-4 py-3 text-right">${item.berat_masuk.toLocaleString('id-ID')}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    });
}

function closeDetailModal() {
    document.getElementById('modalDetailPenampungan').classList.add('hidden');
}
</script>

<!-- Modal Gabungkan Bahan -->
<div id="modalGabung" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Gabungkan Bahan Baku</h3>
                <p class="text-xs text-gray-500">Campur beberapa bahan dari supplier ke satu penampungan.</p>
            </div>
            <button onclick="closeGabungModal()" class="p-2 hover:bg-gray-200 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6">

            <!-- Tab toggle: Baru / Sudah Ada -->
            <?php if (!empty($existing_penampungan)): ?>
            <div class="flex gap-2 mb-5 p-1 bg-gray-100 rounded-xl">
                <button id="tab_baru" onclick="setGabungMode('baru')" class="flex-1 py-2 rounded-lg text-xs font-bold transition bg-emerald-600 text-white">
                    + Buat Penampungan Baru
                </button>
                <button id="tab_exist" onclick="setGabungMode('exist')" class="flex-1 py-2 rounded-lg text-xs font-bold transition bg-gray-100 text-gray-600">
                    Tambah ke Penampungan Ada
                </button>
            </div>
            <?php endif; ?>

            <!-- Mode: Buat Baru -->
            <div id="section_nama_baru" class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Penampungan / Wadah Baru</label>
                <input type="text" id="nama_penampungan" placeholder="Misal: Wadah Campuran A & B"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none transition shadow-sm text-sm">
            </div>

            <!-- Mode: Pilih yang Sudah Ada -->
            <div id="section_nama_exist" class="mb-5 hidden">
                <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Penampungan yang Sudah Ada</label>
                <select id="existing_penampungan_id"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none transition shadow-sm text-sm bg-white">
                    <option value="">-- Pilih Penampungan --</option>
                    <?php foreach ($existing_penampungan as $pn): 
                        $sisa_pn = (float)$pn['total_masuk'] - (float)$pn['terpakai'];
                    ?>
                    <option value="<?= $pn['id'] ?>">
                        <?= htmlspecialchars($pn['nama_penampungan']) ?> (Sisa: <?= number_format($sisa_pn, 0, ',', '.') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4 flex justify-between items-center">
                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Item yang digabungkan</h4>
                <button type="button" onclick="addGabungRow()" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    TAMBAH ITEM
                </button>
            </div>

            <div id="gabungItemsContainer" class="space-y-3 max-h-[220px] overflow-y-auto pr-2 mb-8">
                <!-- Rows added by JS -->
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button onclick="closeGabungModal()" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                <button onclick="submitGabung()" class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition-all">Simpan Campuran</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Penampungan -->
<div id="modalDetailPenampungan" class="hidden fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 id="detailTitle" class="text-xl font-bold text-gray-800">Detail Penampungan</h3>
                <p class="text-xs text-gray-500">Breakdown supplier yang ada di dalam wadah ini.</p>
            </div>
            <button onclick="closeDetailModal()" class="p-2 hover:bg-gray-200 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="detailContent" class="overflow-x-auto">
            <!-- Content loaded by JS -->
        </div>
        <div class="p-6 border-t border-gray-100 flex justify-end">
            <button onclick="closeDetailModal()" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Tutup</button>
        </div>
    </div>
</div>

<?php include "../partials/footer.php"; ?>
