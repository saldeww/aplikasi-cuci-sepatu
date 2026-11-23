<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once 'config/database.php';
require_once 'functions/helper.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'Customer') {
    header('Location: admin/dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cart count untuk badge
$cart_count = 0;
$stmt_cart = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE user_id = ?");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_count = (int) $stmt_cart->get_result()->fetch_row()[0];
$stmt_cart->close();

$success = '';
$error = '';

// Ambil data pengguna
$stmt = mysqli_prepare($conn, "
    SELECT id, nama, email, no_wa, password, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Proses perubahan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Token keamanan tidak cocok. Silakan refresh halaman.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {

        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_wa = trim($_POST['no_wa'] ?? '');

        if ($nama === '' || $email === '' || $no_wa === '') {
            $error = 'Semua data profil wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($nama) > 100 || strlen($email) > 150 || strlen($no_wa) > 20) {
            $error = 'Ada data yang melebihi batas karakter.';
        } else {
            // Periksa email apakah sudah digunakan akun lain
            $stmt = mysqli_prepare($conn, "
                SELECT id
                FROM users
                WHERE email = ? AND id != ?
                LIMIT 1
            ");

            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);

            $email_exists = mysqli_num_rows(
                mysqli_stmt_get_result($stmt)
            ) > 0;

            mysqli_stmt_close($stmt);

            if ($email_exists) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE users
                    SET nama = ?, email = ?, no_wa = ?
                    WHERE id = ?
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $nama,
                    $email,
                    $no_wa,
                    $user_id
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION['nama'] = $nama;

                $success = 'Profil berhasil diperbarui.';

                // Perbarui data lokal
                $user['nama'] = $nama;
                $user['email'] = $email;
                $user['no_wa'] = $no_wa;
            }
        }
    } elseif ($action === 'change_password') {

        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if (
            $password_lama === '' ||
            $password_baru === '' ||
            $konfirmasi_password === ''
        ) {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (!password_verify($password_lama, $user['password'])) {
            $error = 'Password lama yang kamu masukkan salah.';
        } elseif (strlen($password_baru) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($password_baru !== $konfirmasi_password) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif ($password_lama === $password_baru) {
            $error = 'Password baru tidak boleh sama dengan password lama.';
        } else {
            $password_hash = password_hash(
                $password_baru,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare($conn, "
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $password_hash,
                $user_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $user['password'] = $password_hash;

            $success = 'Password berhasil diperbarui.';
        }
    } else {
        $error = 'Permintaan tidak valid.';
    }
}

$initial = strtoupper(substr($user['nama'], 0, 1));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Profil Saya | Cuci Disini</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        .profile-page {
            padding: 40px 0 70px;
        }

        .profile-heading {
            margin-bottom: 28px;
        }

        .profile-heading h1 {
            margin-bottom: 8px;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .profile-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
        }

        .profile-sidebar {
            text-align: center;
        }

        .profile-avatar {
            width: 82px;
            height: 82px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 32px;
            font-weight: 800;
        }

        .profile-sidebar h2 {
            font-size: 19px;
            margin: 0 0 6px;
            overflow-wrap: anywhere;
        }

        .profile-email {
            font-size: 13px;
            color: #777;
            overflow-wrap: anywhere;
        }

        .profile-role {
            display: inline-block;
            margin-top: 14px;
            padding: 7px 13px;
            border-radius: 30px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-menu {
            margin-top: 25px;
            text-align: left;
            display: grid;
            gap: 7px;
        }

        .profile-menu a {
            display: block;
            padding: 12px 14px;
            border-radius: 9px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .profile-menu a:hover,
        .profile-menu a.active {
            background: #eef2ff;
            color: #4f46e5;
        }

        .profile-content {
            display: grid;
            gap: 20px;
        }

        .profile-section h2 {
            font-size: 19px;
            margin: 0 0 7px;
        }

        .section-description {
            color: #777;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .profile-form label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .profile-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font: inherit;
            margin-bottom: 18px;
            outline: none;
            transition: .2s;
        }

        .profile-form input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        .profile-form .btn {
            border: none;
            cursor: pointer;
            font: inherit;
        }

        .profile-alert {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .profile-alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .profile-alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .password-note {
            font-size: 12px;
            color: #777;
            margin-top: -9px;
            margin-bottom: 18px;
        }

        .profile-divider {
            border: 0;
            border-top: 1px solid #eee;
            margin: 25px 0;
        }

        @media (max-width: 760px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                padding: 20px;
            }

            .profile-menu {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="index.php" class="brand">
                <span class="brand-mark">C</span>
                cuci disini
            </a>

            <div class="icon-row">
                <a href="profile.php" class="icon-btn" aria-label="Profil Saya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21c0-4 4-7 8-7s8 3 8 7" />
                    </svg>
                </a>

                <a href="keranjang.php" class="icon-btn icon-btn--cart" aria-label="Keranjang">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2l-2 5v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5z" />
                        <path d="M4 7h16" />
                        <path d="M16 11a4 4 0 0 1-8 0" />
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span class="count"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>

                <button class="nav-toggle" aria-label="Open menu" aria-expanded="false">≡</button>
            </div>
        </div>
    </header>

    <!-- Category Nav -->
    <nav class="nav-bar" aria-label="Primary">
        <div class="container">
            <a href="layanan.php" class="all-cats">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
                Semua Layanan
            </a>
            <div class="main-nav">
                <a href="index.php">Home</a>
                <a href="layanan.php">Layanan</a>
                <a href="pesanan.php">Pesanan</a>
                <a href="kontak.php">Kontak</a>
                <a href="feedback.php">Feedback</a>
            </div>
        </div>
    </nav>

    <!-- Mobile Drawer -->
    <div class="drawer" id="drawer" aria-hidden="true">
        <div class="drawer-head">
            <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a>
            <button class="drawer-close" aria-label="Close menu">Close ✕</button>
        </div>
        <a href="index.php">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="feedback.php">Feedback</a>
        <a href="profile.php">Profil Saya</a>
        <a href="logout.php">Keluar</a>
    </div>

    <main id="main" class="profile-page">

        <div class="container">

            <div class="crumbs">
                <a href="index.php">Home</a>
                <span class="sep">›</span>
                <span>Profil Saya</span>
            </div>

            <div class="profile-heading">
                <h1>Profil Saya</h1>
                <p>Kelola informasi akun dan keamanan kamu.</p>
            </div>

            <?php if ($success !== ''): ?>
                <div class="profile-alert success">
                    ✓ <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="profile-alert error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="profile-layout">

                <!-- SIDEBAR -->
                <aside class="profile-card profile-sidebar">

                    <div class="profile-avatar">
                        <?= e($initial) ?>
                    </div>

                    <h2><?= e($user['nama']) ?></h2>

                    <div class="profile-email">
                        <?= e($user['email']) ?>
                    </div>

                    <span class="profile-role">
                        Customer
                    </span>

                    <nav class="profile-menu">
                        <a href="profile.php" class="active">
                            Profil Saya
                        </a>

                        <a href="pesanan.php">
                            Pesanan Saya
                        </a>

                        <a href="keranjang.php">
                            Keranjang
                        </a>

                        <a href="layanan.php">
                            Semua Layanan
                        </a>

                        <a href="logout.php">
                            Keluar Akun
                        </a>
                    </nav>

                </aside>

                <!-- CONTENT -->
                <div class="profile-content">

                    <!-- INFORMASI PROFIL -->
                    <section class="profile-card profile-section">

                        <h2>Informasi Profil</h2>

                        <p class="section-description">
                            Perbarui informasi pribadi yang digunakan untuk pesanan.
                        </p>

                        <form method="POST" class="profile-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="update_profile">

                            <label for="nama">Nama Lengkap</label>

                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                maxlength="100"
                                value="<?= e($user['nama']) ?>"
                                required>

                            <label for="email">Alamat Email</label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="150"
                                value="<?= e($user['email']) ?>"
                                required>

                            <label for="no_wa">Nomor WhatsApp</label>

                            <input
                                type="tel"
                                id="no_wa"
                                name="no_wa"
                                maxlength="20"
                                value="<?= e($user['no_wa']) ?>"
                                placeholder="08xxxxxxxxxx"
                                required>

                            <button type="submit" class="btn">
                                Simpan Perubahan
                            </button>

                        </form>

                    </section>

                    <!-- GANTI PASSWORD -->
                    <section class="profile-card profile-section">

                        <h2>Keamanan Akun</h2>

                        <p class="section-description">
                            Gunakan password yang kuat dan jangan bagikan kepada orang lain.
                        </p>

                        <form method="POST" class="profile-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="change_password">

                            <label for="password_lama">
                                Password Saat Ini
                            </label>

                            <input
                                type="password"
                                id="password_lama"
                                name="password_lama"
                                autocomplete="current-password"
                                required>

                            <label for="password_baru">
                                Password Baru
                            </label>

                            <input
                                type="password"
                                id="password_baru"
                                name="password_baru"
                                minlength="8"
                                autocomplete="new-password"
                                required>

                            <p class="password-note">
                                Minimal 8 karakter.
                            </p>

                            <label for="konfirmasi_password">
                                Konfirmasi Password Baru
                            </label>

                            <input
                                type="password"
                                id="konfirmasi_password"
                                name="konfirmasi_password"
                                minlength="8"
                                autocomplete="new-password"
                                required>

                            <button type="submit" class="btn">
                                Ganti Password
                            </button>

                        </form>

                    </section>

                </div>

            </div>

        </div>

    </main>

    <script src="assets/js/main.js" defer></script>

</body>

</html>

<?php
mysqli_close($conn);
?>