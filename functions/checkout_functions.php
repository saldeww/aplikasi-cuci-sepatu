<?php

class CheckoutValidationException extends Exception {}

/*
|--------------------------------------------------------------------------
| AMBIL DATA KERANJANG UNTUK CHECKOUT
|--------------------------------------------------------------------------
*/

function getCheckoutCart($conn, $user_id)
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

            v.nama_varian,
            v.durasi_label,
            v.harga AS harga_varian,
            v.status AS status_varian,

            u.kode_ukuran,
            u.nama_ukuran,
            u.harga_tambahan AS harga_ukuran,
            u.status AS status_ukuran,
            u.layanan_id AS ukuran_layanan_id,

            EXISTS (
                SELECT 1
                FROM layanan_ukuran ux
                WHERE ux.layanan_id = k.layanan_id
                  AND ux.status = 'Aktif'
            ) AS has_sizes

        FROM keranjang k

        INNER JOIN layanan l
            ON l.id = k.layanan_id

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

    // Ambil addon yang dipilih.
    $stmt = $conn->prepare("
        SELECT
            ka.keranjang_id,
            ka.addon_id,
            ka.harga_snapshot,
            la.nama_addon,
            la.status AS status_addon,
            la.layanan_id AS addon_layanan_id

        FROM keranjang_addon ka

        LEFT JOIN layanan_addon la
            ON la.id = ka.addon_id

        INNER JOIN keranjang k
            ON k.id = ka.keranjang_id

        WHERE k.user_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $addon_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $addons_by_cart = [];

    foreach ($addon_rows as $addon) {
        $addons_by_cart[(int) $addon['keranjang_id']][] = $addon;
    }

    $total_harga = 0;
    $cart_invalid = false;

    foreach ($items as &$item) {

        $cart_id = (int) $item['keranjang_id'];

        $item['addons'] = $addons_by_cart[$cart_id] ?? [];

        $harga_dasar = (float) ($item['harga_varian'] ?? 0);
        $harga_ukuran = (float) ($item['harga_ukuran'] ?? 0);
        $harga_addon = 0;

        foreach ($item['addons'] as $addon) {

            $harga_addon += (float) $addon['harga_snapshot'];

            if (
                $addon['status_addon'] !== 'Aktif' ||
                (int) $addon['addon_layanan_id'] !== (int) $item['layanan_id']
            ) {
                $cart_invalid = true;
            }
        }

        $item['harga_dasar'] = $harga_dasar;
        $item['harga_ukuran'] = $harga_ukuran;
        $item['harga_addon'] = $harga_addon;

        $item['harga_satuan'] =
            $harga_dasar + $harga_ukuran + $harga_addon;

        $item['subtotal'] =
            $item['harga_satuan'] * (int) $item['jumlah'];

        $total_harga += $item['subtotal'];

        if (
            $item['status_layanan'] !== 'Aktif' ||
            empty($item['varian_id']) ||
            $item['status_varian'] !== 'Aktif' ||
            (
                $item['has_sizes'] &&
                (
                    empty($item['ukuran_id']) ||
                    $item['status_ukuran'] !== 'Aktif' ||
                    (int) $item['ukuran_layanan_id'] !== (int) $item['layanan_id']
                )
            )
        ) {
            $cart_invalid = true;
        }
    }

    unset($item);

    return [
        'items' => $items,
        'total' => $total_harga,
        'invalid' => $cart_invalid
    ];
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PELANGGAN
|--------------------------------------------------------------------------
*/

function getCheckoutUser($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT nama, no_wa
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $user;
}


/*
|--------------------------------------------------------------------------
| BUAT PESANAN
|--------------------------------------------------------------------------
*/

function createCheckoutOrder(
    $conn,
    $user_id,
    $nama,
    $no_wa,
    $alamat,
    $metode_pembayaran,
    $metode_pengantaran,
    $jadwal_sql
) {
    $transaction_started = false;

    try {
        mysqli_begin_transaction($conn);
        $transaction_started = true;

        /*
        |--------------------------------------------------------------------------
        | KUNCI DAN AMBIL DATA KERANJANG
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                k.id AS keranjang_id,
                k.layanan_id,
                k.varian_id,
                k.ukuran_id,
                k.jumlah,
                k.catatan,

                l.nama AS nama_layanan,
                l.status AS status_layanan,

                v.nama_varian,
                v.durasi_label,
                v.harga AS harga_dasar,
                v.status AS status_varian,

                u.kode_ukuran,
                u.nama_ukuran,
                u.harga_tambahan AS harga_ukuran,
                u.status AS status_ukuran,
                u.layanan_id AS ukuran_layanan_id,

                EXISTS (
                    SELECT 1
                    FROM layanan_ukuran ux
                    WHERE ux.layanan_id = k.layanan_id
                      AND ux.status = 'Aktif'
                ) AS has_sizes

            FROM keranjang k

            INNER JOIN layanan l
                ON l.id = k.layanan_id

            LEFT JOIN layanan_varian v
                ON v.id = k.varian_id

            LEFT JOIN layanan_ukuran u
                ON u.id = k.ukuran_id

            WHERE k.user_id = ?

            FOR UPDATE
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $checkout_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($checkout_items)) {
            throw new CheckoutValidationException('Keranjang kamu kosong.');
        }

        $total_harga = 0;
        $details = [];

        /*
        |--------------------------------------------------------------------------
        | VALIDASI DAN HITUNG SETIAP ITEM
        |--------------------------------------------------------------------------
        */

        foreach ($checkout_items as $item) {

            $keranjang_id = (int) $item['keranjang_id'];
            $layanan_id = (int) $item['layanan_id'];

            if ($item['status_layanan'] !== 'Aktif') {
                throw new CheckoutValidationException('Ada layanan yang sudah tidak aktif.');
            }

            if (
                empty($item['varian_id']) ||
                $item['status_varian'] !== 'Aktif'
            ) {
                throw new CheckoutValidationException(
                    'Ada varian layanan yang tidak valid. Periksa keranjang.'
                );
            }

            if ($item['has_sizes']) {

                if (
                    empty($item['ukuran_id']) ||
                    $item['status_ukuran'] !== 'Aktif' ||
                    (int) $item['ukuran_layanan_id'] !== $layanan_id
                ) {
                    throw new CheckoutValidationException(
                        'Ada ukuran barang yang belum dipilih atau tidak valid.'
                    );
                }
            }

            $jumlah = (int) $item['jumlah'];

            if ($jumlah < 1 || $jumlah > 99) {
                throw new CheckoutValidationException('Jumlah item tidak valid.');
            }

            $harga_dasar = (float) $item['harga_dasar'];
            $harga_ukuran = (float) ($item['harga_ukuran'] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | AMBIL ADDON TERPILIH
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT
                    ka.addon_id,
                    ka.harga_snapshot,
                    la.nama_addon,
                    la.wajib,
                    la.status,
                    la.layanan_id

                FROM keranjang_addon ka

                LEFT JOIN layanan_addon la
                    ON la.id = ka.addon_id

                WHERE ka.keranjang_id = ?
            ");

            $stmt->bind_param("i", $keranjang_id);
            $stmt->execute();

            $selected_addons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $harga_total_addon = 0;
            $addon_snapshot = [];
            $selected_ids = [];

            foreach ($selected_addons as $addon) {

                if (
                    empty($addon['addon_id']) ||
                    $addon['status'] !== 'Aktif' ||
                    (int) $addon['layanan_id'] !== $layanan_id
                ) {
                    throw new CheckoutValidationException(
                        'Ada addon yang sudah tidak tersedia. Periksa keranjang.'
                    );
                }

                $addon_id = (int) $addon['addon_id'];
                $harga_addon = (float) $addon['harga_snapshot'];

                $selected_ids[] = $addon_id;
                $harga_total_addon += $harga_addon;

                $addon_snapshot[] = [
                    'id' => $addon_id,
                    'nama' => $addon['nama_addon'],
                    'harga' => $harga_addon
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI ADDON WAJIB
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT id, nama_addon
                FROM layanan_addon
                WHERE layanan_id = ?
                  AND status = 'Aktif'
                  AND wajib = 1
            ");

            $stmt->bind_param("i", $layanan_id);
            $stmt->execute();

            $required_addons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($required_addons as $required) {

                if (!in_array((int) $required['id'], $selected_ids, true)) {
                    throw new CheckoutValidationException(
                        'Addon wajib ' . $required['nama_addon'] . ' belum dipilih.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | HITUNG TOTAL HARGA
            |--------------------------------------------------------------------------
            */

            $harga_tambahan = $harga_ukuran + $harga_total_addon;
            $harga_satuan = $harga_dasar + $harga_tambahan;
            $subtotal = $harga_satuan * $jumlah;

            $total_harga += $subtotal;

            $varian_snapshot = $item['nama_varian'];

            if (!empty($item['durasi_label'])) {
                $varian_snapshot .= ' - ' . $item['durasi_label'];
            }

            $ukuran_snapshot = $item['nama_ukuran'] ?? '';

            if (!empty($item['kode_ukuran'])) {
                $ukuran_snapshot .= ' (' . $item['kode_ukuran'] . ')';
            }

            $snapshot_json = json_encode([
                'addons' => $addon_snapshot,
                'catatan' => $item['catatan'] ?? ''
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($snapshot_json === false) {
                throw new CheckoutValidationException('Gagal membuat snapshot pesanan.');
            }

            $details[] = [
                'layanan_id' => $layanan_id,
                'nama_layanan' => $item['nama_layanan'],
                'varian' => $varian_snapshot,
                'ukuran' => $ukuran_snapshot,
                'snapshot_json' => $snapshot_json,
                'harga_satuan' => $harga_satuan,
                'harga_dasar' => $harga_dasar,
                'harga_tambahan' => $harga_tambahan,
                'jumlah' => $jumlah,
                'addons' => $addon_snapshot
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CEK BIAYA TAMBAHAN LUAR KOTA (DI LUAR SUKABUMI)
        |--------------------------------------------------------------------------
        */
        $biaya_tambahan_luar_kota = 0;
        if (stripos($alamat, 'sukabumi') === false) {
            $biaya_tambahan_luar_kota = 15000;
        }
        $total_harga += $biaya_tambahan_luar_kota;

        /*
        |--------------------------------------------------------------------------
        | SIMPAN PESANAN UTAMA
        |--------------------------------------------------------------------------
        */

        $no_pesanan = 'CD-' . date('YmdHis') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        $stmt = $conn->prepare("
            INSERT INTO pesanan (
                no_pesanan,
                user_id,
                nama_lengkap,
                no_wa,
                alamat_pengantaran,
                total_harga,
                status_pesanan,
                status_pembayaran,
                metode_pembayaran,
                metode_pengantaran,
                jadwal_pelaksanaan
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                'Pending', 'Unpaid', ?, ?, ?
            )
        ");

        $stmt->bind_param(
            "sisssdsss",
            $no_pesanan,
            $user_id,
            $nama,
            $no_wa,
            $alamat,
            $total_harga,
            $metode_pembayaran,
            $metode_pengantaran,
            $jadwal_sql
        );

        $stmt->execute();

        $pesanan_id = $conn->insert_id;

        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DETAIL PESANAN
        |--------------------------------------------------------------------------
        */

        $stmt_detail = $conn->prepare("
            INSERT INTO detail_pesanan (
                pesanan_id,
                layanan_id,
                nama_layanan_snapshot,
                nama_varian_snapshot,
                ukuran_snapshot,
                addon_snapshot,
                harga_satuan,
                harga_dasar,
                harga_tambahan,
                jumlah
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt_addon = $conn->prepare("
            INSERT INTO detail_pesanan_addon (
                detail_pesanan_id,
                addon_id,
                nama_addon_snapshot,
                harga_satuan
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($details as $detail) {

            $layanan_id = $detail['layanan_id'];
            $nama_layanan = $detail['nama_layanan'];
            $nama_varian = $detail['varian'];
            $ukuran = $detail['ukuran'];
            $snapshot = $detail['snapshot_json'];

            $harga_satuan = $detail['harga_satuan'];
            $harga_dasar = $detail['harga_dasar'];
            $harga_tambahan = $detail['harga_tambahan'];
            $jumlah = $detail['jumlah'];

            $stmt_detail->bind_param(
                "iissssdddi",
                $pesanan_id,
                $layanan_id,
                $nama_layanan,
                $nama_varian,
                $ukuran,
                $snapshot,
                $harga_satuan,
                $harga_dasar,
                $harga_tambahan,
                $jumlah
            );

            $stmt_detail->execute();

            $detail_pesanan_id = $conn->insert_id;

            foreach ($detail['addons'] as $addon) {

                $addon_id = (int) $addon['id'];
                $nama_addon = $addon['nama'];
                $harga_addon = (float) $addon['harga'];

                $stmt_addon->bind_param(
                    "iisd",
                    $detail_pesanan_id,
                    $addon_id,
                    $nama_addon,
                    $harga_addon
                );

                $stmt_addon->execute();
            }
        }

        $stmt_detail->close();
        $stmt_addon->close();

        /*
        |--------------------------------------------------------------------------
        | KOSONGKAN KERANJANG DAN COMMIT
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            DELETE FROM keranjang
            WHERE user_id = ?
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        mysqli_commit($conn);

        return $pesanan_id;
    } catch (Throwable $exception) {

        if ($transaction_started) {
            mysqli_rollback($conn);
        }

        throw $exception;
    }
}
