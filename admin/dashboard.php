<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$msmeCount = (int) $db->query('SELECT COUNT(*) FROM msmes')->fetchColumn();
$validatorCount = (int) $db->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='validator'")->fetchColumn();
$trxCount = (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
$rulesCount = (int) $db->query('SELECT COUNT(*) FROM smart_contract_rules WHERE is_active=1')->fetchColumn();
$chain = HashChain::verifyChain();

$recentTrx = $db->query(
    "SELECT t.*, m.business_name FROM transactions t JOIN msmes m ON m.id=t.msme_id ORDER BY t.id DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard Peneliti';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $userCount ?></div><div class="fac-stat-label">Pengguna</div></div></div>
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $msmeCount ?></div><div class="fac-stat-label">UMKM</div></div></div>
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $validatorCount ?></div><div class="fac-stat-label">Validator</div></div></div>
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $trxCount ?></div><div class="fac-stat-label">Transaksi</div></div></div>
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $pendingCount ?></div><div class="fac-stat-label">Menunggu Validasi</div></div></div>
  <div class="col-6 col-lg-2"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $rulesCount ?></div><div class="fac-stat-label">Rule Aktif</div></div></div>
</div>

<div class="card fac-card p-3 mb-3 text-center">
  <?= $chain['is_valid'] ? '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Integrity Verified</span>' : '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Integrity Warning</span>' ?>
  <span class="text-muted small"> — <?= $chain['total_entries'] ?> entri audit trail global</span>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Transaksi Terbaru (Seluruh UMKM)</h6>
      <?php if (!$recentTrx): render_empty_state('fa-right-left', 'Belum ada transaksi.'); else: ?>
      <table class="table table-fac table-sm">
        <thead><tr><th>UMKM</th><th>ID</th><th>Jenis</th><th>Nominal</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentTrx as $r): ?>
          <tr>
            <td><?= e($r['business_name']) ?></td>
            <td><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>"><?= e($r['transaction_uid']) ?></a></td>
            <td><?= e(TransactionService::TYPES[$r['type']] ?? $r['type']) ?></td>
            <td><?= format_money($r['amount']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Akses Cepat</h6>
      <div class="d-grid gap-2">
        <a href="<?= base_url('admin/users.php') ?>" class="btn btn-outline-secondary btn-sm text-start"><i class="fa-solid fa-user-gear me-2"></i>Kelola Pengguna</a>
        <a href="<?= base_url('admin/msmes.php') ?>" class="btn btn-outline-secondary btn-sm text-start"><i class="fa-solid fa-shop me-2"></i>Kelola UMKM</a>
        <a href="<?= base_url('admin/rules.php') ?>" class="btn btn-outline-secondary btn-sm text-start"><i class="fa-solid fa-gears me-2"></i>Kelola Smart Contract Rules</a>
        <a href="<?= base_url('admin/research_dashboard.php') ?>" class="btn btn-outline-secondary btn-sm text-start"><i class="fa-solid fa-chart-line me-2"></i>Dashboard Riset</a>
        <a href="<?= base_url('reports/index.php') ?>" class="btn btn-outline-secondary btn-sm text-start"><i class="fa-solid fa-file-pdf me-2"></i>Cetak Laporan</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
