<?php

function handleCheckoutRequest($conn, $user_id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '';
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        return 'Token keamanan tidak cocok. Silakan refresh halaman.';
    }

    $nama = trim(is_string($_POST['nama_lengkap'] ?? null)
        ? $_POST['nama_lengkap'] : '');

    $no_wa = trim(is_string($_POST['no_wa'] ?? null)
        ? $_POST['no_wa'] : '');

    $alamat = trim(is_string($_POST['alamat_pengantaran'] ?? null)
        ? $_POST['alamat_pengantaran'] : '');

    $latitude = filter_var(
        $_POST['latitude'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $longitude = filter_var(
        $_POST['longitude'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    if (
        $latitude === false || $latitude === null ||
        $longitude === false || $longitude === null ||
        !is_finite((float) $latitude) ||
        !is_finite((float) $longitude) ||
        $latitude < -90 || $latitude > 90 ||
        $longitude < -180 || $longitude > 180
    ) {
        return 'Silakan pilih lokasi pengantaran melalui peta.';
    }

    if (
        $nama === '' ||
        $no_wa === '' ||
        $alamat === '' ||
        mb_strlen($nama) > 150 ||
        mb_strlen($no_wa) > 30 ||
        mb_strlen($alamat) > 1000
    ) {
        return 'Informasi pelanggan tidak valid atau belum lengkap.';
    }

    if (
        !preg_match('/^[0-9+\s().-]+$/', $no_wa) ||
        strlen(preg_replace('/\D/', '', $no_wa)) < 8 ||
        strlen(preg_replace('/\D/', '', $no_wa)) > 15
    ) {
        return 'Nomor WhatsApp tidak valid.';
    }

    $wilayah_pengantaran = $_POST['wilayah_pengantaran'] ?? '';
    $metode_pembayaran   = $_POST['metode_pembayaran'] ?? '';
    $metode_pengantaran  = $_POST['metode_pengantaran'] ?? '';

    if (!in_array($wilayah_pengantaran, ['kota_sukabumi', 'luar_kota'], true)) {
        return 'Wilayah pengantaran tidak valid.';
    }

    if (!in_array($metode_pembayaran, ['QRIS', 'Cash'], true)) {
        return 'Metode pembayaran tidak valid.';
    }

    if (!in_array($metode_pengantaran, ['Antar-Jemput', 'Antar', 'Jemput'], true)) {
        return 'Metode pengantaran tidak valid.';
    }

    $jadwal_input = $_POST['jadwal_pelaksanaan'] ?? '';

    if (!is_string($jadwal_input) || $jadwal_input === '') {
        return 'Jadwal pelaksanaan wajib diisi.';
    }

    $jadwal = DateTime::createFromFormat(
        '!Y-m-d\TH:i',
        $jadwal_input
    );

    $date_errors = DateTime::getLastErrors();

    if (
        !$jadwal ||
        ($date_errors !== false && (
            $date_errors['warning_count'] > 0 ||
            $date_errors['error_count'] > 0
        )) ||
        $jadwal->format('Y-m-d\TH:i') !== $jadwal_input
    ) {
        return 'Format jadwal pelaksanaan tidak valid.';
    }

    $now = new DateTime();
    $now->setTime(
        (int) $now->format('H'),
        (int) $now->format('i'),
        0
    );

    if ($jadwal < $now) {
        return 'Jadwal pelaksanaan harus belum lewat.';
    }

    $jadwal_sql = $jadwal->format('Y-m-d H:i:s');

    try {

        $pesanan_id = createCheckoutOrder(
            $conn,
            $user_id,
            $nama,
            $no_wa,
            $alamat,
            (float) $latitude,
            (float) $longitude,
            $wilayah_pengantaran,
            $metode_pembayaran,
            $metode_pengantaran,
            $jadwal_sql
        );


        $_SESSION['flash_pesanan'] = 'Pesanan berhasil dibuat!';

        header('Location: pesanan.php?id=' . (int) $pesanan_id);
        exit;
    } catch (CheckoutValidationException $exception) {

        return $exception->getMessage();
    } catch (Throwable $exception) {

        error_log('Checkout error: ' . $exception->getMessage());

        return 'Checkout gagal diproses. Silakan coba lagi.';
    }
}
