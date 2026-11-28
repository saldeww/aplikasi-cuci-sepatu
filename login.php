<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
  header('Location: ' . (($_SESSION['role'] ?? '') === 'Admin'
    ? 'admin/dashboard.php'
    : 'index.php'));
  exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login_type = $_POST['login_type'] ?? '';
  $identifier = trim($_POST['identifier'] ?? '');
  $password = $_POST['password'] ?? '';

  // Whitelist kolom agar tidak bisa diinjeksi melalui nama kolom SQL
  $allowed_columns = [
    'email'    => 'email',
    'no_wa'    => 'no_wa',
  ];

  if (
    !isset($allowed_columns[$login_type]) ||
    $identifier === '' ||
    $password === ''
  ) {
    $error_message = 'Lengkapi semua data login.';
  } else {
    $column = $allowed_columns[$login_type];

    $sql = "SELECT id, email, password, role
                FROM users
                WHERE `$column` = ?
                LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $identifier);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
      session_regenerate_id(true);

      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];

      $stmt->close();

      if ($user['role'] === 'Admin') {
        header('Location: admin/dashboard.php');
      } else {
        header('Location: index.php');
      }
      exit;
    }

    $error_message = 'Identitas atau password yang kamu masukkan salah.';
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Club | Cuci Disini</title>
  <meta
    name="description"
    content="Login ke akun Cuci Disini Club untuk mendapatkan berbagai layanan perawatan sepatu, tas, dan helm terbaik." />
  <meta name="theme-color" content="#4F46E5" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" />
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
      background: transparent;
      border: none;
      cursor: pointer;
      color: var(--fg-soft);
      font-size: 18px;
      z-index: 2;
    }

    .password-toggle i {
      display: inline-block;
    }
  </style>
</head>

<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand"><span class="brand-mark">C</span> Cuci Disini</a>
      <button class="nav-toggle" aria-label="Open menu">≡</button>
    </div>
  </header>

  <nav class="nav-bar" aria-label="Primary">
    <div class="container">
      <a href="layanan.php" class="all-cats">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>Semua Layanan</a>
      <div class="main-nav">
        <a href="index.php">Home</a>
        <a href="layanan.php">Layanan</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="kontak.php">Kontak</a>
        <a href="feedback.php">Feedback</a>
        <a href="login.php" aria-current="page">Login</a>
      </div>
    </div>
  </nav>

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
    <a href="login.php" class="btn btn--indigo" style="margin-top:var(--s5);justify-content:center">Login / Daftar →</a>
  </div>

  <main id="main">
    <section class="page-head">
      <div class="container">
        <div class="crumbs">
          <a href="index.php">Home</a> <span class="sep">›</span>
          <span>Login</span>
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
                  background: linear-gradient(
                    135deg,
                    var(--indigo),
                    var(--card-purple)
                  );
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
                  Butuh Layanan Kemitraan atau Jumlah Besar?
                </h3>
                <p
                  style="
                      color: rgba(255, 255, 255, 0.85);
                      font-size: var(--text-sm);
                      line-height: 1.6;
                      margin-bottom: var(--s4);
                    ">
                  Pemesanan kolektif atau instansi di atas 10 unit mendapatkan
                  diskon bertingkat, syarat pembayaran khusus, dan penanganan
                  prioritas.
                </p>
                <a href="kontak.php" class="btn btn--paper">Hubungi Tim Kami →</a>
              </div>
            </div>
          </div>

          <form class="contact-form" action="login.php" method="POST">
            <h2 style="font-size: var(--text-xl); margin-bottom: var(--s2)">
              Login Account
            </h2>

            <p style="
        color: var(--fg-soft);
        font-size: var(--text-sm);
        margin-bottom: var(--s5);
    ">
              Selamat datang kembali di Cuci Disini Club.
              Masuk untuk mengelola akun dan pesananmu.
            </p>

            <?php if (!empty($error_message)): ?>
              <div style="
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        ">
                <?= htmlspecialchars($error_message) ?>
              </div>
            <?php endif; ?>

            <div class="field">
              <label for="login_type">Login menggunakan</label>

              <select name="login_type" id="login_type" required>
                <option value="email">Email</option>
                <option value="no_wa">Nomor Whatsapp</option>
              </select>
            </div>

            <div class="field">
              <label for="identifier">Identitas akun</label>

              <input
                type="text"
                id="identifier"
                name="identifier"
                placeholder="Masukkan email"
                value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                required>
            </div>
            <div class="field">
              <label for="password">Password</label>

              <div class="password-wrapper">
                <input
                  type="password"
                  name="password"
                  id="password"
                  autocomplete="current-password"
                  placeholder="Masukkan password"
                  required>

                <button
                  type="button"
                  class="password-toggle"
                  id="togglePassword"
                  aria-label="Tampilkan password"
                  aria-pressed="false">
                  <i class="fa-solid fa-eye"></i>
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
              Login →
            </button>

            <p style="
        font-size: 13px;
        color: var(--fg-soft);
        margin-top: var(--s4);
        text-align: center;
    ">
              Belum punya akun?
              <a href="register.php" style="color: var(--indigo)">
                Daftar sekarang
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
        <span>© <?php echo date('Y'); ?> Cuci Disini. All rights reserved.</span>
      </div>
    </div>
  </footer>
  <script src="assets/js/main.js" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const loginType = document.getElementById('login_type');
      const identifier = document.getElementById('identifier');

      if (!loginType || !identifier) return;

      const placeholders = {
        email: 'Masukkan email',
        no_wa: 'Masukkan nomor WhatsApp',
      };

      function updatePlaceholder() {
        identifier.placeholder = placeholders[loginType.value] || 'Masukkan identitas akun';
      }

      loginType.addEventListener('change', updatePlaceholder);
      updatePlaceholder();
    });
    document.addEventListener('DOMContentLoaded', function() {
      const password = document.getElementById('password');
      const toggle = document.getElementById('togglePassword');
      const icon = toggle.querySelector('i');

      toggle.addEventListener('click', function() {
        const isHidden = password.type === 'password';

        password.type = isHidden ? 'text' : 'password';

        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);

        toggle.setAttribute('aria-pressed', String(isHidden));
        toggle.setAttribute(
          'aria-label',
          isHidden ? 'Sembunyikan password' : 'Tampilkan password'
        );
      });
    });
  </script>
</body>