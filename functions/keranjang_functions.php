
<?php

function redirectCart()
{
    header('Location: keranjang.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL ISI KERANJANG
|--------------------------------------------------------------------------
*/

function getCartItems($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT
            k.id AS keranjang_id,
            k.layanan_id,
            k.varian_id,
            k.ukuran_id,
            k.jumlah,
            k.catatan,

            l.nama,
            l.gambar,
            l.waktu,
            l.status AS status_layanan,

            c.nama AS kategori,

            v.nama_varian,
            v.durasi_label,
            v.harga AS harga_varian,

            u.kode_ukuran,
            u.nama_ukuran,
            u.harga_tambahan AS harga_ukuran,

            COALESCE((
                SELECT SUM(ka.harga_snapshot)
                FROM keranjang_addon ka
                WHERE ka.keranjang_id = k.id
            ), 0) AS total_addon,

            (
                COALESCE(v.harga, 0)
                + COALESCE(u.harga_tambahan, 0)
                + COALESCE((
                    SELECT SUM(ka.harga_snapshot)
                    FROM keranjang_addon ka
                    WHERE ka.keranjang_id = k.id
                ), 0)
            ) AS harga_satuan,

            (
                (
                    COALESCE(v.harga, 0)
                    + COALESCE(u.harga_tambahan, 0)
                    + COALESCE((
                        SELECT SUM(ka.harga_snapshot)
                        FROM keranjang_addon ka
                        WHERE ka.keranjang_id = k.id
                    ), 0)
                ) * k.jumlah
            ) AS subtotal

        FROM keranjang k

        INNER JOIN layanan l
            ON l.id = k.layanan_id

        INNER JOIN category c
            ON c.id = l.category_id

        LEFT JOIN layanan_varian v
            ON v.id = k.varian_id

        LEFT JOIN layanan_ukuran u
            ON u.id = k.ukuran_id

        WHERE k.user_id = ?

        ORDER BY k.created_at DESC, k.id DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total = 0;
    $total_item = 0;
    $cart_ready = true;

    foreach ($items as &$item) {

        $item['harga_satuan'] = (float) $item['harga_satuan'];
        $item['subtotal'] = (float) $item['subtotal'];

        $total += $item['subtotal'];
        $total_item += (int) $item['jumlah'];

        if (
            $item['status_layanan'] !== 'Aktif' ||
            empty($item['varian_id'])
        ) {
            $cart_ready = false;
        }
    }

    unset($item);

    return [
        'items' => $items,
        'total' => $total,
        'total_item' => $total_item,
        'cart_ready' => $cart_ready
    ];
}

/*
|--------------------------------------------------------------------------
| AMBIL PILIHAN VARIAN
|--------------------------------------------------------------------------
*/

function getCartVariants($conn)
{
    $result = $conn->query("
        SELECT id, layanan_id, nama_varian, harga
        FROM layanan_varian
        WHERE status = 'Aktif'
        ORDER BY urutan ASC, id ASC
    ");

    $variants = [];

    while ($row = $result->fetch_assoc()) {
        $variants[(int) $row['layanan_id']][] = $row;
    }

    return $variants;
}

/*
|--------------------------------------------------------------------------
| AMBIL PILIHAN UKURAN
|--------------------------------------------------------------------------
*/

function getCartSizes($conn)
{
    $result = $conn->query("
        SELECT id, layanan_id, kode_ukuran, nama_ukuran, harga_tambahan
        FROM layanan_ukuran
        WHERE status = 'Aktif'
        ORDER BY urutan ASC, id ASC
    ");

    $sizes = [];

    while ($row = $result->fetch_assoc()) {
        $sizes[(int) $row['layanan_id']][] = $row;
    }

    return $sizes;
}

/*
|--------------------------------------------------------------------------
| AMBIL PILIHAN ADDON
|--------------------------------------------------------------------------
*/

function getCartAddons($conn)
{
    $result = $conn->query("
        SELECT id, layanan_id, nama_addon, deskripsi, harga, wajib
        FROM layanan_addon
        WHERE status = 'Aktif'
        ORDER BY urutan ASC, id ASC
    ");

    $addons = [];

    while ($row = $result->fetch_assoc()) {
        $addons[(int) $row['layanan_id']][] = $row;
    }

    return $addons;
}

/*
|--------------------------------------------------------------------------
| AMBIL ADDON YANG SUDAH DIPILIH
|--------------------------------------------------------------------------
*/

function getSelectedCartAddons($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT
            ka.keranjang_id,
            ka.addon_id,
            ka.harga_snapshot,
            la.nama_addon

        FROM keranjang_addon ka

        INNER JOIN layanan_addon la
            ON la.id = ka.addon_id

        INNER JOIN keranjang k
            ON k.id = ka.keranjang_id

        WHERE k.user_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $selected = [];

    while ($row = $result->fetch_assoc()) {
        $selected[(int) $row['keranjang_id']][] = $row;
    }

    $stmt->close();

    return $selected;
}
