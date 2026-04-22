
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
                if ($this->session->flashdata('emailreset')) {?>
                    <div class="alert alert-success">
                    <?= $this->session->flashdata('emailreset') ?>
                    </div>
                    <br>
            <?php } ?>
            <?php 
                if ($this->session->flashdata('emailresetfail')) {?>
                    <div class="alert alert-warning">
                    <?= $this->session->flashdata('emailresetfail') ?>
                    </div>
                    <br>
            <?php } ?>
            <h2 class="text-center">Reset Password</h2>
            <div class="form-wrapper">
            <p align="center">Input your email to send a verification for reset password</a>.</p>
              <form action="<?= base_url('auth/emailresetpasswordvalidation') ?>" method="post">
                
                  <div class="form-group">
                      <input type="email" class="form-control" placeholder="Enter your Email" name="email" required="">
                    </div>
                
                <div class="text-center">
                  <input type="hidden" name="redirect_to" value="<?=$redirect_to?>" />
                  <button type="submit" class="btn">Send Verification</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  