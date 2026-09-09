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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> · CRF Prototype PPU</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/crf-prototype/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg crf-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand crf-brand" href="/crf-prototype/">
      <span class="crf-brand-mark">CRF</span>
      <span class="crf-brand-text">PT Persona Prima Utama</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#crfNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="crfNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="/crf-prototype/user/form_crf.php">
            <i class="bi bi-file-earmark-text"></i> Form CRF
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/crf-prototype/admin/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard Admin
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
