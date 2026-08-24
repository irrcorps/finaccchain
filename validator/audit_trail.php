<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('validator');
$db = Database::connection();

$msmeId = (int) ($_GET['msme_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = '1=1';
$params = [];
if ($msmeId) { $where .= ' AND t.msme_id = ?'; $params[] = $msmeId; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_trails at JOIN transactions t ON t.id = at.transaction_id WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
[$page, $totalPages, $offset] = paginate_query($total, $page, 20);

$stmt = $db->prepare(
    "SELECT at.*, t.transaction_uid, m.business_name, u.name AS actor_name
     FROM audit_trails at
     JOIN transactions t ON t.id = at.transaction_id
     JOIN msmes m ON m.id = t.msme_id
     LEFT JOIN users u ON u.id = at.actor_id
     WHERE $where ORDER BY at.id DESC LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$msmes = $db->query('SELECT id, business_name FROM msmes ORDER BY business_name')->fetchAll();

$pageTitle = 'Audit Trail';
$activeMenu = 'audit';
require __DIR__ . '/../includes/app_header.php';
?>
<form class="d-flex gap-2 mb-3" method="get">
  <select name="msme_id" class="form-select form-select-sm" style="width:250px" onchange="this.form.submit()">
    <option value="">Semua UMKM</option>
    <?php foreach ($msmes as $m): ?>
      <option value="<?= $m['id'] ?>" <?= $msmeId===(int)$m['id']?'selected':'' ?>><?= e($m['business_name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>ID</th><th>UMKM</th><th>Transaksi</th><th>Aksi</th><th>Aktor</th><th>Hash</th><th>Waktu</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7"><?php render_empty_state('fa-magnifying-glass-chart', 'Belum ada entri audit trail.'); ?></td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= e($r['business_name']) ?></td>
          <td><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['transaction_id']) ?>"><?= e($r['transaction_uid']) ?></a></td>
          <td><?= e(ucwords(str_replace('_',' ',$r['action']))) ?></td>
          <td><?= e($r['actor_name'] ?? 'sistem') ?></td>
          <td class="hash-chip"><?= substr($r['current_hash'],0,14) ?>…</td>
          <td><?= format_datetime($r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3"><?php render_pagination($page, $totalPages, http_build_query(['msme_id'=>$msmeId])); ?></div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
