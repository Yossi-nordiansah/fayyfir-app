<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

// Validasi parameter
if (!isset($_GET["user_id"])) {
    die("Parameter user_id tidak ditemukan.");
}

$user_id = (int) $_GET["user_id"];

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM user_cash_flows WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User tidak ditemukan.");
}

// Ambil data transaksi user
$stmt2 = $conn->prepare("SELECT * FROM cash_flows WHERE user_id = ? ORDER BY date ASC, id ASC");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result = $stmt2->get_result();

$kas = [];
$runningSaldo = 0;
$totalDebit = 0;
$totalCredit = 0;

while ($row = $result->fetch_assoc()) {
    $debit = (int)$row['debit'];
    $credit = (int)$row['credit'];
    $runningSaldo += $debit - $credit;
    $kas[] = [
        "date" => $row["date"],
        "description" => $row["description"],
        "debit" => $debit,
        "credit" => $credit,
        "saldo" => $runningSaldo
    ];
    $totalDebit += $debit;
    $totalCredit += $credit;
}
$stmt2->close();

// Fungsi format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ",", ".");
}

$saldo_fmt = formatRupiah($runningSaldo);

// Buat PDF
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 10);

// Judul
$pdf->writeHTML("<h3 style='text-align:center;'>Rincian Kas Pengguna</h3>", true, false, true, false, "");

// Ringkasan user
$htmlUser = <<<EOD
<table width="60%">
  <tr><td width="30%"><b>Nama</b></td><td width="5%">:</td><td width="65%">{$user['name']}</td></tr>
  <tr><td><b>Nomor HP</b></td><td>:</td><td>{$user['phone']}</td></tr>
  <tr><td><b>Alamat</b></td><td>:</td><td>{$user['address']}</td></tr>
  <tr><td><b>Sisa Saldo</b></td><td>:</td><td><b style="color:green;">{$saldo_fmt}</b></td></tr>
</table>
<br><br>
EOD;

$pdf->writeHTML($htmlUser, true, false, true, false, "");

// Tabel transaksi
$htmlTable = <<<EOD
<table border="1" cellpadding="4">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th align="center">Tanggal</th>
      <th align="center">Deskripsi</th>
      <th align="center">Credit</th>
      <th align="center">Debit</th>
      <th align="center">Saldo</th>
    </tr>
  </thead>
  <tbody>
EOD;

// Isi tabel transaksi
if (empty($kas)) {
    $htmlTable .= "<tr><td colspan='5' align='center'>Belum ada transaksi.</td></tr>";
} else {
    foreach ($kas as $item) {
        $tanggal = date("d/m/Y", strtotime($item["date"]));
        $desc = htmlspecialchars($item["description"]);
        $debit = $item["debit"] ? number_format($item["debit"], 0, ",", ".") : "-";
        $credit = $item["credit"] ? number_format($item["credit"], 0, ",", ".") : "-";
        $saldo = number_format($item["saldo"], 0, ",", ".");
        $htmlTable .= "
          <tr>
            <td>{$tanggal}</td>
            <td>{$desc}</td>
            <td align='right'>{$debit}</td>
            <td align='right'>{$credit}</td>
            <td align='right'>{$saldo}</td>
          </tr>
        ";
    }

    // Total
    $htmlTable .= "
      <tr style='background-color:#f2f2f2; font-weight:bold;'>
        <td></td>
        <td align='right'>Total</td>
        <td align='right'>" . number_format($totalDebit, 0, ",", ".") . "</td>
        <td align='right'>" . number_format($totalCredit, 0, ",", ".") . "</td>
        <td align='right'>" . number_format($runningSaldo, 0, ",", ".") . "</td>
      </tr>
    ";
}

$htmlTable .= "</tbody></table>";
$pdf->writeHTML($htmlTable, true, false, true, false, "");

// Footer
$tglCetak = date("d/m/Y");
$pdf->writeHTML("<br><hr><small>Fayyfir System Report | Dicetak {$tglCetak}</small>", true, false, true, false, "");

// Output PDF
$pdf->Output("Rincian_Kas_{$user['name']}.pdf", "I");