<?php
// Memanggil data layanan dari file eksternal
require_once 'data_layanan.php';
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Layanan | Cuci Disini</title>
    <meta
      name="description"
      content="Daftar layanan perawatan dan pencucian sepatu, tas, helm, dan aksesoris di Cuci Disini."
    />
    <meta name="theme-color" content="#4F46E5" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap"
    />
    <link rel="stylesheet" href="assets/css/styles.css" />
  </head>

  <body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
      <div class="container">
        <a href="index.php" class="brand"
          ><span class="brand-mark">C</span> Cuci Disini</a
        >
        <form class="search" role="search" onsubmit="event.preventDefault()">
          <input type="text" placeholder="Cari layanan perawatan..." />
          <button type="submit" aria-label="Search">
            <svg
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.2"
              stroke-linecap="round"
            >
              <circle cx="11" cy="11" r="7" />
              <path d="M21 21l-4.3-4.3" />
            </svg>
          </button>
        </form>
        <div class="icon-row">
          <a href="#" class="icon-btn" aria-label="Account"
            ><svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            >
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21c0-4 4-7 8-7s8 3 8 7" /></svg
          ></a>
          <a
            href="pesanan.php"
            class="icon-btn icon-btn--cart"
            aria-label="Cart"
            ><svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M6 2l-2 5v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5z" />
              <path d="M4 7h16" />
              <path d="M16 11a4 4 0 0 1-8 0" /></svg
            ><span class="count">2</span></a
          >
          <button class="nav-toggle" aria-label="Open menu">≡</button>
        </div>
      </div>
    </header>

    <nav class="nav-bar" aria-label="Primary">
      <div class="container">
        <a href="layanan.php" class="all-cats"
          ><svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linecap="round"
          >
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" /></svg
          >Semua Kategori</a
        >
        <div class="main-nav">
          <a href="index.php">Home</a>
          <a href="layanan.php" aria-current="page">Layanan</a>
          <a href="pesanan.php">Pesanan</a>
          <a href="kontak.php">Kontak</a>
          <a href="tracking.php">Tracking Pesanan</a>
          <a href="login.php">Login</a>
        </div>
      </div>
    </nav>

    <div class="drawer" id="drawer" aria-hidden="true">
      <div class="drawer-head">
        <a href="index.php" class="brand"
          ><span class="brand-mark">C</span> Cuci Disini</a
        ><button class="drawer-close">Close ✕</button>
      </div>
      <a href="index.php">Home</a>
      <a href="layanan.php">Layanan</a>
      <a href="pesanan.php">Pesanan</a>
      <a href="kontak.php">Kontak</a>
    </div>

    <main id="main">
      <section class="page-head">
        <div class="container">
          <div class="crumbs">
            <a href="index.php">Home</a> <span class="sep">›</span>
            <span>Layanan</span>
          </div>
          <h1>Semua Layanan</h1>
          <p>
            <?php echo count($layanan_list); ?>
            Layanan yang tersedia di Cuci Disini
          </p>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <div class="shop-layout">
            <aside class="filters" aria-label="Filters">
              <div class="filter-block">
                <h3>Kategori</h3>
                <label
                  ><input type="checkbox" checked /> Shoes
                  <span class="ct">76</span></label
                >
                <label
                  ><input type="checkbox" /> Bags
                  <span class="ct">42</span></label
                >
                <label
                  ><input type="checkbox" /> Helmet
                  <span class="ct">28</span></label
                >
                <label
                  ><input type="checkbox" /> Apparel
                  <span class="ct">35</span></label
                >
              </div>
              <div class="filter-block">
                <h3>Rentang Harga</h3>
                <div
                  style="
                    display: flex;
                    justify-content: space-between;
                    font-family: var(--ff-mono);
                    font-size: 11px;
                    color: var(--fg-mute);
                  "
                >
                  <span>Rp 20.000</span><span>Rp 150.000</span>
                </div>
                <div class="range-bar" aria-hidden="true"></div>
              </div>
              <a href="#" class="btn btn--indigo btn--block">Terapkan Filter</a>
            </aside>

            <div>
              <div class="shop-toolbar">
                <span class="count"
                  >Menampilkan 1 – <?php echo count($layanan_list); ?></span
                >
                <div style="display: flex; gap: var(--s3); align-items: center">
                  <span
                    style="
                      font-size: var(--text-xs);
                      color: var(--fg-mute);
                      font-family: var(--ff-mono);
                    "
                    >URUTKAN</span
                  >
                  <select>
                    <option>Paling Populer</option>
                    <option>Harga: Rendah ke Tinggi</option>
                    <option>Harga: Tinggi ke Rendah</option>
                  </select>
                </div>
              </div>

              <div class="shop-grid">
                <?php foreach ($layanan_list as $layanan): ?>
                <article class="product-card">
                  <div class="img-wrap">
                    <?php if (!empty($layanan['badge'])): ?>
                    <span class="badge"
                      ><?php echo htmlspecialchars($layanan['badge']); ?></span
                    >
                    <?php endif; ?>
                    <button class="wishlist">♡</button>
                    <img
                      src="<?php echo htmlspecialchars($layanan['gambar']); ?>"
                      alt="<?php echo htmlspecialchars($layanan['nama']); ?>"
                    />
                  </div>
                  <div class="stock">
                    <span class="dot"></span
                    ><?php echo htmlspecialchars($layanan['waktu']); ?>
                  </div>
                  <a href="product.php" class="name"
                    ><?php echo htmlspecialchars($layanan['nama']); ?></a
                  >
                  <div class="price">
                    <span class="now"
                      ><?php echo htmlspecialchars($layanan['harga']); ?></span
                    >
                  </div>
                  <a href="pesanan.php" class="btn">Pesan Sekarang →</a>
                </article>
                <?php endforeach; ?>
              </div>
            </div>
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
              <li><a href="pesanan.php">Pesanan Saya</a></li>
              <li><a href="#">Kebijakan Pengiriman</a></li>
              <li><a href="#">Pusat Bantuan</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Tentang Kami</h4>
            <ul>
              <li><a href="#">Tentang Kami</a></li>
              <li><a href="kontak.php">Kontak</a></li>
              <li><a href="login.php">Login / Register</a></li>
              <li><a href="#">Syarat &amp; Ketentuan</a></li>
              <li><a href="#">Kebijakan Privasi</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span
            >© <?php echo date('Y'); ?> Cuci Disini. All rights reserved.</span
          >
        </div>
      </div>
    </footer>

    <script src="assets/js/main.js" defer></script>
  </body>
</html>