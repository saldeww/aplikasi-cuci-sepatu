
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'Admin'
) {
    header('Location: ../../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

function kembaliDenganPesan($type, $message)
{
    $_SESSION['flash_layanan'] = [
        'type' => $type,
        'message' => $message
    ];

    header('Location: ../tambah_layanan.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../layanan.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_layanan']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_layanan'], $token)
) {
    kembaliDenganPesan('error', 'Sesi keamanan tidak valid. Silakan coba lagi.');
}

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'toggle_status') {
    $layanan_id = (int) ($_POST['layanan_id'] ?? 0);

    if ($layanan_id < 1) {
        $_SESSION['flash_layanan'] = [
            'type' => 'error',
            'message' => 'ID layanan tidak valid.'
        ];

        header('Location: ../layanan.php');
        exit;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE layanan
            SET status = CASE
                WHEN status = 'Aktif' THEN 'Nonaktif'
                ELSE 'Aktif'
            END
            WHERE id = ?
        ");

        $stmt->bind_param("i", $layanan_id);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            throw new Exception('Layanan tidak ditemukan atau status tidak berubah.');
        }

        $stmt->close();

        $_SESSION['flash_layanan'] = [
            'type' => 'success',
            'message' => 'Status layanan berhasil diperbarui.'
        ];
    } catch (Throwable $e) {
        error_log('Admin layanan: ' . $e->getMessage());

        $_SESSION['flash_layanan'] = [
            'type' => 'error',
            'message' => 'Gagal memperbarui status layanan.'
        ];
    }

    header('Location: ../layanan.php');
    exit;
}

if ($aksi !== 'tambah') {
    kembaliDenganPesan('error', 'Aksi tidak dikenali.');
}

$nama = trim($_POST['nama'] ?? '');
$category_id = (int) ($_POST['category_id'] ?? 0);
$deskripsi = trim($_POST['deskripsi'] ?? '');
$waktu = trim($_POST['waktu'] ?? '');
$harga = $_POST['harga'] ?? '';
$badge = trim($_POST['badge'] ?? '');
$status = $_POST['status'] ?? 'Aktif';

if ($nama === '' || $category_id < 1 || !is_numeric($harga) || (float) $harga < 0) {
    kembaliDenganPesan('error', 'Nama, kategori, dan harga wajib diisi dengan benar.');
}

if (!in_array($status, ['Aktif', 'Nonaktif'], true)) {
    kembaliDenganPesan('error', 'Status layanan tidak valid.');
}

if (
    mb_strlen($nama) > 150 ||
    mb_strlen($waktu) > 100 ||
    mb_strlen($badge) > 100
) {
    kembaliDenganPesan('error', 'Ada informasi yang melebihi batas karakter.');
}

$harga = (float) $harga;

// Pastikan kategori tersedia.
$stmt = $conn->prepare("SELECT id FROM category WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $category_id);
$stmt->execute();

$kategori_ada = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$kategori_ada) {
    kembaliDenganPesan('error', 'Kategori tidak ditemukan.');
}

// Validasi data varian.
$varian_data = [];
$varian = $_POST['varian'] ?? [];

$varian_nama = $varian['nama'] ?? [];
$varian_durasi = $varian['durasi'] ?? [];
$varian_harga = $varian['harga'] ?? [];

if (!is_array($varian_nama) || !is_array($varian_durasi) || !is_array($varian_harga)) {
    kembaliDenganPesan('error', 'Format data varian tidak valid.');
}

foreach ($varian_nama as $i => $nama_varian) {
    $nama_varian = trim((string) $nama_varian);
    $durasi = trim((string) ($varian_durasi[$i] ?? ''));
    $harga_varian = $varian_harga[$i] ?? '';

    if ($nama_varian === '' && $durasi === '' && $harga_varian === '') {
        continue;
    }

    if (
        $nama_varian === '' ||
        !is_numeric($harga_varian) ||
        (float) $harga_varian < 0 ||
        mb_strlen($nama_varian) > 150 ||
        mb_strlen($durasi) > 100
    ) {
        kembaliDenganPesan('error', 'Periksa kembali data varian. Nama dan harga wajib diisi.');
    }

    $varian_data[] = [
        'nama' => $nama_varian,
        'durasi' => $durasi,
        'harga' => (float) $harga_varian
    ];
}
if (empty($varian_data)) {
    kembaliDenganPesan(
        'error',
        'Minimal tambahkan satu varian layanan beserta nama dan harganya.'
    );
}

// Validasi data ukuran.
$ukuran_data = [];
$ukuran = $_POST['ukuran'] ?? [];

$ukuran_kode = $ukuran['kode'] ?? [];
$ukuran_nama = $ukuran['nama'] ?? [];
$ukuran_harga = $ukuran['harga'] ?? [];

if (!is_array($ukuran_kode) || !is_array($ukuran_nama) || !is_array($ukuran_harga)) {
    kembaliDenganPesan('error', 'Format data ukuran tidak valid.');
}

foreach ($ukuran_kode as $i => $kode) {
    $kode = trim((string) $kode);
    $nama_ukuran = trim((string) ($ukuran_nama[$i] ?? ''));
    $harga_tambahan = $ukuran_harga[$i] ?? '';

    if ($kode === '' && $nama_ukuran === '' && $harga_tambahan === '') {
        continue;
    }

    if (
        $kode === '' ||
        $nama_ukuran === '' ||
        !is_numeric($harga_tambahan) ||
        (float) $harga_tambahan < 0 ||
        mb_strlen($kode) > 50 ||
        mb_strlen($nama_ukuran) > 100
    ) {
        kembaliDenganPesan('error', 'Periksa kembali kode, nama, dan harga tambahan ukuran.');
    }

    $ukuran_data[] = [
        'kode' => $kode,
        'nama' => $nama_ukuran,
        'harga' => (float) $harga_tambahan
    ];
}

// Validasi data addon.
$addon_data = [];
$addon = $_POST['addon'] ?? [];

$addon_nama = $addon['nama'] ?? [];
$addon_deskripsi = $addon['deskripsi'] ?? [];
$addon_harga = $addon['harga'] ?? [];
$addon_wajib = $addon['wajib'] ?? [];

if (
    !is_array($addon_nama) ||
    !is_array($addon_deskripsi) ||
    !is_array($addon_harga) ||
    !is_array($addon_wajib)
) {
    kembaliDenganPesan('error', 'Format data addon tidak valid.');
}

foreach ($addon_nama as $i => $nama_addon) {
    $nama_addon = trim((string) $nama_addon);
    $deskripsi_addon = trim((string) ($addon_deskripsi[$i] ?? ''));
    $harga_addon = $addon_harga[$i] ?? '';
    $wajib = $addon_wajib[$i] ?? '0';

    if ($nama_addon === '' && $deskripsi_addon === '' && $harga_addon === '') {
        continue;
    }

    if (
        $nama_addon === '' ||
        !is_numeric($harga_addon) ||
        (float) $harga_addon < 0 ||
        !in_array((string) $wajib, ['0', '1'], true) ||
        mb_strlen($nama_addon) > 150
    ) {
        kembaliDenganPesan('error', 'Periksa kembali data addon. Nama dan harga wajib diisi.');
    }

    $addon_data[] = [
        'nama' => $nama_addon,
        'deskripsi' => $deskripsi_addon,
        'harga' => (float) $harga_addon,
        'wajib' => (int) $wajib
    ];
}

// Upload gambar jika ada.
$gambar_path = '';
$uploaded_file = '';

if (
    isset($_FILES['gambar']) &&
    $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        kembaliDenganPesan('error', 'Upload gambar gagal.');
    }

    if ($_FILES['gambar']['size'] > 3 * 1024 * 1024) {
        kembaliDenganPesan('error', 'Ukuran gambar maksimal 3 MB.');
    }

    $tmp = $_FILES['gambar']['tmp_name'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);

    $allowed_mime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed_mime[$mime]) || getimagesize($tmp) === false) {
        kembaliDenganPesan('error', 'Format gambar harus JPG, PNG, atau WEBP.');
    }

    $upload_dir = __DIR__ . '/../../assets/uploads/layanan/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        kembaliDenganPesan('error', 'Folder upload gambar tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed_mime[$mime];

    $uploaded_file = $upload_dir . $filename;
    $gambar_path = 'assets/uploads/layanan/' . $filename;

    if (!move_uploaded_file($tmp, $uploaded_file)) {
        kembaliDenganPesan('error', 'Gambar gagal disimpan ke folder upload.');
    }
}

try {
    $conn->begin_transaction();

    // Simpan layanan utama.
    $stmt = $conn->prepare("
        INSERT INTO layanan (
            category_id,
            nama,
            deskripsi,
            waktu,
            harga,
            gambar,
            badge,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssdsss",
        $category_id,
        $nama,
        $deskripsi,
        $waktu,
        $harga,
        $gambar_path,
        $badge,
        $status
    );

    $stmt->execute();
    $layanan_id = $conn->insert_id;
    $stmt->close();

    // Simpan varian.
    if ($varian_data) {
        $stmt = $conn->prepare("
            INSERT INTO layanan_varian (
                layanan_id,
                nama_varian,
                durasi_label,
                harga,
                urutan
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($varian_data as $i => $v) {
            $urutan = $i + 1;

            $stmt->bind_param(
                "issdi",
                $layanan_id,
                $v['nama'],
                $v['durasi'],
                $v['harga'],
                $urutan
            );

            $stmt->execute();
        }

        $stmt->close();
    }

    // Simpan ukuran.
    if ($ukuran_data) {
        $stmt = $conn->prepare("
            INSERT INTO layanan_ukuran (
                layanan_id,
                kode_ukuran,
                nama_ukuran,
                harga_tambahan,
                urutan
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($ukuran_data as $i => $u) {
            $urutan = $i + 1;

            $stmt->bind_param(
                "issdi",
                $layanan_id,
                $u['kode'],
                $u['nama'],
                $u['harga'],
                $urutan
            );

            $stmt->execute();
        }

        $stmt->close();
    }

    // Simpan addon.
    if ($addon_data) {
        $stmt = $conn->prepare("
            INSERT INTO layanan_addon (
                layanan_id,
                nama_addon,
                deskripsi,
                harga,
                wajib,
                urutan
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($addon_data as $i => $a) {
            $urutan = $i + 1;

            $stmt->bind_param(
                "issdii",
                $layanan_id,
                $a['nama'],
                $a['deskripsi'],
                $a['harga'],
                $a['wajib'],
                $urutan
            );

            $stmt->execute();
        }

        $stmt->close();
    }

    $conn->commit();

    $_SESSION['flash_layanan'] = [
        'type' => 'success',
        'message' => 'Layanan baru berhasil ditambahkan.'
    ];

    header('Location: ../layanan.php');
    exit;
} catch (Throwable $e) {
    $conn->rollback();

    if ($uploaded_file !== '' && is_file($uploaded_file)) {
        unlink($uploaded_file);
    }

    error_log('Tambah layanan gagal: ' . $e->getMessage());

    kembaliDenganPesan(
        'error',
        'Layanan gagal disimpan. Periksa kembali data atau struktur database.'
    );
}
