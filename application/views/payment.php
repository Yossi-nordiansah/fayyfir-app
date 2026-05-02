<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>Checkout</span></li>
        </ul>
      </div>
    </div>
    <div class="holder">
      <div class="container">
        <h1 class="text-center">Payment</h1>
        <div class="row">
          <div class="col-md-10">
            <div class="card">
              <div class="card-body">
                <?php foreach ($invoice as $invc) {?>
                <center>
                <p>order code :</p>
                <h1 class="mb-3"><?= $invc->id?></h1>
                </center>
                  
                    <div class="container">
                      <div class="card">
                        <div class="card-body">
                          <p align="justify">Your groceries are being processed, please send the following code to our <a target="_blank" href="https://wa.me/6281290004460?text=Selamat%20datang%20di%20FAYYFIR%20SECRET%0A%0AKode%20Pemesanan%0A<?=$invc->id?>%0A%0ATerimakasih%20Sudah%20berbelanja%20Pada%20Kami.">WHATSAPP</a> for further processing.</p>
                        </div>
                      </div>
                    </div>
                  
                  <?php } ?>
              </div>
            </div>
            <br>
            
            <?php foreach ($ekspedisi as $eks) {}
            $total1 = $invc->totalbayar;
            $total2 = $invc->totalbayar2;

            $amount1 = $total1+$eks->ongkir;
            $amount2 = $total2+$eks->ongkir; ?>
            <div class="card">
            <div class="card-body">

              <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th colspan="4" class="text-center" style="color: red;">Status : <?php if($invc->status == 'y'){echo"PAID";}else{echo "UNPAID";}?>  -  Limit  : <?php echo $invc->btsbayar?></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Total Product</td>
                    <td>:</td>
                    <?php if ($total2 != null) { ?>
                    <td colspan="1">Rp<?= number_format($total2, 0, ",", ".") ?> </td>
                    <?php }else{ ?>
                    <td colspan="1">Rp<?= number_format($total1, 0, ",", ".") ?> </td>
                    <?php } ?>
                  </tr>
                  <tr>
                    <td>Method Delivery</td>
                    <td>:</td>
                    <td colspan="1">Rp<?= number_format($eks->ongkir, 0, ",", ".") .' - '. $eks->courier.' ('.$eks->service.')'?></td>
                  </tr>
                  <tr>
                    <td>Total Payment</td>
                    <td>:</td>
                    <?php if ($total2 != null) { ?>
                    <td colspan="1">Rp<?= number_format($amount2, 0, ",", ".") ?> </td>
                    <?php }else{ ?>
                    <td colspan="1">Rp<?= number_format($amount1, 0, ",", ".") ?> </td>
                    <?php } ?>
                    </tr>
                </tbody>
              </table>
            </div>

            <form action="<?= base_url('checkout/bayar') ?>" method="post">
                <input type="hidden" name="token" value="<?php echo $invc->id?>"> 
                <input type="hidden" name="email" value="<?php echo $invc->email?>">
                <input type="hidden" name="description" value="Transaction Fayyfir">
                <?php if ($total2 != null) { ?>
                <input type="hidden" name="amount" value="<?= $amount2 ?>">
                <?php }else{ ?>
                <input type="hidden" name="amount" value="<?= $amount1 ?>">
                <?php } ?>
                <input type="submit" class="btn btn--lg w-100" name="BAYAR" value="Payment">
             </form>
              
            </div>
            </div>
          </div>

          <div class="col-md-8 pl-lg-8 mt-2 mt-md-0">
            
            <h2 class="custom-color">Order Summary</h2>
            <div class="cart-table cart-table--sm pt-3 pt-md-0">
              <div class="cart-table-prd cart-table-prd--head py-1 d-none d-md-flex">
                <div class="cart-table-prd-image text-center">
                  Image
                </div>
                <div class="cart-table-prd-content-wrap">
                  <div class="cart-table-prd-info">Name</div>
                  <div class="cart-table-prd-qty">Qty</div>
                  <div class="cart-table-prd-price">Price</div>
                </div>
              </div>
              <?php 
              foreach ($pesanan as $items) {
              $diskon = $this->db->query("SELECT * FROM voucher where kodevoucher = '$items->kodevoucher'")->row();
              $sum = $items->jumlah * $items->harga;
              $sum2 = $items->jumlah * $items->hargasetdiskon?> 
              <div class="cart-table-prd">
                <div class="cart-table-prd-image">
                  <a href="#" class="prd-img"><img class="lazyload fade-up" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $items->gambar?>" alt=""></a>
                </div>
                <div class="cart-table-prd-content-wrap">
                  <div class="cart-table-prd-info">
                    <h2 class="cart-table-prd-name"><a href=""><?= $items->namaproduk ?></a></h2>
                    size - <?= $items->ukuran ?><br>
                    <?php if ($items->diskon){ ?>
                      Rp<?= number_format($items->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;"><?= number_format($items->harga, 0, ",", ".") ?></s></small>
                    <?php }else{
                      echo number_format($items->harga, 0, ",", ".");
                    } ?>
                  </div>
                  <div class="cart-table-prd-qty">
                    <div class="qty qty-changer">
                      <input type="text" class="qty-input disabled" value="<?= $items->jumlah ?>" data-min="0" data-max="1000" disable="" readonly>
                    </div>
                  </div>
                  <div class="cart-table-prd-price-total">
                    <?php if ($items->diskon){ ?>
                    Rp<?= number_format($sum2, 0, ",", ".") ?>
                    <?php }else{?>
                    Rp<?= number_format($sum, 0, ",", ".") ?>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>
              <hr>  
            <div class="mt-2"></div>
            <?php if ($items->totalbayar2 != null) {?>
            <div class="cart-total-sm">
              <span>Subtotal</span>
              <span class="card-total-price">Rp<?= number_format($items->totalbayar2, 0, ",", ".") ?></span>
            </div>
            <small class="float-right" style="font-size: 15px; color: red; margin-top: -2px;" ><s>Rp<?= number_format($items->totalbayar, 0, ",", ".") ?></s> (<?= $diskon->diskon ?>%)</small>
            <?php }else{ ?>
            <div class="cart-total-sm">
              <span>Subtotal</span>
              <span class="card-total-price">Rp<?= number_format($items->totalbayar, 0, ",", ".") ?></span>
            </div>
            <?php } ?>
            <br>
           
          </div>
        </form>
        </div>
      </div>
    </div>
  </div>