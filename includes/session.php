<?php
/**
 * includes/session.php
 * ---------------------------------------------------------------
 * Prototype ini BELUM punya halaman login (lihat brief, butir 5 & 34).
 * File ini mensimulasikan "user yang sedang aktif" dengan cara yang
 * rapi, supaya nanti gampang diganti dengan sesi login SIAP PPU yang
 * sesungguhnya. Jangan hardcode nama/departemen/divisi di HTML -
 * semua diambil dari tabel `users` lewat getCurrentUser().
 * ---------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ID user yang disimulasikan sedang login (lihat brief butir 5 & 9).
const SIMULATED_USER_ID = 1;

/**
 * Mengambil data user aktif dari database.
 * Menyimpan hasilnya ke $_SESSION supaya tidak query berulang-ulang.
 */
function getCurrentUser(): array
{
    if (!isset($_SESSION['active_user'])) {
        $pdo = getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => SIMULATED_USER_ID]);
        $user = $stmt->fetch();

        if (!$user) {
            die('User simulasi (id=' . SIMULATED_USER_ID . ') tidak ditemukan di database. '
                . 'Pastikan database.sql sudah di-import dengan benar.');
        }

        $_SESSION['active_user'] = $user;
    }

    return $_SESSION['active_user'];
}

/**
 * Helper kecil untuk escape output (mencegah XSS).
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
