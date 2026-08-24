<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('validator');
$db = Database::connection();

$pending = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn();
$validatedToday = (int) $db->query("SELECT COUNT(*) FROM audit_trails WHERE action = 'validated' AND DATE(created_at) = CURDATE()")->fetchColumn();
$rejected = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'rejected'")->fetchColumn();
$msmeCount = (int) $db->query("SELECT COUNT(*) FROM msmes")->fetchColumn();
$chain = HashChain::verifyChain();

$pendingList = $db->query(
    "SELECT t.*, m.business_name FROM transactions t JOIN msmes m ON m.id = t.msme_id WHERE t.status = 'pending' ORDER BY t.amount DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard Validator';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $pending ?></div><div class="fac-stat-label">Menunggu Verifikasi</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $validatedToday ?></div><div class="fac-stat-label">Tervalidasi Hari Ini</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $rejected ?></div><div class="fac-stat-label">Ditolak</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $msmeCount ?></div><div class="fac-stat-label">UMKM Terdaftar</div></div></div>
</div>

<div class="card fac-card p-3 mb-3 text-center">
  <?= $chain['is_valid'] ? '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Integrity Verified</span>' : '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Integrity Warning</span>' ?>
  <span class="text-muted small"> — <?= $chain['total_entries'] ?> entri audit trail global</span>
</div>

<div class="card fac-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Transaksi Prioritas Menunggu Verifikasi</h6>
    <a href="<?= base_url('validator/verify.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
  </div>
  <?php if (!$pendingList): render_empty_state('fa-check-double', 'Tidak ada transaksi yang menunggu verifikasi saat ini.'); else: ?>
  <table class="table table-fac">
    <thead><tr><th>UMKM</th><th>ID</th><th>Jenis</th><th>Nominal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pendingList as $p): ?>
      <tr>
        <td><?= e($p['business_name']) ?></td>
        <td><?= e($p['transaction_uid']) ?></td>
        <td><?= e(TransactionService::TYPES[$p['type']] ?? $p['type']) ?></td>
        <td><?= format_money($p['amount']) ?></td>
        <td><a href="<?= base_url('validator/verify.php?id=' . $p['id']) ?>" class="btn btn-sm text-white" style="background:var(--fac-primary)">Tinjau</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
