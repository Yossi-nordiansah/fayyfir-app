<?php error_reporting(0);?>

<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>My Account</span></li>
        </ul>
      </div>
    </div>
<div class="holder">
      <?php foreach ($invo as $invo) {} ?>
      <div class="container">
        <div class="row">
          <div class="col-md-4 aside aside--left">
            <div class="list-group">
              <a href="<?= base_url('profil') ?>" class="list-group-item active">Account Details</a>
              <a href="<?= base_url('profil/address') ?>" class="list-group-item">My Addresses</a>
              <?php if ($invo->status == 'n' && $this->session->userdata['logged_in']['status'] == "login") {?>
              <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item" style="color: red" hidden>My Order History !</a>
              <?php }else{ ?>
                <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item" hidden>My Order History</a>
              <?php } ?>
              <a target="_blank" href="<?= base_url('home/confirm_payment') ?>" class="list-group-item" hidden>Payment Confirmation</a>
            </div>
          </div>
          <div class="col-md-14 aside">
            <h1 class="mb-3">Account Details</h1>
            <div class="row vert-margin">
              <div class="col-sm-9">
                <div class="card">
                  <div class="card-body">
                    <h3>Personal Info</h3>
                    <p><b>Full Name :</b> <?= $this->session->userdata['logged_in']['nama'] ?><br>
                      <b>E-mail :</b> <?= $this->session->userdata['logged_in']['email'] ?><br>
                      <b>Phone :</b> <?= $this->session->userdata['logged_in']['notelp'] ?><br>
                      <b>Username :</b> <?= $this->session->userdata['logged_in']['username'] ?><br>
                    </p>
                    <div class="mt-2 clearfix">
                      <a href="#" class="link-icn js-show-form" data-form="#updateDetails"><i class="icon-pencil"></i>Edit</a>
                      <p align="right" style="margin-top:-20px;"><a href="<?= base_url('auth/emailresetpassword') ?>">Change Password</a></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card mt-3 d-none" id="updateDetails">
              <div class="card-body">
              <form  method="post" action="<?= base_url('profil/updateprofil') ?>" enctype="multipart/form-data">
                <h3>Update Account Details</h3>
                <input type="hidden" class="form-control form-control--sm" placeholder="Jenny" value="<?= $this->session->userdata['logged_in']['iduser'] ?>">
                <div class="row mt-2">
                  <div class="col-sm-9">
                    <label class="text-uppercase">Full Name:</label>
                    <div class="form-group">
                    <input type="text" class="form-control form-control--sm" placeholder="Jenny" value="<?= $this->session->userdata['logged_in']['nama'] ?>">
                    </div>
                  </div>
                  <div class="col-sm-9">
                    <label class="text-uppercase">Email :</label>
                    <div class="form-group">
                    <input type="email" class="form-control form-control--sm" placeholder="Jenny" value="<?= $this->session->userdata['logged_in']['email'] ?>">
                    </div>
                  </div>
                </div>
                <div class="row mt-2">
                  <div class="col-sm-9">
                    <label class="text-uppercase">No Phone :</label>
                    <div class="form-group">
                    <input type="text" class="form-control form-control--sm" placeholder="Jenny" value="<?= $this->session->userdata['logged_in']['notelp'] ?>">
                    </div>
                  </div>
                  <div class="col-sm-9">
                    <label class="text-uppercase">Username :</label>
                    <div class="form-group">
                    <input type="text" class="form-control form-control--sm" placeholder="Jenny" value="<?= $this->session->userdata['logged_in']['username'] ?>">
                    </div>
                  </div>
                </div>
                <div class="mt-2">
                  <button type="reset" class="btn btn--alt js-close-form" data-form="#updateDetails">Cancel</button>
                  <button type="submit" class="btn ml-1">Update</button>
                </div>
              </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>