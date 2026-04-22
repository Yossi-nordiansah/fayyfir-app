<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Proses ubah status kontainer menjadi 'lunas'
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["mark_accepted"])) {
    $container_id = intval($_POST["container_id"]);
    $user_id = $_SESSION["user_id"] ?? null;

    if ($container_id && $user_id) {
      $stmt = $conn->prepare("UPDATE containers SET status = 'accepted', accepted_at = NOW() WHERE id = ?");
      $stmt->bind_param("i", $container_id);
      $stmt->execute();
      $stmt->close();

      header("Location: sudah-diterima?accepted=success");
      exit();
    }
  }

  // Proses update nomor kontainer
  if (isset($_POST['update_number'])) {
    $id = intval($_POST['container_id']);
    $number = trim($_POST['number']);
    if ($id && $number !== '') {
      $stmt = $conn->prepare("UPDATE containers SET number = ? WHERE id = ?");
      $stmt->bind_param("si", $number, $id);
      $stmt->execute();
      $stmt->close();
      $_SESSION['status_pesan'] = "Nomor kontainer berhasil diperbarui.";
      header("Location: verifikasi.php");
      exit();
    }
  }
  
  // Proses update tanggal verifikasi kontainer
  if (isset($_POST['update_TglVer'])) {
    $id = intval($_POST['container_id']);
    $tanggal = trim($_POST['tanggal']);
    if ($id && $tanggal !== '') {
      $stmt = $conn->prepare("UPDATE containers SET verified_at = ? WHERE id = ?");
      $stmt->bind_param("si", $tanggal, $id);
      $stmt->execute();
      $stmt->close();
      $_SESSION['status_pesan'] = "Tanggal verifikasi kontainer berhasil diperbarui.";
      header("Location: verifikasi.php");
      exit();
    }
  }
}

$level = $_SESSION["role_id"] ?? "";

$query = "
  SELECT c.*, p.name AS product_name 
  FROM containers c
  LEFT JOIN products p ON c.product_id = p.id
  WHERE c.status = 'verified'
  ORDER BY c.number ASC
";

$result = $conn->query($query);
?><!DOCTYPE html><html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verifikasi Kontainer - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Kontainer Terverifikasi</h1>
    </div>
  </header>
  <main class="pt-20 px-4 pb-32 mx-auto space-y-6 max-w-6xl lg:max-w-full">
    <?php if (isset($_GET["lunas"]) && $_GET["lunas"] === "success"): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
        Status kontainer berhasil diubah menjadi <strong>Lunas</strong>.
      </div>
    <?php endif; ?>
    <?php if (isset($_SESSION["status_pesan"])): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
        <?= $_SESSION["status_pesan"];
        unset($_SESSION["status_pesan"]); ?>
      </div>
    <?php endif; ?>
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="bg-white rounded-lg shadow p-4 text-gray-800 space-y-2">
      <a href="riwayat-kontainer2?id=<?= $row["id"] ?>" class="block hover:opacity-90">
        <div class="flex items-center space-x-4">
          <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>
          <div>
            <h2 class="text-sm text-gray-500"><?= htmlspecialchars($row["container_number"]) ?></h2>
            <p class="text-2xl font-bold text-gray-500"><?= htmlspecialchars($row["number"]) ?></p>
            <h2 class="text-sm text-gray-500">Produk: <?= htmlspecialchars($row["product_name"] ?? "-") ?> | Area: <?= htmlspecialchars($row["region_name"] ?? "-") ?></h2>
          </div>
        </div>
      </a>
      <div class="flex items-center justify-between gap-1">
        <span class="text-sm text-gray-500">Status:
          <?php if ($row["status"] === "verified"): ?>
            <span class="text-green-500 font-semibold">Verified</span>
          <?php elseif ($row["status"] === "lunas"): ?>
            <span class="text-green-700 font-semibold">Lunas</span>
          <?php endif; ?>
        </span>
        <div class="flex gap-1">
          <!-- Tombol Nomor -->
          <button onclick="openNomorModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['number']) ?>')" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1 rounded">Nomor</button>
          <!-- Tombol Tandai Lunas -->
          <form method="POST" onsubmit="return confirm('Apakah Anda yakin kontainer tersebut telah diterima?');">
            <input type="hidden" name="container_id" value="<?= $row["id"] ?>">
            <button type="submit" name="mark_accepted" class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1 rounded">Tandai Diterima</button>
          </form>
        </div>
      </div>
      <div class="flex items-center justify-between gap-1" style="margin-bottom: -10px;">
        <span class="text-sm text-gray-300">Closed
        </span>
        <span class="text-sm text-gray-300">Diterima
        </span>
        <span class="text-sm text-gray-300">Lunas
        </span>
      </div>
      <div class="flex items-center justify-between gap-1">
        <button onclick="openTglVerModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['verified_at']) ?>')" class="text-sm text-gray-500">
          <?= !empty($row["verified_at"]) ? date("d/m/Y", strtotime($row["verified_at"])) : "-" ?>
        </button>
        <span class="text-sm text-gray-500">
          <?= !empty($row["accepted_at"]) ? date("d/m/Y", strtotime($row["accepted_at"])) : "-" ?>
        </span>
        <span class="text-sm text-gray-500">
          <?= !empty($row["lunas_at"]) ? date("d/m/Y", strtotime($row["lunas_at"])) : "-" ?>
        </span>
      </div>
    </div>
  <?php endwhile; ?>
</section>

  </main>
  
  <!-- Modal Nomor -->
  <div id="modalNomor" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm relative">
      <h2 class="text-lg font-semibold mb-4">Isi Nomor</h2>
      <form method="POST">
        <input type="hidden" name="container_id" id="modalContainerId">
        <input type="text" name="number" id="modalContainerNumber" class="w-full border px-3 py-2 rounded mb-4" required>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeNomorModal()" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Batal</button>
          <button type="submit" name="update_number" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Simpan</button>
        </div>
      </form>
      <button onclick="closeNomorModal()" class="absolute top-2 right-2 text-xl text-gray-500 hover:text-black">&times;</button>
    </div>
  </div>
  
  <!-- Modal Tanggal Verifikasi -->
  <div id="modalTglVer" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm relative">
      <h2 class="text-lg font-semibold mb-4">Ubah Tanggal Verifikasi</h2>
      <form method="POST" id="formTglVer">
        <!-- hidden input untuk mengirim ke server dalam format MySQL -->
        <input type="hidden" name="container_id" id="modalTglVerContainerId">
        <input type="hidden" name="tanggal" id="modalTglVerHidden">

        <!-- visible input datetime-local untuk user (tidak bernama) -->
        <label class="block text-sm font-medium mb-2">Tanggal & Waktu (lokal)</label>
        <input type="datetime-local" id="modalTglVerVisible" class="w-full border px-3 py-2 rounded mb-4" />

        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeTglVerModal()" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Batal</button>
          <button type="submit" name="update_TglVer" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Simpan</button>
        </div>
      </form>
      <button onclick="closeTglVerModal()" class="absolute top-2 right-2 text-xl text-gray-500 hover:text-black">&times;</button>
    </div>
  </div>

<script>
  // Modal Nomor
  function openNomorModal(id, number) {
    document.getElementById('modalContainerId').value = id;
    document.getElementById('modalContainerNumber').value = number;
    document.getElementById('modalNomor').classList.remove('hidden');
  }

  function closeNomorModal() {
    document.getElementById('modalNomor').classList.add('hidden');
  }
  
  // Modal Tanggal Verifikasi
  // Helper: konversi MySQL datetime "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM"
  function mysqlToDatetimeLocal(mysqlDt) {
    if (!mysqlDt) return '';
    // some DB values may already be null/empty
    // normalize: replace space with 'T' and remove seconds
    // MySQL: "2025-09-27 15:30:45" -> datetime-local: "2025-09-27T15:30"
    var parts = mysqlDt.trim().split(' ');
    if (parts.length === 0) return '';
    var date = parts[0];
    var time = (parts[1] || '00:00:00');
    var hm = time.split(':');
    var hour = hm[0] || '00';
    var minute = hm[1] || '00';
    return date + 'T' + (hour.padStart(2,'0')) + ':' + (minute.padStart(2,'0'));
  }

  // Helper: konversi datetime-local "YYYY-MM-DDTHH:MM" -> MySQL "YYYY-MM-DD HH:MM:SS"
  function datetimeLocalToMysql(dtLocal) {
    if (!dtLocal) return null;
    // dtLocal: "2025-09-27T15:30" -> "2025-09-27 15:30:00"
    return dtLocal.replace('T', ' ') + ':00';
  }

  // Modal Tanggal Verifikasi
  function openTglVerModal(id, mysqlDatetime) {
    // isi hidden container id
    document.getElementById('modalTglVerContainerId').value = id;

    // convert mysql value ke format datetime-local untuk input visible
    var visibleVal = mysqlToDatetimeLocal(mysqlDatetime);
    document.getElementById('modalTglVerVisible').value = visibleVal;

    // reset hidden field (will be set on submit)
    document.getElementById('modalTglVerHidden').value = datetimeLocalToMysql(visibleVal);

    // show modal
    document.getElementById('modalTglVer').classList.remove('hidden');
  }

  function closeTglVerModal() {
    document.getElementById('modalTglVer').classList.add('hidden');
  }

  // Saat form akan disubmit, copy visible datetime -> hidden input dalam format MySQL
  document.getElementById('formTglVer').addEventListener('submit', function(e) {
    var visible = document.getElementById('modalTglVerVisible').value;
    var mysqlVal = datetimeLocalToMysql(visible);
    // jika user kosongkan, batalkan submit (atau kamu bisa izinkan null)
    if (!visible) {
      alert('Silakan isi tanggal dan waktu verifikasi terlebih dahulu atau klik Batal.');
      e.preventDefault();
      return false;
    }
    document.getElementById('modalTglVerHidden').value = mysqlVal;
    // biarkan submit berjalan (POST akan berisi container_id dan tanggal)
  });


</script>
</body>
</html>