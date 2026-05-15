<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$area_result = $conn->query("SELECT DISTINCT region_name FROM users WHERE region_name IS NOT NULL");
$filter_area = isset($_GET['filter_area']) ? $_GET['filter_area'] : '';

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan'])) {
  $region_name = $_POST["area"];
  $description = $_POST["keterangan"];
  $amount = str_replace('.', '', $_POST["harga_jual"]); // hilangkan titik

  // Simpan ke database
  $stmt = $conn->prepare(
    "INSERT INTO fixed_expenses (region_name, expense_type, amount) 
     VALUES (?, ?, ?)"
  );
  $stmt->bind_param(
    "sss",
    $region_name,
    $description,
    $amount
  );

  if ($stmt->execute()) {
    header("Location: tambah-pengeluaran-tetap.php");
    exit();
  } else {
    $error = "Gagal menyimpan data. Silakan coba lagi.";
  }
  $stmt->close();
}

// Ambil data untuk tabel
$query = "SELECT * FROM fixed_expenses";
if ($filter_area != '') {
    $query .= " WHERE region_name = '" . $conn->real_escape_string($filter_area) . "'";
}
$query .= " ORDER BY id DESC";
$expenses_result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pengeluaran Tetap - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Pengeluaran Tetap</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 mx-auto space-y-8">
    
    <!-- Form Tambah -->
    <section class="bg-white shadow rounded-lg p-6">
      <h2 class="text-md font-bold mb-4 flex items-center space-x-2">
        <span class="material-symbols-outlined">add_circle</span>
        <span>Tambah Pengeluaran Baru</span>
      </h2>
      <form class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end" method="POST">
        <div>
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Area</label>
          <select name="area" required class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50">
            <option value="">-- Pilih Area --</option>
            <?php 
            $area_result->data_seek(0);
            while($r = $area_result->fetch_assoc()): 
            ?>
              <option value="<?= htmlspecialchars($r['region_name']) ?>"><?= htmlspecialchars($r['region_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Deskripsi</label>
          <input type="text" name="keterangan" required placeholder="Contoh: Listrik, Air, Gaji" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Biaya (Rp)</label>
          <div class="flex space-x-2">
            <input type="text" name="harga_jual" id="harga_jual" required class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-yellow-300 focus:outline-none bg-gray-50" />
            <button type="submit" name="simpan" class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold px-4 py-2 rounded transition flex items-center space-x-1">
              <span class="material-symbols-outlined text-sm">save</span>
              <span>Simpan</span>
            </button>
          </div>
        </div>
      </form>
    </section>

    <!-- Tabel Data -->
    <section class="bg-white shadow rounded-lg overflow-hidden">
      <div class="p-6 border-b flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h2 class="text-md font-bold flex items-center space-x-2">
          <span class="material-symbols-outlined">list_alt</span>
          <span>Daftar Pengeluaran Tetap</span>
        </h2>
        
        <!-- Filter Area -->
        <form method="GET" class="flex items-center space-x-2">
          <label class="text-sm font-medium text-gray-600">Filter Area:</label>
          <select name="filter_area" onchange="this.form.submit()" class="border px-3 py-1 rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-300">
            <option value="">Semua Area</option>
            <?php 
            $area_result->data_seek(0);
            while($r = $area_result->fetch_assoc()): 
            ?>
              <option value="<?= htmlspecialchars($r['region_name']) ?>" <?= $filter_area == $r['region_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['region_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
          <?php if($filter_area != ''): ?>
            <a href="tambah-pengeluaran-tetap.php" class="text-red-500 hover:text-red-700">
              <span class="material-symbols-outlined text-base">close</span>
            </a>
          <?php endif; ?>
        </form>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
              <th class="px-6 py-3 border-b">Area</th>
              <th class="px-6 py-3 border-b">Deskripsi</th>
              <th class="px-6 py-3 border-b text-right">Biaya</th>
              <th class="px-6 py-3 border-b text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-gray-100">
            <?php if ($expenses_result->num_rows > 0): ?>
              <?php while($row = $expenses_result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($row['region_name']) ?></td>
                  <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['expense_type']) ?></td>
                  <td class="px-6 py-4 text-right font-bold text-gray-900">Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                  <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center space-x-3">
                      <a href="edit-pengeluaran-tetap.php?id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 transition" title="Edit">
                        <span class="material-symbols-outlined text-lg">edit</span>
                      </a>
                      <a href="hapus-pengeluaran-tetap.php?id=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="text-red-500 hover:text-red-700 transition" title="Hapus">
                        <span class="material-symbols-outlined text-lg">delete</span>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data pengeluaran.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    // Format angka ribuan di input harga
    document.getElementById("harga_jual").addEventListener("input", function(e) {
      let value = e.target.value.replace(/\./g, "").replace(/\D/g, "");
      if (value !== "") {
        e.target.value = parseInt(value).toLocaleString("id-ID");
      } else {
        e.target.value = "";
      }
    });
  </script>
</body>
</html>