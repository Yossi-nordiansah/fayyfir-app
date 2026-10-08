<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Ambil data biaya operasional dari database
$years_query = "
  SELECT DISTINCT YEAR(tanggal) AS yr 
  FROM operational_costs 
  WHERE tanggal IS NOT NULL 
  ORDER BY yr DESC
";
$years_res = $conn->query($years_query);
$years = [];
while ($y_row = $years_res->fetch_assoc()) {
  if ($y_row['yr'] !== null) {
    $years[] = intval($y_row['yr']);
  }
}
$current_year = intval(date('Y'));

// Tentukan default year: tahun sekarang jika ada data, atau tahun terbaru yang ada datanya
if (in_array($current_year, $years)) {
  $default_year = $current_year;
} elseif (!empty($years)) {
  $default_year = $years[0];
} else {
  $default_year = $current_year;
}

if (!in_array($current_year, $years)) {
  $years[] = $current_year;
}
rsort($years);

$sql = "SELECT * FROM operational_costs ORDER BY tanggal DESC, created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Biaya Operasional - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <style>
    @media print {
      header, .no-print, #detailModal, #modalCetak, #custom-period-container,
      .flex.justify-between.items-center.flex-wrap.gap-4.mb-4,
      .bg-white.p-4.rounded-lg.shadow-md,
      th:last-child, td:last-child {
        display: none !important;
      }
      body {
        background-color: #fff !important;
        color: #000 !important;
      }
      main {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
      }
      .shadow, .shadow-md {
        box-shadow: none !important;
      }
      .rounded-lg {
        border-radius: 0 !important;
      }
    }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Biaya Operasional</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <!-- Header dan Tombol Tambah & Cetak -->
    <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
      <h2 class="text-xl font-bold text-gray-800">Daftar Biaya Operasional</h2>
      <div class="flex items-center gap-2">
        <button type="button" id="btnBukaModalCetak" class="inline-flex items-center bg-gray-800 hover:bg-yellow-500 text-yellow-400 hover:text-black text-sm px-4 py-2 rounded shadow space-x-1 transition">
          <span class="material-symbols-outlined text-base">print</span>
          <span>Cetak</span>
        </button>
        <a href="tambah-operasional" class="inline-flex items-center bg-gray-800 hover:bg-yellow-500 text-yellow-400 hover:text-black text-sm px-4 py-2 rounded shadow space-x-1 transition">
          <span class="material-symbols-outlined text-base">add</span>
          <span>Tambah Operasional</span>
        </a>
      </div>
    </div>

    <!-- Filter and Search Section -->
    <div class="bg-white p-4 rounded-lg shadow-md">
      <div class="flex flex-col md:flex-row gap-4 md:items-end">

        <!-- Search Input -->
        <div class="flex-1 w-full">
          <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Cari Operasional</label>
          <div class="relative">
            <input type="text" id="search" placeholder="Cari deskripsi atau keterangan..."
              class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm text-gray-800" />
            <button id="clear-search" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
              <span class="material-symbols-outlined text-sm">close</span>
            </button>
          </div>
        </div>

        <!-- Year Filter Dropdown -->
        <div class="w-full md:w-48">
          <label for="tahun" class="block text-sm font-semibold text-gray-700 mb-1">Filter Waktu</label>
          <select id="tahun" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm text-gray-800">
            <option value="">Semua Tahun</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= $y ?>" <?= $y == $default_year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
            <option value="custom">Pilih Periode...</option>
          </select>
        </div>

        <!-- Custom Period Inputs -->
        <div id="custom-period-container" class="hidden w-full md:w-auto flex flex-col sm:flex-row gap-4">
          <div class="w-full sm:w-40">
            <label for="start-date" class="block text-sm font-semibold text-gray-700 mb-1">Dari Tanggal</label>
            <input type="date" id="start-date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm text-gray-800" />
          </div>
          <div class="w-full sm:w-40">
            <label for="end-date" class="block text-sm font-semibold text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" id="end-date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm text-gray-800" />
          </div>
        </div>

      </div>
    </div>

    <!-- Tabel -->
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-4 py-2 text-center">Tanggal</th>
            <th class="px-4 py-2 text-left">Deskripsi</th>
            <th class="px-4 py-2 text-right">Jumlah (Rp)</th>
            <th class="px-4 py-2 text-left">Keterangan</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php
          $total_amount = 0; // inisialisasi total

          while ($row = $result->fetch_assoc()):
            $id = $row["id"];
            $date = $row["tanggal"];
            $nama = json_encode($row["nama_biaya"]);
            $desc = json_encode($row["deskripsi"]);
            $amount = $row["jumlah"]; // simpan angka asli

            $total_amount += $amount; // jumlahkan angka asli
          ?>
            <tr class="operasional-row" 
                data-year="<?= date("Y", strtotime($date)) ?>"
                data-date="<?= date("Y-m-d", strtotime($date)) ?>"
                data-name="<?= htmlspecialchars($row["nama_biaya"]) ?>"
                data-desc="<?= htmlspecialchars($row["deskripsi"]) ?>"
                data-amount="<?= $amount ?>">
              <td class="px-4 py-2 text-center"><?= htmlspecialchars(date("d/m/Y", strtotime($date))) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["nama_biaya"]) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($amount, 0, ",", ".") ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["deskripsi"]) ?></td>
              <td class="px-4 py-2 text-center">
                <button onclick='showModal(<?= $id ?>, <?= $nama ?>, <?= $desc ?>, "<?= number_format($amount, 0, ",", ".") ?>", "<?= $date ?>")'
                  class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
          <tr id="empty-state" class="hidden">
            <td colspan="5" class="px-4 py-8 text-center text-gray-500 font-semibold">
              <span class="material-symbols-outlined text-4xl text-gray-300 mb-1">payments</span>
              <p class="text-xs">Tidak ada data operasional yang ditemukan.</p>
            </td>
          </tr>
        </tbody>
        <tfoot class="bg-gray-100 font-semibold">
          <td colspan="2" class="px-4 py-2 text-right">TOTAL</td>
          <td id="total-amount-sum" class="px-4 py-2 text-right"><?= number_format($total_amount, 0, ",", ".") ?></td>
          <td colspan="2" class="px-4 py-2 text-center"></td>
        </tfoot>
      </table>
    </div>
  </main>

  <!-- Modal -->
  <div id="detailModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative text-gray-800">
      <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
        <span class="material-symbols-outlined">close</span>
      </button>
      <h2 class="text-lg font-semibold mb-4">Detail Biaya</h2>
      <div class="space-y-2 text-sm">
        <p><strong>Tanggal:</strong> <span id="modalTanggal"></span></p>
        <p><strong>Deskripsi:</strong> <span id="modalNama"></span></p>
        <p><strong>Jumlah:</strong> <span id="modalJumlah"></span></p>
        <p><strong>Keterangan:</strong> <span id="modalDesc"></span></p>
      </div>
      <div class="mt-6 flex justify-end space-x-3">
        <a href="#" id="btnEdit" class="bg-yellow-400 text-white px-4 py-2 rounded hover:bg-yellow-500 text-sm">Edit</a>
        <form method="POST" action="hapus-operasional.php" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
          <input type="hidden" name="id" id="hiddenDeleteId" />
          <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Cetak Biaya Operasional -->
  <div id="modalCetak" style="display: none;" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative text-gray-800">
      <button type="button" onclick="closeModalCetak()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
        <span class="material-symbols-outlined">close</span>
      </button>
      <div class="flex items-center space-x-2 text-gray-800 mb-4">
        <span class="material-symbols-outlined text-yellow-500 text-2xl">print</span>
        <h2 class="text-lg font-bold">Cetak Laporan Operasional</h2>
      </div>

      <form id="formCetak" action="cetak-operasional.php" method="GET" target="_blank">
        <div class="space-y-4 text-sm">
          <div>
            <label for="modalCetakTahun" class="block font-semibold text-gray-700 mb-1">Periode Waktu</label>
            <select name="tahun" id="modalCetakTahun" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm">
              <option value="">Semua Periode</option>
              <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
              <?php endforeach; ?>
              <option value="custom">Pilih Rentang Tanggal...</option>
            </select>
          </div>

          <div id="modalCetakDates" class="hidden grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded border border-gray-200">
            <div>
              <label for="modalCetakStartDate" class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
              <input type="date" name="start_date" id="modalCetakStartDate" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring focus:ring-yellow-300">
            </div>
            <div>
              <label for="modalCetakEndDate" class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
              <input type="date" name="end_date" id="modalCetakEndDate" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring focus:ring-yellow-300">
            </div>
          </div>

          <div>
            <label for="modalCetakSearch" class="block font-semibold text-gray-700 mb-1">Filter Kata Kunci (Opsional)</label>
            <input type="text" name="search" id="modalCetakSearch" placeholder="Cari nama atau keterangan..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-sm">
          </div>

          <div class="p-3 bg-yellow-50 text-yellow-800 rounded text-xs border border-yellow-200">
            <span class="material-symbols-outlined text-sm align-middle mr-1">info</span>
            Dokumen cetak sudah dilengkapi KOP resmi Fayyfir, ringkasan pengeluaran, rincian biaya, dan lembar pengesahan.
          </div>
        </div>

        <input type="hidden" name="auto_print" value="1">

        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" onclick="closeModalCetak()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm font-semibold text-gray-700">Batal</button>
          <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-gray-900 rounded text-sm font-bold flex items-center space-x-1 shadow">
            <span class="material-symbols-outlined text-sm">print</span>
            <span>Buka & Cetak</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showModal(id, nama, desc, amount, date) {
      document.getElementById("modalTanggal").textContent = date;
      document.getElementById("modalNama").textContent = nama;
      document.getElementById("modalDesc").textContent = desc;
      document.getElementById("modalJumlah").textContent = "Rp " + amount;
      document.getElementById("btnEdit").href = "edit-operasional?id=" + id;
      document.getElementById("hiddenDeleteId").value = id;
      document.getElementById("detailModal").style.display = "flex";
    }

    function closeModal() {
      document.getElementById("detailModal").style.display = "none";
    }

    /* =========================
       REAL-TIME CLIENT FILTER & SUM UPDATING
    ========================= */
    const searchInput = document.getElementById('search');
    const clearSearchBtn = document.getElementById('clear-search');
    const tahunSelect = document.getElementById('tahun');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const customPeriodContainer = document.getElementById('custom-period-container');
    const rows = document.querySelectorAll('.operasional-row');
    const emptyState = document.getElementById('empty-state');
    const totalAmountSum = document.getElementById('total-amount-sum');

    const formatter = new Intl.NumberFormat("id-ID");

    function filterOperasional() {
      const query = searchInput.value.toLowerCase().trim();
      const selectedYear = tahunSelect.value;

      if (selectedYear === 'custom') {
        customPeriodContainer.classList.remove('hidden');
      } else {
        customPeriodContainer.classList.add('hidden');
      }

      const startDateVal = startDateInput ? startDateInput.value : '';
      const endDateVal = endDateInput ? endDateInput.value : '';
      let visibleCount = 0;
      let totalAmount = 0;

      // Show/hide clear button
      if (query) {
        clearSearchBtn.classList.remove('hidden');
      } else {
        clearSearchBtn.classList.add('hidden');
      }

      rows.forEach(row => {
        const year = row.getAttribute('data-year');
        const dateStr = row.getAttribute('data-date');
        const name = (row.getAttribute('data-name') || '').toLowerCase();
        const desc = (row.getAttribute('data-desc') || '').toLowerCase();
        const amount = parseFloat(row.getAttribute('data-amount')) || 0;

        // Check year filter or custom period filter
        let matchesTime = false;
        if (selectedYear === 'custom') {
          matchesTime = true;
          if (dateStr) {
            if (startDateVal && dateStr < startDateVal) {
              matchesTime = false;
            }
            if (endDateVal && dateStr > endDateVal) {
              matchesTime = false;
            }
          } else {
            // if date range is defined but row has no date, it shouldn't match
            if (startDateVal || endDateVal) {
              matchesTime = false;
            }
          }
        } else {
          matchesTime = !selectedYear || year === selectedYear;
        }

        // Check search query against both Deskripsi (name) and Keterangan (desc)
        const matchesQuery = !query ||
          name.includes(query) ||
          desc.includes(query);

        if (matchesTime && matchesQuery) {
          row.style.display = '';
          totalAmount += amount;
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      // Update total sum in footer
      if (totalAmountSum) {
        totalAmountSum.textContent = formatter.format(totalAmount);
      }

      if (visibleCount === 0) {
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterOperasional);
    }
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterOperasional();
        searchInput.focus();
      });
    }
    if (tahunSelect) {
      tahunSelect.addEventListener('change', filterOperasional);
    }
    if (startDateInput) {
      startDateInput.addEventListener('change', filterOperasional);
    }
    if (endDateInput) {
      endDateInput.addEventListener('change', filterOperasional);
    }

    // Modal Cetak Controller
    function bukaModalCetak() {
      const currentYear = tahunSelect ? tahunSelect.value : '';
      const currentStartDate = startDateInput ? startDateInput.value : '';
      const currentEndDate = endDateInput ? endDateInput.value : '';
      const currentSearch = searchInput ? searchInput.value.trim() : '';

      const modalTahun = document.getElementById('modalCetakTahun');
      const modalDates = document.getElementById('modalCetakDates');
      const modalStart = document.getElementById('modalCetakStartDate');
      const modalEnd = document.getElementById('modalCetakEndDate');
      const modalSearch = document.getElementById('modalCetakSearch');

      modalTahun.value = currentYear;
      modalStart.value = currentStartDate;
      modalEnd.value = currentEndDate;
      modalSearch.value = currentSearch;

      if (currentYear === 'custom' || currentStartDate || currentEndDate) {
        modalTahun.value = 'custom';
        modalDates.classList.remove('hidden');
      } else {
        modalDates.classList.add('hidden');
      }

      document.getElementById('modalCetak').style.display = 'flex';
    }

    function closeModalCetak() {
      document.getElementById('modalCetak').style.display = 'none';
    }

    const btnBukaCetak = document.getElementById('btnBukaModalCetak');
    if (btnBukaCetak) {
      btnBukaCetak.addEventListener('click', bukaModalCetak);
    }

    const modalTahunSelect = document.getElementById('modalCetakTahun');
    if (modalTahunSelect) {
      modalTahunSelect.addEventListener('change', function() {
        const modalDates = document.getElementById('modalCetakDates');
        if (this.value === 'custom') {
          modalDates.classList.remove('hidden');
        } else {
          modalDates.classList.add('hidden');
        }
      });
    }

    // Run filter automatically on page load
    filterOperasional();
  </script>
</body>

</html>