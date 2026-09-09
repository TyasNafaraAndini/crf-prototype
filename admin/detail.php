<?php
/**
 * admin/detail.php
 * ---------------------------------------------------------------
 * Menampilkan seluruh isi satu pengajuan CRF (lihat brief butir 20).
 * Halaman ini read-only; perubahan Level Complain / Status / PIR /
 * Implementasi dilakukan di admin/edit.php.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getConnection();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT cr.*, u.name AS submitter_name, u.email AS submitter_email, u.phone AS submitter_phone
     FROM change_requests cr
     JOIN users u ON u.id = cr.user_id
     WHERE cr.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$crf = $stmt->fetch();

if (!$crf) {
    http_response_code(404);
    $pageTitle = 'CRF Tidak Ditemukan';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="crf-page"><div class="container">';
    echo '<div class="alert alert-danger">Pengajuan CRF dengan ID tersebut tidak ditemukan.</div>';
    echo '<a href="dashboard.php" class="btn btn-crf-outline"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>';
    echo '</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$attStmt = $pdo->prepare('SELECT * FROM attachments WHERE change_request_id = :id ORDER BY uploaded_at ASC');
$attStmt->execute(['id' => $id]);
$attachments = $attStmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Detail CRF - ' . $crf['request_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="crf-page">
  <div class="container">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 crf-page-header">
      <div>
        <h1>Detail CRF</h1>
        <p>Nomor Register: <strong><?= h($crf['request_number']) ?></strong></p>
      </div>
      <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-crf-outline"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="edit.php?id=<?= (int) $crf['id'] ?>" class="btn btn-crf-primary"><i class="bi bi-gear"></i> Kelola CRF</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?> crf-alert" role="alert">
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mb-4">
      <span class="crf-badge <?= levelBadgeClass($crf['level']) ?>">
        Level Complain: <?= h($crf['level'] ?? 'Belum ditentukan') ?>
      </span>
      <span class="crf-badge <?= statusBadgeClass($crf['status']) ?>">
        Status: <?= h(statusLabel($crf['status'])) ?>
      </span>
    </div>

    <!-- Informasi Pengajuan -->
    <div class="crf-section">
      <div class="crf-section-header">
        <span class="crf-section-number"><i class="bi bi-info-lg"></i></span>
        <h2>Informasi Pengajuan</h2>
      </div>
      <div class="crf-section-body">
        <div class="row">
          <div class="col-md-3">
            <div class="crf-detail-label">Hari/Tanggal</div>
            <div class="crf-detail-value"><?= h(formatTanggalIndonesia(new DateTime($crf['submission_date']))) ?></div>
          </div>
          <div class="col-md-3">
            <div class="crf-detail-label">Nomor Register</div>
            <div class="crf-detail-value"><?= h($crf['request_number']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="crf-detail-label">Kepada</div>
            <div class="crf-detail-value"><?= h($crf['to_department'] . ' (' . $crf['to_division'] . ')') ?></div>
          </div>
          <div class="col-md-3">
            <div class="crf-detail-label">Dari</div>
            <div class="crf-detail-value">
              <?= h($crf['from_department']) ?><br><?= h('(' . ($crf['from_division'] ?? '-') . ')') ?>
            </div>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-4">
            <div class="crf-detail-label">Pengaju</div>
            <div class="crf-detail-value"><?= h($crf['submitter_name']) ?></div>
          </div>
          <div class="col-md-4">
            <div class="crf-detail-label">Email</div>
            <div class="crf-detail-value"><?= h($crf['submitter_email'] ?? '-') ?></div>
          </div>
          <div class="col-md-4">
            <div class="crf-detail-label">No. HP/WA</div>
            <div class="crf-detail-value"><?= h($crf['submitter_phone'] ?? '-') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Pengajuan -->
    <div class="crf-section">
      <div class="crf-section-header">
        <span class="crf-section-number"><i class="bi bi-card-checklist"></i></span>
        <h2>Detail Pengajuan</h2>
      </div>
      <div class="crf-section-body">

        <div class="crf-detail-label">Rincian Permohonan Perubahan</div>
        <div class="crf-detail-value"><?= h($crf['change_description'] ?? '-') ?></div>

        <div class="crf-detail-label">Benefit dari Perubahan yang Diharapkan</div>
        <div class="crf-detail-value"><?= h($crf['benefit'] ?? '-') ?></div>

        <div class="crf-detail-label">Dampak Jika Tidak Dilakukan Perubahan</div>
        <div class="crf-detail-value"><?= h($crf['impact'] ?? '-') ?></div>

        <div class="crf-detail-label">Alasan Permohonan Perubahan</div>
        <div class="crf-detail-value"><?= h($crf['reason'] ?? '-') ?></div>

        <div class="crf-detail-label">Bukti dan Informasi Pendukung</div>
        <div class="crf-detail-value">
          <?php if (!$attachments): ?>
            <span class="text-muted">Tidak ada file yang diupload.</span>
          <?php else: ?>
            <ul class="mb-0 ps-3">
              <?php foreach ($attachments as $file): ?>
                <li>
                  <a href="../<?= h($file['file_path']) ?>" target="_blank" rel="noopener">
                    <?= h($file['original_name']) ?>
                  </a>
                  <span class="text-muted">(<?= round($file['file_size'] / 1024) ?> KB)</span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

        <div class="crf-detail-label">Biaya / Anggaran</div>
        <div class="crf-detail-value">
          <?= h(budgetTypeLabel($crf['budget_type'])) ?>
          <?php if ($crf['budget_amount'] !== null): ?>
            &mdash; <?= h(formatRupiah($crf['budget_amount'])) ?>
          <?php endif; ?>
        </div>

        <div class="crf-detail-label">Kategori Perubahan</div>
        <div class="crf-detail-value">
          <?= h($crf['change_category'] ?? '-') ?>
          <?php if (!empty($crf['change_category_detail'])): ?>
            &mdash; <?= h($crf['change_category_detail']) ?>
          <?php endif; ?>
        </div>

        <div class="crf-detail-label">Saran Alternatif</div>
        <div class="crf-detail-value"><?= h($crf['alternative_suggestion'] ?? '-') ?></div>

        <div class="crf-detail-label">Post Implementation Review</div>
        <div class="crf-detail-value"><?= h($crf['post_implementation_review'] ?? '-') ?></div>

        <div class="crf-detail-label">Implementasi</div>
        <div class="crf-detail-value mb-0"><?= h($crf['implementation'] ?? '-') ?></div>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
