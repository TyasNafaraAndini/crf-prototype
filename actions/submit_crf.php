<?php
/**
 * actions/submit_crf.php
 * ---------------------------------------------------------------
 * Menangani tombol "Submit CRF" (lihat brief butir 18).
 * Alur: validasi -> simpan data -> buat nomor register ->
 *       simpan tanggal pengajuan -> simpan user_id -> status "Dalam Proses".
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../user/form_crf.php');
    exit;
}

$pdo  = getConnection();
$user = getCurrentUser();

/* ------------------------------------------------------------------
 * 1. Ambil & bersihkan input
 * ------------------------------------------------------------------ */
$fullName          = trim($_POST['full_name'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$email             = trim($_POST['email'] ?? '');

$changeDescription    = trim($_POST['change_description'] ?? '');
$benefit               = trim($_POST['benefit'] ?? '');
$impact                = trim($_POST['impact'] ?? '');
$reason                = trim($_POST['reason'] ?? '');

$fromDepartment = trim($_POST['from_department'] ?? '');
$fromDivision   = trim($_POST['from_division'] ?? '');

$budgetTypeRaw         = $_POST['budget_type'] ?? null;
$budgetAmountRaw       = $_POST['budget_amount'] ?? null;

$changeCategory        = $_POST['change_category'] ?? '';
$changeCategoryDetail  = trim($_POST['change_category_detail'] ?? '');

$alternativeSuggestion = trim($_POST['alternative_suggestion'] ?? '');
$postImplementation    = trim($_POST['post_implementation_review'] ?? '');
$implementation        = trim($_POST['implementation'] ?? '');

/* ------------------------------------------------------------------
 * 2. Validasi server-side (lihat brief butir 30)
 * Kepada tidak divalidasi dari input karena nilainya tetap/hardcode,
 * bukan berasal dari input user (mencegah manipulasi field readonly).
 * ------------------------------------------------------------------ */
$allowedCategories = ['Aplikasi', 'Infrastruktur', 'Proses', 'Security', 'Lainnya'];
$allowedBudgetTypes = ['rkap', 'boq_pks', 'anggaran_baru'];

$errors = [];

if ($fullName === '') {
    $errors[] = 'Nama Lengkap wajib diisi.';
}

if ($phone === '') {
    $errors[] = 'No. Handphone/WA wajib diisi.';
}

if ($email === '') {
    $errors[] = 'Email wajib diisi.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid.';
}

if ($changeDescription === '') { $errors[] = 'Rincian Permohonan Perubahan wajib diisi.'; }
if ($benefit === '')            { $errors[] = 'Benefit dari Perubahan wajib diisi.'; }
if ($impact === '')             { $errors[] = 'Dampak Jika Tidak Dilakukan Perubahan wajib diisi.'; }
if ($reason === '')             { $errors[] = 'Alasan Permohonan Perubahan wajib diisi.'; }

if ($fromDepartment === '') {
    $errors[] = 'Departemen wajib diisi.';
}

if ($fromDivision === '') {
    $errors[] = 'Divisi wajib diisi.';
}

if (!in_array($changeCategory, $allowedCategories, true)) {
    $errors[] = 'Kategori Perubahan wajib dipilih.';
}
if ($changeCategory === 'Lainnya' && $changeCategoryDetail === '') {
    $errors[] = 'Detail Kategori wajib diisi untuk kategori "Lainnya".';
}

if ($alternativeSuggestion === '') {
    $errors[] = 'Saran Alternatif wajib diisi.';
}

if ($budgetTypeRaw !== null && !in_array($budgetTypeRaw, $allowedBudgetTypes, true)) {
    $budgetTypeRaw = null;
}
$budgetAmount = ($budgetTypeRaw !== null && $budgetAmountRaw !== '' && is_numeric($budgetAmountRaw))
    ? (float) $budgetAmountRaw
    : null;

if ($budgetTypeRaw === null || $budgetTypeRaw === '') {
    $errors[] = 'Biaya / Anggaran belum dipilih.';
}

if (
    $budgetTypeRaw !== null &&
    $budgetTypeRaw !== '' &&
    ($budgetAmountRaw === '' || !is_numeric($budgetAmountRaw))
) {
    $errors[] = 'Nominal Biaya / Anggaran belum diisi.';
}

if ($errors) {
    // Simpan kembali isian form agar tetap muncul saat halaman dimuat ulang.
    $_SESSION['old_crf'] = $_POST;

    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'CRF belum dapat disimpan. Silakan periksa field yang belum lengkap.',
    ];

    header('Location: ../user/form_crf.php');
    exit;
}

/* ------------------------------------------------------------------
 * 3. Data otomatis (lihat brief butir 25)
 * ------------------------------------------------------------------ */
$today = new DateTime();
$submissionDate = $today->format('Y-m-d');
$requestNumber  = generateRequestNumber($pdo, $today);

$toDepartment = 'Departemen Operasional';
$toDivision   = 'Divisi Otomasi';

/* ------------------------------------------------------------------
 * 4. Simpan ke database
 * ------------------------------------------------------------------ */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO change_requests (
            request_number, user_id, full_name, phone, email, submission_date,
            to_department, to_division, from_department, from_division,
            change_description, benefit, impact, reason,
            budget_type, budget_amount,
            change_category, change_category_detail,
            alternative_suggestion,
            post_implementation_review, implementation,
            level, status
        ) VALUES (
            :request_number, :user_id, :full_name, :phone, :email, :submission_date,
            :to_department, :to_division, :from_department, :from_division,
            :change_description, :benefit, :impact, :reason,
            :budget_type, :budget_amount,
            :change_category, :change_category_detail,
            :alternative_suggestion,
            :post_implementation_review, :implementation,
            NULL, "Belum Ditindak Lanjuti"
        )'
    );

    $stmt->execute([
        'request_number'             => $requestNumber,
        'user_id'                    => $user['id'],
        'full_name'                  => $fullName,
        'phone'                      => $phone,
        'email'                      => $email,
        'submission_date'            => $submissionDate,
        'to_department'              => $toDepartment,
        'to_division'                => $toDivision,
        'from_department'            => $fromDepartment,
        'from_division'              => $fromDivision,
        'change_description'         => $changeDescription,
        'benefit'                    => $benefit,
        'impact'                     => $impact,
        'reason'                     => $reason,
        'budget_type'                => $budgetTypeRaw,
        'budget_amount'              => $budgetAmount,
        'change_category'            => $changeCategory,
        'change_category_detail'     => $changeCategoryDetail,
        'alternative_suggestion'     => $alternativeSuggestion,
        'post_implementation_review' => $postImplementation !== '' ? $postImplementation : null,
        'implementation'             => $implementation !== '' ? $implementation : null,
    ]);

    $crfId = (int) $pdo->lastInsertId();

    $uploadErrors = handleAttachmentUploads(
        $pdo,
        $crfId,
        $_FILES['attachments'] ?? []
    );

    $pdo->commit();

    if ($uploadErrors) {
        $_SESSION['flash'] = [
            'type'    => 'warning',
            'message' => 'CRF berhasil diajukan dengan Nomor Register ' . $requestNumber
                . ', namun ada file yang gagal diupload: ' . implode(' ', $uploadErrors),
        ];
    } else {
        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'CRF berhasil diajukan dengan Nomor Register ' . $requestNumber . '.',
        ];
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('submit_crf error: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Terjadi kesalahan saat menyimpan CRF. Silakan coba lagi.',
    ];
}

header('Location: ../user/form_crf.php');
exit;
