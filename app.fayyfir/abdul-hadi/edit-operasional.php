<?php
session_start();
require "config.php";

// Cegah akses jika belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Cek apakah ada parameter ID yang dikirim
if (!isset($_GET["id"])) {
  header("Location: riwayat-operasional");
  exit();
}

$id = $_GET["id"];
$user_id = $_SESSION["user_id"];

// Ambil data biaya operasional berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM operational_costs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
  echo "Data tidak ditemukan.";
  exit();
}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $tanggal = $_POST["tanggal"];
  $nama_biaya = $_POST["nama_biaya"];
  $kategori = $_POST["kategori"];
  $deskripsi = $_POST["deskripsi"];
  $jumlah = str_replace('.', '', $_POST["jumlah"]);

  $stmt = $conn->prepare("UPDATE operational_costs 
                          SET tanggal=?, nama_biaya=?, kategori=?, deskripsi=?, jumlah=?, updated_at=NOW() 
                          WHERE id=?");
  $stmt->bind_param("ssssii", $tanggal, $nama_biaya, $kategori, $deskripsi, $jumlah, $id);

  if ($stmt->execute()) {
    header("Location: riwayat-operasional");
    exit();
  } else {
    echo "Gagal memperbarui data.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Biaya Operasional - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="riwayat-operasional" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Riwayat</span>
      </a>
      <h1 class="text-lg font-semibold">Edit Biaya Operasional</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <form class="space-y-6 bg-white shadow rounded-lg p-6" method="POST">
      
      <div>
        <label class="block text-sm font-medium">Tanggal Biaya</label>
        <input type="date" name="tanggal" value="<?= htmlspecialchars($data["tanggal"]) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Deskripsi</label>
        <input type="text" name="nama_biaya" value="<?= htmlspecialchars($data["nama_biaya"]) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Kategori</label>
        <select name="kategori" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none">
          <option value="">Pilih Kategori</option>
          <?php
            $kategori_list = ["Transportasi", "Gaji", "Listrik", "Sewa", "Lainnya"];
            foreach ($kategori_list as $kat) {
              $selected = ($data["kategori"] == $kat) ? "selected" : "";
              echo "<option value=\"$kat\" $selected>$kat</option>";
            }
          ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Jumlah (Rp)</label>
        <input type="text" name="jumlah" id="jumlah" value="<?= number_format($data["jumlah"], 0, ',', '.') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-medium">Keterangan</label>
        <textarea name="deskripsi" rows="2" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 focus:outline-none"><?= htmlspecialchars($data["deskripsi"]) ?></textarea>
      </div>

      <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md transition flex items-center justify-center space-x-2">
        <span class="material-symbols-outlined">save</span>
        <span>Simpan Perubahan</span>
      </button>

    </form>
  </main>

  <!-- Bottom Spacer for Mobile -->
  <div class="lg:hidden h-24"></div>

  <!-- Format input angka -->
  <script>
    document.getElementById("jumlah").addEventListener("input", function(e) {
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