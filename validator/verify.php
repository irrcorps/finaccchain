<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('validator');
$u = current_user();
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) $_POST['id'];
    if ($_POST['action'] === 'approve') {
        [$ok, $msg] = RuleEngine::approveByValidator($id, $u['id']);
    } else {
        [$ok, $msg] = RuleEngine::rejectByValidator($id, $u['id'], trim($_POST['reason'] ?? 'Tidak memenuhi syarat validasi.'));
    }
    flash($ok ? 'success' : 'danger', $msg);
    redirect('validator/verify.php');
}

$focusId = (int) ($_GET['id'] ?? 0);

$list = $db->query(
    "SELECT t.*, m.business_name FROM transactions t JOIN msmes m ON m.id = t.msme_id WHERE t.status = 'pending' ORDER BY t.created_at ASC"
)->fetchAll();

$pageTitle = 'Verifikasi Transaksi';
$activeMenu = 'verify';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>UMKM</th><th>ID</th><th>Tanggal</th><th>Jenis</th><th>Nominal</th><th>Channel</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7"><?php render_empty_state('fa-check-double', 'Tidak ada transaksi yang menunggu verifikasi.'); ?></td></tr>
      <?php endif; ?>
      <?php foreach ($list as $r): ?>
        <tr class="<?= $r['id']===$focusId ? 'table-warning' : '' ?>">
          <td><?= e($r['business_name']) ?></td>
          <td><?= e($r['transaction_uid']) ?></td>
          <td><?= format_date($r['transaction_date']) ?></td>
          <td><?= e(TransactionService::TYPES[$r['type']] ?? $r['type']) ?></td>
          <td><?= format_money($r['amount']) ?></td>
          <td><?= e(TransactionService::CHANNELS[$r['payment_channel']] ?? $r['payment_channel']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detail<?= $r['id'] ?>"><i class="fa-solid fa-eye"></i></button>
            <form method="post" class="d-inline" data-confirm="Setujui transaksi ini?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="approve">
              <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
            </form>
            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reject<?= $r['id'] ?>"><i class="fa-solid fa-xmark"></i></button>
          </td>
        </tr>

        <!-- detail modal -->
        <div class="modal fade" id="detail<?= $r['id'] ?>" tabindex="-1">
          <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Detail <?= e($r['transaction_uid']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body small">
              <p><strong>UMKM:</strong> <?= e($r['business_name']) ?></p>
              <p><strong>Pihak:</strong> <?= e($r['party_name'] ?: '-') ?></p>
              <p><strong>Deskripsi:</strong> <?= e($r['description'] ?: '-') ?></p>
              <p><strong>Nominal:</strong> <?= format_money($r['amount']) ?></p>
              <p class="mb-0"><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>" target="_blank">Lihat riwayat validasi & audit trail lengkap &raquo;</a></p>
            </div>
          </div></div>
        </div>

        <!-- reject modal -->
        <div class="modal fade" id="reject<?= $r['id'] ?>" tabindex="-1">
          <div class="modal-dialog"><div class="modal-content">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="reject">
              <div class="modal-header"><h6 class="modal-title">Tolak Transaksi <?= e($r['transaction_uid']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <label class="form-label">Alasan Penolakan</label>
                <textarea name="reason" class="form-control" required></textarea>
              </div>
              <div class="modal-footer"><button class="btn btn-danger">Tolak Transaksi</button></div>
            </form>
          </div></div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
