<div class="page-content">        
  <div class="holder breadcrumbs-wrap mt-0">        
    <div class="container">        
      <ul class="breadcrumbs">        
        <li><a href="<?= base_url() ?>">Home</a></li>        
        <li><span><?= urldecode($namakategori) ?></span></li>        
        <li><span><?= urldecode($namasubkategori) ?></span></li>        
      </ul>        
    </div>        
  </div>        

  <!-- Main Slider -->      
  <div class="holder fullwidth full-nopad mt-0">      
    <div class="container">      
      <div class="bnslider-wrapper">      
        <div class="bnslider bnslider--lg keep-scale" id="bnslider-001" data-slick='{"arrows": true, "dots": true}' data-autoplay="true" data-speed="5000" data-start-width="1900" data-start-height="800" data-start-mwidth="1700" data-start-mheight="800">      
          <?php foreach ($bannerkategori as $bk) {?>      
          <div class="bnslider-slide">      
            <div class="bnslider-image-mobile lazyload" data-bgset="<?= base_url() ?>asset/images/categories/<?=$bk->gambar?>"></div>      
            <div class="bnslider-image lazyload" data-bgset="<?= base_url() ?>asset/images/categories/<?=$bk->gambar?>"></div>    
          </div>      
          <?php } ?>    
        </div>      
        <div class="bnslider-arrows container-fluid"><div></div></div>      
        <div class="bnslider-dots container-fluid"></div>      
      </div>      
    </div>      
  </div>      
  <!-- //Main Slider -->      

  <div class="holder">        
    <div class="container">        
      <div class="page-title text-center">        
        <h1><?= urldecode(ucfirst($namakategori)) ?></h1>    
        <h3><?= urldecode(ucfirst($namasubkategori)) ?></h3>        
      </div>        
      <div class="prd-description px-2 text-justify mb-4">
        <p><?= urldecode(ucfirst($descen)) ?></p>
      </div>
      
      <?php if (!empty($gambar_kategori)) : ?>
        <!-- ✅ OUR PROCESS -->
        <div class="page-title text-center">
          <h1>Our Process</h1>
        </div>
        <div class="mb-4">
          <div class="row justify-content-center g-3">
            <?php foreach ($gambar_kategori as $index => $gk) : ?>
              <div class="col-8 col-md-4 col-lg-4">
                <div class="ratio ratio-1x1 overflow-hidden rounded-3 shadow-sm hover-zoom mb-2">
                  <img 
                    src="<?= base_url('asset/images/categories/'.$gk->gambar_kategori) ?>" 
                    alt="Category Image" 
                    class="img-fluid w-100 h-100 object-fit-cover gallery-thumb" 
                    data-index="<?= $index ?>">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ✅ LIGHTBOX FULLSCREEN -->
      <div id="lightboxModal" class="lightbox-modal">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-content">
          <img id="lightboxImage" src="" alt="Lightbox" class="img-fluid">
          <a class="lightbox-prev" onclick="changeSlide(-1)">&#10094;</a>
          <a class="lightbox-next" onclick="changeSlide(1)">&#10095;</a>
        </div>
      </div>

      <?php if (!empty(trim($specifications))) : ?>
        <!-- ✅ SPECIFICATIONS -->
        <div class="page-title text-center">
          <h1>Specifications</h1>
        </div>
        <div class="prd-description px-2 text-center">
          <p><?= urldecode(ucfirst($specifications)) ?></p>
        </div>
      <?php endif; ?>

      <!-- ✅ PRODUCTS SECTION -->
      <div class="holder py-4">      
        <div class="container">      

          <?php      
          $groupedProducts = [];    
          foreach ($product as $pd) {    
            $groupedProducts[$pd->nama_sub_kategori][] = $pd;    
          }    
          ?>    

          <?php foreach ($groupedProducts as $subcat => $items): ?>    
            <div class="product-container row g-4 mb-4">    
              <?php foreach ($items as $pd) { ?>      
              <div class="col product-item">      
                <div class="card border-0 shadow-sm h-100 overflow-hidden product-card">      
                  
                  <!-- ✅ Gambar Produk Menjadi Slideshow -->
                  <div class="position-relative product-img-wrap">
                    <div class="product-slideshow" id="slideshow-<?= $pd->id_product ?>">
                      <?php 
                        $hasImages = false;
                        foreach ($gambar_product as $gp) {
                          if ($gp->id_product == $pd->id_product) {
                            $hasImages = true;
                            echo '<div class="slide fade"><img src="'.base_url('asset/images/product/'.$gp->gambar).'" class="img-fluid w-100 product-image"></div>';
                          }
                        }
                        if (!$hasImages) {
                          echo '<div class="slide fade"><img src="'.base_url('asset/images/product/'.$pd->gambar).'" class="img-fluid w-100 product-image"></div>';
                        }
                      ?>
                      <div class="thumbnails d-flex justify-content-center mt-2">
                        <?php 
                        foreach ($gambar_product as $gp) {
                          if ($gp->id_product == $pd->id_product) {
                            echo '<img src="'.base_url('asset/images/product/'.$gp->gambar).'" class="thumb" onclick="setSlide('.$pd->id_product.', this)">';
                          }
                        }
                        ?>
                      </div>
                    </div>
                  </div>

                  <!-- ✅ Informasi Produk -->
                  <div class="card-body text-center d-flex flex-column justify-content-between">      
                    <div>      
                      <h6 class="fw-bold mb-1">      
                        <a href="https://api.whatsapp.com/send?phone=6281290004460&text=Selamat%20Datang%20di%20*FAYYFIR*%0A%0AAda%20yang%20ingin%20ditanyakan%3F%20atau%20di%20konfirmasi%3F%0A%0ATerimakasih%20telah%20mempercayai%20kami" class="text-dark text-decoration-none hover-text-success"><?= $pd->nama_product ?></a>      
                      </h6>      
                      <div class="text-muted small mb-2"><?= $pd->desc_en ?></div>
                      <div class="row justify-content-between g-3 col-6  col-md-4 col-lg-4 text-left">
                        <div class="text-muted small mb-2"><?= $pd->size_pro ?></div>
                        <div class="text-muted small mb-2"><?= $pd->pack_pro ?></div>
                        <div class="text-muted small mb-2"><?= $pd->ship_pro ?></div>
                      </div>
                    </div>      
                  </div>      
                </div>      
              </div>      
              <?php } ?>      
            </div>    
          <?php endforeach; ?>    

        </div>      
      </div>     
      
      <?php if (!empty($gambar_kategori_proses2)) : ?>
        <!-- ✅ OUR BRAND -->
        <div class="page-title text-center">
          <h1>Our Brand</h1>
        </div>
        <div class="mb-4">
          <div class="row justify-content-center g-3">
            <?php foreach ($gambar_kategori_proses2 as $index => $gk2) : ?>
              <div class="col-8 col-md-4 col-lg-4">
                <div class="ratio ratio-1x1 overflow-hidden rounded-3 shadow-sm hover-zoom mb-2">
                  <img 
                    src="<?= base_url('asset/images/categories/'.$gk2->gambar_kategori_proses2) ?>" 
                    alt="Category Image" 
                    class="img-fluid w-100 h-100 object-fit-cover gallery-thumb" 
                    data-index="<?= $index ?>">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ✅ LIGHTBOX FULLSCREEN -->
      <div id="lightboxModal" class="lightbox-modal">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-content">
          <img id="lightboxImage" src="" alt="Lightbox" class="img-fluid">
          <a class="lightbox-prev" onclick="changeSlide(-1)">&#10094;</a>
          <a class="lightbox-next" onclick="changeSlide(1)">&#10095;</a>
        </div>
      </div>

      <!-- ✅ STYLES -->
      <style>
      .product-card{transition:transform .25s ease,box-shadow .25s ease}.product-card:hover{transform:translateY(-6px);box-shadow:0 .8rem 1.5rem rgba(0,0,0,.08)}.product-image{transition:transform .4s ease;object-fit:cover;height:auto}.product-card:hover .product-image{transform:scale(1.05)}.text-truncate-3{overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical}.text-success2{color:#FBBF23!important}.hover-text-success:hover{color:#FBBF23!important}
      .product-container{display:grid;grid-template-columns:repeat(1,1fr);gap:1rem;padding-inline:.5rem}
      @media(min-width:768px){.product-container{display:flex;flex-direction:column;gap:1.75rem;padding-inline:0}.product-item{flex:0 0 100%;max-width:100%}.product-card{display:flex;flex-direction:row;align-items:center;gap:1.5rem;padding:.75rem;box-shadow:0 .2rem .6rem rgba(0,0,0,.05)}.product-img-wrap{flex:0 0 220px;max-width:220px}.product-image{height:180px;object-fit:cover}.card-body{text-align:left;padding:1rem}}
      .product-slideshow{position:relative;width:100%;overflow:hidden;border-radius:.5rem}
      .slide{display:none;position:absolute;top:0;left:0;width:100%;opacity:0;transition:opacity 1s ease}
      .slide.active{display:block;opacity:1;position:relative}
      .thumbnails img.thumb{width:40px;height:40px;object-fit:cover;border:2px solid transparent;border-radius:.25rem;cursor:pointer;margin:0 .2rem;opacity:.6;transition:.3s}
      .thumbnails img.thumb.active{border-color:#FBBF23;opacity:1}
      /* ✅ Gallery Kategori */
      .hover-zoom img{transition:transform .4s ease,box-shadow .4s ease}
      .hover-zoom:hover img{transform:scale(1.05);box-shadow:0 0.5rem 1rem rgba(0,0,0,.15)}
      .object-fit-cover{object-fit:cover}
      /* ✅ Lightbox */
      .lightbox-modal{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;overflow:hidden;background:rgba(0,0,0,.95);align-items:center;justify-content:center}
      .lightbox-modal.active{display:flex}
      .lightbox-content{position:relative;width:90%;max-width:900px}
      .lightbox-content img{width:100%;height:auto;border-radius:.5rem}
      .lightbox-close{position:absolute;top:30px;right:40px;color:#fff;font-size:40px;font-weight:bold;cursor:pointer;z-index:10000}
      .lightbox-prev,.lightbox-next{cursor:pointer;position:absolute;top:50%;width:auto;padding:16px;color:white;font-weight:bold;font-size:40px;user-select:none;transition:.3s}
      .lightbox-prev:hover,.lightbox-next:hover{color:#FBBF23}
      .lightbox-prev{left:0}.lightbox-next{right:0}
      </style>

      <!-- ✅ JAVASCRIPT -->
      <script>
      // Product slideshow
      const slideshows = {};
      document.addEventListener('DOMContentLoaded', ()=>{
        document.querySelectorAll('.product-slideshow').forEach((slideshow)=>{
          const slides = slideshow.querySelectorAll('.slide');
          if(slides.length === 0) return;
          let idx = 0;
          slides[0].classList.add('active');
          const thumbs = slideshow.querySelectorAll('.thumb');
          if(thumbs.length>0) thumbs[0].classList.add('active');
          const id = slideshow.id;
          slideshows[id] = {slides, thumbs, idx};
          setInterval(()=>{ nextSlide(id); },5000);
        });

        // Lightbox setup
        const galleryThumbs = document.querySelectorAll('.gallery-thumb');
        galleryThumbs.forEach((img, index)=>{
          img.addEventListener('click',()=>openLightbox(index));
        });
      });

      function nextSlide(id){
        const show = slideshows[id];
        if(!show) return;
        show.slides[show.idx].classList.remove('active');
        if(show.thumbs.length>0) show.thumbs[show.idx].classList.remove('active');
        show.idx = (show.idx+1)%show.slides.length;
        show.slides[show.idx].classList.add('active');
        if(show.thumbs.length>0) show.thumbs[show.idx].classList.add('active');
      }
      function setSlide(productId, el){
        const id = 'slideshow-'+productId;
        const show = slideshows[id];
        if(!show) return;
        const newIdx = Array.from(show.thumbs).indexOf(el);
        show.slides[show.idx].classList.remove('active');
        show.thumbs[show.idx].classList.remove('active');
        show.idx = newIdx;
        show.slides[show.idx].classList.add('active');
        show.thumbs[show.idx].classList.add('active');
      }

      // ✅ Lightbox
      let currentIndex = 0;
      const images = Array.from(document.querySelectorAll('.gallery-thumb')).map(img=>img.src);
      const modal = document.getElementById("lightboxModal");
      const lightboxImg = document.getElementById("lightboxImage");

      function openLightbox(index){
        currentIndex = index;
        modal.classList.add('active');
        lightboxImg.src = images[currentIndex];
      }
      function closeLightbox(){
        modal.classList.remove('active');
      }
      function changeSlide(dir){
        currentIndex = (currentIndex + dir + images.length) % images.length;
        lightboxImg.src = images[currentIndex];
      }
      document.addEventListener('keydown', function(e){
        if(e.key === "Escape") closeLightbox();
        if(e.key === "ArrowLeft") changeSlide(-1);
        if(e.key === "ArrowRight") changeSlide(1);
      });
      </script>

    </div>        
  </div>        
</div>