
<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>Login</span></li>
        </ul>
      </div>
    </div>
    <div class="holder">
      <div class="container">
        
        <div class="row justify-content-center">
          <div class="col-md-18 col-lg-12">
            <?php 
                if ($this->session->flashdata('salah')) {?>
                    <div class="alert alert-warning">
                    <?= $this->session->flashdata('salah') ?>
                    </div>
                    <br>
            <?php } ?>
            <?php 
                if ($this->session->flashdata('success_register')) {?>
                    <div class="alert alert-success">
                    <?= $this->session->flashdata('success_register') ?>
                    </div>
                    <br>
            <?php } ?>
            <?php 
                if ($this->session->flashdata('message')) {?>
                    <div class="alert alert-success">
                    <?= $this->session->flashdata('message') ?>
                    </div>
                    <br>
            <?php } ?>
            <?php 
                if ($this->session->flashdata('success_resetpass')) {?>
                    <div class="alert alert-success">
                    <?= $this->session->flashdata('success_resetpass') ?>
                    </div>
                    <br>
            <?php } ?>
            <h2 class="text-center">Login</h2>
            <div class="form-wrapper">
            <p align="justify">Join with us now. If you don't have an account with us yet, please registration at the <a href="<?= base_url('auth/register') ?>">Register page</a>.</p>
              <form action="<?= base_url('auth/proseslogin') ?>" method="post">
                <div class="row">
                  <div class="col-sm-9">
                    <div class="form-group">
                      <label class="text">Email or Username :</label>
                      <input type="text" class="form-control" placeholder="Enter your Email or Username" name="email" required="">
                    </div>
                  </div>
                  <div class="col-sm-9">
                    <div class="form-group">
                      <label class="text">Password :</label>
                      <input type="password" class="form-control" placeholder="Enter Your Password" name="password" required="">
                    </div>
                  </div>
                  <div class="col-sm-9">
                  </div>
                  <div class="col-sm-9">
                    <div class="form-group">
                    <p align="right"><a href="<?= base_url('auth/emailresetpassword') ?>">Forgotten Password</a></p>
                    </div>
                  </div>
                </div>
                <div class="text-center">
                  <input type="hidden" name="redirect_to" value="<?=$redirect_to?>" />
                  <button type="submit" class="btn">Login</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  