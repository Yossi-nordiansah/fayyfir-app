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
$stmt = $conn->prepare("SELECT * FROM bb_bahan_master WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$bahan = $result->fetch_assoc();

if (!$bahan) {
  header("Location: index?error=notfound");
  exit();
}

$success = false;
$error = "";

// Proses update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $nama_bahan = trim($_POST["nama_bahan"] ?? "");
  $satuan = trim($_POST["satuan"] ?? "");
  $keterangan = trim($_POST["keterangan"] ?? "");

  // Validasi
  if ($nama_bahan === "" || $satuan === "") {
    $error = "Nama bahan dan satuan wajib diisi.";
  } else {

    // Cek duplikat bahan
    $check = $conn->prepare("
      SELECT id FROM bb_bahan_master 
      WHERE nama_bahan = ? 
      AND id != ? 
      AND deleted_at IS NULL
    ");

    $check->bind_param("si", $nama_bahan, $id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
      $error = "Nama bahan sudah digunakan.";
    } else {

      // ==========================
      // DATA LAMA (UNTUK AUDIT)
      // ==========================
      $data_lama = json_encode([
        "nama_bahan" => $bahan["nama_bahan"],
        "satuan" => $bahan["satuan"],
        "keterangan" => $bahan["keterangan"]
      ]);

      // ==========================
      // DATA BARU
      // ==========================
      $data_baru = json_encode([
        "nama_bahan" => $nama_bahan,
        "satuan" => $satuan,
        "keterangan" => $keterangan
      ]);

      // ==========================
      // UPDATE DATA
      // ==========================
      $update = $conn->prepare("
        UPDATE bb_bahan_master 
        SET nama_bahan = ?, satuan = ?, keterangan = ?
        WHERE id = ?
      ");

      $update->bind_param("sssi", $nama_bahan, $satuan, $keterangan, $id);

      if ($update->execute()) {

        // ==========================
        // SIMPAN AUDIT LOG
        // ==========================
        $log = $conn->prepare("
          INSERT INTO bb_bahan_log 
          (bahan_id, user_id, aksi, data_lama, data_baru)
          VALUES (?, ?, 'update', ?, ?)
        ");

        $log->bind_param(
          "iiss",
          $id,
          $_SESSION["user_id"],
          $data_lama,
          $data_baru
        );

        $log->execute();

        $_SESSION["success"] = "Perubahan bahan berhasil disimpan.";
        header("Location: index");
        exit();

      } else {
        $error = "Terjadi kesalahan saat menyimpan perubahan.";
      }
    }
  }
}

$activeMenu = "materials";
$activeModule = "Edit Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">

    <?php
    $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index';
    ?>
    
    <a href="<?= $back_url ?>"
       class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium">
       <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
         stroke-width="2" stroke="currentColor">
         <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
       </svg>
       Kembali
    </a>

    <h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
      Edit Bahan
    </h1>

  </div>


  <!-- Notifikasi error -->
  <?php if (!empty($error)): ?>
  <div class="max-w-2xl mx-auto mb-6">
    <div class="flex items-center gap-2 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none"
        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M21 12a9 9 0 11-9-9" />
      </svg>
      <?= htmlspecialchars($error) ?>
    </div>
  </div>
  <?php endif; ?>


  <!-- Form -->
  <form method="POST"
    class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md hover:shadow-lg transition border border-gray-100 p-6 sm:p-8">


    <div class="mb-5 border-b pb-4">
      <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none"
          viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M11 17a4 4 0 100-8 4 4 0 000 8zm0-8V5m0 12v2m6-6h2M3 11H1" />
        </svg>

        Detail Bahan
      </h2>

      <p class="text-gray-500 mt-1 text-sm">
        Ubah data bahan kemudian tekan <strong>Simpan Perubahan</strong>.
      </p>
    </div>


    <!-- ID -->
    <div class="mb-5">
      <label class="block text-sm font-medium text-gray-700 mb-1">
        ID Bahan
      </label>

      <input type="text"
        value="<?= $bahan['id'] ?>"
        readonly
        class="w-full bg-gray-100 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-600">
    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">

      <!-- Nama Bahan -->
      <div>

        <label class="block text-sm font-medium text-gray-700 mb-1">
          Nama Bahan <span class="text-red-500">*</span>
        </label>

        <input
          type="text"
          name="nama_bahan"
          required
          value="<?= htmlspecialchars($bahan['nama_bahan']) ?>"
          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none"
        >

      </div>


      <!-- Satuan -->
      <div>

        <label class="block text-sm font-medium text-gray-700 mb-1">
          Satuan <span class="text-red-500">*</span>
        </label>
        
        <select
          name="satuan"
          required
          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none appearance-none bg-white">
          <option value="" disabled>Pilih Satuan</option>
          <?php 
          $satuans = ["Kg", "Ton", "Gram", "Liter", "Karung", "Sak", "Box", "Pcs"];
          foreach ($satuans as $s): ?>
            <option value="<?= $s ?>" <?= ($bahan['satuan'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>


    <!-- Keterangan -->
    <div class="mb-6">

      <label class="block text-sm font-medium text-gray-700 mb-1">
        Keterangan
      </label>

      <textarea
        name="keterangan"
        rows="3"
        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none"
      ><?= htmlspecialchars($bahan['keterangan']) ?></textarea>

    </div>


    <!-- Created at -->
    <div class="mb-8">

      <label class="block text-sm font-medium text-gray-700 mb-1">
        Tanggal Dibuat
      </label>

      <input
        type="text"
        readonly
        value="<?= date('d M Y H:i', strtotime($bahan['created_at'])) ?>"
        class="w-full bg-gray-100 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-600"
      >

    </div>


    <!-- Buttons -->
    <div class="flex flex-col sm:flex-row justify-end gap-3">

      <button
        type="submit"
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 font-medium transition"
      >

        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
          viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M5 13l4 4L19 7" />
        </svg>

        Simpan Perubahan

      </button>
      
      <a
        href="<?= $back_url ?>"
        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium transition"
      >

        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
          viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M15 19l-7-7 7-7" />
        </svg>

        Batal

      </a>

    </div>

  </form>

</main>

<?php include "../partials/footer.php"; ?>