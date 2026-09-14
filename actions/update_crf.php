<?php
/**
 * actions/update_crf.php
 * ---------------------------------------------------------------
 * Menangani penyimpanan dari admin/edit.php:
 *   - Level Complain (Kecil / Sedang / Tinggi)
 *   - Status (Belum Ditindak Lanjuti / Dalam Proses / Solve / Cancel) - lihat brief butir 23
 *   - Tanggapan / Tindak Lanjut
 *   - Post Implementation Review
 *   - Implementasi
 *
 * Aturan solved_at / cancelled_at:
 *   - Saat status berubah menjadi Solve -> solved_at diisi waktu saat itu
 *     (kalau belum pernah diisi sebelumnya).
 *   - Saat status berubah menjadi Cancel -> cancelled_at diisi waktu saat itu
 *     (kalau belum pernah diisi sebelumnya).
 *   - Saat status menjadi belum Ditindak Lanjuti atau Dalam Proses 
 *    -> solved_at dan cancelled_at dikosongkan.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$pdo = getConnection();

$id                  = (int) ($_POST['id'] ?? 0);
$levelRaw            = $_POST['level'] ?? '';
$statusRaw           = $_POST['status'] ?? '';
$tanggapan           = trim($_POST['tanggapan_tindak_lanjut'] ?? '');
$postImplementation  = trim($_POST['post_implementation_review'] ?? '');
$implementation      = trim($_POST['implementation'] ?? '');

$allowedLevels  = ['Kecil', 'Sedang', 'Tinggi'];
$allowedStatuses = [ 'Belum Ditindak Lanjuti', 'Dalam Proses', 'Solve', 'Cancel'];

$level  = in_array($levelRaw, $allowedLevels, true) ? $levelRaw : null;
$status = in_array($statusRaw, $allowedStatuses, true) ? $statusRaw : null;

if ($id <= 0 || $status === null) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Data tidak valid.'];
    header('Location: ../admin/dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT status, solved_at, cancelled_at FROM change_requests WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$current = $stmt->fetch();

if (!$current) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pengajuan CRF tidak ditemukan.'];
    header('Location: ../admin/dashboard.php');
    exit;
}

$solvedAt    = $current['solved_at'];
$cancelledAt = $current['cancelled_at'];

if ($status === 'Solve') {
    $solvedAt = $solvedAt ?? (new DateTime())->format('Y-m-d H:i:s');
    $cancelledAt = null;
} elseif ($status === 'Cancel') {
    $cancelledAt = $cancelledAt ?? (new DateTime())->format('Y-m-d H:i:s');
    $solvedAt = null;
} elseif (
    $status === 'Dalam Proses' ||
    $status === 'Belum Ditindak Lanjuti'
) {
    $solvedAt = null;
    $cancelledAt = null;
}

try {
    $stmt = $pdo->prepare(
        'UPDATE change_requests
         SET level = :level,
             status = :status,
             tanggapan_tindak_lanjut = :tanggapan,
             solved_at = :solved_at,
             cancelled_at = :cancelled_at,
             post_implementation_review = :pir,
             implementation = :implementation
         WHERE id = :id'
    );
    $stmt->execute([
        'level'          => $level,
        'status'         => $status,
        'tanggapan'      => $tanggapan !== '' ? $tanggapan : null,
        'solved_at'      => $solvedAt,
        'cancelled_at'   => $cancelledAt,
        'pir'            => $postImplementation !== '' ? $postImplementation : null,
        'implementation' => $implementation !== '' ? $implementation : null,
        'id'             => $id,
    ]);

    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => 'Perubahan berhasil disimpan. Status saat ini: ' . statusLabel($status) . '.',
    ];
} catch (Throwable $e) {
    error_log('update_crf error: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.',
    ];
}

header('Location: ../admin/detail.php?id=' . $id);
exit;
