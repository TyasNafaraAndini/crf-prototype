<?php
/**
 * user/form_crf.php
 * ---------------------------------------------------------------
 * Halaman utama prototype. Tidak ada login - langsung tampil Form CRF
 * (lihat brief butir 4A). Field otomatis (Hari/Tanggal, Kepada, Dari,
 * Nomor Register) ditampilkan readonly dan diambil dari sistem/database,
 * bukan diketik user (brief butir 7-10).
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo  = getConnection();
$user = getCurrentUser();

$today = new DateTime();
$tanggalDisplay   = formatTanggalIndonesia($today);
$previewRequestNo = generateRequestNumber($pdo, $today);

$toDepartment = 'Departemen Operasional';
$toDivision   = 'Divisi Otomasi';

// Ambil pesan flash (sukses/gagal) dari proses submit.
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Form CRF';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="crf-page">
  <div class="container">

    <div class="crf-page-header">
      <h1>Change Request Form (CRF)</h1>
      <p>Silakan lengkapi form di bawah untuk mengajukan permohonan perubahan.</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?> crf-alert" role="alert">
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>

    <form action="../actions/submit_crf.php" method="POST" enctype="multipart/form-data" id="crfForm">

      <!-- ============================================================ -->
      <!-- 1. INFORMASI PENGAJUAN                                        -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">1</span>
          <h2>Informasi Pengajuan</h2>
        </div>
        <div class="crf-section-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="crf-field-label">Hari/Tanggal</label>
              <input type="text" class="form-control" value="<?= h($tanggalDisplay) ?>" readonly>
              <div class="crf-readonly-note"><i class="bi bi-lock-fill"></i>Diisi otomatis oleh sistem</div>
            </div>
            <div class="col-md-6">
              <label class="crf-field-label">Nomor Register</label>
              <input type="text" class="form-control" value="<?= h($previewRequestNo) ?>" readonly>
              <div class="crf-readonly-note"><i class="bi bi-lock-fill"></i>Nomor akhir dibuat sistem saat data disimpan</div>
            </div>
            <div class="col-md-6">
              <label class="crf-field-label">Kepada</label>
              <input type="text" class="form-control" value="<?= h($toDepartment . ' (' . $toDivision . ')') ?>" readonly>
              <div class="crf-readonly-note"><i class="bi bi-lock-fill"></i>Tujuan pengajuan CRF pada prototype ini tetap</div>
            </div>
            <div class="col-md-6">
              <label class="crf-field-label">Dari</label>

              <div class="row g-2">
                <div class="col-12">
                  <label for="from_department" class="form-label">Departemen</label>
                  <input
                    type="text"
                    class="form-control"
                    id="from_department"
                    name="from_department"
                    placeholder="Masukkan nama departemen"
                    required
                  >
                </div>

                <div class="col-12">
                  <label for="from_division" class="form-label">Divisi</label>
                  <input
                    type="text"
                    class="form-control"
                    id="from_division"
                    name="from_division"
                    placeholder="Masukkan nama divisi"
                    required
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 2. CHANGE REQUEST DESCRIPTION                                 -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">2</span>
          <h2>Change Request Description</h2>
        </div>
        <div class="crf-section-body">

          <div class="mb-3">
            <label for="change_description" class="crf-field-label">Rincian Permohonan Perubahan</label>
            <p class="crf-hint">Silakan tulis penjelasan yang lengkap, jelas, dan rinci mengenai permohonan perubahan yang disampaikan.</p>
            <textarea class="form-control" id="change_description" name="change_description" required></textarea>
          </div>

          <div class="mb-3">
            <label for="benefit" class="crf-field-label">Benefit dari Perubahan yang Diharapkan</label>
            <p class="crf-hint">Silakan tulis benefit yang akan diperoleh setelah dilakukan perubahan.</p>
            <textarea class="form-control" id="benefit" name="benefit" required></textarea>
          </div>

          <div class="mb-3">
            <label for="impact" class="crf-field-label">Dampak Jika Tidak Dilakukan Perubahan</label>
            <p class="crf-hint">Silakan tulis dampak jika tidak dilakukan perubahan.</p>
            <textarea class="form-control" id="impact" name="impact" required></textarea>
          </div>

          <div class="mb-0">
            <label for="reason" class="crf-field-label">Alasan Permohonan Perubahan</label>
            <p class="crf-hint">Silakan tulis alasan perubahan yang Saudara sampaikan.</p>
            <textarea class="form-control" id="reason" name="reason" required></textarea>
          </div>

        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 3. BUKTI DAN INFORMASI PENDUKUNG                              -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">3</span>
          <h2>Bukti dan Informasi Pendukung</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">Silakan sampaikan bukti berupa screenshot, printout, atau dokumen pendukung lain (opsional).</p>
          <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                 accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
          <div class="crf-readonly-note mt-2">
            Format yang didukung: PDF, JPG, JPEG, PNG, DOC, DOCX, XLS, XLSX. Maks. 5 MB per file.
          </div>
          <div id="file-list-preview" class="mt-2"></div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 4. BIAYA / ANGGARAN                                           -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">4</span>
          <h2>Biaya / Anggaran</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">Silakan sampaikan apakah untuk perubahan ini sudah dianggarkan atau perlu diusulkan.</p>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="budget_type" id="budget_rkap" value="rkap">
            <label class="form-check-label" for="budget_rkap">RKAP tahun berjalan</label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="budget_type" id="budget_boq" value="boq_pks">
            <label class="form-check-label" for="budget_boq">Tercantum dalam BoQ PKS</label>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="budget_type" id="budget_baru" value="anggaran_baru">
            <label class="form-check-label" for="budget_baru">Akan diajukan anggaran baru</label>
          </div>

          <label for="budget_amount" class="crf-field-label">Nominal</label>
          <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text">Rp</span>
            <input type="number" min="0" step="1000" class="form-control"
                   id="budget_amount" name="budget_amount" placeholder="0" disabled>
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 5. KATEGORI PERUBAHAN                                         -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">5</span>
          <h2>Kategori Perubahan</h2>
        </div>
        <div class="crf-section-body">
          <label for="change_category" class="crf-field-label">Kategori</label>
          <select class="form-select mb-3" id="change_category" name="change_category" required style="max-width: 320px;">
            <option value="" selected disabled>Pilih kategori...</option>
            <option value="Aplikasi">Aplikasi</option>
            <option value="Infrastruktur">Infrastruktur</option>
            <option value="Proses">Proses</option>
            <option value="Security">Security</option>
            <option value="Lainnya">Lainnya</option>
          </select>

          <div id="category-detail-wrap" class="d-none">
            <label for="change_category_detail" class="crf-field-label" id="category-detail-label">Detail Kategori</label>
            <input type="text" class="form-control" id="change_category_detail" name="change_category_detail">
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 6. CHANGE REQUEST ACTION                                      -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">6</span>
          <h2>Change Request Action</h2>
        </div>
        <div class="crf-section-body">
          <label for="alternative_suggestion" class="crf-field-label">Saran Alternatif</label>
          <p class="crf-hint">Saran alternatif yang akan dilakukan atas perubahan yang telah disampaikan.</p>
          <textarea class="form-control" id="alternative_suggestion" name="alternative_suggestion"></textarea>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 7. POST IMPLEMENTATION REVIEW                                 -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">7</span>
          <h2>Post Implementation Review</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">
            Proses evaluasi yang dilakukan setelah perubahan dilakukan sebelum perubahan tersebut diterapkan.
            <strong>Tidak wajib diisi saat pengajuan pertama</strong> - bagian ini biasanya dilengkapi oleh admin setelah proses berjalan.
          </p>
          <textarea class="form-control" id="post_implementation_review" name="post_implementation_review"></textarea>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- 8. IMPLEMENTASI                                               -->
      <!-- ============================================================ -->
      <div class="crf-section">
        <div class="crf-section-header">
          <span class="crf-section-number">8</span>
          <h2>Implementasi</h2>
        </div>
        <div class="crf-section-body">
          <p class="crf-hint">
            Pelaksanaan yang telah dilakukan atas perubahan yang telah disampaikan.
            <strong>Tidak wajib diisi saat pengajuan pertama.</strong>
          </p>
          <textarea class="form-control" id="implementation" name="implementation"></textarea>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- TOMBOL FORM                                                   -->
      <!-- ============================================================ -->
      <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="submit" class="btn btn-crf-primary px-4" formaction="../actions/submit_crf.php">
          <i class="bi bi-send-check"></i> Submit CRF
        </button>
      </div>

    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
