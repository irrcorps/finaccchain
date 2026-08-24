<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$msmeId = (int) ($_GET['msme_id'] ?? 0);
$status = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = 'WHERE 1=1';
$params = [];
if ($msmeId) { $where .= ' AND t.msme_id = ?'; $params[] = $msmeId; }
if ($status) { $where .= ' AND t.status = ?'; $params[] = $status; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
[$page, $totalPages, $offset] = paginate_query($total, $page, 15);

$stmt = $db->prepare("SELECT t.*, m.business_name FROM transactions t JOIN msmes m ON m.id=t.msme_id $where ORDER BY t.id DESC LIMIT 15 OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$msmes = $db->query('SELECT id, business_name FROM msmes ORDER BY business_name')->fetchAll();

$pageTitle = 'Transaksi (Seluruh UMKM)';
$activeMenu = 'transactions';
require __DIR__ . '/../includes/app_header.php';
?>
<form class="d-flex flex-wrap gap-2 mb-3" method="get">
  <select name="msme_id" class="form-select form-select-sm" style="width:220px">
    <option value="">Semua UMKM</option>
    <?php foreach ($msmes as $m): ?><option value="<?= $m['id'] ?>" <?= $msmeId===(int)$m['id']?'selected':'' ?>><?= e($m['business_name']) ?></option><?php endforeach; ?>
  </select>
  <select name="status" class="form-select form-select-sm" style="width:150px">
    <option value="">Semua Status</option>
    <?php foreach (['draft','pending','validated','rejected','recorded','reversed'] as $s): ?>
    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-sm btn-outline-secondary">Filter</button>
</form>

<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>UMKM</th><th>ID</th><th>Tanggal</th><th>Jenis</th><th>Nominal</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7"><?php render_empty_state('fa-right-left','Belum ada transaksi.'); ?></td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['business_name']) ?></td>
          <td><?= e($r['transaction_uid']) ?></td>
          <td><?= format_date($r['transaction_date']) ?></td>
          <td><?= e(TransactionService::TYPES[$r['type']] ?? $r['type']) ?></td>
          <td><?= format_money($r['amount']) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3"><?php render_pagination($page, $totalPages, http_build_query(['msme_id'=>$msmeId,'status'=>$status])); ?></div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
