<?php
/**
 * Shared authenticated-app shell (sidebar + topbar + content opener).
 * Expects $pageTitle (string) and optional $activeMenu (string) to be set
 * by the including page before requiring this file.
 */
require_login();
$u = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';

function fac_nav_item(string $key, string $active, string $href, string $icon, string $label): string
{
    $isActive = $key === $active ? ' active' : '';
    return '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . e($href) . '"><i class="fa-solid ' . e($icon) . '"></i>' . e($label) . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:,">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>

<div class="fac-app-sidebar" id="facSidebar">
  <div class="brand"><i class="fa-solid fa-link-slash"></i> <?= e(APP_NAME) ?></div>
  <ul class="nav flex-column pt-2">
  <?php if ($u['role_code'] === 'admin'): ?>
    <div class="nav-section">Riset & Administrasi</div>
    <?= fac_nav_item('dashboard', $activeMenu, base_url('admin/dashboard.php'), 'fa-gauge-high', 'Dashboard Peneliti') ?>
    <?= fac_nav_item('research_dashboard', $activeMenu, base_url('admin/research_dashboard.php'), 'fa-chart-line', 'Dashboard Riset') ?>
    <div class="nav-section">Manajemen</div>
    <?= fac_nav_item('users', $activeMenu, base_url('admin/users.php'), 'fa-user-gear', 'Pengguna') ?>
    <?= fac_nav_item('msmes', $activeMenu, base_url('admin/msmes.php'), 'fa-shop', 'UMKM') ?>
    <?= fac_nav_item('validators', $activeMenu, base_url('admin/validators.php'), 'fa-user-shield', 'Validator') ?>
    <?= fac_nav_item('transactions', $activeMenu, base_url('admin/transactions.php'), 'fa-right-left', 'Transaksi') ?>
    <?= fac_nav_item('rules', $activeMenu, base_url('admin/rules.php'), 'fa-gears', 'Smart Contract Rules') ?>
    <div class="nav-section">Riset & Laporan</div>
    <?= fac_nav_item('questionnaires', $activeMenu, base_url('research/questionnaire.php'), 'fa-clipboard-list', 'Kuesioner') ?>
    <?= fac_nav_item('expert', $activeMenu, base_url('research/expert_summary.php'), 'fa-user-graduate', 'Validasi Ahli') ?>
    <?= fac_nav_item('research_export', $activeMenu, base_url('research/export.php'), 'fa-file-export', 'Ekspor Dataset') ?>
    <?= fac_nav_item('reports', $activeMenu, base_url('reports/index.php'), 'fa-file-pdf', 'Laporan') ?>
    <?= fac_nav_item('settings', $activeMenu, base_url('admin/settings.php'), 'fa-sliders', 'Pengaturan Formula') ?>
  <?php elseif ($u['role_code'] === 'msme'): ?>
    <div class="nav-section">Utama</div>
    <?= fac_nav_item('dashboard', $activeMenu, base_url('msme/dashboard.php'), 'fa-gauge-high', 'Dashboard') ?>
    <?= fac_nav_item('profile', $activeMenu, base_url('msme/profile.php'), 'fa-building', 'Profil Usaha') ?>
    <div class="nav-section">Keuangan</div>
    <?= fac_nav_item('transactions', $activeMenu, base_url('msme/transactions.php'), 'fa-right-left', 'Transaksi Keuangan') ?>
    <?= fac_nav_item('fintech', $activeMenu, base_url('msme/fintech_simulate.php'), 'fa-mobile-screen-button', 'Simulasi Transaksi Fintech') ?>
    <?= fac_nav_item('accounting', $activeMenu, base_url('msme/accounting.php'), 'fa-book', 'Jurnal & Buku Besar') ?>
    <div class="nav-section">Akuntabilitas</div>
    <?= fac_nav_item('accountability', $activeMenu, base_url('msme/accountability.php'), 'fa-shield-halved', 'Skor Akuntabilitas') ?>
    <?= fac_nav_item('chain', $activeMenu, base_url('msme/chain_verification.php'), 'fa-link', 'Verifikasi Rantai Hash') ?>
    <?= fac_nav_item('reports', $activeMenu, base_url('reports/index.php'), 'fa-file-pdf', 'Laporan') ?>
    <div class="nav-section">Riset</div>
    <?= fac_nav_item('questionnaires', $activeMenu, base_url('research/questionnaire.php'), 'fa-clipboard-list', 'Kuesioner') ?>
  <?php elseif ($u['role_code'] === 'validator'): ?>
    <div class="nav-section">Validasi</div>
    <?= fac_nav_item('dashboard', $activeMenu, base_url('validator/dashboard.php'), 'fa-gauge-high', 'Dashboard Validator') ?>
    <?= fac_nav_item('verify', $activeMenu, base_url('validator/verify.php'), 'fa-check-double', 'Verifikasi Transaksi') ?>
    <?= fac_nav_item('audit', $activeMenu, base_url('validator/audit_trail.php'), 'fa-magnifying-glass-chart', 'Audit Trail') ?>
    <?= fac_nav_item('chain', $activeMenu, base_url('validator/chain_verification.php'), 'fa-link', 'Verifikasi Rantai Hash') ?>
    <div class="nav-section">Penilaian Ahli</div>
    <?= fac_nav_item('expert', $activeMenu, base_url('validator/expert_validation.php'), 'fa-star-half-stroke', 'Expert Judgment') ?>
    <?= fac_nav_item('questionnaires', $activeMenu, base_url('research/questionnaire.php'), 'fa-clipboard-list', 'Kuesioner') ?>
  <?php endif; ?>
  </ul>
</div>
<div class="fac-sidebar-backdrop d-lg-none" id="facSidebarBackdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1025;display:none;"></div>
<style>#facSidebarBackdrop.show{display:block;}</style>

<div class="fac-app-main">
  <div class="fac-app-topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="facSidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <h5 class="mb-0"><?= e($pageTitle) ?></h5>
    </div>
    <div class="dropdown">
      <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <i class="fa-solid fa-circle-user fa-lg"></i>
        <span class="d-none d-sm-inline"><?= e($u['name']) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text text-muted small"><?= e($u['role_name']) ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= base_url('auth/logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
  <div class="fac-app-content">
    <?php $flashes = get_flashes(); if ($flashes): ?>
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($f['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
