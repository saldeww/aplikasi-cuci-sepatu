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

if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_admin'];

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$q = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$detail_id = (int) ($_GET['id'] ?? 0);

$valid_status = ['Pending', 'Diproses', 'Selesai', 'Dibatalkan'];

// Ambil data statistik pesanan untuk ringkasan di atas
$stats = [
    'Total' => 0,
    'Pending' => 0,
    'Diproses' => 0,
    'Selesai' => 0,
    'Dibatalkan' => 0
];

$stats_query = $conn->query("SELECT status_pesanan, COUNT(*) as total FROM pesanan GROUP BY status_pesanan");
if ($stats_query) {
    while ($row = $stats_query->fetch_assoc()) {
        $stats['Total'] += (int)$row['total'];
        if (isset($stats[$row['status_pesanan']])) {
            $stats[$row['status_pesanan']] = (int)$row['total'];
        }
    }
}

// Query utama untuk daftar pesanan
$sql = "
    SELECT
        id,
        no_pesanan,
        nama_lengkap,
        no_wa,
        total_harga,
        status_pesanan,
        status_pembayaran,
        metode_pembayaran,
        metode_pengantaran,
        jadwal_pelaksanaan,
        alamat_pengantaran,
        created_at
    FROM pesanan
    WHERE 1=1
";

$params = [];
$types = '';

if ($q !== '') {
    $sql .= " AND (no_pesanan LIKE ? OR nama_lengkap LIKE ?)";
    $keyword = "%{$q}%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'ss';
}

if (in_array($filter_status, $valid_status, true)) {
    $sql .= " AND status_pesanan = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$pesanan_list = [];
while ($row = $result->fetch_assoc()) {
    $pesanan_list[] = $row;
}
$stmt->close();

// Detail pesanan yang dipilih
$pesanan_detail = null;
$detail_items = [];

if ($detail_id > 0) {
    $stmt = $conn->prepare("
        SELECT *
        FROM pesanan
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $detail_id);
    $stmt->execute();
    $pesanan_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pesanan_detail) {
        $stmt = $conn->prepare("
            SELECT
                d.*,
                l.gambar
            FROM detail_pesanan d
            LEFT JOIN layanan l ON l.id = d.layanan_id
            WHERE d.pesanan_id = ?
            ORDER BY d.id ASC
        ");

        $stmt->bind_param("i", $detail_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $snapshot = json_decode($row['addon_snapshot'] ?? '', true);

            if (!is_array($snapshot)) {
                $snapshot = [];
            }

            $addons = $snapshot['addons'] ?? [];
            if (!is_array($addons)) {
                $addons = [];
            }

            $row['addons'] = $addons;
            $row['catatan'] = $snapshot['catatan'] ?? '';

            $detail_items[] = $row;
        }

        $stmt->close();
    }
}

function adminStatusClass($status)
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
    <title>Manajemen Pesanan | Cuci Disini</title>

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
            color: inherit;
            text-decoration: none;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 245px;
            background: #17172e;
            color: white;
            padding: 27px 18px;
        }

        .brand {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 40px;
        }

        .brand span {
            display: inline-grid;
            place-items: center;
            width: 37px;
            height: 37px;
            margin-right: 8px;
            border-radius: 11px;
            background: #6366f1;
        }

        .menu-label {
            color: #8585a3;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0 10px 12px;
        }

        .menu {
            display: grid;
            gap: 7px;
        }

        .menu a {
            padding: 13px;
            border-radius: 10px;
            color: #c4c4d8;
            font-size: 13px;
            font-weight: 600;
        }

        .menu a:hover,
        .menu a.active {
            background: #6366f1;
            color: white;
        }

        .main {
            margin-left: 245px;
            padding: 32px;
            max-width: 1600px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 26px;
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
        }

        .subtitle {
            color: #8991a2;
            font-size: 12px;
            margin-top: 6px;
        }

        .back-link {
            padding: 11px 15px;
            background: white;
            border: 1px solid #e7e9f0;
            border-radius: 10px;
            color: #4f46e5;
            font-size: 12px;
            font-weight: 700;
        }

        .flash {
            padding: 14px 17px;
            border-radius: 11px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 600;
        }

        .flash.success {
            background: #dcfce7;
            color: #166534;
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Statistik Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: #4f46e5;
            transform: translateY(-2px);
        }

        .stat-card.active {
            border-color: #4f46e5;
            background: #eef2ff;
        }

        .stat-card span {
            color: #8991a2;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stat-card h3 {
            font-size: 20px;
            font-weight: 800;
            color: #172033;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding: 17px;
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .toolbar input,
        .toolbar select {
            min-height: 42px;
            padding: 0 12px;
            border: 1px solid #e2e5ed;
            border-radius: 9px;
            font: inherit;
            font-size: 12px;
            background: white;
            outline: none;
        }

        .toolbar input {
            flex: 1;
            min-width: 180px;
        }

        .toolbar button {
            padding: 0 19px;
            border: 0;
            border-radius: 9px;
            background: #4f46e5;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .table-card,
        .detail-card {
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 22px;
        }

        .table-heading {
            padding: 19px 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .table-heading h2 {
            font-size: 15px;
        }

        .table-heading p {
            color: #9299a8;
            font-size: 11px;
            margin-top: 5px;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 15px 17px;
            text-align: left;
            border-bottom: 1px solid #f0f1f5;
            font-size: 11px;
        }

        th {
            background: #fafbfc;
            color: #8991a2;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
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
            white-space: nowrap;
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

        .badge.info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.refunded {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .detail-link {
            color: #4f46e5;
            font-weight: 800;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #9299a8;
        }

        .detail-card {
            padding: 22px;
        }

        .detail-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .detail-top h2 {
            font-size: 17px;
            margin-bottom: 6px;
        }

        .detail-top p {
            color: #9299a8;
            font-size: 11px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .detail-info {
            padding: 14px;
            border-radius: 10px;
            background: #f8f9fc;
            min-width: 0;
        }

        .detail-info small {
            display: block;
            color: #8991a2;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .detail-info strong {
            font-size: 12px;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .item-row {
            display: flex;
            gap: 13px;
            padding: 15px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .item-image {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            object-fit: cover;
            background: #f1f3f6;
        }

        .item-info {
            flex: 1;
            min-width: 0;
            font-size: 12px;
            line-height: 1.8;
        }

        .item-info small {
            display: block;
            color: #8991a2;
        }

        .item-price {
            font-weight: 800;
            white-space: nowrap;
            font-size: 12px;
        }

        .update-box {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
            margin-top: 22px;
        }

        .update-form {
            padding: 16px;
            border: 1px solid #eaecf2;
            border-radius: 12px;
        }

        .update-form label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .update-form select {
            width: 100%;
            height: 42px;
            border: 1px solid #e2e5ed;
            border-radius: 8px;
            padding: 0 10px;
            font: inherit;
            font-size: 12px;
            background: white;
        }

        .update-form button {
            width: 100%;
            margin-top: 10px;
            height: 40px;
            border: 0;
            border-radius: 8px;
            background: #4f46e5;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .update-form button:hover {
            background: #4338ca;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-top: 17px;
            margin-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-weight: 800;
        }

        .total-row strong {
            color: #4f46e5;
            font-size: 18px;
        }

        @media(max-width: 850px) {
            .sidebar {
                position: static;
                width: 100%;
                padding: 16px;
            }

            .brand {
                margin-bottom: 17px;
            }

            .menu-label {
                display: none;
            }

            .menu {
                display: flex;
                overflow-x: auto;
            }

            .menu a {
                white-space: nowrap;
            }

            .main {
                margin: 0;
                padding: 22px 15px;
            }
        }

        @media(max-width: 550px) {
            .topbar {
                align-items: flex-start;
            }

            h1 {
                font-size: 20px;
            }

            .detail-grid,
            .update-box {
                grid-template-columns: 1fr;
            }

            .detail-card {
                padding: 16px;
            }

            .item-image {
                width: 48px;
                height: 48px;
            }

            .item-price {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="brand"><span>C</span> Cuci Disini</div>
        <div class="menu-label">MENU UTAMA</div>
        <nav class="menu">
            <a href="dashboard.php">⌂ Dashboard</a>
            <a href="pesanan.php" class="active">📦 Manajemen Pesanan</a>
            <a href="layanan.php">🧼 Manajemen Layanan</a>
            <a href="../index.php">↗ Lihat Website</a>
        </nav>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Manajemen Pesanan</h1>
                <p class="subtitle">Kelola transaksi dan pantau progres pengerjaan pelanggan.</p>
            </div>
            <a href="dashboard.php" class="back-link">← Dashboard</a>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- KARTU STATISTIK RINGKASAN STATUS -->
        <div class="stats-grid">
            <a href="pesanan.php" class="stat-card <?= $filter_status === '' ? 'active' : '' ?>">
                <span>Total Pesanan</span>
                <h3><?= $stats['Total'] ?></h3>
            </a>
            <a href="pesanan.php?status=Pending" class="stat-card <?= $filter_status === 'Pending' ? 'active' : '' ?>">
                <span>Pending</span>
                <h3 style="color: #92400e;"><?= $stats['Pending'] ?></h3>
            </a>
            <a href="pesanan.php?status=Diproses" class="stat-card <?= $filter_status === 'Diproses' ? 'active' : '' ?>">
                <span>Diproses</span>
                <h3 style="color: #1e40af;"><?= $stats['Diproses'] ?></h3>
            </a>
            <a href="pesanan.php?status=Selesai" class="stat-card <?= $filter_status === 'Selesai' ? 'active' : '' ?>">
                <span>Selesai</span>
                <h3 style="color: #166534;"><?= $stats['Selesai'] ?></h3>
            </a>
            <a href="pesanan.php?status=Dibatalkan" class="stat-card <?= $filter_status === 'Dibatalkan' ? 'active' : '' ?>">
                <span>Dibatalkan</span>
                <h3 style="color: #991b1b;"><?= $stats['Dibatalkan'] ?></h3>
            </a>
        </div>

        <form method="GET" class="toolbar">
            <input
                type="search"
                name="q"
                placeholder="Cari nomor pesanan atau nama pelanggan..."
                value="<?= e($q) ?>">

            <select name="status">
                <option value="">Semua status</option>
                <?php foreach ($valid_status as $status): ?>
                    <option
                        value="<?= e($status) ?>"
                        <?= $filter_status === $status ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Cari Pesanan</button>
        </form>

        <section class="table-card">
            <div class="table-heading">
                <h2>Daftar Transaksi</h2>
                <p><?= count($pesanan_list) ?> pesanan ditemukan</p>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status Pesanan</th>
                            <th>Pembayaran</th>
                            <th>Detail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$pesanan_list): ?>
                            <tr>
                                <td colspan="7" class="empty">
                                    Tidak ada pesanan yang cocok.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pesanan_list as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['no_pesanan']) ?></strong></td>
                                    <td><?= e($p['nama_lengkap']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                                    <td><strong><?= formatRupiah($p['total_harga']) ?></strong></td>
                                    <td>
                                        <span class="badge <?= adminStatusClass($p['status_pesanan']) ?>">
                                            <?= e($p['status_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= adminStatusClass($p['status_pembayaran']) ?>">
                                            <?= e($p['status_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a
                                            class="detail-link"
                                            href="pesanan.php?id=<?= (int) $p['id'] ?>">
                                            Lihat detail →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($detail_id > 0): ?>

            <?php if (!$pesanan_detail): ?>
                <section class="detail-card">
                    Pesanan tidak ditemukan.
                </section>
            <?php else: ?>

                <section class="detail-card" id="detail">
                    <div class="detail-top">
                        <div>
                            <h2>Detail <?= e($pesanan_detail['no_pesanan']) ?></h2>
                            <p>
                                Dibuat pada
                                <?= date('d M Y, H:i', strtotime($pesanan_detail['created_at'])) ?>
                            </p>
                        </div>

                        <a href="pesanan.php" class="detail-link">Tutup detail ×</a>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-info">
                            <small>Nama pelanggan</small>
                            <strong><?= e($pesanan_detail['nama_lengkap']) ?></strong>
                        </div>

                        <div class="detail-info">
                            <small>Nomor WhatsApp</small>
                            <strong><?= e($pesanan_detail['no_wa']) ?></strong>
                        </div>

                        <div class="detail-info">
                            <small>Metode pengantaran</small>
                            <strong><?= e($pesanan_detail['metode_pengantaran']) ?></strong>
                        </div>

                        <div class="detail-info">
                            <small>Jadwal pelaksanaan</small>
                            <strong>
                                <?= $pesanan_detail['jadwal_pelaksanaan']
                                    ? date('d M Y, H:i', strtotime($pesanan_detail['jadwal_pelaksanaan']))
                                    : '-' ?>
                            </strong>
                        </div>

                        <div class="detail-info">
                            <small>Metode pembayaran</small>
                            <strong><?= e($pesanan_detail['metode_pembayaran']) ?></strong>
                        </div>

                        <div class="detail-info">
                            <small>Alamat pengantaran</small>
                            <strong><?= nl2br(e($pesanan_detail['alamat_pengantaran'])) ?></strong>
                        </div>
                    </div>

                    <h3 style="font-size:14px;margin-bottom:8px;">Item pesanan</h3>

                    <?php foreach ($detail_items as $item): ?>
                        <div class="item-row">

                            <?php if (!empty($item['gambar'])): ?>
                                <img
                                    class="item-image"
                                    src="../<?= e(ltrim($item['gambar'], '/')) ?>"
                                    alt="<?= e($item['nama_layanan_snapshot']) ?>">
                            <?php else: ?>
                                <div class="item-image"></div>
                            <?php endif; ?>

                            <div class="item-info">
                                <strong><?= e($item['nama_layanan_snapshot']) ?></strong>

                                <small>
                                    Varian: <?= e($item['nama_varian_snapshot'] ?: '-') ?>
                                    · Ukuran: <?= e($item['ukuran_snapshot'] ?: '-') ?>
                                </small>

                                <?php if ($item['addons']): ?>
                                    <small>
                                        Addon:
                                        <?php
                                        $names = [];
                                        foreach ($item['addons'] as $addon) {
                                            if (!empty($addon['nama'])) {
                                                $names[] = $addon['nama'];
                                            }
                                        }
                                        echo e(implode(', ', $names));
                                        ?>
                                    </small>
                                <?php endif; ?>

                                <?php if (!empty($item['catatan'])): ?>
                                    <small>Catatan: <?= e($item['catatan']) ?></small>
                                <?php endif; ?>

                                <small>
                                    <?= (int) $item['jumlah'] ?> ×
                                    <?= formatRupiah($item['harga_satuan']) ?>
                                </small>
                            </div>

                            <div class="item-price">
                                <?= formatRupiah($item['subtotal']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-row">
                        <span>Total pembayaran</span>
                        <strong><?= formatRupiah($pesanan_detail['total_harga']) ?></strong>
                    </div>

                    <div class="update-box">

                        <form
                            action="handlers/pesanan_handler.php"
                            method="POST"
                            class="update-form"
                            onsubmit="return confirm('Yakin ingin mengubah status pesanan?')">

                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                            <input type="hidden" name="pesanan_id" value="<?= (int) $detail_id ?>">
                            <input type="hidden" name="jenis" value="status_pesanan">

                            <label>Status pengerjaan</label>

                            <select name="status" required>
                                <?php foreach ($valid_status as $status): ?>
                                    <option
                                        value="<?= e($status) ?>"
                                        <?= $pesanan_detail['status_pesanan'] === $status ? 'selected' : '' ?>>
                                        <?= e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit">Simpan Status Pesanan</button>
                        </form>

                        <form
                            action="handlers/pesanan_handler.php"
                            method="POST"
                            class="update-form"
                            onsubmit="return confirm('Yakin ingin mengubah status pembayaran?')">

                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                            <input type="hidden" name="pesanan_id" value="<?= (int) $detail_id ?>">
                            <input type="hidden" name="jenis" value="status_pembayaran">

                            <label>Status pembayaran</label>

                            <select name="status" required>
                                <?php foreach (['Unpaid', 'Paid', 'Refunded'] as $status): ?>
                                    <option
                                        value="<?= e($status) ?>"
                                        <?= $pesanan_detail['status_pembayaran'] === $status ? 'selected' : '' ?>>
                                        <?= e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit">Simpan Status Pembayaran</button>
                        </form>

                    </div>
                </section>

            <?php endif; ?>

        <?php endif; ?>

    </main>

</body>

</html>