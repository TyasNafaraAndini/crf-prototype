<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/crf.php';

/**
 * Memastikan user sudah login.
 * Semua user yang sudah login boleh membuka Form CRF dan Pengajuan Saya.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Apakah user yang sedang login termasuk admin CRF?
 * Ditentukan dari atribut user (dept + divisi) sesuai aturan di
 * config/crf.php. Atribut dibaca dari database di setiap request,
 * jadi perubahan jabatan langsung berlaku.
 *
 * Saat integrasi ke SIAP: ganti nama tabel `users` menjadi `tbl_user`.
 */
function isAdmin(): bool
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = getConnection()->prepare(
        'SELECT userid, dept, divisi FROM users WHERE id = :id LIMIT 1'
    );

    $stmt->execute([
        'id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        return $cache = false;
    }

    $norm = static fn($value) => mb_strtolower(trim((string) $value));

    // Akun yang dikecualikan
    $excluded = array_map($norm, CRF_ADMIN_EXCLUDE_USERIDS);

    if (in_array($norm($user['userid']), $excluded, true)) {
        return $cache = false;
    }

    // Cocokkan dengan aturan dept + divisi
    foreach (CRF_ADMIN_RULES as $rule) {
        $divisiList = array_map($norm, $rule['divisi']);

        if (
            $norm($user['dept']) === $norm($rule['dept'])
            && in_array($norm($user['divisi']), $divisiList, true)
        ) {
            return $cache = true;
        }
    }

    return $cache = false;
}

/**
 * Memastikan user sudah login dan termasuk admin CRF.
 */
function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Anda tidak memiliki akses ke halaman tersebut.'
        ];

        header('Location: ../user/pengajuan_saya.php');
        exit;
    }
}

/**
 * Apakah user yang login boleh mengakses CRF (dan lampirannya)?
 * - Pemilik CRF: boleh (termasuk saat masih Draft).
 * - Admin CRF: boleh, kecuali CRF berstatus Draft.
 */
function canAccessCrf(PDO $pdo, int $crfId): bool
{
    if ($crfId <= 0 || !isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT user_id, status
         FROM change_requests
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute(['id' => $crfId]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    if ((int) $row['user_id'] === (int) $_SESSION['user_id']) {
        return true;
    }

    return isAdmin() && $row['status'] !== 'Draft';
}