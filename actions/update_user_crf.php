<?php
/**
 * actions/update_user_crf.php
 * ---------------------------------------------------------------
 * Menyimpan Post Implementation Review dan Implementasi
 * yang diisi oleh user.
 *
 * User hanya dapat mengubah CRF miliknya sendiri.
 * Pengisian hanya diperbolehkan ketika status CRF = Dalam Proses.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../user/pengajuan_saya.php');
    exit;
}

verifyCsrf();


$user = getCurrentUser();
$pdo = getConnection();

$id = (int) ($_POST['id'] ?? 0);

$postImplementationReview = trim(
    $_POST['post_implementation_review'] ?? ''
);

$implementation = trim(
    $_POST['implementation'] ?? ''
);

/*
 * Pastikan CRF memang milik user yang sedang login
 * dan statusnya masih Dalam Proses.
 */
$stmt = $pdo->prepare(
    'SELECT id, status
     FROM change_requests
     WHERE id = :id
       AND user_id = :user_id
     LIMIT 1'
);

$stmt->execute([
    'id' => $id,
    'user_id' => $user['id']
]);

$crf = $stmt->fetch();

if (!$crf) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Pengajuan CRF tidak ditemukan atau bukan milik Anda.'
    ];

    header('Location: ../user/pengajuan_saya.php');
    exit;
}

/*
 * Hanya boleh diisi saat status = Dalam Proses.
 */
if ($crf['status'] !== 'Dalam Proses') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Post Implementation Review dan Implementasi hanya dapat diisi ketika CRF dalam proses.'
    ];

    header('Location: ../user/detail.php?id=' . $id);
    exit;
}

/*
 * Simpan PIR dan Implementasi.
 */
$stmt = $pdo->prepare(
    'UPDATE change_requests
     SET
        post_implementation_review = :post_implementation_review,
        implementation = :implementation
     WHERE id = :id
       AND user_id = :user_id'
);

$stmt->execute([
    'post_implementation_review' => $postImplementationReview !== ''
        ? $postImplementationReview
        : null,

    'implementation' => $implementation !== ''
        ? $implementation
        : null,

    'id' => $id,
    'user_id' => $user['id']
]);

$logStmt = $pdo->prepare("
    INSERT INTO crf_activity_logs (
        change_request_id,
        activity,
        description,
        actor
    ) VALUES (
        :change_request_id,
        :activity,
        :description,
        :actor
    )
");

$actor = !empty($user['nama']) ? $user['nama'] : $user['userid'];

$logStmt->execute([
    'change_request_id' => $id,
    'activity'          => 'Isi PIR & Implementasi',
    'description'       => 'User mengisi/memperbarui Post Implementation Review dan Implementasi.',
    'actor'             => $actor,
]);

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Post Implementation Review dan Implementasi berhasil disimpan.'
];

header('Location: ../user/detail.php?id=' . $id);
exit;