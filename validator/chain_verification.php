<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('validator');
$chain = HashChain::verifyChain();

$pageTitle = 'Verifikasi Rantai Hash';
$activeMenu = 'chain';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="fac-disclaimer p-3 mb-4">
  <i class="fa-solid fa-circle-info me-1"></i> Simulasi rantai hash konseptual untuk prototipe riset; bukan jaringan blockchain produksi.
</div>

<div class="card fac-card p-4 mb-3 text-center">
  <?php if ($chain['is_valid']): ?>
    <div class="display-6 text-success mb-2"><i class="fa-solid fa-circle-check"></i> Integrity Verified</div>
    <p class="text-muted mb-0">Seluruh <?= $chain['total_entries'] ?> entri audit trail global membentuk rantai hash yang konsisten.</p>
  <?php else: ?>
    <div class="display-6 text-danger mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Integrity Warning</div>
    <p class="text-muted mb-0">Terdeteksi <?= count($chain['broken_entry_ids']) ?> entri bermasalah (ID: <?= implode(', ', $chain['broken_entry_ids']) ?>).</p>
  <?php endif; ?>
</div>

<div class="card fac-card p-4">
  <h6 class="fw-bold mb-3">Seluruh Rantai Hash (Global)</h6>
  <div class="table-responsive" style="max-height:520px;overflow-y:auto;">
  <table class="table table-fac table-sm">
    <thead><tr><th>#</th><th>Transaksi</th><th>Aksi</th><th>Hash Saat Ini</th><th>Hash Sebelumnya</th><th>Waktu</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($chain['rows'] as $e): $broken = in_array((int)$e['id'], $chain['broken_entry_ids'], true); ?>
      <tr class="<?= $broken ? 'table-danger' : '' ?>">
        <td><?= $e['id'] ?></td>
        <td><?= (int) $e['transaction_id'] ?></td>
        <td><?= e(ucwords(str_replace('_',' ',$e['action']))) ?></td>
        <td class="hash-chip"><?= substr($e['current_hash'],0,18) ?>…</td>
        <td class="hash-chip"><?= substr($e['previous_hash'],0,18) ?>…</td>
        <td><?= format_datetime($e['created_at']) ?></td>
        <td><?= $broken ? '<i class="fa-solid fa-triangle-exclamation text-danger"></i>' : '<i class="fa-solid fa-check text-success"></i>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
