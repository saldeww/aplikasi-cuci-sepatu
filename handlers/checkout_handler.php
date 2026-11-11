
<?php

function handleCheckoutRequest($conn, $user_id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '';
    }

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        return 'Token keamanan tidak cocok. Silakan refresh halaman.';
    }

    $nama = trim($_POST['nama_lengkap'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');
    $alamat = trim($_POST['alamat_pengantaran'] ?? '');

    $metode_pembayaran = $_POST['metode_pembayaran'] ?? '';
    $metode_pengantaran = $_POST['metode_pengantaran'] ?? '';

    $jadwal_input = $_POST['jadwal_pelaksanaan'] ?? '';

    if (
        $nama === '' ||
        $no_wa === '' ||
        $alamat === '' ||
        $jadwal_input === ''
    ) {
        return 'Semua detail pesanan wajib diisi.';
    }

    if (
        mb_strlen($nama) > 150 ||
        mb_strlen($no_wa) > 30 ||
        mb_strlen($alamat) > 1000
    ) {
        return 'Ada informasi pelanggan yang terlalu panjang.';
    }

    if (!in_array($metode_pembayaran, ['QRIS', 'Cash'], true)) {
        return 'Metode pembayaran tidak valid.';
    }

    if (!in_array($metode_pengantaran, ['Antar', 'Antar-Jemput'], true)) {
        return 'Metode pengantaran tidak valid.';
    }

    $jadwal = DateTime::createFromFormat('!Y-m-d\TH:i', $jadwal_input);
    $date_errors = DateTime::getLastErrors();
    $now = new DateTime();

    if (
        !$jadwal ||
        (
            $date_errors !== false &&
            (
                $date_errors['warning_count'] > 0 ||
                $date_errors['error_count'] > 0
            )
        ) ||
        $jadwal <= $now
    ) {
        return 'Jadwal pelaksanaan harus valid dan belum lewat.';
    }

    $jadwal_sql = $jadwal->format('Y-m-d H:i:s');

    try {

        $pesanan_id = createCheckoutOrder(
            $conn,
            $user_id,
            $nama,
            $no_wa,
            $alamat,
            $metode_pembayaran,
            $metode_pengantaran,
            $jadwal_sql
        );

        $_SESSION['flash_pesanan'] = 'Pesanan berhasil dibuat!';

        header('Location: pesanan.php?id=' . $pesanan_id);
        exit;
    } catch (Throwable $exception) {

        error_log('Checkout error: ' . $exception->getMessage());

        return 'Checkout gagal: ' . $exception->getMessage();
    }
}
