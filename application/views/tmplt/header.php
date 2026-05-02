<!DOCTYPE html>
<?php error_reporting(0);?>
<html lang="en">

<head>
  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-TVTTMFH7RM"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-TVTTMFH7RM');
  </script>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
  <meta name="keywords" content="Fayyfir, Dates, Oil Perfume, kurma, parfum, parfume, fayyfir"/>
  <meta name="author" content="Diky Alwi"/>
  <meta name="description" content="Fayyfir is a shop that sells hajj and middle eastern souvenirs with the best quality and affordable prices."/>
  <!-- SEO Tag -->
  <meta name="description" content="Fayyfir is a shop that sells hajj and middle eastern souvenirs with the best quality and affordable prices."/>
  <meta property="og:locale" content="id_ID"/>
  <meta property="og:type" content="website"/>
  <meta property="og:title" content="Fayyfir"/>
  <meta property="og:description" content="Fayyfir is a shop that sells hajj and middle eastern souvenirs with the best quality and affordable prices." />
  <meta property="og:url" content="<?= base_url() ?>"/>
  <meta property="og:site_name" content="Fayyfir"/>
  <meta property="og:image" content="<?= base_url() ?>asset/images/logo2x.png"/>
  <meta property="og:image:secure_url" content="<?= base_url() ?>asset/images/logo2x.png"/>
  <meta property="og:image:width" content="560"/>
  <meta property="og:image:height" content="315"/>
  <!-- End SEO Tag. -->
  <title>Fayyfir <?= $titel ?></title>
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
  <style type="text/css">
    .loader {
    position: fixed;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: url('asset/images/load.gif') 50% 50% no-repeat rgb(249,249,249);
    opacity: .8;
    }

    .notif{
      width: 200px;
      height: 50px;
      background: #FBBF23;
      border-radius: 50%;
    }

  </style>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <script type="text/javascript">
    $(window).load(function() {
    $(".loader").fadeOut("slow");
});
  </script>
</head>

<body class="template-collection has-smround-btns has-loader-bg equal-height has-sm-container call-to-action">
<div class="loader"></div>
  <!--header-->
  <header class="hdr-wrap">
    <!-- header scroll -->
    <div class="hdr-content hdr-content-sticky" style="background-color: #151515;">
      <div class="container">
        <div class="row">
          <div class="col-auto show-mobile">
            <!-- Menu Toggle -->
            <div class="menu-toggle"> <a href="#" class="mobilemenu-toggle"><i class="icon-menu" style="color: #FFFFFF"></i></a> </div>
            <!-- /Menu Toggle -->
          </div>
          <div class="col-auto hdr-logo">
            <a href="<?= base_url() ?>" class="logo"><img srcset="<?= base_url() ?>asset/images/logo.png 1x, <?= base_url() ?>asset/images/logo2x.png 2x" alt="Logo"></a>
          </div>
          <!--navigation-->
          <div class="hdr-nav hide-mobile nav-holder-s">
          </div>
          <!--//navigation-->
          <div class="hdr-links-wrap col-auto ml-auto">
            <div class="hdr-inline-link">
              <!-- Header Search -->
              <div class="search_container_desktop">
                <div class="dropdn dropdn_search dropdn_fullwidth">
                  <a href="#" class="dropdn-link  js-dropdn-link only-icon"><i class="icon-search" style="color: #FFFFFF"></i><span class="dropdn-link-txt" style="color: #FFFFFF">Search</span></a>
                  <div class="dropdn-content">
                    <div class="container">
                      <?= form_open('home/search'); ?>
                          <div class="search search-off-popular">
                            <input name="keyword" type="text" class="search-input input-empty" placeholder="What are you looking for?">
                            <button type="submit" class="search-button"><i class="icon-search"></i></button>
                            <a href="#" class="search-close js-dropdn-close"><i class="icon-close-thin"></i></a>
                          </div>
                      <?= form_close() ?>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /Header Search -->
              <!-- notif -->
              <div class="dropdn dropdn_account dropdn_fullheight" hidden>
                <a href="" class="dropdn-link js-dropdn-link minicart-link only-icon" data-panel="#dropdnhistory">
                  <i class="icon-bell"></i>
                  <span class="minicart-qty hidden" style="background-color:#FBBF23;"><?= $suminvo ?></span>
                </a>
              </div>
              <!-- notif -->
              <!-- header acount -->
              <div class="dropdn dropdn_account dropdn_fullheight" hidden>
                <a href="" class="dropdn-link js-dropdn-link js-dropdn-link only-icon" data-panel="#dropdnAccount">
                  <i class="icon-user"></i>
                  <span class="dropdn-link-txt">Account</span>
                </a>
              </div>
              <!-- /Header Account -->
              <div class="dropdn dropdn_fullheight minicart" hidden>
                <a href="#" class="dropdn-link js-dropdn-link minicart-link only-icon" data-panel="#dropdnMinicart">
                  <i class="icon-basket"></i>
                  <span class="minicart-qty" id="total_items"></span>
                  <span class="minicart-total hide-mobile"></span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="hdr">
      <div class="hdr-topline hdr-topline--dark js-hdr-top">
        <div class="container">
          <div class="row flex-nowrap">
            <div class="col hdr-topline-left hide-mobile">
              <!-- Header Social -->
              <div class="hdr-line-separate">
                <ul class="social-list list-unstyled">
                  <li>
                    <a href="#"><i class="icon-facebook"></i></a>
                  </li>
                  <li>
                    <a target="_blank" href="https://api.whatsapp.com/send?phone=6281290004460&text=Selamat%20Datang%20di%20*FAYYFIR*%0A%0AAda%20yang%20ingin%20ditanyakan%3F%20atau%20di%20konfirmasi%3F%0A%0ATerimakasih%20telah%20mempercayai%20kami"><i class="icon-whatsapp"></i></a>
                  </li>
                  <li>
                    <a target="_blank" href="https://instagram.com/fayyfir?r=nametag"><i class="icon-instagram"></i></a>
                  </li>
                </ul>
              </div>
              <!-- /Header Social -->
            </div>

            <div class="col hdr-topline-center">
              <div class="custom-text js-custom-text-carousel" data-slick='{"speed": 1000, "autoplaySpeed": 3000}'>
                <div class="custom-text-item"><i class="icon-air-freight"></i> <span>Free</span> plane shipping over <span>Rp.5.000.000</span></div>
                <div class="custom-text-item"><i class="icon-gift"></i> let's shop at the <span>Fayyfir</span> with affordable prices</div>
              </div>
            </div>

            <div class="col hdr-topline-right hide-mobile">
              <div class="hdr-inline-link">
               
                <!-- Header Language -->
                <!-- <div class="dropdn_language">
                  <div class="dropdn dropdn_language dropdn_language--noimg dropdn_caret">
                    <a href="#" class="dropdn-link js-dropdn-link"><span class="js-dropdn-select-current">English</span><i class="icon-angle-down"></i></a>
                    <div class="dropdn-content">
                      <ul>
                        <li class="active"><a href="#"><img src="<?= base_url() ?>asset/images/flags/en.png" alt="">English</a></li>
                        <li><a href="#"><img src="<?= base_url() ?>asset/images/flags/sp.png" alt="">Bahasa Indonesia <span class="menu-label menu-label--color1">Coming Soon</span></a></li>
                      </ul>
                    </div>
                  </div>
                </div> -->
                <!-- /Header Language -->

                  <!-- notif Account -->
                <div class="hdr_container_desktop" hidden>
                  <div class="dropdn dropdn_account dropdn_fullheight">
                    <a href="#" class="dropdn-link js-dropdn-link" data-panel="#dropdnhistory"><i class="icon-bell"></i><span class="dropdn-link-txt">Notification <span class="notif"><?= $suminvo ?></span></span></a>
                  </div>
                </div>
                  <!-- /notif Account -->

                <div class="hdr_container_desktop" hidden>
                  <!-- Header Account -->
                  <div class="dropdn dropdn_account dropdn_fullheight">
                    <?php if($this->session->userdata['logged_in']['status'] == "login"){?>
                    <a href="#" class="dropdn-link js-dropdn-link" data-panel="#dropdnAccount"><i class="icon-user"></i><span class="dropdn-link-txt"><?= $this->session->userdata['logged_in']['nama'] ?></span></a>
                    <?php }else{ ?>
                    <a href="#" class="dropdn-link js-dropdn-link" data-panel="#dropdnAccount"><i class="icon-user"></i><span class="dropdn-link-txt">Account</span><i class="icon-alert"></i></a>
                    <?php } ?>
                  </div>
                  <!-- /Header Account -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="hdr-content" style="color: #fff; background-color: #151515;">
        <div class="container">
          <div class="row">
            <div class="col-auto show-mobile">
              <!-- Menu Toggle -->
              <div class="menu-toggle"> <a href="#" class="mobilemenu-toggle"><i class="icon-menu" style="color: #fff;"></i></a> </div>
              <!-- /Menu Toggle -->
            </div>
            <!--navigation-->
            <div class="col-10 hdr-nav hide-mobile nav-holder justify-content-start">
              <!--mmenu-->
              <ul class="mmenu mmenu-js">
                <li><a href="<?= base_url() ?>" style="color: #fff;">HOME</a></li>
                <li class="mmenu-item--mega"><a href="javascript:void(0)" style="color: #fff;">PRODUCT</a>
                  <div class="mmenu-submenu mmenu-submenu--has-bottom">
                    <div class="mmenu-submenu-inside">
                      <div class="container">
                        <div class="mmenu-cols column-3 pb-4">
                          <?php foreach ($kategori as $ktgr) { ?>
                          <div class="mmenu-col">
                            <h3 class="submenu-title"><a href="<?= base_url('product/shop').'/'.$ktgr->nama_kategori ?>"><?= $ktgr->nama_kategori ?></a></h3>
                          </div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
                <li><a href="<?= base_url('information/gallery') ?>" style="color: #fff;">GALLERY</a></li>
              </ul>
              <!--/mmenu-->
            </div>
            <!--//navigation-->
            <div class="hdr-logo">
              <a href="<?= base_url() ?>" class="logo"><img srcset="<?= base_url() ?>asset/images/logo.png 1x, <?= base_url() ?>asset/images/logo2x.png 2x" alt="Logo"></a>
            </div>
            <div class="col col-lg-2 hdr-links-wrap">
              <div class="hdr-links">
                <div class="hdr-inline-link">
                  <!-- Header Search -->
                  <div class="search_container_desktop">
                    <div class="dropdn dropdn_search dropdn_fullwidth">
                      <a href="#" class="dropdn-link  js-dropdn-link only-icon"><i class="icon-search" style="color: #fff;"></i><span class="dropdn-link-txt" style="color: #fff;">Search</span></a>
                      <div class="dropdn-content">
                        <div class="container">
                          <?= form_open('home/search'); ?>
                          <div class="search search-off-popular">
                            <input name="keyword" type="text" class="search-input input-empty" placeholder="What are you looking for?">
                            <button type="submit" class="search-button"><i class="icon-search"></i></button>
                            <a href="#" class="search-close js-dropdn-close"><i class="icon-close-thin"></i></a>
                          </div>
                          <?= form_close() ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- /Header Search -->

                  <!-- notif -->
                  <div class="hdr_container_mobile" hidden>
                    <div class="dropdn dropdn_account dropdn_fullheight">
                      <a href="#" class="dropdn-link js-dropdn-link" data-panel="#dropdnhistory">
                      <i class="icon-bell"></i>
                      <span class="minicart-qty" style="background-color:#FBBF23;"><?= $suminvo ?></span>
                      </a>
                    </div>
                  </div>
                   <!-- notif -->
                  <!-- Header Account -->
                  <div class="hdr_container_mobile" hidden>
                    <div class="dropdn dropdn_account dropdn_fullheight">
                      <a href="#" class="dropdn-link js-dropdn-link" data-panel="#dropdnAccount"><i class="icon-user"></i><span class="dropdn-link-txt">Account</span></a>
                    </div>
                  </div>
                   <!-- /Header Account -->
                  <div class="dropdn dropdn_fullheight minicart" hidden>
                    <a href="#" class="dropdn-link js-dropdn-link minicart-link" data-panel="#dropdnMinicart">
                      <i class="icon-basket"></i>
                      <span class="minicart-qty" id="total_items2"></span>
                      <span class="minicart-total hide-mobile"></span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-5 hdr-nav hide-mobile nav-holder justify-content-start">
              <!--mmenu-->
              <ul class="mmenu mmenu-js">
                <li><a href="<?= base_url('information/about') ?>" style="color: #fff;">ABOUT US</a></li>
                <li><a href="<?= base_url('information/contact') ?>" style="color: #fff;">CONTACT US</a></li>
              </ul>
              <!--/mmenu-->
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>


  <div class="header-side-panel">
    <!-- Mobile Menu -->
    <div class="mobilemenu js-push-mbmenu">
      <div class="mobilemenu-content">
        <div class="mobilemenu-close mobilemenu-toggle">Close</div>
        <div class="mobilemenu-scroll">
          <div class="mobilemenu-search"></div>
          <div class="nav-wrapper show-menu">
            <div class="nav-toggle">
              <span class="nav-back"><i class="icon-angle-left"></i></span>
              <span class="nav-title"></span>
              <!-- <a href="#" class="nav-viewall">view all</a> -->
            </div>
            <ul class="nav nav-level-1">
              <li><a href="<?= base_url() ?>" target="_blank">HOME</a></li>
              <li><a href="javascript:void(0)">PRODUCT<span class="arrow"><i class="icon-angle-right"></i></span></a>
                <ul class="nav-level-2">

                  <?php foreach ($kategori as $ktgr) { ?>
                  <li><a href="<?= base_url('product/shop').'/'.$ktgr->nama_kategori ?>"><?= $ktgr->nama_kategori ?><span class="arrow"><i class="icon-angle-right"></i></span></a>
                  </li>
                  <?php } ?>

                </ul>
              </li>
              <li><a href="<?= base_url('information/gallery') ?>" target="_blank">GALLERY</a></li>
              <li><a href="<?= base_url('information/about') ?>" target="_blank">ABOUT US</a></li>
              <li><a href="<?= base_url('information/contact') ?>" target="_blank">CONTACT US</a></li>
            </ul>
          </div>
          <div class="mobilemenu-bottom" hidden>
            <div class="mobilemenu-language">
              <!-- Header Language -->
              <!-- <div class="dropdn_language">
                <div class="dropdn dropdn_language dropdn_language--noimg dropdn_caret">
                  <a href="#" class="dropdn-link js-dropdn-link"><span class="js-dropdn-select-current">English</span><i class="icon-angle-down"></i></a>
                  <div class="dropdn-content">
                    <ul>
                      <li class="active"><a href="#"><img src="<?= base_url() ?>asset/images/flags/en.png" alt="">English</a></li>
                      <li><a href="#"><img src="<?= base_url() ?>asset/images/flags/sp.png" alt="">Indonesian <span class="menu-label menu-label--color1">Soon</span></a></li>
                    </ul>
                  </div>
                </div>
              </div> -->
              <!-- /Header Language -->
            </div>
            <?php foreach ($bannermenu as $menu){} ?>
             <a href="#" class="image-hover-scale"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-srcset="<?= base_url() ?>asset/images/banner/<?= $menu->gambar ?>" class="lazyload fade-up" alt=""></a>
          </div>
        </div>
      </div>
    </div>
    <!-- /Mobile Menu -->

    <!-- login samping -->
    <div class="dropdn-content account-drop" id="dropdnhistory">
      <div class="dropdn-content-block">
        <div class="dropdn-close"><span class="js-dropdn-close">Close</span></div>
          <div class="minicart-drop-content js-dropdn-content-scroll">
          <?php foreach ($invo as $inv) {
          $tgl = date('Y-m-d H:i:s');
            ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Invoice Code : <a href="<?= base_url('profil/detailorder/').$inv->id ?>" style="color: green;"><?= $inv->id ?></a></h5>
              <p class="card-text">
                Due : <?= date('d-m-Y', strtotime($inv->btsbayar)) ?> | Status : <?php if ($inv->status == 'y') {echo "Paid";}elseif ($inv->btsbayar < $tgl) {echo "Expired";}else{ echo "Unpaid";} ?>
              </p>
              <?php if($inv->status == 'y'){ ?>

              <?php }else if($inv->btsbayar < $tgl && $inv->status == 'n'){?>

              <?php }else if($inv->statuslink == 'y' && $inv->status == 'n'){?>
              <a href="#" onclick="return alert('Please Check your email to complete payment')" class="btn btn-outline-primary btn--sm">Pay</a>
              <?php }else if($inv->ongkir != null && $inv->status == 'n'){?>
              <a href="<?= base_url('checkoutv2/payment/').$inv->id ?>" class="btn btn-outline-primary btn--sm">Pay</a>
              <?php }else{ ?>
              <a href="<?= base_url('checkoutv2/confirm/').$inv->id ?>" class="btn btn-primary btn--sm">Pay</a>
              <?php } ?>
            </div>
          </div>
          <br>
          <?php } ?>
          <br>
          <center>
          <a href="<?= base_url('profil/orderhistory') ?>">See More</a>
          </center>
    
          </div>
      </div>
      <div class="drop-overlay js-dropdn-close"></div>
    </div>

    <!-- login samping -->
    <div class="dropdn-content account-drop" id="dropdnAccount">
      <div class="dropdn-content-block">
        <div class="dropdn-close"><span class="js-dropdn-close">Close</span></div>
        <?php if($this->session->userdata['logged_in']['status'] == "login"){?>
        <ul>
          <li><a href="<?= base_url('profil') ?>"><span><?= $this->session->userdata['logged_in']['nama'] ?></span><i class="icon-user2"></i></a></li>
          <li></li>
          <li></li>
          <li><div class="col-auto" style="float: right;"><a href="<?= base_url('auth/logout') ?>" class="btn btn--grey btn--sm" onclick="return confirm('are you sure you want to quit ?')">Logout</a></div></li>
        </ul>
        <?php }else{ ?>
        <ul>
          <li><a href="<?= base_url('auth/login') ?>"><span>Log In</span><i class="icon-login"></i></a></li>
          <li><a href="<?= base_url('auth/register') ?>"><span>Register</span><i class="icon-user2"></i></a></li>
        </ul>
        <hr>
        <?php 
        if(!isset($redirect_to))
            $redirect_to = $_SERVER['HTTP_REFERER'];
        ?>

        <div class="dropdn-form-wrapper">
          <h5>Quick Login</h5>
          <form action="<?= base_url('auth/proseslogin') ?>" method="post">
            <div class="form-group">
              <input type="text" class="form-control form-control--sm " placeholder="Enter your Email or Username" required="" name="email">
            </div>
            <div class="form-group">
              <input type="password" class="form-control form-control--sm" placeholder="Enter your password" required="" name="password">
            </div>
            <input type="hidden" name="redirect_to" value="<?= $redirect_to ?>" />
            <button type="submit" class="btn">Login</button>
          </form>
        </div>
        
      <?php } ?>
      </div>
      <div class="drop-overlay js-dropdn-close"></div>
    </div>

    <!-- cart samping -->
    <div class="dropdn-content minicart-drop" id="dropdnMinicart">
      <div class="dropdn-content-block">
        <div class="dropdn-close"><span class="js-dropdn-close">Close</span></div>
        <div class="minicart-drop-content js-dropdn-content-scroll">
          
          <div id="cartsamping">
          <!-- konten cart load ajax -->
          </div>
          
        </div>
        <div class="minicart-drop-fixed js-hide-empty">
          <div class="loader-horizontal-sm js-loader-horizontal-sm" data-loader-horizontal=""><span></span></div>
          <div class="minicart-drop-total js-minicart-drop-total row no-gutters align-items-center">
            <div class="minicart-drop-total-txt col-auto heading-font">Subtotal</div>
            <div id="totalpay" class="minicart-drop-total-price col" data-header-cart-total="">Rp <?= number_format($this->cart->total(), 0, ",", ".") ?></div>
          </div>
          <div class="minicart-drop-actions">
            <a href="<?= base_url('cart') ?>" class="btn btn--md btn--grey"><i class="icon-basket"></i><span>Cart Page</span></a>
          </div>
          <ul class="payment-link mb-2 justify-content-center">
            <!-- <li><i class="icon-visa-pay-logo"></i></li> -->
            <img class="lazyload" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/payment/safecheckout.png" alt="" width="100%" height="100%">
          </ul>
        </div>
      </div>
      <div class="drop-overlay js-dropdn-close"></div>
    </div>
  </div>
  <!--//header-->
  



