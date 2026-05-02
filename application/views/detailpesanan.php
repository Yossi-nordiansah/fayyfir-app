                                                   
<div class="page-content">
    <div class="holder">
        <div class="container">
        <h2 class="text-center">Order Detail</h2>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Name Product</th>
                    <th>Qty</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                 <?php 
                  $total=0;
                  foreach ($pesanan as $pro) {
                  $diskon = $this->db->query("SELECT * FROM voucher where kodevoucher = '$pro->kodevoucher'")->row();
                  $subtotal = $pro->jumlah * $pro->harga;
                  $subtotaldis = $pro->jumlah * $pro->hargasetdiskon;
                  $status = $invoice->status;
                ?>
                  <tr>
                    <td><img style="width: 50px" src="<?= base_url() ?>asset/images/product/<?= $pro->gambar?>"></td>
                    <td><?= $pro->namaproduk ?> <br><span style="color: grey;"> <?= $pro->ukuran ?></span></td>
                    <td><?= $pro->jumlah ?></td>
                    <td><?= $pro->kategori ?></td>
                    <?php if ($pro->diskon) {?>
                    <td>Rp <?= number_format($pro->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;">Rp <?= number_format($pro->harga, 0, ",", ".") ?></s></small></td>
                    <td>Rp <?= number_format($subtotaldis, 0, ",", ".") ?></td>  
                    <?php }else{ ?>
                    <td>Rp <?= number_format($pro->harga, 0, ",", ".") ?></td>
                    <td>Rp <?= number_format($subtotal, 0, ",", ".") ?></td>
                    <?php } ?>
                  </tr>
                <?php } ?>

                  <tr>
                    <td colspan="5" class="text-right">Total</td>
                    <?php if ($pro->totalbayar2) {?>
                      <td>Rp<?= number_format($pro->totalbayar2, 0, ",", ".") ?> <br><small style="color:darkred;"><s>Rp<?= number_format($pro->totalbayar, 0, ",", ".") ?></s> (<?= $diskon->diskon ?>%)</small></td>
                    <?php }else{ ?>
                      <td>Rp<?= number_format($pro->totalbayar, 0, ",", ".") ?></td>
                    <?php } ?>
                  </tr>
                  <tr>
                    <td>Status</td>
                    <td colspan="5"><?php if($status == 'y'){echo"Paid";}else{echo "Unpaid";}?></td>
                  </tr>
                  <tr>
                    <td>Address</td>
                    <td colspan="5">
                      <?php if ($pro->provinsi != null) {?>
                        <?= $pro->alamat .' - '. $pro->kecamatan .' - '. $pro->kota .' - '. $pro->provinsi .' - '. $pro->kodepos .' - '. $pro->negara ?>
                      <?php }else{ ?>
                          <?= $pro->alamat .' - '. $pro->negara ?>
                      <?php } ?>
                    </td>
                  </tr>
                  <tr>
                    <td>Courier</td>
                    <td colspan="5"><?= $pro->courier ?></td>
                  </tr>
                  <tr>
                    <td>Service</td>
                    <td colspan="5"><?= $pro->service ?></td>
                  </tr>
                  <tr>
                    <td>Postal Fee</td>
                    <td colspan="5">Rp <?= number_format($pro->ongkir, 0, ",", ".") ?></td>
                  </tr>
                </tbody>

              </table>

            <!-- <p style="color: grey;">The total expenditure above does not include shipping services <span style="color: red;">*</span></p> -->
            </div>
            <div class="text-right mt-2">
              <a target="_blank" href="https://api.whatsapp.com/send?phone=6281290004460&text=Selamat%20Datang%20di%20*FAYYFIR*%0A%0AAda%20yang%20ingin%20ditanyakan%3F%20atau%20di%20konfirmasi%3F%0A%0ATerimakasih%20telah%20mempercayai%20kami" class="btn btn--alt"><i class="icon icon-whatsapp"></i></a>
              <a href="<?= base_url('profil/orderhistory') ?>" class="btn btn--alt">See History</a>
            </div>
        </div>
    </div>
</div>