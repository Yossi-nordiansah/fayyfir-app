<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require_once "config.php";

// ============================================================
// AMBIL PARAMETER FILTER DARI GET
// ============================================================
$tahun      = isset($_GET['tahun']) ? trim($_GET['tahun']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$auto_print = isset($_GET['auto_print']) && $_GET['auto_print'] == '1';

// Ambil semua tahun yang ada di database untuk filter bar
$years_query = "
  SELECT DISTINCT YEAR(tanggal) AS yr 
  FROM operational_costs 
  WHERE tanggal IS NOT NULL 
  ORDER BY yr DESC
";
$years_res = $conn->query($years_query);
$available_years = [];
if ($years_res) {
  while ($y_row = $years_res->fetch_assoc()) {
    if ($y_row['yr'] !== null) {
      $available_years[] = intval($y_row['yr']);
    }
  }
}
$current_year = intval(date('Y'));
if (!in_array($current_year, $available_years)) {
  $available_years[] = $current_year;
}
rsort($available_years);

// ============================================================
// SUSUN QUERY BERDASARKAN FILTER
// ============================================================
$where_clauses = [];
$params = [];
$types = "";
$label_periode = "Semua Periode";
$mode_filter = "semua";

if ($tahun === 'custom' || (!empty($start_date) || !empty($end_date))) {
  $mode_filter = "custom";
  if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "tanggal BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
    $label_periode = date("d/m/Y", strtotime($start_date)) . " s/d " . date("d/m/Y", strtotime($end_date));
  } elseif (!empty($start_date)) {
    $where_clauses[] = "tanggal >= ?";
    $params[] = $start_date;
    $types .= "s";
    $label_periode = "Mulai " . date("d/m/Y", strtotime($start_date));
  } elseif (!empty($end_date)) {
    $where_clauses[] = "tanggal <= ?";
    $params[] = $end_date;
    $types .= "s";
    $label_periode = "Sampai " . date("d/m/Y", strtotime($end_date));
  }
} elseif (!empty($tahun) && is_numeric($tahun)) {
  $mode_filter = "tahun";
  $where_clauses[] = "YEAR(tanggal) = ?";
  $params[] = intval($tahun);
  $types .= "i";
  $label_periode = "Tahun " . intval($tahun);
}

if (!empty($search)) {
  $where_clauses[] = "(nama_biaya LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?)";
  $searchTerm = "%" . $search . "%";
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= "sss";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
$sql = "SELECT * FROM operational_costs $where_sql ORDER BY tanggal ASC, id ASC";

if (!empty($params)) {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = $conn->query($sql);
}

// Data agregat
$data_rows = [];
$total_biaya = 0;
while ($row = $result->fetch_assoc()) {
  $data_rows[] = $row;
  $total_biaya += floatval($row["jumlah"]);
}
$count_data = count($data_rows);
$avg_biaya = $count_data > 0 ? $total_biaya / $count_data : 0;

$printed_by = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Administrator';
$printed_at = date('d F Y, H:i');
$logo_path = file_exists('assets/logo-fayyfir1.png') ? 'assets/logo-fayyfir1.png' : '../assets/logo-fayyfir1.png';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Biaya Operasional - <?= htmlspecialchars($label_periode) ?> - Fayyfir</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: #f1f5f9;
      color: #0f172a;
      margin: 0;
      padding: 0;
      font-size: 13px;
      line-height: 1.5;
    }

    /* Screen Topbar (Hidden saat print) */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 50;
      background: #0f172a;
      color: #fff;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      flex-wrap: wrap;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .topbar-title {
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .topbar-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .select-sm,
    .input-sm {
      background: #1e293b;
      border: 1px solid #334155;
      color: #f8fafc;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 12px;
      outline: none;
    }

    .select-sm:focus,
    .input-sm:focus {
      border-color: #f59e0b;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-secondary {
      background: #334155;
      color: #f8fafc;
    }

    .btn-secondary:hover {
      background: #475569;
    }

    .btn-primary {
      background: #f59e0b;
      color: #0f172a;
    }

    .btn-primary:hover {
      background: #d97706;
    }

    .btn-blue {
      background: #2563eb;
      color: #fff;
    }

    .btn-blue:hover {
      background: #1d4ed8;
    }

    /* Canvas Lembar Cetak A4 */
    .page-container {
      max-width: 900px;
      margin: 25px auto;
      background: #fff;
      padding: 35px 45px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      border-radius: 8px;
    }

    /* Kop Surat */
    .kop-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 15px;
      margin-bottom: 18px;
    }

    .kop-brand-area {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .kop-logo-img {
      height: 52px;
      width: auto;
      object-fit: contain;
      display: block;
    }

    .kop-title {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.5px;
      color: #0f172a;
      line-height: 1.2;
    }

    .kop-sub {
      font-size: 11px;
      color: #64748b;
      margin-top: 2px;
    }

    .kop-meta {
      text-align: right;
      font-size: 11px;
      color: #475569;
      line-height: 1.6;
    }

    /* Banner Judul Laporan */
    .report-banner {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      background: #f8fafc;
      border-left: 4px solid #f59e0b;
      padding: 12px 16px;
      border-radius: 0 6px 6px 0;
      margin-bottom: 20px;
    }

    .report-banner h1 {
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      margin: 0 0 4px 0;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .report-banner p {
      font-size: 12px;
      color: #475569;
      margin: 0;
    }

    .report-badge {
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px;
      font-weight: 700;
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
      padding: 4px 10px;
      border-radius: 4px;
      white-space: nowrap;
    }

    /* KPI Grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    .kpi-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px 14px;
    }

    .kpi-label {
      font-size: 11px;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .kpi-value {
      font-family: 'JetBrains Mono', monospace;
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      margin-top: 4px;
    }

    /* Tabel */
    table.report-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
      font-size: 12px;
    }

    table.report-table th {
      background: #1e293b;
      color: #f8fafc;
      font-weight: 600;
      padding: 9px 10px;
      text-align: left;
      border: 1px solid #334155;
    }

    table.report-table th.text-center {
      text-align: center;
    }

    table.report-table th.text-right {
      text-align: right;
    }

    table.report-table td {
      padding: 8px 10px;
      border: 1px solid #e2e8f0;
      color: #1e293b;
      vertical-align: top;
    }

    table.report-table td.text-center {
      text-align: center;
    }

    table.report-table td.text-right {
      text-align: right;
    }

    table.report-table tr:nth-child(even) td {
      background: #f8fafc;
    }

    table.report-table tfoot td {
      background: #fef3c7 !important;
      color: #92400e;
      font-weight: 800;
      font-size: 13px;
      border: 1px solid #fde68a;
    }

    /* Tanda Tangan */
    .signature-section {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
      padding-top: 15px;
      page-break-inside: avoid;
    }

    .signature-box {
      width: 200px;
      text-align: center;
      font-size: 12px;
    }

    .signature-space {
      height: 65px;
    }

    .signature-line {
      font-weight: 700;
      border-bottom: 1px solid #334155;
      padding-bottom: 4px;
      color: #0f172a;
    }

    .signature-role {
      font-size: 11px;
      color: #64748b;
      margin-top: 3px;
    }

    /* Print Specific Media */
    @media print {
      body {
        background: #fff;
        color: #000;
      }

      .no-print {
        display: none !important;
      }

      .page-container {
        box-shadow: none;
        margin: 0;
        padding: 0;
        max-width: 100%;
        border-radius: 0;
      }

      @page {
        size: A4 portrait;
        margin: 15mm 12mm 15mm 12mm;
      }

      table.report-table th {
        background: #0f172a !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      table.report-table tfoot td {
        background: #fef3c7 !important;
        color: #92400e !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .report-banner {
        background: #f8fafc !important;
        border-left: 4px solid #f59e0b !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .kpi-card {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>

<body>

  <!-- Screen Topbar (Controls) -->
  <div class="topbar no-print">
    <div class="topbar-left">
      <a href="riwayat-operasional.php" class="btn btn-secondary" title="Kembali ke Riwayat Operasional">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        <span>Kembali</span>
      </a>
      <div class="topbar-title">
        <span class="material-symbols-outlined text-yellow-400">print</span>
        <span>Cetak Biaya Operasional</span>
      </div>
    </div>

    <!-- Filter Form di Topbar -->
    <form method="GET" action="cetak-operasional.php" class="topbar-controls" id="topbarFilterForm">
      <label for="topbarTahun" style="font-size:11px; font-weight:600; color:#cbd5e1;">Waktu:</label>
      <select name="tahun" id="topbarTahun" class="select-sm" onchange="handleTahunChange(this.value)">
        <option value="" <?= empty($tahun) && empty($start_date) && empty($end_date) ? 'selected' : '' ?>>Semua Periode</option>
        <?php foreach ($available_years as $yr): ?>
          <option value="<?= $yr ?>" <?= ($tahun == $yr && empty($start_date) && empty($end_date)) ? 'selected' : '' ?>>Tahun <?= $yr ?></option>
        <?php endforeach; ?>
        <option value="custom" <?= ($tahun === 'custom' || (!empty($start_date) || !empty($end_date))) ? 'selected' : '' ?>>Periode Tanggal...</option>
      </select>

      <div id="topbarCustomDates" class="<?= ($tahun === 'custom' || (!empty($start_date) || !empty($end_date))) ? 'flex' : 'hidden' ?>" style="display: <?= ($tahun === 'custom' || (!empty($start_date) || !empty($end_date))) ? 'flex' : 'none' ?>; gap:6px; align-items:center;">
        <input type="date" name="start_date" id="topbarStartDate" value="<?= htmlspecialchars($start_date) ?>" class="input-sm" title="Dari Tanggal">
        <span style="color:#94a3b8; font-size:12px;">s/d</span>
        <input type="date" name="end_date" id="topbarEndDate" value="<?= htmlspecialchars($end_date) ?>" class="input-sm" title="Sampai Tanggal">
      </div>

      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/keterangan..." class="input-sm" style="width: 140px;">

      <button type="submit" class="btn btn-secondary" title="Terapkan Filter">
        <span class="material-symbols-outlined text-sm">filter_alt</span>
        <span>Filter</span>
      </button>

      <button type="button" onclick="window.print()" class="btn btn-primary" title="Cetak atau Simpan PDF">
        <span class="material-symbols-outlined text-sm">print</span>
        <span>Cetak Dokumen</span>
      </button>
    </form>
  </div>

  <!-- Lembar Cetak Dokumen -->
  <div class="page-container" id="printableArea">

    <!-- 1. KOP SURAT -->
    <header class="kop-header">
      <div class="kop-brand-area">
        <img src="<?= $logo_path ?>" alt="Logo Fayyfir" class="kop-logo-img">
        <div>
          <div class="kop-title">FAYYFIR</div>
          <div class="kop-sub">Sistem Manajemen & Pembukuan Operasional Usaha</div>
        </div>
      </div>
      <div class="kop-meta">
        <div><strong>NO. DOKUMEN:</strong> BOP/<?= !empty($tahun) && is_numeric($tahun) ? $tahun : date('Y') ?>/<?= str_pad($count_data, 3, '0', STR_PAD_LEFT) ?></div>
        <div><strong>TANGGAL CETAK:</strong> <?= $printed_at ?> WIB</div>
        <div><strong>PETUGAS:</strong> <?= htmlspecialchars($printed_by) ?></div>
      </div>
    </header>

    <!-- 2. BANNER JUDUL LAPORAN -->
    <div class="report-banner">
      <div>
        <h1>Laporan Biaya Operasional</h1>
        <p>Rekapitulasi pengeluaran operasional &mdash; <strong><?= htmlspecialchars($label_periode) ?></strong></p>
        <?php if (!empty($search)): ?>
          <p style="font-size:11px; color:#b45309; margin-top:3px;">Filter Kata Kunci: "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
      </div>
      <div class="report-badge">
        <?= htmlspecialchars($label_periode) ?>
      </div>
    </div>

    <!-- 3. RINGKASAN KPI -->
    <div class="kpi-grid w-full grid-cols-2">
      <div class="kpi-card">
        <div class="kpi-label">Total Pengeluaran</div>
        <div class="kpi-value" style="color: #b45309;">Rp <?= number_format($total_biaya, 0, ',', '.') ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Jumlah Transaksi</div>
        <div class="kpi-value"><?= number_format($count_data, 0, ',', '.') ?> <span style="font-size:12px; font-weight:500; color:#64748b;">data</span></div>
      </div>
    </div>

    <!-- 4. TABEL RINCIAN BIAYA OPERASIONAL -->
    <table class="report-table">
      <thead>
        <tr>
          <th style="width: 40px;" class="text-center">No</th>
          <th style="width: 95px;" class="text-center">Tanggal</th>
          <th>Deskripsi / Nama Biaya</th>
          <th>Keterangan</th>
          <th style="width: 140px;" class="text-right">Jumlah (Rp)</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($count_data === 0): ?>
          <tr>
            <td colspan="5" class="text-center" style="padding: 30px; color:#64748b;">
              Tidak ada data biaya operasional untuk periode ini.
            </td>
          </tr>
        <?php else: ?>
          <?php $no = 1;
          foreach ($data_rows as $row): ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td class="text-center"><?= !empty($row["tanggal"]) ? htmlspecialchars(date("d/m/Y", strtotime($row["tanggal"]))) : "-" ?></td>
              <td style="font-weight: 600;"><?= htmlspecialchars($row["nama_biaya"]) ?></td>
              <td><?= !empty($row["deskripsi"]) ? htmlspecialchars($row["deskripsi"]) : "-" ?></td>
              <td class="text-right" style="font-family:'JetBrains Mono', monospace; font-weight:600;">
                <?= number_format(floatval($row["jumlah"]), 0, ",", ".") ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="text-right" style="font-weight: 800;">TOTAL PENGELUARAN OPERASIONAL</td>
          <td class="text-right" style="font-family:'JetBrains Mono', monospace; font-weight:800;">
            Rp <?= number_format($total_biaya, 0, ",", ".") ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

  <script>
    function handleTahunChange(val) {
      const customDates = document.getElementById('topbarCustomDates');
      if (val === 'custom') {
        customDates.style.display = 'flex';
      } else {
        customDates.style.display = 'none';
        document.getElementById('topbarStartDate').value = '';
        document.getElementById('topbarEndDate').value = '';
      }
    }

    <?php if ($auto_print): ?>
      window.addEventListener('load', () => {
        setTimeout(() => {
          window.print();
        }, 300);
      });
    <?php endif; ?>
  </script>

</body>

</html>