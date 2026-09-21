<?php
/**
 * includes/header.php
 * ---------------------------------------------------------------
 * Header HTML bersama untuk semua halaman.
 * Pastikan variabel $pageTitle sudah di-set sebelum include file ini.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/auth.php';

if (!isset($pageTitle)) {
    $pageTitle = 'CRF Prototype';
}

$currentUser = getCurrentUser();
$currentPath = basename($_SERVER['PHP_SELF'] ?? '');

$isDashboard = $currentPath === 'dashboard.php';
$isForm = $currentPath === 'form_crf.php';
$isPengajuanSaya = $currentPath === 'pengajuan_saya.php';
$isAdminUser = isAdmin();

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$appBasePath = preg_replace('#/(?:admin|user)/[^/]+$#', '', $scriptPath) ?: '';
$appBasePath = rtrim($appBasePath, '/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= h($pageTitle) ?> · CRF Prototype PPU</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>

<link
    href="<?= h($appBasePath) ?>/assets/css/style.css"
    rel="stylesheet"
>

</head>

<body>

<div class="crf-app-shell">

  <aside class="crf-sidebar">

    <!-- BRAND -->
    <a
        class="crf-sidebar-brand"
        href="<?= h($appBasePath) ?><?= $isAdminUser
            ? '/admin/dashboard.php'
            : '/user/form_crf.php' ?>"
    >
        <span class="crf-sidebar-mark">
            <i class="bi bi-house-door-fill"></i>
        </span>

        <span class="ppu-brand-text">CRF</span>
    </a> 


    <div class="crf-sidebar-section">
        Menu Utama
    </div>


    <nav
        class="crf-sidebar-nav"
        aria-label="Navigasi utama"
    >

        <?php if ($isAdminUser): ?>

            <!-- DASHBOARD ADMIN -->
            <a
                class="<?= $isDashboard ? 'active' : '' ?>"
                href="<?= h($appBasePath) ?>/admin/dashboard.php"
            >
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>


            <!-- FORM CRF -->
            <a
                class="<?= $isForm ? 'active' : '' ?>"
                href="<?= h($appBasePath) ?>/user/form_crf.php"
            >
                <i class="bi bi-file-earmark-plus"></i>
                <span>Form CRF</span>
            </a>

        <?php else: ?>

            <!-- FORM CRF USER -->
            <a
                class="<?= $isForm ? 'active' : '' ?>"
                href="<?= h($appBasePath) ?>/user/form_crf.php"
            >
                <i class="bi bi-file-earmark-plus"></i>
                <span>Form CRF</span>
            </a>


            <!-- PENGAJUAN SAYA -->
            <a
                class="<?= $isPengajuanSaya ? 'active' : '' ?>"
                href="<?= h($appBasePath) ?>/user/pengajuan_saya.php"
            >
                <i class="bi bi-file-earmark-check"></i>
                <span>Pengajuan Saya</span>
            </a>

        <?php endif; ?>

    </nav>


    <!-- FOOTER SIDEBAR -->
    <div class="crf-sidebar-footer">

        <a href="<?= h($appBasePath) ?>/actions/logout.php">
            <i class="bi bi-box-arrow-left"></i>
            Keluar
        </a>

    </div>

  </aside>


  <div class="crf-content-shell">

    <header class="crf-topbar">

    <!-- KANAN -->
    <div class="crf-user">

      <strong>
          <?= h($currentUser['nama'] ?? '-') ?>
      </strong>

      <span class="crf-avatar">
          <?= h(
              strtoupper(
                  substr(
                      $currentUser['nama'] ?? 'U',
                      0,
                      2
                  )
              )
          ) ?>
      </span>

    </div>

  </header>