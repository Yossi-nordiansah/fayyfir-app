
<?php error_reporting(0);?>
<html>
<head>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <title>Cetak PDF</title>
  <style>
    table {
      border-collapse:collapse;
      table-layout:fixed;width: 630px;
    }
    table td {
      word-wrap:break-word;
      width: 20%;
    }
  </style>
</head>
<body>
    <b><?php echo $ket; ?></b><br /><br />
    
  <table id="" class="table table-striped table-bordered" style="width:100%">
  <!-- <table border="1" cellpadding="8"> -->
  <thead>
    <tr>
          <th>Tanggal</th>
          <th>Kode Transaksi</th>
          <th>Barang</th>
          <th>Jumlah</th>
          <th>Harga</th>
          <th>Sub Harga</th>
          <th>Total</th>
          <th>Ongkir</th>
      </tr>
  </thead>
  <tbody>
      <?php if( ! empty($transaksi)){
          $no = 1;
          $kode = "";
          $totalongkir = "";
          foreach($transaksi as $data){
          $voucher = $this->db->query("SELECT * FROM voucher where kodevoucher = '$data->kodevoucher'")->row();
          $totdis = $data->jumlah * $data->hargasetdiskon;
          $tot = $data->jumlah * $data->harga;
          // $tota[] = $tot;
          // $total = array_sum($tota);
          $tgl = date('d-m-Y', strtotime($data->tglpesan)); 
         ?>
                    <tr>
                      <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
                      <td><?= $tgl ?></td>
                      <?php } if ($kode == $data->id) {echo "<td></td>";}else{ ?>
                      <td><?= $data->id ?></td>
                    <?php } ?>
                      <td><?= $data->nama ?> - <?= $data->ukuran ?></td>
                      <td><?= $data->jumlah ?></td>
                      <?php if ($data->diskon) {?>
                        <td>Rp<?= number_format($data->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;">Rp <?= number_format($data->harga, 0, ",", ".") ?></s></small></td>
                        <td>Rp<?= number_format($totdis, 0, ",", ".") ?></td>
                        <?php }else{ ?>
                        <td>Rp<?= number_format($data->harga, 0, ",", ".") ?></td>
                        <td>Rp<?= number_format($tot, 0, ",", ".") ?></td>
                        <?php } ?>
                      <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
                      <?php if ($data->totalbayar2) {?>
                      <td>Rp<?= number_format($data->totalbayar2, 0, ",", ".") ?> <br><small><s>Rp<?= number_format($data->totalbayar, 0, ",", ".") ?></s> (<?= $voucher->diskon ?>%)</small></td>
                      <?php }else{ ?>
                      <td>Rp<?= number_format($data->totalbayar, 0, ",", ".") ?></td>
                      <?php }
                      $total2 += $data->totalbayar2;
                      if ($data->totalbayar2 == null) {
                        $total1 += $data->totalbayar;
                       } }?>
                      <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
                      <td>Rp<?= number_format($data->ongkir, 0, ",", ".") ?></td>
                    <?php $kode=$data->id; $totalongkir += $data->ongkir;} ?>
                    </tr>
                  
              <?php $total = $total1+$total2; } } ?>
  </tbody>
  <tfoot>
                 <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td><?= number_format($total, 0, ",", ".") ?></td>
                    <td><?= number_format($totalongkir, 0, ",", ".") ?></td>
                  </tr>
                  <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td colspan="2">
                      <center>
                      <?php $totalall = $total + $totalongkir ?>
                      Rp<?= number_format($totalall, 0, ",", ".") ?>  
                      </center>
                    </td>
                  </tr>
  </tfoot>
  </table>
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>

<script type="text/javascript">window.print()</script>