
  <div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="<?= base_url() ?>">Home</a></li>
          <li><span>Search Result</span></li>
        </ul>
      </div>
    </div>
    
    <div class="holder">
      <div class="container">
        <!-- Two columns -->
        <!-- Page Title -->
        <div class="page-title text-center">
        
        </div>
        <!-- /Page Title -->
        
        <div class="row">

          <!-- Center column -->
          <div class="col-lg aside">
            <div class="prd-grid-wrap">
              <!-- Products Grid -->
              <div class="prd-grid product-listing data-to-show-4 data-to-show-md-3 data-to-show-sm-2 js-category-grid" data-grid-tab-content>
                <?php foreach ($product as $pd) {
                  $hrg = $pd->harga;
                  ?>
                <div class="prd prd--style2 prd-labels--max prd-labels-shadow ">
                  <div class="prd-inside">

                    <div class="prd-img-area">

                      <a href="<?= base_url('product/detail/').($pd->nama_kategori ?? 'all').'/'.$pd->id_product ?>" class="prd-img image-hover-scale image-container" style="padding-bottom: 128.48%">
                        <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $pd->gambar?>" alt="Leather Pegged Pants" class="js-prd-img lazyload fade-up">
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
                        <a href="<?= base_url('product/detail/').($pd->nama_kategori ?? 'all').'/'.$pd->id_product ?>" class="circle-label-qview" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Details</span></a>
                      </div>

                       <ul class="list-options color-swatch">
                          <?php foreach($gambar_product as $gp){?>
                          <?php if($gp->id_product == $pd->id_product){?>
                          <li data-image="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>"><a href="#" class="js-color-toggle" data-toggle="tooltip" data-placement="right" title="Another Image"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $gp->gambar?>" class="lazyload fade-up" alt="Color Name"></a></li>
                          <?php } } ?>
                        </ul>

                     <!--  <ul class="list-options color-swatch">
                        <li data-image="<?= base_url() ?>asset/images/product/<?= $pd->gambar?>"><a href="#" class="js-color-toggle" data-toggle="tooltip" data-placement="right" title="Another image"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $pd->gambar?>" class="lazyload fade-up" alt="Another image"></a></li>
                      </ul> -->

                    </div>

                    <div class="prd-info">
                      <div class="prd-info-wrap">
                        <div class="prd-info-top">
                          <div class="prd-rating"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                        </div>
                        <div class="prd-rating justify-content-center"><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i><i class="icon-star-fill fill"></i></div>
                        <div class="prd-tag"><?= $namakategori ?></div>
                        <h2 class="prd-title"><a href="<?= base_url('product/detail').'/'.$namakategori.'/'.$pd->id_product ?>"><?= $pd->nama_product ?></a></h2>
                        <div class="prd-description"><?= $pd->desc_en ?></div>
                        <!-- <div class="prd-action" id="addcart">
                          <?php foreach ($uk2 as $uk2_row) {} ?>
                          <?php if ($pd->stok < 3){ ?>
                             <button class="btn js-prd-addtocart" type="button" style="background-color: grey;" >Out of Stock</button>
                          <?php }elseif ($uk2_row->id_product == $pd->id_product) {?>
                              <a href="<?= base_url('product/detail').'/'.$namakategori.'/'.$pd->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                          <?php }else{ ?>
                            <button data-id="<?= $pd->id_product ?>" data-harga="<?= $pd->harga ?>" data-uk="<?= $pd->ukuran ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pd->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pd->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}'>Add To Cart</button>
                          <?php } ?>
                        </div> -->
                      </div>
                      <div class="prd-hovers">
                        <div class="prd-circle-labels">
                          <div class="prd-hide-mobile"><a href="<?= base_url('product/detail/').($pd->nama_kategori ?? 'all').'/'.$pd->id_product ?>" class="circle-label-qview js-prd-quickview" data-src="ajax/ajax-quickview.html"><i class="icon-eye"></i><span>See Details</span></a></div>
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
                            <div class="price-new" hidden>Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                            <?php  if (!$isRange){ ?>
                                <div class="price-old" hidden>Rp<?= number_format($hrg, 0, ",", ".") ?></div>
                            <?php } ?>

                            <?php foreach ($uk2 as $uk2_row) {
                                if ($uk2_row->id_product == $pd->id_product) {
                                  $diskon2 = ($uk2_row->harga * $pd->promo->diskon)/100;
                                    $setdiskon = $uk2_row->harga - $diskon2;
                            ?>
                                <div class="price-new" hidden> - Rp<?= number_format($setdiskon, 0, ",", ".") ?></div>
                                <div class="price-old" hidden>Rp<?= number_format($hrg, 0, ",", ".") ?> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                            <?php break; } }?>
                        </div>
                        <?php }else{ 
                          $harga = $hrg;
                          $setdiskon = 0;
                          ?>
                            <div class="prd-price">
                                <!-- <div class="price-old">Rp500.000</div> -->
                                <div class="price-new" hidden>Rp<?= number_format($harga, 0, ",", ".") ?> </div>
                                <?php foreach ($uk2 as $uk2_row) {
                                    if ($uk2_row->id_product == $pd->id_product) {?>
                                        <div class="price-new" hidden> - Rp<?= number_format($uk2_row->harga, 0, ",", ".") ?></div>
                                <?php break; } }?>
                            </div>
                        <?php } ?>
                        <div class="prd-action">
                          <div class="prd-action-left" id="addcart">
                            <?php if ($pd->stok <= 2){ ?>
                             <button class="btn js-prd-addtocart" type="button" style="background-color: grey;" >Out of Stock</button>
                            <?php }elseif ($uk2_row->id_product == $pd->id_product) {?>
                              <a href="<?= base_url('product/detail').'/'.$namakategori.'/'.$pd->id_product ?>" class="btn js-prd-addtocart btn_cart" >Choose Size</a>
                            <?php }else{ ?>
                              <button data-id="<?= $pd->id_product ?>" data-harga="<?= $hrg ?>" data-uk="<?= $pd->ukuran ?>" data-pot="<?= $setdiskon ?>" type="submit" class="btn js-prd-addtocart btn_cart" data-product='{"name": "<?= $pd->nama_product ?>", "path":"<?= base_url();  ?>asset/images/product/<?= $pd->gambar;  ?>", "url":"product.html", "aspect_ratio":0.778}' hidden>Add To Cart</button>
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
          <!-- /Center column -->
        </div>
        <!-- /Two columns -->
      </div>
    </div>
  </div>