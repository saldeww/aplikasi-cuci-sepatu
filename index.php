<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once 'config/database.php';
require_once 'functions/helper.php';

$is_logged_in = !empty($_SESSION['user_id']);
$user_id      = $is_logged_in ? (int) $_SESSION['user_id'] : 0;

// Jumlah item di keranjang (hanya jika login)
$cart_count = 0;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

$layanan_unggulan = array_slice($layanan_list, 0, 5);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cuci Disini | Laundry Sepatu dan Barang Pribadi</title>
  <meta name="description" content="Layanan laundry dan perawatan sepatu serta barang pribadi cepat, bersih, dan profesional." />
  <meta name="theme-color" content="#4F46E5" />
  <link rel="icon" type="image/x-icon" href="assets/img/logo background putih.jpeg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" />
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>

<body>

  <!-- Header -->
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <span class="brand-mark">C</span>
        cuci disini
      </a>

      <div class="icon-row">
        <?php if ($is_logged_in): ?>
          <a href="profile.php" class="icon-btn" aria-label="Profil Saya">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21c0-4 4-7 8-7s8 3 8 7" />
            </svg>
          </a>
        <?php else: ?>
          <a href="login.php" class="icon-btn" aria-label="Login">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
              <polyline points="10 17 15 12 10 7"/>
              <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
          </a>
        <?php endif; ?>

        <a href="keranjang.php" class="icon-btn icon-btn--cart" aria-label="Keranjang">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2l-2 5v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5z" />
            <path d="M4 7h16" />
            <path d="M16 11a4 4 0 0 1-8 0" />
          </svg>
          <?php if ($cart_count > 0): ?>
            <span class="count"><?= $cart_count ?></span>
          <?php endif; ?>
        </a>

        <button class="nav-toggle" aria-label="Open menu" aria-expanded="false">≡</button>
      </div>
    </div>
  </header>

  <!-- Category Nav -->
  <nav class="nav-bar" aria-label="Primary">
    <div class="container">
      <a href="layanan.php" class="all-cats">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
        Semua Layanan
      </a>
      <div class="main-nav">
        <a href="index.php" aria-current="page">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="feedback.php">Feedback</a>
        <?php if (!$is_logged_in): ?>
          <a href="login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Mobile Drawer -->
  <div class="drawer" id="drawer" aria-hidden="true">
    <div class="drawer-head">
      <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a>
      <button class="drawer-close" aria-label="Close menu">Close ✕</button>
    </div>
    <a href="index.php">Home</a>
    <a href="layanan.php">Layanan</a>
    <a href="pesanan.php">Pesanan</a>
    <a href="kontak.php">Kontak</a>
    <a href="feedback.php">Feedback</a>
    <?php if ($is_logged_in): ?>
      <a href="profile.php">Profil Saya</a>
      <a href="logout.php">Keluar</a>
    <?php else: ?>
      <a href="login.php" class="btn btn--indigo" style="margin-top:var(--s5);justify-content:center">Login / Daftar →</a>
    <?php endif; ?>
  </div>

  <main id="main">

    <!-- HERO Bento Grid -->
    <section class="hero">
      <div class="container">
        <div class="bento">

          <article class="bento-card bento-card--lg">
            <div class="sparkle"></div>
            <div>
              <span class="eyebrow">⚡ Tentang Kami &middot; Cuci Disini</span>
              <h2>Kenapa cuci disini?</h2>
              <p>Layanan cuci dan perawatan sepatu profesional dengan hasil maksimal dan harga terjangkau. Antar jemput ke seluruh Sukabumi.</p>
              <a href="layanan.php" class="btn btn--paper">Pesan Sekarang
                <svg width="14" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                  <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </a>
            </div>
            <img class="product" src="assets/img/foto lokasi.jpeg" alt="Lokasi Cuci Disini" width="300" />
          </article>

          <article class="bento-card bento-card--purple">
            <div class="sparkle"></div>
            <span class="eyebrow">Perawatan Spesial</span>
            <h3 style="font-size:var(--text-xl);line-height:1.15">Deep Clean<br />Shoes &amp; Bags</h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg width="12" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </a>
            <img class="product" src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80&auto=format&fit=crop" alt="Layanan Perawatan" />
          </article>

          <article class="bento-card bento-card--teal">
            <div class="sparkle"></div>
            <span class="eyebrow">Layanan Cepat</span>
            <h3 style="font-size:var(--text-xl);line-height:1.15">Fast Clean<br />Express Service</h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg width="12" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </a>
          </article>

          <div class="bento-row">
            <article class="bento-card bento-card--orange">
              <div class="sparkle"></div>
              <span class="eyebrow">Repaint</span>
              <h3 style="font-size:var(--text-lg);line-height:1.2">Restorasi Warna<br />Sepatu Favorit</h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg width="12" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                  <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </a>
              <img class="product" src="https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?w=500&q=80&auto=format&fit=crop" alt="Repaint Sepatu" />
            </article>

            <article class="bento-card bento-card--green">
              <div class="sparkle"></div>
              <span class="eyebrow">Unyellowing</span>
              <h3 style="font-size:var(--text-lg);line-height:1.2">Memutihkan Sole<br />Sepatu Kuning</h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg width="12" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                  <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </a>
              <img class="product" src="https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=500&q=80&auto=format&fit=crop" alt="Unyellowing" />
            </article>

            <article class="bento-card bento-card--black">
              <div class="sparkle"></div>
              <span class="eyebrow">Premium</span>
              <h3 style="font-size:var(--text-lg);line-height:1.2">Full Treatment<br />All Material</h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg width="12" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true">
                  <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </a>
              <img class="product" src="https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=500&q=80&auto=format&fit=crop" alt="Premium Treatment" />
            </article>
          </div>

        </div>
      </div>
    </section>

    <!-- LAYANAN UNGGULAN -->
    <section class="section" style="padding-top:var(--s5)">
      <div class="container">
        <div class="section-head">
          <h2>Layanan</h2>
          <a href="layanan.php" class="view-all">Lihat Semua
            <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
              <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </a>
        </div>

        <div class="products">
          <?php if (empty($layanan_unggulan)): ?>
            <p style="color:#6b7280;">Belum ada layanan tersedia.</p>
          <?php else: ?>
            <?php foreach ($layanan_unggulan as $item): ?>
              <article class="product-card">
                <div class="img-wrap">
                  <img src="<?= e($item['gambar']) ?>" alt="<?= e($item['nama']) ?>" loading="lazy" />
                </div>
                <a href="layanan.php" class="name"><?= e($item['nama']) ?></a>
                <div class="price">
                  <span class="now"><?= formatRupiah($item['harga']) ?></span>
                </div>
                <a href="layanan.php" class="btn">Pesan Sekarang →</a>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- PROMO BANNER -->
    <section class="section" style="padding-top:0">
      <div class="container">
        <div class="discount-row">
          <article class="discount-card discount-card--watch">
            <span class="meta">PROMO MINGGU INI</span>
            <h3>Pengantaran Gratis<br /><span class="pct">Sekitaran Sukabumi</span></h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
                <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </a>
          </article>

          <article class="discount-card discount-card--airpods">
            <span class="meta">Yuk jadi bagian dari club kami!</span>
            <h3>Cuci Disini Club<br /><span class="pct">FREE</span></h3>
            <?php if ($is_logged_in): ?>
              <a href="pesanan.php" class="login-now">Lihat Pesanan →</a>
            <?php else: ?>
              <a href="register.php" class="login-now">Daftar Sekarang
                <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
                  <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </a>
            <?php endif; ?>
          </article>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section style="background:var(--paper)">
      <div class="container">
        <div class="newsletter">
          <div class="newsletter-grid">
            <div>
              <h2><strong>Don't buy new shoes</strong>, just wash them! 🧼 — cuci disini.</h2>
              <p>Bersihkan sepatu kotormu disini. Cepat, bersih, wangi, dan ramah di kantong. Rawat sepatumu sekarang sebelum nodanya menetap!</p>
              <a href="layanan.php" class="btn"><strong>Pesan Sekarang →</strong></a>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="mark">
            <span class="brand-mark">C</span> Cuci Disini
          </div>
          <p>Cuci sepatu, tas, dan barang pribadi. Sejak 2025.</p>
        </div>

        <div class="footer-col">
          <h4>Akun</h4>
          <ul>
            <?php if ($is_logged_in): ?>
              <li><a href="profile.php">Profil Saya</a></li>
              <li><a href="pesanan.php">Pesanan Saya</a></li>
              <li><a href="keranjang.php">Keranjang</a></li>
              <li><a href="logout.php">Keluar</a></li>
            <?php else: ?>
              <li><a href="login.php">Login</a></li>
              <li><a href="register.php">Daftar Akun</a></li>
              <li><a href="pesanan.php">Pesanan Saya</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Tentang Kami</h4>
          <ul>
            <li><a href="kontak.php">Kontak</a></li>
            <li><a href="feedback.php">Feedback &amp; Saran</a></li>
            <li><a href="layanan.php">Semua Layanan</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <span>© <?= date('Y') ?> Cuci Disini. All rights reserved.</span>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js" defer></script>
</body>

</html>
