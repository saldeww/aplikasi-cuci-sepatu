<?php
require_once 'config/database.php';

// ==========================================
// FILTER DAN SORTING
// ==========================================

$sort_by = $_GET['sort'] ?? 'popular';
$keyword = trim($_GET['q'] ?? '');
$category_id = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

// Whitelist sorting agar tidak bisa disisipi SQL
$sort_options = [
    'popular' => " (l.badge IS NOT NULL AND l.badge <> '') DESC, l.id DESC ",
    'price_low' => " l.harga ASC ",
    'price_high' => " l.harga DESC "
];

if (!isset($sort_options[$sort_by])) {
    $sort_by = 'popular';
}

$order_by = $sort_options[$sort_by];


// ==========================================
// AMBIL DATA CATEGORY
// ==========================================

$category_query = mysqli_query(
    $conn,
    "SELECT
        c.id,
        c.nama,
        COUNT(l.id) AS jumlah
     FROM category c
     LEFT JOIN layanan l
        ON c.id = l.category_id
        AND l.status = 'Aktif'
     GROUP BY c.id, c.nama
     ORDER BY c.nama ASC"
);

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

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$layanan_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_stmt_close($stmt);


// ==========================================
// FORMAT HARGA
// ==========================================

function formatRupiah($harga)
{
    return 'Rp ' . number_format(
        (float) $harga,
        0,
        ',',
        '.'
    );
}

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Layanan | Cuci Disini</title>
    <meta
        name="description"
        content="Daftar layanan perawatan dan pencucian sepatu, tas, helm, dan aksesoris di Cuci Disini." />
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
            <form class="search" role="search" action="layanan.php" method="GET">
                <input
                    type="text"
                    name="q"
                    placeholder="Cari layanan perawatan..."
                    value="<?php echo htmlspecialchars($keyword); ?>" />

                <input type="hidden" name="sort"
                    value="<?php echo htmlspecialchars($sort_by); ?>">

                <input type="hidden" name="category"
                    value="<?php echo $category_id; ?>">

                <button type="submit" aria-label="Search">
                    <!-- SVG search lama tetap di sini -->
                </button>
            </form>
            <input type="text" placeholder="Cari layanan perawatan..." />
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
            </form>
            <div class="icon-row">
                <a href="#" class="icon-btn" aria-label="Account"><svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21c0-4 4-7 8-7s8 3 8 7" />
                    </svg></a>
                <a
                    href="pesanan.php"
                    class="icon-btn icon-btn--cart"
                    aria-label="Cart"><svg
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
                    </svg><span class="count">2</span></a>
                <button class="nav-toggle" aria-label="Open menu">≡</button>
            </div>
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
            <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a><button class="drawer-close">Close ✕</button>
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

                            <a href="layanan.php?sort=<?php echo urlencode($sort_by); ?>"
                                class="category-link">
                                Semua Kategori
                            </a>

                            <?php foreach ($category_list as $category): ?>

                                <a
                                    href="layanan.php?category=<?php echo $category['id']; ?>&sort=<?php echo urlencode($sort_by); ?>&q=<?php echo urlencode($keyword); ?>"
                                    class="category-link">
                                    <?php echo htmlspecialchars($category['nama']); ?>

                                    <span class="ct">
                                        <?php echo $category['jumlah']; ?>
                                    </span>
                                </a>

                            <?php endforeach; ?>
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
                  ">
                                <span>Rp 20.000</span><span>Rp 150.000</span>
                            </div>
                            <div class="range-bar" aria-hidden="true"></div>
                        </div>
                        <a href="#" class="btn btn--indigo btn--block">Terapkan Filter</a>
                    </aside>

                    <div>
                        <div class="shop-toolbar">
                            <span class="count">Menampilkan 1 – <?php echo count($layanan_list); ?></span>
                            <div style="display: flex; gap: var(--s3); align-items: center">
                                <span
                                    style="
                      font-size: var(--text-xs);
                      color: var(--fg-mute);
                      font-family: var(--ff-mono);
                    ">URUTKAN</span>
                                <select onchange="location = this.value;">
                                    <option value="layanan.php?sort=popular" <?php echo ($sort_by === 'popular') ? 'selected' : ''; ?>>Paling Populer</option>
                                    <option value="layanan.php?sort=price_low" <?php echo ($sort_by === 'price_low') ? 'selected' : ''; ?>>Harga: Rendah ke Tinggi</option>
                                    <option value="layanan.php?sort=price_high" <?php echo ($sort_by === 'price_high') ? 'selected' : ''; ?>>Harga: Tinggi ke Rendah</option>
                                </select>
                            </div>
                        </div>

                        <div class="shop-grid">
                            <?php foreach ($layanan_list as $layanan): ?>
                                <article class="product-card">
                                    <div class="img-wrap">
                                        <?php if (!empty($layanan['badge'])): ?>
                                            <span class="badge"><?php echo htmlspecialchars($layanan['badge']); ?></span>
                                        <?php endif; ?>
                                        <button class="wishlist">♡</button>
                                        <img
                                            src="<?php echo htmlspecialchars($layanan['gambar']); ?>"
                                            alt="<?php echo htmlspecialchars($layanan['nama']); ?>" />
                                    </div>
                                    <div class="stock">
                                        <span class="dot"></span><?php echo htmlspecialchars($layanan['waktu']); ?>
                                    </div>
                                    <a href="product.php" class="name"><?php echo htmlspecialchars($layanan['nama']); ?></a>
                                    <div class="price">
                                        <span class="now"><?php echo htmlspecialchars($layanan['harga']); ?></span>
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
                <span>© <?php echo date('Y'); ?> Cuci Disini. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js" defer></script>
</body>

</html>

nah coba rapihin semuanya disini aku terima jadi aja :D