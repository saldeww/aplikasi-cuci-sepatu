<?php

// 1. Panggil data dari file eksternal
require_once 'data_layanan.php';

// 2. Opsional: Ambil 4 layanan pertama saja jika ingin menampilkan "Layanan Unggulan" di Home
$layanan_unggulan = array_slice($layanan_list, 0, 5);
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cuci Disini | Laundry Sepatu dan Barang Pribadi</title>
  <meta
    name="description"
    content="Layanan laundry dan perawatan sepatu serta barang pribadi cepat, bersih, dan profesional. " />
  <meta name="theme-color" content="#4F46E5" />
  <link rel="icon" type="image/x-icon" href="assets/img/logo background putih.jpeg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" />
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
      <a class="skip-link" href="#main">Skip to content</a>
      <!-- <form class="search" role="search" onsubmit="event.preventDefault()">
        <input
          type="text"
          placeholder="Cari layanan atau produk…"
          aria-label="Cari di toko" />
        <button type="submit" aria-label="Search">
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round">
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.3-4.3" />
          </svg>
        </button>
      </form> -->

      <div class="icon-row">
        <a href="login.php" class="icon-btn" aria-label="Account">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c0-4 4-7 8-7s8 3 8 7" />
          </svg>
        </a>
        <a href="#" class="icon-btn" aria-label="Wishlist">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round">
            <path
              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
          <span class="count">3</span>
        </a>
        <a
          href="pesanan.php"
          class="icon-btn icon-btn--cart"
          aria-label="Cart">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M6 2l-2 5v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5z" />
            <path d="M4 7h16" />
            <path d="M16 11a4 4 0 0 1-8 0" />
          </svg>
          <span class="count">2</span>
        </a>
        <button
          class="nav-toggle"
          aria-label="Open menu"
          aria-expanded="false">
          ≡
        </button>
      </div>
    </div>
  </header>

  <!-- Category Nav -->
  <nav class="nav-bar" aria-label="Primary">
    <div class="container">
      <a href="layanan.php" class="all-cats">
        <svg
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
        </svg>
        Semua Kategori
      </a>
      <div class="main-nav">
        <a href="index.php" aria-current="page">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="login.php">Login</a>
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
    <a href="login.php">Login</a>
    <a
      href="pesanan.php"
      class="btn btn--indigo"
      style="margin-top: var(--s5); justify-content: center">Lihat Pesanan →</a>
  </div>

  <main id="main">
    <!-- HERO Bento Grid -->
    <section class="hero">
      <div class="container">
        <div class="bento">
          <article class="bento-card bento-card--lg">
            <div class="sparkle"></div>
            <div>
              <span class="eyebrow">⚡ About Us &middot; Cuci Disini</span>
              <h2>Kenapa cuci disini?</h2>
              <p>
                Layanan cuci dan perawatan sepatu profesional dengan hasil
                maksimal dan harga terjangkau.
              </p>
              <a href="layanan.php" class="btn btn--paper">Pesan Sekarang
                <svg
                  width="14"
                  height="10"
                  viewBox="0 0 14 10"
                  fill="none"
                  aria-hidden="true">
                  <path
                    d="M1 5h12m0 0L9 1m4 4L9 9"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </a>
            </div>
            <img
              class="product"
              src="assets/img/foto lokasi.jpeg"
              alt="lokasi cuci disini"
              width="300" />
          </article>

          <article class="bento-card bento-card--purple">
            <div class="sparkle"></div>
            <span class="eyebrow">Perawatan Spesial</span>
            <h3 style="font-size: var(--text-xl); line-height: 1.15">
              Deep Clean<br />Shoes & Bags
            </h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg
                width="12"
                height="10"
                viewBox="0 0 14 10"
                fill="none"
                aria-hidden="true">
                <path
                  d="M1 5h12m0 0L9 1m4 4L9 9"
                  stroke="currentColor"
                  stroke-width="1.6"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </a>
            <img
              class="product"
              src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80&auto=format&fit=crop"
              alt="Layanan Perawatan" />
          </article>

          <article class="bento-card bento-card--teal">
            <div class="sparkle"></div>
            <span class="eyebrow">Layanan Cepat</span>
            <h3 style="font-size: var(--text-xl); line-height: 1.15">
              Fast Clean<br />Express Service
            </h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg
                width="12"
                height="10"
                viewBox="0 0 14 10"
                fill="none"
                aria-hidden="true">
                <path
                  d="M1 5h12m0 0L9 1m4 4L9 9"
                  stroke="currentColor"
                  stroke-width="1.6"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </a>
            <img
              class="product"
              src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&q=80&auto=format&fit=crop"
              alt="Fast Clean" />
          </article>

          <div class="bento-row">
            <article class="bento-card bento-card--orange">
              <div class="sparkle"></div>
              <span class="eyebrow">Repaint</span>
              <h3 style="font-size: var(--text-lg); line-height: 1.2">
                Restorasi Warna<br />Sepatu Favorit
              </h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg
                  width="12"
                  height="10"
                  viewBox="0 0 14 10"
                  fill="none"
                  aria-hidden="true">
                  <path
                    d="M1 5h12m0 0L9 1m4 4L9 9"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </a>
              <img
                class="product"
                src="https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?w=500&q=80&auto=format&fit=crop"
                alt="Repaint Sepatu" />
            </article>

            <article class="bento-card bento-card--green">
              <div class="sparkle"></div>
              <span class="eyebrow">Unyellowing</span>
              <h3 style="font-size: var(--text-lg); line-height: 1.2">
                Memutihkan Sole<br />Sepatu Kuning
              </h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg
                  width="12"
                  height="10"
                  viewBox="0 0 14 10"
                  fill="none"
                  aria-hidden="true">
                  <path
                    d="M1 5h12m0 0L9 1m4 4L9 9"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </a>
              <img
                class="product"
                src="https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=500&q=80&auto=format&fit=crop"
                alt="Unyellowing" />
            </article>

            <article class="bento-card bento-card--black">
              <div class="sparkle"></div>
              <span class="eyebrow">Premium</span>
              <h3 style="font-size: var(--text-lg); line-height: 1.2">
                Full Treatment<br />All Material
              </h3>
              <a href="layanan.php" class="shop-now">Pesan Sekarang
                <svg
                  width="12"
                  height="10"
                  viewBox="0 0 14 10"
                  fill="none"
                  aria-hidden="true">
                  <path
                    d="M1 5h12m0 0L9 1m4 4L9 9"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </a>
              <img
                class="product"
                src="https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=500&q=80&auto=format&fit=crop"
                alt="Premium Treatment" />
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- LAYANAN SECTION -->
    <section class="section" style="padding-top: var(--s5)">
      <div class="container">
        <div class="section-head">
          <h2>Layanan</h2>
          <a href="layanan.php" class="view-all">Lihat Semua
            <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
              <path
                d="M1 5h12m0 0L9 1m4 4L9 9"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
          </a>
        </div>

        <!-- Dynamic Products List -->
        <div class="products">
          <?php foreach ($layanan_unggulan as $item): ?>
            <article class="product-card">
              <div class="img-wrap">
                <?php if (!empty($item['badge'])): ?>
                  <span class="badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                <?php endif; ?>
                <button class="wishlist" aria-label="Wishlist">♡</button>
                <img
                  src="<?= htmlspecialchars($item['gambar']) ?>"
                  alt="<?= htmlspecialchars($item['nama']) ?>" />
              </div>

              <a href="layanan.php" class="name"><?= htmlspecialchars($item['nama']) ?></a>
              <div class="price">
                <span class="now"><?= htmlspecialchars($item['harga']) ?></span>
              </div>

              <a href="pesanan.php" class="btn">Pesan Sekarang →</a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- DISCOUNT BANNERS -->
    <section class="section" style="padding-top: 0">
      <div class="container">
        <div class="discount-row">
          <article class="discount-card discount-card--watch">
            <span class="meta">PROMO MINGGU INI</span>
            <h3>
              Pengantaran Gratis<br /><span class="pct">Sekitaran Sukabumi</span>
            </h3>
            <a href="layanan.php" class="shop-now">Pesan Sekarang
              <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
                <path
                  d="M1 5h12m0 0L9 1m4 4L9 9"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </a>
          </article>

          <article class="discount-card discount-card--airpods">
            <span class="meta">Ayo jadi bagian dari club kami!</span>
            <h3>cuci disini club<br /><span class="pct">FREE</span></h3>
            <a href="login.php" class="login-now">Login Sekarang
              <svg width="14" height="10" viewBox="0 0 14 10" fill="none">
                <path
                  d="M1 5h12m0 0L9 1m4 4L9 9"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </a>
          </article>
        </div>
      </div>
    </section>

    <!-- NEWSLETTER -->
    <section style="background: var(--paper)">
      <div class="container">
        <div class="newsletter">
          <div class="newsletter-grid">
            <div>
              <h2>
                <strong>Don't buy new shoes</strong>, just wash them! 🧼 —
                cuci disini.
              </h2>
              <p>
                Bersihkan sepatu kotormu disini. Cepat, bersih, wangi, dan
                ramah di kantong. Rawat sepatumu sekarang sebelum nodanya
                menetap!
              </p>
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
          <p>Cuci tas sepatu semuanya disini, Sejak 2025</p>
        </div>
        <div class="footer-col">
          <h4>Bantuan</h4>
          <ul>
            <li><a href="#">Akun Saya</a></li>
            <li><a href="pesanan.php">Pesanan Saya</a></li>
            <li><a href="#">Kebijakan Pengiriman</a></li>
            <li><a href="#">Pusat Bantuan</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Tentang Kami</h4>
          <ul>
            <li><a href="#">Tentang Cuci Disini</a></li>
            <li><a href="kontak.php">Kontak</a></li>
            <li><a href="login.php">Login / Register</a></li>
            <li><a href="#">Syarat &amp; Ketentuan</a></li>
            <li><a href="#">Kebijakan Privasi</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <?= date('Y'); ?> Cuci Disini. All rights reserved.</span>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js" defer></script>
</body>

</html>