
<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span><?= $product->nama_kategori ?></span></li>
          <li><span><?= $product->nama_sub_kategori ?></span></li>
          <li><span><?= $product->nama_product ?></span></li>
        </ul>
      </div>
    </div>
    <?php 
    $hrga = $product->harga; 
    ?>

    <div class="holder">
      <div class="container js-prd-gallery" id="prdGallery">
        <div class="row prd-block prd-block-under prd-block--prv-bottom">
          <div class="col-md-auto prd-block-prevnext-wrap">
            <!-- <div class="prd-block-prevnext">
              <a href="#"><span class="prd-img"><img class="lazyload fade-up" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $product->gambar?>" alt=""><i class="icon-arrow-left"></i></span></a>
            </div> -->
          </div>
        </div>

        <div class="row prd-block prd-block--prv-bottom">
          <div class="col-md-8 col-lg-8 col-xl-8 aside--sticky js-sticky-collision">
            <div class="aside-content">
              <!-- Product Gallery -->
              <div class="mb-2 js-prd-m-holder"></div>
              <div class="prd-block_main-image">
                <div class="prd-block_main-image-holder" id="prdMainImage">
                  <div class="product-main-carousel js-product-main-carousel" data-zoom-position="inner">
                    <?php foreach($gambar_product as $gp){?>
                  <?php if($gp->id_product == $product->id_product){?>
                    <div data-value="Beige"><span class="prd-img"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" class="lazyload fade-up elzoom" alt="" data-zoom-image="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" /></span></div>
                    <?php }} ?>
                  </div>
                  <?php if($product->best_seller == 'y'){ ?>
                  <div class="prd-block_label-sale-squared justify-content-center align-items-center"><span>Best Seller</span></div>
                  <?php } if (isset($product->promo)) { ?>
                  <div class="prd-block_label-sale-squared justify-content-center align-items-center"><span><?= $product->promo->diskon ?>%</span></div>
                  <?php }if($product->stok <= 2){ ?>
                  <div class="prd-block_label-outstock-squared justify-content-center align-items-center"><span>Sold Out</span></div>
                  <?php } ?>
                </div>
                <div class="prd-block_main-image-links">
                  <a href="<?= base_url() ?>asset/images/product/<?= $product->gambar?>" class="prd-block_zoom-link"><i class="icon-zoom-in"></i></a>
                </div>
              </div>

              <div class="product-previews-wrapper">
                <div class="product-previews-carousel js-product-previews-carousel">
                  <?php foreach($gambar_product as $gp){?>
                  <?php if($gp->id_product == $product->id_product){?>
                  <a href="#" data-value="Beige"><span class="prd-img"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" class="lazyload fade-up" alt="" /></span></a>
                  <?php }} ?>
                </div>
              </div>
              <!-- /Product Gallery -->
                  

            </div>
          </div>
          <div class="col-md-10 col-lg-10 col-xl-10 mt-1 mt-md-0">
            <div class="prd-block_info prd-block_info--style1" data-prd-handle="/products/copy-of-suede-leather-mini-skirt">
              <div class="prd-block_info-top prd-block_info_item order-0 order-md-2">
               <div class="prd-block_title-wrap">
                  <h1 class="prd-block_title"><?= $product->nama_product ?></h1>
                </div>
              </div>
              <div class="prd-block_info-top prd-block_info_item order-0 order-md-2">
                <div class="prd-block_price prd-block_price--style2">
                  <div class="prd-block_price--actual" id="hargaawal"> </div>
                  <div class="prd-block_price-old-wrap">
                    <span class="prd-block_price--old" id="hargasetdis"></span>
                    <!-- <span class="prd-block_price--text">You Save: $131.99 (28%)</span> -->
                  </div>
                </div>
                <!-- <div class="prd-block_viewed-wrap d-none d-md-flex">
                  <div class="prd-block_viewed">
                    <i class="icon-time"></i>
                    <span>This product was viewed 13 times within last hour</span>
                  </div>
                </div> -->
              </div>
              <div class="prd-block_description prd-block_info_item ">
                <h3>Description</h3>
                <p><?= $product->desc_en ?></p>
                <div class="mt-1"></div>
                <div class="row vert-margin-less">
                  <div class="col-sm">
                    <ul class="list-marker">
                      <li>100% original</li>
                      <li>Best Quality</li>
                    </ul>
                  </div>
                  <div class="col-sm">
                    <ul class="list-marker">
                      <li>Affordable Prices</li>
                      <li>Sold : <?=$product->terjual ?> pcs</li>
                      <!-- <li>Only non-chlorine</li> -->
                    </ul>
                  </div>
                </div>
              </div>
              <div class="prd-progress prd-block_info_item" data-left-in-stock="">
                <div class="prd-progress-text">
                  Hurry Up! Left <span class="prd-progress-text-left js-stock-left" id="cekstok"></span> in stock
                </div>
                <div class="prd-progress-text-null"></div>
                <div class="prd-progress-bar-wrap progress">
                  <div class="prd-progress-bar progress-bar active" data-stock="50, 10, 30, 25, 1000, 15000" style="width: 33%;"></div>
                </div>
              </div>
              <div class="prd-block_info_item prd-block_info-when-arrives d-none" data-when-arrives>
                <div class="prd-block_links prd-block_links m-0 d-inline-flex">
                  <i class="icon-email-1"></i>
                  <div><a href="#" data-follow-up="" data-name="Oversize Cotton Dress" class="prd-in-stock" data-src="#whenArrives">Inform me when the item arrives</a></div>
                </div>
              </div>
              <div class="prd-block_info-box prd-block_info_item">
                <div class="two-column">
                  <p>Availability:
                    <span class="prd-in-stock" data-stock-status="">In stock</span>
                  </p>
                  <p class="prd-taxes">Tax Info:
                    <span>Tax included.</span>
                  </p>
                  <p>Collection: <span> <a href="collections.html" data-toggle="tooltip" data-placement="top" data-original-title="View all"><?= $product->nama_sub_kategori ?></a></span></p>
                  <p>Sku: <span data-sku="">ALSHRFSHP-45812</span></p>
                  <p>Category: <span><?= $product->nama_kategori ?></span></p>
                  <p>Barcode: <span>314363563</span></p>
                </div>
              </div>
              <div class="order-0 order-md-100">
                <form method="post" action="#">
                  <div class="prd-block_options" hidden>
                    <div class="prd-size swatches">
                      <div class="option-label">Size:</div>

                      <div class="btn-group btn-group-toggle" data-toggle="buttons" role="group">

                      <?php
                        $set1diskon =0;
                       if (isset($product->promo)) {
                         $diskon = ($hrga * $product->promo->diskon)/100;
                          $set1diskon = $hrga - $diskon;

                          // $harga = $hrga - $diskon;
                          $isRange = false;
                          foreach ($uku2 as $uk) {
                              if ($uk->id_product == $product->id_product) {
                                  $isRange = true;
                                  break; 
                              }
                          }
                           ?>

                        <label class="form-control btn btn-outline-success active">
                          <input type="radio" value="<?= $hrga ?>" name="" autocomplete="off" onchange="hargautama()" checked=""><?= $product->ukuran ?>
                        </label>
                        <?php foreach ($uku2 as $uk) {?>
                        <?php if($uk->id_product == $product->id_product){
                         $diskon2 = ($uk->harga * $product->promo->diskon)/100;
                          $setdiskon = $uk->harga - $diskon2;
                          ?>
                        <label class="form-control btn btn-outline-success">
                          <input type="radio" value="<?= $uk->harga ?>" name="" autocomplete="off" onchange="hargalain('Rp <?= number_format($setdiskon, 0, ",", ".") ?>','Rp <?= number_format($uk->harga, 0, ",", ".") ?>',<?= $uk->harga ?>,'<?= $uk->ukuran ?>','<?= $uk->stok ?>',<?= $setdiskon ?>)"><?= $uk->ukuran ?>
                        </label>
                        <?php }} ?>
                      
                      <?php 
                      }else{
                      $set1diskon = 0;
                       ?>
                      

                        <label class="form-control btn btn-outline-success active">
                          <input type="radio" value="<?= $hrga ?>" name="" autocomplete="off" onchange="hargautama()" checked=""><?= $product->ukuran ?>
                        </label>
                        <?php foreach ($uku2 as $uk) {?>
                        <?php if($uk->id_product == $product->id_product){
                          $setdiskon = $uk->harga - $diskon;
                          ?>
                        <label class="form-control btn btn-outline-success">
                          <input type="radio" value="<?= $uk->harga ?>" name="" autocomplete="off" onchange="hargalain('Rp <?= number_format($setdiskon, 0, ",", ".") ?>','Rp <?= number_format($uk->harga, 0, ",", ".") ?>',<?= $uk->harga ?>,'<?= $uk->ukuran ?>','<?= $uk->stok ?>',<?= $setdiskon ?>,<?= $dsk ?>)"><?= $uk->ukuran ?>
                        </label>
                        <?php }} ?>
                      <?php } ?>
                      </div>

                     
                    </div>
                  </div>
                  <div class="prd-block_actions prd-block_actions--wishlist">
                    <!-- <div class="prd-block_qty">
                      <div class="qty qty-changer">
                        <button class="decrease js-qty-button"></button>
                        <input type="number" class="qty-input" name="quantity" value="1" data-min="1" data-max="1000">
                        <button class="increase js-qty-button"></button>
                      </div>
                    </div> -->
                    <div class="btn-wrap">
                      
                      <a href="https://api.whatsapp.com/send?phone=6281290004460&text=Selamat%20Datang%20di%20*FAYYFIR*%0A%0AAda%20yang%20ingin%20ditanyakan%3F%20atau%20di%20konfirmasi%3F%0A%0ATerimakasih%20telah%20mempercayai%20kami" type="button" style="background-color: #FBBF23; color: #282828; font-weight: bold;" class="btn btn--add-to-cart" id="orderwa">Order</a>
                      
                      <button type="button" style="background-color: grey;" class="btn btn--add-to-cart" id="stokhabis" hidden>Out of Stock</button>
                      
                      <button data-id="<?= $product->id_product ?>" data-disk="<?= $sdk ?>" type="submit" class="btn btn--add-to-cart js-trigger-addtocart js-prd-addtocart btn_cart" data-product='{"name":  "<?= $product->nama_product ?>",  "url ": "product.html",  "path":"<?= base_url() ?>asset/images/product/<?= $gp->gambar?>",  "aspect_ratio ": "0.778"}' id="stokada" hidden>Add to cart</button>
                    
                    </div>
                  </div>
                </form>
              </div>

              <div class="prd-block_info_item" hidden>
                <ul class="prd-block_links list-unstyled">
                  <li><i class="icon-delivery-1"></i><a href="#" data-fancybox class="modal-info-link" data-src="#deliveryInfo">Delivery and Return</a></li>
                  <li><i class="icon-email-1"></i><a href="#" data-fancybox class="modal-info-link" data-src="#contactModal">Ask about this product</a></li>
                </ul>
                <div id="deliveryInfo" style="display: none;" class="modal-info-content modal-info-content-lg">
                  <div class="modal-info-heading">
                    <div class="mb-1"><i class="icon-delivery-1"></i></div>
                    <h2>Delivery and Return</h2>
                  </div>
                  <br>
                  <h5>Our parcel courier service</h5>
                  <p>Foxic is proud to offer an exceptional international parcel shipping service. It is straightforward and very easy to organise international parcel shipping. Our customer service team works around the clock to make sure that you receive high quality courier service from start to finish.</p>
                  <p>Sending a parcel with us is simple. To start the process you will first need to get a quote using our free online quotation service. From this, you’ll be able to navigate through the online form to book a collection date for your parcel, selecting a shipping day suitable for you.</p>
                  <br>
                  <h5>Shipping Time</h5>
                  <p>The shipping time is based on the shipping method you chose.<br>
                    EMS takes about 5-10 working days for delivery.<br>
                    DHL takes about 2-5 working days for delivery.<br>
                    DPEX takes about 2-8 working days for delivery.<br>
                    JCEX takes about 3-7 working days for delivery.<br>
                    China Post Registered Mail takes 20-40 working days for delivery.</p>
                </div>
                <div id="contactModal" style="display: none;" class="modal-info-content modal-info-content-sm">
                  <div class="modal-info-heading">
                    <div class="mb-1"><i class="icon-envelope"></i></div>
                    <h2>Have a question?</h2>
                  </div>
                  <form method="post" action="#" id="contactForm" class="contact-form">
                    <div class="form-group">
                      <input type="text" name="contact[name]" class="form-control form-control--sm" placeholder="Name">
                    </div>
                    <div class="form-group">
                      <input type="text" name="contact[email]" class="form-control form-control--sm" placeholder="Email" required="">
                    </div>
                    <div class="form-group">
                      <input type="text" name="contact[phone]" class="form-control form-control--sm" placeholder="Phone Number">
                    </div>
                    <div class="form-group">
                      <textarea class="form-control textarea--height-170" name="contact[body]" placeholder="Message" required="">Hi! I need next info about the "Oversize Cotton Dress":</textarea>
                    </div>
                    <button class="btn" type="submit">Ask our consultant</button>
                    <p class="p--small mt-15 mb-0">and we will contact you soon</p><input name="recaptcha-v3-token" type="hidden" value="03AGdBq27T8WvzvZu79QsHn8lp5GhjNX-w3wkcpVJgCH15Ehh0tu8c9wTKj4aNXyU0OLM949jTA4cDxfznP9myOBw9m-wggkfcp1Cv_vhsi-TQ9E_EbeLl33dqRhp2sa5tKBOtDspTgwoEDODTHAz3nuvG28jE7foIFoqGWiCqdQo5iEphqtGTvY1G7XgWPAkNPnD0B9V221SYth9vMazf1mkYX3YHAj_g_6qhikdQDsgF2Sa2wOcoLKWiTBMF6L0wxdwhIoGFz3k3VptYem75sxPM4lpS8Y_UAxfvF06fywFATA0nNH0IRnd5eEPnnhJuYc5LYsV6Djg7_S4wLBmOzYnahC-S60MHvQFf-scQqqhPWOtgEKPihUYiGFBJYRn2p1bZwIIhozAgveOtTNQQi7FGqmlbKkRWCA">
                  </form>
                </div>
              </div>
              <div class="prd-block_info_item" hidden>
                <img class="img-responsive lazyload d-none d-sm-block" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/payment/safecheckout.png" alt="" width="100%" height="100%">
                <img class="img-responsive lazyload d-sm-none" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/payment/safecheckout-m.png" alt="" width="100%" height="100%">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="holder prd-block_links-wrap-bg d-none d-md-block">
      <div class="prd-block_links-wrap prd-block_info_item container mt-2 mt-md-5 py-1" hidden>
        <div class="prd-block_link"><span><i class="icon-call-center"></i>24/7 Support</span></div>
        <div class="prd-block_link">
          <span>Use promocode FAYYFIR to get 15% discount!</span>
        </div>
        <div class="prd-block_link"><span><i class="icon-delivery-truck"></i> Fast Shipping</span></div>
      </div>
    </div>


    <div class="holder" hidden>
      <div class="container">
        <div class="title-wrap text-center">
          <h2 class="h1-style">You may also like</h2>
          <div class="carousel-arrows carousel-arrows--center"></div>
        </div>
        <div class="prd-grid prd-carousel js-prd-carousel slick-arrows-aside-simple slick-arrows-mobile-lg data-to-show-4 data-to-show-md-3 data-to-show-sm-3 data-to-show-xs-2" data-slick='{"slidesToShow": 5, "slidesToScroll": 1, "responsive": [{"breakpoint": 992,"settings": {"slidesToShow": 3, "slidesToScroll": 1}},{"breakpoint": 768,"settings": {"slidesToShow": 2, "slidesToScroll": 1}},{"breakpoint": 480,"settings": {"slidesToShow": 2, "slidesToScroll": 1}}]}'>
          <?php foreach ($opsi as $op) {
                  $hrg = $op->harga;
                  $dsk = $op->promo->diskon
           ?>
          <div class="prd prd--style2 prd-labels--max prd-labels-shadow ">
              <div class="prd-inside">

                <div class="prd-img-area">

                  <a href="<?= base_url('product/detail').'/'.$op->nama_kategori.'/'.$op->id_product ?>" class="prd-img image-hover-scale image-container" style="padding-bottom: 128.48%">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $op->gambar?>" alt="Oversized Cotton Blouse" class="js-prd-img lazyload fade-up">
                    <div class="foxic-loader"></div>
                    
                    <div class="prd-big-squared-labels">
                      <?php if ($op->new == 'y') { ?>
                      <div class="label-new"><span>New</span></div>
                      <?php } if ($op->best_seller == 'y') { ?>
                      <div class="label-sale"><span class="sale-text">Best Seller</span></div>
                       <?php } if (isset($op->promo)) { ?>
                          <div class="label-sale"><span class="sale-text"><?= $op->promo->diskon ?>%</span></div>
                          <?php } if ($op->stok <= 2) { ?>
                      <div class="label-outstock"><span>Sold Out</span></div>
                      <?php } ?>
                    </div>
                  </a>
                  
                  <div class="prd-circle-labels">
                    <a href="#" class="circle-label-qview js-prd-quickview prd-hide-mobile" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Detail</span></a>
                  </div>

                  <ul class="list-options color-swatch">
                    <?php foreach($gambar_product as $gp){?>
                    <?php if($gp->id_product == $op->id_product){?>
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
                    <?php if($ktgr->id_kategori == $op->id_kategori){?>
                    <div class="prd-tag"><a href="#"><?= $ktgr->nama_kategori ." | Sold : ". $op->terjual ?>pcs</a></div>
                    <?php } }?>
                    <h2 class="prd-title"><a href="product.html"><?= $op->nama_product ?></a></h2>
                    <div class="prd-description"><?= $op->desc_en ?></div>
                   <!--  <div class="prd-action" id="addcart">
                      <?php foreach ($uk2 as $uk2_row) {} ?>
                      <?php if ($op->stok <= 2){ ?>
                         <button type="button" class="btn js-prd-addtocart" style="background-color: grey;" >Out of Stock</button>
                        <?php }elseif ($uk2_row->id_product == $op->id_product) {?>
                          <a href="<?= base_url('product/detail').'/'.$ktgr->nama_kategori.'/'.$op->id_product ?>" class="btn js-prd-addtocart" >Choose Size</a>
                        <?php }else{ ?>
                       <button data-id="<?= $op->id_product ?>" data-harga="<?= $op->harga ?>" data-uk="<?= $op->ukuran ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $op->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $op->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                      <?php } ?>
                    </div> -->
                  </div>
                  <div class="prd-hovers">
                    <div class="prd-circle-labels">
                      <div class="prd-hide-mobile"><a href="#" class="circle-label-qview js-prd-quickview" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Details</span></a></div>
                    </div>
                     <?php if (isset($op->promo)) {?>
                      <div class="prd-price">
                      <?php 
                          $diskon = ($hrg * $op->promo->diskon)/100;
                          $setdiskon = $hrg - $diskon;
                          // $harga = $hrg - $diskon;
                          $isRange = false;
                          foreach ($uk2 as $uk2_row) {
                              if ($uk2_row->id_product == $op->id_product) {
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
                              if ($uk2_row->id_product == $op->id_product) {
                                  $diskon2 = ($uk2_row->harga * $op->promo->diskon)/100;
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
                                  if ($uk2_row->id_product == $op->id_product) {?>
                                      <div class="price-new"> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                              <?php break; } }?>
                          </div>
                      <?php } ?>
                    <div class="prd-action">
                      <div class="prd-action-left" id="addcart">
                        <?php if ($op->stok <= 2){ ?>
                         <button class="btn js-prd-addtocart" style="background-color: grey;" type="button">Out of Stock</button>
                        <?php }elseif ($uk2_row->id_product == $op->id_product) {?>
                          <a href="<?= base_url('product/detail').'/'.$ktgr->nama_kategori.'/'.$op->id_product ?>" class="btn js-prd-addtocart" >Choose Size</a>
                        <?php }else{ ?>
                         <button data-id="<?= $op->id_product ?>" data-harga="<?= $hrg ?>" data-uk="<?= $op->ukuran ?>" data-pot="<?= $setdiskon ?>" data-diskon="<?= $dsk ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $op->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $op->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
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
  <script type="text/javascript">
    function hargautama(){
            diskon = <?= $product->promo->diskon ?>;
            stok = <?= $product->stok; ?>;
            cekdisc = <?= $set1diskon ?>;
            disc = 'Rp <?= number_format($set1diskon, 0, ",", ".") ?>';
            if(cekdisc == 0){
            document.getElementById("hargaawal").innerHTML = 'Rp <?= number_format($product->harga, 0, ",", ".") ?>';
            document.getElementById("hargasetdis").style.display = 'none';
            }else{
            document.getElementById("hargaawal").innerHTML = disc;
            document.getElementById("hargasetdis").innerHTML = 'Rp <?= number_format($product->harga, 0, ",", ".") ?>';
            }
            document.getElementById("cekstok").innerHTML = stok;
            harga = <?= $product->harga ?>;
            setdiskon = cekdisc;
            ukuran = '<?= $product->ukuran ?>';
            // diskon = <?= $dsk ?>;

            if(Number(stok) <= 2){
            $("#stokhabis").show();
            $("#stokada").hide();
            }else{
            $("#stokhabis").hide();
            $("#stokada").show();
            }
        }
  </script>

