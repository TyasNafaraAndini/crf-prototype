<?php
/**
 * admin/edit.php
 * ---------------------------------------------------------------
 * Halaman bagi admin untuk mengelola CRF (lihat brief butir 21, 22, 27):
 *   - Menentukan Level Complain
 *   - Mengubah Status (Dalam Proses / Solve / Cancel)
 *   - Mengisi Post Implementation Review & Implementasi
 * Data lain (isi pengajuan user) ditampilkan read-only di sini sebagai
 * konteks; untuk melihat rincian lengkap, admin bisa membuka detail.php.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getConnection();
$id  = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT cr.*, u.name AS submitter_name
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

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Kelola CRF - ' . $crf['request_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="crf-page">
  <div class="container">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 crf-page-header">
      <div>
        <h1>Kelola CRF</h1>
        <p>
          Nomor Register: <strong><?= h($crf['request_number']) ?></strong>
          &middot; Diajukan oleh <?= h($crf['submitter_name']) ?>
        </p>
      </div>
      <div class="d-flex gap-2">
        <a href="detail.php?id=<?= (int) $crf['id'] ?>" class="btn btn-crf-outline">
          <i class="bi bi-file-text"></i> Lihat Rincian Lengkap
        </a>
        <a href="dashboard.php" class="btn btn-crf-outline"><i class="bi bi-arrow-left"></i> Dashboard</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?> crf-alert" role="alert">
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="crf-detail-label">Rincian Permohonan Perubahan</div>
        <div class="crf-detail-value"><?= h($crf['change_description'] ?? 'Belum diisi (draft)') ?></div>
      </div>
      <div class="col-md-6">
        <div class="crf-detail-label">Kategori Perubahan</div>
        <div class="crf-detail-value">
          <?= h($crf['change_category'] ?? '-') ?>
          <?php if (!empty($crf['change_category_detail'])): ?>
            &mdash; <?= h($crf['change_category_detail']) ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <form action="../actions/update_crf.php" method="POST">
      <input type="hidden" name="id" value="<?= (int) $crf['id'] ?>">

      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number"><i class="bi bi-flag"></i></span>
          <h2>Level Complain</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">
            Catatan: Level Complain di prototype ini berbeda dengan Level Urgensi pada dokumen CRF perusahaan.
          </p>
          <select name="level" class="form-select" style="max-width: 260px;">
            <option value="" <?= $crf['level'] === null ? 'selected' : '' ?>>Belum ditentukan</option>
            <option value="Tinggi" <?= $crf['level'] === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
            <option value="Normal" <?= $crf['level'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
            <option value="Rendah" <?= $crf['level'] === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
          </select>
        </div>
      </div>

      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number"><i class="bi bi-toggles"></i></span>
          <h2>Status</h2>
        </div>
        <div class="crf-section-body">
          <select name="status" id="status" class="form-select" style="max-width: 260px;">
            <option value="Belum Ditindak Lanjuti" <?= $crf['status'] === 'Belum Ditindak Lanjuti' ? 'selected' : '' ?>>
              Belum Ditindak Lanjuti
            </option>

            <option value="Dalam Proses" <?= $crf['status'] === 'Dalam Proses' ? 'selected' : '' ?>>
              Dalam Proses
            </option>

            <option value="Solve" <?= $crf['status'] === 'Solve' ? 'selected' : '' ?>>
              Solve (Selesai)
            </option>

            <option value="Cancel" <?= $crf['status'] === 'Cancel' ? 'selected' : '' ?>>
              Cancel (Dibatalkan)
            </option>
          </select>
          
          <?php if ($crf['status'] === 'Solve' && $crf['solved_at']): ?>
            <div class="crf-readonly-note mt-2">Ditandai Solve pada: <?= h(date('d-m-Y H:i', strtotime($crf['solved_at']))) ?></div>
          <?php endif; ?>
          <?php if ($crf['status'] === 'Cancel' && $crf['cancelled_at']): ?>
            <div class="crf-readonly-note mt-2">Dibatalkan pada: <?= h(date('d-m-Y H:i', strtotime($crf['cancelled_at']))) ?></div>
          <?php endif; ?>
        </div>
      </div>

            <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number"><i class="bi bi-chat-left-text"></i></span>
          <h2>Tanggapan / Tindak Lanjut</h2>
        </div>

        <div class="crf-section-body">
          <p class="crf-hint">
            Tanggapan atau tindak lanjut yang diberikan oleh admin terkait pengajuan CRF.
          </p>

          <textarea
            name="tanggapan_tindak_lanjut"
            id="tanggapan_tindak_lanjut"
            class="form-control"
            rows="4"
          ><?= h($crf['tanggapan_tindak_lanjut'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">7</span>
          <h2>Post Implementation Review</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">
            Proses evaluasi yang dilakukan setelah perubahan dilakukan sebelum perubahan tersebut diterapkan.
            <!-- <strong>Tidak wajib diisi saat pengajuan pertama</strong> - bagian ini biasanya dilengkapi oleh admin setelah proses berjalan. -->
          </p>
          <textarea name="post_implementation_review" class="form-control"><?= h($crf['post_implementation_review'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">8</span>
          <h2>Implementasi</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">
            Pelaksanaan yang telah dilakukan atas perubahan yang telah disampaikan.
            <!-- <strong>Tidak wajib diisi saat pengajuan pertama.</strong> -->
          </p>
          <textarea name="implementation" class="form-control"><?= h($crf['implementation'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="detail.php?id=<?= (int) $crf['id'] ?>" class="btn btn-crf-outline px-4">Batal</a>
        <button type="submit" class="btn btn-crf-primary px-4"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>