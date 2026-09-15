<?php
/**
 * includes/header.php
 * ---------------------------------------------------------------
 * Header HTML bersama untuk semua halaman.
 * Pastikan variabel $pageTitle sudah di-set sebelum include file ini.
 * ---------------------------------------------------------------
 */
if (!isset($pageTitle)) {
    $pageTitle = 'CRF Prototype';
}
$currentPath = basename($_SERVER['PHP_SELF'] ?? '');
$isDashboard = $currentPath === 'dashboard.php';
$isForm = $currentPath === 'form_crf.php';
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= h($appBasePath) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="crf-app-shell">
  <aside class="crf-sidebar">
    <a class="crf-sidebar-brand" href="<?= h($appBasePath) ?>/user/form_crf.php">
      <span class="crf-sidebar-mark"><i class="bi bi-file-earmark-text"></i></span>
      <span>SIAP PPU</span>
    </a>
    <div class="crf-sidebar-section">Menu Utama</div>
    <nav class="crf-sidebar-nav" aria-label="Navigasi utama">
      <a class="<?= $isDashboard ? 'active' : '' ?>" href="<?= h($appBasePath) ?>/admin/dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
      </a>
      <a class="<?= $isForm ? 'active' : '' ?>" href="<?= h($appBasePath) ?>/user/form_crf.php">
        <i class="bi bi-file-earmark-plus"></i><span>Form CRF</span>
      </a>
    </nav>
    <div class="crf-sidebar-footer"><a href="<?= h($appBasePath) ?>/"><i class="bi bi-box-arrow-left"></i> Keluar</a></div>
  </aside>
  <div class="crf-content-shell">
    <header class="crf-topbar">
      <div class="crf-breadcrumb"><strong>SIAP PPU</strong><span>Menu Saya</span><i class="bi bi-chevron-right"></i><span><?= h($pageTitle) ?></span></div>
      <div class="crf-user">
        <div><strong>Damayanti Diah P</strong><small>Magang</small></div>
        <span class="crf-avatar">DP</span>
      </div>
    </header>