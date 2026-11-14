<?php

session_start();

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helper.php';
require_once __DIR__ . '/functions/checkout_functions.php';
require_once __DIR__ . '/handlers/checkout_handler.php';

// Pastikan pelanggan sudah login.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Buat CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil data pelanggan.
$user = getCheckoutUser($conn, $user_id);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Proses checkout jika form dikirim.
$error = handleCheckoutRequest($conn, $user_id);

// Ambil ringkasan keranjang terbaru.
$cart_data = getCheckoutCart($conn, $user_id);

$items = $cart_data['items'];
$total_harga = $cart_data['total'];
$cart_invalid = $cart_data['invalid'];

// Jika keranjang kosong, kembali ke keranjang.
if (empty($items)) {
    header('Location: keranjang.php');
    exit;
}

// Cegah checkout jika konfigurasi keranjang tidak valid.
if ($cart_invalid && $error === '') {
    $error = 'Ada konfigurasi layanan yang tidak valid atau sudah tidak tersedia. Silakan periksa keranjang.';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Checkout | Cuci Disini</title>

    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .checkout-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .checkout-card h2 {
            margin: 0 0 20px;
            font-size: 20px;
        }

        .checkout-form label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .checkout-form input,
        .checkout-form textarea,
        .checkout-form select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            margin-bottom: 16px;
            font: inherit;
            box-sizing: border-box;
        }

        .checkout-form textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }

        .checkout-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 800;
            padding-top: 18px;
        }

        .checkout-submit {
            width: 100%;
            margin-top: 20px;
            cursor: pointer;
        }

        .checkout-error {
            padding: 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="container">

            <a href="index.php" class="brand">
                <span class="brand-mark">C</span>
                cuci disini
            </a>

            <div class="icon-row">
                <a href="keranjang.php" class="icon-btn">Keranjang</a>
                <a href="pesanan.php" class="icon-btn">Pesanan</a>
            </div>

        </div>
    </header>

    <main class="section">
        <div class="container">

            <div class="crumbs">
                <a href="index.php">Home</a>
                <span class="sep">›</span>
                <a href="keranjang.php">Keranjang</a>
                <span class="sep">›</span>
                <span>Checkout</span>
            </div>

            <h1>Checkout</h1>
            <p>Lengkapi detail pesanan sebelum melanjutkan.</p>

            <?php if ($error !== ''): ?>
                <div class="checkout-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="checkout-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="checkout-layout">

                    <!-- INFORMASI PELANGGAN -->
                    <div>

                        <div class="checkout-card">

                            <h2>Informasi Pelanggan</h2>

                            <label for="nama_lengkap">Nama Lengkap</label>

                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                maxlength="150"
                                value="<?= e($_POST['nama_lengkap'] ?? $user['nama'] ?? '') ?>"
                                required>

                            <label for="no_wa">Nomor WhatsApp</label>

                            <input
                                type="tel"
                                id="no_wa"
                                name="no_wa"
                                maxlength="30"
                                value="<?= e($_POST['no_wa'] ?? $user['no_wa'] ?? '') ?>"
                                placeholder="08xxxxxxxxxx"
                                required>

                            <label for="alamat_pengantaran">Alamat Pengantaran</label>

                            <textarea
                                id="alamat_pengantaran"
                                name="alamat_pengantaran"
                                maxlength="1000"
                                placeholder="Masukkan alamat lengkap..."
                                required><?= e($_POST['alamat_pengantaran'] ?? '') ?></textarea>

                        </div>

                        <!-- METODE PENGANTARAN -->
                        <div class="checkout-card">

                            <h2>Metode Pengantaran</h2>

                            <label for="metode_pengantaran">Pilih Metode</label>

                            <select
                                id="metode_pengantaran"
                                name="metode_pengantaran"
                                required>

                                <option value="">Pilih metode pengantaran</option>

                                <option value="Antar"
                                    <?= (($_POST['metode_pengantaran'] ?? '') === 'Antar') ? 'selected' : '' ?>>
                                    Antar
                                </option>

                                <option value="Antar-Jemput"
                                    <?= (($_POST['metode_pengantaran'] ?? '') === 'Antar-Jemput') ? 'selected' : '' ?>>
                                    Antar-Jemput
                                </option>

                            </select>

                            <label for="jadwal_pelaksanaan">Jadwal Pelaksanaan</label>

                            <input
                                type="datetime-local"
                                id="jadwal_pelaksanaan"
                                name="jadwal_pelaksanaan"
                                min="<?= date('Y-m-d\TH:i') ?>"
                                value="<?= e($_POST['jadwal_pelaksanaan'] ?? '') ?>"
                                required>

                        </div>

                        <!-- METODE PEMBAYARAN -->
                        <div class="checkout-card">

                            <h2>Metode Pembayaran</h2>

                            <label for="metode_pembayaran">Pilih Pembayaran</label>

                            <select
                                id="metode_pembayaran"
                                name="metode_pembayaran"
                                required>

                                <option value="">Pilih metode pembayaran</option>

                                <option value="QRIS"
                                    <?= (($_POST['metode_pembayaran'] ?? '') === 'QRIS') ? 'selected' : '' ?>>
                                    QRIS
                                </option>

                                <option value="Cash"
                                    <?= (($_POST['metode_pembayaran'] ?? '') === 'Cash') ? 'selected' : '' ?>>
                                    Cash
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- RINGKASAN PESANAN -->
                    <div>

                        <div class="checkout-card">

                            <h2>Ringkasan Pesanan</h2>

                            <?php foreach ($items as $item): ?>

                                <div class="checkout-item">

                                    <div style="flex: 1;">

                                        <strong><?= e($item['nama']) ?></strong>

                                        <div style="margin-top: 5px;">
                                            <small>
                                                <?= e($item['nama_varian'] ?? 'Varian belum dipilih') ?>

                                                <!-- <?php if (!empty($item['durasi_label'])): ?>
                                                    · <?= e($item['durasi_label']) ?>
                                                <?php endif; ?> -->
                                            </small>
                                        </div>

                                        <?php if (!empty($item['nama_ukuran'])): ?>
                                            <div>
                                                <small>
                                                    Ukuran: <?= e($item['nama_ukuran']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <?php foreach ($item['addons'] as $addon): ?>
                                            <div>
                                                <small>
                                                    + <?= e($addon['nama_addon']) ?>
                                                    (<?= formatRupiah($addon['harga_snapshot']) ?>)
                                                </small>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (!empty($item['catatan'])): ?>
                                            <div style="margin-top: 5px;">
                                                <small>
                                                    Catatan: <?= e($item['catatan']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-top: 7px;">
                                            <small>
                                                <?= (int) $item['jumlah'] ?> ×
                                                <?= formatRupiah($item['harga_satuan']) ?>
                                            </small>
                                        </div>

                                    </div>

                                    <strong>
                                        <?= formatRupiah($item['subtotal']) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                            <div class="checkout-total">
                                <span>Total</span>
                                <span><?= formatRupiah($total_harga) ?></span>
                            </div>

                            <button
                                type="submit"
                                class="btn checkout-submit"
                                <?= ($error !== '' || $cart_invalid) ? 'disabled' : '' ?>>

                                Buat Pesanan

                            </button>

                            <p style="text-align: center; margin-top: 14px;">
                                <a href="keranjang.php">Kembali ke keranjang</a>
                            </p>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    </main>

    <script src="assets/js/main.js" defer></script>

</body>

</html>