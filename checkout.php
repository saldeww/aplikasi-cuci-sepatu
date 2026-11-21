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

// Proses checkout.
$error = handleCheckoutRequest($conn, $user_id);

// Ambil keranjang terbaru.
$cart_data = getCheckoutCart($conn, $user_id);

$items = $cart_data['items'];
$total_harga = $cart_data['total'];
$cart_invalid = $cart_data['invalid'];

// Keranjang kosong.
if (empty($items)) {
    header('Location: keranjang.php');
    exit;
}

// Validasi konfigurasi keranjang.
if ($cart_invalid && $error === '') {
    $error = 'Ada konfigurasi layanan yang tidak valid atau sudah tidak tersedia. Silakan periksa keranjang.';
}

// Lokasi awal peta.
$default_lat = -6.9080217;
$default_lng = 106.8893248;

// Pertahankan koordinat jika form sebelumnya gagal.
$latitude_awal = isset($_POST['latitude']) && is_numeric($_POST['latitude'])
    ? (float) $_POST['latitude']
    : $default_lat;

$longitude_awal = isset($_POST['longitude']) && is_numeric($_POST['longitude'])
    ? (float) $_POST['longitude']
    : $default_lng;

// Validasi rentang koordinat.
if (
    $latitude_awal < -90 || $latitude_awal > 90 ||
    $longitude_awal < -180 || $longitude_awal > 180
) {
    $latitude_awal = $default_lat;
    $longitude_awal = $default_lng;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Checkout | Cuci Disini</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="">

    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        #map {
            width: 100%;
            height: 280px;
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            z-index: 1;
        }

        .map-search {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .map-search input {
            flex: 1;
            min-width: 0;
            margin-bottom: 0;
        }

        .map-search button {
            padding: 0 18px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .map-search button:hover {
            background: #1d4ed8;
        }

        .map-search button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .map-search-status {
            margin: 5px 0 10px;
            color: #6b7280;
            font-size: 13px;
        }

        .map-results {
            display: none;
            margin-bottom: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
        }

        .map-result {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-bottom: 1px solid #eee;
            background: #fff;
            text-align: left;
            cursor: pointer;
            font: inherit;
        }

        .map-result:last-child {
            border-bottom: none;
        }

        .map-result:hover {
            background: #f3f4f6;
        }

        .map-result small {
            display: block;
            margin-top: 4px;
            color: #6b7280;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .checkout-card {
            margin-bottom: 20px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
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
            box-sizing: border-box;
            width: 100%;
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font: inherit;
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
            padding-top: 18px;
            font-size: 18px;
            font-weight: 800;
        }

        .checkout-submit {
            width: 100%;
            margin-top: 20px;
            cursor: pointer;
        }

        .checkout-error {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
        }

        /* ======================================
           DELIVERY OPTION CARDS
        ====================================== */

        .delivery-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .delivery-option {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: border-color .15s, background .15s, box-shadow .15s;
            user-select: none;
            position: relative;
        }

        .delivery-option:hover {
            border-color: #93c5fd;
            background: #f8faff;
        }

        .delivery-option.is-selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37,99,235,.08);
        }

        .delivery-option-icon {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #f1f5f9;
            color: #64748b;
            transition: background .15s, color .15s;
        }

        .delivery-option.is-selected .delivery-option-icon {
            background: #dbeafe;
            color: #2563eb;
        }

        .delivery-option-body {
            flex: 1;
            min-width: 0;
        }

        .delivery-option-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 14px;
            color: #111827;
            line-height: 1.4;
        }

        .delivery-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
        }

        .delivery-option.is-selected .delivery-badge {
            background: #dbeafe;
            color: #1e40af;
        }

        .delivery-option-desc {
            margin-top: 4px;
            font-size: 12.5px;
            color: #6b7280;
            line-height: 1.6;
        }

        .delivery-option-check {
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1.5px solid #d1d5db;
            display: grid;
            place-items: center;
            color: transparent;
            transition: background .15s, border-color .15s, color .15s;
            margin-top: 2px;
        }

        .delivery-option.is-selected .delivery-option-check {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
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
                                placeholder="08xxxxxxxxxx"
                                value="<?= e($_POST['no_wa'] ?? $user['no_wa'] ?? '') ?>"
                                required>

                            <label for="alamat_pengantaran">Alamat Pengantaran</label>

                            <p style="font-size:13px;color:#6b7280;margin-top:-6px;margin-bottom:10px;">
                                Cari alamat atau pilih titik pada peta. Geser pin untuk menyesuaikan lokasi.
                            </p>

                            <div class="map-search">
                                <input
                                    type="search"
                                    id="map-search-input"
                                    placeholder="Cari alamat, jalan, atau tempat..."
                                    autocomplete="off">

                                <button type="button" id="map-search-button">
                                    Cari
                                </button>
                            </div>

                            <div id="map-search-status" class="map-search-status">
                                Cari lokasi atau pilih titik langsung pada peta.
                            </div>

                            <div id="map-results" class="map-results"></div>

                            <div id="map"></div>

                            <textarea
                                id="alamat_pengantaran"
                                name="alamat_pengantaran"
                                maxlength="1000"
                                placeholder="Alamat akan terisi otomatis saat memilih titik pada peta..."
                                required><?= e($_POST['alamat_pengantaran'] ?? '') ?></textarea>

                            <input
                                type="hidden"
                                name="latitude"
                                id="latitude"
                                value="<?= e((string) $latitude_awal) ?>">

                            <input
                                type="hidden"
                                name="longitude"
                                id="longitude"
                                value="<?= e((string) $longitude_awal) ?>">

                            <label for="wilayah_pengantaran">
                                Wilayah Pengantaran
                            </label>

                            <select
                                name="wilayah_pengantaran"
                                id="wilayah_pengantaran"
                                required>
                                <option value="">Pilih wilayah pengantaran</option>

                                <option
                                    value="kota_sukabumi"
                                    <?= (($_POST['wilayah_pengantaran'] ?? '') === 'kota_sukabumi') ? 'selected' : '' ?>>
                                    Kota Sukabumi — Gratis
                                </option>

                                <option
                                    value="luar_kota"
                                    <?= (($_POST['wilayah_pengantaran'] ?? '') === 'luar_kota') ? 'selected' : '' ?>>
                                    Kabupaten Sukabumi / Luar Kota — Rp15.000
                                </option>
                            </select>

                            <p id="wilayah-hint" style="font-size:12px;color:#6b7280;margin-top:-8px;">
                                Ongkir gratis untuk Kota Sukabumi. Wilayah lainnya dikenakan Rp15.000.
                            </p>
                        </div>

                        <!-- METODE PENGANTARAN -->
                        <div class="checkout-card">
                            <h2>Metode Pengantaran</h2>

                            <p style="font-size:13px;color:#6b7280;margin:-8px 0 16px;">
                                Pilih cara pengiriman sepatu yang paling sesuai.
                            </p>

                            <?php
                            $selected_metode = $_POST['metode_pengantaran'] ?? 'Antar-Jemput';
                            $metode_options = [
                                [
                                    'value'    => 'Antar-Jemput',
                                    'label'    => 'Antar Jemput',
                                    'badge'    => 'Full Service',
                                    'desc'     => 'Kurir menjemput sepatu dari alamat kamu dan mengantarkannya kembali setelah selesai.',
                                    'icon'     => '<path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="1"/><circle cx="12" cy="21" r=".5" fill="currentColor"/><circle cx="20" cy="21" r=".5" fill="currentColor"/>',
                                ],
                                [
                                    'value'    => 'Antar',
                                    'label'    => 'Antar ke Outlet',
                                    'badge'    => null,
                                    'desc'     => 'Kamu mengantarkan sepatu ke outlet, lalu sepatu yang sudah selesai akan diantar ke alamat kamu.',
                                    'icon'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                                ],
                                [
                                    'value'    => 'Jemput',
                                    'label'    => 'Jemput & Ambil Sendiri',
                                    'badge'    => null,
                                    'desc'     => 'Kurir menjemput sepatu dari alamat kamu. Setelah selesai, kamu mengambilnya sendiri di outlet.',
                                    'icon'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/><path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83"/>',
                                ],
                            ];
                            ?>

                            <input
                                type="hidden"
                                id="metode_pengantaran"
                                name="metode_pengantaran"
                                value="<?= e($selected_metode) ?>"
                                required>

                            <div class="delivery-options" id="delivery-options">
                                <?php foreach ($metode_options as $opt): ?>
                                    <label
                                        class="delivery-option <?= $selected_metode === $opt['value'] ? 'is-selected' : '' ?>"
                                        data-value="<?= e($opt['value']) ?>">

                                        <div class="delivery-option-icon">
                                            <svg
                                                width="22" height="22"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <?= $opt['icon'] ?>
                                            </svg>
                                        </div>

                                        <div class="delivery-option-body">
                                            <div class="delivery-option-title">
                                                <?= e($opt['label']) ?>
                                                <?php if ($opt['badge']): ?>
                                                    <span class="delivery-badge">
                                                        <?= e($opt['badge']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="delivery-option-desc">
                                                <?= e($opt['desc']) ?>
                                            </div>
                                        </div>

                                        <div class="delivery-option-check">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </div>

                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <label for="jadwal_pelaksanaan" style="margin-top:4px;">
                                Jadwal Pelaksanaan
                            </label>

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

                            <label for="metode_pembayaran">
                                Pilih Pembayaran
                            </label>

                            <select
                                id="metode_pembayaran"
                                name="metode_pembayaran"
                                required>
                                <option value="">Pilih metode pembayaran</option>

                                <option
                                    value="QRIS"
                                    <?= (($_POST['metode_pembayaran'] ?? '') === 'QRIS') ? 'selected' : '' ?>>
                                    QRIS
                                </option>

                                <option
                                    value="Cash"
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

                                    <div style="flex:1;">
                                        <strong>
                                            <?= e($item['nama']) ?>
                                        </strong>

                                        <div style="margin-top:5px;">
                                            <small>
                                                <?= e($item['nama_varian'] ?? 'Varian belum dipilih') ?>

                                                <?php if (!empty($item['durasi_label'])): ?>
                                                    | <?= e($item['durasi_label']) ?>
                                                <?php endif; ?>
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
                                            <div style="margin-top:5px;">
                                                <small>
                                                    Catatan: <?= e($item['catatan']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-top:7px;">
                                            <small>
                                                <?= (int) $item['jumlah'] ?>
                                                ×
                                                <?= formatRupiah($item['harga_satuan']) ?>
                                            </small>
                                        </div>
                                    </div>

                                    <strong>
                                        <?= formatRupiah($item['subtotal']) ?>
                                    </strong>

                                </div>
                            <?php endforeach; ?>

                            <!-- ONGKIR -->
                            <div style="
                                display:flex;
                                justify-content:space-between;
                                margin-top:15px;
                                padding-top:12px;
                                border-top:1px solid #eee;
                                color:#4b5563;
                                font-size:14px;
                            ">
                                <span id="label-ongkir">
                                    Biaya Pengantaran
                                </span>

                                <span id="nominal-ongkir">
                                    Rp 0
                                </span>
                            </div>

                            <!-- TOTAL -->
                            <div class="checkout-total">
                                <span>Total</span>

                                <span
                                    id="total-harga-display"
                                    data-base-total="<?= (float) $total_harga ?>">
                                    <?= formatRupiah($total_harga) ?>
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="btn checkout-submit"
                                <?= $cart_invalid ? 'disabled' : '' ?>>
                                Buat Pesanan
                            </button>

                            <p style="text-align:center;margin-top:14px;">
                                <a href="keranjang.php">
                                    Kembali ke keranjang
                                </a>
                            </p>

                        </div>
                    </div>

                </div>
            </form>
        </div>
    </main>

    <script src="assets/js/main.js" defer></script>

    <!-- Leaflet JS wajib dimuat sebelum script peta -->
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ======================================
            // ELEMEN HALAMAN
            // ======================================

            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const alamatInput = document.getElementById('alamat_pengantaran');

            const wilayahSelect = document.getElementById('wilayah_pengantaran');
            const labelOngkir = document.getElementById('label-ongkir');
            const nominalOngkir = document.getElementById('nominal-ongkir');
            const totalDisplay = document.getElementById('total-harga-display');

            const searchInput = document.getElementById('map-search-input');
            const searchButton = document.getElementById('map-search-button');
            const searchResults = document.getElementById('map-results');
            const searchStatus = document.getElementById('map-search-status');

            const baseTotal = Number(totalDisplay.dataset.baseTotal) || 0;

            // ======================================
            // HITUNG ONGKIR
            // ======================================

            function formatRupiah(angka) {
                return 'Rp ' + Number(angka).toLocaleString('id-ID');
            }

            function cekBiayaPengantaran() {
                const wilayah = wilayahSelect.value;
                let ongkir = 0;

                if (wilayah === 'luar_kota') {
                    ongkir = 15000;
                    labelOngkir.textContent = 'Biaya Pengantaran (Luar Kota)';
                } else if (wilayah === 'kota_sukabumi') {
                    labelOngkir.textContent = 'Biaya Pengantaran (Kota Sukabumi)';
                } else {
                    labelOngkir.textContent = 'Biaya Pengantaran';
                }

                nominalOngkir.textContent = formatRupiah(ongkir);
                totalDisplay.textContent = formatRupiah(baseTotal + ongkir);
            }

            wilayahSelect.addEventListener('change', cekBiayaPengantaran);
            cekBiayaPengantaran();

            // ======================================
            // SIMPAN KOORDINAT
            // ======================================

            function simpanKoordinat(lat, lng) {
                const latitude = Number(lat);
                const longitude = Number(lng);

                if (
                    !Number.isFinite(latitude) ||
                    !Number.isFinite(longitude) ||
                    latitude < -90 ||
                    latitude > 90 ||
                    longitude < -180 ||
                    longitude > 180
                ) {
                    return;
                }

                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);
            }

            // ======================================
            // INISIALISASI PETA
            // ======================================

            const initialLat = Number(latitudeInput.value);
            const initialLng = Number(longitudeInput.value);

            const map = L.map('map').setView(
                [initialLat, initialLng],
                13
            );

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const marker = L.marker(
                [initialLat, initialLng], {
                    draggable: true
                }
            ).addTo(map);

            // Menandai apakah pengguna sudah memilih lokasi sendiri.
            let lokasiDipilihManual = false;

            // ======================================
            // AMBIL ALAMAT DARI KOORDINAT
            // ======================================

            // Kode pos Kota Sukabumi: 43100–43199
            function isKotaSukabumi(postcode) {
                if (!postcode) return null;
                const code = parseInt(postcode.replace(/\D/g, ''), 10);
                if (isNaN(code)) return null;
                return code >= 43100 && code <= 43199;
            }

            const wilayahHint = document.getElementById('wilayah-hint');

            function terapkanWilayah(postcode) {
                const kotaSukabumi = isKotaSukabumi(postcode);

                if (kotaSukabumi === true) {
                    wilayahSelect.value = 'kota_sukabumi';
                    wilayahHint.textContent =
                        'Lokasi terdeteksi di Kota Sukabumi (kode pos ' + postcode + ') — ongkir gratis.';
                    wilayahHint.style.color = '#16a34a';
                } else if (kotaSukabumi === false) {
                    wilayahSelect.value = 'luar_kota';
                    wilayahHint.textContent =
                        'Lokasi terdeteksi di luar Kota Sukabumi (kode pos ' + postcode + ') — dikenakan Rp15.000.';
                    wilayahHint.style.color = '#b45309';
                } else {
                    wilayahHint.textContent =
                        'Kode pos tidak ditemukan. Pilih wilayah secara manual.';
                    wilayahHint.style.color = '#6b7280';
                }

                cekBiayaPengantaran();
            }

            async function fetchAddress(lat, lng) {
                simpanKoordinat(lat, lng);

                try {
                    const params = new URLSearchParams({
                        lat: lat,
                        lon: lng,
                        format: 'jsonv2',
                        addressdetails: '1'
                    });

                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?${params.toString()}`, {
                            headers: {
                                'Accept-Language': 'id'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Gagal mengambil alamat.');
                    }

                    const data = await response.json();

                    if (data.display_name) {
                        alamatInput.value = data.display_name;
                    }

                    terapkanWilayah(data.address?.postcode ?? null);

                } catch (error) {
                    console.error('Gagal mengambil alamat:', error);
                    searchStatus.textContent =
                        'Koordinat tersimpan, tetapi alamat otomatis gagal dimuat.';
                }
            }

            // ======================================
            // PIN DIGESER
            // ======================================

            marker.on('dragend', function() {
                lokasiDipilihManual = true;

                const position = marker.getLatLng();

                fetchAddress(position.lat, position.lng);
            });

            // ======================================
            // PETA DIKLIK
            // ======================================

            map.on('click', function(event) {
                lokasiDipilihManual = true;

                const lat = event.latlng.lat;
                const lng = event.latlng.lng;

                marker.setLatLng([lat, lng]);

                fetchAddress(lat, lng);
            });

            // ======================================
            // GPS PENGGUNA
            // ======================================

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {

                        // Jangan timpa lokasi yang sudah dipilih manual.
                        if (lokasiDipilihManual) {
                            return;
                        }

                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;

                        map.setView([userLat, userLng], 16);
                        marker.setLatLng([userLat, userLng]);

                        simpanKoordinat(userLat, userLng);

                        // Jangan timpa alamat yang sudah diisi sebelumnya.
                        if (alamatInput.value.trim() === '') {
                            fetchAddress(userLat, userLng);
                        }

                    },
                    function() {
                        console.log(
                            'GPS tidak tersedia atau tidak diizinkan. Menggunakan lokasi awal.'
                        );
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }

            // ======================================
            // PENCARIAN LOKASI
            // ======================================

            async function cariLokasi() {
                const keyword = searchInput.value.trim();

                if (keyword.length < 3) {
                    searchStatus.textContent =
                        'Masukkan minimal 3 karakter.';
                    searchResults.style.display = 'none';
                    return;
                }

                searchButton.disabled = true;
                searchButton.textContent = 'Mencari...';
                searchStatus.textContent = 'Sedang mencari lokasi...';
                searchResults.style.display = 'none';

                try {
                    const params = new URLSearchParams({
                        q: keyword,
                        format: 'jsonv2',
                        addressdetails: '1',
                        limit: '5',
                        countrycodes: 'id'
                    });

                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/search?${params.toString()}`, {
                            headers: {
                                'Accept-Language': 'id'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Gagal menghubungi layanan pencarian.');
                    }

                    const data = await response.json();

                    searchResults.innerHTML = '';

                    if (!data.length) {
                        searchStatus.textContent =
                            'Lokasi tidak ditemukan. Coba kata kunci lain.';
                        return;
                    }

                    searchStatus.textContent =
                        `${data.length} lokasi ditemukan. Pilih salah satu:`;

                    searchResults.style.display = 'block';

                    data.forEach(function(location) {

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'map-result';

                        const title = document.createElement('strong');
                        title.textContent =
                            location.name ||
                            location.display_name.split(',')[0];

                        const description = document.createElement('small');
                        description.textContent = location.display_name;

                        button.appendChild(title);
                        button.appendChild(description);

                        button.addEventListener('click', function() {

                            const lat = parseFloat(location.lat);
                            const lng = parseFloat(location.lon);

                            if (
                                !Number.isFinite(lat) ||
                                !Number.isFinite(lng)
                            ) {
                                return;
                            }

                            lokasiDipilihManual = true;

                            map.setView([lat, lng], 17);
                            marker.setLatLng([lat, lng]);

                            simpanKoordinat(lat, lng);

                            alamatInput.value = location.display_name;

                            searchInput.value =
                                location.name || location.display_name;

                            // Terapkan wilayah dari postcode hasil pencarian
                            // jika ada; jika tidak, reverse geocode akan mengisinya.
                            const postcode = location.address?.postcode ?? null;
                            if (postcode) {
                                terapkanWilayah(postcode);
                            } else {
                                fetchAddress(lat, lng);
                            }

                            searchResults.style.display = 'none';

                            searchStatus.textContent =
                                'Lokasi berhasil dipilih. Geser pin jika posisi belum tepat.';
                        });

                        searchResults.appendChild(button);
                    });

                } catch (error) {
                    console.error('Pencarian lokasi gagal:', error);

                    searchStatus.textContent =
                        'Pencarian gagal. Periksa koneksi internet dan coba lagi.';

                } finally {
                    searchButton.disabled = false;
                    searchButton.textContent = 'Cari';
                }
            }

            searchButton.addEventListener('click', cariLokasi);

            searchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    cariLokasi();
                }
            });

            // ======================================
            // DELIVERY OPTION CARD PICKER
            // ======================================

            const deliveryOptions   = document.querySelectorAll('.delivery-option');
            const metodeInput       = document.getElementById('metode_pengantaran');

            deliveryOptions.forEach(function(card) {
                card.addEventListener('click', function() {
                    deliveryOptions.forEach(function(c) {
                        c.classList.remove('is-selected');
                    });
                    card.classList.add('is-selected');
                    metodeInput.value = card.dataset.value;
                });
            });

            // Pastikan card yang sesuai value input terpilih saat halaman load.
            if (metodeInput.value) {
                deliveryOptions.forEach(function(card) {
                    if (card.dataset.value === metodeInput.value) {
                        card.classList.add('is-selected');
                    }
                });
            }

            // Validasi sebelum submit — pastikan metode terpilih.
            document.querySelector('form.checkout-form').addEventListener('submit', function(e) {
                if (!metodeInput.value) {
                    e.preventDefault();
                    alert('Silakan pilih metode pengantaran terlebih dahulu.');
                    document.getElementById('delivery-options').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

        });
    </script>

</body>

</html>