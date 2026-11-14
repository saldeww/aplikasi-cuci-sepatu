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
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        #map {
            height: 280px;
            width: 100%;
            border-radius: 9px;
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
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
            color: white;
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

        .map-results {
            display: none;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }

        .map-result {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-bottom: 1px solid #eee;
            background: white;
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

        .map-search-status {
            font-size: 13px;
            color: #6b7280;
            margin: 5px 0 10px;
        }

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

                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="checkout-layout">

                    <!-- INFORMASI PELANGGAN -->
                    <div>
                        <div class="checkout-card">
                            <h2>Informasi Pelanggan</h2>

                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="150" value="<?= e($_POST['nama_lengkap'] ?? $user['nama'] ?? '') ?>" required>

                            <label for="no_wa">Nomor WhatsApp</label>
                            <input type="tel" id="no_wa" name="no_wa" maxlength="30" value="<?= e($_POST['no_wa'] ?? $user['no_wa'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>

                            <!-- Bagian Peta & Alamat -->
                            <label for="alamat_pengantaran">Alamat Pengantaran</label>
                            <p style="font-size: 13px; color: #6b7280; margin-top: -6px; margin-bottom: 10px;">
                                Geser pin merah pada peta atau klik lokasi Anda untuk mengisi alamat secara otomatis. (Luar Sukabumi dikenakan tambahan ongkir Rp 15.000).
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
                                placeholder="Alamat akan terisi otomatis saat Anda memilih titik di peta..."
                                required><?= e($_POST['alamat_pengantaran'] ?? '') ?></textarea>
                        </div>

                        <!-- METODE PENGANTARAN -->
                        <div class="checkout-card">
                            <h2>Metode Pengantaran</h2>
                            <label for="metode_pengantaran">Pilih Metode</label>
                            <select id="metode_pengantaran" name="metode_pengantaran" required>
                                <option value="">Pilih metode pengantaran</option>
                                <option value="Antar" <?= (($_POST['metode_pengantaran'] ?? '') === 'Antar') ? 'selected' : '' ?>>Antar</option>
                                <option value="Antar-Jemput" <?= (($_POST['metode_pengantaran'] ?? '') === 'Antar-Jemput') ? 'selected' : '' ?>>Antar-Jemput</option>
                            </select>

                            <label for="jadwal_pelaksanaan">Jadwal Pelaksanaan</label>
                            <input type="datetime-local" id="jadwal_pelaksanaan" name="jadwal_pelaksanaan" min="<?= date('Y-m-d\TH:i') ?>" value="<?= e($_POST['jadwal_pelaksanaan'] ?? '') ?>" required>
                        </div>

                        <!-- METODE PEMBAYARAN -->
                        <div class="checkout-card">
                            <h2>Metode Pembayaran</h2>
                            <label for="metode_pembayaran">Pilih Pembayaran</label>
                            <select id="metode_pembayaran" name="metode_pembayaran" required>
                                <option value="">Pilih metode pembayaran</option>
                                <option value="QRIS" <?= (($_POST['metode_pembayaran'] ?? '') === 'QRIS') ? 'selected' : '' ?>>QRIS</option>
                                <option value="Cash" <?= (($_POST['metode_pembayaran'] ?? '') === 'Cash') ? 'selected' : '' ?>>Cash</option>
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
                                                <?php if (!empty($item['durasi_label'])): ?>
                                                    | <?= e($item['durasi_label']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <?php if (!empty($item['nama_ukuran'])): ?>
                                            <div><small>Ukuran: <?= e($item['nama_ukuran']) ?></small></div>
                                        <?php endif; ?>
                                        <?php foreach ($item['addons'] as $addon): ?>
                                            <div><small>+ <?= e($addon['nama_addon']) ?> (<?= formatRupiah($addon['harga_snapshot']) ?>)</small></div>
                                        <?php endforeach; ?>
                                        <?php if (!empty($item['catatan'])): ?>
                                            <div style="margin-top: 5px;"><small>Catatan: <?= e($item['catatan']) ?></small></div>
                                        <?php endif; ?>
                                        <div style="margin-top: 7px;">
                                            <small><?= (int) $item['jumlah'] ?> × <?= formatRupiah($item['harga_satuan']) ?></small>
                                        </div>
                                    </div>
                                    <strong><?= formatRupiah($item['subtotal']) ?></strong>
                                </div>
                            <?php endforeach; ?>

                            <!-- Baris Biaya Pengantaran / Luar Sukabumi -->
                            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 14px; color: #4b5563; border-top: 1px solid #eee; padding-top: 12px;">
                                <span id="label-ongkir">Biaya Pengantaran</span>
                                <span id="nominal-ongkir">Rp 0</span>
                            </div>

                            <div class="checkout-total">
                                <span>Total</span>
                                <span id="total-harga-display" data-base-total="<?= $total_harga ?>"><?= formatRupiah($total_harga) ?></span>
                            </div>

                            <!-- Input tersembunyi untuk flag/biaya tambahan -->
                            <input type="hidden" name="biaya_tambahan_luar_kota" id="biaya_tambahan_luar_kota" value="0">

                            <button type="submit" class="btn checkout-submit" <?= $cart_invalid ? 'disabled' : '' ?>>
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
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const defaultLat = -6.9080217;
            const defaultLng = 106.8893248;

            const map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            let marker = L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(map);

            // Fungsi Format Rupiah JavaScript
            function formatRupiahJs(angka) {
                return 'Rp ' + angka.toLocaleString('id-ID');
            }

            // Fungsi untuk mengecek teks alamat dan menyesuaikan total harga
            function cekBiayaPengantaran(alamatTeks) {
                const baseTotal = parseInt(document.getElementById('total-harga-display').getAttribute('data-base-total')) || 0;
                const labelOngkir = document.getElementById('label-ongkir');
                const nominalOngkir = document.getElementById('nominal-ongkir');
                const totalDisplay = document.getElementById('total-harga-display');
                const hiddenBiaya = document.getElementById('biaya_tambahan_luar_kota');

                const isSukabumi = alamatTeks.toLowerCase().includes('sukabumi');
                let tambahanOngkir = 0;

                if (!isSukabumi && alamatTeks.trim() !== '') {
                    tambahanOngkir = 15000;
                    labelOngkir.textContent = "Biaya Pengantaran (Luar Sukabumi)";
                    nominalOngkir.textContent = "Rp 15.000";
                } else {
                    labelOngkir.textContent = "Biaya Pengantaran";
                    nominalOngkir.textContent = "Gratis / Standar";
                }

                const totalAkhir = baseTotal + tambahanOngkir;
                totalDisplay.textContent = formatRupiahJs(totalAkhir);
                hiddenBiaya.value = tambahanOngkir;
            }

            // Event saat textarea alamat diketik manual
            document.getElementById('alamat_pengantaran').addEventListener('input', function() {
                cekBiayaPengantaran(this.value);
            });

            async function fetchAddress(lat, lng) {
                const textarea = document.getElementById('alamat_pengantaran');
                textarea.placeholder = "Mencari alamat...";

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                        headers: {
                            'Accept-Language': 'id'
                        }
                    });
                    const data = await response.json();

                    if (data && data.display_name) {
                        textarea.value = data.display_name;
                    } else {
                        textarea.value = `Lat: ${lat}, Lng: ${lng}`;
                    }

                    cekBiayaPengantaran(textarea.value);
                } catch (error) {
                    console.error('Gagal mengambil alamat:', error);
                    textarea.value = `Lat: ${lat}, Lng: ${lng}`;
                    cekBiayaPengantaran(textarea.value);
                }
            }

            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                fetchAddress(position.lat, position.lng);
            });

            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                fetchAddress(e.latlng.lat, e.latlng.lng);
            });

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;

                    map.setView([userLat, userLng], 16);
                    marker.setLatLng([userLat, userLng]);

                    // Isi alamat otomatis hanya jika kolom masih kosong.
                    // Jangan menimpa alamat yang sudah diketik/dikirim pengguna.
                    const textarea = document.getElementById('alamat_pengantaran');
                    if (textarea.value.trim() === '') {
                        fetchAddress(userLat, userLng);
                    }
                }, function(error) {
                    console.log("GPS tidak diizinkan atau gagal diakses, menggunakan lokasi default.");
                }, {
                    timeout: 10000
                });
            }
            // ======================================
            // FITUR PENCARIAN LOKASI PETA
            // ======================================

            const searchInput = document.getElementById('map-search-input');
            const searchButton = document.getElementById('map-search-button');
            const searchResults = document.getElementById('map-results');
            const searchStatus = document.getElementById('map-search-status');

            async function cariLokasi() {
                const keyword = searchInput.value.trim();

                if (keyword.length < 3) {
                    searchStatus.textContent = 'Masukkan minimal 3 karakter.';
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
                        searchStatus.textContent = 'Lokasi tidak ditemukan. Coba kata kunci lain.';
                        return;
                    }

                    searchStatus.textContent = `${data.length} lokasi ditemukan. Pilih salah satu:`;
                    searchResults.style.display = 'block';

                    data.forEach(function(location) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'map-result';

                        const title = document.createElement('strong');
                        title.textContent = location.name || location.display_name.split(',')[0];

                        const description = document.createElement('small');
                        description.textContent = location.display_name;

                        button.appendChild(title);
                        button.appendChild(description);

                        button.addEventListener('click', function() {
                            const lat = parseFloat(location.lat);
                            const lng = parseFloat(location.lon);

                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                                return;
                            }

                            // Pindahkan peta dan pin.
                            map.setView([lat, lng], 17);
                            marker.setLatLng([lat, lng]);

                            // Isi alamat berdasarkan hasil pencarian.
                            const textarea = document.getElementById('alamat_pengantaran');
                            textarea.value = location.display_name;

                            // Hitung ulang biaya pengantaran.
                            cekBiayaPengantaran(textarea.value);

                            searchInput.value = location.name || location.display_name;
                            searchResults.style.display = 'none';
                            searchStatus.textContent = 'Lokasi berhasil dipilih. Kamu bisa menggeser pin untuk menyesuaikan posisi.';
                        });

                        searchResults.appendChild(button);
                    });

                } catch (error) {
                    console.error('Pencarian lokasi gagal:', error);
                    searchStatus.textContent = 'Pencarian gagal. Periksa koneksi internet dan coba lagi.';
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

            // Jalankan cek awal jika textarea sudah terisi nilai sebelumnya
            const initialAlamat = document.getElementById('alamat_pengantaran').value;
            if (initialAlamat.trim() !== '') {
                cekBiayaPengantaran(initialAlamat);
            }
        });
    </script>

</body>

</html>