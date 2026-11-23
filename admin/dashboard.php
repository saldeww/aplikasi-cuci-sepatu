<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

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

// Statistik dashboard
$total_pesanan = (int) $conn->query("SELECT COUNT(*) FROM pesanan")->fetch_row()[0];

$pesanan_pending = (int) $conn->query("SELECT COUNT(*) FROM pesanan WHERE status_pesanan = 'Pending'")->fetch_row()[0];

$pesanan_hari_ini = (int) $conn->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];

$total_layanan = (int) $conn->query("SELECT COUNT(*) FROM layanan WHERE status = 'Aktif'")->fetch_row()[0];

$total_transaksi = (float) $conn->query("SELECT COALESCE(SUM(total_harga), 0) FROM pesanan")->fetch_row()[0];

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

// Data grafik pesanan per bulan (12 bulan terakhir)
$chart_labels = [];
$chart_pesanan = [];
$chart_omzet = [];

for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-{$i} months");
    $chart_labels[] = date('M Y', $ts);

    $bln = date('Y-m', $ts);
    $row_c = $conn->query("
        SELECT COUNT(*), COALESCE(SUM(total_harga), 0)
        FROM pesanan
        WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$bln}'
    ")->fetch_row();

    $chart_pesanan[] = (int) $row_c[0];
    $chart_omzet[]   = (float) $row_c[1];
}

// Data grafik per kuartal (4 kuartal terakhir)
// Hitung manual: quarter saat ini, lalu mundur 3 kuartal
$chart_q_labels  = [];
$chart_q_pesanan = [];
$chart_q_omzet   = [];

$cur_year    = (int) date('Y');
$cur_quarter = (int) ceil(date('n') / 3); // 1-4

for ($i = 3; $i >= 0; $i--) {
    // Mundur $i kuartal dari kuartal sekarang
    $q_offset    = $cur_quarter - $i;
    $year_offset = $cur_year;

    while ($q_offset < 1) {
        $q_offset += 4;
        $year_offset--;
    }

    $q_month_start = ($q_offset - 1) * 3 + 1;             // bulan pertama kuartal
    $q_month_end   = $q_offset * 3;                        // bulan terakhir kuartal

    $q_start = date('Y-m-01', mktime(0, 0, 0, $q_month_start, 1, $year_offset));
    $q_end   = date('Y-m-t',  mktime(0, 0, 0, $q_month_end,   1, $year_offset));

    $chart_q_labels[] = "Q{$q_offset} {$year_offset}";

    $row_q = $conn->query("
        SELECT COUNT(*), COALESCE(SUM(total_harga), 0)
        FROM pesanan
        WHERE created_at BETWEEN '{$q_start}' AND '{$q_end} 23:59:59'
    ")->fetch_row();

    $chart_q_pesanan[] = (int) $row_q[0];
    $chart_q_omzet[]   = (float) $row_q[1];
}

// Data grafik per tahun (5 tahun terakhir)
$chart_y_labels = [];
$chart_y_pesanan = [];
$chart_y_omzet = [];

for ($i = 4; $i >= 0; $i--) {
    $year = (int) date('Y') - $i;
    $chart_y_labels[] = (string) $year;

    $row_y = $conn->query("
        SELECT COUNT(*), COALESCE(SUM(total_harga), 0)
        FROM pesanan
        WHERE YEAR(created_at) = {$year}
    ")->fetch_row();

    $chart_y_pesanan[] = (int) $row_y[0];
    $chart_y_omzet[]   = (float) $row_y[1];
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
            grid-template-columns: repeat(5, minmax(0, 1fr));
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

        @media (max-width: 1300px) {
            .stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
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
        .chart-period-btn,
        .chart-type-btn {
            padding: 8px 14px;
            border: 1px solid #e2e5ed;
            border-radius: 8px;
            background: white;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: .15s;
        }

        .chart-period-btn.active,
        .chart-type-btn.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }

        .chart-period-btn:hover:not(.active),
        .chart-type-btn:hover:not(.active) {
            border-color: #a5b4fc;
            color: #4f46e5;
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
                    <span class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2l-2 5v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5z"/><path d="M4 7h16"/><path d="M16 11a4 4 0 0 1-8 0"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-value"><?= number_format($total_pesanan) ?></div>
                <div class="stat-description">Seluruh transaksi tersimpan</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Perlu Ditindak</span>
                    <span class="stat-icon" style="background:#fef3c7;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-value" style="<?= $pesanan_pending > 0 ? 'color:#92400e' : '' ?>">
                    <?= number_format($pesanan_pending) ?>
                </div>
                <div class="stat-description">Pesanan berstatus Pending</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pesanan Hari Ini</span>
                    <span class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-value"><?= number_format($pesanan_hari_ini) ?></div>
                <div class="stat-description">Transaksi masuk hari ini</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Nilai Transaksi</span>
                    <span class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-value" style="font-size:18px">
                    <?= formatRupiah($total_transaksi) ?>
                </div>
                <div class="stat-description">Akumulasi semua pesanan</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Layanan Aktif</span>
                    <span class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-value"><?= number_format($total_layanan) ?></div>
                <div class="stat-description">Layanan tersedia di website</div>
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

        <div class="section-heading" style="margin-top:32px">
            <div>
                <h2>Grafik Pembelian</h2>
                <p>Jumlah pesanan dan omzet berdasarkan periode.</p>
            </div>

            <div style="display:flex;gap:8px;">
                <button class="chart-period-btn active" data-period="bulan">Per Bulan</button>
                <button class="chart-period-btn" data-period="kuartal">Per Kuartal</button>
                <button class="chart-period-btn" data-period="tahun">Per Tahun</button>
            </div>
        </div>

        <section class="table-card" style="padding:22px 24px 18px;">
            <div style="display:flex;gap:12px;margin-bottom:18px;">
                <button class="chart-type-btn active" data-type="pesanan">Jumlah Pesanan</button>
                <button class="chart-type-btn" data-type="omzet">Omzet (Rp)</button>
            </div>
            <div style="position:relative;height:280px;">
                <canvas id="salesChart"></canvas>
            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const DATA = {
            bulan: {
                labels:  <?= json_encode($chart_labels) ?>,
                pesanan: <?= json_encode($chart_pesanan) ?>,
                omzet:   <?= json_encode($chart_omzet) ?>
            },
            kuartal: {
                labels:  <?= json_encode($chart_q_labels) ?>,
                pesanan: <?= json_encode($chart_q_pesanan) ?>,
                omzet:   <?= json_encode($chart_q_omzet) ?>
            },
            tahun: {
                labels:  <?= json_encode($chart_y_labels) ?>,
                pesanan: <?= json_encode($chart_y_pesanan) ?>,
                omzet:   <?= json_encode($chart_y_omzet) ?>
            }
        };

        let currentPeriod = 'bulan';
        let currentType   = 'pesanan';

        const ctx = document.getElementById('salesChart').getContext('2d');

        const chart = new Chart(ctx, {
            type: 'bar',
            data: buildData(),
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (item) {
                                if (currentType === 'omzet') {
                                    return 'Rp ' + Number(item.raw).toLocaleString('id-ID');
                                }
                                return item.raw + ' pesanan';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) {
                                if (currentType === 'omzet') {
                                    if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'jt';
                                    if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'rb';
                                    return 'Rp ' + val;
                                }
                                return val;
                            }
                        }
                    }
                }
            }
        });

        function buildData() {
            const d = DATA[currentPeriod];
            const values = currentType === 'pesanan' ? d.pesanan : d.omzet;
            return {
                labels: d.labels,
                datasets: [{
                    data: values,
                    backgroundColor: 'rgba(99, 102, 241, 0.75)',
                    borderRadius: 7,
                    borderSkipped: false
                }]
            };
        }

        function refresh() {
            chart.data = buildData();
            chart.update();
        }

        document.querySelectorAll('.chart-period-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.chart-period-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;
                refresh();
            });
        });

        document.querySelectorAll('.chart-type-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentType = btn.dataset.type;
                refresh();
            });
        });
    })();
    </script>

</body>

</html>