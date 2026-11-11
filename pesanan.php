<?php
// Memuat data layanan dari file eksternal (jika ada) atau definisi array lokal
if (file_exists('data_layanan.php')) {
  require_once 'data_layanan.php';
} else {
  // Fallback data jika data_layanan.php belum dipanggil
  $layanan_list = [
    ["nama" => "Deep Cleaning", "harga_min" => 30000, "waktu" => "Pengerjaan 1-7 hari"],
    ["nama" => "Bag Treatment", "harga_min" => 25000, "waktu" => "Pengerjaan 1-7 hari"],
    ["nama" => "Helm Treatment", "harga_min" => 35000, "waktu" => "Pengerjaan 1-7 hari"],
    ["nama" => "Sandals Treatment", "harga_min" => 25000, "waktu" => "Pengerjaan 1-7 hari"],
    ["nama" => "Reglue", "harga_min" => 40000, "waktu" => "Pengerjaan 7 hari"],
    ["nama" => "Shoe Repair", "harga_min" => 50000, "waktu" => "Pengerjaan 7 hari"]
  ];
}

// Opsi durasi pengerjaan beserta biaya tambahan
$opsi_hari = [
  ["label" => "Reguler (3-7 Hari)", "biaya" => 0],
  ["label" => "Express (2 Hari) - Tambah Rp 15.000", "biaya" => 15000],
  ["label" => "Kilat (1 Hari / Sameday) - Tambah Rp 30.000", "biaya" => 30000]
];

// Penanganan submit form
$success_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama        = isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '';
  $layanan_sel = isset($_POST['layanan']) ? htmlspecialchars($_POST['layanan']) : '';
  $total       = isset($_POST['total_harga_input']) ? htmlspecialchars($_POST['total_harga_input']) : '0';

  $success_message = "Pesanan layanan <strong>$layanan_sel</strong> atas nama <strong>$nama</strong> berhasil dibuat! Total estimasi: <strong>Rp " . number_format((int)$total, 0, ',', '.') . "</strong>.";
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pesan Layanan | Cuci Disini</title>
  <meta
    name="description"
    content="Form pemesanan online layanan perawatan sepatu, tas, helm, dan aksesoris di Cuci Disini." />
  <meta name="theme-color" content="#4F46E5" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" />
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>

<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a>
      <button class="nav-toggle" aria-label="Open menu">≡</button>
    </div>
  </header>

  <nav class="nav-bar" aria-label="Primary">
    <div class="container">
      <a href="layanan.php" class="all-cats"><svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.4"
          stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>Semua Kategori</a>
      <div class="main-nav">
        <a href="index.php">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesan.php" aria-current="page">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="login.php">Login</a>
      </div>
    </div>
  </nav>

  <div class="drawer" id="drawer" aria-hidden="true">
    <div class="drawer-head">
      <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a><button class="drawer-close">Close ✕</button>
    </div>
    <a href="index.php">Home</a>
    <a href="layanan.php">Layanan</a>
    <a href="pesan.php">Pesanan</a>
    <a href="kontak.php">Kontak</a>
  </div>

  <main id="main">
    <section class="page-head">
      <div class="container">
        <div class="crumbs">
          <a href="index.php">Home</a> <span class="sep">›</span>
          <span>Form Pemesanan</span>
        </div>
      </div>
    </section>

    <section>
      <div class="container">
        <div class="contact-grid">
          <div class="contact-info">
            <div
              style="
                  margin-top: var(--s7);
                  padding: var(--s6);
                  background: linear-gradient(
                    135deg,
                    var(--indigo),
                    var(--card-purple)
                  );
                  color: var(--paper);
                  border-radius: var(--r-lg);
                  position: relative;
                  overflow: hidden;
                ">
              <div
                style="
                    position: absolute;
                    inset: 0;
                    background-image: radial-gradient(
                      circle at 80% 20%,
                      rgba(255, 255, 255, 0.18) 0,
                      transparent 40%
                    );
                    pointer-events: none;
                  "></div>
              <div style="position: relative">
                <h3
                  style="
                      color: var(--paper);
                      font-size: var(--text-xl);
                      margin-bottom: var(--s3);
                    ">
                  Layanan Antar-Jemput Gratis
                </h3>
                <p
                  style="
                      color: rgba(255, 255, 255, 0.85);
                      font-size: var(--text-sm);
                      line-height: 1.6;
                      margin-bottom: var(--s4);
                    ">
                  Isi form di samping dan kurir kami akan menjemput sepatu, tas, atau helm Anda langsung ke lokasi.
                </p>
                <a href="kontak.php" class="btn btn--paper">Tanya CS via Whatsapp →</a>
              </div>
            </div>
          </div>

          <form class="contact-form" action="pesan.php" method="POST" id="orderForm">
            <h2 style="font-size: var(--text-xl); margin-bottom: var(--s2)">
              Form Pemesanan Layanan
            </h2>
            <p
              style="
                  color: var(--fg-soft);
                  font-size: var(--text-sm);
                  margin-bottom: var(--s5);
                ">
              Pilih jenis perawatan, estimasi hari, dan masukkan data penjemputan Anda.
            </p>

            <?php if (!empty($success_message)): ?>
              <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 16px; font-size: 14px;">
                <?php echo $success_message; ?>
              </div>
            <?php endif; ?>

            <div class="field-row">
              <div class="field">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input id="nama_lengkap" name="nama_lengkap" type="text" required placeholder="Masukkan nama lengkap..." />
              </div>
              <div class="field">
                <label for="phone">Nomor WhatsApp</label>
                <input id="phone" name="phone" type="tel" required placeholder="0812xxxxxxxx" />
              </div>
            </div>

            <div class="field-row">
              <div class="field">
                <label for="layanan">Pilih Jenis Layanan</label>
                <select id="layanan" name="layanan" required style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color, #ccc);">
                  <option value="" data-harga="0">-- Pilih Layanan --</option>
                  <?php foreach ($layanan_list as $item):
                    $harga_dasar = isset($item['harga_min']) ? $item['harga_min'] : 30000;
                  ?>
                    <option value="<?php echo htmlspecialchars($item['nama']); ?>" data-harga="<?php echo $harga_dasar; ?>">
                      <?php echo htmlspecialchars($item['nama']); ?> (Mulai Rp <?php echo number_format($harga_dasar, 0, ',', '.'); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="field">
                <label for="durasi_hari">Estimasi Waktu Pengerjaan</label>
                <select id="durasi_hari" name="durasi_hari" required style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color, #ccc);">
                  <?php foreach ($opsi_hari as $opsi): ?>
                    <option value="<?php echo htmlspecialchars($opsi['label']); ?>" data-biaya="<?php echo $opsi['biaya']; ?>">
                      <?php echo htmlspecialchars($opsi['label']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="field">
              <label for="jumlah">Jumlah Pasang / Unit</label>
              <input id="jumlah" name="jumlah" type="number" min="1" value="1" required />
            </div>

            <div class="field">
              <label for="alamat">Alamat Penjemputan & Pengantaran</label>
              <textarea
                id="alamat"
                name="alamat"
                required
                placeholder="Tuliskan alamat lengkap lokasi penjemputan barang..."></textarea>
            </div>

            <!-- Rincian Hitung Total Otomatis -->
            <div style="background: var(--bg-soft, #f8fafc); padding: 16px; border-radius: 8px; margin-top: 16px; border: 1px solid #e2e8f0;">
              <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: var(--text-base); color: var(--indigo);">
                <span>Estimasi Total Biaya:</span>
                <span id="display_total">Rp 0</span>
              </div>
              <input type="hidden" name="total_harga_input" id="total_harga_input" value="0" />
            </div>

            <button
              class="btn btn--indigo btn--block"
              type="submit"
              style="
                  padding: 16px;
                  font-size: var(--text-base);
                  margin-top: var(--s4);
                ">
              Kirim Pesanan Sekarang →
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="mark">
            <span class="brand-mark">C</span> Cuci Disini
          </div>
          <p>Cuci tas sepatu semuanya disini, Sejak 2025</p>
        </div>
        <div class="footer-col">
          <h4>Bantuan</h4>
          <ul>
            <li><a href="#">Akun Saya</a></li>
            <li><a href="pesan.php">Pesanan Saya</a></li>
            <li><a href="#">Pusat Bantuan</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Tentang Kami</h4>
          <ul>
            <li><a href="#">Tentang Kami</a></li>
            <li><a href="kontak.php">Kontak</a></li>
            <li><a href="login.php">Login / Register</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <?php echo date('Y'); ?> Cuci Disini. All rights reserved.</span>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js" defer></script>

  <!-- Script JavaScript untuk Perhitungan Otomatis -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const selectLayanan = document.getElementById('layanan');
      const selectDurasi = document.getElementById('durasi_hari');
      const inputJumlah = document.getElementById('jumlah');
      const displayTotal = document.getElementById('display_total');
      const inputTotalHidden = document.getElementById('total_harga_input');

      function hitungTotal() {
        const optLayanan = selectLayanan.options[selectLayanan.selectedIndex];
        const optDurasi = selectDurasi.options[selectDurasi.selectedIndex];

        const hargaLayanan = parseFloat(optLayanan.getAttribute('data-harga')) || 0;
        const biayaExpress = parseFloat(optDurasi.getAttribute('data-biaya')) || 0;
        const jumlah = parseInt(inputJumlah.value) || 1;

        const total = (hargaLayanan + biayaExpress) * jumlah;

        displayTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
        inputTotalHidden.value = total;
      }

      selectLayanan.addEventListener('change', hitungTotal);
      selectDurasi.addEventListener('change', hitungTotal);
      inputJumlah.addEventListener('input', hitungTotal);
    });
  </script>
</body>

</html>