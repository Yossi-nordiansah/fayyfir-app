<?php
session_start();
require "../../config.php";
$conn = $conn2; // gunakan koneksi aktif

// Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID pembelian dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
  header("Location: index?error=notfound");
  exit();
}

// Ambil data pembelian berdasarkan ID
$query = $conn->prepare("
  SELECT p.*, s.nama_supplier AS supplier_nama, bm.nama_bahan AS bahan_nama
  FROM bb_pembelian_awal p
  LEFT JOIN bb_supplier s ON p.id_supplier = s.id
  LEFT JOIN bb_bahan_master bm ON p.id_bahan = bm.id
  WHERE p.id = ?
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
  header("Location: index?error=notfound");
  exit();
}

// Hitung total otomatis
$total = $data["berat_awal"] * $data["harga_per_kg"];
$hpp_awal_per_kg = $data["berat_awal"] > 0 ? $total / $data["berat_awal"] : 0;

// Aktifkan highlight menu di sidebar
$activeMenu = "purchases";
$activeModule = "Detail Pembelian Awal";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
    <a href="index"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
        stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Kembali ke daftar pembelian</span>
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">Detail Pembelian Awal</h1>
  </div>

  <!-- Card Detail -->
  <div
    class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
    <div class="p-6 sm:p-8">

      <div class="border-b border-gray-100 pb-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-600" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m-9 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Informasi Pembelian
        </h2>
        <p class="text-gray-500 mt-1 text-sm leading-relaxed">
          Detail lengkap pembelian bahan baku awal yang tercatat dalam sistem.
        </p>
      </div>

      <!-- Grid Detail -->
      <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
          <div>
            <dt class="text-gray-500">Tanggal Pembelian</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars(date("d M Y", strtotime($data["tanggal_pembelian"]))) ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Nama Supplier</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars($data["supplier_nama"] ?? "-") ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Nama Bahan</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars($data["bahan_nama"]) ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Berat (Kg)</dt>
            <dd class="font-medium text-gray-900">
              <?= number_format($data["berat_awal"], 0, ',', '.') ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Harga per Kg (Rp)</dt>
            <dd class="font-medium text-gray-900">
              <?= number_format($data["harga_per_kg"], 0, ',', '.') ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Total Harga (Rp)</dt>
            <dd class="font-semibold text-emerald-700">
              Rp <?= number_format($total, 0, ',', '.') ?>
            </dd>
          </div>

          <div class="sm:col-span-2">
            <dt class="text-gray-500">Catatan</dt>
            <dd class="font-medium text-gray-900">
              <?= nl2br(htmlspecialchars($data["catatan"] ?: "-")) ?>
            </dd>
          </div>

          <!-- Payment Info -->
          <div class="sm:col-span-2 mt-4 pt-4 border-t border-gray-200">
            <dt class="text-blue-600 font-bold uppercase text-xs mb-2">Status Pembayaran</dt>
            <dd>
                <?php 
                $pStatus = $data['status_pembayaran'] ?? 'belum_dibayar';
                $nominal_bayar = (float)($data['nominal_bayar'] ?? 0);
                
                // Fix: Jika lunas, paksa sisa 0 dan nominal = total
                if ($pStatus === 'lunas') {
                    $nominal_bayar = $total;
                    $sisa_bayar = 0;
                } else {
                    $sisa_bayar = $total - $nominal_bayar;
                }
                
                if ($pStatus === 'lunas'): ?>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">LUNAS</span>
                <?php elseif ($pStatus === 'dp'): ?>
                    <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-bold">DP (Uang Muka)</span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">BELUM DIBAYAR</span>
                <?php endif; ?>
                
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3 bg-white border border-gray-200 rounded-lg">
                        <p class="text-xs text-gray-500">Sudah Dibayar</p>
                        <p class="text-lg font-bold text-gray-900">Rp <?= number_format($nominal_bayar, 0, ',', '.') ?></p>
                    </div>
                    <div class="p-3 bg-white border border-gray-200 rounded-lg">
                        <p class="text-xs text-gray-500">Sisa Pembayaran</p>
                        <p class="text-lg font-bold <?= $sisa_bayar > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                            Rp <?= number_format($sisa_bayar, 0, ',', '.') ?>
                        </p>
                    </div>
                </div>
            </dd>
          </div>
        </dl>
      </div>

      <div
        class="mt-8 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white p-5 rounded-xl shadow-inner flex items-center justify-between">
        <div class="flex flex-col">
            <span class="text-lg font-medium">💰 Harga awal:</span>
            <span class="text-2xl font-bold">
              Rp <?= number_format($hpp_awal_per_kg, 0, ',', '.') ?> / Kg
            </span>
        </div>
      </div>

      <!-- Tombol Aksi -->
      <div class="mt-8 flex flex-col sm:flex-row justify-between gap-3">
        <a href="index"
          class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Kembali
        </a>

        <div class="flex flex-wrap gap-2">
          <!-- Tombol Pembayaran -->
          <a href="pembayaran.php?id=<?= $id ?>"
            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Pembayaran
          </a>

          <a href="../proses-produksi/mulai-produksi?id=<?= $id ?>"
            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M13 9l3 3-3 3m8-3H3" />
            </svg>
            Lanjut ke Produksi
          </a>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include "../partials/footer.php"; ?>