<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, arahkan sesuai role.
if (isset($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? '') === 'Admin')
        ? 'admin/dashboard.php'
        : 'index.php';

    header("Location: {$redirect}");
    exit;
}

$error_message = '';
$success_message = '';

$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error_message = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password minimal harus terdiri dari 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Konfirmasi password tidak cocok.';
    } else {
        // Periksa apakah email sudah digunakan.
        $stmt_check = $conn->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );
        $stmt_check->bind_param('s', $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
            $stmt_check->close();
        } else {
            $stmt_check->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Customer';

            $stmt_insert = $conn->prepare(
                'INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)'
            );
            $stmt_insert->bind_param('ssss', $nama, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                $success_message = 'Registrasi berhasil! Silakan login dengan akun baru kamu.';
                // Kosongkan kembali nilai form setelah registrasi berhasil.
                $nama = '';
                $email = '';
            } else {
                $error_message = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
            }

            $stmt_insert->close();
        }
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Cuci Disini</title>
    <meta
        name="description"
        content="Daftar akun baru di Cuci Disini Club untuk menikmati layanan perawatan sepatu, tas, dan helm terbaik.">
    <meta name="theme-color" content="#4F46E5">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            color: var(--fg-soft);
            background: transparent;
            border: 0;
            cursor: pointer;
            font-size: 18px;
            z-index: 2;
        }

        .password-toggle:focus-visible {
            outline: 2px solid var(--indigo);
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="container">
            <a href="index.php" class="brand">
                <span class="brand-mark">C</span> Cuci Disini
            </a>
            <button class="nav-toggle" type="button" aria-label="Open menu">≡</button>
        </div>
    </header>

    <nav class="nav-bar" aria-label="Primary">
        <div class="container">
            <a href="layanan.php" class="all-cats">
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.4"
                    stroke-linecap="round"
                    aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                Semua Layanan
            </a>

            <div class="main-nav">
                <a href="index.php">Home</a>
                <a href="layanan.php">Layanan</a>
                <a href="pesanan.php">Pesanan</a>
                <a href="kontak.php">Kontak</a>
                <a href="feedback.php">Feedback</a>
                <a href="login.php">Login</a>
            </div>
        </div>
    </nav>

    <div class="drawer" id="drawer" aria-hidden="true">
        <div class="drawer-head">
            <a href="index.php" class="brand">
                <span class="brand-mark">C</span> Cuci Disini
            </a>
            <button class="drawer-close" type="button" aria-label="Close menu">
                Close ✕
            </button>
        </div>

        <a href="index.php">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="feedback.php">Feedback</a>
        <a
            href="login.php"
            class="btn btn--indigo"
            style="margin-top: var(--s5); justify-content: center">
            Login / Daftar →
        </a>
    </div>

    <main id="main">
        <section class="page-head">
            <div class="container">
                <div class="crumbs">
                    <a href="index.php">Home</a>
                    <span class="sep">›</span>
                    <span>Register</span>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="contact-grid">
                    <div class="contact-info">
                        <div
                            style="
                                margin-top: var(--s7);
                                padding: var(--s6);
                                background: linear-gradient(135deg, var(--indigo), var(--card-purple));
                                color: var(--paper);
                                border-radius: var(--r-lg);
                                position: relative;
                                overflow: hidden;
                            ">
                            <div
                                style="
                                    position: absolute;
                                    inset: 0;
                                    background-image: radial-gradient(
                                        circle at 80% 20%,
                                        rgba(255, 255, 255, 0.18) 0,
                                        transparent 40%
                                    );
                                    pointer-events: none;
                                "></div>

                            <div style="position: relative">
                                <h3
                                    style="
                                        color: var(--paper);
                                        font-size: var(--text-xl);
                                        margin-bottom: var(--s3);
                                    ">
                                    Bergabung Bersama Cuci Disini Club
                                </h3>

                                <p
                                    style="
                                        color: rgba(255, 255, 255, 0.85);
                                        font-size: var(--text-sm);
                                        line-height: 1.6;
                                        margin-bottom: var(--s4);
                                    ">
                                    Dapatkan kemudahan melacak pesanan, riwayat perawatan sepatu
                                    &amp; tas kesayangan, serta promo eksklusif khusus member.
                                </p>

                                <a href="layanan.php" class="btn btn--paper">
                                    Lihat Layanan Kami →
                                </a>
                            </div>
                        </div>
                    </div>

                    <form class="contact-form" action="register.php" method="POST">
                        <h2 style="font-size: var(--text-xl); margin-bottom: var(--s2)">
                            Create Account
                        </h2>

                        <p
                            style="
                                color: var(--fg-soft);
                                font-size: var(--text-sm);
                                margin-bottom: var(--s5);
                            ">
                            Daftar akun baru dan nikmati kemudahan perawatan item favoritmu.
                        </p>

                        <?php if ($error_message !== ''): ?>
                            <div
                                role="alert"
                                style="
                                    padding: 12px;
                                    background: #fee2e2;
                                    color: #991b1b;
                                    border-radius: 6px;
                                    margin-bottom: 16px;
                                    font-size: 14px;
                                ">
                                <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message !== ''): ?>
                            <div
                                role="status"
                                style="
                                    padding: 12px;
                                    background: #d1fae5;
                                    color: #065f46;
                                    border-radius: 6px;
                                    margin-bottom: 16px;
                                    font-size: 14px;
                                ">
                                <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>

                                <div style="margin-top: 8px">
                                    <a
                                        href="login.php"
                                        style="color: #047857; font-weight: 600; text-decoration: underline">
                                        Klik di sini untuk login →
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="field">
                            <label for="c-nama">Nama Lengkap</label>
                            <input
                                id="c-nama"
                                name="nama"
                                type="text"
                                required
                                autocomplete="name"
                                placeholder="Nama lengkap kamu..."
                                value="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="field">
                            <label for="c-email">Email</label>
                            <input
                                id="c-email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="nama@email.com"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                    placeholder="Masukkan password">
                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="confirm_password">Konfirmasi Password</label>
                            <div class="password-wrapper">
                                <input
                                    id="confirm_password"
                                    name="confirm_password"
                                    type="password"
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                    placeholder="Ulangi password">
                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="confirm_password"
                                    aria-label="Tampilkan konfirmasi password"
                                    aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button
                            class="btn btn--indigo btn--block"
                            type="submit"
                            style="
                                padding: 16px;
                                font-size: var(--text-base);
                                margin-top: var(--s2);
                            ">
                            Daftar Sekarang →
                        </button>

                        <p
                            style="
                                font-size: 13px;
                                color: var(--fg-soft);
                                margin-top: var(--s4);
                                text-align: center;
                            ">
                            Sudah punya akun?
                            <a href="login.php" style="color: var(--indigo)">
                                Login di sini
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="mark">
                        <span class="brand-mark">C</span> Cuci Disini
                    </div>
                    <p>Cuci tas sepatu semuanya disini, Sejak 2025</p>
                </div>

                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <ul>
                        <li><a href="#">Akun Saya</a></li>
                        <li><a href="pesanan.php">Pesanan Saya</a></li>
                        <li><a href="#">Kebijakan Pengiriman</a></li>
                        <li><a href="#">Pusat Bantuan</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Tentang Kami</h4>
                    <ul>
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="kontak.php">Kontak</a></li>
                        <li><a href="login.php">Login / Register</a></li>
                        <li><a href="#">Syarat &amp; Ketentuan</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© <?= date('Y') ?> Cuci Disini. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js" defer></script>
    <script>
        document.querySelectorAll('.password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const icon = button.querySelector('i');

                if (!input || !icon) return;

                const shouldShow = input.type === 'password';
                input.type = shouldShow ? 'text' : 'password';

                icon.classList.toggle('fa-eye', !shouldShow);
                icon.classList.toggle('fa-eye-slash', shouldShow);

                button.setAttribute('aria-pressed', String(shouldShow));
                button.setAttribute(
                    'aria-label',
                    shouldShow ? 'Sembunyikan password' : 'Tampilkan password'
                );
            });
        });
    </script>
</body>

</html>