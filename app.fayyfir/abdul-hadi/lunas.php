<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

/* =========================
   PROSES UPDATE TANGGAL
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if (isset($_POST['update_tanggal'])) {

    $id = intval($_POST['container_id']);
    $tanggal = trim($_POST['tanggal']);
    $field = $_POST['field'] ?? '';

    $allowed_fields = ['verified_at', 'accepted_at', 'lunas_at'];

    if ($id && $tanggal !== '' && in_array($field, $allowed_fields)) {

      $query = "UPDATE containers SET $field = ? WHERE id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("si", $tanggal, $id);
      $stmt->execute();
      $stmt->close();

      $_SESSION['status_pesan'] = "Tanggal berhasil diperbarui.";

      header("Location: lunas.php");
      exit();
    }
  }
}

// Query unique years from lunas_at
$years_query = "
  SELECT DISTINCT YEAR(lunas_at) AS yr 
  FROM containers 
  WHERE status = 'lunas' AND lunas_at IS NOT NULL 
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

$query = "
SELECT c.*, p.name AS product_name
FROM containers c
LEFT JOIN products p ON c.product_id = p.id
WHERE c.status = 'lunas'
ORDER BY c.number ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Kontainer Lunas - Fayyfir</title>

  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">

      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>

      <h1 class="text-lg font-semibold">Kontainer Lunas</h1>

    </div>
  </header>


  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">

    <?php if (isset($_SESSION["status_pesan"])): ?>

      <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
        <?= $_SESSION["status_pesan"];
        unset($_SESSION["status_pesan"]); ?>
      </div>

    <?php endif; ?>

    <!-- Filter and Search Section -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
      <div class="flex flex-col md:flex-row gap-4 md:items-end">

        <!-- Search Input -->
        <div class="flex-1 w-full">
          <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Cari Kontainer</label>
          <div class="relative">
            <input type="text" id="search" placeholder="Cari nama, nomor kontainer, nomor urut, atau area..."
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


    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

      <!-- Empty state container for javascript/php -->
      <div id="empty-state" class="<?= $result->num_rows === 0 ? '' : 'hidden' ?> col-span-full bg-white rounded-lg shadow p-6 text-center text-gray-500 font-semibold">
        <span class="material-symbols-outlined text-5xl text-gray-300 mb-2">inventory_2</span>
        <p class="text-sm">Tidak ada data kontainer lunas yang ditemukan.</p>
      </div>

      <?php while ($row = $result->fetch_assoc()): ?>

        <a href="riwayat-kontainer2?id=<?= $row["id"] ?>"
          class="container-card bg-white rounded-lg shadow p-4"
          data-year="<?= !empty($row["lunas_at"]) ? date("Y", strtotime($row["lunas_at"])) : '' ?>"
          data-date="<?= !empty($row["lunas_at"]) ? date("Y-m-d", strtotime($row["lunas_at"])) : '' ?>"
          data-number="<?= htmlspecialchars($row["number"]) ?>"
          data-container-number="<?= htmlspecialchars($row["container_number"]) ?>"
          data-product="<?= htmlspecialchars($row["product_name"] ?? '') ?>"
          data-area="<?= htmlspecialchars($row["region_name"] ?? '') ?>">

          <div class="text-gray-800 flex justify-between items-center mb-2">

            <div class="flex items-center space-x-4">

              <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>

              <div>

                <h2 class="text-sm text-gray-500">
                  <?= htmlspecialchars($row["container_number"]) ?>
                </h2>

                <p class="text-2xl font-bold text-gray-500">
                  <?= htmlspecialchars($row["number"]) ?>
                </p>

                <h2 class="text-sm text-gray-500">
                  Produk: <?= htmlspecialchars($row["product_name"] ?? "-") ?> |
                  Area: <?= htmlspecialchars($row["region_name"] ?? "-") ?>
                </h2>

              </div>
            </div>


            <div class="flex flex-col items-center">

              <h2 class="text-sm text-gray-500">Status</h2>

              <?php if ($row["status"] == "lunas"): ?>

                <span class="text-green-500 mt-1 text-sm font-semibold">
                  Lunas
                </span>

              <?php else: ?>

                <span class="text-red-500 mt-1 text-sm font-semibold">
                  Load
                </span>

              <?php endif; ?>

            </div>
          </div>


          <div class="flex items-center justify-between gap-1">

            <span class="text-sm text-gray-300">Closed</span>
            <span class="text-sm text-gray-300">Diterima</span>
            <span class="text-sm text-gray-300">Lunas</span>

          </div>


          <div class="flex items-center justify-between gap-1">

            <!-- VERIFIED -->

            <button
              onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'verified_at','<?= htmlspecialchars($row['verified_at']) ?>')"
              class="text-sm text-gray-500">

              <?= !empty($row["verified_at"]) ? date("d/m/Y", strtotime($row["verified_at"])) : "-" ?>

            </button>


            <!-- ACCEPTED -->

            <button
              onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'accepted_at','<?= htmlspecialchars($row['accepted_at']) ?>')"
              class="text-sm text-gray-500">

              <?= !empty($row["accepted_at"]) ? date("d/m/Y", strtotime($row["accepted_at"])) : "-" ?>

            </button>


            <!-- LUNAS -->

            <button
              onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'lunas_at','<?= htmlspecialchars($row['lunas_at']) ?>')"
              class="text-sm text-gray-500">

              <?= !empty($row["lunas_at"]) ? date("d/m/Y", strtotime($row["lunas_at"])) : "-" ?>

            </button>

          </div>

        </a>

      <?php endwhile; ?>

    </section>

  </main>



  <!-- ========================
     MODAL EDIT TANGGAL
======================== -->

  <div id="modalTanggal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">

    <div class="bg-white rounded-lg p-6 w-full max-w-sm relative">

      <h2 class="text-lg font-semibold mb-4">Ubah Tanggal</h2>

      <form method="POST" id="formTanggal">

        <input type="hidden" name="container_id" id="modalContainerId">
        <input type="hidden" name="field" id="modalField">
        <input type="hidden" name="tanggal" id="modalHidden">

        <label class="block text-sm font-medium mb-2">
          Tanggal & Waktu
        </label>

        <input
          type="datetime-local"
          id="modalVisible"
          class="w-full border px-3 py-2 rounded mb-4" />

        <div class="flex justify-end gap-2">

          <button
            type="button"
            onclick="closeTanggalModal()"
            class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">
            Batal
          </button>

          <button
            type="submit"
            name="update_tanggal"
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
            Simpan
          </button>

        </div>

      </form>

      <button
        onclick="closeTanggalModal()"
        class="absolute top-2 right-2 text-xl text-gray-500 hover:text-black">
        &times;
      </button>

    </div>
  </div>



  <script>
    function mysqlToDatetimeLocal(mysqlDt) {
      if (!mysqlDt) return '';
      var parts = mysqlDt.trim().split(' ');
      var date = parts[0];
      var time = parts[1] || '00:00:00';
      var hm = time.split(':');
      return date + 'T' + hm[0] + ':' + hm[1];
    }

    function datetimeLocalToMysql(dt) {
      if (!dt) return null;
      return dt.replace('T', ' ') + ':00';
    }

    function openTanggalModal(id, field, mysqlDatetime) {
      document.getElementById('modalContainerId').value = id;
      document.getElementById('modalField').value = field;
      var visible = mysqlToDatetimeLocal(mysqlDatetime);
      document.getElementById('modalVisible').value = visible;
      document.getElementById('modalHidden').value = datetimeLocalToMysql(visible);
      document.getElementById('modalTanggal').classList.remove('hidden');
    }

    function closeTanggalModal() {
      document.getElementById('modalTanggal').classList.add('hidden');
    }

    document.getElementById('formTanggal').addEventListener('submit', function(e) {
      var visible = document.getElementById('modalVisible').value;
      if (!visible) {
        alert('Silakan isi tanggal terlebih dahulu');
        e.preventDefault();
        return false;
      }
      document.getElementById('modalHidden').value = datetimeLocalToMysql(visible);
    });

    /* =========================
       REAL-TIME CLIENT FILTER
    ========================= */
    const searchInput = document.getElementById('search');
    const clearSearchBtn = document.getElementById('clear-search');
    const tahunSelect = document.getElementById('tahun');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const customPeriodContainer = document.getElementById('custom-period-container');
    const cards = document.querySelectorAll('.container-card');
    const emptyState = document.getElementById('empty-state');

    function filterContainers() {
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

      if (query) {
        clearSearchBtn.classList.remove('hidden');
      } else {
        clearSearchBtn.classList.add('hidden');
      }

      cards.forEach(card => {
        const year = card.getAttribute('data-year');
        const dateStr = card.getAttribute('data-date');
        const number = card.getAttribute('data-number').toLowerCase();
        const containerNumber = card.getAttribute('data-container-number').toLowerCase();
        const product = card.getAttribute('data-product').toLowerCase();
        const area = (card.getAttribute('data-area') || '').toLowerCase();

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
            if (startDateVal || endDateVal) {
              matchesTime = false;
            }
          }
        } else {
          matchesTime = !selectedYear || year === selectedYear;
        }

        const matchesQuery = !query ||
          number.includes(query) ||
          containerNumber.includes(query) ||
          product.includes(query) ||
          area.includes(query);

        if (matchesTime && matchesQuery) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      if (visibleCount === 0) {
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterContainers);
    }
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterContainers();
        searchInput.focus();
      });
    }
    if (tahunSelect) {
      tahunSelect.addEventListener('change', filterContainers);
    }
    if (startDateInput) {
      startDateInput.addEventListener('change', filterContainers);
    }
    if (endDateInput) {
      endDateInput.addEventListener('change', filterContainers);
    }

    filterContainers();
  </script>

</body>

</html>