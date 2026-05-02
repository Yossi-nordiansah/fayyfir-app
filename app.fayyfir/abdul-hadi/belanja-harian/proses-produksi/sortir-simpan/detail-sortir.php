<?php
session_start();
require "../../../config.php";
$conn = $conn2;
require "../../includes/helpers.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../../login");
  exit();
}

// Ambil ID batch sortir dari query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
  die("Data tidak valid.");
}

// Ambil data sortir + pembelian + bahan
$stmt = $conn->prepare("
  SELECT 
    ps.*,
    pa.kode_batch,
    pa.berat_awal,
    pa.harga_per_kg,
    pa.total_modal,
    pj.berat_setelah_jemur,
    pj.penyusutan_jemur,
    pk.berat_setelah_kupas,
    pk.penyusutan_kupas,
    b.nama_bahan,
    COALESCE(SUM(p.biaya_exp), 0) AS total_biaya,
    (COALESCE(SUM(p.biaya_exp),0) / ps.berat_akhir) AS exp_kg
  FROM bb_proses_sortir ps
  JOIN bb_pembelian_awal pa ON ps.id_pembelian = pa.id
  LEFT JOIN bb_proses_jemur pj ON pj.id_pembelian = pa.id
  LEFT JOIN bb_proses_kupas pk ON pk.id_pembelian = pa.id
  LEFT JOIN bb_pengeluaran p ON p.id_pembelian = pa.id
  JOIN bb_bahan_master b ON pa.id_bahan = b.id
  WHERE ps.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  die("Data batch sortir tidak ditemukan.");
}
$data = $result->fetch_assoc();
$stmt->close();

// Hitung penyusutan & HPP
$id_pembelian = $data['id_pembelian'];
$berat_awal = $data['berat_awal'];
$berat_setelah_1 = $data['berat_setelah_jemur'];
$berat_setelah_2 = $data['berat_setelah_kupas'];
$berat_setelah_3 = $data['berat_akhir'] ?? 0;
$susut_setelah_1 = $data['penyusutan_jemur'] ?? 0;
$susut_setelah_2 = $data['penyusutan_kupas'] ?? 0;
$susut_setelah_3 = $data['penyusutan_total'] ?? 0;
$penyusutan_total = $susut_setelah_1 + $susut_setelah_2 + $susut_setelah_3;
$harga_awal = $data['harga_per_kg'];

// Hitung harga setelah proses
$harga_setelah_1 = ($berat_setelah_1 > 0) ? ($harga_awal * $berat_awal / $berat_setelah_1) : null;
$harga_setelah_2 = ($berat_setelah_2 > 0) ? ($harga_awal * $berat_awal / $berat_setelah_2) : null;
$harga_setelah_3 = ($berat_setelah_3 > 0) ? ($harga_awal * $berat_awal / $berat_setelah_3) : null;

// Hitung total harga akhir per kg dan total modal akhir
$harga_akhir_perkg = (!empty($harga_setelah_3) && !empty($data['exp_kg'])) 
  ? ($harga_setelah_3 + $data['exp_kg']) 
  : null;
$total_modal_akhir = (!empty($harga_akhir_perkg) && $berat_setelah_3 > 0) 
  ? ($harga_akhir_perkg * $berat_setelah_3) 
  : null;

// Cek kelengkapan data
$data_lengkap = $berat_setelah_3 > 0 && $harga_akhir_perkg !== null && $total_modal_akhir !== null;

// Ambil semua data pengeluaran (dengan prepared statement)
$stmt2 = $conn->prepare("
  SELECT 
    p.*,
    pa.kode_batch, 
    pa.berat_awal, 
    pa.harga_per_kg
  FROM bb_pengeluaran p
  JOIN bb_pembelian_awal pa ON p.id_pembelian = pa.id
  WHERE p.id_pembelian = ?
");
$stmt2->bind_param("i", $id_pembelian);
$stmt2->execute();
$result_2 = $stmt2->get_result();

$activeMenu = "productions";
$activeModule = "Detail Sortir & Simpan";

include "../../partials/header.php";
include "../../partials/sidebar.php";
include "../../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="list-sortir.php"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar penyimpanan</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Detail Batch Sortir & Penyimpanan
    </h1>
  </div>

  <!-- Card Detail -->
  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-100 transition-all duration-200">
    <div class="p-6 sm:p-8">
      <!-- Header Info -->
      <div class="mb-6 border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Batch <?= htmlspecialchars($data['kode_batch']) ?></h2>
          <p class="text-sm text-gray-500 mt-1">Rincian hasil proses dan penyimpanan bahan</p>
        </div>
        <div class="mt-3 sm:mt-0 px-3 py-1.5 text-sm font-medium bg-yellow-100 text-yellow-700 rounded-full border border-yellow-200">
          <?= htmlspecialchars($data['nama_bahan']) ?>
        </div>
      </div>

      <!-- Data Grid -->
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
        <div><dt class="text-gray-500 mb-1">Modal Awal</dt><dd class="font-semibold text-gray-900"><?= format_rupiah($data['total_modal']) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Berat Awal (kg)</dt><dd class="font-semibold text-gray-900"><?= format_angka($berat_awal) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Harga Awal</dt><dd class="font-semibold text-gray-900"><?= format_rupiah($harga_awal) ?>/Kg</dd></div>
        <div><dt class="text-gray-500 mb-1">Berat Setelah Proses 1 (kg)</dt><dd class="font-semibold text-gray-900"><?= format_angka($berat_setelah_1) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Penyusutan Proses 1 (%)</dt><dd class="font-semibold text-gray-900"><?= format_persen($susut_setelah_1) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Harga Setelah Proses 1</dt><dd class="font-semibold text-gray-900"><?= format_rupiah($harga_setelah_1) ?>/Kg</dd></div>
        <div><dt class="text-gray-500 mb-1">Berat Setelah Proses 2 (kg)</dt><dd class="font-semibold text-gray-900"><?= format_angka($berat_setelah_2) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Penyusutan Proses 2 (%)</dt><dd class="font-semibold text-gray-900"><?= format_persen($susut_setelah_2) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Harga Setelah Proses 2</dt><dd class="font-semibold text-gray-900"><?= format_rupiah($harga_setelah_2) ?>/Kg</dd></div>
        <div><dt class="text-gray-500 mb-1">Berat Akhir (kg)</dt><dd class="font-semibold text-gray-900"><?= format_angka($berat_setelah_3) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Penyusutan Akhir (%)</dt><dd class="font-semibold text-red-600"><?= format_persen($susut_setelah_3) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Harga Akhir/Kg</dt><dd class="font-semibold text-gray-900"><?= format_rupiah($harga_setelah_3) ?>/Kg</dd></div>
        <div><dt class="text-gray-500 mb-1">Tanggal Proses</dt><dd class="font-semibold text-gray-900"><?= htmlspecialchars(format_tanggal($data['tanggal_simpan'])) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Lokasi Simpan</dt><dd class="font-semibold text-gray-900"><?= htmlspecialchars($data['lokasi_simpan']) ?></dd></div>
        <div><dt class="text-gray-500 mb-1">Keterangan</dt><dd class="font-medium text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($data['catatan'] ?: '-')) ?></dd></div>
      </dl>

      <!-- Status Box -->
      <?php if ($berat_setelah_3 == 0): ?>
        <div class="mt-8 bg-red-50 border border-red-200 rounded-xl p-4 sm:p-5 flex items-center gap-4 animate-pulse">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-red-100 text-red-600 font-semibold">!</div>
          <div>
            <p class="text-sm text-red-500">Berat akhir sortir belum diinput.</p>
            <p class="text-base font-semibold text-gray-800">Silakan lengkapi data berat akhir terlebih dahulu.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-5 flex items-center gap-4">
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-700 font-semibold">✅</div>
          <div>
            <p class="text-sm text-gray-500">Proses sortir selesai</p>
            <p class="text-base font-semibold text-gray-900">
              Total penyusutan <?= format_persen($penyusutan_total) ?>
              (<?= format_angka($berat_awal - $berat_setelah_3) ?> kg hilang)
            </p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Tombol Aksi -->
      <div class="flex justify-between flex-col sm:flex-row gap-3 pt-4 mt-8">
        <!-- Hidden -->
        <div hidden class="relative inline-block text-left">
          <button type="button"
            class="inline-flex justify-center items-center w-full rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-green-600 font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300"
            onclick="this.nextElementSibling.classList.toggle('hidden')">
            Input Data
            <svg class="w-4 h-4 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div
            class="hidden absolute left-0 mt-2 w-50 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-lg z-[99]">
            <a href="input-sortir?id=<?= $data['id_pembelian'] ?>"
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Input Penyusutan</a>
            <a href="pengeluaran?id=<?= $data['id_pembelian'] ?>"
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tambah Pengeluaran</a>
          </div>
        </div>
      
        <a href="input-sortir?id=<?= $data['id_pembelian'] ?>" 
           class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-medium transition">
           Input Data Susut
        </a>
        
        <!-- Tombol Hapus -->
        <button type="button"
          onclick="cekSebelumHapus(<?= $id_pembelian ?>)"
          class="inline-flex justify-center items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow-sm transition">
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
      async function cekSebelumHapus(idPembelian) {
        try {
          const res = await fetch(`cek-pengeluaran.php?id=${idPembelian}`);
          const data = await res.json();
      
          if (data.ada_pengeluaran) {
            // Jika masih ada pengeluaran
            Swal.fire({
              icon: 'warning',
              title: 'Tidak dapat menghapus data!',
              text: 'Batch ini masih memiliki data pengeluaran terkait. Hapus terlebih dahulu semua data pengeluaran sebelum menghapus batch ini.',
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
              title: 'Yakin ingin menghapus batch ini?',
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
                window.location.href = `hapus-sortir.php?id=${idPembelian}`;
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
  </div>
</main>

<?php include "../../partials/footer.php"; ?>