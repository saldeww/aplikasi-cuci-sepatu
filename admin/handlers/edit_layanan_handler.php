
<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

function kembali($type, $message, $id)
{
    $_SESSION['flash_layanan'] = [
        'type' => $type,
        'message' => $message
    ];

    header('Location: ../edit_layanan.php?id=' . (int)$id);
    exit;
}

function inputArray($data, $key)
{
    return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../layanan.php');
    exit;
}

$id = filter_input(INPUT_POST, 'layanan_id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: ../layanan.php');
    exit;
}

if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_layanan'] ?? '', $_POST['csrf_token'])
) {
    kembali('error', 'Token keamanan tidak valid. Silakan coba lagi.', $id);
}

$nama = trim($_POST['nama'] ?? '');
$category_id = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
$deskripsi = trim($_POST['deskripsi'] ?? '');
$harga = $_POST['harga'] ?? '';
$waktu = trim($_POST['waktu'] ?? '');
$badge = trim($_POST['badge'] ?? '');
$status = $_POST['status'] ?? 'Aktif';

if (
    $nama === '' ||
    !$category_id ||
    !is_numeric($harga) ||
    (float)$harga < 0 ||
    !in_array($status, ['Aktif', 'Nonaktif'], true)
) {
    kembali('error', 'Data utama layanan tidak valid.', $id);
}

$check = $conn->prepare("SELECT id, gambar FROM layanan WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$layanan_lama = $check->get_result()->fetch_assoc();

if (!$layanan_lama) {
    kembali('error', 'Layanan tidak ditemukan.', $id);
}

$kategori = $conn->prepare("SELECT id FROM category WHERE id = ?");
$kategori->bind_param("i", $category_id);
$kategori->execute();

if (!$kategori->get_result()->fetch_assoc()) {
    kembali('error', 'Kategori tidak ditemukan.', $id);
}

$gambar_lama = $layanan_lama['gambar'];
$gambar_baru = $gambar_lama;
$file_gambar_baru = null;

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        kembali('error', 'Upload gambar gagal.', $id);
    }

    if ($_FILES['gambar']['size'] > 3 * 1024 * 1024) {
        kembali('error', 'Ukuran gambar maksimal 3 MB.', $id);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['gambar']['tmp_name']);

    $format = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($format[$mime])) {
        kembali('error', 'Format gambar harus JPG, PNG, atau WEBP.', $id);
    }

    $folder = __DIR__ . '/../../assets/uploads/layanan/';

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        kembali('error', 'Folder upload tidak dapat dibuat.', $id);
    }

    $nama_file = bin2hex(random_bytes(16)) . '.' . $format[$mime];
    $tujuan = $folder . $nama_file;

    if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan)) {
        kembali('error', 'Gagal menyimpan gambar.', $id);
    }

    $gambar_baru = 'assets/uploads/layanan/' . $nama_file;
    $file_gambar_baru = $tujuan;
}

$varian = $_POST['varian'] ?? [];
$ukuran = $_POST['ukuran'] ?? [];
$addon = $_POST['addon'] ?? [];

$varian_id = inputArray($varian, 'id');
$varian_nama = inputArray($varian, 'nama');
$varian_durasi = inputArray($varian, 'durasi');
$varian_harga = inputArray($varian, 'harga');
$varian_status = inputArray($varian, 'status');

$ukuran_id = inputArray($ukuran, 'id');
$ukuran_kode = inputArray($ukuran, 'kode');
$ukuran_nama = inputArray($ukuran, 'nama');
$ukuran_harga = inputArray($ukuran, 'harga');
$ukuran_status = inputArray($ukuran, 'status');

$addon_id = inputArray($addon, 'id');
$addon_nama = inputArray($addon, 'nama');
$addon_deskripsi = inputArray($addon, 'deskripsi');
$addon_harga = inputArray($addon, 'harga');
$addon_wajib = inputArray($addon, 'wajib');
$addon_status = inputArray($addon, 'status');

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        UPDATE layanan
        SET category_id = ?, nama = ?, deskripsi = ?, harga = ?,
            waktu = ?, gambar = ?, badge = ?, status = ?
        WHERE id = ?
    ");

    $harga_float = (float)$harga;
    $stmt->bind_param(
        "issdssssi",
        $category_id,
        $nama,
        $deskripsi,
        $harga_float,
        $waktu,
        $gambar_baru,
        $badge,
        $status,
        $id
    );
    $stmt->execute();

    /*
     * VARIAN
     * Baris lama yang dihapus dari form akan dinonaktifkan.
     */
    $stmt = $conn->prepare("
        UPDATE layanan_varian
        SET status = 'Nonaktif'
        WHERE layanan_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt_update = $conn->prepare("
        UPDATE layanan_varian
        SET nama_varian = ?, durasi_label = ?, harga = ?, urutan = ?, status = ?
        WHERE id = ? AND layanan_id = ?
    ");

    $stmt_insert = $conn->prepare("
        INSERT INTO layanan_varian
        (layanan_id, nama_varian, durasi_label, harga, urutan, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $urutan = 0;

    for ($i = 0; $i < count($varian_nama); $i++) {
        $nama_v = trim($varian_nama[$i] ?? '');
        $durasi_v = trim($varian_durasi[$i] ?? '');
        $harga_v = $varian_harga[$i] ?? '';
        $row_id = (int)($varian_id[$i] ?? 0);
        $status_v = $varian_status[$i] ?? 'Aktif';

        if ($nama_v === '' && $durasi_v === '' && $harga_v === '') {
            continue;
        }

        if ($nama_v === '' || !is_numeric($harga_v) || (float)$harga_v < 0) {
            throw new Exception('Nama dan harga setiap varian harus diisi dengan benar.');
        }

        if (!in_array($status_v, ['Aktif', 'Nonaktif'], true)) {
            throw new Exception('Status varian tidak valid.');
        }

        $urutan++;
        $harga_v = (float)$harga_v;

        if ($row_id > 0) {
            $stmt_update->bind_param(
                "ssd isii",
                $nama_v,
                $durasi_v,
                $harga_v,
                $urutan,
                $status_v,
                $row_id,
                $id
            );
            $stmt_update->execute();

            if ($stmt_update->affected_rows === 0) {
                $verify = $conn->prepare("SELECT id FROM layanan_varian WHERE id = ? AND layanan_id = ?");
                $verify->bind_param("ii", $row_id, $id);
                $verify->execute();

                if (!$verify->get_result()->fetch_assoc()) {
                    throw new Exception('Varian tidak ditemukan atau bukan milik layanan ini.');
                }
            }
        } else {
            $stmt_insert->bind_param(
                "issdis",
                $id,
                $nama_v,
                $durasi_v,
                $harga_v,
                $urutan,
                $status_v
            );
            $stmt_insert->execute();
        }
    }

    $cek_varian = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM layanan_varian
        WHERE layanan_id = ? AND status = 'Aktif'
    ");
    $cek_varian->bind_param("i", $id);
    $cek_varian->execute();
    $jumlah_varian = (int)$cek_varian->get_result()->fetch_assoc()['total'];

    if ($jumlah_varian < 1) {
        throw new Exception('Minimal harus ada satu varian layanan yang aktif.');
    }

    /*
     * UKURAN
     */
    $stmt = $conn->prepare("
        UPDATE layanan_ukuran
        SET status = 'Nonaktif'
        WHERE layanan_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt_update = $conn->prepare("
        UPDATE layanan_ukuran
        SET kode_ukuran = ?, nama_ukuran = ?, harga_tambahan = ?, urutan = ?, status = ?
        WHERE id = ? AND layanan_id = ?
    ");

    $stmt_insert = $conn->prepare("
        INSERT INTO layanan_ukuran
        (layanan_id, kode_ukuran, nama_ukuran, harga_tambahan, urutan, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $urutan = 0;

    for ($i = 0; $i < count($ukuran_kode); $i++) {
        $kode = strtoupper(trim($ukuran_kode[$i] ?? ''));
        $nama_u = trim($ukuran_nama[$i] ?? '');
        $harga_u = $ukuran_harga[$i] ?? '';
        $row_id = (int)($ukuran_id[$i] ?? 0);
        $status_u = $ukuran_status[$i] ?? 'Aktif';

        if ($kode === '' && $nama_u === '' && $harga_u === '') {
            continue;
        }

        if ($kode === '' || $nama_u === '' || !is_numeric($harga_u) || (float)$harga_u < 0) {
            throw new Exception('Kode, nama, dan harga ukuran harus diisi dengan benar.');
        }

        if (!in_array($status_u, ['Aktif', 'Nonaktif'], true)) {
            throw new Exception('Status ukuran tidak valid.');
        }

        $urutan++;
        $harga_u = (float)$harga_u;

        if ($row_id > 0) {
            $stmt_update->bind_param(
                "ssdisii",
                $kode,
                $nama_u,
                $harga_u,
                $urutan,
                $status_u,
                $row_id,
                $id
            );
            $stmt_update->execute();
        } else {
            $stmt_insert->bind_param(
                "issdis",
                $id,
                $kode,
                $nama_u,
                $harga_u,
                $urutan,
                $status_u
            );
            $stmt_insert->execute();
        }
    }

    /*
     * ADD-ON
     */
    $stmt = $conn->prepare("
        UPDATE layanan_addon
        SET status = 'Nonaktif'
        WHERE layanan_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt_update = $conn->prepare("
        UPDATE layanan_addon
        SET nama_addon = ?, deskripsi = ?, harga = ?, wajib = ?, urutan = ?, status = ?
        WHERE id = ? AND layanan_id = ?
    ");

    $stmt_insert = $conn->prepare("
        INSERT INTO layanan_addon
        (layanan_id, nama_addon, deskripsi, harga, wajib, urutan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $urutan = 0;

    for ($i = 0; $i < count($addon_nama); $i++) {
        $nama_a = trim($addon_nama[$i] ?? '');
        $deskripsi_a = trim($addon_deskripsi[$i] ?? '');
        $harga_a = $addon_harga[$i] ?? '';
        $wajib_a = (int)($addon_wajib[$i] ?? 0);
        $row_id = (int)($addon_id[$i] ?? 0);
        $status_a = $addon_status[$i] ?? 'Aktif';

        if ($nama_a === '' && $deskripsi_a === '' && $harga_a === '') {
            continue;
        }

        if ($nama_a === '' || !is_numeric($harga_a) || (float)$harga_a < 0) {
            throw new Exception('Nama dan harga add-on harus diisi dengan benar.');
        }

        if (!in_array($status_a, ['Aktif', 'Nonaktif'], true) || !in_array($wajib_a, [0, 1], true)) {
            throw new Exception('Status atau pilihan wajib add-on tidak valid.');
        }

        $urutan++;
        $harga_a = (float)$harga_a;

        if ($row_id > 0) {
            $stmt_update->bind_param(
                "ssd iisii",
                $nama_a,
                $deskripsi_a,
                $harga_a,
                $wajib_a,
                $urutan,
                $status_a,
                $row_id,
                $id
            );
            $stmt_update->execute();
        } else {
            $stmt_insert->bind_param(
                "issdiis",
                $id,
                $nama_a,
                $deskripsi_a,
                $harga_a,
                $wajib_a,
                $urutan,
                $status_a
            );
            $stmt_insert->execute();
        }
    }

    $conn->commit();

    if (
        $file_gambar_baru &&
        $gambar_lama &&
        str_starts_with($gambar_lama, 'assets/uploads/layanan/')
    ) {
        $path_lama = __DIR__ . '/../../' . $gambar_lama;

        if (is_file($path_lama)) {
            unlink($path_lama);
        }
    }

    kembali('success', 'Layanan berhasil diperbarui.', $id);
} catch (Throwable $e) {
    $conn->rollback();

    if ($file_gambar_baru && is_file($file_gambar_baru)) {
        unlink($file_gambar_baru);
    }

    error_log('Edit layanan gagal: ' . $e->getMessage());

    kembali('error', $e->getMessage(), $id);
}
