<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
  header("Location: index");
  exit();
}

// Ambil data pembelian
$stmt = $conn->prepare("SELECT p.*, bm.nama_bahan FROM bb_pembelian_awal p JOIN bb_bahan_master bm ON p.id_bahan = bm.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
  header("Location: index");
  exit();
}

$total = $data["total_modal"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status_pembayaran = $_POST["status_pembayaran"];
    // Hapus titik ribuan sebelum dikonversi ke float
    $nominal_raw = str_replace('.', '', $_POST["nominal_bayar"]);
    $nominal_tambahan = floatval($nominal_raw);
    
    // Logika Akumulasi:
    // Jika Lunas, paksa nominal_bayar = total_modal
    // Jika Belum Dibayar, nominal_bayar = 0
    // Jika DP atau Belum Lunas, nominal_bayar = lama + baru
    if ($status_pembayaran === 'lunas') {
        $nominal_final = $total;
    } elseif ($status_pembayaran === 'belum_dibayar') {
        $nominal_final = 0;
    } else {
        $nominal_final = floatval($data['nominal_bayar']) + $nominal_tambahan;
        
        // Otomatis ubah ke lunas jika nominal sudah mencukupi
        if ($nominal_final >= $total) {
            $status_pembayaran = 'lunas';
            $nominal_final = $total;
        }
    }

    $stmt = $conn->prepare("UPDATE bb_pembelian_awal SET status_pembayaran = ?, nominal_bayar = ? WHERE id = ?");
    $stmt->bind_param("sdi", $status_pembayaran, $nominal_final, $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('✅ Status pembayaran berhasil diperbarui!'); window.location.href='detail-pembelian.php?id=$id';</script>";
        exit();
    } else {
        $error = "Gagal memperbarui data: " . $conn->error;
    }
}

$activeMenu = "purchases";
$activeModule = "Update Pembayaran";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-6 sm:p-8">
  <div class="max-w-xl mx-auto">
    <a href="detail-pembelian.php?id=<?= $id ?>" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 mb-6 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      Kembali ke Detail
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 bg-gray-800 text-yellow-400">
            <h2 class="text-xl font-bold">Update Status Pembayaran</h2>
            <p class="text-sm text-gray-300"><?= htmlspecialchars($data['kode_batch']) ?> - <?= htmlspecialchars($data['nama_bahan']) ?></p>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Total Tagihan</p>
                    <p class="text-lg font-bold text-gray-900">Rp <?= number_format($total, 0, ',', '.') ?></p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-500">Sudah Dibayar</p>
                    <p class="text-lg font-bold text-blue-900">Rp <?= number_format($data['nominal_bayar'], 0, ',', '.') ?></p>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider">Pilih Status Baru</label>
                <div class="grid grid-cols-1 gap-3">
                    <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition <?= $data['status_pembayaran'] == 'belum_dibayar' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                        <input type="radio" name="status_pembayaran" value="belum_dibayar" class="hidden" <?= $data['status_pembayaran'] == 'belum_dibayar' ? 'checked' : '' ?>>
                        <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center <?= $data['status_pembayaran'] == 'belum_dibayar' ? 'border-blue-500' : 'border-gray-300' ?>">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 <?= $data['status_pembayaran'] == 'belum_dibayar' ? '' : 'hidden' ?>"></div>
                        </div>
                        <span class="font-medium">Belum Dibayar</span>
                    </label>

                    <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition <?= $data['status_pembayaran'] == 'dp' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                        <input type="radio" name="status_pembayaran" value="dp" class="hidden" <?= $data['status_pembayaran'] == 'dp' ? 'checked' : '' ?>>
                        <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center <?= $data['status_pembayaran'] == 'dp' ? 'border-blue-500' : 'border-gray-300' ?>">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 <?= $data['status_pembayaran'] == 'dp' ? '' : 'hidden' ?>"></div>
                        </div>
                        <span class="font-medium">DP (Uang Muka)</span>
                    </label>

                    <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition <?= $data['status_pembayaran'] == 'belum_lunas' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                        <input type="radio" name="status_pembayaran" value="belum_lunas" class="hidden" <?= $data['status_pembayaran'] == 'belum_lunas' ? 'checked' : '' ?>>
                        <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center <?= $data['status_pembayaran'] == 'belum_lunas' ? 'border-blue-500' : 'border-gray-300' ?>">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 <?= $data['status_pembayaran'] == 'belum_lunas' ? '' : 'hidden' ?>"></div>
                        </div>
                        <span class="font-medium">Belum Lunas (Ada Kekurangan)</span>
                    </label>

                    <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition <?= $data['status_pembayaran'] == 'lunas' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                        <input type="radio" name="status_pembayaran" value="lunas" class="hidden" <?= $data['status_pembayaran'] == 'lunas' ? 'checked' : '' ?>>
                        <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center <?= $data['status_pembayaran'] == 'lunas' ? 'border-blue-500' : 'border-gray-300' ?>">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 <?= $data['status_pembayaran'] == 'lunas' ? '' : 'hidden' ?>"></div>
                        </div>
                        <span class="font-medium">Lunas</span>
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <?php 
                $sudah_bayar = (float)$data['nominal_bayar'];
                $sisa_tagihan = $total - $sudah_bayar;
                ?>
                <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider">Nominal yang Dibayarkan (Tambahan) (Rp)</label>
                <input type="text" name="nominal_bayar" id="nominal_bayar" 
                    value="<?= $sisa_tagihan > 0 ? number_format($sisa_tagihan, 0, ',', '.') : '0' ?>" 
                    placeholder="Masukkan jumlah pembayaran baru..." required
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-lg font-bold focus:ring-2 focus:ring-blue-400 outline-none transition">
                <p class="text-xs text-gray-400 italic">
                    * Sisa tagihan saat ini: <span class="font-bold text-red-500">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></span>. 
                    Input di atas akan <strong>ditambahkan</strong> ke total yang sudah dibayar.
                </p>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition duration-200 transform hover:-translate-y-0.5">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="status_pembayaran"]');
    const nominalInput = document.getElementById('nominal_bayar');
    const totalTagihan = <?= $total ?>;

    radios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            // Visual feedback for labels
            document.querySelectorAll('label.border-blue-500').forEach(l => {
                l.classList.remove('border-blue-500', 'bg-blue-50');
                l.classList.add('border-gray-200');
                const radioIconWrapper = l.querySelector('.w-5.h-5');
                if (radioIconWrapper) {
                    radioIconWrapper.classList.replace('border-blue-500', 'border-gray-300');
                    radioIconWrapper.querySelector('.bg-blue-500').classList.add('hidden');
                }
            });
            
            const label = e.target.closest('label');
            label.classList.replace('border-gray-200', 'border-blue-500');
            label.classList.add('bg-blue-50');
            const newIconWrapper = label.querySelector('.w-5.h-5');
            if (newIconWrapper) {
                newIconWrapper.classList.replace('border-gray-300', 'border-blue-500');
                newIconWrapper.querySelector('.bg-blue-500').classList.remove('hidden');
            }

            // Business logic
            if (radio.value === 'lunas') {
                nominalInput.value = totalTagihan.toLocaleString('id-ID');
                nominalInput.readOnly = true;
                nominalInput.classList.add('bg-gray-50');
            } else if (radio.value === 'belum_dibayar') {
                nominalInput.value = '0';
                nominalInput.readOnly = true;
                nominalInput.classList.add('bg-gray-50');
            } else if (radio.value === 'belum_lunas' || radio.value === 'dp') {
                // Pre-fill dengan sisa pembayaran sebagai saran pelunasan
                const sudahBayar = <?= (float)$data['nominal_bayar'] ?>;
                const sisa = totalTagihan - sudahBayar;
                nominalInput.value = sisa > 0 ? sisa.toLocaleString('id-ID') : '0';
                nominalInput.readOnly = false;
                nominalInput.classList.remove('bg-gray-50');
                nominalInput.focus();
            } else {
                nominalInput.readOnly = false;
                nominalInput.classList.remove('bg-gray-50');
                nominalInput.focus();
            }
        });
    });

    // Thousand Separator Logic
    nominalInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\./g, '');
        if (!isNaN(value) && value.length > 0) {
            this.value = parseInt(value).toLocaleString('id-ID');
        } else {
            this.value = '';
        }
    });

    // Initial state check
    const checked = document.querySelector('input[name="status_pembayaran"]:checked');
    if (checked && (checked.value === 'lunas' || checked.value === 'belum_dibayar')) {
        nominalInput.readOnly = true;
        nominalInput.classList.add('bg-gray-50');
    }
});
</script>

<?php include "../partials/footer.php"; ?>