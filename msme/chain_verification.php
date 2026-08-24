<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

$chain = HashChain::verifyChain();
$stmt = $db->prepare('SELECT id FROM transactions WHERE msme_id = ?');
$stmt->execute([$msmeId]);
$myTrxIds = array_column($stmt->fetchAll(), 'id');
$myEntries = array_values(array_filter($chain['rows'], fn($r) => in_array((int) $r['transaction_id'], $myTrxIds, true)));

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
    <p class="text-muted mb-0">Seluruh <?= $chain['total_entries'] ?> entri audit trail pada sistem membentuk rantai hash yang konsisten dan tidak terputus.</p>
  <?php else: ?>
    <div class="display-6 text-danger mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Integrity Warning</div>
    <p class="text-muted mb-0">Terdeteksi <?= count($chain['broken_entry_ids']) ?> entri yang tidak konsisten dengan hash sebelumnya (ID: <?= implode(', ', $chain['broken_entry_ids']) ?>).</p>
  <?php endif; ?>
</div>

<div class="card fac-card p-4">
  <h6 class="fw-bold mb-3">Rantai Hash Transaksi UMKM Saya</h6>
  <?php if (!$myEntries): render_empty_state('fa-link', 'Belum ada entri rantai hash.'); else: ?>
  <div class="table-responsive">
  <table class="table table-fac table-sm">
    <thead><tr><th>#</th><th>Aksi</th><th>Hash Saat Ini</th><th>Hash Sebelumnya</th><th>Waktu</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($myEntries as $e): $broken = in_array((int)$e['id'], $chain['broken_entry_ids'], true); ?>
      <tr class="<?= $broken ? 'table-danger' : '' ?>">
        <td><?= $e['id'] ?></td>
        <td><?= e(ucwords(str_replace('_',' ',$e['action']))) ?></td>
        <td class="hash-chip"><?= substr($e['current_hash'],0,20) ?>…</td>
        <td class="hash-chip"><?= substr($e['previous_hash'],0,20) ?>…</td>
        <td><?= format_datetime($e['created_at']) ?></td>
        <td><?= $broken ? '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '<span class="text-success"><i class="fa-solid fa-check"></i></span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
