<?php
session_start();

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helper.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ambil flash message
$flash = $_SESSION['flash_pesanan'] ?? '';
unset($_SESSION['flash_pesanan']);

// Ambil pesanan pengguna
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.no_pesanan,
        p.nama_lengkap,
        p.no_wa,
        p.alamat_pengantaran,
        p.total_harga,
        p.status_pesanan,
        p.status_pembayaran,
        p.metode_pembayaran,
        p.metode_pengantaran,
        p.jadwal_pelaksanaan,
        p.created_at,
        d.id AS detail_id,
        d.layanan_id,
        d.nama_layanan_snapshot,
        d.nama_varian_snapshot,
        d.ukuran_snapshot,
        d.addon_snapshot,
        d.harga_satuan,
        d.harga_dasar,
        d.harga_tambahan,
        d.jumlah,
        d.subtotal,
        l.gambar
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON d.pesanan_id = p.id
    LEFT JOIN layanan l ON l.id = d.layanan_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC, p.id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$pesanan_list = [];

while ($row = $result->fetch_assoc()) {
  $id = (int) $row['id'];

  if (!isset($pesanan_list[$id])) {
    $pesanan_list[$id] = [
      'id' => $id,
      'no_pesanan' => $row['no_pesanan'],
      'nama_lengkap' => $row['nama_lengkap'],
      'no_wa' => $row['no_wa'],
      'alamat_pengantaran' => $row['alamat_pengantaran'],
      'total_harga' => (float) $row['total_harga'],
      'subtotal_layanan' => 0,
      'status_pesanan' => $row['status_pesanan'],
      'status_pembayaran' => $row['status_pembayaran'],
      'metode_pembayaran' => $row['metode_pembayaran'],
      'metode_pengantaran' => $row['metode_pengantaran'],
      'jadwal_pelaksanaan' => $row['jadwal_pelaksanaan'],
      'created_at' => $row['created_at'],
      'detail' => []
    ];
  }

  if ($row['detail_id'] !== null) {
    $snapshot = json_decode($row['addon_snapshot'] ?? '', true);

    if (!is_array($snapshot)) {
      $snapshot = [];
    }

    $addons = $snapshot['addons'] ?? [];

    if (!is_array($addons)) {
      $addons = [];
    }

    $subtotal_item = (float) $row['subtotal'];

    $pesanan_list[$id]['subtotal_layanan'] += $subtotal_item;

    $pesanan_list[$id]['detail'][] = [
      'detail_id' => (int) $row['detail_id'],
      'nama_layanan' => $row['nama_layanan_snapshot'] ?: 'Layanan tidak tersedia',
      'gambar' => $row['gambar'],
      'varian' => $row['nama_varian_snapshot'] ?? '',
      'ukuran' => $row['ukuran_snapshot'] ?? '',
      'addons' => $addons,
      'catatan' => $snapshot['catatan'] ?? '',
      'harga_dasar' => $row['harga_dasar'],
      'harga_tambahan' => $row['harga_tambahan'],
      'harga_satuan' => $row['harga_satuan'],
      'jumlah' => $row['jumlah'],
      'subtotal' => $subtotal_item
    ];
  }
}

$stmt->close();

$filter_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pesanan Saya | Cuci Disini</title>
  <link rel="stylesheet" href="assets/css/styles.css">


  <style>
    /* ===== ORDER PAGE ===== */

    .orders-page {
      max-width: 1050px;
      margin: 0 auto;
    }

    .orders-heading {
      margin-bottom: 28px;
    }

    .orders-heading p {
      color: #6b7280;
      margin-top: 8px;
    }

    /* Order card */

    .order-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      padding: 24px;
      margin-bottom: 22px;
      transition: border-color .2s, box-shadow .2s;
    }

    .order-card:hover {
      border-color: #c7d2fe;
      box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .order-card.is-new {
      border: 2px solid #4f46e5;
      box-shadow: 0 8px 25px rgba(79, 70, 229, .08);
    }

    /* Header */

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      flex-wrap: wrap;
      padding-bottom: 20px;
      border-bottom: 1px solid #f1f5f9;
    }

    .order-number {
      font-size: 16px;
      font-weight: 800;
      color: #111827;
      overflow-wrap: anywhere;
    }

    .order-date {
      color: #6b7280;
      font-size: 13px;
      margin-top: 6px;
    }

    .order-statuses {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .status {
      display: inline-flex;
      align-items: center;
      padding: 7px 11px;
      border-radius: 30px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
      background: #f3f4f6;
      color: #374151;
    }

    .status.pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status.success {
      background: #dcfce7;
      color: #166534;
    }

    .status.danger {
      background: #fee2e2;
      color: #991b1b;
    }

    .status.info {
      background: #dbeafe;
      color: #1e40af;
    }

    /* Section title */

    .order-section-title {
      font-size: 16px;
      font-weight: 750;
      margin: 22px 0 12px;
      color: #111827;
    }

    /* Service items */

    .order-detail {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      padding: 17px 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .order-detail:last-child {
      border-bottom: 0;
    }

    .order-product-image {
      width: 76px;
      height: 76px;
      flex-shrink: 0;
      border-radius: 12px;
      object-fit: cover;
      background: #f3f4f6;
    }

    .order-product-placeholder {
      width: 76px;
      height: 76px;
      flex-shrink: 0;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: #f3f4f6;
      color: #9ca3af;
      font-size: 25px;
    }

    .order-product-info {
      flex: 1;
      min-width: 0;
    }

    .order-product-name {
      font-weight: 750;
      color: #111827;
    }

    .order-product-meta {
      color: #6b7280;
      font-size: 13px;
      line-height: 1.7;
      margin-top: 5px;
    }

    .order-product-note {
      margin-top: 8px;
      padding: 9px 11px;
      background: #f9fafb;
      border-radius: 9px;
      color: #4b5563;
      font-size: 12px;
    }

    .order-product-price {
      text-align: right;
      white-space: nowrap;
      font-weight: 750;
      color: #111827;
    }

    .order-product-price small {
      display: block;
      color: #6b7280;
      font-size: 12px;
      font-weight: 400;
      margin-top: 5px;
    }

    /* Information */

    .order-info {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
      margin-top: 22px;
      padding: 20px;
      background: #f9fafb;
      border-radius: 13px;
    }

    .info-item {
      min-width: 0;
    }

    .info-item small {
      display: block;
      color: #6b7280;
      font-size: 12px;
      margin-bottom: 6px;
    }

    .info-item strong {
      display: block;
      color: #111827;
      font-size: 14px;
      overflow-wrap: anywhere;
      line-height: 1.6;
    }

    /* Total */

    .order-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px solid #e5e7eb;
      font-weight: 800;
      font-size: 17px;
    }

    .order-total strong {
      color: #4f46e5;
      font-size: 20px;
    }

    /* Success message */

    .order-success {
      padding: 15px 18px;
      border-radius: 12px;
      background: #dcfce7;
      color: #166534;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Empty state */

    .empty-orders {
      background: #fff;
      padding: 55px 20px;
      border-radius: 18px;
      text-align: center;
      border: 1px solid #e5e7eb;
    }

    .empty-orders-icon {
      width: 64px;
      height: 64px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: #eef2ff;
      color: #4f46e5;
      font-size: 28px;
      margin: 0 auto 18px;
    }

    .empty-orders h2 {
      margin-bottom: 8px;
    }

    .empty-orders p {
      color: #6b7280;
      margin-bottom: 20px;
    }

    /* Responsive */

    @media (max-width: 600px) {
      .order-card {
        padding: 17px;
        border-radius: 14px;
      }

      .order-header {
        flex-direction: column;
      }

      .order-info {
        grid-template-columns: 1fr;
        padding: 15px;
        gap: 15px;
      }

      .order-detail {
        gap: 10px;
      }

      .order-product-image,
      .order-product-placeholder {
        width: 60px;
        height: 60px;
      }

      .order-product-price {
        font-size: 13px;
      }

      .order-total {
        font-size: 15px;
      }

      .order-total strong {
        font-size: 18px;
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
        <a href="keranjang.php" class="icon-btn">Keranjang</a>
        <a href="pesanan.php" class="icon-btn">Pesanan</a>
      </div>
    </div>
  </header>
  <main class="section">
    <div class="container orders-page">

      <div class="crumbs">
        <a href="index.php">Home</a>
        <span class="sep">›</span>
        <span>Pesanan Saya</span>
      </div>

      <div class="orders-heading">
        <h1>Pesanan Saya</h1>
        <p>Pantau pesanan, rincian layanan, dan status pembayaran kamu.</p>
      </div>

      <?php if ($flash !== ''): ?>
        <div class="order-success">
          <span>✓</span>
          <strong><?= e($flash) ?></strong>
        </div>
      <?php endif; ?>

      <?php if (empty($pesanan_list)): ?>

        <div class="empty-orders">

          <div class="empty-orders-icon">🧺</div>

          <h2>Belum Ada Pesanan</h2>

          <p>
            Sepertinya kamu belum punya transaksi.
            Yuk, temukan layanan yang cocok untuk barangmu.
          </p>

          <a href="layanan.php" class="btn">
            Jelajahi Layanan
          </a>

        </div>

      <?php else: ?>

        <?php foreach ($pesanan_list as $pesanan): ?>

          <?php
          $is_new_order = (
            $filter_id > 0 &&
            $filter_id === $pesanan['id']
          );

          $status_pesanan = strtolower(
            trim($pesanan['status_pesanan'])
          );

          $status_pembayaran = strtolower(
            trim($pesanan['status_pembayaran'])
          );

          $status_pesanan_class = 'status';

          if ($status_pesanan === 'pending') {
            $status_pesanan_class .= ' pending';
          } elseif (
            in_array($status_pesanan, ['selesai', 'completed'], true)
          ) {
            $status_pesanan_class .= ' success';
          } elseif (
            in_array($status_pesanan, ['dibatalkan', 'cancelled'], true)
          ) {
            $status_pesanan_class .= ' danger';
          }

          $status_bayar_class = 'status';

          if (
            in_array($status_pembayaran, ['paid', 'lunas', 'berhasil'], true)
          ) {
            $status_bayar_class .= ' success';
          } elseif (
            in_array($status_pembayaran, ['unpaid', 'belum dibayar'], true)
          ) {
            $status_bayar_class .= ' pending';
          } elseif (
            in_array($status_pembayaran, ['failed', 'gagal'], true)
          ) {
            $status_bayar_class .= ' danger';
          }

          $jumlah_item = count($pesanan['detail']);
          ?>

          <article
            class="order-card <?= $is_new_order ? 'is-new' : '' ?>"
            id="pesanan-<?= $pesanan['id'] ?>">

            <!-- HEADER PESANAN -->
            <div class="order-header">

              <div>
                <div class="order-number">
                  <?= e($pesanan['no_pesanan']) ?>
                </div>

                <div class="order-date">
                  Dipesan pada
                  <?= date(
                    'd M Y, H:i',
                    strtotime($pesanan['created_at'])
                  ) ?>
                </div>
              </div>

              <div class="order-statuses">

                <span class="<?= $status_pesanan_class ?>">
                  <?= e($pesanan['status_pesanan']) ?>
                </span>

                <span class="<?= $status_bayar_class ?>">
                  <?= e($pesanan['status_pembayaran']) ?>
                </span>

              </div>

            </div>

            <!-- RINCIAN LAYANAN -->
            <h3 class="order-section-title">
              Rincian Pesanan
              <span style="color:#9ca3af;font-weight:400;font-size:13px;">
                (<?= $jumlah_item ?> item)
              </span>
            </h3>

            <?php foreach ($pesanan['detail'] as $detail): ?>

              <div class="order-detail">

                <?php if (!empty($detail['gambar'])): ?>

                  <img
                    src="<?= e($detail['gambar']) ?>"
                    alt="<?= e($detail['nama_layanan']) ?>"
                    class="order-product-image"
                    loading="lazy">

                <?php else: ?>

                  <div class="order-product-placeholder">
                    🧼
                  </div>

                <?php endif; ?>

                <div class="order-product-info">

                  <div class="order-product-name">
                    <?= e($detail['nama_layanan']) ?>
                  </div>

                  <div class="order-product-meta">

                    <?php if ($detail['varian'] !== ''): ?>
                      <div>
                        Varian: <?= e($detail['varian']) ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($detail['ukuran'] !== ''): ?>
                      <div>
                        Ukuran: <?= e($detail['ukuran']) ?>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($detail['addons'])): ?>
                      <div>
                        Addon:
                        <?php
                        $addon_names = [];

                        foreach ($detail['addons'] as $addon) {
                          if (!empty($addon['nama'])) {
                            $addon_names[] = $addon['nama'];
                          }
                        }

                        echo e(implode(', ', $addon_names));
                        ?>
                      </div>
                    <?php endif; ?>

                    <div>
                      <?= (int) $detail['jumlah'] ?> ×
                      <?= formatRupiah($detail['harga_satuan']) ?>
                    </div>

                  </div>

                  <?php if ($detail['catatan'] !== ''): ?>
                    <div class="order-product-note">
                      <strong>Catatan:</strong>
                      <?= e($detail['catatan']) ?>
                    </div>
                  <?php endif; ?>

                </div>

                <div class="order-product-price">

                  <?= formatRupiah($detail['subtotal']) ?>

                  <small>Subtotal</small>

                </div>

              </div>

            <?php endforeach; ?>

            <!-- INFORMASI PESANAN -->
            <h3 class="order-section-title">
              Informasi Pesanan
            </h3>

            <div class="order-info">

              <div class="info-item">
                <small>Nama Pelanggan</small>
                <strong>
                  <?= e($pesanan['nama_lengkap']) ?>
                </strong>
              </div>

              <div class="info-item">
                <small>Nomor WhatsApp</small>
                <strong>
                  <?= e($pesanan['no_wa']) ?>
                </strong>
              </div>

              <div class="info-item">
                <small>Metode Pengantaran</small>
                <strong>
                  <?= e($pesanan['metode_pengantaran']) ?>
                </strong>
              </div>

              <div class="info-item">
                <small>Jadwal Pelaksanaan</small>
                <strong>
                  <?= date(
                    'd M Y, H:i',
                    strtotime($pesanan['jadwal_pelaksanaan'])
                  ) ?>
                </strong>
              </div>

              <div class="info-item">
                <small>Metode Pembayaran</small>
                <strong>
                  <?= e($pesanan['metode_pembayaran']) ?>
                </strong>
              </div>

              <div class="info-item">
                <small>Status Pembayaran</small>
                <strong>
                  <?= e($pesanan['status_pembayaran']) ?>
                </strong>
              </div>

              <div class="info-item" style="grid-column:1/-1;">
                <small>Alamat Pengantaran</small>
                <strong>
                  <?= nl2br(e($pesanan['alamat_pengantaran'])) ?>
                </strong>
              </div>

            </div>

            <!-- BIAYA PENGANTARAN & TOTAL -->
            <?php
            $biaya_pengantaran = $pesanan['total_harga'] - $pesanan['subtotal_layanan'];
            ?>

            <div style="
              display:flex;
              justify-content:space-between;
              gap:12px;
              margin-top:16px;
              padding-top:14px;
              border-top:1px solid #e5e7eb;
              color:#4b5563;
              font-size:14px;
            ">
              <span>Subtotal Layanan</span>
              <span><?= formatRupiah($pesanan['subtotal_layanan']) ?></span>
            </div>

            <div style="
              display:flex;
              justify-content:space-between;
              gap:12px;
              margin-top:8px;
              color:#4b5563;
              font-size:14px;
            ">
              <span>Biaya Pengantaran</span>
              <span>
                <?= $biaya_pengantaran > 0
                    ? formatRupiah($biaya_pengantaran)
                    : '<span style="color:#16a34a;">Gratis</span>' ?>
              </span>
            </div>

            <div class="order-total">

              <span>Total Pembayaran</span>

              <strong>
                <?= formatRupiah($pesanan['total_harga']) ?>
              </strong>

            </div>

          </article>

        <?php endforeach; ?>

      <?php endif; ?>

    </div>
  </main>


  <script src="assets/js/main.js" defer></script>
</body>

</html>