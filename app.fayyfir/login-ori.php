<?php
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $identity = trim($_POST["email"]);
  $password = $_POST["password"];

  $stmt = $conn->prepare("SELECT id, name, region_name, password, role_id, session_token, token_expiry FROM users WHERE email = ? OR phone = ?");
  $stmt->bind_param("ss", $identity, $identity);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user["password"])) {
      $now = time();

      if (!empty($user["session_token"]) && !empty($user["token_expiry"])) {
        $expiry = strtotime($user["token_expiry"]);

        if ($now < $expiry) {
          // Redirect jika akun masih aktif di device lain
          header("Location: login.php?email=" . urlencode($identity) . "&status=active");
          exit();
        }
      }

      // Login sukses, buat token baru
      $token = bin2hex(random_bytes(16));
      $token_expiry = date("Y-m-d H:i:s", $now + (60 * 60)); // 30 menit

      $update_stmt = $conn->prepare("UPDATE users SET session_token = ?, token_expiry = ?, is_online = 1 WHERE id = ?");
      $update_stmt->bind_param("ssi", $token, $token_expiry, $user["id"]);
      $update_stmt->execute();
      $update_stmt->close();

      $_SESSION["user_id"] = $user["id"];
      $_SESSION["user_name"] = $user["name"];
      $_SESSION["role_id"] = $user["role_id"];
      $_SESSION["region"] = $user["region_name"];
      $_SESSION["session_token"] = $token;

      header("Location: index");
      exit();
    } else {
      $error = "Password salah.";
    }
  } else {
    $error = "Email atau nomor telepon tidak ditemukan.";
  }

  $stmt->close();
}
?>

<!doctype html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Login - Fayyfir</title>
        <link
            href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
            rel="stylesheet"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
            rel="stylesheet"
        />
        <style>
            .material-symbols-outlined {
                font-variation-settings:
                    "FILL" 0,
                    "wght" 400,
                    "GRAD" 0,
                    "opsz" 24;
            }
        </style>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-sm p-6 bg-white rounded-xl shadow-md">
            <!-- Logo -->
            <div class="flex justify-center mb-4">
                <img
                    src="assets/logo-fayyfir2.png"
                    alt="Logo Fayyfir"
                    class="h-28"
                />
            </div>
            <h1 class="text-normal font-semibold text-center text-yellow-400">
                Selamat datang!
            </h1>
            <p class="text-sm text-center text-gray-600 mb-6">
                Silahkan login ke Aplikasi Fayyfir.
            </p>
            
            <?php if (!empty($error)): ?>
              <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative">
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>
        
            <?php if (isset($_GET['status']) && $_GET['status'] === 'active' && isset($_GET['email'])): ?>
              <div class="mb-4 bg-gray-100 border gray-red-400 text-gray-700 px-4 py-3 rounded relative flex items-start space-x-2">
                <span class="material-symbols-outlined mt-0.5">error</span>
                <span>
                  Akun ini sedang aktif di perangkat lain. Silakan klik >> 
                  <a href="logout.php?email=<?= urlencode($_GET['email']) ?>" class="font-bold text-red-800 hover:underline">Logout</a>.
                </span>
              </div>
            <?php endif; ?>
        
            <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
              <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded reladata text-sm">
                Berhasil logout. Silakan login kembali untuk mengisi data.
              </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-gray-800"
                        >Email/ No Handphone</label
                    >
                    <input
                        type="text"
                        id="email"
                        name="email"
                        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300"
                    />
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-gray-800"
                        >Password</label
                    >
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300"
                    />
                </div>

                <div class="flex items-center justify-between text-sm">
                  <a href="https://wa.me/6281290004460?text=Assalamu'alaikum,%20saat%20ini%20saya%20mengalami%20kesulitan%20untuk%20login%20ke%20aplikasi%20Fayyfir,%20mohon%20untuk%20reset%20password%20dan%20meminta%20password%20yang%20baru%20%F0%9F%99%8F%F0%9F%99%82" 
                class="text-yellow-400 hover:underline" target="_blank">
                Lupa Password?
                  </a>
                </div>

                <button
                    type="submit"
                    class="group flex items-center justify-center space-x-2 bg-gray-800 hover:bg-yellow-400 text-white px-4 py-3 rounded-md font-medium transition duration-200 w-full" name="login"
                >
                    <span
                        class="material-symbols-outlined text-base text-yellow-400 group-hover:text-gray-800 transition"
                        >login</span
                    >
                    <span>Masuk</span>
                </button>
            </form>
        </div>
    </body>
</html>