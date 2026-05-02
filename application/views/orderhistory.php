<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>My Order History</span></li>
        </ul>
      </div>
    </div>
<div class="holder">
    <?php foreach ($invo as $inv) {}
      $tgl = date('Y-m-d H:i:s'); ?>
      <div class="container">
        <div class="row">
          <div class="col-md-4 aside aside--left">
            <div class="list-group">
              <a href="<?= base_url('profil') ?>" class="list-group-item">Account Details</a>
              <a href="<?= base_url('profil/address') ?>" class="list-group-item">My Addresses</a>
              <?php if ($inv->btsbayar < $tgl && $inv->status == 'n' && $this->session->userdata['logged_in']['status']) {?>
              <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item active">My Order History</a>
              <?php }else if($inv->status == 'n' && $this->session->userdata['logged_in']['status']){ ?>
                <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item active" style="color: red;">My Order History !</a>
              <?php }else{ ?>
                <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item active">My Order History</a>
              <?php } ?>
              <a target="_blank" href="<?= base_url('home/confirm_payment') ?>" class="list-group-item">Payment Confirmation</a>
            </div>
          </div>
          <div class="col-md-14 aside">
            <h1 class="mb-3">Order History</h1>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-order-history">
                <thead>
                  <tr>
                    <th scope="col">Order Id</th>
                    <th scope="col">Order Date </th>
                    <th scope="col">Payment Status</th>
                    <th scope="col">Receipt Number</th>
                    <th scope="col">Total Price</th>
                  </tr>
                </thead>
                <?php 
                $no = 1;
                // $tgl = date('Y-m-d H:i:s');
                foreach ($history as $hs){?>
                <tbody>
                  <tr>
                    <td><b><?= $hs->id ?></b> <a href="<?= base_url('profil/detailorder/').$hs->idinvoice ?>" class="ml-1">Details</a></td>
                    <td><?= date('d-m-Y', strtotime($hs->tglpesan)) ?></td>
                    <?php if ($hs->status == 'y') {?>
                    <td>Paid</td>
                    <?php }else if($hs->btsbayar < $tgl){ ?>
                    <td><s>Expired</s></td>
                    <?php }else if($hs->statuslink == 'y' && $hs->status == 'n'){ ?>
                      <td style="color: red">Unpaid <a href="#" onclick="return alert('Please Check your email to complete payment')" class="btn btn-success btn--sm">Pay</a></td>
                    <?php }else if($hs->ongkir != null){ ?>
                    <td style="color: red">Unpaid <a href="<?= base_url('checkoutv2/payment/').$hs->idinvoice ?>" class="btn btn-success btn--sm">Pay</a></td>
                    <?php }else{ ?>
                    <td style="color: red">Unpaid <a href="<?= base_url('checkoutv2/confirm/').$hs->idinvoice ?>" class="btn btn-success btn--sm">Pay</a></td>
                    <?php } ?>
                    <?php if ($hs->noresi == null) { echo "<td> - </td>";}else{?>
                    <td><?= $hs->noresi ?> <a href="<?= base_url('profil/tracking?noresi='.$hs->noresi.'&kurir='.$hs->courier.'') ?>">Details</a></td>
                    <?php } ?>
                    <td>
                      <span class="color">
                        <?php 
                        $tootal = $hs->totalbayar + $hs->ongkir;
                        if (empty($hs->ongkir)) {?>
                        Rp<?= number_format($hs->totalbayar, 0, ",", ".") ?> <span style="color:red">*</span>
                        <?php }else{?>
                        Rp<?= number_format($tootal, 0, ",", ".") ?>  
                        <?php }
                        ?>
                        </span>
                    </td>
                  </tr>
                </tbody>
                <?php } ?>
              </table>
              <p style="color: grey;"> <span style="color: red;">*</span> Haven't chosen a shipping service yet, please select first</p>

            </div>
            <div class="text-right mt-2">
              <!-- <a href="#" class="btn btn--alt">Clear History</a> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>