<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'Admin'
) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helper.php';

$nama = $_SESSION['nama'] ?? 'Administrator';

function adminCount($conn, $sql)
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    return (int) $result->fetch_row()[0];
}

// Statistik dashboard
$total_pesanan = adminCount($conn, "SELECT COUNT(*) FROM pesanan");

$pesanan_hari_ini = adminCount(
    $conn,
    "SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()"
);

$total_layanan = adminCount(
    $conn,
    "SELECT COUNT(*) FROM layanan WHERE status = 'Aktif'"
);

$total_transaksi = adminCount(
    $conn,
    "SELECT COALESCE(SUM(total_harga), 0) FROM pesanan"
);

// Pesanan terbaru
$pesanan_terbaru = [];

$result = $conn->query("
    SELECT
        id,
        no_pesanan,
        nama_lengkap,
        total_harga,
        status_pesanan,
        status_pembayaran,
        created_at
    FROM pesanan
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pesanan_terbaru[] = $row;
    }
}

function statusClass($status)
{
    return match ($status) {
        'Selesai', 'Paid' => 'success',
        'Dibatalkan' => 'danger',
        'Pending', 'Unpaid' => 'pending',
        'Diproses' => 'info',
        'Refunded' => 'refunded',
        default => 'neutral'
    };
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin | Cuci Disini</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f6f7fb;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .badge.refunded {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 250px;
            padding: 28px 18px;
            background: #17172e;
            color: white;
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 42px;
            padding: 0 8px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: #6366f1;
        }

        .menu-label {
            color: #8585a3;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.3px;
            padding: 0 12px;
            margin-bottom: 12px;
        }

        .menu {
            display: grid;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 10px;
            color: #c4c4d8;
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #6366f1;
            color: white;
        }

        .sidebar-bottom {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
            padding-top: 18px;
            border-top: 1px solid #30304b;
        }

        .sidebar-bottom a {
            display: block;
            padding: 11px 13px;
            color: #c4c4d8;
            font-size: 13px;
            border-radius: 9px;
        }

        .sidebar-bottom a:hover {
            background: #292947;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 34px;
            max-width: 1600px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 25px;
            font-weight: 800;
        }

        .topbar p {
            color: #7b8497;
            font-size: 13px;
            margin-top: 6px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 13px;
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 12px;
        }

        .avatar {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 800;
        }

        .admin-profile strong {
            display: block;
            font-size: 12px;
        }

        .admin-profile small {
            display: block;
            color: #8991a2;
            font-size: 10px;
            margin-top: 3px;
        }

        .welcome {
            padding: 27px 30px;
            border-radius: 18px;
            background: linear-gradient(115deg, #4f46e5, #7c3aed);
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .welcome h2 {
            font-size: 21px;
            margin-bottom: 8px;
        }

        .welcome p {
            font-size: 13px;
            opacity: .88;
        }

        .welcome::after {
            content: "C";
            position: absolute;
            right: 35px;
            top: -40px;
            font-size: 170px;
            font-weight: 800;
            opacity: .08;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 28px 0 16px;
        }

        .section-heading h2 {
            font-size: 16px;
            font-weight: 800;
        }

        .section-heading p {
            font-size: 11px;
            color: #9299a8;
            margin-top: 4px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: white;
            padding: 21px;
            border: 1px solid #eaecf2;
            border-radius: 15px;
            min-width: 0;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #eef2ff;
            font-size: 19px;
        }

        .stat-label {
            color: #7d8799;
            font-size: 11px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 25px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .stat-description {
            color: #9aa1af;
            font-size: 10px;
            margin-top: 5px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .action-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 14px;
            padding: 19px;
            transition: .2s;
        }

        .action-card:hover {
            border-color: #a5b4fc;
            transform: translateY(-2px);
        }

        .action-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #eef2ff;
            font-size: 21px;
            flex-shrink: 0;
        }

        .action-card strong {
            display: block;
            font-size: 13px;
        }

        .action-card small {
            display: block;
            color: #8b93a3;
            font-size: 11px;
            margin-top: 5px;
        }

        .table-card {
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 15px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th,
        td {
            text-align: left;
            padding: 15px 18px;
            border-bottom: 1px solid #f0f1f5;
            font-size: 12px;
        }

        th {
            color: #8991a2;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            background: #fafbfc;
        }

        td {
            color: #4b5567;
        }

        td strong {
            color: #202a3d;
        }

        .badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            background: #f1f3f6;
            color: #596273;
        }

        .badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.neutral {
            background: #eef2ff;
            color: #4338ca;
        }

        .detail-link {
            color: #4f46e5;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: #9299a8;
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                padding: 17px;
            }

            .brand {
                margin-bottom: 18px;
            }

            .menu-label,
            .sidebar-bottom {
                display: none;
            }

            .menu {
                display: flex;
                overflow-x: auto;
            }

            .menu a {
                white-space: nowrap;
                padding: 11px 13px;
            }

            .main {
                margin: 0;
                padding: 22px 15px;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 21px;
            }

            .admin-profile {
                padding: 7px;
            }

            .admin-profile div:last-child {
                display: none;
            }

            .welcome {
                padding: 23px;
            }

            .welcome h2 {
                font-size: 18px;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 21px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="brand">
            <span class="brand-mark">C</span>
            Cuci Disini
        </div>

        <div class="menu-label">MENU UTAMA</div>

        <nav class="menu">
            <a href="dashboard.php" class="active">⌂ Dashboard</a>
            <a href="pesanan.php">📦 Manajemen Pesanan</a>
            <a href="layanan.php">🧼 Manajemen Layanan</a>
            <a href="../index.php">↗ Lihat Website</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php">⇥ Keluar dari akun</a>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Ringkasan aktivitas dan operasional Cuci Disini.</p>
            </div>

            <div class="admin-profile">
                <div class="avatar">
                    <?= e(strtoupper(substr($nama, 0, 1))) ?>
                </div>
                <div>
                    <strong><?= e($nama) ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
        </header>

        <section class="welcome">
            <h2>Halo, <?= e($nama) ?> 👋</h2>
            <p>Selamat datang kembali. Berikut ringkasan aktivitas tokomu.</p>
        </section>

        <div class="section-heading">
            <div>
                <h2>Ringkasan Bisnis</h2>
                <p>Data terbaru dari database toko.</p>
            </div>
        </div>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Pesanan</span>
                    <span class="stat-icon">📦</span>
                </div>
                <div class="stat-value"><?= number_format($total_pesanan) ?></div>
                <div class="stat-description">Seluruh transaksi tersimpan</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pesanan Hari Ini</span>
                    <span class="stat-icon">📅</span>
                </div>
                <div class="stat-value"><?= number_format($pesanan_hari_ini) ?></div>
                <div class="stat-description">Transaksi masuk hari ini</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Nilai Transaksi</span>
                    <span class="stat-icon">💰</span>
                </div>
                <div class="stat-value" style="font-size:20px">
                    <?= formatRupiah($total_transaksi) ?>
                </div>
                <div class="stat-description">Akumulasi seluruh pesanan, bukan hanya pembayaran lunas</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Layanan Aktif</span>
                    <span class="stat-icon">🧼</span>
                </div>
                <div class="stat-value"><?= number_format($total_layanan) ?></div>
                <div class="stat-description">Layanan yang tersedia di website</div>
            </div>

        </section>

        <div class="section-heading">
            <div>
                <h2>Akses Cepat</h2>
                <p>Kelola operasional toko dari sini.</p>
            </div>
        </div>

        <section class="quick-actions">
            <a href="pesanan.php" class="action-card">
                <div class="action-icon">📦</div>
                <div>
                    <strong>Kelola Pesanan</strong>
                    <small>Lihat transaksi dan ubah status pesanan</small>
                </div>
            </a>

            <a href="layanan.php" class="action-card">
                <div class="action-icon">🧼</div>
                <div>
                    <strong>Kelola Layanan</strong>
                    <small>Tambah, edit, dan atur layanan toko</small>
                </div>
            </a>
        </section>

        <div class="section-heading">
            <div>
                <h2>Pesanan Terbaru</h2>
                <p>Lima transaksi terakhir yang masuk.</p>
            </div>

            <a href="pesanan.php" class="detail-link" style="font-size:11px">
                Lihat semua →
            </a>
        </div>

        <section class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status Pesanan</th>
                            <th>Status Bayar</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($pesanan_terbaru)): ?>
                            <tr>
                                <td colspan="7" class="empty">
                                    Belum ada transaksi yang masuk.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pesanan_terbaru as $item): ?>
                                <tr>
                                    <td><strong><?= e($item['no_pesanan']) ?></strong></td>
                                    <td><?= e($item['nama_lengkap']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($item['created_at'])) ?></td>
                                    <td><strong><?= formatRupiah($item['total_harga']) ?></strong></td>
                                    <td>
                                        <span class="badge <?= statusClass($item['status_pesanan']) ?>">
                                            <?= e($item['status_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= statusClass($item['status_pembayaran']) ?>">
                                            <?= e($item['status_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="detail-link" href="pesanan.php?id=<?= (int) $item['id'] ?>">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>

</html>