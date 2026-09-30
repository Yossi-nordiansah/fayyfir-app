<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Ambil data dari product_stocks sebagai sumber utama
$sql = "  
  SELECT ps.id, ps.product_name, ps.quantity, u.symbol AS unit_symbol
  FROM product_stocks ps
  LEFT JOIN units u ON ps.unit_id = u.id
  ORDER BY ps.updated_at DESC
";
$result = $conn->query($sql);

$productions = [];
while ($row = $result->fetch_assoc()) {
  $productions[] = $row;
}

// Hitung Unsold Cost per produk (FIFO) untuk acuan modal timbang ulang
$sold_map = [];
$res_s = $conn->query("SELECT product_id, COALESCE(SUM(qty), 0) AS total_sold FROM selling_products GROUP BY product_id");
if ($res_s) {
  while ($s = $res_s->fetch_assoc()) {
    $sold_map[(int)$s['product_id']] = (float)$s['total_sold'];
  }
}

$batches_map = [];
$res_b = $conn->query("
  SELECT product_id, COALESCE(total_output, 0) AS total_output,
         (COALESCE(total_pro_materials, 0) + COALESCE(total_pro_expenses, 0)) AS total_cost
  FROM productions
  WHERE status != 'Proses' AND COALESCE(total_output, 0) > 0
  ORDER BY COALESCE(production_date, DATE(created_at)) ASC, id ASC
");
if ($res_b) {
  while ($b = $res_b->fetch_assoc()) {
    $batches_map[(int)$b['product_id']][] = $b;
  }
}

$grand_total_stock = 0.0;
$grand_total_unsold_cost = 0.0;

foreach ($productions as &$prod_row) {
  $pid = (int)$prod_row['id'];
  $p_sold = $sold_map[$pid] ?? 0.0;
  $p_batches = $batches_map[$pid] ?? [];
  $rem_sold = $p_sold;
  $u_cost = 0.0;

  foreach ($p_batches as $pb) {
    $b_out = (float)$pb['total_output'];
    $b_cost = (float)$pb['total_cost'];
    $b_uc = $b_out > 0 ? ($b_cost / $b_out) : 0;

    if ($rem_sold >= $b_out) {
      $rem_sold -= $b_out;
    } elseif ($rem_sold > 0) {
      $b_s_qty = $rem_sold;
      $b_s_cost = $b_s_qty * $b_uc;
      $u_cost += max(0.0, $b_cost - $b_s_cost);
      $rem_sold = 0.0;
    } else {
      $u_cost += $b_cost;
    }
  }
  $prod_row['unsold_cost'] = $u_cost;
  $cur_qty = (float)$prod_row['quantity'];
  $prod_row['current_hpp'] = $cur_qty > 0 ? ($u_cost / $cur_qty) : 0.0;

  $grand_total_stock += $cur_qty;
  $grand_total_unsold_cost += $u_cost;
}
unset($prod_row);

$grand_avg_hpp = $grand_total_stock > 0 ? ($grand_total_unsold_cost / $grand_total_stock) : 0.0;

// Hitung Total Penyusutan Gudang (dari product_shrinkage_logs)
$grand_total_susut_gudang = 0.0;
$conn->query("
  CREATE TABLE IF NOT EXISTS `product_shrinkage_logs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `ref_no` VARCHAR(100) NOT NULL,
    `adjustment_date` DATETIME NOT NULL,
    `qty_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `qty_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `qty_shrinkage` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `unsold_cost_at_time` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `hpp_before` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `hpp_after` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
$res_susut = $conn->query("SELECT COALESCE(SUM(qty_shrinkage), 0) AS total_susut FROM product_shrinkage_logs WHERE qty_shrinkage > 0");
if ($res_susut && $row_susut = $res_susut->fetch_assoc()) {
  $grand_total_susut_gudang = (float)$row_susut['total_susut'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hasil Produksi - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Hasil Produksi</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">

    <?php if (isset($_SESSION['success_msg'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-green-600">check_circle</span>
          <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success_msg']) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900 font-bold text-base leading-none">×</button>
      </div>
      <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-red-600">error</span>
          <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error_msg']) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900 font-bold text-base leading-none">×</button>
      </div>
      <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <!-- Ringkasan Statistik -->
    <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-1">Ringkasan Stok</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center space-x-3">
        <div class="w-11 h-11 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 flex-shrink-0">
          <span class="material-symbols-outlined text-2xl">inventory_2</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium">Total Stok Tersimpan</p>
          <p class="text-lg font-bold text-gray-900"><?= number_format($grand_total_stock, 0, ',', '.') ?> <span class="text-xs font-normal text-gray-500">gram</span></p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center space-x-3">
        <div class="w-11 h-11 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 flex-shrink-0">
          <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium">Total Biaya Produksi (Belum Terjual)</p>
          <p class="text-lg font-bold text-blue-700">Rp <?= number_format($grand_total_unsold_cost, 0, ',', '.') ?></p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-4 flex items-center space-x-3">
        <div class="w-11 h-11 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 flex-shrink-0">
          <span class="material-symbols-outlined text-2xl">warehouse</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium">Total Penyusutan Gudang</p>
          <p class="text-lg font-bold text-orange-600"><?= number_format($grand_total_susut_gudang, 0, ',', '.') ?> <span class="text-xs font-normal text-gray-500">gram</span></p>
        </div>
      </div>
    </div>

    <!-- Filter & jumlah baris -->
    <div class="mb-4 flex flex-wrap justify-between items-center gap-2">
      <div class="flex flex-wrap items-center gap-2">
        <input id="searchInput" type="text" placeholder="Cari produk..." class="w-64 px-3 py-2 border border-gray-300 rounded text-sm">
        <a href="laporan-penyusutan.php"
          class="inline-flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white px-3.5 py-2 rounded-lg text-sm font-semibold shadow transition">
          <span class="material-symbols-outlined text-base">analytics</span>
          Laporan Penyusutan
        </a>
      </div>
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

    <!-- Tabel Data -->
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400 text-sm">
          <tr>
            <th class="px-4 py-3 text-center whitespace-nowrap">No.</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Nama Produk</th>
            <th class="px-4 py-3 text-right whitespace-nowrap">Stok (gram)</th>
            <th class="px-4 py-3 text-right whitespace-nowrap">Total Biaya Produksi<br><span class="text-xs font-normal text-yellow-200">(Belum Terjual)</span></th>
            <th class="px-4 py-3 text-right whitespace-nowrap">HPP terhadap Biaya Belum Terjual<br><span class="text-xs font-normal text-yellow-200">(Sesuai Stok)</span></th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="text-gray-700 text-sm divide-y divide-gray-200">
          <?php if (!empty($productions)): ?>
            <?php $no = 1;
            foreach ($productions as $row): ?>
              <tr class="data-row">
                <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                <td class="px-4 py-2 font-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                <td class="px-4 py-2 text-right font-semibold">
                  <?= number_format($row['quantity'], 0, ',', '.') ?> <?= htmlspecialchars($row['unit_symbol'] ?? '') ?>
                </td>
                <td class="px-4 py-2 text-right font-bold text-blue-700 whitespace-nowrap">
                  Rp <?= number_format($row['unsold_cost'], 0, ',', '.') ?>
                </td>
                <td class="px-4 py-2 text-right font-semibold text-gray-800 whitespace-nowrap">
                  <?php if ((float)$row['quantity'] > 0): ?>
                    Rp <?= number_format($row['current_hpp'], 0, ',', '.') ?> <span class="text-xs text-gray-500 font-normal">/ <?= htmlspecialchars($row['unit_symbol'] ?? 'g') ?></span>
                  <?php else: ?>
                    <span class="text-xs text-gray-400 italic">Stok Habis</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-center space-x-2 whitespace-nowrap">
                  <a href="hasil-produksi-riwayat.php?product_name=<?= urlencode($row['product_name']) ?>" class="text-blue-600 hover:text-blue-800" title="Riwayat Batch Produksi">
                    <span class="material-symbols-outlined text-base">visibility</span>
                  </a>
                  <button type="button" class="text-amber-500 hover:text-amber-700 openSusutModal"
                    data-id="<?= $row['id'] ?>"
                    data-name="<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>"
                    data-stock="<?= (float)$row['quantity'] ?>"
                    data-unsold-cost="<?= (float)$row['unsold_cost'] ?>"
                    data-current-hpp="<?= (float)$row['current_hpp'] ?>"
                    data-symbol="<?= htmlspecialchars($row['unit_symbol'] ?? 'g', ENT_QUOTES) ?>"
                    title="Timbang Ulang / Penyesuaian Bobot Susut">
                    <span class="material-symbols-outlined text-base">scale</span>
                  </button>
                  <button type="button" class="text-yellow-500 hover:text-yellow-700 openEditModal" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>" title="Ubah Nama Produk">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-4 py-2 text-center text-gray-500">Belum ada data produksi.</td>
            </tr>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($productions)): ?>
          <tfoot class="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-300">
            <tr>
              <td colspan="2" class="px-4 py-3 text-center uppercase tracking-wider text-xs font-bold text-gray-700">Total Keseluruhan</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900"><?= number_format($grand_total_stock, 0, ',', '.') ?> g</td>
              <td class="px-4 py-3 text-right font-bold text-blue-800">Rp <?= number_format($grand_total_unsold_cost, 0, ',', '.') ?></td>
              <td class="px-4 py-3 text-right font-bold text-gray-800">
                <?php if ($grand_total_stock > 0): ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-center">-</td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
      <div id="totalRowsInfo"></div>
      <div id="paginationControls" class="flex gap-1"></div>
    </div>
  </main>

  <!-- Edit Product Modal -->
  <div id="editModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-6">

      <h2 class="text-lg font-semibold mb-4">Ubah Nama Produk</h2>

      <form id="editProductForm" class="space-y-4">
        <input type="hidden" id="editProductId">

        <div>
          <label class="text-sm font-semibold">Nama Produk</label>
          <input
            type="text"
            id="editProductName"
            class="w-full border rounded px-3 py-2 mt-1"
            required>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="closeModal" class="px-4 py-2 rounded border">
            Batal
          </button>
          <button type="submit" class="px-4 py-2 rounded bg-yellow-500 hover:bg-yellow-600 text-white">
            Simpan
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- Modal Timbang Ulang / Penyesuaian Bobot (Susut) -->
  <div id="susutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl overflow-hidden border border-gray-100 my-auto flex flex-col max-h-[90vh]">
      <!-- Header Modal -->
      <div class="bg-gray-900 text-white px-6 py-4 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-amber-400">scale</span>
          <h2 class="text-base font-semibold">Timbang Ulang & Penyesuaian Bobot</h2>
        </div>
        <button type="button" id="closeSusutModal" class="text-gray-400 hover:text-white text-xl leading-none">✕</button>
      </div>

      <!-- Form dengan Scrollable Body & Sticky Footer -->
      <form id="susutForm" action="hasil-produksi-susut-simpan.php" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
        <div class="p-6 space-y-4 overflow-y-auto flex-1">
          <input type="hidden" name="product_id" id="susutProductId">
          <input type="hidden" id="susutUnsoldCostVal">
          <input type="hidden" id="susutOldStockVal">

          <!-- Ringkasan Produk & Stok Saat Ini -->
          <div class="bg-amber-50 border border-amber-200 rounded-lg p-3.5 space-y-2 text-xs">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 font-medium">Nama Produk:</span>
              <span id="susutProductName" class="font-bold text-gray-900 text-sm">-</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 font-medium">Stok Tercatat di Sistem:</span>
              <span class="font-bold text-blue-800 text-sm"><span id="susutCurrentStock">0</span> <span class="susutUnitSymbol">g</span></span>
            </div>
            <div class="flex justify-between items-center border-t border-amber-200/60 pt-2">
              <span class="text-gray-600 font-medium">Biaya Belum Terjual (Modal Sisa):</span>
              <span class="font-bold text-gray-900">Rp <span id="susutUnsoldCost">0</span></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 font-medium">HPP Sisa Stok Saat Ini:</span>
              <span class="font-semibold text-gray-700">Rp <span id="susutCurrentHpp">0</span> / <span class="susutUnitSymbol">g</span></span>
            </div>
          </div>

          <!-- Input Bobot Riil Baru dengan Separator Ribuan Titik -->
          <div>
            <label for="newWeightInput" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
              Bobot Riil Setelah Timbang (<span class="susutUnitSymbol">g</span>) <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                type="text"
                inputmode="numeric"
                name="new_weight"
                id="newWeightInput"
                placeholder="Contoh: 5.200"
                class="w-full border-2 border-amber-400 focus:border-amber-600 focus:ring-0 rounded-lg px-3 py-2.5 text-sm font-semibold text-gray-900 placeholder-gray-400"
                autocomplete="off"
                required>
            </div>
            <p class="text-[11px] text-gray-500 mt-1">Ketikkan bobot fisik riil (titik ribuan otomatis).</p>
          </div>

          <!-- Live Calculation Card -->
          <div id="liveCalcCard" class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2 text-xs transition-all">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 font-medium">Selisih Bobot:</span>
              <span id="liveShrinkageDiff" class="font-bold text-gray-500">0 <span class="susutUnitSymbol">g</span></span>
            </div>
            <div class="flex justify-between items-center border-t border-gray-200 pt-1.5">
              <span class="text-gray-700 font-bold">Estimasi HPP Sisa Baru:</span>
              <span id="liveNewHpp" class="font-bold text-sm text-amber-700">Rp 0 / <span class="susutUnitSymbol">g</span></span>
            </div>
            <div id="liveShrinkageNote" class="text-[11px] text-gray-500 italic">Ketik bobot baru untuk melihat kalkulasi perubahan HPP.</div>
          </div>

          <!-- Input Tanggal Penimbangan & Keterangan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="susutDateInput" class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Timbang</label>
              <input
                type="date"
                name="adjustment_date"
                id="susutDateInput"
                value="<?= date('Y-m-d') ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500">
            </div>
            <div>
              <label for="susutNotesInput" class="block text-xs font-semibold text-gray-700 mb-1">Keterangan / Alasan</label>
              <input
                type="text"
                name="notes"
                id="susutNotesInput"
                placeholder="Contoh: Pengeringan gudang, selisih kirim..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500">
            </div>
          </div>
        </div>

        <!-- Action Buttons Footer (Sticky di Bagian Bawah Modal, Selalu Terlihat) -->
        <div class="bg-gray-100 px-6 py-3.5 border-t border-gray-200 flex justify-end items-center gap-3 flex-shrink-0">
          <button type="button" id="btnCancelSusut" class="px-4 py-2 text-xs font-medium rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm">
            Batal
          </button>
          <button type="submit" class="px-5 py-2 text-xs font-bold rounded-lg bg-blue-500 hover:bg-blue-600 text-white shadow flex items-center gap-1.5 transition">
            <span class="material-symbols-outlined text-sm">save</span>
            Simpan Hasil Timbangan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Script Pagination & Search -->
  <script>
    const rowsPerPage = document.getElementById("rowsPerPage");
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("materialTable");
    const rows = table.getElementsByClassName("data-row");
    const pagination = document.getElementById("paginationControls");
    const totalInfo = document.getElementById("totalRowsInfo");

    let currentPage = 1;

    function filterRows() {
      const query = searchInput.value.toLowerCase();
      for (let row of rows) {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
      }
      paginate();
    }

    function paginate() {
      const maxRows = parseInt(rowsPerPage.value);
      const visibleRows = [...rows].filter(r => r.style.display !== "none");
      const totalPages = Math.ceil(visibleRows.length / maxRows);
      currentPage = Math.min(currentPage, totalPages || 1);

      visibleRows.forEach((row, index) => {
        row.style.display = (index >= (currentPage - 1) * maxRows && index < currentPage * maxRows) ? "" : "none";
        row.querySelector("td:first-child").textContent = index + 1 + (currentPage - 1) * maxRows;
      });

      pagination.innerHTML = "";
      for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.className = "px-2 py-1 border rounded " + (i === currentPage ? "bg-yellow-500 text-white" : "hover:bg-yellow-100");
        btn.textContent = i;
        btn.onclick = () => {
          currentPage = i;
          paginate();
        };
        pagination.appendChild(btn);
      }

      totalInfo.textContent = `Menampilkan ${visibleRows.length} data dari total ${rows.length}`;
    }

    rowsPerPage.addEventListener("change", paginate);
    searchInput.addEventListener("keyup", filterRows);
    window.onload = paginate;

    /* ============================
       Modal Logic
    ============================ */
    const modal = document.getElementById("editModal");
    const closeModalBtn = document.getElementById("closeModal");
    const form = document.getElementById("editProductForm");
    const idInput = document.getElementById("editProductId");
    const nameInput = document.getElementById("editProductName");

    document.addEventListener("click", e => {
      const btn = e.target.closest(".openEditModal");
      if (!btn) return;

      idInput.value = btn.dataset.id;
      nameInput.value = btn.dataset.name;
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    });

    closeModalBtn.onclick = () => {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
    };

    modal.addEventListener("click", e => {
      if (e.target === modal) closeModalBtn.onclick();
    });

    /* ============================
       Submit Edit (AJAX)
    ============================ */
    form.addEventListener("submit", async e => {
      e.preventDefault();

      const id = idInput.value;
      const name = nameInput.value.trim();

      if (!name) return alert("Nama produk wajib diisi.");

      const res = await fetch("produk-update-nama.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          id,
          name
        })
      });

      const data = await res.json();

      if (data.success) {
        // Update nama di tabel tanpa reload
        document.querySelectorAll(`.openEditModal[data-id='${id}']`)
          .forEach(btn => {
            btn.dataset.name = name;
            btn.closest("tr").children[1].textContent = name;
          });

        closeModalBtn.onclick();
      } else {
        alert(data.message || "Gagal update data.");
      }
    });

    /* =======================================================
       Modal Timbang Ulang / Susut Logic
       ======================================================= */
    const susutModal = document.getElementById("susutModal");
    const closeSusutModalBtn = document.getElementById("closeSusutModal");
    const btnCancelSusut = document.getElementById("btnCancelSusut");
    const newWeightInput = document.getElementById("newWeightInput");

    document.addEventListener("click", e => {
      const btn = e.target.closest(".openSusutModal");
      if (!btn) return;

      const id = btn.dataset.id;
      const name = btn.dataset.name;
      const stock = parseFloat(btn.dataset.stock) || 0;
      const unsoldCost = parseFloat(btn.dataset.unsoldCost) || 0;
      const currentHpp = parseFloat(btn.dataset.currentHpp) || 0;
      const symbol = btn.dataset.symbol || "g";

      document.getElementById("susutProductId").value = id;
      document.getElementById("susutProductName").textContent = name;
      document.getElementById("susutOldStockVal").value = stock;
      document.getElementById("susutUnsoldCostVal").value = unsoldCost;
      document.getElementById("susutCurrentStock").textContent = new Intl.NumberFormat('id-ID').format(stock);
      document.getElementById("susutUnsoldCost").textContent = new Intl.NumberFormat('id-ID').format(Math.round(unsoldCost));
      document.getElementById("susutCurrentHpp").textContent = new Intl.NumberFormat('id-ID').format(Math.round(currentHpp));
      document.querySelectorAll(".susutUnitSymbol").forEach(el => el.textContent = symbol);

      newWeightInput.value = "";
      document.getElementById("susutNotesInput").value = "";
      document.getElementById("liveShrinkageDiff").innerHTML = `0 ${symbol}`;
      document.getElementById("liveNewHpp").textContent = `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(currentHpp))} / ${symbol}`;
      document.getElementById("liveShrinkageNote").textContent = "Ketik bobot baru untuk melihat kalkulasi perubahan HPP.";

      susutModal.classList.remove("hidden");
      susutModal.classList.add("flex");
      setTimeout(() => newWeightInput.focus(), 60);
    });

    function closeSusut() {
      susutModal.classList.add("hidden");
      susutModal.classList.remove("flex");
    }
    if (closeSusutModalBtn) closeSusutModalBtn.onclick = closeSusut;
    if (btnCancelSusut) btnCancelSusut.onclick = closeSusut;

    susutModal.addEventListener("click", e => {
      if (e.target === susutModal) closeSusut();
    });

    // Realtime live calculation & format titik ribuan
    newWeightInput.addEventListener("input", () => {
      // Ambil hanya digit angka
      const rawDigits = newWeightInput.value.replace(/\D/g, "");

      // Format input dengan pemisah titik tiap 3 digit
      if (rawDigits === "") {
        newWeightInput.value = "";
      } else {
        newWeightInput.value = rawDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      }

      const oldStock = parseFloat(document.getElementById("susutOldStockVal").value) || 0;
      const unsoldCost = parseFloat(document.getElementById("susutUnsoldCostVal").value) || 0;
      const symbol = document.querySelector(".susutUnitSymbol") ? document.querySelector(".susutUnitSymbol").textContent : "g";

      if (rawDigits === "") {
        const oldHpp = oldStock > 0 ? (unsoldCost / oldStock) : 0;
        document.getElementById("liveShrinkageDiff").textContent = `0 ${symbol}`;
        document.getElementById("liveNewHpp").textContent = `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(oldHpp))} / ${symbol}`;
        document.getElementById("liveShrinkageNote").textContent = "Ketik bobot baru untuk melihat kalkulasi perubahan HPP.";
        return;
      }

      const newWeight = parseFloat(rawDigits) || 0;
      const diff = newWeight - oldStock;
      const diffElem = document.getElementById("liveShrinkageDiff");
      const newHppElem = document.getElementById("liveNewHpp");
      const noteElem = document.getElementById("liveShrinkageNote");

      if (newWeight <= 0) {
        diffElem.innerHTML = `<span class="text-red-600 font-bold">Stok Habis (0 ${symbol})</span>`;
        newHppElem.textContent = "Rp 0";
        noteElem.textContent = "Seluruh sisa modal belum terjual akan menjadi beban persediaan.";
        return;
      }

      const newHpp = unsoldCost / newWeight;
      const oldHpp = oldStock > 0 ? (unsoldCost / oldStock) : 0;
      const hppDiff = newHpp - oldHpp;

      if (diff < 0) {
        const susutGram = Math.abs(diff);
        const pct = oldStock > 0 ? ((susutGram / oldStock) * 100).toFixed(1) : 0;
        diffElem.innerHTML = `<span class="text-red-600 font-bold">-${new Intl.NumberFormat('id-ID').format(susutGram)} ${symbol} (Susut ${pct}%)</span>`;
        newHppElem.innerHTML = `<span class="text-amber-700 font-bold">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(newHpp))} / ${symbol}</span>`;
        noteElem.innerHTML = `<span class="text-amber-600">Modal belum terjual tetap utuh. HPP per gram naik <strong>+Rp ${new Intl.NumberFormat('id-ID').format(Math.round(hppDiff))}</strong> untuk menyerap susut ${new Intl.NumberFormat('id-ID').format(susutGram)} ${symbol}.</span>`;
      } else if (diff > 0) {
        diffElem.innerHTML = `<span class="text-green-600 font-bold">+${new Intl.NumberFormat('id-ID').format(diff)} ${symbol} (Penambahan)</span>`;
        newHppElem.innerHTML = `<span class="text-green-700 font-bold">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(newHpp))} / ${symbol}</span>`;
        noteElem.innerHTML = `<span class="text-green-600">HPP per gram turun Rp ${new Intl.NumberFormat('id-ID').format(Math.round(Math.abs(hppDiff)))}.</span>`;
      } else {
        diffElem.innerHTML = `<span class="text-gray-600">Sama (0 ${symbol})</span>`;
        newHppElem.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(newHpp))} / ${symbol}`;
        noteElem.textContent = "Tidak ada perubahan bobot.";
      }
    });
  </script>

</body>

</html>