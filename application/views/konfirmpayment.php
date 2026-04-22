<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Fayyfir | Payment Confirmation</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url() ?>asset/images/favicon.png" />
    <!-- Vendor CSS -->
    <link href="<?= base_url() ?>asset/css/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>asset/css/vendor/vendor.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="<?= base_url() ?>asset/css/style.css" rel="stylesheet">
    <!-- Custom font -->
    <link href="<?= base_url() ?>asset/fonts/icomoon/icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open%20Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  </head>
  
  <body class="template-collection has-smround-btns has-loader-bg equal-height has-sm-container call-to-action">
  <div class="container">
   <div class="page-content">
      <div class="holder">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-md-18 col-lg-12">
              <?php 
                  if ($this->session->flashdata('bukti')) {?>
                      <div class="alert alert-Success">
                      <?= $this->session->flashdata('bukti') ?>
                      </div>
                      <br>
              <?php } ?>
              <!-- <img src='<?= base_url() ?>asset/images/logoemail.png' alt='Logo' style='width:420px; margin: -100px -100px; border:0;'/> -->
              <h2 class="text-center">Payment Confirmation</h2>
              <div class="form-wrapper">
                <form action="<?= base_url('home/proses_konfirm_payment') ?>" method="post" enctype="multipart/form-data" accept-charset="utf-8">
                  <div class="form-group">
                    <label>Order Id</label>
                    <input type="text" class="form-control" placeholder="Order Id" required="" name="idinvoice">
                  </div>
                  <div class="form-group">
                    <label>Account in the name of</label>
                    <input type="text" class="form-control" placeholder="Account in the name of" required="" name="atasnama">
                  </div>
                  <div class="row">
                    <div class="col-sm-9">
                    <label>Transfer Via</label>
                      <div class="form-group">
                        <select class="form-control" required="" name="via">
                          <option>-- Bank --</option>
                          <option>BNI</option>
                          <option>Mandiri</option>
                          <option>Permata</option>
                          <option>BCA</option>
                          <option>-- Retail Outlet --</option>
                          <option>Alfamart</option>
                          <option>Indomaret</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-9">
                      <label>Date Transfer</label>
                      <div class="form-group">
                        <input type="date" class="form-control" required="" name="tanggal">
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Total</label>
                    <input type="text" class="form-control" placeholder="Example = 10000" required="" name="bayar">
                  </div>
                  <div class="form-group">
                    <label>Evidence of Transfer</label>
                    <input type="file" class="form-control" required="" name="gambar"/>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn">Send</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    <div class="container"><br><br><br><br><br></div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="<?= base_url() ?>asset/js/vendor-special/lazysizes.min.js"></script>
  <script src="<?= base_url() ?>asset/js/vendor-special/ls.bgset.min.js"></script>
  <script src="<?= base_url() ?>asset/js/vendor-special/ls.aspectratio.min.js"></script>
  <script src="<?= base_url() ?>asset/js/vendor-special/jquery.min.js"></script>
  <script src="<?= base_url() ?>asset/js/vendor-special/jquery.ez-plus.js"></script>
  <script src="<?= base_url() ?>asset/js/vendor/vendor.min.js"></script>
  <script src="<?= base_url() ?>asset/js/app-html.js"></script>

  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  </body>
</html>