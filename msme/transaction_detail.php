<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role(['msme', 'admin', 'validator']);
$u = current_user();
$msmeId = (int) ($u['msme_id'] ?? 0);
$canEdit = $u['role_code'] === 'msme' || $u['role_code'] === 'admin';
$db = Database::connection();

$id = (int) ($_GET['id'] ?? 0);
$sql = 'SELECT t.*, da.code AS debit_code, da.name AS debit_name, ca.code AS credit_code, ca.name AS credit_name
    FROM transactions t
    LEFT JOIN accounts da ON da.id = t.debit_account_id
    LEFT JOIN accounts ca ON ca.id = t.credit_account_id
    WHERE t.id = ?';
$params = [$id];
if ($u['role_code'] === 'msme') { $sql .= ' AND t.msme_id = ?'; $params[] = $msmeId; }
$stmt = $db->prepare($sql);
$stmt->execute($params);
$trx = $stmt->fetch();
if (!$trx) { http_response_code(404); die('Transaksi tidak ditemukan.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canEdit) { http_response_code(403); die('Validator tidak memiliki izin untuk mengubah transaksi ini.'); }
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_evidence' && !empty($_FILES['evidence'])) {
        [$ok, $msg] = TransactionService::attachEvidence($id, $u['id'], $_FILES['evidence']);
        flash($ok ? 'success' : 'danger', $msg);
        redirect('msme/transaction_detail.php?id=' . $id);
    }

    if ($action === 'resubmit') {
        $result = RuleEngine::process($id, $u['id']);
        flash($result['ok'] ? 'success' : 'warning', 'Diproses ulang: ' . implode(' | ', $result['log']));
        redirect('msme/transaction_detail.php?id=' . $id);
    }

    if ($action === 'reverse') {
        [$ok, $msg] = RuleEngine::reverse($id, $u['id'], trim($_POST['reason'] ?? 'Koreksi data'));
        flash($ok ? 'success' : 'danger', $msg);
        redirect('msme/transaction_detail.php?id=' . $id);
    }
}

$evStmt = $db->prepare('SELECT * FROM transaction_evidence WHERE transaction_id = ? ORDER BY id DESC');
$evStmt->execute([$id]);
$evidence = $evStmt->fetchAll();

$valStmt = $db->prepare('SELECT tv.*, r.rule_name FROM transaction_validations tv JOIN smart_contract_rules r ON r.id = tv.rule_id WHERE tv.transaction_id = ? ORDER BY tv.id ASC');
$valStmt->execute([$id]);
$validations = $valStmt->fetchAll();

$fintechStmt = $db->prepare('SELECT * FROM fintech_transactions WHERE transaction_id = ?');
$fintechStmt->execute([$id]);
$fintech = $fintechStmt->fetch();

$journalStmt = $db->prepare('SELECT j.*, jd.debit, jd.credit, a.code, a.name FROM journals j JOIN journal_details jd ON jd.journal_id = j.id JOIN accounts a ON a.id = jd.account_id WHERE j.transaction_id = ? ORDER BY j.id, jd.id');
$journalStmt->execute([$id]);
$journalLines = $journalStmt->fetchAll();

$trail = HashChain::trailFor($id);

$pageTitle = 'Detail Transaksi ' . $trx['transaction_uid'];
$activeMenu = 'transactions';
require __DIR__ . '/../includes/app_header.php';
?>
<a href="<?= base_url('msme/transactions.php') ?>" class="small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke daftar transaksi</a>

<div class="row g-3 mt-1">
  <div class="col-lg-7">
    <div class="card fac-card p-4 mb-3">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h5 class="fw-bold mb-0"><?= e($trx['transaction_uid']) ?></h5>
          <span class="text-muted small"><?= e(TransactionService::TYPES[$trx['type']] ?? $trx['type']) ?> · <?= format_date($trx['transaction_date']) ?></span>
        </div>
        <?= status_badge($trx['status']) ?>
      </div>
      <table class="table table-sm">
        <tr><th class="text-muted" style="width:40%">Nominal</th><td class="fw-bold"><?= format_money($trx['amount']) ?></td></tr>
        <tr><th class="text-muted">Pihak Terkait</th><td><?= e($trx['party_name'] ?: '-') ?></td></tr>
        <tr><th class="text-muted">Channel Pembayaran</th><td><?= e(TransactionService::CHANNELS[$trx['payment_channel']] ?? $trx['payment_channel']) ?></td></tr>
        <tr><th class="text-muted">Deskripsi</th><td><?= e($trx['description'] ?: '-') ?></td></tr>
        <tr><th class="text-muted">Klasifikasi Akun</th><td><?= $trx['debit_code'] ? e("Debit {$trx['debit_code']} ({$trx['debit_name']}) / Kredit {$trx['credit_code']} ({$trx['credit_name']})") : '-' ?></td></tr>
        <?php if ($trx['rejected_reason']): ?><tr><th class="text-muted">Catatan</th><td class="text-danger"><?= e($trx['rejected_reason']) ?></td></tr><?php endif; ?>
      </table>

      <?php if ($fintech): ?>
        <div class="alert alert-light border small mb-0">
          <strong><i class="fa-solid fa-mobile-screen-button me-1"></i>Metadata Transaksi Fintech (Simulasi)</strong><br>
          Referensi: <span class="hash-chip"><?= e($fintech['reference_id']) ?></span> · Status: <?= status_badge($fintech['payment_status']) ?> · <?= format_datetime($fintech['created_at']) ?>
        </div>
      <?php endif; ?>

      <?php if ($canEdit): ?>
      <div class="d-flex gap-2 mt-3">
        <?php if ($trx['status'] === 'draft'): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resubmit">
            <button class="btn btn-sm text-white" style="background:var(--fac-primary)"><i class="fa-solid fa-rotate me-1"></i>Proses ke Rule Engine</button>
          </form>
        <?php endif; ?>
        <?php if ($trx['status'] === 'recorded'): ?>
          <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reverseModal"><i class="fa-solid fa-rotate-left me-1"></i>Buat Pembalikan/Koreksi</button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="card fac-card p-4 mb-3">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-paperclip me-1"></i>Bukti Transaksi</h6>
      <?php if ($evidence): foreach ($evidence as $ev): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div><i class="fa-solid fa-file me-2 text-muted"></i><a href="<?= base_url($ev['file_path']) ?>" target="_blank"><?= e($ev['original_name']) ?></a></div>
          <span class="small text-muted"><?= format_datetime($ev['uploaded_at']) ?></span>
        </div>
      <?php endforeach; else: render_empty_state('fa-paperclip', 'Belum ada bukti transaksi.'); endif; ?>
      <?php if ($canEdit): ?>
      <form method="post" enctype="multipart/form-data" class="d-flex gap-2 mt-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_evidence">
        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" class="form-control form-control-sm" required>
        <button class="btn btn-sm btn-outline-secondary text-nowrap">Unggah</button>
      </form>
      <?php endif; ?>
    </div>

    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-book me-1"></i>Entri Jurnal</h6>
      <?php if ($journalLines): ?>
      <table class="table table-sm">
        <thead><tr><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
        <tbody>
        <?php foreach ($journalLines as $jl): ?>
          <tr><td><?= e($jl['code'] . ' - ' . $jl['name']) ?></td><td class="text-end"><?= $jl['debit'] > 0 ? format_money($jl['debit']) : '-' ?></td><td class="text-end"><?= $jl['credit'] > 0 ? format_money($jl['credit']) : '-' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: render_empty_state('fa-book', 'Jurnal belum diposting (transaksi belum berstatus Recorded).'); endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card fac-card p-4 mb-3">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-gears me-1"></i>Riwayat Validasi Rule Engine</h6>
      <?php if ($validations): ?>
      <ol class="small ps-3">
        <?php foreach ($validations as $v): ?>
          <li class="mb-2">
            <?= status_badge($v['result']) ?> <strong><?= e($v['rule_name']) ?></strong><br>
            <span class="text-muted"><?= e($v['notes']) ?> · <?= format_datetime($v['validated_at']) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php else: render_empty_state('fa-gears', 'Belum diproses rule engine.'); endif; ?>
    </div>

    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-link me-1"></i>Audit Trail (Hash Chain)</h6>
      <?php if ($trail): foreach ($trail as $t): ?>
        <div class="border-bottom py-2 small">
          <div class="d-flex justify-content-between"><strong><?= e(ucwords(str_replace('_',' ',$t['action']))) ?></strong><span class="text-muted"><?= format_datetime($t['created_at']) ?></span></div>
          <div class="text-muted">Aktor: <?= e($t['actor_name'] ?? 'sistem') ?></div>
          <div>Hash: <span class="hash-chip"><?= substr($t['current_hash'],0,16) ?>…</span></div>
          <div>Prev: <span class="hash-chip"><?= substr($t['previous_hash'],0,16) ?>…</span></div>
        </div>
      <?php endforeach; else: render_empty_state('fa-link', 'Belum ada entri audit trail.'); endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="reverseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reverse">
        <div class="modal-header"><h5 class="modal-title">Buat Pembalikan/Koreksi</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p class="small text-muted">Transaksi tervalidasi tidak dihapus langsung. Sistem akan membuat entri penyesuaian (reversal) baru dan menandai transaksi ini sebagai <em>Reversed</em>.</p>
          <label class="form-label">Alasan Koreksi</label>
          <textarea name="reason" class="form-control" required></textarea>
        </div>
        <div class="modal-footer"><button class="btn btn-danger">Buat Pembalikan</button></div>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
