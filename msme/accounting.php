<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

$accounts = $db->query('SELECT * FROM accounts ORDER BY code')->fetchAll();

$journalStmt = $db->prepare(
    'SELECT j.*, t.transaction_uid, t.type FROM journals j JOIN transactions t ON t.id = j.transaction_id WHERE t.msme_id = ? ORDER BY j.journal_date DESC, j.id DESC LIMIT 100'
);
$journalStmt->execute([$msmeId]);
$journals = $journalStmt->fetchAll();

$ledger = Accounting::ledger($msmeId);
$ledgerByAccount = [];
foreach ($ledger as $l) {
    $ledgerByAccount[$l['account_code']]['name'] = $l['account_name'];
    $ledgerByAccount[$l['account_code']]['lines'][] = $l;
}

$ie = Accounting::incomeExpenseSummary($msmeId);
$cashflow = Accounting::cashFlowSummary($msmeId);

$pageTitle = 'Jurnal & Buku Besar';
$activeMenu = 'accounting';
require __DIR__ . '/../includes/app_header.php';
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#coa">Chart of Accounts</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#journal">Jurnal</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ledger">Buku Besar</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cashflow">Arus Kas &amp; Ringkasan</button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="coa">
    <div class="card fac-card p-3">
      <table class="table table-fac">
        <thead><tr><th>Kode</th><th>Nama Akun</th><th>Tipe</th><th>Saldo Normal</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
          <tr><td><?= e($a['code']) ?></td><td><?= e($a['name']) ?></td><td><?= e(ucfirst($a['type'])) ?></td><td><?= e(ucfirst($a['normal_balance'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="tab-pane fade" id="journal">
    <div class="card fac-card p-3">
      <?php if (!$journals): render_empty_state('fa-book', 'Belum ada jurnal (transaksi belum berstatus Recorded).'); else: ?>
      <table class="table table-fac">
        <thead><tr><th>No. Referensi</th><th>Tanggal</th><th>Transaksi</th><th>Deskripsi</th></tr></thead>
        <tbody>
        <?php foreach ($journals as $j): ?>
          <tr><td><?= e($j['reference_no']) ?></td><td><?= format_date($j['journal_date']) ?></td><td><?= e($j['transaction_uid']) ?></td><td><?= e($j['description']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="tab-pane fade" id="ledger">
    <?php if (!$ledgerByAccount): ?>
      <div class="card fac-card p-3"><?php render_empty_state('fa-scale-balanced', 'Belum ada mutasi buku besar.'); ?></div>
    <?php endif; ?>
    <?php foreach ($ledgerByAccount as $code => $acc): ?>
      <div class="card fac-card p-3 mb-3">
        <h6 class="fw-bold"><?= e($code . ' - ' . $acc['name']) ?></h6>
        <table class="table table-sm">
          <thead><tr><th>Tanggal</th><th>Referensi</th><th>Deskripsi</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
          <tbody>
          <?php $balance = 0; foreach ($acc['lines'] as $l): $balance += $l['debit'] - $l['credit']; ?>
            <tr>
              <td><?= format_date($l['journal_date']) ?></td>
              <td><?= e($l['reference_no']) ?></td>
              <td><?= e($l['journal_desc']) ?></td>
              <td class="text-end"><?= $l['debit'] > 0 ? format_money($l['debit']) : '-' ?></td>
              <td class="text-end"><?= $l['credit'] > 0 ? format_money($l['credit']) : '-' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th colspan="3" class="text-end">Saldo Berjalan</th><th colspan="2" class="text-end"><?= format_money($balance) ?></th></tr></tfoot>
        </table>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="tab-pane fade" id="cashflow">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="fac-stat-card p-3"><div class="fac-stat-value"><?= format_money($ie['total_income'] ?? 0) ?></div><div class="fac-stat-label">Total Pendapatan (Recorded)</div></div>
      </div>
      <div class="col-md-6">
        <div class="fac-stat-card p-3"><div class="fac-stat-value"><?= format_money($ie['total_expense'] ?? 0) ?></div><div class="fac-stat-label">Total Beban (Recorded)</div></div>
      </div>
    </div>
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-2">Arus Kas Bulanan (Estimasi Masuk vs Keluar)</h6>
      <?php if (!$cashflow): render_empty_state('fa-money-bill-trend-up', 'Belum ada data arus kas.'); else: ?>
      <table class="table table-sm">
        <thead><tr><th>Bulan</th><th class="text-end">Arus Masuk</th><th class="text-end">Arus Keluar</th><th class="text-end">Bersih</th></tr></thead>
        <tbody>
        <?php foreach ($cashflow as $c): ?>
          <tr><td><?= e($c['ym']) ?></td><td class="text-end"><?= format_money($c['inflow']) ?></td><td class="text-end"><?= format_money($c['outflow']) ?></td><td class="text-end fw-bold"><?= format_money($c['inflow']-$c['outflow']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
