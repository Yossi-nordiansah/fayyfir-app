<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";
require "../includes/functions.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// --- Ambil ID penjualan ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("ID penjualan tidak valid.");
}

// --- Ambil data lengkap penjualan ---
$sql = "
    SELECT
        p.*,
        b.nama_buyer,
        SUM(biaya_exp) AS total_biaya_exp,

        (
            SELECT bm.nama_bahan
            FROM bb_pembelian_awal pa
            LEFT JOIN bb_bahan_master bm ON bm.id = pa.id_bahan
            WHERE pa.status = 'siap_jual'
            ORDER BY pa.id ASC
            LIMIT 1
        ) AS nama_bahan,

        (
            SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
            FROM bb_pembelian_awal pa
            LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
            WHERE pa.status = 'siap_jual'
        ) AS harga_akhir_perkg,

        (
            p.berat_jual *
            (
                SELECT SUM(pa.total_modal) / NULLIF(SUM(ps.berat_akhir), 0)
                FROM bb_pembelian_awal pa
                LEFT JOIN bb_proses_sortir ps ON ps.id_pembelian = pa.id
                WHERE pa.status = 'siap_jual'
            )
        ) AS total_modal_akhir

    FROM bb_penjualan p
    LEFT JOIN bb_buyer b ON p.id_buyer = b.id
    LEFT JOIN bb_pengeluaran k ON k.id_penjualan = p.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  die("Data penjualan tidak ditemukan.");
}
$data = $result->fetch_assoc();

// --- Hitung HPP & Laba Bersih ---
$hpp_per_kg = $data['total_modal'] / ($data['berat_awal'] ?: 1);
$total_hpp = $hpp_per_kg * $data['berat_jual'];
$laba_bersih = $data['total_penjualan'] - $data['total_modal_akhir'];

// Ambil semua data pengeluaran (dengan prepared statement)
$stmt2 = $conn->prepare("
  SELECT 
    p.*
  FROM bb_pengeluaran p
  JOIN bb_penjualan pj ON p.id_penjualan = pj.id
  WHERE p.id_penjualan = ?
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$result_2 = $stmt2->get_result();

$activeMenu = "sales";
$activeModule = "Detail Penjualan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
    <a href="index.php" class="inline-flex items-center text-gray-700 hover:text-gray-900 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Kembali ke Daftar Penjualan
    </a>
    <h1 class="text-2xl font-semibold text-gray-800 mt-4 sm:mt-0">Detail Penjualan</h1>
  </div>

  <!-- Card Detail -->
  <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">
          Invoice <span class="text-blue-600"><?= htmlspecialchars($data['no_invoice']) ?></span>
        </h2>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($data['nama_bahan']) ?> — <?= htmlspecialchars($data['nama_buyer']) ?></p>
      </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-100 mb-8">
      <table class="min-w-full divide-y divide-gray-100 text-sm">
        <tbody class="divide-y divide-gray-50">
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600 w-1/3">Tanggal Jual</th>
            <td class="py-3 px-4 text-gray-800"><?= format_tanggal($data['tanggal_jual']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Berat Jual</th>
            <td class="py-3 px-4 text-gray-800"><?= number_format($data['berat_jual'], 2) ?> kg</td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Harga Jual / Kg</th>
            <td class="py-3 px-4 text-gray-800"><?= format_rupiah($data['harga_jual_per_kg']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50 bg-gray-50">
            <th class="text-left py-3 px-4 font-semibold text-gray-700">Harga Jual</th>
            <td class="py-3 px-4 font-semibold text-blue-700"><?= format_rupiah($data['total_penjualan']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Total Biaya</th>
            <td class="py-3 px-4 text-gray-800"><?= format_rupiah($data['total_biaya_exp']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50 bg-gray-50">
            <th class="text-left py-3 px-4 font-semibold text-gray-700">Total Penjualan</th>
            <td class="py-3 px-4 font-semibold text-blue-700"><?= format_rupiah($data['total_penjualan'] + $data['total_biaya_exp']) ?></td>
          </tr>
          <tr class="hover:bg-gray-50">
            <th class="text-left py-3 px-4 font-medium text-gray-600">Keterangan</th>
            <td class="py-3 px-4 text-gray-700"><?= nl2br(htmlspecialchars($data['keterangan'] ?: '-')) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
      
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Biaya Pengeluaran</h2>
        <!-- <p class="text-sm text-gray-500"><?= htmlspecialchars($data['nama_bahan']) ?> — <?= htmlspecialchars($data['nama_buyer']) ?></p> -->
      </div>
      <a href="pengeluaran?id_penjualan=<?= $id ?>"        
        class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-yellow-400 bg-gray-800 hover:bg-gray-900 font-medium transition">
        + Pengeluaran
      </a>
    </div>
    
    <!-- Tabel Pengeluaran -->
    <div class="overflow-x-auto rounded-xl border border-gray-100">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 font-semibold">
          <tr class="whitespace-nowrap">
            <th class="px-5 py-3 text-center font-medium text-gray-600">No</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Pengeluaran</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Biaya</th>
            <th class="px-5 py-3 text-center font-medium text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if ($result_2->num_rows > 0): ?>
            <?php $no = 1; while ($row = $result_2->fetch_assoc()):
            $total_pengeluaran += $row['biaya_exp'];
            
            ?>
              <?php
                $isEmpty = empty($row['deskripsi_exp']);
                $textColor = $isEmpty ? 'text-red-500' : 'text-gray-800';
              ?>
              <tr class="whitespace-nowrap hover:bg-gray-50 transition">
                <td class="text-left px-5 py-3"><?= $no++ ?></td>
                <td class="text-left px-5 py-3 font-medium"><?= htmlspecialchars($row['deskripsi_exp'] ?: '-') ?></td>
                <td class="text-right px-5 py-3"><?= format_rupiah($row['biaya_exp']) ?></td>
                <td class="px-5 py-3 text-center">
                  <div class="flex justify-center gap-4">        
                  <a href="edit-pengeluaran?id_pengeluaran=<?= $row['id'] ?>&id_penjualan=<?= $id ?>"        
                    class="inline-flex justify-center items-center rounded-lg border border-gray-300 shadow-sm px-5 py-1.5 bg-blue-600 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Edit        
                  </a>
                  <a href="hapus-pengeluaran?id_pengeluaran=<?= $row['id'] ?>&id_penjualan=<?= $id ?>"
                     onclick="return confirm('Menghapus data pengeluaran akan mempengaruhi total harga akhir. Apakah Anda yakin ingin menghapusnya?');"
                     class="inline-flex justify-center items-center rounded-lg border border-gray-300 shadow-sm px-3 py-1.5 bg-red-600 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-300">
                     Hapus
                  </a>        
                </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="px-5 py-10 text-center text-gray-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                  <span class="text-gray-500">Belum ada data pengeluaran.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot class="bg-gray-50 font-semibold">
          <tr class="whitespace-nowrap">
            <td colspan="2" class="text-right px-5 py-3">Total</td>
            <td class="text-right px-5 py-3"><?= format_rupiah($total_pengeluaran) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <!-- Tombol Aksi -->
      <div class="flex justify-between flex-col sm:flex-row gap-3 pt-4 mt-8">
      
        <a href="edit-penjualan?id_penjualan=<?= $data['id'] ?>"
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-medium transition">
           Edit Penjualan
        </a>
        
        <!-- Tombol Hapus -->
        <button type="button"
          onclick="cekSebelumHapus(<?= $id ?>)"
          class="inline-flex justify-center items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium shadow-sm transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12" />
          </svg>
          Hapus Data
        </button>
      </div>
      
      <!-- SweetAlert2 -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      
      <script>
      async function cekSebelumHapus(idPenjualan) {
        try {
          const res = await fetch(`cek-pengeluaran.php?id=${idPenjualan}`);
          const data = await res.json();
      
          if (data.ada_pengeluaran) {
            // Jika masih ada pengeluaran
            Swal.fire({
              icon: 'warning',
              title: 'Tidak dapat menghapus data!',
              text: 'Penjualan ini masih memiliki data pengeluaran terkait. Hapus terlebih dahulu semua data pengeluaran sebelum menghapus data penjualan ini.',
              confirmButtonText: 'Mengerti',
              confirmButtonColor: '#f59e0b',
              backdrop: true,
              allowOutsideClick: false,
              customClass: {
                popup: 'rounded-2xl shadow-lg'
              }
            });
          } else {
            // Jika aman untuk dihapus
            Swal.fire({
              title: 'Yakin ingin menghapus data penjualan ini?',
              text: "Tindakan ini tidak dapat dibatalkan!",
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Ya, hapus!',
              cancelButtonText: 'Batal',
              confirmButtonColor: '#dc2626',
              cancelButtonColor: '#6b7280',
              reverseButtons: true,
              backdrop: true,
              customClass: {
                popup: 'rounded-2xl shadow-lg'
              }
            }).then((result) => {
              if (result.isConfirmed) {
                Swal.fire({
                  title: 'Menghapus...',
                  text: 'Mohon tunggu sebentar.',
                  allowOutsideClick: false,
                  didOpen: () => Swal.showLoading(),
                });
                // Redirect ke halaman hapus
                window.location.href = `hapus-penjualan?id=${idPenjualan}`;
              }
            });
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Terjadi kesalahan!',
            text: 'Gagal memeriksa data pengeluaran. Coba lagi nanti.',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#ef4444'
          });
        }
      }
      </script>
  </div>
</main>

<?php include "../partials/footer.php"; ?>