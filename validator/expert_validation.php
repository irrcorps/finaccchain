<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('validator');
$u = current_user();
$db = Database::connection();

$criteria = [
    'relevance' => 'Relevansi model terhadap kebutuhan akuntabilitas UMKM',
    'clarity' => 'Kejelasan alur proses (rule engine & hash-chain)',
    'feasibility' => 'Kelayakan implementasi',
    'accounting_adequacy' => 'Kecukupan modul akuntansi',
    'technological_adequacy' => 'Kecukupan teknologi yang digunakan',
    'fintech_integration' => 'Integrasi simulasi fintech',
    'smart_contract_logic' => 'Logika rule engine "smart contract"',
    'accountability_contribution' => 'Kontribusi terhadap akuntabilitas keuangan',
    'usefulness' => 'Kebermanfaatan keseluruhan sistem',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cols = array_keys($criteria);
    $values = [$u['id'], $_POST['msme_id'] ?: null];
    foreach ($cols as $c) {
        $v = (int) ($_POST[$c] ?? 0);
        if ($v < 1 || $v > 5) { flash('danger', 'Semua skor harus antara 1-5.'); redirect('validator/expert_validation.php'); }
        $values[] = $v;
    }
    $values[] = trim($_POST['comments'] ?? '');

    $stmt = $db->prepare(
        'INSERT INTO expert_validations (expert_user_id, msme_id, ' . implode(',', $cols) . ', comments)
         VALUES (?, ?, ' . implode(',', array_fill(0, count($cols), '?')) . ', ?)'
    );
    $stmt->execute($values);
    flash('success', 'Penilaian ahli berhasil disimpan.');
    redirect('validator/expert_validation.php');
}

$msmes = $db->query('SELECT id, business_name FROM msmes ORDER BY business_name')->fetchAll();
$myList = $db->prepare('SELECT ev.*, m.business_name FROM expert_validations ev LEFT JOIN msmes m ON m.id = ev.msme_id WHERE ev.expert_user_id = ? ORDER BY ev.id DESC');
$myList->execute([$u['id']]);
$mine = $myList->fetchAll();

$pageTitle = 'Expert Judgment';
$activeMenu = 'expert';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Formulir Penilaian Ahli (Skala 1-5)</h6>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">UMKM (opsional - kosongkan untuk penilaian model secara umum)</label>
          <select name="msme_id" class="form-select">
            <option value="">Model Umum</option>
            <?php foreach ($msmes as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['business_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php foreach ($criteria as $key => $label): ?>
        <div class="mb-2">
          <label class="form-label small mb-1"><?= e($label) ?></label>
          <select name="<?= $key ?>" class="form-select form-select-sm" required>
            <option value="">Pilih skor</option>
            <?php for ($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
          </select>
        </div>
        <?php endforeach; ?>
        <div class="mb-3 mt-2">
          <label class="form-label">Komentar/Catatan</label>
          <textarea name="comments" class="form-control" rows="3"></textarea>
        </div>
        <button class="btn text-white w-100" style="background:var(--fac-primary)">Simpan Penilaian</button>
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Riwayat Penilaian Saya</h6>
      <?php if (!$mine): render_empty_state('fa-star-half-stroke', 'Belum ada penilaian yang disimpan.'); else: ?>
      <?php foreach ($mine as $r): ?>
        <div class="border rounded-3 p-3 mb-2">
          <div class="d-flex justify-content-between">
            <strong><?= e($r['business_name'] ?? 'Model Umum') ?></strong>
            <span class="text-muted small"><?= format_datetime($r['created_at']) ?></span>
          </div>
          <div class="small text-muted mt-1">
            Rata-rata: <?php
              $avg = array_sum(array_map(fn($k) => (int) $r[$k], array_keys($criteria))) / count($criteria);
              echo number_format($avg, 2);
            ?> / 5
          </div>
          <?php if ($r['comments']): ?><p class="small mb-0 mt-1"><?= e($r['comments']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
