// ======== SIDEBAR TOGGLE (untuk layar mobile) ========

// Elemen-elemen utama
const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarClose = document.getElementById("sidebarClose");
const sidebarOverlay = document.getElementById("sidebarOverlay");

// Fungsi: buka sidebar
function openSidebar() {
  sidebar.classList.remove("-translate-x-full");
  sidebarOverlay.classList.remove("hidden");
}

// Fungsi: tutup sidebar
function closeSidebar() {
  sidebar.classList.add("-translate-x-full");
  sidebarOverlay.classList.add("hidden");
}

// Klik tombol menu (☰) di navbar → buka sidebar
if (sidebarToggle) {
  sidebarToggle.addEventListener("click", openSidebar);
}

// Klik tombol close (✕) di sidebar → tutup sidebar
if (sidebarClose) {
  sidebarClose.addEventListener("click", closeSidebar);
}

// Klik overlay hitam → tutup sidebar
if (sidebarOverlay) {
  sidebarOverlay.addEventListener("click", closeSidebar);
}

// ======== RESPONSIVE BEHAVIOR ========
// Saat lebar layar berubah, pastikan sidebar tampil otomatis di layar besar
window.addEventListener("resize", () => {
  if (window.innerWidth >= 1024) {
    sidebar.classList.remove("-translate-x-full");
    sidebarOverlay.classList.add("hidden");
  } else {
    sidebar.classList.add("-translate-x-full");
  }
});