
  <div class="page-content">
    <!-- Main Slider -->
    <div class="holder fullwidth full-nopad mt-0">
      <div class="container">
        <div class="bnslider-wrapper">
          <div class="bnslider bnslider--lg keep-scale" id="bnslider-001" data-slick='{"arrows": true, "dots": true}' data-autoplay="true" data-speed="5000" data-start-width="1900" data-start-height="800" data-start-mwidth="1700" data-start-mheight="800">
            <?php foreach ($bannerutamav1 as $v1) {?>
            <div class="bnslider-slide">
              <div class="bnslider-image-mobile lazyload" data-bgset="<?= base_url() ?>asset/images/banner/<?=$v1->gambar?>"></div>
              <div class="bnslider-image lazyload" data-bgset="<?= base_url() ?>asset/images/banner/<?=$v1->gambar?>"></div>
              <div class="bnslider-text-wrap bnslider-overlay ">
                <div class="bnslider-text-content txt-middle txt-right txt-middle-m txt-center-m">
                  <div class="bnslider-text-content-flex ">
                    <div class="bnslider-vert w-s-60 w-ms-100" style="padding: 0px">
                      <!-- <div class="bnslider-text order-1 mt-sm bnslider-text--md text-center data-ini" data-animation="fadeInUp" data-animation-delay="800" data-fontcolor="#282828" data-fontweight="700" data-fontline="1.5"></div>
                      <div class="bnslider-text order-2 mt-sm bnslider-text--xs text-center data-ini" data-animation="fadeInUp" data-animation-delay="1000" data-fontcolor="#7c7c7c" data-fontweight="400" data-fontline="1.5"></div><br> <br>  <br>  <br>  
                      <div class="btn-wrap text-center  order-4 mt-md" data-animation="fadeIn" data-animation-delay="2000" style="opacity: 1;">
                        <a href="https://bit.ly/3eJX5XE" target="_blank" class="btn">
                          Shop now
                        </a>
                      </div> -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php } ?>


            <!-- video -->
            <!-- <div class="bnslider-slide slick-slide is-paused" data-autoplay="true" data-video-type="video" data-slick-index="2" aria-hidden="true" style="width: 1903px; position: relative; left: -3806px; top: 0px; z-index: 998; opacity: 0; transition: opacity 500ms ease 0s;" id="slick-slide02">
              <div class="video-wrap">
                <video playsinline="" loop="" preload="auto">
                  <source src="<?= base_url('asset/images/vid.mp4') ?>" type="video/mp4">
                </video>
                <div class="video-control visible">
                  <div class="video-play js-video-slider-play"><i class="icon-play"></i></div>
                  <div class="video-stop js-video-slider-stop"><i class="icon-pause"></i></div>
                </div>
              </div>
              <div class="bnslider-text-wrap bnslider-overlay ">
                <div class="bnslider-text-content txt-middle txt-center txt-middle-m txt-center-m">
                  <div class="bnslider-text-content-flex ">
                    <div class="bnslider-vert " style="padding: 0px">
                      <div class="bnslider-text order-1 mt-sm bnslider-text--md text-center data-ini" data-animation="fadeInUp" data-animation-delay="800" data-fontcolor="#282828" data-fontweight="700" data-fontline="1.5"><h1>FAYYFIR</h1></div>
                      <div class="btn-wrap text-center  order-4 mt-md" data-animation="fadeIn" data-animation-delay="2000" style="opacity: 1;">
                        <a href="#" target="_self" class="btn">
                          Shop now
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div> -->

          </div>
          <div class="bnslider-arrows container-fluid">
            <div></div>
          </div>
          <div class="bnslider-dots container-fluid"></div>
        </div>
      </div>
    </div>
    <!-- //Main Slider -->

    <!-- Categories -->
    <div class="holder holder-mt-medium">
      <div class="container">
        <div class="prd-grid product-listing data-to-show-3 data-to-show-md-3 data-to-show-sm-2 js-category-grid">
          <?php foreach ($kategorihome as $ktgrhm) {?>
          <div class="prd prd--style2 prd-labels--max prd-labels-shadow">
            <a href="<?= base_url('product/shop').'/'.$ktgrhm->nama_kategori ?>" class="collection-grid-3-item image-hover-scale rounded-lg" style="box-shadow: 0 5px 10px rgba(0, 0, 0, .2);">
              <div class="collection-grid-3-item-img image-container" style="padding-bottom: 100%">
                <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url();  ?>/asset/images/categories/<?= $ktgrhm->gambar;  ?>" class="lazyload fade-up" alt="Banner">
                <div class="foxic-loader"></div>
              </div>
              <div class="collection-grid-3-caption-bg">
                <h2 class="collection-grid-3-title"><?= $ktgrhm->nama_kategori ?></h2>
              </div>
            </a>
          </div>
        <?php } ?>
      </div>
    </div>
   
    <div class="holder" hidden>
      <div class="container">
        <div class="title-wrap text-center">
          <h2 class="h1-style">New arrival</h2>
          <div class="h-sub maxW-825">Hurry up! Limited</div>
        </div>
        <div class="prd-grid-wrap position-relative">
          <div class="prd-grid data-to-show-4 data-to-show-lg-4 data-to-show-md-3 data-to-show-sm-2 data-to-show-xs-2 js-category-grid" data-grid-tab-content>
            <?php 
            foreach ($product as $pd) {
                  $hrg = $pd->harga;
                  $dsk = $pd->promo->diskon;
              ?>
            <div class="prd prd--style2 prd-labels--max prd-labels-shadow ">
              <div class="prd-inside">

                <div class="prd-img-area">

                  <a href="<?= base_url('product/detail').'/'.$pd->nama_kategori.'/'.$pd->id_product ?>" class="prd-img image-hover-scale image-container" style="padding-bottom: 128.48%">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $pd->gambar?>" alt="Oversized Cotton Blouse" class="js-prd-img lazyload fade-up">
                    <div class="foxic-loader"></div>
                    
                    <div class="prd-big-squared-labels">
                      <?php if ($pd->new == 'y') { ?>
                      <div class="label-new"><span>New</span></div>
                      <?php } if ($pd->best_seller == 'y') { ?>
                      <div class="label-sale"><span class="sale-text">Best Seller</span></div>
                     <?php } if (isset($pd->promo)) { ?>
                      <div class="label-sale"><span class="sale-text"><?= $pd->promo->diskon ?>%</span></div>
                      <?php } if ($pd->stok <= 2) { ?>
                      <div class="label-outstock"><span>Sold Out</span></div>
                      <?php } ?>
                    </div>
                  </a>
                  
                  <div class="prd-circle-labels">
                    <a href="#" class="circle-label-qview js-prd-quickview prd-hide-mobile" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Detail</span></a>
                  </div>

                  <ul class="list-options color-swatch">
                    <?php foreach($gambar_product as $gp){?>
                    <?php if($gp->id_product == $pd->id_product){?>
                    <li data-image="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>"><a href="#" class="js-color-toggle" data-toggle="tooltip" data-placement="right" title="Color Name"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" class="lazyload fade-up" alt="Color Name"></a></li>
                    <?php } } ?>
                  </ul>

                </div>

                <div class="prd-info">
                  <div class="prd-info-wrap">
                    <div class="prd-info-top">
                      <div class="prd-rating"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                    </div>
                    <div class="prd-rating justify-content-center"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                    <?php foreach($kategori as $ktgr){?>
                    <?php if($ktgr->id_kategori == $pd->id_kategori){?>
                    <div class="prd-tag"><a href="#"><?= $ktgr->nama_kategori ." | Sold : ". $pd->terjual ?>pcs</a></div>
                    <?php } }?>
                    <h2 class="prd-title"><a href="product.html"><?= $pd->nama_product ?></a></h2>
                    <div class="prd-description"><?= $pd->desc_en ?></div>
                    <!-- <div class="prd-action" id="addcart">
                      <?php foreach ($uk2 as $uk2_row) {} ?>
                      <?php if ($pd->stok <= 2){ ?>
                       <button class="btn js-prd-addtocart" type="button" style="background-color: grey;" >Out of Stock</button>
                       <?php }elseif ($uk2_row->id_product == $pd->id_product) {?>
                        <a href="<?= base_url('product/detail').'/'.$pd->nama_kategori.'/'.$pd->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                      <?php }else{ ?>
                      <button data-id="<?= $pd->id_product ?>" data-harga="<?= $pd->harga ?>" data-uk="<?= $pd->ukuran ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pd->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pd->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                      <?php } ?>
                    </div> -->
                  </div>
                  <div class="prd-hovers">
                    <div class="prd-circle-labels">
                      <div class="prd-hide-mobile"><a href="#" class="circle-label-qview js-prd-quickview" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Details</span></a></div>
                    </div>
                     <?php if (isset($pd->promo)) {?>
                      <div class="prd-price">
                      <?php 
                          $diskon = ($hrg * $pd->promo->diskon)/100;
                          $setdiskon = $hrg - $diskon;
                          // $harga = $hrg - $diskon;
                          $isRange = false;
                          foreach ($uk2 as $uk2_row) {
                              if ($uk2_row->id_product == $pd->id_product) {
                                  $isRange = true;
                                  break; 
                              }
                          } 
                      ?>
                          <div class="price-new">Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                          <?php  if (!$isRange){ ?>
                              <div class="price-old">Rp<?= number_format($hrg, 0, ",", ".") ?></div>
                          <?php } ?>

                          <?php foreach ($uk2 as $uk2_row) {
                              if ($uk2_row->id_product == $pd->id_product) {
                                  $diskon2 = ($uk2_row->harga * $pd->promo->diskon)/100;
                                  $setdiskon = $uk2_row->harga - $diskon2;
                          ?>
                              <div class="price-new"> - Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                              <div class="price-old">Rp<?= number_format($hrg, 0, ",", ".") ?> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                          <?php break; } }?>
                      </div>
                      <?php }else{ 
                        $harga = $hrg;
                        $setdiskon = 0;
                        ?>
                          <div class="prd-price">
                              <!-- <div class="price-old">Rp500.000</div> -->
                              <div class="price-new">Rp<?= number_format($harga, 0, ",", ".") ?> </div>
                              <?php foreach ($uk2 as $uk2_row) {
                                  if ($uk2_row->id_product == $pd->id_product) {?>
                                      <div class="price-new"> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                              <?php break; } }?>
                          </div>
                      <?php } ?>
                    <div class="prd-action">
                      <div class="prd-action-left" id="addcart">
                        <?php if ($pd->stok <= 2){ ?>
                       <button class="btn js-prd-addtocart" type="button" style="background-color: grey;" >Out of Stock</button>
                       <?php }elseif ($uk2_row->id_product == $pd->id_product) {?>
                          <a href="<?= base_url('product/detail').'/'.$pd->nama_kategori.'/'.$pd->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                        <?php }else{ ?>
                        <button data-id="<?= $pd->id_product ?>" data-harga="<?= $hrg ?>" data-uk="<?= $pd->ukuran ?>" data-pot="<?= $setdiskon ?>" data-diskon="<?= $dsk ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pd->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pd->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                      <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Categories -->

  <!-- pisah -->
 <?php foreach ($bannertengahv1 as $vt1) {}?>
  <?php foreach ($bannertengahv2 as $vt2) {}?>
  <div class="holder holder-mt-medium " hidden>
      <div class="container">
        <a href="<?= base_url() ?>" target="_blank" class="bnr-wrap bnr-">
          <div class="bnr custom-caption image-hover-scale bnr--middle bnr--right bnr--fullwidth">
            <div class="bnr-img d-none d-sm-block image-container" style="padding-bottom: 41.36752136752137%">
              <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/banner/<?= $vt1->gambar ?>" class="lazyload fade-up" alt="">
            </div>
            <div class="bnr-img d-sm-none image-container" style="padding-bottom: 74.3139407244786%">
              <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/banner/<?= $vt2->gambar ?>" class="lazyload fade-up" alt="">
            </div>
            <div class="bnr-caption text-center" style="padding: 4% 4%; ">
              <div class="bnr-caption-inside w-s-50 w-ms-100 title-wrap">
                <!-- <h1 class="h1-style" style="color: white;">The Best Perfumes<br class="d-sm-none"> Of The Month</h1> -->
                <div class="h-sub mt-0"></div>
                <div class="bnr-btn inherit mt-sm order-3">
                  <!-- <div class="btn">Buy Now</div> -->
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
    <br>

    <!-- pisah -->

    <div class="holder" hidden>
      <div class="container">
        <div class="title-wrap text-center">
          <h2 class="h1-style">Best Seller</h2>
          <div class="h-sub maxW-825">Hurry up! Limited</div>
        </div>
        <div class="prd-grid-wrap position-relative">
          <div class="prd-grid data-to-show-4 data-to-show-lg-4 data-to-show-md-3 data-to-show-sm-2 data-to-show-xs-2 js-category-grid" data-grid-tab-content>
            <?php foreach ($productbest as $pdbst) {
                  $hrg = $pdbst->harga;
                  $dsk = $pdbst->promo->diskon;
              ?>
            <div class="prd prd--style2 prd-labels--max prd-labels-shadow ">
              <div class="prd-inside">

                <div class="prd-img-area">

                  <a href="<?= base_url('product/detail').'/'.$pdbst->nama_kategori.'/'.$pdbst->id_product ?>" class="prd-img image-hover-scale image-container" style="padding-bottom: 128.48%">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $pdbst->gambar?>" alt="Oversized Cotton Blouse" class="js-prd-img lazyload fade-up">
                    <div class="foxic-loader"></div>
                    
                    <div class="prd-big-squared-labels">
                      <?php if ($pdbst->new == 'y') { ?>
                      <div class="label-new"><span>New</span></div>
                      <?php } if ($pdbst->best_seller == 'y') { ?>
                      <div class="label-sale"><span class="sale-text">Best Seller</span></div>
                      <?php } if (isset($pdbst->promo)) { ?>
                      <div class="label-sale"><span class="sale-text"><?= $pdbst->promo->diskon ?>%</span></div>
                      <?php } if ($pdbst->stok <= 2) { ?>
                      <div class="label-outstock"><span>Sold Out</span></div>
                      <?php } ?>
                    </div>
                  </a>
                  
                  <div class="prd-circle-labels">
                    <a href="#" class="circle-label-qview js-prd-quickview prd-hide-mobile" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Detail</span></a>
                  </div>

                  <ul class="list-options color-swatch">
                    <?php foreach($gambar_product as $gp){?>
                    <?php if($gp->id_product == $pdbst->id_product){?>
                    <li data-image="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>"><a href="#" class="js-color-toggle" data-toggle="tooltip" data-placement="right" title="Color Name"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" class="lazyload fade-up" alt="Color Name"></a></li>
                    <?php } } ?>
                  </ul>

                </div>

                <div class="prd-info">
                  <div class="prd-info-wrap">
                    <div class="prd-info-top">
                      <div class="prd-rating"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                    </div>
                    <div class="prd-rating justify-content-center"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                    <?php foreach($kategori as $ktgr){?>
                    <?php if($ktgr->id_kategori == $pdbst->id_kategori){?>
                    <div class="prd-tag"><a href="#"><?= $ktgr->nama_kategori ." | Sold : ". $pdbst->terjual ?>pcs</a></div>
                    <?php } }?>
                    <h2 class="prd-title"><a href="product.html"><?= $pdbst->nama_product ?></a></h2>
                    <div class="prd-description"><?= $pdbst->desc_en ?></div>
                    <!-- <div class="prd-action" id="addcart">
                      <?php foreach ($uk2 as $uk2_row) {} ?>
                      <?php if ($pdbst->stok <= 2){ ?>
                       <button class="btn js-prd-addtocart" type="button" style="background-color: grey;" >Out of Stock</button>
                       <?php }elseif ($uk2_row->id_product == $pdbst->id_product) {?>
                          <a href="<?= base_url('product/detail').'/'.$pdbst->nama_kategori.'/'.$pdbst->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                      <?php }else{ ?>
                        <button data-id="<?= $pdbst->id_product ?>" data-harga="<?= $pdbst->harga ?>" data-uk="<?= $pdbst->ukuran ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pdbst->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pdbst->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                      <?php } ?>
                    </div> -->
                  </div>
                  <div class="prd-hovers">
                    <div class="prd-circle-labels">
                      <div class="prd-hide-mobile"><a href="#" class="circle-label-qview js-prd-quickview" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Details</span></a></div>
                    </div>
                    <?php if (isset($pdbst->promo)) {?>
                      <div class="prd-price">
                      <?php 
                          $diskon = ($hrg * $pdbst->promo->diskon)/100;
                          $setdiskon = $hrg - $diskon;
                          // $harga = $hrg - $diskon;
                          $isRange = false;
                          foreach ($uk2 as $uk2_row) {
                              if ($uk2_row->id_product == $pdbst->id_product) {
                                  $isRange = true;
                                  break; 
                              }
                          } 
                      ?>
                          <div class="price-new">Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                          <?php  if (!$isRange){ ?>
                              <div class="price-old">Rp<?= number_format($hrg, 0, ",", ".") ?></div>
                          <?php } ?>

                          <?php foreach ($uk2 as $uk2_row) {
                              if ($uk2_row->id_product == $pdbst->id_product) {
                                  $diskon2 = ($uk2_row->harga * $pdbst->promo->diskon)/100;
                                  $setdiskon = $uk2_row->harga - $diskon2;
                          ?>
                              <div class="price-new"> - Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                              <div class="price-old">Rp<?= number_format($hrg, 0, ",", ".") ?> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                          <?php break; } }?>
                      </div>
                      <?php }else{ 
                        $harga = $hrg;
                        $setdiskon = 0;
                        ?>
                          <div class="prd-price">
                              <!-- <div class="price-old">Rp500.000</div> -->
                              <div class="price-new">Rp<?= number_format($harga, 0, ",", ".") ?> </div>
                              <?php foreach ($uk2 as $uk2_row) {
                                  if ($uk2_row->id_product == $pdbst->id_product) {?>
                                      <div class="price-new"> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                              <?php break; } }?>
                          </div>
                      <?php } ?>
                    <div class="prd-action">
                      <div class="prd-action-left" id="addcart">
                        <?php if ($pdbst->stok <= 2){ ?>
                       <button class="btn js-prd-addtocart" type="button" style="background-color: grey;">Out of Stock</button>
                       <?php }elseif ($uk2_row->id_product == $pdbst->id_product) {?>
                          <a href="<?= base_url('product/detail').'/'.$pdbst->nama_kategori.'/'.$pdbst->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                      <?php }else{ ?>
                         <button data-id="<?= $pdbst->id_product ?>" data-harga="<?= $hrg ?>" data-uk="<?= $pdbst->ukuran ?>" data-pot="<?= $setdiskon ?>" data-diskon="<?= $dsk ?>" type="submit"class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pdbst->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pdbst->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
 
 

  