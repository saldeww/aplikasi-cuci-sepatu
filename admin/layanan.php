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

if (empty($_SESSION['csrf_layanan'])) {
    $_SESSION['csrf_layanan'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_layanan'];

$flash = $_SESSION['flash_layanan'] ?? null;
unset($_SESSION['flash_layanan']);

$q = trim($_GET['q'] ?? '');
$kategori = (int) ($_GET['kategori'] ?? 0);
$status = $_GET['status'] ?? '';

$kategori_result = $conn->query("
    SELECT id, nama
    FROM category
    ORDER BY nama ASC
");

$kategori_list = [];

while ($row = $kategori_result->fetch_assoc()) {
    $kategori_list[] = $row;
}

$total_result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Aktif') AS aktif,
        SUM(status = 'Nonaktif') AS nonaktif
    FROM layanan
");

$stats = $total_result->fetch_assoc();

$sql = "
    SELECT
        l.id,
        l.category_id,
        l.nama,
        l.deskripsi,
        l.waktu,
        l.harga,
        l.gambar,
        l.badge,
        l.status,
        c.nama AS nama_kategori
    FROM layanan l
    LEFT JOIN category c ON c.id = l.category_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($q !== '') {
    $sql .= " AND (l.nama LIKE ? OR l.deskripsi LIKE ?)";
    $keyword = "%{$q}%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'ss';
}

if ($kategori > 0) {
    $sql .= " AND l.category_id = ?";
    $params[] = $kategori;
    $types .= 'i';
}

if (in_array($status, ['Aktif', 'Nonaktif'], true)) {
    $sql .= " AND l.status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY l.created_at DESC, l.id DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$layanan_list = [];

while ($row = $result->fetch_assoc()) {
    $layanan_list[] = $row;
}

$stmt->close();

function layananBadgeClass($status)
{
    return $status === 'Aktif' ? 'active' : 'inactive';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Layanan | Cuci Disini</title>

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

        button,
        input,
        select {
            font: inherit;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 245px;
            padding: 27px 18px;
            background: #17172e;
            color: white;
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

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 17px;
            border: 0;
            border-radius: 10px;
            background: #4f46e5;
            color: white;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .primary-btn:hover {
            background: #4338ca;
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

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            padding: 19px;
            border: 1px solid #eaecf2;
            border-radius: 14px;
            background: white;
        }

        .stat-card small {
            color: #8991a2;
            font-size: 11px;
            font-weight: 600;
        }

        .stat-card strong {
            display: block;
            margin-top: 9px;
            font-size: 25px;
            font-weight: 800;
        }

        .stat-card .green {
            color: #16a34a;
        }

        .stat-card .gray {
            color: #64748b;
        }

        .filter-card {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 16px;
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .filter-card input,
        .filter-card select {
            min-height: 42px;
            padding: 0 12px;
            border: 1px solid #e2e5ed;
            border-radius: 9px;
            background: white;
            font-size: 12px;
            outline: none;
        }

        .filter-card input {
            flex: 1;
            min-width: 180px;
        }

        .filter-card button {
            padding: 0 17px;
            border: 0;
            border-radius: 9px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .table-card {
            overflow: hidden;
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 15px;
        }

        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
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
            min-width: 850px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f1f5;
            text-align: left;
            font-size: 11px;
        }

        th {
            background: #fafbfc;
            color: #8991a2;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .service {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 210px;
        }

        .service img,
        .service-placeholder {
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            border-radius: 10px;
            object-fit: cover;
            background: #f1f3f6;
        }

        .service strong {
            display: block;
            color: #202a3d;
            font-size: 12px;
        }

        .service small {
            display: block;
            margin-top: 5px;
            color: #9299a8;
            font-size: 10px;
        }

        .price {
            font-weight: 800;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .badge.inactive {
            background: #f1f3f6;
            color: #64748b;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .edit-btn,
        .toggle-btn {
            padding: 8px 10px;
            border: 0;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            color: #ff0000;
        }

        .edit-btn {
            background: #eef2ff;
            color: #4338ca;
        }

        .toggle-btn {
            background: #f1f3f6;
            color: #475569;
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #9299a8;
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
                flex-direction: column;
            }

            h1 {
                font-size: 20px;
            }

            .stats {
                gap: 8px;
            }

            .stat-card {
                padding: 13px;
            }

            .stat-card strong {
                font-size: 21px;
            }

            .stat-card small {
                font-size: 10px;
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
            <a href="pesanan.php">📦 Manajemen Pesanan</a>
            <a href="layanan.php" class="active">🧼 Manajemen Layanan</a>
            <a href="../index.php">↗ Lihat Website</a>
        </nav>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Manajemen Layanan</h1>
                <p class="subtitle">
                    Atur katalog, harga, varian, ukuran, dan layanan tambahan.
                </p>
            </div>

            <a href="tambah_layanan.php" class="primary-btn">
                + Tambah Layanan
            </a>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat-card">
                <small>Total Layanan</small>
                <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
            </div>

            <div class="stat-card">
                <small>Layanan Aktif</small>
                <strong class="green"><?= (int) ($stats['aktif'] ?? 0) ?></strong>
            </div>

            <div class="stat-card">
                <small>Layanan Nonaktif</small>
                <strong class="gray"><?= (int) ($stats['nonaktif'] ?? 0) ?></strong>
            </div>
        </section>

        <form method="GET" class="filter-card">
            <input
                type="search"
                name="q"
                placeholder="Cari nama layanan..."
                value="<?= e($q) ?>">

            <select name="kategori">
                <option value="0">Semua kategori</option>

                <?php foreach ($kategori_list as $cat): ?>
                    <option
                        value="<?= (int) $cat['id'] ?>"
                        <?= $kategori === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">Semua status</option>
                <option value="Aktif" <?= $status === 'Aktif' ? 'selected' : '' ?>>
                    Aktif
                </option>
                <option value="Nonaktif" <?= $status === 'Nonaktif' ? 'selected' : '' ?>>
                    Nonaktif
                </option>
            </select>

            <button type="submit">Terapkan Filter</button>
        </form>

        <section class="table-card">
            <div class="table-heading">
                <div>
                    <h2>Daftar Layanan</h2>
                    <p><?= count($layanan_list) ?> layanan ditemukan</p>
                </div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Layanan</th>
                            <th>Kategori</th>
                            <th>Harga Dasar</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$layanan_list): ?>
                            <tr>
                                <td colspan="6" class="empty">
                                    Belum ada layanan atau tidak ada hasil pencarian.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($layanan_list as $item): ?>
                                <tr>
                                    <td>
                                        <div class="service">
                                            <?php if (!empty($item['gambar'])): ?>
                                                <img
                                                    src="../<?= e(ltrim($item['gambar'], '/')) ?>"
                                                    alt="<?= e($item['nama']) ?>">
                                            <?php else: ?>
                                                <div class="service-placeholder"></div>
                                            <?php endif; ?>

                                            <div>
                                                <strong><?= e($item['nama']) ?></strong>
                                                <small>
                                                    <?= e($item['badge'] ?: 'Tanpa badge') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <td><?= e($item['nama_kategori'] ?? 'Tanpa kategori') ?></td>

                                    <td class="price"><?= rupiah($item['harga']) ?></td>

                                    <td><?= e($item['waktu'] ?: '-') ?></td>

                                    <td>
                                        <span class="badge <?= layananBadgeClass($item['status']) ?>">
                                            <?= e($item['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <a href="edit_layanan.php?id=<?= (int)$item['id'] ?>" class="action-btn">
                                                Edit
                                            </a>

                                            <form
                                                action="handlers/layanan_handler.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin mengubah status layanan ini?')">

                                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                                <input type="hidden" name="aksi" value="toggle_status">
                                                <input type="hidden" name="layanan_id" value="<?= (int) $item['id'] ?>">

                                                <button type="submit" class="toggle-btn ">
                                                    <?= $item['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                        </div>
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