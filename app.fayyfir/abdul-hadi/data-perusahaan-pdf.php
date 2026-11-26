<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

require_once "tcpdf/tcpdf.php";
require_once "config.php";

$db = function_exists('get_conn1') ? get_conn1() : ($conn ?? null);
if (!$db || $db->connect_error) {
    die("Koneksi database gagal: " . ($db ? $db->connect_error : "Koneksi tidak terdefinisi"));
}

$tipe_map = [
    'pt' => 'PT / CV',
    'personal' => 'Personal',
    'lainnya' => 'Lainnya'
];

$id_master   = intval($_GET['id'] ?? 0);
$data_id     = intval($_GET['data_id'] ?? 0);
$mode        = $_GET['mode'] ?? '';
$is_download = isset($_GET['download']) && $_GET['download'] == '1';
$output_dest = $is_download ? 'D' : 'I';

$logo_path = 'assets/logo-fayyfir3.png';
$has_logo = file_exists(__DIR__ . '/' . $logo_path);
$logo_html = $has_logo ? '<img src="' . $logo_path . '" height="26" />' : '<b style="font-size:16px; color:#312e81;">FAYYFIR</b>';

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$tgl_cetak = date('d/m/Y H:i') . ' WIB';

// =========================================================
// MODE 1: TINGKAT LABEL DATA (DETAIL PERUSAHAAN / LABEL DATA)
// =========================================================
if ($id_master > 0 && $mode !== 'master') {
    // Ambil info master perusahaan
    $st = $db->prepare("SELECT * FROM perusahaan_master WHERE id = ?");
    $st->bind_param("i", $id_master);
    $st->execute();
    $master = $st->get_result()->fetch_assoc();

    if (!$master) {
        die("Data master perusahaan tidak ditemukan.");
    }

    $cur_tipe = $tipe_map[$master['tipe']] ?? $master['tipe'];
    $clean_nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $master['nama']);
    $tgl_dibuat = date('d F Y, H:i', strtotime($master['created_at'])) . ' WIB';

    // Query data (perusahaan_data) beserta sub data (perusahaan_subdata)
    if ($data_id > 0) {
        // Mode Single Label Data
        $st2 = $db->prepare("SELECT id, label, value, created_at FROM perusahaan_data WHERE id_master = ? AND id = ?");
        $st2->bind_param("ii", $id_master, $data_id);
        $st2->execute();
        $res2 = $st2->get_result();

        $detail_data = [];
        while ($r = $res2->fetch_assoc()) {
            $st3 = $db->prepare("SELECT id, label, value, created_at FROM perusahaan_subdata WHERE id_data = ? ORDER BY id ASC");
            $st3->bind_param("i", $r['id']);
            $st3->execute();
            $r['subs'] = $st3->get_result()->fetch_all(MYSQLI_ASSOC);
            $detail_data[] = $r;
        }
        $sub_title = "DOKUMEN DETAIL LABEL DATA";
        $filename = "Data_{$clean_nama}_Label_ID{$data_id}_" . date('Ymd_His') . ".pdf";
    } else {
        // Mode Seluruh Data Perusahaan
        $st2 = $db->prepare("SELECT id, label, value, created_at FROM perusahaan_data WHERE id_master = ? ORDER BY id ASC");
        $st2->bind_param("i", $id_master);
        $st2->execute();
        $res2 = $st2->get_result();

        $detail_data = [];
        while ($r = $res2->fetch_assoc()) {
            $st3 = $db->prepare("SELECT id, label, value, created_at FROM perusahaan_subdata WHERE id_data = ? ORDER BY id ASC");
            $st3->bind_param("i", $r['id']);
            $st3->execute();
            $r['subs'] = $st3->get_result()->fetch_all(MYSQLI_ASSOC);
            $detail_data[] = $r;
        }
        $sub_title = "DOKUMEN DATA PERUSAHAAN";
        $filename = "Data_Perusahaan_{$clean_nama}_" . date('Ymd_His') . ".pdf";
    }

    $total_fields = count($detail_data);
    $total_subdata = 0;
    foreach ($detail_data as $d) {
        if (!empty($d['subs'])) {
            $total_subdata += count($d['subs']);
        }
    }
    $count_fields_txt = "{$total_fields} Label Data";
    if ($total_subdata > 0) {
        $count_fields_txt .= " ({$total_subdata} Sub-Data)";
    }

    // Setup TCPDF (Portrait A4)
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Fayyfir System');
    $pdf->SetAuthor('Fayyfir');
    $pdf->SetTitle('Data Perusahaan: ' . $master['nama']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();

    $html = <<<EOD
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td width="55%" valign="middle">
      {$logo_html}
      <div style="font-size:8px; color:#6b7280; margin-top:2px;">Sistem Manajemen Data Perusahaan Fayyfir</div>
    </td>
    <td width="45%" align="right" valign="middle" style="font-size:8.5px; color:#4b5563;">
      <b>{$sub_title}</b><br>
      Dicetak: {$tgl_cetak}<br>
      Oleh: {$user_name}
    </td>
  </tr>
</table>
<hr style="height:1.5px; color:#4f46e5; background-color:#4f46e5; border:none; margin-top:5px; margin-bottom:10px;">

<!-- INFORMASI PROFIL MASTER PERUSAHAAN -->
<table width="100%" cellpadding="5" cellspacing="0" border="1" style="border-collapse:collapse; border-color:#cbd5e1; background-color:#f8fafc;">
  <tr>
    <td width="22%" style="background-color:#f1f5f9; color:#475569; font-weight:bold; font-size:9.5px;">Nama Perusahaan</td>
    <td width="43%" style="color:#0f172a; font-weight:bold; font-size:11px;">{$master['nama']}</td>
    <td width="17%" style="background-color:#f1f5f9; color:#475569; font-weight:bold; font-size:9.5px;">Tipe Entitas</td>
    <td width="18%" style="color:#3730a3; font-weight:bold; font-size:9.5px;">{$cur_tipe}</td>
  </tr>
  <tr>
    <td style="background-color:#f1f5f9; color:#475569; font-weight:bold; font-size:9.5px;">Tanggal Dibuat</td>
    <td style="color:#334155; font-size:9.5px;">{$tgl_dibuat}</td>
    <td style="background-color:#f1f5f9; color:#475569; font-weight:bold; font-size:9.5px;">Total Field</td>
    <td style="color:#334155; font-size:9.5px;"><b>{$count_fields_txt}</b></td>
  </tr>
</table>
<br><br>

<h3 style="font-size:10.5px; color:#1e1b4b; margin:0 0 5px 0;">RINCIAN DATA & SUB-DATA PERUSAHAAN</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse; border-color:#cbd5e1;">
  <thead>
    <tr style="background-color:#1e293b; color:#ffffff; font-weight:bold; font-size:9.5px;">
      <th width="38%" align="left">Label Data / Sub Data</th>
      <th width="62%" align="left">Nilai Data / Value</th>
    </tr>
  </thead>
  <tbody>
EOD;

    if (empty($detail_data)) {
        $html .= '<tr><td colspan="2" align="center" style="color:#94a3b8; font-style:italic; padding:12px;">Belum ada data yang tersimpan untuk perusahaan ini.</td></tr>';
    } else {
        $idx = 0;
        foreach ($detail_data as $d) {
            $lbl = htmlspecialchars($d['label']);
            $raw_val = trim($d['value'] ?? '');
            $val = ($raw_val !== '') ? nl2br(htmlspecialchars($raw_val)) : '<span style="color:#94a3b8; font-style:italic;">-</span>';
            $has_subs = !empty($d['subs']);

            if (!$has_subs) {
                // Single label data (tanpa sub data)
                $bg = ($idx % 2 === 0) ? '#f8fafc' : '#ffffff';
                $html .= <<<EOD
<tr style="background-color:{$bg}; font-size:9.5px;">
  <td width="38%" style="font-weight:bold; color:#1e1b4b; background-color:#f1f5f9;">{$lbl}</td>
  <td width="62%" style="color:#0f172a;">{$val}</td>
</tr>
EOD;
            } else {
                // Label data grup yang memiliki sub data
                $val_header = ($raw_val !== '') ? nl2br(htmlspecialchars($raw_val)) : '<span style="color:#94a3b8; font-style:italic; font-weight:normal;">-</span>';
                $html .= <<<EOD
<tr style="background-color:#ede9fe; font-size:9.5px;">
  <td width="38%" style="font-weight:bold; color:#5b21b6;">{$lbl}</td>
  <td width="62%" style="color:#4c1d95; font-weight:bold;">{$val_header}</td>
</tr>
EOD;
                // Baris-baris Label Sub Data dan Value Sub Data
                foreach ($d['subs'] as $si => $s) {
                    $slbl = htmlspecialchars($s['label']);
                    $raw_sval = trim($s['value'] ?? '');
                    $sval = ($raw_sval !== '') ? nl2br(htmlspecialchars($raw_sval)) : '<span style="color:#94a3b8; font-style:italic;">-</span>';

                    $html .= <<<EOD
<tr style="background-color:#faf5ff; font-size:9px;">
  <td width="38%" style="color:#581c87; font-weight:bold;">&nbsp;&nbsp;&nbsp;&bull; {$slbl}</td>
  <td width="62%" style="color:#1e293b;">{$sval}</td>
</tr>
EOD;
                }
            }
            $idx++;
        }
    }

    $html .= <<<EOD
  </tbody>
</table>
<br><br>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:8px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:4px;">
  <tr>
    <td width="60%">Fayyfir Enterprise Document - Dokumen Resmi Data & Sub-Data Perusahaan</td>
    <td width="40%" align="right">Laporan Dicetak Otomatis dari Sistem Fayyfir</td>
  </tr>
</table>
EOD;

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename, $output_dest);
    exit();
}

// =========================================================
// MODE 2: TINGKAT MASTER DATA (SELURUH REKAP PERUSAHAAN)
// =========================================================
$q = "SELECT m.*, 
        COALESCE(NULLIF(m.label, ''), GROUP_CONCAT(DISTINCT d.label ORDER BY d.id SEPARATOR ', ')) AS labels,
        COUNT(DISTINCT d.id) AS total_data
      FROM perusahaan_master m
      LEFT JOIN perusahaan_data d ON d.id_master = m.id
      GROUP BY m.id
      ORDER BY m.id DESC";
$masters = $db->query($q);
$total_masters = $masters ? $masters->num_rows : 0;

$filename = "Rekap_Master_Data_Perusahaan_" . date('Ymd_His') . ".pdf";

// Setup TCPDF Landscape A4
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Fayyfir System');
$pdf->SetAuthor('Fayyfir');
$pdf->SetTitle('Rekap Master Data Perusahaan');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 14);
$pdf->SetFont('helvetica', '', 9);
$pdf->AddPage();

$html = <<<EOD
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td width="55%" valign="middle">
      {$logo_html}
      <div style="font-size:8.5px; color:#6b7280; margin-top:2px;">Sistem Manajemen Data Perusahaan Fayyfir</div>
    </td>
    <td width="45%" align="right" valign="middle" style="font-size:8.5px; color:#4b5563;">
      <b>REKAPITULASI MASTER DATA PERUSAHAAN</b><br>
      Total: <b>{$total_masters} Perusahaan/Entitas</b><br>
      Dicetak: {$tgl_cetak} | Oleh: {$user_name}
    </td>
  </tr>
</table>
<hr style="height:1.5px; color:#4f46e5; background-color:#4f46e5; border:none; margin-top:5px; margin-bottom:10px;">

<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse; border-color:#cbd5e1;">
  <thead>
    <tr style="background-color:#1e293b; color:#ffffff; font-weight:bold; font-size:9.5px;">
      <th width="30%" align="left">Nama Master Data</th>
      <th width="15%" align="center">Tipe Entitas</th>
      <th width="15%" align="center">Tanggal Dibuat</th>
      <th width="10%" align="center">Jumlah Field</th>
      <th width="30%" align="left">Label</th>
    </tr>
  </thead>
  <tbody>
EOD;

if (!$masters || $total_masters === 0) {
    $html .= '<tr><td colspan="5" align="center" style="color:#94a3b8; font-style:italic; padding:15px;">Belum ada master data perusahaan.</td></tr>';
} else {
    $idx = 0;
    while ($row = $masters->fetch_assoc()) {
        $cur_tipe = $tipe_map[$row['tipe']] ?? $row['tipe'];
        $tgl = date('d M Y, H:i', strtotime($row['created_at']));
        $labels_str = !empty($row['labels']) ? htmlspecialchars($row['labels']) : '<span style="color:#94a3b8; font-style:italic;">-</span>';
        $total_f = intval($row['total_data']);
        $bg_color = ($idx % 2 === 0) ? '#f8fafc' : '#ffffff';

        $html .= <<<EOD
<tr style="background-color:{$bg_color}; font-size:9px;">
  <td width="30%" style="font-weight:bold; color:#0f172a;">{$row['nama']}</td>
  <td width="15%" align="center" style="color:#3730a3; font-weight:bold;">{$cur_tipe}</td>
  <td width="15%" align="center" style="color:#475569;">{$tgl}</td>
  <td width="10%" align="center" style="font-weight:bold; color:#0f172a;">{$total_f} Field</td>
  <td width="30%" style="color:#334155; font-size:8.5px;">{$labels_str}</td>
</tr>
EOD;
        $idx++;
    }
}

$html .= <<<EOD
  </tbody>
</table>
<br><br>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:8px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:4px;">
  <tr>
    <td width="60%">Fayyfir Enterprise Document - Dokumen Rekapitulasi Internal</td>
    <td width="40%" align="right">Laporan Dicetak Otomatis dari Sistem Fayyfir</td>
  </tr>
</table>
EOD;

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($filename, $output_dest);
exit();
