
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pesanan.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_admin']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_admin'], $token)
) {
    $_SESSION['flash_admin'] = [
        'type' => 'error',
        'message' => 'Sesi keamanan tidak valid. Silakan coba lagi.'
    ];

    header('Location: ../pesanan.php');
    exit;
}

$pesanan_id = (int) ($_POST['pesanan_id'] ?? 0);
$jenis = $_POST['jenis'] ?? '';
$status = $_POST['status'] ?? '';

$allowed_status = [
    'status_pesanan' => [
        'Pending',
        'Diproses',
        'Selesai',
        'Dibatalkan'
    ],
    'status_pembayaran' => [
        'Unpaid',
        'Paid',
        'Refunded'
    ]
];

if (
    $pesanan_id < 1 ||
    !isset($allowed_status[$jenis]) ||
    !in_array($status, $allowed_status[$jenis], true)
) {
    $_SESSION['flash_admin'] = [
        'type' => 'error',
        'message' => 'Data perubahan status tidak valid.'
    ];

    header('Location: ../pesanan.php');
    exit;
}

try {
    // Pastikan pesanan memang tersedia.
    $check = $conn->prepare(
        "SELECT id FROM pesanan WHERE id = ? LIMIT 1"
    );
    $check->bind_param("i", $pesanan_id);
    $check->execute();

    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if (!$exists) {
        throw new Exception('Pesanan tidak ditemukan.');
    }

    // Nama kolom dipilih hanya dari whitelist di atas.
    $sql = "UPDATE pesanan SET $jenis = ? WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $pesanan_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_admin'] = [
        'type' => 'success',
        'message' => 'Status pesanan berhasil diperbarui.'
    ];
} catch (Throwable $e) {
    error_log('Admin update pesanan: ' . $e->getMessage());

    $_SESSION['flash_admin'] = [
        'type' => 'error',
        'message' => 'Gagal memperbarui status pesanan.'
    ];
}

header('Location: ../pesanan.php?id=' . $pesanan_id);
exit;
