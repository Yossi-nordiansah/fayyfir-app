<?php
session_start();
require "config.php";

ini_set("display_errors", 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$success = "";
$error = "";

// Pastikan ada ID
if (!isset($_GET["id"])) {
  die("ID user tidak ditemukan.");
}
$user_id = (int) $_GET["id"];

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
  die("User tidak ditemukan.");
}

// Proses update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["nama"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone"]);
  $role_id = (int) $_POST["role"];
  $password = $_POST["password"];

  // Validasi email/HP duplikat (kecuali milik user sendiri)
  $stmt = $conn->prepare(
    "SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?"
  );
  $stmt->bind_param("ssi", $email, $phone, $user_id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $error = "Email atau nomor HP sudah digunakan oleh user lain.";
  } else {
    // Update
    if (!empty($password)) {
      if (
        strlen($password) < 6 ||
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) ||
        !preg_match("/[0-9]/", $password)
      ) {
        $error =
          "Password harus minimal 6 karakter dan mengandung huruf besar, kecil, dan angka.";
      } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
          "UPDATE users SET name=?, email=?, phone=?, password=?, role_id=? WHERE id=?"
        );
        $stmt->bind_param(
          "ssssii",
          $name,
          $email,
          $phone,
          $hashedPassword,
          $role_id,
          $user_id
        );
      }
    } else {
      $stmt = $conn->prepare(
        "UPDATE users SET name=?, email=?, phone=?, role_id=? WHERE id=?"
      );
      $stmt->bind_param("sssii", $name, $email, $phone, $role_id, $user_id);
    }

    if ($stmt->execute()) {
      $success = "Data user berhasil diperbarui.";
      // Refresh data
      header("Location: daftar-tim");
      exit();
    } else {
      $error = "Gagal memperbarui data user.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit User - Fayyfir</title>
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
      <h1 class="text-lg font-semibold">Edit User</h1>
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

    <form method="POST" class="space-y-6 bg-white shadow rounded-lg p-6">
      <div>
        <label class="block text-sm font-medium">Nama Lengkap</label>
        <input type="text" name="nama" value="<?= htmlspecialchars(
          $user["name"]
        ) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars(
          $user["email"]
        ) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Nomor HP</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars(
          $user["phone"]
        ) ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 text-base" />
      </div>
      <div>
        <label class="block text-sm font-medium">Jabatan</label>
        <select name="role" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 text-base">
          <?php
          $roles = $conn->query("SELECT id, name FROM roles ORDER BY name");
          while ($r = $roles->fetch_assoc()):
            $selected = $r["id"] == $user["role_id"] ? "selected" : ""; ?>
            <option value="<?= $r[
              "id"
            ] ?>" <?= $selected ?>><?= htmlspecialchars($r["name"]) ?></option>
          <?php
          endwhile;
          ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Password (opsional)</label>
        <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-yellow-300 text-base" />
      </div>

      <button type="submit" class="w-full bg-gray-800 hover:bg-yellow-400 font-base py-2 px-4 rounded-md transition flex justify-center items-center text-white hover:text-gray-800">
        <span class="material-icons mr-2 text-base">save</span>
        <span>Simpan Perubahan</span>
      </button>
    </form>
  </main>
</body>
</html>