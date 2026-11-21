<?php
session_start();

require_once 'config/database.php';
require_once 'functions/helper.php';

// Buat token CSRF jika belum tersedia
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$sort_by = $_GET['sort'] ?? 'popular';
$keyword = trim($_GET['q'] ?? '');
$category_id = isset($_GET['category'])
  ? (int) $_GET['category']
  : 0;

// Whitelist sorting untuk mencegah SQL injection
$sort_options = [
  'popular'    => " (l.badge IS NOT NULL AND l.badge <> '') DESC, l.id DESC ",
  'price_low'  => " l.harga ASC ",
  'price_high' => " l.harga DESC "
];

if (!array_key_exists($sort_by, $sort_options)) {
  $sort_by = 'popular';
}

$order_by = $sort_options[$sort_by];


// ==========================================
// FUNGSI FORMAT RUPIAH
// ==========================================

// function formatRupiah($harga)
// {
//   return 'Rp ' . number_format((float) $harga, 0, ',', '.');
// }


// ==========================================
// AMBIL DATA KATEGORI
// ==========================================

$category_sql = "
    SELECT
        c.id,
        c.nama,
        COUNT(l.id) AS jumlah
    FROM category c
    LEFT JOIN layanan l
        ON l.category_id = c.id
        AND l.status = 'Aktif'
    GROUP BY c.id, c.nama
    ORDER BY c.nama ASC
";

$category_query = mysqli_query($conn, $category_sql);

if (!$category_query) {
  die("Gagal mengambil kategori: " . mysqli_error($conn));
}

$category_list = mysqli_fetch_all($category_query, MYSQLI_ASSOC);


// ==========================================
// AMBIL DATA LAYANAN
// ==========================================

$sql = "
    SELECT
        l.*,
        c.nama AS nama_kategori
    FROM layanan l
    INNER JOIN category c
        ON l.category_id = c.id
    WHERE
        l.status = 'Aktif'
        AND (? = 0 OR l.category_id = ?)
        AND (? = '' OR l.nama LIKE CONCAT('%', ?, '%'))
    ORDER BY $order_by
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
  die("Query layanan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
  $stmt,
  "iiss",
  $category_id,
  $category_id,
  $keyword,
  $keyword
);

if (!mysqli_stmt_execute($stmt)) {
  die("Gagal mengambil layanan: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);

$layanan_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_stmt_close($stmt);


// ==========================================
// JUMLAH LAYANAN
// ==========================================

$total_layanan = count($layanan_list);

?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Layanan | Cuci Disini</title>

  <meta
    name="description"
    content="Daftar layanan perawatan dan pencucian sepatu, tas, helm, dan aksesoris di Cuci Disini.">

  <meta name="theme-color" content="#4F46E5">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap">

  <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>


  <!-- Header -->
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <span class="brand-mark">C</span>
        cuci disini
      </a>

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
          href="keranjang.php"
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


  <button
    class="nav-toggle"
    type="button"
    aria-label="Buka menu">
    ≡
  </button>

  </div>
  </div>
  </header>


  <!-- ======================================
         NAVIGATION
    ======================================= -->

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

          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>

        Semua Kategori

      </a>

      <div class="main-nav">
        <a href="index.php">Home</a>
        <a href="layanan.php" aria-current="page">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="login.php">Login</a>
      </div>

    </div>

  </nav>


  <!-- ======================================
         MOBILE DRAWER
    ======================================= -->

  <div class="drawer" id="drawer" aria-hidden="true">

    <div class="drawer-head">

      <a href="index.php" class="brand">
        <span class="brand-mark">C</span>
        Cuci Disini
      </a>

      <button class="drawer-close" type="button">
        Close ✕
      </button>

    </div>

    <a href="index.php">Home</a>
    <a href="layanan.php">Layanan</a>
    <a href="pesanan.php">Pesanan</a>
    <a href="kontak.php">Kontak</a>
    <a href="login.php">Login</a>

  </div>


  <!-- ======================================
         MAIN CONTENT
    ======================================= -->

  <main id="main">

    <!-- Page Heading -->

    <section class="page-head">

      <div class="container">

        <div class="crumbs">
          <a href="index.php">Home</a>
          <span class="sep">›</span>
          <span>Layanan</span>
        </div>

        <h1>Semua Layanan</h1>

        <p>
          <?php echo $total_layanan; ?>
          layanan ditemukan di Cuci Disini
        </p>

      </div>

    </section>


    <!-- ======================================
             SHOP SECTION
        ======================================= -->

    <section class="section">

      <div class="container">

        <div class="shop-layout">


          <aside class="filters" aria-label="Filter layanan">

            <div class="filter-block">

              <h3>Kategori</h3>

              <!-- Semua kategori -->

              <a
                href="layanan.php?sort=<?php echo urlencode($sort_by); ?>&q=<?php echo urlencode($keyword); ?>"
                class="category-link <?php echo ($category_id === 0) ? 'active' : ''; ?>">

                Semua Kategori

              </a>
              <?php foreach ($category_list as $category): ?>

                <a
                  href="layanan.php?category=<?php echo (int) $category['id']; ?>&sort=<?php echo urlencode($sort_by); ?>&q=<?php echo urlencode($keyword); ?>"
                  class="category-link <?php echo ($category_id === (int) $category['id']) ? 'active' : ''; ?>">

                  <?php echo e($category['nama']); ?>

                  <span class="ct">
                    <?php echo (int) $category['jumlah']; ?>
                  </span>

                </a>

              <?php endforeach; ?>

            </div>

          </aside>


          <!-- PRODUCT CONTENT -->

          <div>
            <!-- TOOLBAR -->

            <!-- TOOLBAR -->
            <div class="shop-toolbar">

              <form
                class="search"
                role="search"
                action="layanan.php"
                method="GET">

                <input
                  type="text"
                  name="q"
                  placeholder="Cari layanan perawatan..."
                  value="<?php echo e($keyword); ?>">

                <input type="hidden" name="sort"
                  value="<?php echo e($sort_by); ?>">

                <input type="hidden" name="category"
                  value="<?php echo $category_id; ?>">

                <button type="submit" aria-label="Cari layanan">
                  <svg width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor"
                    stroke-width="2.2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M21 21l-4.3-4.3"></path>
                  </svg>
                </button>
              </form>

              <!-- SORTING GROUP -->
              <div style="
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    ">

                <span style="
            font-size: var(--text-xs);
            color: var(--fg-mute);
            font-family: var(--ff-mono);
            white-space: nowrap;
        ">
                  URUTKAN
                </span>

                <form action="layanan.php" method="GET">

                  <input type="hidden" name="category"
                    value="<?php echo $category_id; ?>">

                  <input type="hidden" name="q"
                    value="<?php echo e($keyword); ?>">

                  <select
                    name="sort"
                    onchange="this.form.submit()"
                    aria-label="Urutkan layanan">

                    <option value="popular"
                      <?php echo ($sort_by === 'popular') ? 'selected' : ''; ?>>
                      Unggulan
                    </option>

                    <option value="price_low"
                      <?php echo ($sort_by === 'price_low') ? 'selected' : ''; ?>>
                      Harga: Rendah ke Tinggi
                    </option>

                    <option value="price_high"
                      <?php echo ($sort_by === 'price_high') ? 'selected' : ''; ?>>
                      Harga: Tinggi ke Rendah
                    </option>

                  </select>
                </form>

              </div>

            </div>



            <!-- PRODUCT GRID -->

            <div class="shop-grid">

              <?php if (!empty($layanan_list)): ?>

                <?php foreach ($layanan_list as $layanan): ?>

                  <article class="product-card">

                    <!-- Product Image -->

                    <div class="img-wrap">

                      <?php if (!empty($layanan['badge'])): ?>

                        <span class="badge">
                          <?php echo e($layanan['badge']); ?>
                        </span>

                      <?php endif; ?>

                      <img
                        src="<?php echo e($layanan['gambar']); ?>"
                        alt="<?php echo e($layanan['nama']); ?>"
                        loading="lazy">

                    </div>


                    <!-- Service Duration -->

                    <div class="stock">

                      <span class="dot"></span>

                      <?php echo e($layanan['waktu']); ?>

                    </div>


                    <!-- Service Name -->

                    <a
                      href="product.php?id=<?php echo (int) $layanan['id']; ?>"
                      class="name">

                      <?php echo e($layanan['nama']); ?>

                    </a>


                    <!-- Service Price -->

                    <div class="price">

                      <span class="now">

                        <?php echo formatRupiah($layanan['harga']); ?>

                      </span>

                    </div>


                    <!-- Order Button -->


                    <form action="keranjang.php" method="POST">
                      <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                      <input type="hidden" name="action" value="add">

                      <input type="hidden" name="layanan_id"
                        value="<?= (int) $layanan['id'] ?>">

                      <input type="hidden" name="jumlah" value="1">

                      <button type="submit" class="btn">
                        <span>Tambah ke Keranjang</span>
                        <span aria-hidden="true">＋</span>
                      </button>
                    </form>


                  </article>

                <?php endforeach; ?>

              <?php else: ?>

                <!-- EMPTY STATE -->

                <div class="empty-state">

                  <h3>Layanan Tidak Ditemukan</h3>

                  <p>
                    Maaf, layanan yang kamu cari belum tersedia.
                    Coba gunakan kata kunci atau kategori lain.
                  </p>

                  <a href="layanan.php" class="btn">
                    Lihat Semua Layanan
                  </a>

                </div>

              <?php endif; ?>

            </div>

          </div>

        </div>

      </div>

    </section>

  </main>


  <!-- ======================================
         FOOTER
    ======================================= -->

  <footer class="site-footer">

    <div class="container">

      <div class="footer-grid">

        <div class="footer-brand">

          <div class="mark">
            <span class="brand-mark">C</span>
            Cuci Disini
          </div>

          <p>
            Cuci tas sepatu semuanya di sini, Sejak 2025
          </p>

        </div>


        <div class="footer-col">

          <h4>Bantuan</h4>

          <ul>
            <li><a href="login.php">Akun Saya</a></li>
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

        <span>
          © <?php echo date('Y'); ?> Cuci Disini. All rights reserved.
        </span>

      </div>

    </div>

  </footer>


  <script src="assets/js/main.js" defer></script>

</body>

</html>

<?php
mysqli_close($conn);
?>