
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

        <?php 
        $resetkey = $this->uri->segment(3);
         ?>
        
        <div class="row justify-content-center">
          <div class="col-md-18 col-lg-12">
            <h2 class="text-center">Reset Password</h2>
            <div class="form-wrapper">
            <!-- <p align="justify">Join with us now. If you don't have an account with us yet, please registration at the <a href="<?= base_url('auth/register') ?>">Register page</a>.</p> -->
              <form action="<?= base_url('auth/resetpasswordvalidation') ?>" method="post">
                <div class="row">
                    <input type="hidden" class="form-control" placeholder="Enter your Email or Username" name="resetkey" required="" value="<?= $resetkey ?>">
                  <div class="col-sm-9">
                    <div class="form-group">
                      <input type="password" class="form-control" placeholder="Enter New Password" name="password" required="">
                    </div>
                  </div>
                  <div class="col-sm-9">
                    <div class="form-group">
                      <input type="password" class="form-control" placeholder="Confirm New Password" name="confirmpassword" required="">
                    </div>
                  </div>
                  <div class="col-sm-9">
                  </div>
                  <div class="col-sm-9">
                  </div>
                </div>
                <div class="text-center">
                  <input type="hidden" name="redirect_to" value="<?=$redirect_to?>" />
                  <button type="submit" class="btn">Reset Password</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  