<style type="text/css">

figure {
  display: flex;
}
figure img {
  width: 8rem;
  height: 8rem;
  border-radius: 15%;
  border: 1.5px solid #107D17;
  margin-right: 1.5rem;
  padding:1rem;
}
figure figcaption {
  display: flex;
  flex-direction: column;
  justify-content: space-evenly;
}
figure figcaption h4 {
  font-size: 1.4rem;
  font-weight: 500;
}
figure figcaption h6 {
  font-size: 1rem;
  font-weight: 300;
}
figure figcaption h2 {
  font-size: 1.6rem;
  font-weight: 500;
}

.order-track {
  margin-top: 2rem;
  padding: 0 1rem;
  border-top: 1px dashed #2c3e50;
  padding-top: 2.5rem;
  display: flex;
  flex-direction: column;
}
.order-track-step {
  display: flex;
  height: 4rem;
}
.order-track-step:last-child {
  overflow: hidden;
  height: 4rem;
}
.order-track-step:last-child .order-track-status span:last-of-type {
  display: none;
}
.order-track-status {
  margin-right: 1.5rem;
  position: relative;
}
.order-track-status-dot {
  display: block;
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 50%;
  background: #107D17;
}
.order-track-status-line {
  display: block;
  margin: 0 auto;
  width: 2px;
  height: 4rem;
  background: #107D17;
}
.order-track-text-stat {
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 3px;
}
.order-track-text-sub {
  font-size: 1rem;
  font-weight: 300;
}

.order-track {
  transition: all .3s height 0.3s;
  transform-origin: top center;
}
</style>

<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>Tracking Order</span></li>
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
            <h1 class="mb-3">Tracking Order</h1>
            <?php if (empty($tracking['rajaongkir']['result'])) { 
              echo "
              <div class='alert alert-warning'>
                <strong>Attention!</strong> Sorry, we don't have a feature to track your order using the expedition you use, we can only track it using J&T, POS Indonesia, and Sicepat expeditions.
              </div>
              ";
            }else{ 
            $trekdet = $tracking['rajaongkir']['result']['summary'];
            ?>
             <table class="table table-bordered table-striped table-order-history">
               <tbody>
                    <tr>
                        <td width="30%" align="left">Receipt Number / Courier</td>
                        <td><center><?= $trekdet['waybill_number'] ." / ". $trekdet['courier_name']?></center></td>
                    </tr>
                    <tr>
                        <td width="30%" align="left">Receiver Name</td>
                        <td><center><?= $trekdet['receiver_name']?></center></td>
                    </tr>
                    <tr>
                        <td width="30%" align="left">Status</td>
                        <td><center><?= $trekdet['status']?></center></td>
                    </tr>
                </tbody>
              </table>
            <section class="root">
              <div class="order-track">
              <?php foreach ($tracking['rajaongkir']['result']['manifest'] as $trek) { ?>
                <div class="order-track-step">
                  <div class="order-track-status">
                    <span class="order-track-status-dot"></span>
                    <span class="order-track-status-line"></span>
                  </div>
                  <div class="order-track-text">
                    <p class="order-track-text-stat"><?= $trek['manifest_description'] ?></p>
                    <span class="order-track-text-sub"><?= $trek['manifest_time'] ." / ". $trek['manifest_date'] ?></span>
                  </div>
                </div>
              <?php } ?>
              </div>
            </section>
              
            <?php } ?>
            
            <div class="text-right mt-2">
              <a href="<?= base_url('profil/orderhistory') ?>" class="btn btn--alt">Back</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>