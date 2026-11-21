
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
function hapusPesanan($conn, $pesanan_id)
{
    $conn->begin_transaction();

    try {
        // Kunci dan periksa pesanan
        $stmt = $conn->prepare("
            SELECT status_pembayaran
            FROM pesanan
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();

        $pesanan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pesanan) {
            throw new Exception('Pesanan tidak ditemukan.');
        }

        // Lindungi transaksi yang sudah dibayar
        if (in_array($pesanan['status_pembayaran'], ['Paid', 'Refunded'], true)) {
            throw new Exception('Pesanan yang sudah dibayar atau direfund tidak boleh dihapus.');
        }

        // Hapus detail item
        $stmt = $conn->prepare("
            DELETE FROM detail_pesanan
            WHERE pesanan_id = ?
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();
        $stmt->close();

        // Hapus pesanan
        $stmt = $conn->prepare("
            DELETE FROM pesanan
            WHERE id = ?
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

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
if ($pesanan_id < 1) {
    $_SESSION['flash_admin'] = [
        'type' => 'error',
        'message' => 'ID pesanan tidak valid.'
    ];

    header('Location: ../pesanan.php');
    exit;
}

try {

    // AKSI HAPUS PESANAN
    if ($jenis === 'hapus_pesanan') {

        hapusPesanan($conn, $pesanan_id);

        $_SESSION['flash_admin'] = [
            'type' => 'success',
            'message' => 'Pesanan berhasil dihapus.'
        ];

        header('Location: ../pesanan.php');
        exit;
    }

    // AKSI UPDATE STATUS
    if (
        !isset($allowed_status[$jenis]) ||
        !in_array($status, $allowed_status[$jenis], true)
    ) {
        throw new Exception('Data perubahan status tidak valid.');
    }

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

    error_log('Admin pesanan: ' . $e->getMessage());

    $_SESSION['flash_admin'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];
}

header('Location: ../pesanan.php?id=' . $pesanan_id);
exit;

header('Location: ../pesanan.php?id=' . $pesanan_id);
exit;
