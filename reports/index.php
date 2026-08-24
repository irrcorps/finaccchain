<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role(['admin', 'msme', 'validator']);
$u = current_user();
$db = Database::connection();

$msmes = $u['role_code'] === 'msme'
    ? []
    : $db->query('SELECT id, business_name FROM msmes ORDER BY business_name')->fetchAll();

$myMsmeId = $u['role_code'] === 'msme' ? (int) $u['msme_id'] : null;

$perMsmeReports = [
    'msme_profile' => ['fa-building', '1. Profil UMKM'],
    'transactions' => ['fa-right-left', '2. Laporan Transaksi'],
    'journal_ledger' => ['fa-book', '3. Jurnal & Buku Besar'],
    'audit_trail' => ['fa-magnifying-glass-chart', '4. Audit Trail'],
    'transaction_verification' => ['fa-check-double', '5. Verifikasi Transaksi'],
    'accountability' => ['fa-shield-halved', '6. Penilaian Akuntabilitas Keuangan'],
];
$globalReports = [
    'expert_validation' => ['fa-user-graduate', '7. Hasil Validasi Ahli'],
    'research_summary' => ['fa-file-lines', '8. Ringkasan Riset'],
    'astobe' => ['fa-code-compare', '9. Ringkasan AS-IS/TO-BE'],
];

$pageTitle = 'Laporan';
$activeMenu = 'reports';
require __DIR__ . '/../includes/app_header.php';
?>
<?php if ($u['role_code'] !== 'msme'): ?>
<div class="mb-3">
  <label class="form-label small">Pilih UMKM untuk laporan #1-#6</label>
  <select id="msmeSelect" class="form-select" style="max-width:400px">
    <?php foreach ($msmes as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['business_name']) ?></option><?php endforeach; ?>
  </select>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
<?php foreach ($perMsmeReports as $type => $info): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card fac-card p-4 h-100 d-flex flex-column">
      <div class="fac-icon-tile mb-3"><i class="fa-solid <?= $info[0] ?>"></i></div>
      <h6 class="fw-bold"><?= $info[1] ?></h6>
      <a class="btn btn-sm btn-outline-primary mt-auto report-link" data-type="<?= $type ?>"
         href="<?= base_url('reports/generate.php?type=' . $type . ($myMsmeId ? '&msme_id=' . $myMsmeId : '')) ?>" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i>Cetak PDF
      </a>
    </div>
  </div>
<?php endforeach; ?>
</div>

<h6 class="fw-bold mb-3 text-muted text-uppercase small">Laporan Lintas-UMKM / Model</h6>
<div class="row g-3">
<?php foreach ($globalReports as $type => $info): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card fac-card p-4 h-100 d-flex flex-column">
      <div class="fac-icon-tile mb-3"><i class="fa-solid <?= $info[0] ?>"></i></div>
      <h6 class="fw-bold"><?= $info[1] ?></h6>
      <a class="btn btn-sm btn-outline-primary mt-auto" href="<?= base_url('reports/generate.php?type=' . $type) ?>" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>Cetak PDF</a>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php if ($u['role_code'] !== 'msme'): ?>
<script>
document.getElementById('msmeSelect').addEventListener('change', function () {
  document.querySelectorAll('.report-link').forEach(function (a) {
    var type = a.dataset.type;
    a.href = '<?= base_url('reports/generate.php') ?>?type=' + type + '&msme_id=' + this.value;
  }, this);
});
document.getElementById('msmeSelect').dispatchEvent(new Event('change'));
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
