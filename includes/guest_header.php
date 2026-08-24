<?php
/** Shared head for guest-facing pages (landing, auth). */
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<meta name="description" content="<?= e(APP_TAGLINE) ?> - Prototipe riset TKT 3 integrasi smart contract & fintech untuk akuntabilitas keuangan UMKM.">
<link rel="icon" href="data:,">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg fac-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= base_url('index.php') ?>"><i class="fa-solid fa-link-slash me-1"></i><?= e(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#facNav" style="border-color:rgba(255,255,255,.4)">
      <i class="fa-solid fa-bars text-white"></i>
    </button>
    <div class="collapse navbar-collapse" id="facNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php#overview') ?>">Ringkasan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php#how-it-works') ?>">Cara Kerja</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php#features') ?>">Fitur</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php#research') ?>">Riset</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php#faq') ?>">FAQ</a></li>
        <li class="nav-item ms-lg-2"><a class="btn btn-outline-light btn-sm" href="<?= base_url('auth/login.php') ?>">Masuk</a></li>
        <li class="nav-item"><a class="btn btn-sm text-white" style="background:var(--fac-primary)" href="<?= base_url('auth/register.php') ?>">Daftar UMKM</a></li>
      </ul>
    </div>
  </div>
</nav>
