<html>
<head>
  <title>Pengiriman</title>
  <style>
    body{
      font-family: calibri;
    }
    table {
      border-collapse:collapse;
      table-layout:fixed;width: 500px;
    }
    table td {
      word-wrap:break-word;
      width: 20%;
    }
  </style>
</head>
<body>

  <table border="1" cellpadding="8">
    <tr>
        <th colspan="4">Kode Pesanan : <?= $invoice->id ?> <br></th>
    </tr>
    <tr>
      <td>&nbsp;<b>Pengirim</b></td>
      <td colspan="3">
        <b>ALSHARIF SHOP</b> (+6281290007740) <br>
        Jakarta Pusat - Indonesia
      </td>
    </tr>
    <?php foreach ($pesanan as $pro) {} ?>
    <tr height="100px">
      <td>&nbsp;<b>Penerima</b></td>
      <td colspan="3">
        <b><?= $pro->penerima ?></b> (<?= $pro->notelp ?>)<br>
        <?= $pro->alamat .', '. $pro->kota .', '. $pro->provinsi ?> <br>
        tambahan : 
        <br><br><br>
      </td>
    </tr>
    <tr height="70px">
      <td>
        &nbsp;<b>Ekspedisi</b><br>
        &nbsp;<?= $pro->courier ?> - (<?= $pro->service ?>)
      </td>
      <td colspan="3">
        <b>Deskripsi</b> :<br>
        Tambahan :
        <br><br>
      </td>
    </tr>
    
  </table>

  <br><br>

 <!--  <table border="1" cellpadding="8" background="<?= base_url('asset/images/payment/printpengiriman.jpg') ?>">
    <tr>
        <th colspan="4">Kode Pesanan : <?= $invoice->id ?></th>
    </tr>
    <?php foreach ($pesanan as $pro) {} ?>
    <tr>
      <td>
        Nama Pengirim :<br>
        ALSHARIF SHOP (081290007740)<br><br>
        Nama Penerima :<br>
        <?= $pro->penerima ?>  (<?= $pro->notelp ?>)
      </td>
      <td>
        Barang :<br>
        <?= $pro->kategori ?><br><br>

        Ekspedisi :<br>
        <?= $pro->courier ?> - (<?= $pro->service ?>)
      </td>
      <td colspan="2">
        Alamat :<br>
        <?= $pro->alamat .' - '. $pro->kota .' - '. $pro->provinsi ?><br><br><br><br><br>

        Tambahan :<br><br><br>
      </td>
    </tr>
</table> -->
</body>
</html>

<script>
    window.print();
  </script>
