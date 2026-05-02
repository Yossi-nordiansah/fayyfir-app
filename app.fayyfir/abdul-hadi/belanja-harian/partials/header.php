<?php
  // Jika variabel judul belum diset, gunakan default
  if (!isset($pageTitle)) {
    $pageTitle = "Belanja Harian - Dashboard";
  }
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Manajemen Belanja Harian - Pencatatan pembelian, produksi, penjualan, dan laporan harian.">
    <title><?= htmlspecialchars($pageTitle); ?></title>

    <!-- Tailwind CSS v4.1 -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">  
    <style>  
      .material-symbols-outlined {  
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;  
      }
    </style>

    <!-- Custom CSS (tambahkan sesuai kebutuhan) -->
    <link rel="stylesheet" href="/abdul-hadi/belanja-harian/assets/style.css">
    <link rel="stylesheet" href="/abdul-hadi/belanja-harian/assets/form.css">
    <link rel="stylesheet" href="/abdul-hadi/belanja-harian/assets/table.css">
  </head>

  <body class="bg-gray-50 text-gray-800 font-[Inter]">