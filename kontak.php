<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once 'config/database.php';
require_once 'functions/helper.php';

$is_logged_in = !empty($_SESSION['user_id']);
$user_id      = $is_logged_in ? (int) $_SESSION['user_id'] : 0;

$cart_count = 0;
if ($is_logged_in) {
    $stmt_cart = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE user_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $cart_count = (int) $stmt_cart->get_result()->fetch_row()[0];
    $stmt_cart->close();
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kontak | Cuci Disini</title>
    <meta name="description" content="Hubungi Cuci Disini — laundry sepatu Sukabumi. WhatsApp, alamat outlet, jam operasional, dan form feedback." />
    <meta name="theme-color" content="#4F46E5" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <style>
        .kontak-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            padding: 40px 0 70px;
        }

        .kontak-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 28px 30px;
        }

        .kontak-card h2 {
            font-size: 19px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 20px;
        }

        .info-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eef2ff;
            color: #4f46e5;
            flex-shrink: 0;
        }

        .info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.5;
        }

        .info-value a {
            color: #4f46e5;
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        .jam-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 4px;
        }

        .jam-table td {
            padding: 5px 0;
            color: #374151;
        }

        .jam-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .jam-table tr.today td {
            color: #4f46e5;
            font-weight: 700;
        }

        .wa-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 14px 18px;
            margin-top: 8px;
            background: #25d366;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            justify-content: center;
        }

        .wa-btn:hover {
            background: #1ebe5d;
        }

        .feedback-cta {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 14px 18px;
            margin-top: 12px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            justify-content: center;
        }

        .feedback-cta:hover {
            background: #3730a3;
        }

        .map-embed {
            border-radius: 14px;
            overflow: hidden;
            margin-top: 20px;
            border: 1px solid #e5e7eb;
        }

        .map-embed iframe {
            display: block;
            width: 100%;
            height: 220px;
            border: 0;
        }

        @media (max-width: 760px) {
            .kontak-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="index.php">Home</a>
                <a href="layanan.php">Layanan</a>
                <a href="pesanan.php">Pesanan</a>
                <a href="kontak.php" aria-current="page">Kontak</a>
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

        <section class="page-head">
            <div class="container">
                <div class="crumbs">
                    <a href="index.php">Home</a>
                    <span class="sep">›</span>
                    <span>Kontak</span>
                </div>
                <h1>Hubungi Kami</h1>
                <p>Ada pertanyaan atau ingin pesan layanan? Kami siap membantu.</p>
            </div>
        </section>

        <div class="container">
            <div class="kontak-grid">

                <!-- KARTU INFORMASI KONTAK -->
                <div class="kontak-card">
                    <h2>Informasi Kontak</h2>

                    <!-- WhatsApp -->
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.36 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.78a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.73 16z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">WhatsApp</div>
                            <div class="info-value">
                                <a href="https://wa.me/6281234567890" target="_blank" rel="noopener">+62 812-3456-7890</a><br>
                                <span style="font-size:12px;color:#6b7280;font-weight:400">Chat langsung untuk info harga & pemesanan</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Alamat Outlet</div>
                            <div class="info-value">
                                Jl. Contoh No. 1, Sukabumi<br>
                                Kota Sukabumi, Jawa Barat 43100
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <a href="mailto:halo@cucidisini.id">halo@cucidisini.id</a>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Operasional -->
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div style="flex:1">
                            <div class="info-label">Jam Operasional</div>
                            <?php
                            $hari_ini = (int) date('N'); // 1=Senin … 7=Minggu
                            $jadwal = [
                                ['Senin – Jumat', '08.00 – 17.00', [1,2,3,4,5]],
                                ['Sabtu',          '08.00 – 15.00', [6]],
                                ['Minggu',         'Tutup',         [7]],
                            ];
                            ?>
                            <table class="jam-table">
                                <?php foreach ($jadwal as [$label, $jam, $days]): ?>
                                    <tr<?= in_array($hari_ini, $days, true) ? ' class="today"' : '' ?>>
                                        <td><?= $label ?></td>
                                        <td><?= $jam ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- CTA WhatsApp -->
                    <a class="wa-btn" href="https://wa.me/6281234567890?text=Halo%20Cuci%20Disini%2C%20saya%20ingin%20tanya%20tentang%20layanan" target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                        </svg>
                        Chat via WhatsApp
                    </a>

                </div>

                <!-- KARTU FEEDBACK & PETA -->
                <div>
                    <div class="kontak-card">
                        <h2>Kirim Feedback</h2>
                        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin-bottom:16px">
                            Punya saran, kritik, atau menemukan masalah?
                            Kami selalu terbuka untuk masukan demi pelayanan yang lebih baik.
                        </p>
                        <a href="feedback.php" class="feedback-cta">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            Beri Feedback Sekarang →
                        </a>
                    </div>

                    <!-- Peta -->
                    <div class="kontak-card" style="margin-top:20px">
                        <h2>Lokasi Outlet</h2>
                        <p style="color:#6b7280;font-size:13px;margin-bottom:0">Jl. Contoh No. 1, Sukabumi, Jawa Barat</p>
                        <div class="map-embed">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63392.52!2d106.9272!3d-6.9232!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68a3a5cf0b9fa1%3A0x4c14f81f68e6d0b6!2sKota+Sukabumi%2C+Jawa+Barat!5e0!3m2!1sid!2sid!4v1"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Lokasi Cuci Disini">
                            </iframe>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
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

<?php mysqli_close($conn); ?>
