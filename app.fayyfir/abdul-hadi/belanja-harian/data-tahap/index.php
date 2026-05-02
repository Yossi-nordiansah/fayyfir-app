<?php
session_start();
require "../../config.php";
$conn = $conn2; // Gunakan koneksi DB alsz2632_ahadi

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

// Ambil data bahan yang memiliki tahap atau ingin diatur tahapnya
$query_materials = "SELECT id, nama_bahan FROM bb_bahan_master WHERE deleted_at IS NULL ORDER BY nama_bahan ASC";
$res_materials = $conn->query($query_materials);

// Ambil data tahap dan kelompokkan di PHP
$query_processes = "SELECT * FROM bb_proses_master ORDER BY id_bahan, urutan_tahap ASC";
$res_processes = $conn->query($query_processes);

$processes_map = [];
while ($row = $res_processes->fetch_assoc()) {
    $processes_map[$row['id_bahan']][] = $row;
}
?>

<?php
// Variabel layout aktif
$activeMenu = "purchases";
$activeModule = "Daftar Tahap";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">

  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6">
    <div class="flex items-center gap-4">
        <a href="../proses-produksi/index" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h2 class="text-2xl font-semibold text-gray-800">Pengaturan Tahapan per Bahan</h2>
    </div>
    <a href="tambah-tahap.php"
      class="mt-3 sm:mt-0 inline-flex items-center justify-center font-medium border border-yellow-500 gap-2 px-4 py-2 bg-yellow-400 text-white rounded-lg hover:bg-yellow-500 transition-all shadow-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Tambah Tahapan Baru
    </a>
  </div>

  <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
      <input id="searchInput" type="text" placeholder="Cari nama bahan..." class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition-all outline-none">
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-800 text-yellow-400 text-left font-semibold uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4 w-16 text-center">No</th>
            <th class="px-6 py-4">Nama Bahan</th>
            <th class="px-6 py-4 text-center">Jumlah Tahap</th>
            <th class="px-6 py-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="divide-y divide-gray-200">
          <?php if ($res_materials && $res_materials->num_rows > 0): ?>
            <?php $no = 1; while ($material = $res_materials->fetch_assoc()): 
                $mid = $material['id'];
                $m_processes = $processes_map[$mid] ?? [];
                $total_tahap = count($m_processes);
            ?>
              <tr class="material-row hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-center text-gray-500"><?= $no++; ?></td>
                <td class="px-6 py-4 font-bold text-gray-800"><?= htmlspecialchars($material["nama_bahan"]); ?></td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $total_tahap > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                    <?= $total_tahap ?> Tahap
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <button onclick="toggleDetail(<?= $mid ?>)" class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1">
                    <span id="btnText-<?= $mid ?>">Detail</span>
                    <svg id="icon-<?= $mid ?>" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </td>
              </tr>
              
              <!-- Detail Row (Hidden by default) -->
              <tr id="detail-<?= $mid ?>" class="hidden bg-gray-50 border-l-4 border-yellow-400">
                <td colspan="4" class="px-6 py-4">
                  <div class="bg-white rounded-lg border border-gray-200 shadow-inner p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-bold text-gray-700">Urutan Tahapan: <?= htmlspecialchars($material["nama_bahan"]); ?></h4>
                        <a href="tambah-tahap.php?id_bahan=<?= $mid ?>" class="text-xs bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1.5 rounded-md font-semibold transition-colors">
                          + Tambah Tahap untuk Bahan Ini
                        </a>
                    </div>
                    
                    <?php if ($total_tahap > 0): ?>
                      <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
                        <thead class="bg-gray-100">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Urutan</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Nama Proses</th>
                            <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 uppercase w-24">Aksi</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                          <?php foreach ($m_processes as $p): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                              <td class="px-4 py-2 text-sm text-gray-600 font-mono">Tahap <?= $p['urutan_tahap'] ?></td>
                              <td class="px-4 py-2 text-sm font-medium text-gray-800"><?= htmlspecialchars($p['nama_proses']) ?></td>
                              <td class="px-4 py-2 text-center">
                                <div class="flex justify-center gap-3">
                                  <a href="edit-tahap.php?id=<?= $p['id'] ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                  </a>
                                  <a href="hapus-tahap.php?id=<?= $p['id'] ?>" onclick="return confirm('Hapus tahap ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                  </a>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <div class="text-center py-6 text-gray-400 text-xs italic">
                        Belum ada tahapan yang diatur untuk bahan ini.
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                Tidak ada data bahan ditemukan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<script>
function toggleDetail(id) {
    const detailRow = document.getElementById('detail-' + id);
    const icon = document.getElementById('icon-' + id);
    const btnText = document.getElementById('btnText-' + id);
    
    if (detailRow.classList.contains('hidden')) {
        detailRow.classList.remove('hidden');
        icon.classList.add('rotate-180');
        btnText.innerText = 'Tutup';
    } else {
        detailRow.classList.add('hidden');
        icon.classList.remove('rotate-180');
        btnText.innerText = 'Detail';
    }
}

// Client-side search
document.getElementById('searchInput').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('.material-row');
    
    rows.forEach(row => {
        const text = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
        const detailRow = row.nextElementSibling;
        
        if (text.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
            detailRow.classList.add('hidden'); // auto-close if filtered out
            const id = detailRow.id.split('-')[1];
            document.getElementById('icon-' + id).classList.remove('rotate-180');
            document.getElementById('btnText-' + id).innerText = 'Detail';
        }
    });
});
</script>

<?php include "../partials/footer.php"; ?>
