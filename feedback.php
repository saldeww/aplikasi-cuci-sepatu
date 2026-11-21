<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/config/database.php';

function feedbackEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function feedbackInput($key)
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$kategori_list = [
    'Saran',
    'Keluhan',
    'Apresiasi',
    'Laporan Bug',
    'Lainnya'
];

$error = '';
$success = $_SESSION['flash_feedback'] ?? '';
unset($_SESSION['flash_feedback']);

if (empty($_SESSION['csrf_feedback'])) {
    $_SESSION['csrf_feedback'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_feedback']) ||
        !hash_equals($_SESSION['csrf_feedback'], $token)
    ) {
        $error = 'Sesi formulir tidak valid. Silakan refresh halaman.';
    } else {

        $nama = feedbackInput('nama');
        $email = feedbackInput('email');
        $kategori = feedbackInput('kategori');
        $rating = filter_var(
            $_POST['rating'] ?? null,
            FILTER_VALIDATE_INT
        );
        $subjek = feedbackInput('subjek');
        $pesan = feedbackInput('pesan');

        if (
            $nama === '' ||
            $kategori === '' ||
            $subjek === '' ||
            $pesan === ''
        ) {
            $error = 'Semua kolom wajib diisi kecuali email.';
        } elseif (
            mb_strlen($nama) > 150 ||
            mb_strlen($email) > 150 ||
            mb_strlen($subjek) > 200 ||
            mb_strlen($pesan) < 10 ||
            mb_strlen($pesan) > 5000
        ) {
            $error = 'Periksa kembali panjang informasi yang kamu masukkan.';
        } elseif (
            $email !== '' &&
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $error = 'Format email tidak valid.';
        } elseif (!in_array($kategori, $kategori_list, true)) {
            $error = 'Kategori feedback tidak valid.';
        } elseif (
            $rating === false ||
            $rating < 1 ||
            $rating > 5
        ) {
            $error = 'Silakan pilih rating antara 1 sampai 5.';
        } else {

            try {
                $user_id = !empty($_SESSION['user_id'])
                    ? (int) $_SESSION['user_id']
                    : null;

                $stmt = $conn->prepare("
                    INSERT INTO feedback_saran (
                        user_id,
                        nama,
                        email,
                        kategori,
                        rating,
                        subjek,
                        pesan
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "isssiss",
                    $user_id,
                    $nama,
                    $email,
                    $kategori,
                    $rating,
                    $subjek,
                    $pesan
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['flash_feedback'] =
                    'Terima kasih! Feedback kamu berhasil dikirim.';

                $_SESSION['csrf_feedback'] =
                    bin2hex(random_bytes(32));

                header('Location: feedback.php');
                exit;
            } catch (Throwable $exception) {

                error_log(
                    'Feedback error: ' . $exception->getMessage()
                );

                $error = 'Feedback gagal dikirim. Silakan coba lagi.';
            }
        }
    }
}

$old_nama = feedbackInput('nama');
$old_email = feedbackInput('email');
$old_kategori = feedbackInput('kategori');
$old_rating = feedbackInput('rating');
$old_subjek = feedbackInput('subjek');
$old_pesan = feedbackInput('pesan');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Feedback & Saran | Cuci Disini</title>

    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        .feedback-wrapper {
            max-width: 760px;
            margin: 35px auto;
        }

        .feedback-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .04);
        }

        .feedback-heading {
            margin-bottom: 25px;
        }

        .feedback-heading h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .feedback-heading p {
            color: #6b7280;
            line-height: 1.6;
        }

        .feedback-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feedback-form input,
        .feedback-form select,
        .feedback-form textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            margin-bottom: 20px;
            box-sizing: border-box;
            font: inherit;
            outline: none;
            transition: border-color .2s;
        }

        .feedback-form input:focus,
        .feedback-form select:focus,
        .feedback-form textarea:focus {
            border-color: #2563eb;
        }

        .feedback-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .feedback-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 22px;
        }

        .feedback-rating input {
            display: none;
        }

        .feedback-rating label {
            font-size: 34px;
            color: #d1d5db;
            cursor: pointer;
            margin: 0;
            transition: color .15s;
        }

        .feedback-rating label:hover,
        .feedback-rating label:hover~label,
        .feedback-rating input:checked~label {
            color: #f59e0b;
        }

        .feedback-alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .feedback-alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .feedback-alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .feedback-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .feedback-submit:hover {
            background: #1d4ed8;
        }

        .feedback-note {
            color: #6b7280;
            font-size: 13px;
            margin-top: 18px;
            line-height: 1.6;
        }

        @media(max-width: 600px) {
            .feedback-wrapper {
                margin: 20px auto;
            }

            .feedback-card {
                padding: 20px;
            }

            .feedback-heading h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="container">
            <a href="index.php" class="brand">
                <span class="brand-mark">C</span>
                cuci disini
            </a>

            <div class="icon-row">
                <a href="index.php" class="icon-btn">Beranda</a>
                <a href="keranjang.php" class="icon-btn">Keranjang</a>
                <a href="pesanan.php" class="icon-btn">Pesanan</a>
            </div>
        </div>
    </header>

    <main class="section">
        <div class="container">

            <div class="crumbs">
                <a href="index.php">Home</a>
                <span class="sep">›</span>
                <span>Feedback & Saran</span>
            </div>

            <div class="feedback-wrapper">

                <div class="feedback-card">

                    <div class="feedback-heading">
                        <h1>Feedback & Saran</h1>

                        <p>
                            Punya saran, kritik, atau menemukan masalah
                            saat menggunakan Cuci Disini?
                            Ceritakan kepada kami. Masukan kamu membantu
                            kami memberikan pelayanan yang lebih baik.
                        </p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="feedback-alert error">
                            <?= feedbackEscape($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success !== ''): ?>
                        <div class="feedback-alert success">
                            <?= feedbackEscape($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="feedback-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= feedbackEscape($_SESSION['csrf_feedback']) ?>">

                        <label for="nama">Nama Lengkap *</label>

                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            maxlength="150"
                            value="<?= feedbackEscape($old_nama) ?>"
                            placeholder="Masukkan nama kamu"
                            required>

                        <label for="email">Email</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            value="<?= feedbackEscape($old_email) ?>"
                            placeholder="email@contoh.com">

                        <label for="kategori">Kategori Feedback *</label>

                        <select id="kategori" name="kategori" required>
                            <option value="">Pilih kategori</option>

                            <?php foreach ($kategori_list as $kategori): ?>
                                <option
                                    value="<?= feedbackEscape($kategori) ?>"
                                    <?= $old_kategori === $kategori ? 'selected' : '' ?>>
                                    <?= feedbackEscape($kategori) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <label>Bagaimana pengalaman kamu? *</label>

                        <div class="feedback-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input
                                    type="radio"
                                    id="rating<?= $i ?>"
                                    name="rating"
                                    value="<?= $i ?>"
                                    <?= (string) $old_rating === (string) $i ? 'checked' : '' ?>
                                    required>

                                <label for="rating<?= $i ?>" title="<?= $i ?> bintang">
                                    ★
                                </label>
                            <?php endfor; ?>
                        </div>

                        <label for="subjek">Judul Feedback *</label>

                        <input
                            type="text"
                            id="subjek"
                            name="subjek"
                            maxlength="200"
                            value="<?= feedbackEscape($old_subjek) ?>"
                            placeholder="Contoh: Tampilan checkout lebih mudah"
                            required>

                        <label for="pesan">Pesan Kamu *</label>

                        <textarea
                            id="pesan"
                            name="pesan"
                            minlength="10"
                            maxlength="5000"
                            placeholder="Ceritakan saran, kritik, atau masalah yang kamu temukan..."
                            required><?= feedbackEscape($old_pesan) ?></textarea>

                        <button type="submit" class="feedback-submit">
                            Kirim Feedback
                        </button>

                        <p class="feedback-note">
                            Feedback kamu akan disimpan dan ditinjau oleh
                            tim Cuci Disini. Terima kasih sudah membantu
                            kami berkembang.
                        </p>

                    </form>

                </div>

            </div>
        </div>
    </main>

    <script src="assets/js/main.js" defer></script>

</body>

</html>