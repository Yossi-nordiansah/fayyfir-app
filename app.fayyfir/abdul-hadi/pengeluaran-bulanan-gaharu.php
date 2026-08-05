<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}
require "config.php";

$level = $_SESSION["role_id"] ?? "";
if ($level != "2" && $level != "3") {
  header("Location: index");
  exit();
}

// --- Filter bulan ---
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? $_GET['bulan'] : date('Y-m');

// --- Proses POST: tambah pengeluaran ---
$error = '';
$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan'])) {
  $bulan          = trim($_POST['bulan'] ?? '');
  $nama           = trim($_POST['nama_pengeluaran'] ?? '');
  $jenis          = $_POST['jenis'] ?? 'tidak_fix';
  $jumlah_raw     = str_replace('.', '', $_POST['jumlah'] ?? '0');
  $jumlah         = (float)$jumlah_raw;
  $keterangan     = trim($_POST['keterangan'] ?? '');

  if (!$bulan || !$nama || $jumlah <= 0) {
    $error = "Harap isi semua field wajib dengan benar.";
  } elseif (!in_array($jenis, ['fix', 'tidak_fix'])) {
    $error = "Jenis pengeluaran tidak valid.";
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO gaharu_monthly_expenses (bulan, nama_pengeluaran, jenis, jumlah, keterangan)
       VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssds", $bulan, $nama, $jenis, $jumlah, $keterangan);
    if ($stmt->execute()) {
      $success = "Pengeluaran berhasil disimpan.";
      $filter_bulan = $bulan;
    } else {
      $error = "Gagal menyimpan data: " . $conn->error;
    }
    $stmt->close();
  }
}

// --- Proses POST: apply/salin biaya tetap dari bulan lalu ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_fixed_expenses'])) {
  $target_bulan = $_POST['target_bulan'] ?? '';
  $source_bulan = $_POST['source_bulan'] ?? '';
  if ($target_bulan && $source_bulan) {
    $stmt_get_prev = $conn->prepare("SELECT nama_pengeluaran, jenis, jumlah, keterangan FROM gaharu_monthly_expenses WHERE bulan = ? AND jenis = 'fix'");
    $stmt_get_prev->bind_param("s", $source_bulan);
    $stmt_get_prev->execute();
    $prev_expenses = $stmt_get_prev->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_get_prev->close();

    if (!empty($prev_expenses)) {
      $stmt_insert = $conn->prepare("INSERT INTO gaharu_monthly_expenses (bulan, nama_pengeluaran, jenis, jumlah, keterangan) VALUES (?, ?, ?, ?, ?)");
      if ($stmt_insert) {
        $any_error = false;
        foreach ($prev_expenses as $pe) {
          $stmt_insert->bind_param("sssds", $target_bulan, $pe['nama_pengeluaran'], $pe['jenis'], $pe['jumlah'], $pe['keterangan']);
          if (!$stmt_insert->execute()) {
            $any_error = true;
            $error = "Gagal menyalin data: " . $stmt_insert->error;
            break;
          }
        }
        $stmt_insert->close();
        if (!$any_error) {
          $success = "Biaya tetap ('fix') berhasil disalin dari bulan " . htmlspecialchars($source_bulan) . ".";
        }
      } else {
        $error = "Gagal mempersiapkan query insert: " . $conn->error;
      }
    } else {
      $error = "Tidak ada data pengeluaran tetap ('fix') yang ditemukan pada bulan " . htmlspecialchars($source_bulan) . ".";
    }
  }
}

// --- Check jika bulan ini masih kosong untuk menampilkan tombol Salin ---
$show_copy_banner = false;
$prev_bulan = '';

$stmt_check = $conn->prepare("SELECT COUNT(*) AS total FROM gaharu_monthly_expenses WHERE bulan = ?");
$stmt_check->bind_param("s", $filter_bulan);
$stmt_check->execute();
$res_check = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($res_check['total'] == 0) {
  $stmt_prev = $conn->prepare("SELECT MAX(bulan) AS prev_bulan FROM gaharu_monthly_expenses WHERE bulan < ? AND jenis = 'fix'");
  $stmt_prev->bind_param("s", $filter_bulan);
  $stmt_prev->execute();
  $res_prev = $stmt_prev->get_result()->fetch_assoc();
  $stmt_prev->close();

  if ($res_prev && $res_prev['prev_bulan']) {
    $prev_bulan = $res_prev['prev_bulan'];
    $show_copy_banner = true;
  }
}

// --- Ambil data list ---
$stmt_list = $conn->prepare(
  "SELECT * FROM gaharu_monthly_expenses WHERE bulan = ? ORDER BY jenis ASC, id DESC"
);
$stmt_list->bind_param("s", $filter_bulan);
$stmt_list->execute();
$res_list = $stmt_list->get_result();
$list_data = $res_list->fetch_all(MYSQLI_ASSOC);
$stmt_list->close();

// --- Ringkasan total ---
$stmt_sum = $conn->prepare(
  "SELECT jenis, COALESCE(SUM(jumlah),0) AS total FROM gaharu_monthly_expenses WHERE bulan = ? GROUP BY jenis"
);
$stmt_sum->bind_param("s", $filter_bulan);
$stmt_sum->execute();
$res_sum = $stmt_sum->get_result();
$total_fix = 0;
$total_tidak_fix = 0;
while ($r = $res_sum->fetch_assoc()) {
  if ($r['jenis'] === 'fix') $total_fix = (float)$r['total'];
  else $total_tidak_fix = (float)$r['total'];
}
$stmt_sum->close();
$grand_total = $total_fix + $total_tidak_fix;

function fmtIDR2($n)
{
  return number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pengeluaran Bulanan Gaharu - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    /* Modal overlay */
    #modalTambah {
      display: none;
    }
    #modalTambah.active {
      display: flex;
    }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center max-w-6xl mx-auto">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Pengeluaran Bulanan Gaharu</h1>
      <span></span>
    </div>
  </header>

  <!-- Modal Tambah Pengeluaran -->
  <div id="modalTambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-5 border-b">
        <h2 class="font-bold text-gray-800 flex items-center space-x-2">
          <span class="material-symbols-outlined text-yellow-500">add_circle</span>
          <span>Tambah Pengeluaran Bulanan</span>
        </h2>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <!-- Modal Body -->
      <form method="POST" class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Bulan <span class="text-red-500">*</span></label>
            <input type="month" name="bulan" id="modal_bulan" value="<?= htmlspecialchars($filter_bulan) ?>" required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Jenis <span class="text-red-500">*</span></label>
            <select name="jenis" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm">
              <option value="fix">Fix (Tetap — mis. Gaji)</option>
              <option value="tidak_fix">Tidak Fix (Variabel)</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nama Pengeluaran <span class="text-red-500">*</span></label>
          <input type="text" name="nama_pengeluaran" required placeholder="Contoh: Gaji Karyawan, Listrik..."
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
            <input type="text" name="jumlah" id="inp_jumlah" required placeholder="0"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Keterangan</label>
            <input type="text" name="keterangan" placeholder="Opsional..."
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50 text-sm" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" onclick="closeModal()" class="px-4 py-2 rounded border text-gray-600 hover:bg-gray-100 text-sm transition">Batal</button>
          <button type="submit" name="simpan"
            class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold px-5 py-2 rounded transition flex items-center space-x-2 text-sm">
            <span class="material-symbols-outlined text-base">save</span>
            <span>Simpan</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <main class="pt-24 px-4 pb-16 max-w-6xl mx-auto space-y-5">

    <!-- Notifikasi -->
    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 border border-green-300 rounded p-3 flex items-center space-x-2">
        <span class="material-symbols-outlined text-base">check_circle</span>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 border border-red-300 rounded p-3 flex items-center space-x-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <!-- Banner Salin Pengeluaran Tetap (Fix) -->
    <?php if (isset($show_copy_banner) && $show_copy_banner): ?>
      <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
        <div class="flex items-start space-x-3">
          <span class="material-symbols-outlined text-yellow-600 text-xl mt-0.5">info</span>
          <div>
            <h3 class="font-bold text-gray-800 text-sm">Salin Pengeluaran Tetap (Fix)</h3>
            <p class="text-xs text-gray-600 mt-0.5">
              Bulan ini belum ada data. Salin biaya tetap dari bulan <strong><?= htmlspecialchars($prev_bulan) ?></strong>?
            </p>
          </div>
        </div>
        <form method="POST" class="shrink-0 w-full sm:w-auto">
          <input type="hidden" name="target_bulan" value="<?= htmlspecialchars($filter_bulan) ?>" />
          <input type="hidden" name="source_bulan" value="<?= htmlspecialchars($prev_bulan) ?>" />
          <button type="submit" name="apply_fixed_expenses" class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold px-4 py-2 rounded text-xs transition flex items-center justify-center space-x-1 shadow-sm">
            <span class="material-symbols-outlined text-sm">content_copy</span>
            <span>Salin &amp; Simpan Pengeluaran</span>
          </button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Toolbar: Filter + Tombol Tambah -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <!-- Filter Bulan (auto-submit onchange) -->
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-600">Bulan:</label>
        <form method="GET" id="filterForm">
          <input type="month" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>"
            onchange="document.getElementById('filterForm').submit()"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-yellow-300 focus:outline-none cursor-pointer" />
        </form>
      </div>
      <!-- Tombol Tambah -->
      <button onclick="openModal()"
        class="flex items-center space-x-1 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold px-4 py-2 rounded text-sm transition shadow-sm">
        <span class="material-symbols-outlined text-base">add_circle</span>
        <span>Tambah Pengeluaran Bulanan</span>
      </button>
    </div>

    <!-- Ringkasan Bulan -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Fix (Tetap)</div>
        <div class="mt-2 text-xl font-bold text-blue-600">Rp <?= fmtIDR2($total_fix) ?></div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Tidak Fix (Variabel)</div>
        <div class="mt-2 text-xl font-bold text-orange-500">Rp <?= fmtIDR2($total_tidak_fix) ?></div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Grand Total</div>
        <div class="mt-2 text-xl font-bold text-red-600">Rp <?= fmtIDR2($grand_total) ?></div>
      </div>
    </section>

    <!-- Tabel Daftar -->
    <section class="bg-white shadow rounded-lg overflow-hidden">
      <div class="p-4 border-b flex items-center justify-between">
        <h2 class="text-base font-bold flex items-center space-x-2">
          <span class="material-symbols-outlined text-gray-600">list_alt</span>
          <span>Daftar Pengeluaran — <?= htmlspecialchars($filter_bulan) ?></span>
        </h2>
        <span class="text-sm text-gray-400"><?= count($list_data) ?> item</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 border-b text-left">Nama Pengeluaran</th>
              <th class="px-4 py-3 border-b text-center">Jenis</th>
              <th class="px-4 py-3 border-b text-right">Jumlah</th>
              <th class="px-4 py-3 border-b text-left">Keterangan</th>
              <th class="px-4 py-3 border-b text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (empty($list_data)): ?>
              <tr>
                <td colspan="5" class="px-4 py-10 text-center text-gray-400 italic">
                  Belum ada pengeluaran untuk bulan <?= htmlspecialchars($filter_bulan) ?>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($list_data as $row): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($row['nama_pengeluaran']) ?></td>
                  <td class="px-4 py-3 text-center">
                    <?php if ($row['jenis'] === 'fix'): ?>
                      <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">Fix</span>
                    <?php else: ?>
                      <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">Tidak Fix</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-right font-bold text-gray-800">Rp <?= fmtIDR2($row['jumlah']) ?></td>
                  <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center space-x-3">
                      <a href="edit-pengeluaran-bulanan-gaharu.php?id=<?= $row['id'] ?>&bulan=<?= urlencode($filter_bulan) ?>"
                        class="text-blue-500 hover:text-blue-700 transition" title="Edit">
                        <span class="material-symbols-outlined text-lg">edit</span>
                      </a>
                      <a href="hapus-pengeluaran-bulanan-gaharu.php?id=<?= $row['id'] ?>&bulan=<?= urlencode($filter_bulan) ?>"
                        onclick="return confirm('Hapus pengeluaran ini?')"
                        class="text-red-500 hover:text-red-700 transition" title="Hapus">
                        <span class="material-symbols-outlined text-lg">delete</span>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <!-- Baris Total -->
              <tr class="bg-gray-100 font-bold text-sm">
                <td class="px-4 py-3 text-gray-700" colspan="2">TOTAL</td>
                <td class="px-4 py-3 text-right text-red-700">Rp <?= fmtIDR2($grand_total) ?></td>
                <td colspan="2"></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <script>
    // Buka modal
    function openModal() {
      document.getElementById('modalTambah').classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    // Tutup modal
    function closeModal() {
      document.getElementById('modalTambah').classList.remove('active');
      document.body.style.overflow = '';
    }

    // Tutup modal saat klik area luar (overlay)
    document.getElementById('modalTambah').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // Format rupiah input di dalam modal
    const inpJumlah = document.getElementById('inp_jumlah');
    if (inpJumlah) {
      inpJumlah.addEventListener('input', function(e) {
        let val = e.target.value.replace(/\./g, '').replace(/\D/g, '');
        e.target.value = val ? parseInt(val).toLocaleString('id-ID') : '';
      });
    }

    <?php if ($error && isset($_POST['simpan'])): ?>
      // Buka modal kembali jika ada error saat submit form tambah
      openModal();
    <?php endif; ?>
  </script>
</body>

</html>