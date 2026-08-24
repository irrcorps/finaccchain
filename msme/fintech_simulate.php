<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

$lastResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $required = ['type', 'amount', 'channel'];
    $missing = array_filter($required, fn($f) => empty($_POST[$f]));
    if ($missing) {
        flash('danger', 'Lengkapi semua field wajib.');
        redirect('msme/fintech_simulate.php');
    }
    $lastResult = TransactionService::createFintechSimulated($msmeId, $u['id'], $_POST);
    flash($lastResult['engine']['ok'] ? 'success' : 'warning', 'Transaksi fintech disimulasikan (Ref: ' . $lastResult['reference_id'] . '). ' . implode(' | ', $lastResult['engine']['log']));
    redirect('msme/transaction_detail.php?id=' . $lastResult['transaction_id']);
}

$fintechChannels = [
    'bank_transfer' => ['icon' => 'fa-building-columns', 'label' => 'Transfer Bank'],
    'qr_payment' => ['icon' => 'fa-qrcode', 'label' => 'QRIS / Payment Gateway'],
    'e_wallet' => ['icon' => 'fa-wallet', 'label' => 'E-Wallet'],
    'digital_financing' => ['icon' => 'fa-hand-holding-dollar', 'label' => 'Pembiayaan Digital'],
];

$recentStmt = $db->prepare(
    'SELECT t.*, ft.channel, ft.reference_id, ft.payment_status FROM fintech_transactions ft
     JOIN transactions t ON t.id = ft.transaction_id
     WHERE t.msme_id = ? ORDER BY ft.id DESC LIMIT 10'
);
$recentStmt->execute([$msmeId]);
$recent = $recentStmt->fetchAll();

$pageTitle = 'Simulasi Transaksi Fintech';
$activeMenu = 'fintech';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="fac-disclaimer p-3 mb-4">
  <i class="fa-solid fa-triangle-exclamation me-1"></i> Kanal pembayaran di bawah ini bersifat SIMULASI untuk keperluan riset dan tidak terhubung ke penyedia layanan fintech sungguhan.
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Buat Simulasi Transaksi Fintech</h6>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Kanal Fintech</label>
          <div class="row g-2">
            <?php foreach ($fintechChannels as $k => $c): ?>
            <div class="col-6">
              <input type="radio" class="btn-check" name="channel" value="<?= $k ?>" id="ch_<?= $k ?>" required <?= $k==='qr_payment'?'checked':'' ?>>
              <label class="btn btn-outline-secondary w-100 text-start" for="ch_<?= $k ?>"><i class="fa-solid <?= $c['icon'] ?> me-2"></i><?= $c['label'] ?></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Jenis Transaksi</label>
          <select name="type" class="form-select" required>
            <?php foreach (TransactionService::TYPES as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Nominal (Rp)</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Pihak Terkait</label>
          <input name="party_name" class="form-control" placeholder="Pelanggan/Mitra/Penyedia pembiayaan">
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <input name="description" class="form-control">
        </div>
        <button class="btn text-white w-100" style="background:var(--fac-primary)"><i class="fa-solid fa-bolt me-1"></i>Jalankan Simulasi</button>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Transaksi Fintech Terbaru</h6>
      <?php if (!$recent): render_empty_state('fa-mobile-screen-button', 'Belum ada transaksi fintech disimulasikan.'); else: ?>
      <div class="table-responsive">
      <table class="table table-fac table-sm">
        <thead><tr><th>Referensi</th><th>Kanal</th><th>Nominal</th><th>Status Bayar</th><th>Status Transaksi</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td class="hash-chip"><?= e($r['reference_id']) ?></td>
            <td><?= e($fintechChannels[$r['channel']]['label'] ?? $r['channel']) ?></td>
            <td><?= format_money($r['amount']) ?></td>
            <td><?= status_badge($r['payment_status']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><a href="<?= base_url('msme/transaction_detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
