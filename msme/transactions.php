<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $required = ['transaction_date', 'type', 'amount', 'payment_channel'];
        $missing = array_filter($required, fn($f) => empty($_POST[$f]));
        if ($missing) {
            flash('danger', 'Lengkapi semua field wajib.');
        } else {
            $id = TransactionService::create($msmeId, $u['id'], $_POST);
            $result = RuleEngine::process($id, $u['id']);
            flash($result['ok'] ? 'success' : 'warning', 'Transaksi dibuat. Hasil rule engine: ' . implode(' | ', $result['log']));
        }
        redirect('msme/transactions.php');
    }

    if ($action === 'import_csv') {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            flash('danger', 'Pilih file CSV terlebih dahulu.');
        } else {
            [$count, $errors] = TransactionService::importCsv($msmeId, $u['id'], $_FILES['csv_file']['tmp_name']);
            flash($count > 0 ? 'success' : 'warning', "$count transaksi berhasil diimpor." . ($errors ? ' Catatan: ' . implode('; ', array_slice($errors, 0, 5)) : ''));
        }
        redirect('msme/transactions.php');
    }

    if ($action === 'delete_draft') {
        $id = (int) $_POST['id'];
        $chk = $db->prepare('SELECT status FROM transactions WHERE id = ? AND msme_id = ?');
        $chk->execute([$id, $msmeId]);
        if ($chk->fetchColumn() === 'draft') {
            $db->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);
            flash('success', 'Draft transaksi dihapus.');
        } else {
            flash('danger', 'Hanya transaksi berstatus Draft yang dapat dihapus langsung.');
        }
        redirect('msme/transactions.php');
    }
}

// Filters --------------------------------------------------------------
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = 'WHERE msme_id = ?';
$params = [$msmeId];
if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
if ($type) { $where .= ' AND type = ?'; $params[] = $type; }
if ($q) { $where .= ' AND (party_name LIKE ? OR description LIKE ? OR transaction_uid LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
[$page, $totalPages, $offset] = paginate_query($total, $page, 10);

$listStmt = $db->prepare("SELECT * FROM transactions $where ORDER BY id DESC LIMIT 10 OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$pageTitle = 'Transaksi Keuangan';
$activeMenu = 'transactions';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <form class="d-flex flex-wrap gap-2" method="get">
    <input type="text" name="q" class="form-control form-control-sm" style="width:200px" placeholder="Cari pihak/deskripsi/ID" value="<?= e($q) ?>">
    <select name="status" class="form-select form-select-sm" style="width:150px">
      <option value="">Semua Status</option>
      <?php foreach (['draft','pending','validated','rejected','recorded','reversed'] as $s): ?>
      <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="type" class="form-select form-select-sm" style="width:170px">
      <option value="">Semua Jenis</option>
      <?php foreach (TransactionService::TYPES as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-filter"></i></button>
  </form>
  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fa-solid fa-file-csv me-1"></i>Impor CSV</button>
    <button class="btn btn-sm text-white" style="background:var(--fac-primary)" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fa-solid fa-plus me-1"></i>Tambah Transaksi</button>
  </div>
</div>

<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>ID</th><th>Tanggal</th><th>Jenis</th><th>Pihak</th><th>Nominal</th><th>Channel</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8"><?php render_empty_state('fa-inbox', 'Belum ada transaksi. Klik "Tambah Transaksi" untuk memulai.'); ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>" class="small"><?= e($r['transaction_uid']) ?></a></td>
          <td><?= format_date($r['transaction_date']) ?></td>
          <td><?= e(TransactionService::TYPES[$r['type']] ?? $r['type']) ?></td>
          <td><?= e($r['party_name'] ?: '-') ?></td>
          <td><?= format_money($r['amount']) ?></td>
          <td><?= e(TransactionService::CHANNELS[$r['payment_channel']] ?? $r['payment_channel']) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="text-end">
            <a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i></a>
            <?php if ($r['status'] === 'draft'): ?>
            <form method="post" class="d-inline" data-confirm="Hapus draft transaksi ini?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_draft">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3"><?php render_pagination($page, $totalPages, http_build_query(['status'=>$status,'type'=>$type,'q'=>$q])); ?></div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Tambah Transaksi</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Tanggal Transaksi</label><input type="date" name="transaction_date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-6"><label class="form-label">Jenis Transaksi</label>
              <select name="type" class="form-select" required>
                <?php foreach (TransactionService::TYPES as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Pihak Terkait</label><input name="party_name" class="form-control" placeholder="Pelanggan/Pemasok"></div>
            <div class="col-md-6"><label class="form-label">Channel Pembayaran</label>
              <select name="payment_channel" class="form-select" required>
                <?php foreach (TransactionService::CHANNELS as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Nominal (Rp)</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
            <div class="col-md-12"><label class="form-label">Deskripsi</label><input name="description" class="form-control"></div>
          </div>
          <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-gears me-1"></i>Transaksi akan langsung diproses melalui rule engine (kelengkapan, duplikasi, otorisasi, bukti, ambang, klasifikasi).</p>
        </div>
        <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan & Proses</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">
        <div class="modal-header"><h5 class="modal-title">Impor Transaksi via CSV</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p class="small text-muted">Header kolom wajib: <code>transaction_date,type,party_name,description,amount,payment_channel</code></p>
          <input type="file" name="csv_file" accept=".csv" class="form-control" required>
        </div>
        <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Impor</button></div>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
