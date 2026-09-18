<?php
/**
 * actions/update_crf.php
 * ---------------------------------------------------------------
 * Menangani penyimpanan dari admin/edit.php:
 *   - Level Complain (Tinggi / Normal / Rendah)
 *   - Status
 *     (Belum Ditindak Lanjuti / Dalam Proses / Solve / Cancel)
 *   - Tanggapan / Tindak Lanjut
 *
 * Post Implementation Review dan Implementasi
 * TIDAK diubah melalui file ini.
 * Kedua field tersebut diisi oleh user melalui detail pengajuan.
 *
 * Aturan solved_at / cancelled_at:
 *   - Saat status berubah menjadi Solve -> solved_at diisi waktu saat itu
 *     kalau belum pernah diisi.
 *   - Saat status berubah menjadi Cancel -> cancelled_at diisi waktu saat itu
 *     kalau belum pernah diisi.
 *   - Saat status menjadi Belum Ditindak Lanjuti atau Dalam Proses
 *     -> solved_at dan cancelled_at dikosongkan.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ../admin/dashboard.php');
    exit;
}

$pdo = getConnection();
$admin = getCurrentUser();

$id = (int) ($_POST['id'] ?? 0);

$levelRaw = $_POST['level'] ?? '';

$statusRaw = $_POST['status'] ?? '';

$tanggapan = trim(
    $_POST['tanggapan_tindak_lanjut'] ?? ''
);


$allowedLevels = [
    'Tinggi',
    'Normal',
    'Rendah'
];

$allowedStatuses = [
    'Belum Ditindak Lanjuti',
    'Perlu Revisi',
    'Dalam Proses',
    'Solve',
    'Cancel'
];


$level = in_array(
    $levelRaw,
    $allowedLevels,
    true
)
    ? $levelRaw
    : null;


$status = in_array(
    $statusRaw,
    $allowedStatuses,
    true
)
    ? $statusRaw
    : null;

    if (
        $status === 'Perlu Revisi'
        && $tanggapan === ''
    ) {

        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Tanggapan / Tindak Lanjut wajib diisi jika status Perlu Revisi.'
        ];

        header(
            'Location: ../admin/edit.php?id='
            . $id
        );

        exit;
    }


if ($id <= 0 || $status === null) {

    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Data tidak valid.'
    ];

    header('Location: ../admin/dashboard.php');
    exit;
}


/*
 * Ambil status dan timestamp sebelumnya.
 */
$stmt = $pdo->prepare(
    'SELECT
        status,
        approval_at,
        solved_at,
        cancelled_at
     FROM change_requests
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $id
]);

$current = $stmt->fetch();

if (!$current) {

    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Pengajuan CRF tidak ditemukan.'
    ];

    header('Location: ../admin/dashboard.php');
    exit;
}

$currentStatus = $current['status'];


/*
 * Pertahankan timestamp yang sudah ada.
 */
$approvalAt = $current['approval_at'] ?? null;

$solvedAt = $current['solved_at'] ?? null;

$cancelledAt = $current['cancelled_at'] ?? null;


/*
 * Belum Ditindak Lanjuti -> Dalam Proses
 * dianggap sebagai waktu approval / mulai proses.
 */
if (
    ($current['status'] ?? '') === 'Belum Ditindak Lanjuti'
    &&
    $statusRaw === 'Dalam Proses'
) {

    $approvalAt = $approvalAt ?: date('Y-m-d H:i:s');
}


/*
 * Status Solve.
 */
if ($statusRaw === 'Solve') {

    $solvedAt = $solvedAt ?: date('Y-m-d H:i:s');

    $cancelledAt = null;


/*
 * Status Cancel.
 */
} elseif ($statusRaw === 'Cancel') {

    $cancelledAt = $cancelledAt ?: date('Y-m-d H:i:s');

    $solvedAt = null;


/*
 * Status aktif / belum selesai.
 */
} elseif (
    $statusRaw === 'Dalam Proses'
    ||
    $statusRaw === 'Belum Ditindak Lanjuti'
    ||
    $statusRaw === 'Perlu Revisi'
) {

    $solvedAt = null;

    $cancelledAt = null;
}


try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE change_requests
         SET
            level = :level,
            status = :status,
            tanggapan_tindak_lanjut = :tanggapan,
            approval_at = :approval_at,
            solved_at = :solved_at,
            cancelled_at = :cancelled_at
         WHERE id = :id'
    );


    $stmt->execute([
        'level' => $level,

        'status' => $status,

        'tanggapan' =>
            $tanggapan !== ''
                ? $tanggapan
                : null,

        'approval_at' => $approvalAt,

        'solved_at' => $solvedAt,

        'cancelled_at' => $cancelledAt,

        'id' => $id,
    ]);

   /*
 * ---------------------------------------------------------------
 * Catat perubahan status ke timeline
 * ---------------------------------------------------------------
 */
if ($currentStatus !== $status) {

    $actor = !empty($admin['nama'])
        ? $admin['nama']
        : $admin['userid'];

    $activity = $status;
    $description = null;

    if ($status === 'Dalam Proses') {

        $description = 'Pengajuan sedang diproses oleh admin.';

    } elseif ($status === 'Perlu Revisi') {

        $description = $tanggapan;

    } elseif ($status === 'Solve') {

        $description = 'Pengajuan telah selesai diproses.';

    } elseif ($status === 'Cancel') {

        $description = 'Pengajuan dibatalkan.';
    }

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

    $logStmt->execute([
        'change_request_id' => $id,
        'activity'          => $activity,
        'description'       => $description,
        'actor'             => $actor,
    ]);
}

    $pdo->commit();

    $_SESSION['flash'] = [
        'type' => 'success',

        'message' =>
            'Perubahan berhasil disimpan. Status saat ini: '
            . statusLabel($status)
            . '.',
    ];

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'update_crf error: '
        . $e->getMessage()
    );


    $_SESSION['flash'] = [
        'type' => 'danger',

        'message' =>
            'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.',
    ];
}


header(
    'Location: ../admin/detail.php?id='
    . $id
);

exit;