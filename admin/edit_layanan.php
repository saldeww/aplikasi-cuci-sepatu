<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helper.php';

if (empty($_SESSION['csrf_layanan'])) {
    $_SESSION['csrf_layanan'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: layanan.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM layanan WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$layanan = $stmt->get_result()->fetch_assoc();

if (!$layanan) {
    $_SESSION['flash_layanan'] = [
        'type' => 'error',
        'message' => 'Layanan tidak ditemukan.'
    ];
    header('Location: layanan.php');
    exit;
}

$kategori_result = $conn->query("SELECT id, nama FROM category ORDER BY nama ASC");
$kategori_list = $kategori_result->fetch_all(MYSQLI_ASSOC);

function ambilDataAnak($conn, $tabel, $layanan_id)
{
    $allowed = ['layanan_varian', 'layanan_ukuran', 'layanan_addon'];

    if (!in_array($tabel, $allowed, true)) {
        return [];
    }

    $stmt = $conn->prepare("SELECT * FROM {$tabel} WHERE layanan_id = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param("i", $layanan_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$varian_list = ambilDataAnak($conn, 'layanan_varian', $id);
$ukuran_list = ambilDataAnak($conn, 'layanan_ukuran', $id);
$addon_list = ambilDataAnak($conn, 'layanan_addon', $id);

$flash = $_SESSION['flash_layanan'] ?? null;
unset($_SESSION['flash_layanan']);

function nilai($value)
{
    return e((string)($value ?? ''));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Layanan | Cuci Disini</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f6f7fb;
            color: #172033
        }

        a {
            text-decoration: none;
            color: inherit
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 245px;
            padding: 27px 18px;
            background: #17172e;
            color: white
        }

        .brand {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 40px
        }

        .brand span {
            display: inline-grid;
            place-items: center;
            width: 37px;
            height: 37px;
            margin-right: 8px;
            border-radius: 11px;
            background: #6366f1
        }

        .menu-label {
            color: #8585a3;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0 10px 12px
        }

        .menu {
            display: grid;
            gap: 7px
        }

        .menu a {
            padding: 13px;
            border-radius: 10px;
            color: #c4c4d8;
            font-size: 13px;
            font-weight: 600
        }

        .menu a:hover,
        .menu a.active {
            background: #6366f1;
            color: white
        }

        .main {
            margin-left: 245px;
            padding: 32px;
            max-width: 1450px
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px
        }

        h1 {
            font-size: 24px;
            font-weight: 800
        }

        .subtitle {
            color: #8991a2;
            font-size: 12px;
            margin-top: 6px
        }

        .back-link,
        .cancel-btn {
            padding: 11px 15px;
            background: white;
            border: 1px solid #e7e9f0;
            border-radius: 10px;
            color: #4f46e5;
            font-size: 12px;
            font-weight: 700
        }

        .flash {
            padding: 14px 17px;
            border-radius: 11px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 600
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b
        }

        .flash.success {
            background: #dcfce7;
            color: #166534
        }

        .form-card {
            background: white;
            border: 1px solid #eaecf2;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 18px
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 19px
        }

        .section-heading h2 {
            font-size: 15px
        }

        .section-heading p {
            color: #9299a8;
            font-size: 11px;
            margin-top: 5px
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px
        }

        .field {
            min-width: 0
        }

        .field.full {
            grid-column: 1/-1
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px
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
            font-size: 12px
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px #eef2ff
        }

        textarea {
            min-height: 105px;
            resize: vertical
        }

        .hint {
            display: block;
            margin-top: 6px;
            color: #9299a8;
            font-size: 10px
        }

        .image-preview {
            width: 150px;
            height: 115px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 12px;
            border: 1px solid #e5e7eb
        }

        .repeat-list {
            display: grid;
            gap: 10px
        }

        .repeat-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) 38px;
            gap: 9px;
            align-items: end;
            padding: 13px;
            border: 1px solid #edf0f4;
            border-radius: 11px;
            background: #fafbfc
        }

        .repeat-row.addon-row {
            grid-template-columns: 1fr 1.2fr .7fr .7fr 38px
        }

        .repeat-row.size-row {
            grid-template-columns: .7fr 1fr .8fr 38px
        }

        .repeat-row .field.status-field {
            grid-column: 1/-1;
            max-width: 220px
        }

        .remove-btn {
            width: 38px;
            height: 39px;
            border: 0;
            border-radius: 8px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 18px;
            cursor: pointer
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
            cursor: pointer
        }

        .submit-area {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-bottom: 30px
        }

        .save-btn {
            padding: 13px 19px;
            border: 0;
            border-radius: 9px;
            background: #4f46e5;
            color: white;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer
        }

        .save-btn:hover {
            background: #4338ca
        }

        .empty-note {
            padding: 14px;
            border: 1px dashed #d7dbe5;
            border-radius: 10px;
            color: #9299a8;
            font-size: 12px;
            margin-bottom: 10px
        }

        @media(max-width:900px) {
            .sidebar {
                position: static;
                width: 100%;
                padding: 16px
            }

            .brand {
                margin-bottom: 17px
            }

            .menu-label {
                display: none
            }

            .menu {
                display: flex;
                overflow-x: auto
            }

            .menu a {
                white-space: nowrap
            }

            .main {
                margin: 0;
                padding: 22px 15px
            }
        }

        @media(max-width:650px) {
            .form-grid {
                grid-template-columns: 1fr
            }

            .field.full {
                grid-column: auto
            }

            .repeat-row,
            .repeat-row.addon-row,
            .repeat-row.size-row {
                grid-template-columns: 1fr 1fr
            }

            .repeat-row .field:first-child {
                grid-column: 1/-1
            }

            .remove-btn {
                grid-column: 2;
                justify-self: end
            }

            .form-card {
                padding: 17px
            }

            .topbar {
                align-items: flex-start
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
                <h1>Edit Layanan</h1>
                <p class="subtitle">Perbarui informasi, paket, ukuran, dan layanan tambahan.</p>
            </div>
            <a href="layanan.php" class="back-link">← Kembali</a>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= nilai($flash['type']) ?>">
                <?= nilai($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="handlers/edit_layanan_handler.php" method="POST" enctype="multipart/form-data" id="layananForm">
            <input type="hidden" name="csrf_token" value="<?= nilai($_SESSION['csrf_layanan']) ?>">
            <input type="hidden" name="layanan_id" value="<?= (int)$id ?>">

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Informasi Utama</h2>
                        <p>Informasi yang ditampilkan pada katalog layanan.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>Nama Layanan *</label>
                        <input type="text" name="nama" maxlength="150" required value="<?= nilai($layanan['nama']) ?>">
                    </div>

                    <div class="field">
                        <label>Kategori *</label>
                        <select name="category_id" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($kategori_list as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= (int)$cat['id'] === (int)$layanan['category_id'] ? 'selected' : '' ?>>
                                    <?= nilai($cat['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi"><?= nilai($layanan['deskripsi']) ?></textarea>
                    </div>

                    <div class="field">
                        <label>Harga Dasar (Rp) *</label>
                        <input type="number" name="harga" min="0" step="0.01" required value="<?= nilai($layanan['harga']) ?>">
                    </div>

                    <div class="field">
                        <label>Estimasi Waktu</label>
                        <input type="text" name="waktu" maxlength="100" value="<?= nilai($layanan['waktu']) ?>">
                    </div>

                    <div class="field">
                        <label>Badge / Label</label>
                        <input type="text" name="badge" maxlength="100" value="<?= nilai($layanan['badge']) ?>">
                    </div>

                    <div class="field">
                        <label>Status Layanan</label>
                        <select name="status">
                            <option value="Aktif" <?= $layanan['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= $layanan['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Gambar Layanan</label>
                        <input type="file" name="gambar" id="gambar" accept="image/jpeg,image/png,image/webp">
                        <span class="hint">Kosongkan jika tidak ingin mengganti gambar. JPG, PNG, WEBP maksimal 3 MB.</span>
                        <?php if (!empty($layanan['gambar'])): ?>
                            <img id="preview" class="image-preview" src="../<?= nilai($layanan['gambar']) ?>" alt="Gambar layanan">
                        <?php else: ?>
                            <img id="preview" class="image-preview" style="display:none" alt="Preview gambar layanan">
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Varian Layanan</h2>
                        <p>Minimal satu varian harus berstatus Aktif agar layanan dapat dipesan.</p>
                    </div>
                </div>

                <div class="repeat-list" id="varianList">
                    <?php foreach ($varian_list as $v): ?>
                        <div class="repeat-row">
                            <input type="hidden" name="varian[id][]" value="<?= (int)$v['id'] ?>">
                            <div class="field">
                                <label>Nama Varian</label>
                                <input name="varian[nama][]" required value="<?= nilai($v['nama_varian']) ?>" placeholder="Regular">
                            </div>
                            <div class="field">
                                <label>Label Durasi</label>
                                <input name="varian[durasi][]" value="<?= nilai($v['durasi_label']) ?>" placeholder="2-3 hari">
                            </div>
                            <div class="field">
                                <label>Harga (Rp)</label>
                                <input type="number" name="varian[harga][]" min="0" step="0.01" required value="<?= nilai($v['harga']) ?>">
                            </div>
                            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                            <div class="field status-field">
                                <label>Status</label>
                                <select name="varian[status][]">
                                    <option value="Aktif" <?= $v['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= $v['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-btn" onclick="addVarian()">+ Tambah Varian</button>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Ukuran Sepatu</h2>
                        <p>Atur ukuran dan biaya tambahan sesuai kebutuhan.</p>
                    </div>
                </div>

                <div class="repeat-list" id="ukuranList">
                    <?php foreach ($ukuran_list as $u): ?>
                        <div class="repeat-row size-row">
                            <input type="hidden" name="ukuran[id][]" value="<?= (int)$u['id'] ?>">
                            <div class="field">
                                <label>Kode Ukuran</label>
                                <input name="ukuran[kode][]" value="<?= nilai($u['kode_ukuran']) ?>" placeholder="XL">
                            </div>
                            <div class="field">
                                <label>Nama Ukuran</label>
                                <input name="ukuran[nama][]" value="<?= nilai($u['nama_ukuran']) ?>" placeholder="Sepatu ekstra besar">
                            </div>
                            <div class="field">
                                <label>Harga Tambahan (Rp)</label>
                                <input type="number" name="ukuran[harga][]" min="0" step="0.01" value="<?= nilai($u['harga_tambahan']) ?>">
                            </div>
                            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                            <div class="field status-field">
                                <label>Status</label>
                                <select name="ukuran[status][]">
                                    <option value="Aktif" <?= $u['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= $u['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-btn" onclick="addUkuran()">+ Tambah Ukuran</button>
            </section>

            <section class="form-card">
                <div class="section-heading">
                    <div>
                        <h2>Add-on / Layanan Tambahan</h2>
                        <p>Contoh: Repaint, unyellowing, deodorizer.</p>
                    </div>
                </div>

                <div class="repeat-list" id="addonList">
                    <?php foreach ($addon_list as $a): ?>
                        <div class="repeat-row addon-row">
                            <input type="hidden" name="addon[id][]" value="<?= (int)$a['id'] ?>">
                            <div class="field">
                                <label>Nama Add-on</label>
                                <input name="addon[nama][]" value="<?= nilai($a['nama_addon']) ?>" placeholder="Deodorizer">
                            </div>
                            <div class="field">
                                <label>Deskripsi</label>
                                <input name="addon[deskripsi][]" value="<?= nilai($a['deskripsi']) ?>" placeholder="Menghilangkan bau">
                            </div>
                            <div class="field">
                                <label>Harga (Rp)</label>
                                <input type="number" name="addon[harga][]" min="0" step="0.01" value="<?= nilai($a['harga']) ?>">
                            </div>
                            <div class="field">
                                <label>Wajib?</label>
                                <select name="addon[wajib][]">
                                    <option value="0" <?= (int)$a['wajib'] === 0 ? 'selected' : '' ?>>Tidak</option>
                                    <option value="1" <?= (int)$a['wajib'] === 1 ? 'selected' : '' ?>>Ya</option>
                                </select>
                            </div>
                            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                            <div class="field status-field">
                                <label>Status</label>
                                <select name="addon[status][]">
                                    <option value="Aktif" <?= $a['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= $a['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-btn" onclick="addAddon()">+ Tambah Add-on</button>
            </section>

            <div class="submit-area">
                <a href="layanan.php" class="cancel-btn">Batal</a>
                <button type="submit" class="save-btn">Simpan Perubahan</button>
            </div>
        </form>
    </main>

    <script>
        function removeRow(button) {
            button.closest('.repeat-row').remove();
        }

        function addVarian() {
            document.getElementById('varianList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row">
            <input type="hidden" name="varian[id][]" value="">
            <div class="field">
                <label>Nama Varian</label>
                <input name="varian[nama][]" required placeholder="Express">
            </div>
            <div class="field">
                <label>Label Durasi</label>
                <input name="varian[durasi][]" placeholder="1 hari">
            </div>
            <div class="field">
                <label>Harga (Rp)</label>
                <input type="number" name="varian[harga][]" min="0" step="0.01" required placeholder="50000">
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
            <div class="field status-field">
                <label>Status</label>
                <select name="varian[status][]">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
    `);
        }

        function addUkuran() {
            document.getElementById('ukuranList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row size-row">
            <input type="hidden" name="ukuran[id][]" value="">
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
                <input type="number" name="ukuran[harga][]" min="0" step="0.01" value="0">
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
            <div class="field status-field">
                <label>Status</label>
                <select name="ukuran[status][]">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
    `);
        }

        function addAddon() {
            document.getElementById('addonList').insertAdjacentHTML('beforeend', `
        <div class="repeat-row addon-row">
            <input type="hidden" name="addon[id][]" value="">
            <div class="field">
                <label>Nama Add-on</label>
                <input name="addon[nama][]" placeholder="Repaint">
            </div>
            <div class="field">
                <label>Deskripsi</label>
                <input name="addon[deskripsi][]" placeholder="Pengecatan ulang">
            </div>
            <div class="field">
                <label>Harga (Rp)</label>
                <input type="number" name="addon[harga][]" min="0" step="0.01" value="0">
            </div>
            <div class="field">
                <label>Wajib?</label>
                <select name="addon[wajib][]">
                    <option value="0">Tidak</option>
                    <option value="1">Ya</option>
                </select>
            </div>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
            <div class="field status-field">
                <label>Status</label>
                <select name="addon[status][]">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
    `);
        }

        document.getElementById('gambar').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('preview');

            if (!file) return;

            if (file.size > 3 * 1024 * 1024) {
                alert('Ukuran gambar maksimal 3 MB.');
                this.value = '';
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        });
    </script>
</body>

</html>