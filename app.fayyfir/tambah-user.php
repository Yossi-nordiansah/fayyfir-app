<?php
session_start();
require "config.php";

// Redirect kalau belum login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$user_result = $conn->query("SELECT DISTINCT region_name FROM users WHERE region_name IS NOT NULL");

$success = "";
$error = "";

// Proses form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $area = trim($_POST["area"]);
  $area_lainnya = trim($_POST['area_lainnya'] ?? '');
  $region_name = $area === "lainnya2" && $area_lainnya ? $area_lainnya : $area;
  $name = trim($_POST["nama"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone"]);
  $role_id = (int) $_POST["role"];
  $password = $_POST["password"];

  // Validasi password
  if (
    strlen($password) < 6 ||
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password)
  ) {
    $error =
      "Password harus minimal 6 karakter dan mengandung huruf besar, huruf kecil, dan angka.";
  } else {
    // Cek email atau no hp sudah terdaftar atau belum
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $error = "Email atau nomor HP sudah terdaftar.";
    } else {
      // Hash password
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      // Simpan ke database
      $insertStmt = $conn->prepare(
        "INSERT INTO users (name, email, region_name, phone, password, role_id) VALUES (?, ?, ?, ?, ?, ?)"
      );
      $insertStmt->bind_param(
        "sssssi",
        $name,
        $email,
        $region_name,
        $phone,
        $hashedPassword,
        $role_id
      );

      if ($insertStmt->execute()) {
        header("Location: daftar-tim");
        exit();
      } else {
        $error = "Gagal menambahkan user. Silakan coba lagi.";
      }

      $insertStmt->close();
    }

    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah User - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <!-- Header -->
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="daftar-tim" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-icons text-base">chevron_left</span>
        <span class="hidden sm:inline">Kembali ke Daftar Tim</span>
      </a>
      <h1 class="text-lg font-semibold">Tambah User</h1>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-32 max-w-xl mx-auto">
    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 px-4 py-2 rounded-md mb-4">
        <?= $success ?>
      </div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded-md mb-4">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(
      $_SERVER["PHP_SELF"]
    ) ?>" class="space-y-6 bg-white shadow rounded-lg p-6">
      <div>
        <label class="block text-sm font-medium">Area</label>
        <select name="area" id="areaSelect" class="mt-1 w-full border px-3 py-2 rounded">
          <option value="">-- Pilih Area --</option>
          <?php while($r = $user_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($r['region_name']) ?>"><?= htmlspecialchars($r['region_name']) ?></option>
          <?php endwhile; ?>
          <option value="lainnya2">Tambah baru...</option>
        </select>
        <input type="text" name="area_lainnya" id="areaOther" class="mt-2 w-full border px-3 py-2 rounded hidden" placeholder="Area baru…" />
      </div>
      <div>
        <label class="block text-sm font-medium">Nama Lengkap</label>
        <input type="text" name="nama" placeholder="Nama Lengkap..." class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" placeholder="Alamat Email..." class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Nomor HP</label>
        <input type="tel" name="phone" placeholder="Nomor WhatsApp..." class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Jabatan</label>
        <select name="role" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-base">
          <?php
          $roles = $conn->query("SELECT id, name FROM roles ORDER BY name");
          while ($r = $roles->fetch_assoc()): ?>
            <option value="<?= $r["id"] ?>"><?= htmlspecialchars(
  $r["name"]
) ?></option>
          <?php endwhile;
          ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Password</label>
        <input type="password" name="password" placeholder="Password..." class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300 text-base" />
      </div>

      <button type="submit" class="w-full bg-gray-800 hover:bg-yellow-400 font-base py-2 px-4 rounded-md transition flex justify-center items-center text-white hover:text-gray-800">
        <span class="material-icons mr-2 text-base">person_add</span>
        <span>Simpan User</span>
      </button>
    </form>
  </main>

<script>
document.getElementById("areaSelect").addEventListener("change", e => {
  document.getElementById("areaOther").classList.toggle("hidden", e.target.value !== "lainnya2");
});
</script>
</body>
</html>