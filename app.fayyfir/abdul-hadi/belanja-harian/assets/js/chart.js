/* ============================================================================
   Chart.js Loader & Utility
   File  : chart.js
   Role  : Memastikan Chart.js siap pakai di semua halaman
   ============================================================================ */

/**
 * Load Chart.js apabila belum tersedia (fallback loader)
 * Ini mencegah error "Chart is not defined" jika CDN gagal atau belum dipasang.
 */

(function loadChartJS() {
    if (typeof Chart === "undefined") {
        console.warn("Chart.js belum terdeteksi. Memuat fallback CDN…");

        const script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js";
        script.onload = () => console.log("Chart.js berhasil dimuat melalui fallback CDN.");
        script.onerror = () => console.error("Gagal memuat Chart.js CDN fallback.");
        document.head.appendChild(script);
    } else {
        console.log("Chart.js sudah tersedia.");
    }
})();

/* ============================================================================
   Default Global Config
   Berfungsi membuat tampilan chart lebih konsisten secara UX.
   ============================================================================ */

document.addEventListener("DOMContentLoaded", function () {
    if (typeof Chart === "undefined") return;

    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = "#374151";
    Chart.defaults.plugins.tooltip.backgroundColor = "rgba(17, 24, 39, 0.9)";
    Chart.defaults.plugins.tooltip.titleColor = "#fff";
    Chart.defaults.plugins.tooltip.bodyColor = "#f3f4f6";
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
});

/* ============================================================================
   Helper untuk membuat chart responsif dengan padding & animasi lembut
   Jika nanti ada chart baru, cukup panggil:
       createChart(ctx, config)
   ============================================================================ */

function createChart(ctx, config) {
    if (!ctx) {
        console.error("Context canvas Chart.js tidak ditemukan.");
        return null;
    }

    return new Chart(ctx, {
        ...config,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,
                easing: "easeOutQuart"
            },
            ...config.options
        }
    });
}

/* ============================================================================
   Ready digunakan oleh semua halaman, termasuk penyusutan-tahap.php
   ============================================================================ */

console.log("chart.js global utilities loaded.");