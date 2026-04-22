<IfModule mod_rewrite.c>
RewriteCond%{HTTPS} off
RewriteRule.* https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>


 $('.hapus_cart').click(function(e){

          var rowid = $(this).attr('data-rowid');
          total = total - 1;
          $("#total_items").text(total);
          $("#total_items2").text(total);

                    $.ajax({
                        url: '<?= base_url(); ?>cart/deleteproduct/'+rowid+'/',
                        type: 'POST',
                        data: {rowid:rowid},

                        success: function(response){

                        }
                    })
            })


<a href='".base_url('cart/deleteproduct/').$items['rowid']."' class=''><i class='icon-recycle'></i></a>

xnd_development_Tdh8zDFwXwhz7MmGSjE7QRzSaEuLjWI8oYTQpaziQyVj3hzHIvsl3NCnXb0Un

alsz2632
HD1VTT78QwCd89

algp3389
FkIqQDSH4A5VavwnJodT

alsq7374
uAGrYq7Szwvc97

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]

<IfModule LiteSpeed>
  RewriteEngine On
 RewriteCond %{REQUEST_METHOD} ^HEAD|GET$
 RewriteCond %{HTTP_HOST} ^scrt.algindotours.com [NC] [OR]
 RewriteCond %{HTTP_HOST} ^www.scrt.algindotours.com [NC]
 RewriteRule .* - [E=Cache-Control:no-cache]
</IfModule>

'hostname' => 'localhost',
    'username' => 'alsz2632_alshop',
    'password' => 'Alsharif2021.',
    'database' => 'alsz2632_alsharif',


'hostname' => 'localhost',
    'username' => 'neoj1774_alsharif',
    'password' => 'alsharifshop',
    'database' => 'neoj1774_dbalsharif',

'hostname' => 'localhost',
    'username' => 'alsq7374_sarif',
    'password' => '2502sarif',
    'database' => 'alsq7374_alsharif',

'hostname' => 'localhost',
    'username' => 'alsq7374_secret',
    'password' => 'virtha24',
    'database' => 'alsq7374_dbaltrading',

'hostname' => 'localhost',
    'username' => 'algp3389_secret',
    'password' => 'virtha24',
    'database' => 'algp3389_dbaltrading',

                      5b0206
video tutor lengkap
https://www.youtube.com/watch?v=Lqj8TQWHjY0&list=PLYfaT5HP5yRpXK28QDpMlp_4fQgNdz0mq&ab_channel=PadangTekno

https://colorlib.com/preview/#ashion
https://colorlib.com/wp/free-bootstrap-ecommerce-website-templates/

https://www.niagahoster.co.id/blog/cara-agar-website-tampil-di-halaman-pertama-google/?amp

 

<header class="header-area formobile-menu header--transparent black-logo-version">
            <?php 
            if(empty($this->session->userdata('Language')) or $this->session->userdata('Language')=='en')
            {
                echo "
            <div class='header-wrapper' id='header-wrapper'>
                <div class='header-left'>
                    <div class='logo'>
                        <a href='#'>
                            <img src='".base_url('assets/images/logo/logo-.png')."' style='width: 255px; height: 110px;' alt='Logo'>
                        </a>
                    </div>
                </div>
                <div class='header-right'>
                    <div class='mainmenunav d-lg-block'>
                        <!-- Start Mainmanu Nav -->
                        <nav class='main-menu-navbar'>
                            
                            <ul class='mainmenu justify-content-end'>
                                <li style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                      <i class='fas fa-user pl-0'></i>
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('loginregister')."'>Login / Register</a></li>
                                        <li><a href='".base_url()."'>Logout</a></li>
                                    </ul>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       Language
                                    </a>
                                    <ul class='submenu'>
                                        <li>".anchor('language/change/en','English')."</li>
                                        <li>".anchor('language/change/id','Indonesia')."</li>
                                    </ul>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='".base_url('home/detailkeranjang')."' class='nav-link navbar-link-2 waves-effect'>
                                    <span class='badge badge-pill badge-warning' style='background-color:#FBD479'>".$this->cart->total_items()."</span>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                                ";

                                        if ($this->session->flashdata('tmbh1')) {
                                            echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>";
                                            echo $this->session->flashdata('tmbh1');
                                            echo "<span>, <a href='".base_url('home/detailkeranjang')."'>VIEW CART</a></span>";
                                            echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                <span aria-hidden='true'>&times;</span>
                                                </button>";
                                            echo "</div>";
                                        }
                                        
                                    echo"
                            </ul>
                        </nav>
                        

                        <hr style='background-color: white;'>

                            <ul class='mainmenu'>
                                 <li>
                                    <a href='".base_url()."'>Home</a>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Perfumes 
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('parfume/kategori/mens')."'>Mens Perfumes</a></li>
                                        <li><a href='".base_url('parfume/kategori/womens')."'>Womens Perfumes</a></li>
                                        <li><a href='".base_url('parfume/kategori/unisex')."'>Unisex Perfumes</a></li>
                                        <li><a href='".base_url('parfume/kategori/oriental')."'>Oriental Perfumes</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Oud & Incense
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('oud/kategori/alsharifoud')."'>Alsharif Oud</a></li>
                                        <li><a href='".base_url('oud/kategori/oudwholesale')."'>Oud (Wholesale)</a></li>
                                        <li><a href='".base_url('oud/kategori/-')."'>Alsharif</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Oil Perfumes
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('oil/kategori/dehnoud')."'>Dehn Oud</a></li>
                                        <li><a href='".base_url('oil/kategori/alsharifblend')."'>Alsharif Blend</a></li>
                                        <li><a href='".base_url('oil/kategori/aromaticoil')."'>Aromatic Oil</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Bottle
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('botol/kategori/botolparfum')."'>Perfume Bottle</a></li>
                                        <li><a href='".base_url('botol/kategori/botolsaffron')."'>Saffron Bottles</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href='".base_url('souvenir')."'>Gift Set & Accessories</a>
                                </li>
                                <li>
                                    <a href='".base_url('saffron')."'>Saffron</a>
                                </li>

                                <center>
                                <hr class='d-md-none' style='background-color: black; width: 80%;'>
                                </center>

                                <li class='d-md-none has-droupdown' style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                      <i class='fas fa-user pl-0'></i>
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('loginregister')."'>Login / Register</a></li>
                                        <li><a href='".base_url()."'>Logout</a></li>
                                    </ul>
                                </li>
                                <li class='d-md-none has-droupdown' style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       Language 
                                    </a>
                                    <ul class='submenu'>
                                        <li>".anchor('language/change/en','English')."</li>
                                        <li>".anchor('language/change/id','Indonesia')."</li>
                                    </ul>
                                </li>
                                <li class='d-md-none' style='color: #fff;'>
                                    <a href='".base_url('home/detailkeranjang')."' class='nav-link navbar-link-2 waves-effect'>
                                    <span class='badge badge-pill badge-warning' style='background-color:#FBD479'>".$this->cart->total_items()."</span>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                                
                            </ul>
                        </nav>
                        <!-- End Mainmanu Nav -->

                    </div>
                    <!-- Start Humberger Menu  -->
                    <div class='humberger-menu d-block d-lg-none pl--20'>
                        <span class='menutrigger text-white'>
                    <i data-feather='menu'></i>
                        </span>
                    </div>
                    <!-- End Humberger Menu  -->
                    <!-- Start Close Menu  -->
                    <div class='close-menu d-block d-lg-none'>
                        <span class='closeTrigger'>
                    <i data-feather='x'></i>
                </span>
                    </div>
                    <!-- End Close Menu  -->
                </div>
            </div>";
            }

            else if($this->session->userdata('Language')=='id')
            {

                echo "
            <div class='header-wrapper' id='header-wrapper'>
                <div class='header-left'>
                    <div class='logo'>
                        <a href='#'>
                            <img src='".base_url('assets/images/logo/logo-light.png')."' style='width: 270px; height: 110px;' alt='Logo'>
                        </a>
                    </div>
                </div>
                <div class='header-right'>
                    <div class='mainmenunav d-lg-block'>
                        <!-- Start Mainmanu Nav -->
                        <nav class='main-menu-navbar'>
                            
                            <ul class='mainmenu justify-content-end'>
                                <li style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                      <i class='fas fa-user pl-0'></i>
                                    </a>
                                    <ul class='submenu'>
                                       <li><a href='".base_url('loginregister')."'>Login / Register</a></li>
                                        <li><a href='".base_url()."'>Logout</a></li>
                                    </ul>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       Bahasa
                                    </a>
                                    <ul class='submenu'>
                                        <li>".anchor('language/change/en','English')."</li>
                                        <li>".anchor('language/change/id','Indonesia')."</li>
                                    </ul>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='".base_url('home/detailkeranjang')."' class='nav-link navbar-link-2 waves-effect'>
                                    <span class='badge badge-pill badge-warning' style='background-color:#FBD479'>".$this->cart->total_items()."</span>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        

                        <hr style='background-color: white;'>

                            <ul class='mainmenu'>
                                 <li>
                                    <a href='".base_url()."'>Beranda</a>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Parfum 
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('parfume/kategori/mens')."'>Parfum Pria</a></li>
                                        <li><a href='".base_url('parfume/kategori/womens')."'>Parfum Wanita</a></li>
                                        <li><a href='".base_url('parfume/kategori/unisex')."'>Parfum Unisex</a></li>
                                        <li><a href='".base_url('parfume/kategori/oriental')."'>Parfum Oriental</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Oud & Pedupaan
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('oud/kategori/alsharifoud')."'>Alsharif Oud</a></li>
                                        <li><a href='".base_url('oud/kategori/oudwholesale')."'>Oud (Grosir)</a></li>
                                        <li><a href='".base_url('oud/kategori/-')."'>Alsharif</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Minyak Parfum
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('oil/kategori/dehnoud')."'>Dehn Oud</a></li>
                                        <li><a href='".base_url('oil/kategori/alsharifblend')."'>Alsharif Blend</a></li>
                                        <li><a href='".base_url('oil/kategori/aromaticoil')."'>Minyak Aromatik</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        Botol
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('botol/kategori/botolparfum')."'>Botol Parfum</a></li>
                                        <li><a href='".base_url('botol/kategori/botolsaffron')."'>Botol Saffron</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href='".base_url('souvenir')."'>Hadiah & Aksesoris</a>
                                </li>
                                <li>
                                    <a href='".base_url('saffron')."'>Saffron</a>
                                </li>

                                <center>
                                <hr class='d-md-none' style='background-color: black; width: 80%;'>
                                </center>

                                <li class='d-md-none has-droupdown' style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                      <i class='fas fa-user pl-0'></i>
                                    </a>
                                    <ul class='submenu'>
                                        <li><a href='".base_url('loginregister')."'>Login / Register</a></li>
                                        <li><a href='".base_url()."'>Logout</a></li>
                                    </ul>
                                </li>
                                <li class='d-md-none has-droupdown' style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       Bahasa
                                    </a>
                                    <ul class='submenu'>
                                        <li>".anchor('language/change/en','English')."</li>
                                        <li>".anchor('language/change/id','Indonesia')."</li>
                                    </ul>
                                </li>
                                <li class='d-md-none' style='color: #fff;'>
                                    <a href='".base_url('home/detailkeranjang')."' class='nav-link navbar-link-2 waves-effect'>
                                    <span class='badge badge-pill badge-warning' style='background-color:#FBD479'>".$this->cart->total_items()."</span>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                                
                            </ul>
                        </nav>
                        <!-- End Mainmanu Nav -->

                    </div>
                    <!-- Start Humberger Menu  -->
                    <div class='humberger-menu d-block d-lg-none pl--20'>
                        <span class='menutrigger text-white'>
                    <i data-feather='menu'></i>
                        </span>
                    </div>
                    <!-- End Humberger Menu  -->
                    <!-- Start Close Menu  -->
                    <div class='close-menu d-block d-lg-none'>
                        <span class='closeTrigger'>
                    <i data-feather='x'></i>
                </span>
                    </div>
                    <!-- End Close Menu  -->
                </div>
            </div>";

            }

            else
            {

                echo "
            <div class='header-wrapper' id='header-wrapper'>
                <div class='header-left'>
                <div class='mainmenunav d-lg-block'>
                        <!-- Start Mainmanu Nav -->
                        <nav class='main-menu-navbar'>
                            
                            <ul class='mainmenu justify-content-start'>
                                <li style='color: #fff;'>
                                    <a href='#!' class='nav-link navbar-link-2 waves-effect'>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       لغة
                                    </a>
                                    <ul class='submenu text-right'>
                                        <li>".anchor('language/change/en','الإنجليزية')."</li>
                                        <li>".anchor('language/change/id','إندونيسيا')."</li>
                                        <li>".anchor('language/change/ar','عرب')."></li>
                                    </ul>
                                </li>
                                <li style='color: #fff;'>
                                    <a href='".base_url()."'>تسجيل أو تسجيل الدخول </a>
                                </li>
                            </ul>
                        </nav>
                        

                        <hr style='background-color: white;'>

                            <ul class='mainmenu'>
                                 <li>
                                    <a href='".base_url()."'> الرئيسية </a>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        العطور 
                                    </a>
                                    <ul class='submenu text-right'>
                                        <li><a href='".base_url('parfume/kategori/mens')."'> عطور وجالية </a></li>
                                        <li><a href='".base_url('parfume/kategori/womens')."'> عطور نسائية </a></li>
                                        <li><a href='".base_url('parfume/kategori/Unisex')."'> عطور للجنس> </a></li>
                                        <li><a href='".base_url('parfume/kategori/oriental')."'> عطورBقية </a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                       العود والبخور
                                    </a>
                                    <ul class='submenu text-right'>
                                        <li><a href='".base_url('oud/kategori/alsharifoud')."'>Alsharif Oud</a></li>
                                        <li><a href='".base_url('oud/kategori/oudwholesale')."'>Oud (Grosir)</a></li>
                                        <li><a href='".base_url('oud/kategori/-')."'>Alsharif</a></li>
                                    </ul>
                                </li>
                                <li class='has-droupdown'>
                                    <a href='#' class='menu-down'>
                                        دهن عود وزيوت عطرية
                                    </a>
                                    <ul class='submenu text-right'>
                                        <li><a href='".base_url('oil/kategori/dehnoud')."'> دهن عود </a></li>
                                        <li><a href='".base_url('oil/kategori/alsharifblend')."'> مخلطات الYيف </a></li>
                                        <li><a href='".base_url('oil/kategori/aromaticoil')."'> زيوت عطرية </a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href='".base_url('souvenir')."'>  باقة هدايا وإكسسوارات </a>
                                </li>
                                <li>
                                    <a href='".base_url('saffron')."'>زعفران  </a>
                                </li>

                                <center>
                                <hr class='d-md-none' style='background-color: black; width: 80%;'>
                                </center>

                                <li class='d-md-none' style='color: #fff;'>
                                    <a href='#!' class='nav-link navbar-link-2 waves-effect'>
                                      <i class='fas fa-shopping-cart pl-0'></i>
                                    </a>
                                </li>
                                <li class='d-md-none has-droupdown' style='color: #fff;'>
                                    <a href='#' class='menu-down'>
                                       لغة 
                                    </a>
                                    <ul class='submenu text-right'>
                                        <li>".anchor('language/change/en','الإنجليزية ')."</li>
                                        <li>".anchor('language/change/id','إندونيسيا')."</li>
                                        <li>".anchor('language/change/ar','عرب')."></li>
                                    </ul>
                                </li>
                                <li class='d-md-none' style='color: #fff;'>
                                    <a href='".base_url()."'> تسجيل أو تسجيل الدخول </a>
                                </li>
                                
                            </ul>
                        </nav>
                        <!-- End Mainmanu Nav -->

                    </div>
                    <!-- Start Humberger Menu  -->
                    <div class='humberger-menu d-block d-lg-none pl--20'>
                        <span class='menutrigger text-white'>
                    <i data-feather='menu'></i>
                        </span>
                    </div>
                    <!-- End Humberger Menu  -->
                    <!-- Start Close Menu  -->
                    <div class='close-menu d-block d-lg-none'>
                        <span class='closeTrigger'>
                    <i data-feather='x'></i>
                </span>
                    </div>
                    <!-- End Close Menu  -->
                    
                </div>
                <div class='header-right'>

                <div class='logo'>
                        <a href='#'>
                            <img src='".base_url('assets/images/logo/logo-light.png')."' style='width: 270px; height: 110px;' alt='Logo'>
                        </a>
                    </div>

                    
                </div>
            </div>";

            }

             ?>

        </header>
                      




