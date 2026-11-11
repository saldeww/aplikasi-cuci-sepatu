<?php
session_start();

// Proteksi halaman admin
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'Admin'
) {
    header('Location: ../login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Cuci Disini</title>

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
            background: #f5f6fa;
            color: #1e293b;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: #17172e;
            color: white;
            padding: 30px 20px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 45px;
        }

        .brand span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: #6366f1;
            border-radius: 10px;
            margin-right: 8px;
        }

        .menu-label {
            color: #8585a3;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .menu a {
            display: block;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 10px;
            color: #c4c4d8;
            text-decoration: none;
            font-size: 14px;
        }

        .menu a.active {
            background: #6366f1;
            color: white;
        }

        .menu a:hover {
            background: #292947;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
        }

        .topbar h1 {
            font-size: 25px;
            font-weight: 800;
        }

        .topbar p {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        .logout {
            padding: 11px 18px;
            border-radius: 9px;
            background: #fee2e2;
            color: #dc2626;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .welcome {
            padding: 30px;
            border-radius: 18px;
            background: linear-gradient(120deg, #4f46e5, #7c3aed);
            color: white;
            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 23px;
            margin-bottom: 10px;
        }

        .welcome p {
            font-size: 14px;
            opacity: .85;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e9eaf0;
        }

        .card .icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            border-radius: 11px;
            font-size: 20px;
            margin-bottom: 18px;
        }

        .card h3 {
            font-size: 15px;
            margin-bottom: 7px;
        }

        .card p {
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .notice {
            margin-top: 25px;
            padding: 20px;
            background: white;
            border: 1px solid #e9eaf0;
            border-radius: 15px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 850px) {
            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 25px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                padding: 18px;
            }

            .brand {
                margin-bottom: 20px;
            }

            .menu-label {
                display: none;
            }

            .menu {
                display: flex;
                gap: 8px;
                overflow-x: auto;
            }

            .menu a {
                white-space: nowrap;
                margin: 0;
            }

            .main {
                margin-left: 0;
                padding: 22px 16px;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 20px;
            }

            .welcome h2 {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="brand">
            <span>C</span> Cuci Disini
        </div>

        <div class="menu-label">MENU UTAMA</div>

        <nav class="menu">
            <a href="dashboard.php" class="active">⌂ Dashboard</a>
            <a href="../index.php">↗ Lihat Website</a>
        </nav>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Selamat datang di panel administrasi Cuci Disini.</p>
            </div>

            <a href="../logout.php" class="logout">
                Logout ↗
            </a>
        </header>

        <section class="welcome">
            <h2>Halo, <?= htmlspecialchars($nama) ?> 👋</h2>
            <p>
                Kamu berhasil masuk sebagai Administrator.
                Ini adalah dashboard sementara.
            </p>
        </section>

        <section class="cards">
            <div class="card">
                <div class="icon">📦</div>
                <h3>Manajemen Pesanan</h3>
                <p>Pengelolaan pesanan pelanggan akan tersedia di sini.</p>
            </div>

            <div class="card">
                <div class="icon">🧼</div>
                <h3>Manajemen Layanan</h3>
                <p>Kelola layanan, kategori, harga, dan status layanan.</p>
            </div>

            <div class="card">
                <div class="icon">👥</div>
                <h3>Data Pelanggan</h3>
                <p>Lihat dan kelola data akun pelanggan.</p>
            </div>
        </section>

        <div class="notice">
            <strong>Dashboard sementara</strong><br>
            Seluruh menu di atas masih berupa gambaran fitur yang akan
            dikembangkan. Belum ada data transaksi atau statistik yang
            ditampilkan.
        </div>

    </main>

</body>

</html>