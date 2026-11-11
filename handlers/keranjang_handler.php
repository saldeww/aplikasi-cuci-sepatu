
<?php

/**
 * Mengambil dan memvalidasi konfigurasi layanan.
 * Harga selalu diambil dari database, bukan dari input browser.
 */
function validateCartConfig($conn, $layanan_id, $varian_id, $ukuran_id, $addon_ids = [])
{
    $layanan_id = (int) $layanan_id;
    $varian_id = (int) $varian_id;

    $ukuran_id = (
        $ukuran_id === null ||
        $ukuran_id === '' ||
        (int) $ukuran_id === 0
    ) ? null : (int) $ukuran_id;

    if ($layanan_id <= 0 || $varian_id <= 0) {
        throw new Exception('Layanan atau varian tidak valid.');
    }

    // Validasi layanan
    // Ambil ukuran aktif pertama jika tersedia
    $stmt = $conn->prepare("
    SELECT id
    FROM layanan
    WHERE id = ?
      AND status = 'Aktif'
    LIMIT 1
");

    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();

    $layanan = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$layanan) {
        throw new Exception('Layanan tidak tersedia.');
    }

    // Validasi varian
    $stmt = $conn->prepare("
        SELECT id, harga
        FROM layanan_varian
        WHERE id = ?
          AND layanan_id = ?
          AND status = 'Aktif'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $varian_id, $layanan_id);
    $stmt->execute();
    $varian = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$varian) {
        throw new Exception('Varian layanan tidak valid.');
    }

    // Cek apakah layanan memiliki ukuran aktif
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM layanan_ukuran
        WHERE layanan_id = ? AND status = 'Aktif'
    ");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();
    $ukuran_count = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $ukuran = null;

    if ($ukuran_id !== null) {
        $stmt = $conn->prepare("
            SELECT id, harga_tambahan
            FROM layanan_ukuran
            WHERE id = ?
              AND layanan_id = ?
              AND status = 'Aktif'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $ukuran_id, $layanan_id);
        $stmt->execute();
        $ukuran = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ukuran) {
            throw new Exception('Ukuran layanan tidak valid.');
        }
    } elseif ($ukuran_count > 0) {
        throw new Exception('Silakan pilih ukuran layanan.');
    }

    // Normalisasi addon
    $addon_ids = array_values(array_unique(array_filter(
        array_map('intval', (array) $addon_ids),
        fn($id) => $id > 0
    )));

    $addons = [];

    if (!empty($addon_ids)) {
        $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));

        $sql = "
            SELECT id, nama_addon, harga, wajib
            FROM layanan_addon
            WHERE layanan_id = ?
              AND status = 'Aktif'
              AND id IN ($placeholders)
        ";

        $stmt = $conn->prepare($sql);
        $types = 'i' . str_repeat('i', count($addon_ids));
        $params = array_merge([$layanan_id], $addon_ids);

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $addons[(int) $row['id']] = $row;
        }

        $stmt->close();
    }

    // Pastikan semua addon yang dikirim memang milik layanan tersebut
    if (count($addons) !== count($addon_ids)) {
        throw new Exception('Terdapat addon yang tidak valid.');
    }

    // Validasi addon wajib
    $stmt = $conn->prepare("
        SELECT id
        FROM layanan_addon
        WHERE layanan_id = ?
          AND status = 'Aktif'
          AND wajib = 1
    ");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($required = $result->fetch_assoc()) {
        if (!isset($addons[(int) $required['id']])) {
            $stmt->close();
            throw new Exception('Addon wajib belum dipilih.');
        }
    }

    $stmt->close();

    return [
        'varian' => $varian,
        'ukuran' => $ukuran,
        'addons' => $addons
    ];
}


/**
 * Menyimpan addon untuk satu item keranjang.
 * Harga addon disimpan sebagai snapshot.
 */
function saveCartAddons($conn, $keranjang_id, $addons)
{
    $stmt = $conn->prepare("
        DELETE FROM keranjang_addon
        WHERE keranjang_id = ?
    ");
    $stmt->bind_param("i", $keranjang_id);
    $stmt->execute();
    $stmt->close();

    if (empty($addons)) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO keranjang_addon
            (keranjang_id, addon_id, harga_snapshot)
        VALUES (?, ?, ?)
    ");

    foreach ($addons as $addon) {
        $addon_id = (int) $addon['id'];
        $harga = (float) $addon['harga'];

        $stmt->bind_param(
            "iid",
            $keranjang_id,
            $addon_id,
            $harga
        );

        $stmt->execute();
    }

    $stmt->close();
}


/**
 * Memperbarui konfigurasi satu item keranjang.
 */
function saveCartConfig($conn, $user_id, $keranjang_id, $data)
{
    $stmt = $conn->prepare("
        SELECT k.id, k.layanan_id
        FROM keranjang k
        INNER JOIN layanan l ON l.id = k.layanan_id
        WHERE k.id = ?
          AND k.user_id = ?
          AND l.status = 'Aktif'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param("ii", $keranjang_id, $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cart) {
        throw new Exception('Item keranjang tidak ditemukan.');
    }

    $layanan_id = (int) $cart['layanan_id'];

    $varian_id = (int) ($data['varian_id'] ?? 0);
    $ukuran_input = $data['ukuran_id'] ?? null;

    $ukuran_id = (
        $ukuran_input === null ||
        $ukuran_input === '' ||
        (int) $ukuran_input === 0
    ) ? null : (int) $ukuran_input;
    $addon_ids = $data['addon_ids'] ?? [];

    $config = validateCartConfig(
        $conn,
        $layanan_id,
        $varian_id,
        $ukuran_id,
        $addon_ids
    );

    $jumlah = isset($data['jumlah']) ? (int) $data['jumlah'] : null;

    if ($jumlah !== null && ($jumlah < 1 || $jumlah > 99)) {
        throw new Exception('Jumlah item harus antara 1 sampai 99.');
    }

    $catatan = trim($data['catatan'] ?? '');

    if (strlen($catatan) > 1000) {
        throw new Exception('Catatan maksimal 1000 karakter.');
    }

    $stmt = $conn->prepare("
        UPDATE keranjang
        SET varian_id = ?,
            ukuran_id = ?,
            catatan = ?,
            jumlah = COALESCE(?, jumlah)
        WHERE id = ?
          AND user_id = ?
    ");

    $stmt->bind_param(
        "iisiii",
        $varian_id,
        $ukuran_id,
        $catatan,
        $jumlah,
        $keranjang_id,
        $user_id
    );

    $stmt->execute();
    $stmt->close();

    saveCartAddons($conn, $keranjang_id, $config['addons']);
}


/**
 * 1. ADD CART
 */
function handleAddCart($conn, $user_id)
{
    $layanan_id = (int) ($_POST['layanan_id'] ?? 0);

    if ($layanan_id <= 0) {
        throw new Exception('Layanan tidak valid.');
    }

    // Ambil varian aktif pertama
    $stmt = $conn->prepare("
        SELECT id
        FROM layanan_varian
        WHERE layanan_id = ?
          AND status = 'Aktif'
        ORDER BY urutan ASC, id ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();
    $varian = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$varian) {
        throw new Exception('Layanan belum memiliki varian aktif.');
    }

    // Ambil ukuran aktif pertama jika tersedia
    $stmt = $conn->prepare("
        SELECT id
        FROM layanan_ukuran
        WHERE layanan_id = ?
          AND status = 'Aktif'
        ORDER BY urutan ASC, id ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();
    $ukuran = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $varian_id = (int) $varian['id'];
    $ukuran_id = $ukuran ? (int) $ukuran['id'] : null;

    // Ambil addon wajib secara otomatis
    $stmt = $conn->prepare("
        SELECT id, nama_addon, harga, wajib
        FROM layanan_addon
        WHERE layanan_id = ?
          AND status = 'Aktif'
          AND wajib = 1
    ");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();

    $required_addons = [];

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $required_addons[(int) $row['id']] = $row;
    }

    $stmt->close();

    // Validasi layanan dan konfigurasi default
    validateCartConfig(
        $conn,
        $layanan_id,
        $varian_id,
        $ukuran_id,
        array_keys($required_addons)
    );

    $conn->begin_transaction();

    try {
        $jumlah = 1;
        $catatan = '';

        $stmt = $conn->prepare("
    INSERT INTO keranjang
        (user_id, layanan_id, varian_id, ukuran_id, jumlah, catatan)
    VALUES (?, ?, ?, ?, ?, ?)
");

        $stmt->bind_param(
            "iiiiis",
            $user_id,
            $layanan_id,
            $varian_id,
            $ukuran_id,
            $jumlah,
            $catatan
        );

        $stmt->execute();
        $keranjang_id = $conn->insert_id;
        $stmt->close();

        saveCartAddons($conn, $keranjang_id, $required_addons);

        $conn->commit();

        $_SESSION['cart_message'] = 'Layanan berhasil ditambahkan ke keranjang.';
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}


/**
 * 2. UPDATE SATU ITEM
 */
function handleUpdateCart($conn, $user_id)
{
    $keranjang_id = (int) ($_POST['keranjang_id'] ?? 0);

    if ($keranjang_id <= 0) {
        throw new Exception('Item keranjang tidak valid.');
    }

    $conn->begin_transaction();

    try {
        saveCartConfig(
            $conn,
            $user_id,
            $keranjang_id,
            $_POST
        );

        $conn->commit();

        $_SESSION['cart_message'] = 'Keranjang berhasil diperbarui.';
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}


/**
 * 3. UPDATE SEMUA ITEM SEBELUM CHECKOUT
 */
function handleUpdateAllCart($conn, $user_id)
{
    $items = $_POST['items'] ?? [];

    if (!is_array($items) || empty($items)) {
        throw new Exception('Keranjang kamu masih kosong.');
    }

    $conn->begin_transaction();

    try {
        // Ambil seluruh ID keranjang milik user
        $stmt = $conn->prepare("
            SELECT id
            FROM keranjang
            WHERE user_id = ?
            ORDER BY id ASC
            FOR UPDATE
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $database_ids = [];

        while ($row = $result->fetch_assoc()) {
            $database_ids[] = (int) $row['id'];
        }

        $stmt->close();

        $submitted_ids = array_map('intval', array_keys($items));

        sort($database_ids);
        sort($submitted_ids);

        // Pastikan tidak ada item yang terlewat atau disisipkan
        if ($database_ids !== $submitted_ids) {
            throw new Exception(
                'Isi keranjang berubah. Silakan muat ulang halaman.'
            );
        }

        foreach ($items as $keranjang_id => $data) {
            $keranjang_id = (int) $keranjang_id;

            if (!is_array($data)) {
                throw new Exception('Konfigurasi keranjang tidak valid.');
            }

            saveCartConfig(
                $conn,
                $user_id,
                $keranjang_id,
                $data
            );
        }

        $conn->commit();

        $_SESSION['cart_message'] = 'Keranjang berhasil disimpan. Silakan lanjut checkout.';
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}


/**
 * 4. DELETE ITEM
 */
function handleDeleteCart($conn, $user_id)
{
    $keranjang_id = (int) ($_POST['keranjang_id'] ?? 0);

    if ($keranjang_id <= 0) {
        throw new Exception('Item keranjang tidak valid.');
    }

    $stmt = $conn->prepare("
        DELETE FROM keranjang
        WHERE id = ?
          AND user_id = ?
    ");

    $stmt->bind_param("ii", $keranjang_id, $user_id);
    $stmt->execute();

    $deleted = $stmt->affected_rows;
    $stmt->close();

    if (!$deleted) {
        throw new Exception('Item tidak ditemukan atau sudah dihapus.');
    }

    $_SESSION['cart_message'] = 'Item berhasil dihapus dari keranjang.';
}


/**
 * ROUTER REQUEST KERANJANG
 */

function handleCartRequest($conn, $user_id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $is_ajax = (
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    );

    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        if ($is_ajax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Sesi keamanan tidak valid. Muat ulang halaman.'
            ]);
            exit;
        }

        $_SESSION['cart_error'] = 'Sesi keamanan tidak valid. Silakan coba lagi.';
        redirectCart();
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add':
                handleAddCart($conn, $user_id);
                break;

            case 'update_config':
                handleUpdateCart($conn, $user_id);
                break;

            case 'update_all_checkout':
                handleUpdateAllCart($conn, $user_id);

                header('Location: checkout.php');
                exit;

            case 'delete':
                handleDeleteCart($conn, $user_id);
                break;

            default:
                throw new Exception('Aksi keranjang tidak dikenali.');
        }

        // Respons khusus untuk auto-save
        if ($is_ajax && $action === 'update_config') {
            $cart = getCartItems($conn, $user_id);

            $updated_item = null;

            foreach ($cart['items'] as $item) {
                if ((int) $item['keranjang_id'] === (int) ($_POST['keranjang_id'] ?? 0)) {
                    $updated_item = $item;
                    break;
                }
            }

            if (!$updated_item) {
                throw new Exception('Data keranjang tidak ditemukan.');
            }

            header('Content-Type: application/json');

            echo json_encode([
                'success' => true,
                'message' => 'Perubahan berhasil disimpan.',
                'item' => [
                    'harga_satuan' => (float) $updated_item['harga_satuan'],
                    'subtotal' => (float) $updated_item['subtotal']
                ],
                'cart' => [
                    'total' => (float) $cart['total'],
                    'total_item' => (int) $cart['total_item']
                ]
            ]);

            exit;
        }
    } catch (Throwable $e) {
        error_log('Cart handler error: ' . $e->getMessage());

        if ($is_ajax) {
            http_response_code(422);
            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);

            exit;
        }

        $_SESSION['cart_error'] = $e->getMessage();
    }

    redirectCart();
}
