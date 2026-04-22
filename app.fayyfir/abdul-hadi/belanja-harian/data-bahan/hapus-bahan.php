<?php
session_start();
require "../../config.php";
$conn = $conn2;

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID bahan
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
  header("Location: index");
  exit();
}

// Ambil data bahan
$stmt = $conn->prepare("
SELECT * 
FROM bb_bahan_master 
WHERE id = ? 
AND deleted_at IS NULL
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$bahan = $result->fetch_assoc();

if (!$bahan) {
  header("Location: index?error=notfound");
  exit();
}


// ==============================
// PROSES HAPUS (SOFT DELETE)
// ==============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["konfirmasi_hapus"])) {

  // Simpan data lama untuk audit
  $data_lama = json_encode([
    "nama_bahan" => $bahan["nama_bahan"],
    "satuan" => $bahan["satuan"],
    "keterangan" => $bahan["keterangan"]
  ]);

  // Soft delete
  $delete = $conn->prepare("
  UPDATE bb_bahan_master
  SET deleted_at = NOW()
  WHERE id = ?
  ");

  $delete->bind_param("i", $id);

  if ($delete->execute()) {

    // ==============================
    // SIMPAN AUDIT LOG
    // ==============================
    $log = $conn->prepare("
    INSERT INTO bb_bahan_log
    (bahan_id, user_id, aksi, data_lama)
    VALUES (?, ?, 'delete', ?)
    ");

    $log->bind_param(
      "iis",
      $id,
      $_SESSION["user_id"],
      $data_lama
    );

    $log->execute();

    header("Location: index?delete=success");
    exit();

  } else {
    header("Location: index?delete=error");
    exit();
  }
}

$activeMenu = "materials";
$activeModule = "Hapus Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">

    <a href="index"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15 19l-7-7 7-7" />
      </svg>
      Kembali ke daftar bahan
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Hapus Bahan
    </h1>

  </div>


  <!-- Card Konfirmasi -->
  <div
    class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition border border-gray-100">

    <div class="p-6 sm:p-8">

      <div class="mb-5 border-b pb-4">

        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">

          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
          </svg>

          Konfirmasi Penghapusan

        </h2>

        <p class="text-gray-500 mt-1 text-sm leading-relaxed">
          Data bahan tidak akan dihapus permanen, tetapi akan disembunyikan dari daftar bahan aktif.
        </p>

      </div>


      <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">

          <div>
            <dt class="text-gray-500">Nama Bahan</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars($bahan['nama_bahan']) ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Satuan</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars($bahan['satuan']) ?>
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Keterangan</dt>
            <dd class="font-medium text-gray-900">
              <?= htmlspecialchars($bahan['keterangan'] ?: '-') ?>
            </dd>
          </div>

        </dl>

      </div>


      <!-- Tombol -->
      <form method="POST"
        class="mt-8 flex flex-col sm:flex-row gap-3 justify-between">

        <button
          type="submit"
          name="konfirmasi_hapus"
          class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-200 font-medium transition">

          <svg xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5" fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12" />
          </svg>

          Ya, Hapus Sekarang

        </button>
        
        <a href="edit-bahan?id=<?= $bahan['id'] ?>"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white bg-yellow-400 hover:bg-yellow-500 font-medium transition focus:ring-4 focus:ring-yellow-200">
          <img src="/abdul-hadi/belanja-harian/assets/icons/edit-light.svg" class="w-5 h-5" alt="Edit">
          Edit Bahan
        </a>
        
        <a
          href="index"
          class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition">

          <svg xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5" fill="none"
            viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 19l-7-7 7-7" />
          </svg>

          Batal

        </a>

      </form>

    </div>

  </div>

</main>

<?php include "../partials/footer.php"; ?>