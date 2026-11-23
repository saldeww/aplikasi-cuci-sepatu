<?php

session_start();
date_default_timezone_set('Asia/Jakarta');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database dan dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helper.php';
require_once __DIR__ . '/functions/keranjang_functions.php';
require_once __DIR__ . '/handlers/keranjang_handler.php';

// Autentikasi
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id      = (int) $_SESSION['user_id'];
$is_logged_in = true; // halaman ini hanya bisa diakses saat login

// Cart count untuk badge navbar
$cart_count = 0;
$stmt_cart = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE user_id = ?");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_count = (int) $stmt_cart->get_result()->fetch_row()[0];
$stmt_cart->close();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses POST sebelum mengambil data halaman
handleCartRequest($conn, $user_id);

// Ambil flash message
$pesan = $_SESSION['cart_message'] ?? '';
$error = $_SESSION['cart_error'] ?? '';

unset($_SESSION['cart_message'], $_SESSION['cart_error']);

// Ambil data keranjang
$cart = getCartItems($conn, $user_id);

$items = $cart['items'];
$total = $cart['total'];
$total_item = $cart['total_item'];
$cart_ready = $cart['cart_ready'];

// Ambil pilihan konfigurasi
$variants = getCartVariants($conn);
$sizes = getCartSizes($conn);
$addons = getCartAddons($conn);
$selected_addons = getSelectedCartAddons($conn, $user_id);

?>


<!-- HTML keranjang kamu mulai di sini -->

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Keranjang Saya | Cuci Disini</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/styles.css" />
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --danger: #dc2626;
            --success: #15803d;
            --radius: 17px;
        }

        .page-header {
            padding: 42px 0 27px;
        }

        .breadcrumb {
            margin-bottom: 14px;
            font-size: 12px;
            color: var(--muted);
        }

        .breadcrumb a {
            color: var(--primary);
        }

        .page-header h1 {
            font-size: clamp(27px, 4vw, 36px);
            font-weight: 800;
            letter-spacing: -1.2px;
        }

        .page-header p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
        }

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            align-items: start;
            gap: 23px;
            padding-bottom: 70px;
        }

        .cart-panel,
        .summary-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 21px 23px;
            border-bottom: 1px solid var(--border);
        }

        .panel-header h2,
        .summary-panel h2 {
            font-size: 17px;
            font-weight: 800;
        }

        .item-count {
            color: var(--muted);
            font-size: 12px;
        }

        .cart-item {
            padding: 23px;
            border-bottom: 1px solid var(--border);
        }

        .item-top {
            display: flex;
            gap: 17px;
            align-items: flex-start;
        }

        .cart-image {
            width: 108px;
            height: 108px;
            object-fit: cover;
            border-radius: 13px;
            background: var(--paper);
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .category {
            color: var(--primary);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .item-name {
            margin: 5px 0;
            font-size: 17px;
            font-weight: 800;
        }

        .item-time {
            color: var(--muted);
            font-size: 12px;
        }

        .unit-price {
            margin-top: 10px;
            font-size: 14px;
            font-weight: 800;
        }

        .config-area {
            margin-top: 22px;
            padding: 19px;
            background: #fafaff;
            border: 1px solid #ededf7;
            border-radius: 13px;
        }

        .config-title {
            margin-bottom: 15px;
            font-size: 13px;
            font-weight: 800;
        }

        .field {
            margin-bottom: 16px;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        .field label,
        .field-label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .field select,
        .field input,
        .field textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dedeea;
            border-radius: 9px;
            background: #fff;
            color: var(--dark);
            outline: none;
            font-size: 12px;
        }

        .field select:focus,
        .field input:focus,
        .field textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .09);
        }

        .field textarea {
            min-height: 75px;
            resize: vertical;
        }

        .addon-list {
            display: grid;
            gap: 9px;
        }

        .addon-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
        }

        .addon-option input {
            width: 16px;
            height: 16px;
            margin-top: 2px;
            accent-color: var(--primary);
        }

        .addon-details {
            flex: 1;
            font-size: 12px;
        }

        .addon-details strong {
            display: block;
            margin-bottom: 3px;
        }

        .addon-details small {
            color: var(--muted);
            display: block;
            line-height: 1.5;
        }

        .addon-price {
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .required-tag {
            color: var(--danger);
            font-size: 10px;
        }

        .config-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #fff;
        }

        .quantity button {
            width: 27px;
            height: 27px;
            border: 0;
            border-radius: 6px;
            background: var(--paper);
            cursor: pointer;
            font-weight: 800;
        }

        .quantity input {
            width: 35px;
            border: 0;
            outline: 0;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
        }

        .save-btn {
            border: 0;
            border-radius: 9px;
            padding: 11px 17px;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
        }

        .save-btn:hover {
            background: var(--primary-dark);
        }

        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }

        .subtotal-label {
            color: var(--muted);
            font-size: 11px;
        }

        .subtotal {
            font-size: 17px;
            font-weight: 800;
        }

        .remove-btn {
            border: 0;
            background: transparent;
            color: var(--danger);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .summary-panel {
            position: sticky;
            top: 20px;
            padding: 23px;
        }

        .summary-panel h2 {
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            color: var(--muted);
            font-size: 12px;
        }

        .summary-row strong {
            color: var(--dark);
        }

        .summary-total {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            font-size: 15px;
            font-weight: 800;
        }

        .summary-total strong {
            color: var(--primary);
            font-size: 18px;
        }

        .checkout-btn,
        .shop-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 23px;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .checkout-btn:hover,
        .shop-btn:hover {
            background: var(--primary-dark);
        }

        .checkout-btn.disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .continue-shopping {
            display: block;
            margin-top: 16px;
            text-align: center;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
        }

        .secure-note {
            margin-top: 20px;
            color: var(--muted);
            text-align: center;
            font-size: 10px;
            line-height: 1.6;
        }

        .empty-cart {
            padding: 65px 20px;
            text-align: center;
        }

        .empty-icon {
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 20px;
            background: #eeedff;
            font-size: 32px;
        }

        .empty-cart h2 {
            margin-bottom: 8px;
            font-size: 20px;
        }

        .empty-cart p {
            color: var(--muted);
            font-size: 12px;
        }

        .empty-cart .shop-btn {
            width: auto;
            display: inline-block;
            padding: 13px 22px;
        }

        .invalid-note {
            padding: 11px;
            margin-top: 12px;
            border-radius: 9px;
            background: #fff1f2;
            color: #be123c;
            font-size: 11px;
        }

        @media(max-width: 850px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary-panel {
                position: static;
            }
        }

        @media(max-width: 520px) {
            .cart-item {
                padding: 16px;
            }

            .cart-image {
                width: 78px;
                height: 78px;
            }

            .item-name {
                font-size: 14px;
            }

            .config-area {
                padding: 13px;
            }

            .item-top {
                gap: 12px;
            }

            .config-bottom {
                align-items: stretch;
            }

            .save-btn {
                flex: 1;
            }

            .auto-save-status {
                color: var(--success);
                font-size: 11px;
                font-weight: 700;
            }

            .auto-save-status.error {
                color: var(--danger);
            }

            .auto-save-status.saving {
                color: var(--muted);
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
                Cuci Disini
            </a>

            <div class="icon-row">
                <a href="profile.php" class="icon-btn" aria-label="Profil Saya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21c0-4 4-7 8-7s8 3 8 7" />
                    </svg>
                </a>

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
        <a href="kontak.php">Kontak</a>
        <a href="feedback.php">Feedback</a>
        <a href="keranjang.php" aria-current="page">Keranjang</a>
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

    <main id="main" class="container">

        <header class="page-header">

            <div class="breadcrumb">
                <a href="index.php">Home</a> /
                <span>Keranjang</span>
            </div>

            <h1>Keranjang Saya</h1>
            <p>Atur detail perawatan sebelum melanjutkan pesanan.</p>

        </header>

        <?php if ($pesan): ?>
            <div class="alert alert-success"><?= e($pesan) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="cart-layout">

            <section class="cart-panel">

                <div class="panel-header">
                    <h2>Item Keranjang</h2>
                    <span class="item-count"><?= $total_item ?> item</span>
                </div>

                <?php if (empty($items)): ?>

                    <div class="empty-cart">
                        <div class="empty-icon">🛒</div>
                        <h2>Keranjang masih kosong</h2>
                        <p>Pilih layanan yang ingin kamu gunakan.</p>

                        <a href="layanan.php" class="shop-btn">
                            Jelajahi Layanan →
                        </a>
                    </div>

                <?php else: ?>

                    <?php foreach ($items as $item): ?>

                        <?php
                        $kid = (int) $item['keranjang_id'];
                        $lid = (int) $item['layanan_id'];

                        $item_variants = $variants[$lid] ?? [];
                        $item_sizes = $sizes[$lid] ?? [];
                        $item_addons = $addons[$lid] ?? [];
                        $item_selected = $selected_addons[$kid] ?? [];
                        ?>

                        <article class="cart-item">

                            <div class="item-top">

                                <img
                                    class="cart-image"
                                    src="<?= e($item['gambar'] ?: 'assets/img/no-image.png') ?>"
                                    alt="<?= e($item['nama']) ?>"
                                    onerror="this.src='assets/img/no-image.png'">

                                <div class="item-info">

                                    <span class="category">
                                        <?= e($item['kategori']) ?>
                                    </span>

                                    <h3 class="item-name">
                                        <?= e($item['nama']) ?>
                                    </h3>

                                    <p class="item-time">
                                        Estimasi umum: <?= e($item['waktu']) ?>
                                    </p>

                                    <div class="unit-price" data-unit-price>
                                        <?= formatRupiah($item['harga_satuan']) ?> / item
                                    </div>

                                </div>

                            </div>

                            <form method="POST" class="config-area">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e($_SESSION['csrf_token']) ?>">

                                <input type="hidden" name="action"
                                    value="update_config">

                                <input type="hidden" name="keranjang_id"
                                    value="<?= $kid ?>">

                                <h4 class="config-title">
                                    <span>⚙️</span> Atur Detail Layanan
                                </h4>

                                <div class="field">

                                    <label for="varian-<?= $kid ?>">
                                        Durasi / Jenis Pengerjaan
                                    </label>

                                    <select id="varian-<?= $kid ?>" name="varian_id" required>

                                        <option value="">Pilih pengerjaan</option>

                                        <?php foreach ($item_variants as $variant): ?>

                                            <option
                                                value="<?= (int) $variant['id'] ?>"
                                                <?= ((int) $item['varian_id'] === (int) $variant['id']) ? 'selected' : '' ?>>

                                                <?= e($variant['nama_varian']) ?>
                                                <?php if (!empty($variant['durasi_label'])): ?>
                                                    · <?= e($variant['durasi_label']) ?>
                                                <?php endif; ?>
                                                — <?= formatRupiah($variant['harga']) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <?php if (!empty($item_sizes)): ?>

                                    <div class="field">

                                        <label for="ukuran-<?= $kid ?>">
                                            Ukuran Barang
                                        </label>

                                        <select id="ukuran-<?= $kid ?>" name="ukuran_id" required>

                                            <?php foreach ($item_sizes as $size): ?>

                                                <option
                                                    value="<?= (int) $size['id'] ?>"
                                                    <?= ((int) $item['ukuran_id'] === (int) $size['id']) ? 'selected' : '' ?>>

                                                    <?= e($size['nama_ukuran']) ?>
                                                    <?php if ((float) $size['harga_tambahan'] > 0): ?>
                                                        (+<?= formatRupiah($size['harga_tambahan']) ?>)
                                                    <?php else: ?>
                                                        (Harga dasar)
                                                    <?php endif; ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($item_addons)): ?>

                                    <div class="field">

                                        <span class="field-label">
                                            Tambahan Perawatan
                                        </span>

                                        <div class="addon-list">

                                            <?php foreach ($item_addons as $addon): ?>

                                                <?php
                                                $addon_id = (int) $addon['id'];
                                                $is_checked = false;

                                                foreach ($item_selected as $selected) {
                                                    if ((int) $selected['addon_id'] === $addon_id) {
                                                        $is_checked = true;
                                                        break;
                                                    }
                                                }
                                                ?>

                                                <label class="addon-option">

                                                    <input
                                                        type="checkbox"
                                                        name="addon_ids[]"
                                                        value="<?= $addon_id ?>"
                                                        <?= $is_checked ? 'checked' : '' ?>
                                                        <?= ((int) $addon['wajib'] === 1) ? 'required checked' : '' ?>>

                                                    <span class="addon-details">

                                                        <strong>
                                                            <?= e($addon['nama_addon']) ?>

                                                            <?php if ((int) $addon['wajib'] === 1): ?>
                                                                <span class="required-tag">Wajib</span>
                                                            <?php endif; ?>

                                                        </strong>

                                                        <?php if (!empty($addon['deskripsi'])): ?>
                                                            <small><?= e($addon['deskripsi']) ?></small>
                                                        <?php endif; ?>

                                                    </span>

                                                    <span class="addon-price">
                                                        +<?= formatRupiah($addon['harga']) ?>
                                                    </span>

                                                </label>

                                            <?php endforeach; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>

                                <div class="field">

                                    <label for="catatan-<?= $kid ?>">
                                        Catatan Khusus
                                        <span style="color:var(--muted);font-weight:400">
                                            (Opsional)
                                        </span>
                                    </label>

                                    <textarea
                                        id="catatan-<?= $kid ?>"
                                        name="catatan"
                                        maxlength="1000"
                                        placeholder="Contoh: noda di bagian kanan sepatu, jangan gunakan pewangi..."><?= e($item['catatan']) ?></textarea>

                                </div>

                                <div class="config-bottom">

                                    <div class="quantity">

                                        <button type="button"
                                            onclick="ubahJumlah(this, -1)">−</button>

                                        <input
                                            type="number"
                                            name="jumlah"
                                            value="<?= (int) $item['jumlah'] ?>"
                                            min="1"
                                            max="99"
                                            required>

                                        <button type="button"
                                            onclick="ubahJumlah(this, 1)">+</button>

                                    </div>

                                    <span class="auto-save-status">
                                        Perubahan tersimpan
                                    </span>

                                    <!-- <button type="submit" class="save-btn">
                                        Simpan sekarang
                                    </button> -->

                                </div>

                            </form>

                            <div class="item-footer">

                                <div>
                                    <div class="subtotal-label">Subtotal item</div>
                                    <div class="subtotal" data-subtotal>
                                        <?= formatRupiah($item['subtotal']) ?>
                                    </div>
                                </div>

                                <form method="POST"
                                    onsubmit="return confirm('Hapus item ini dari keranjang?')">

                                    <input type="hidden" name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>">

                                    <input type="hidden" name="action" value="delete">

                                    <input type="hidden" name="keranjang_id"
                                        value="<?= $kid ?>">

                                    <button type="submit" class="remove-btn">
                                        Hapus item
                                    </button>

                                </form>

                            </div>

                            <?php if ($item['status_layanan'] !== 'Aktif' || empty($item_variants)): ?>

                                <div class="invalid-note">
                                    Layanan ini belum dapat dipesan. Periksa status layanan dan pilihan variannya.
                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>

            <aside class="summary-panel">

                <h2>Ringkasan Pesanan</h2>

                <div class="summary-row">
                    <span>Total item</span>
                    <strong id="total-item-count"><?= $total_item ?></strong>
                </div>

                <div class="summary-row">
                    <span>Jenis layanan</span>
                    <strong><?= count($items) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Biaya layanan</span>
                    <strong id="summary-service-total"><?= formatRupiah($total) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Pengantaran</span>
                    <strong>Menyesuaikan</strong>
                </div>

                <div class="summary-row summary-total">
                    <span>Total</span>
                    <strong id="summary-grand-total"><?= formatRupiah($total) ?></strong>
                </div>

                <?php if (!empty($items) && $cart_ready): ?>

                    <button
                        type="button"
                        class="checkout-btn"
                        onclick="konfirmasiCheckout()">
                        Simpan Semua & Checkout →
                    </button>

                <?php elseif (!empty($items)): ?>

                    <span class="checkout-btn disabled">
                        Lengkapi Pilihan Layanan
                    </span>

                <?php else: ?>

                    <span class="checkout-btn disabled">
                        Lanjutkan Checkout →
                    </span>

                <?php endif; ?>

                <a href="layanan.php" class="continue-shopping">
                    ← Tambah Layanan Lain
                </a>

                <p class="secure-note">
                    Harga dihitung berdasarkan varian, ukuran, dan addon yang dipilih.
                    Periksa kembali konfigurasi sebelum checkout.
                </p>

            </aside>

        </div>

    </main>

    <script>
        function ubahJumlah(button, perubahan) {
            const form = button.closest('form');
            const input = form.querySelector('[name="jumlah"]');

            const sekarang = parseInt(input.value, 10) || 1;
            const baru = Math.max(1, Math.min(99, sekarang + perubahan));

            input.value = baru;

            // Picu auto-save
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        const formatformatRupiah = (angka) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(angka);
        };

        // Auto-save setiap konfigurasi item
        document.querySelectorAll('.config-area').forEach(form => {
            let timer = null;
            let saving = false;
            let dirty = false;

            const status = form.querySelector('.auto-save-status');
            const button = form.querySelector('.save-btn');

            function setStatus(message, type = '') {
                status.textContent = message;
                status.className = 'auto-save-status ' + type;
            }

            function scheduleSave(delay = 600) {
                clearTimeout(timer);

                setStatus('Menunggu perubahan...', 'saving');

                timer = setTimeout(() => {
                    saveConfig();
                }, delay);
            }

            async function saveConfig() {
                if (saving) {
                    dirty = true;
                    return;
                }

                if (!form.reportValidity()) {
                    setStatus('Periksa konfigurasi terlebih dahulu.', 'error');
                    return;
                }

                saving = true;

                do {
                    dirty = false;

                    setStatus('Menyimpan...', 'saving');
                    if (button) button.disabled = true;

                    try {
                        const formData = new FormData(form);

                        const response = await fetch('keranjang.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(
                                result.message || 'Gagal menyimpan perubahan.'
                            );
                        }

                        // Update harga satuan
                        const unitPrice = form.querySelector('[data-unit-price]');

                        if (unitPrice) {
                            unitPrice.textContent =
                                formatformatRupiah(result.item.harga_satuan) + ' / item';
                        }

                        // Update subtotal item
                        const cartItem = form.closest('.cart-item');
                        const subtotal = cartItem.querySelector('[data-subtotal]');

                        if (subtotal) {
                            subtotal.textContent =
                                formatformatRupiah(result.item.subtotal);
                        }

                        // Update total keranjang
                        document.querySelector('#summary-service-total').textContent =
                            formatformatRupiah(result.cart.total);

                        document.querySelector('#summary-grand-total').textContent =
                            formatformatRupiah(result.cart.total);

                        document.querySelector('#total-item-count').textContent =
                            result.cart.total_item;

                        setStatus('Tersimpan otomatis ✓');

                    } catch (error) {
                        dirty = false;
                        setStatus(error.message, 'error');
                    }

                    if (button) button.disabled = false;

                } while (dirty);

                saving = false;
            }

            // Perubahan dropdown dan checkbox
            form.addEventListener('change', () => {
                scheduleSave(400);
            });

            // Perubahan jumlah dan catatan
            form.addEventListener('input', event => {
                if (
                    event.target.matches('textarea') ||
                    event.target.matches('[name="jumlah"]')
                ) {
                    scheduleSave(800);
                }
            });

            // Tombol manual tetap tersedia sebagai fallback
            form.addEventListener('submit', event => {
                event.preventDefault();

                clearTimeout(timer);
                saveConfig();
            });
        });


        // Checkout
        function konfirmasiCheckout() {
            const forms = document.querySelectorAll('.config-area');

            if (forms.length === 0) {
                alert('Keranjang kamu masih kosong.');
                return;
            }

            for (const form of forms) {
                if (!form.reportValidity()) {
                    return;
                }
            }

            if (!confirm(
                    'Lanjutkan ke checkout dengan konfigurasi terbaru?'
                )) {
                return;
            }

            const checkoutForm = document.createElement('form');

            checkoutForm.method = 'POST';
            checkoutForm.action = 'keranjang.php';

            function addInput(name, value) {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = name;
                input.value = value;

                checkoutForm.appendChild(input);
            }

            const csrfToken = document.querySelector(
                'input[name="csrf_token"]'
            ).value;

            addInput('csrf_token', csrfToken);
            addInput('action', 'update_all_checkout');

            forms.forEach(configForm => {
                const data = new FormData(configForm);
                const cartId = data.get('keranjang_id');

                if (!cartId) return;

                for (const [key, value] of data.entries()) {
                    if (
                        key === 'csrf_token' ||
                        key === 'action' ||
                        key === 'keranjang_id'
                    ) {
                        continue;
                    }

                    const fieldName = key === 'addon_ids[]' ?
                        `items[${cartId}][addon_ids][]` :
                        `items[${cartId}][${key}]`;

                    addInput(fieldName, value);
                }
            });

            document.body.appendChild(checkoutForm);
            checkoutForm.submit();
        }
    </script>

</body>

</html>