
<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($harga)
{
    return 'Rp ' . number_format((float) $harga, 0, ',', '.');
}
// 1. Definisi fungsi untuk mengambil daftar layanan aktif
function layananlist($conn)
{
    $result = $conn->query("
        SELECT id, nama, status,harga,gambar
        FROM layanan
        WHERE status = 'Aktif'
    ");

    $layananList = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $layananList[] = $row;
        }
    }

    return $layananList;
}

// 2. Panggil fungsi untuk mengisi $layanan_list
$layanan_list = layananlist($conn);

// 3. Ambil 5 data pertama untuk layanan unggulan
$layanan_unggulan = array_slice($layanan_list, 0, 5);
?>
