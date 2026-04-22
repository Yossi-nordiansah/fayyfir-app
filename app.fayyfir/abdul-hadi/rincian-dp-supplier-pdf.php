<?php
require_once("tcpdf/tcpdf.php");
require "config.php";

if (!isset($_GET["id"])) {
    die("Parameter id tidak ditemukan.");
}

$id = (int) $_GET["id"];

// get supplier data
$query = "SELECT s.*, p.name AS province_name, r.name AS regency_name, d.name AS district_name, v.name AS village_name
          FROM suppliers s
          LEFT JOIN reg_provinces p ON s.province_id = p.id
          LEFT JOIN reg_regencies r ON s.regency_id = r.id
          LEFT JOIN reg_districts d ON s.district_id = d.id
          LEFT JOIN reg_villages v ON s.village_id = v.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supplier) {
    die("Supplier tidak ditemukan.");
}

// get DP manual
$query1 = "SELECT id, deposit_date AS created_at, description, debit, credit
           FROM deposits_supplier
           WHERE supplier_id = ?
           ORDER BY deposit_date ASC, id ASC";
$stmt1 = $conn->prepare($query1);
$stmt1->bind_param("i", $id);
$stmt1->execute();
$result1 = $stmt1->get_result();

$combined = [];
while ($row = $result1->fetch_assoc()) {
    $row['source'] = 'manual';
    $combined[] = $row;
}
$stmt1->close();

// get transaksi kontainer
$sql = "SELECT t.id, t.created_at, c.container_number, t.weight_kg, t.total_price
        FROM transactions t
        JOIN containers c ON t.container_id = c.id
        WHERE t.supplier_id = ?
        ORDER BY t.created_at ASC, t.id ASC";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    $combined[] = [
        'id' => $row['id'],
        'created_at' => $row['created_at'],
        'description' => 'Pengisian ('.$row['container_number'].')',
        'weight_kg' => $row['weight_kg'],
        'debit' => 0,
        'credit' => (float)$row['total_price'],
        'source' => 'kontainer'
    ];
}
$stmt2->close();

// urutkan ASC
usort($combined, function($a,$b){
    return strtotime($a['created_at']) <=> strtotime($b['created_at']);
});

// hitung saldo
$runningSaldo = 0;
foreach ($combined as $k => $item) {
    $debit  = (int)$item['debit'];
    $credit = (int)$item['credit'];
    if ($k === 0) {
        $runningSaldo = $debit - $credit;
    } else {
        $runningSaldo = $runningSaldo + $debit - $credit;
    }
    $combined[$k]['saldo'] = $runningSaldo;
}

$runningSaldo_fmt = number_format($runningSaldo, 0, ",", ".");

// format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ",", ".");
}

// buat pdf
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 10);

// cetak judul
$pdf->writeHTML("<h3 style='text-align:center;'>Riwayat DP Supplier</h3>", true, false, true, false, "");

// ringkasan supplier
$htmlRingkasan = <<<EOD
<table width="60%">
  <tr>
    <td width="30%"><b>Nama</b></td>
    <td width="5%">:</td>
    <td width="65%">{$supplier['name']}</td>
  </tr>
  <tr>
    <td><b>Nomor HP</b></td>
    <td>:</td>
    <td>{$supplier['phone']}</td>
  </tr>
  <tr>
    <td><b>Alamat</b></td>
    <td>:</td>
    <td>{$supplier['address']}, {$supplier['village_name']}, {$supplier['district_name']}, {$supplier['regency_name']}, {$supplier['province_name']}</td>
  </tr>
  <tr>
    <td><b>Sisa DP</b></td>
    <td>:</td>
    <td><b style="color:green;">Rp. {$runningSaldo_fmt}</b></td>
  </tr>
</table>
<br>
<br>
EOD;

$pdf->writeHTML($htmlRingkasan, true, false, true, false, "");

// tabel transaksi
$htmlTable = <<<EOD
<table border="1" cellpadding="4">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th align="center">Tanggal</th>
      <th align="center">Deskripsi</th>
      <th align="center">Berat (Kg)</th>
      <th align="center">Debit</th>
      <th align="center">Kredit</th>
      <th align="center">Sisa DP</th>
    </tr>
  </thead>
  <tbody>
EOD;

$totalWeight = 0;
$totalDebit = 0;
$totalCredit = 0;

foreach ($combined as $t) {
    $totalDebit += $t['debit'];
    $totalWeight += $t['weight_kg'];
    $totalCredit += $t['credit'];
    $tanggal = date("d/m/Y", strtotime($t['created_at']));
    $desc    = htmlspecialchars($t['description']);
    $weight   = $t['weight_kg'] ? number_format($t['weight_kg'], 0, ",", ".") : "-";
    $debit   = $t['debit'] ? number_format($t['debit'], 0, ",", ".") : "-";
    $credit  = $t['credit'] ? number_format($t['credit'], 0, ",", ".") : "-";
    $saldo   = number_format($t['saldo'], 0, ",", ".");
    $htmlTable .= "
      <tr>
        <td align='left'>{$tanggal}</td>
        <td align='left'>{$desc}</td>
        <td align='right'>{$weight}</td>
        <td align='right'>{$debit}</td>
        <td align='right'>{$credit}</td>
        <td align='right'>{$saldo}</td>
      </tr>
    ";
}

// total
$htmlTable .= "
  <tr style='background-color:#f2f2f2; font-weight:bold;'>
    <td></td>
    <td colspan='2' align='right'>Total</td>
    <td align='right'>".number_format($totalWeight,0,",",".")."</td>
    <td align='right'>".number_format($totalDebit,0,",",".")."</td>
    <td align='right'>".number_format($totalCredit,0,",",".")."</td>
    <td align='right'>".number_format($runningSaldo,0,",",".")."</td>
  </tr>
";

// jika kosong
if (empty($combined)) {
    $htmlTable .= "
      <tr>
        <td colspan='5' align='center'>Belum ada transaksi.</td>
      </tr>
    ";
}

$htmlTable .= "</tbody></table>";

// tulis ke pdf
$pdf->writeHTML($htmlTable, true, false, true, false, "");

// cetak footer
$tglCetak = date("d/m/Y");
$pdf->writeHTML("<br><hr><small>Fayyfir System Report | Dicetak {$tglCetak}</small>", true, false, true, false, "");

// output
$pdf->Output("Rincian_DP_Supplier_{$supplier['name']}.pdf", "I");