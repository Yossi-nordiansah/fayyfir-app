<div class="page-content">
  <div class="holder breadcrumbs-wrap mt-0">
    <div class="container">
      <ul class="breadcrumbs">
        <li><a href="<?= base_url() ?>">Home</a></li>
        <li><span>Gallery</span></li>
      </ul>
    </div>
  </div>

  <div class="holder mt-0">
    <div class="container">
      <div class="page-title text-center mb-4">
        <h1 class="fw-bold mb-2">Gallery</h1>
        <p class="text-muted">Temukan koleksi foto terbaik kami yang menampilkan produk, momen, dan aktivitas spesial.</p>
      </div>

      <!-- ✅ Responsive Grid -->
      <div class="gallery-grid">
        <?php foreach ($gallery as $index => $gl): ?>
          <div class="gallery-item-wrapper">
            <div class="gallery-item position-relative overflow-hidden rounded-4 shadow-sm">
              <img 
                src="<?= base_url('asset/images/gallery/' . $gl->gambar_gallery) ?>" 
                alt="<?= $gl->nama_gambar ?>" 
                class="gallery-thumb img-fluid"
                onclick="openLightbox(<?= $index ?>)"
              >
              <div class="gallery-overlay d-flex align-items-center justify-content-center">
                <div class="text-white text-center small fw-semibold">
                  <?= $gl->nama_gambar ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- 🔍 Lightbox Popup -->
<div id="lightboxModal" class="lightbox-modal">
  <span class="close-btn" onclick="closeLightbox()">&times;</span>

  <div class="lightbox-content">
    <img id="lightboxImage" class="lightbox-img" src="">
    <div id="lightboxCaption" class="lightbox-caption mt-3"></div>

    <!-- ✅ Thumbnail Bar -->
    <div id="thumbnailBar" class="thumbnail-bar mt-4">
      <?php foreach ($gallery as $index => $thumb): ?>
        <img 
          src="<?= base_url('asset/images/gallery/' . $thumb->gambar_gallery) ?>" 
          alt="<?= $thumb->nama_gambar ?>" 
          class="thumbnail-item"
          onclick="openLightbox(<?= $index ?>)"
        >
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ✅ Custom CSS -->
<style>
/* --- Grid Layout --- */
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.2rem;
}

/* --- Gallery Item --- */
.gallery-item {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.gallery-item:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.gallery-thumb {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  transition: transform 0.5s ease;
}
.gallery-item:hover .gallery-thumb {
  transform: scale(1.06);
}

/* Overlay hover effect */
.gallery-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none !important;
}
.gallery-item:hover .gallery-overlay {
  opacity: 1;
}

/* --- Lightbox --- */
.lightbox-modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.92);
  z-index: 2000;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  overflow-y: auto;
  padding: 40px 0;
}
.lightbox-content {
  max-width: 90%;
  text-align: center;
  position: relative;
}
.lightbox-img {
  max-width: 100%;
  max-height: 90vh;
  border-radius: 0.8rem;
  object-fit: contain;
  box-shadow: 0 0 20px rgba(255,255,255,0.25);
}
.lightbox-caption {
  color: #eee;
  font-size: 1rem;
  margin-top: 0.8rem;
}

/* --- Close Button --- */
.close-btn {
  position: fixed;
  top: 20px; right: 35px;
  font-size: 2rem;
  color: white;
  cursor: pointer;
  z-index: 2060;
  transition: color 0.3s ease;
}
.close-btn:hover { color: #FBBF23; }

/* --- Thumbnail Bar --- */
.thumbnail-bar {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  justify-content: left;
  gap: 10px;
  margin-top: 20px;
  padding-bottom: 10px;
  scrollbar-width: thin;
}
.thumbnail-item {
  height: 70px;
  width: 100px;
  object-fit: cover;
  border-radius: 6px;
  opacity: 0.5;
  transition: opacity 0.3s, transform 0.3s;
  cursor: pointer;
}
.thumbnail-item:hover,
.thumbnail-item.active {
  opacity: 1;
  transform: scale(1.05);
  box-shadow: 0 0 8px rgba(255,255,255,0.5);
}

/* Responsive */
@media (max-width: 768px) {
  .nav-arrow { font-size: 1.8rem; padding: 10px; }
  .thumbnail-item { height: 50px; width: 70px; }
}
</style>

<!-- ✅ Lightbox Script (dengan fullscreen saat klik) -->
<script>
let currentSlide = 0;
let isFullscreen = false; // untuk mencegah fullscreen aktif berulang
const galleryItems = <?= json_encode($gallery) ?>;
const baseUrl = "<?= base_url('asset/images/gallery/') ?>";

function openLightbox(index) {
  currentSlide = index;
  const modal = document.getElementById("lightboxModal");
  const img = document.getElementById("lightboxImage");
  const caption = document.getElementById("lightboxCaption");

  img.src = baseUrl + galleryItems[index].gambar_gallery;
  caption.textContent = galleryItems[index].nama_gambar || '';

  // Update thumbnail aktif
  document.querySelectorAll(".thumbnail-item").forEach((thumb, i) => {
    thumb.classList.toggle("active", i === index);
  });

  modal.style.display = "flex";
  document.body.style.overflow = "hidden";

  // ✅ Aktifkan fullscreen hanya saat pertama kali klik (bukan saat slide berikutnya)
  if (!isFullscreen) {
    enterFullscreen(modal);
    isFullscreen = true;
  }
}

function enterFullscreen(element) {
  if (element.requestFullscreen) {
    element.requestFullscreen().catch(err => console.log("Fullscreen gagal:", err));
  } else if (element.webkitRequestFullscreen) { // Safari
    element.webkitRequestFullscreen();
  } else if (element.msRequestFullscreen) { // IE/Edge
    element.msRequestFullscreen();
  }
}

function exitFullscreen() {
  if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.webkitExitFullscreen) { // Safari
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) { // IE/Edge
      document.msExitFullscreen();
    }
  }
}

function closeLightbox() {
  const modal = document.getElementById("lightboxModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto";
  isFullscreen = false; // reset agar klik foto berikutnya bisa fullscreen lagi

  // ✅ Keluar fullscreen saat modal ditutup
  exitFullscreen();
}

function changeSlide(direction) {
  currentSlide += direction;
  if (currentSlide < 0) currentSlide = galleryItems.length - 1;
  if (currentSlide >= galleryItems.length) currentSlide = 0;
  
  // Ganti gambar tanpa masuk fullscreen lagi
  const img = document.getElementById("lightboxImage");
  const caption = document.getElementById("lightboxCaption");
  img.src = baseUrl + galleryItems[currentSlide].gambar_gallery;
  caption.textContent = galleryItems[currentSlide].nama_gambar || '';

  // Update thumbnail aktif
  document.querySelectorAll(".thumbnail-item").forEach((thumb, i) => {
    thumb.classList.toggle("active", i === currentSlide);
  });
}
</script>