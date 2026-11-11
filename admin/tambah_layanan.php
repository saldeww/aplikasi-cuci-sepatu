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

$kategori_result = $conn->query("
    SELECT id, nama
    FROM category
    ORDER BY nama ASC
");

$kategori_list = [];

while ($row = $kategori_result->fetch_assoc()) {
    $kategori_list[] = $row;
}

$flash = $_SESSION['flash_layanan'] ?? null;
unset($_SESSION['flash_layanan']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Layanan | Cuci Disini</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            max-width: 1250px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
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

        .flash.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .flash.success {
            background: #dcfce7;
            color: #166534;
        }

        .form-card {
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 18px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 19px;
        }

        .section-heading h2 {
            font-size: 15px;
        }

        .section-heading p {
            color: #9299a8;
            font-size: 11px;
            margin-top: 5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .field {
            min-width: 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #e2e5ed;
            border-radius: 9px;
            outline: none;
            background: white;
            color: #172033;
            font: inherit;
            font-size: 12px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px #eef2ff;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .hint {
            display: block;
            margin-top: 6px;
            color: #9299a8;
            font-size: 10px;
        }

        .image-preview {
            display: none;
            width: 150px;
            height: 115px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 12px;
            border: 1px solid #e5e7eb;
        }

        .repeat-list {
            display: grid;
            gap: 10px;
        }

        .repeat-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) 38px;
            gap: 9px;
            align-items: end;
            padding: 13px;
            border: 1px solid #edf0f4;
            border-radius: 11px;
            background: #fafbfc;
        }

        .repeat-row.addon-row {
            grid-template-columns: 1fr 1.2fr .7fr .7fr 38px;
        }

        .repeat-row.size-row {
            grid-template-columns: .7fr 1fr .8fr 38px;
        }

        .remove-btn {
            width: 38px;
            height: 39px;
            border: 0;
            border-radius: 8px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 18px;
            cursor: pointer;
        }

        .add-btn {
            margin-top: 12px;
            padding: 10px 13px;
            border: 1px dashed #a5b4fc;
            border-radius: 9px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-area {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-bottom: 30px;
        }

        .cancel-btn,
        .save-btn {
            padding: 13px 19px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .cancel-btn {
            background: white;
            border: 1px solid #e2e5ed;
            color: #64748b;
        }

        .save-btn {
            border: 0;
            background: #4f46e5;
            color: white;
        }

        .save-btn:hover {
            background: #4338ca;
        }

        @media(max-width: 900px) {
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

        @media(max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .repeat-row,
            .repeat-row.addon-row,
            .repeat-row.size-row {
                grid-template-columns: 1fr 1fr;
            }

            .repeat-row .field:first-child {
                grid-column: 1 / -1;
            }

            .remove-btn {
                grid-column: 2;
                justify-self: end;
            }

            .form-card {
                padding: 17px;
            }

            .topbar {
                align-items: flex-start;
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
                <h1>Tambah Layanan</h1>
                <p class="subtitle">
                    Buat layanan baru beserta pilihan paket dan tambahannya.
                </p>
            </div>

            <a href="layanan.php" class="back-link">← Kembali</a>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!$kategori_list): ?>
            <div class="flash error">
                Belum ada kategori. Tambahkan kategori terlebih dahulu sebelum membuat layanan.
            </div>
        <?php endif; ?>

        <form
            action="handlers/layanan_handler.php"
            method="POST"
            enctype="multipart/form-data"
            id="layananForm">

            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="tambah">

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Informasi Utama</h2>
                        <p>Informasi yang akan ditampilkan pada katalog layanan.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>Nama Layanan *</label>
                        <input type="text" name="nama" maxlength="150" required placeholder="Contoh: Deep Cleaning">
                    </div>

                    <div class="field">
                        <label>Kategori *</label>
                        <select name="category_id" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($kategori_list as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>">
                                    <?= e($cat['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Jelaskan detail layanan..."></textarea>
                    </div>

                    <div class="field">
                        <label>Harga Dasar (Rp) *</label>
                        <input type="number" name="harga" min="0" step="0.01" required placeholder="35000">
                        <span class="hint">Harga dasar yang ditampilkan di katalog.</span>
                    </div>

                    <div class="field">
                        <label>Estimasi Waktu</label>
                        <input type="text" name="waktu" maxlength="100" placeholder="Contoh: 2-3 hari">
                    </div>

                    <div class="field">
                        <label>Badge / Label</label>
                        <input type="text" name="badge" maxlength="100" placeholder="Contoh: Best Seller">
                    </div>

                    <div class="field">
                        <label>Status Layanan</label>
                        <select name="status">
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Gambar Layanan</label>
                        <input type="file" name="gambar" id="gambar" accept="image/jpeg,image/png,image/webp">
                        <span class="hint">Format JPG, PNG, atau WEBP. Maksimal 3 MB.</span>
                        <img id="preview" class="image-preview" alt="Preview gambar layanan">
                    </div>
                </div>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Varian Layanan</h2>
                        <p>Minimal satu varian wajib ditambahkan. Contoh: Regular, Express, Premium.</p>
                    </div>
                </div>

                <div class="repeat-list" id="varianList">
                    <div class="repeat-row">
                        <div class="field">
                            <label>Nama Varian</label>
                            <input name="varian[nama][]" placeholder="Regular">
                        </div>
                        <div class="field">
                            <label>Label Durasi</label>
                            <input name="varian[durasi][]" placeholder="2-3 hari">
                        </div>
                        <div class="field">
                            <label>Harga (Rp)</label>
                            <input type="number" name="varian[harga][]" min="0" step="0.01" placeholder="35000">
                        </div>
                        <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                    </div>
                </div>

                <button type="button" class="add-btn" onclick="addVarian()">+ Tambah Varian</button>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Ukuran Sepatu</h2>
                        <p>Tambahkan biaya berdasarkan ukuran. Boleh dikosongkan.</p>
                    </div>
                </div>

                <div class="repeat-list" id="ukuranList">
                    <div class="repeat-row size-row">
                        <div class="field">
                            <label>Kode Ukuran</label>
                            <input name="ukuran[kode][]" placeholder="XL">
                        </div>
                        <div class="field">
                            <label>Nama Ukuran</label>
                            <input name="ukuran[nama][]" placeholder="Sepatu ukuran besar">
                        </div>
                        <div class="field">
                            <label>Harga Tambahan (Rp)</label>
                            <input type="number" name="ukuran[harga][]" min="0" step="0.01" placeholder="5000">
                        </div>
                        <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                    </div>
                </div>

                <button type="button" class="add-btn" onclick="addUkuran()">+ Tambah Ukuran</button>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Addon / Layanan Tambahan</h2>
                        <p>Contoh: Repaint, unyellowing, deodorizer. Boleh dikosongkan.</p>
                    </div>
                </div>

                <div class="repeat-list" id="addonList">
                    <div class="repeat-row addon-row">
                        <div class="field">
                            <label>Nama Addon</label>
                            <input name="addon[nama][]" placeholder="Deodorizer">
                        </div>
                        <div class="field">
                            <label>Deskripsi</label>
                            <input name="addon[deskripsi][]" placeholder="Menghilangkan bau">
                        </div>
                        <div class="field">
                            <label>Harga (Rp)</label>
                            <input type="number" name="addon[harga][]" min="0" step="0.01" placeholder="10000">
                        </div>
                        <div class="field">
                            <label>Wajib?</label>
                            <select name="addon[wajib][]">
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                        </div>
                        <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                    </div>
                </div>

                <button type="button" class="add-btn" onclick="addAddon()">+ Tambah Addon</button>
            </section>

            <div class="submit-area">
                <a href="layanan.php" class="cancel-btn">Batal</a>
                <button
                    type="submit"
                    class="save-btn"
                    <?= !$kategori_list ? 'disabled' : '' ?>>
                    Simpan Layanan
                </button>
            </div>

        </form>
    </main>

    <script>
        function removeRow(button) {
            const row = button.closest('.repeat-row');
            const list = row.parentElement;

            if (list.children.length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(input => input.value = '');
                row.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            }
        }

        function addVarian() {
            document.getElementById('varianList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row">
            <div class="field">
                <label>Nama Varian</label>
                <input name="varian[nama][]" placeholder="Express">
            </div>
            <div class="field">
                <label>Label Durasi</label>
                <input name="varian[durasi][]" placeholder="1 hari">
            </div>
            <div class="field">
                <label>Harga (Rp)</label>
                <input type="number" name="varian[harga][]" min="0" step="0.01" placeholder="50000">
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
        </div>
    `);
        }

        function addUkuran() {
            document.getElementById('ukuranList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row size-row">
            <div class="field">
                <label>Kode Ukuran</label>
                <input name="ukuran[kode][]" placeholder="XXL">
            </div>
            <div class="field">
                <label>Nama Ukuran</label>
                <input name="ukuran[nama][]" placeholder="Sepatu ekstra besar">
            </div>
            <div class="field">
                <label>Harga Tambahan (Rp)</label>
                <input type="number" name="ukuran[harga][]" min="0" step="0.01" placeholder="10000">
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
        </div>
    `);
        }

        function addAddon() {
            document.getElementById('addonList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row addon-row">
            <div class="field">
                <label>Nama Addon</label>
                <input name="addon[nama][]" placeholder="Repaint">
            </div>
            <div class="field">
                <label>Deskripsi</label>
                <input name="addon[deskripsi][]" placeholder="Pengecatan ulang">
            </div>
            <div class="field">
                <label>Harga (Rp)</label>
                <input type="number" name="addon[harga][]" min="0" step="0.01" placeholder="25000">
            </div>
            <div class="field">
                <label>Wajib?</label>
                <select name="addon[wajib][]">
                    <option value="0">Tidak</option>
                    <option value="1">Ya</option>
                </select>
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
        </div>
    `);
        }

        document.getElementById('gambar').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('preview');

            if (!file) {
                preview.style.display = 'none';
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                alert('Ukuran gambar maksimal 3 MB.');
                this.value = '';
                preview.style.display = 'none';
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        });
    </script>

</body>

</html>