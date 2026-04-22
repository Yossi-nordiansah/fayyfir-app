<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit();
}

// Cek apakah token di session masih sama dengan di database
$stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($db_token);
$stmt->fetch();
$stmt->close();

if ($db_token !== $_SESSION["session_token"]) {
  session_destroy();
  header("Location: login.php?force_logout=1");
  exit();
}

// Ambil semua data user dari database
$sql =
  "SELECT users.*, roles.name AS role_name FROM users JOIN roles ON users.role_id = roles.id ORDER BY users.name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Tim - Fayyfir</title>
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
      <h1 class="text-lg font-semibold">Daftar Tim</h1>
    </div>
  </header>
  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <?php if (isset($_GET["kick_success"])): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
        User berhasil dipaksa logout dari sesi.
      </div>
    <?php endif; ?>
    <div class="hidden lg:flex justify-end gap-2 mb-4">
      <a href="tambah-user" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">person_add</span>
        <span class="ml-2">Tambah Tim</span>
      </a>
    </div>
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-4 py-2 text-center">Nama</th>
            <th class="px-4 py-2 text-center">Email</th>
            <th class="px-4 py-2 text-center">No. HP</th>
            <th class="px-4 py-2 text-center">Jabatan</th>
            <th class="px-4 py-2 text-center">Status</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()):

            $id = $row["id"];
            $name = json_encode($row["name"]);
            $email = json_encode($row["email"]);
            $phone = json_encode($row["phone"]);
            $role = json_encode($row["role_name"]);
            $status = json_encode($row["is_online"] ? "Aktif" : "Nonaktif");
            ?>
            <tr>
              <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars(
                $row["name"]
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["email"]
              ) ?></td>
              <td class="px-4 py-2 text-right"><?= htmlspecialchars(
                $row["phone"]
              ) ?></td>
              <td class="px-4 py-2 text-left"><?= htmlspecialchars(
                $row["role_name"]
              ) ?></td>
              <td class="px-4 py-2 text-center">
                <?php if ($row["is_online"]): ?>
                  <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-600 rounded-full">Aktif</span>
                <?php else: ?>
                  <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-500 rounded-full">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2 text-center space-x-2">
                <button onclick='showUserModal(<?= $id ?>, <?= $name ?>, <?= $email ?>, <?= $phone ?>, <?= $role ?>, <?= $status ?>)' class="text-blue-700 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </button>
              </td>
            </tr>
          <?php
          endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>  <button onclick="window.location.href='tambah-user'" 
  class="fixed bottom-4 left-8 right-8 bg-gray-900 border-t border-gray-700 flex justify-center items-center py-3 lg:hidden z-40 rounded-full text-yellow-400 space-x-2">
  <span class="material-symbols-outlined">person_add</span>
  <span class="text-sm text-white">Tambah Tim</span>
</button>  <!-- Modal Detail + Aksi -->
<div id="userModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative text-gray-800">
    <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
      <span class="material-symbols-outlined">close</span>
    </button>
    <h2 class="text-lg font-semibold mb-4">Detail User</h2>
    <div class="space-y-2 text-sm">
      <p><strong>Nama:</strong> <span id="modalNama"></span></p>
      <p><strong>Email:</strong> <span id="modalEmail"></span></p>
      <p><strong>Nomor HP:</strong> <span id="modalPhone"></span></p>
      <p><strong>Jabatan:</strong> <span id="modalRole"></span></p>
      <p><strong>Status:</strong> <span id="modalStatus"></span></p>
    </div>
    <div class="mt-6 flex justify-end space-x-3">
      <form method="POST" action="kick-user.php" onsubmit="return confirm('Yakin ingin memaksa keluar user ini?')">
        <input type="hidden" name="id" id="hiddenKickId" />
        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm">Paksa Logout</button>
      </form>
      <a href="#" id="btnEdit" class="bg-yellow-400 text-white px-4 py-2 rounded hover:bg-yellow-500 text-sm">Edit</a>
      <form method="POST" action="hapus-user.php" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
        <input type="hidden" name="id" id="hiddenDeleteId" />
        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus</button>
      </form>
    </div>
  </div>
</div>

<script>
  function showUserModal(id, name, email, phone, role, status) {
    document.getElementById("modalNama").textContent = name;
    document.getElementById("modalEmail").textContent = email;
    document.getElementById("modalPhone").textContent = phone;
    document.getElementById("modalRole").textContent = role;
    document.getElementById("modalStatus").textContent = status;
    document.getElementById("btnEdit").href = "edit-user?id=" + id;
    document.getElementById("hiddenDeleteId").value = id;
    document.getElementById("userModal").style.display = "flex";
    document.getElementById("hiddenKickId").value = id;
  }

  function closeModal() {
    document.getElementById("userModal").style.display = "none";
  }
</script></body>
</html>