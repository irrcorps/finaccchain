<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $db->prepare('UPDATE research_settings SET setting_value = ? WHERE setting_key = ?');
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        $stmt->execute([trim($value), $key]);
    }
    flash('success', 'Pengaturan formula riset berhasil diperbarui.');
    redirect('admin/settings.php');
}

$settings = $db->query('SELECT * FROM research_settings ORDER BY id')->fetchAll();
$weightKeys = array_filter($settings, fn($s) => strpos($s['setting_key'], 'accountability_weight_') === 0);
$weightSum = array_sum(array_map(fn($s) => (float) $s['setting_value'], $weightKeys));
$others = array_filter($settings, fn($s) => strpos($s['setting_key'], 'accountability_weight_') !== 0);

$pageTitle = 'Pengaturan Formula Riset';
$activeMenu = 'settings';
require __DIR__ . '/../includes/app_header.php';
?>
<form method="post">
  <?= csrf_field() ?>
  <div class="card fac-card p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0">Bobot Indikator Akuntabilitas (%)</h6>
      <span class="badge <?= abs($weightSum-100)<0.01 ? 'text-bg-success' : 'text-bg-warning' ?>">Total: <?= $weightSum ?>%</span>
    </div>
    <?php if (abs($weightSum-100) >= 0.01): ?>
      <div class="alert alert-warning small">Total bobot idealnya 100%. Sesuaikan agar perhitungan indeks proporsional.</div>
    <?php endif; ?>
    <div class="row g-3">
      <?php foreach ($weightKeys as $s): ?>
        <div class="col-md-3">
          <label class="form-label small"><?= e(str_replace('accountability_weight_', '', $s['setting_key'])) ?></label>
          <input type="number" step="0.1" name="settings[<?= e($s['setting_key']) ?>]" class="form-control form-control-sm" value="<?= e($s['setting_value']) ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card fac-card p-4 mb-3">
    <h6 class="fw-bold mb-3">Ambang &amp; Parameter Lain</h6>
    <div class="row g-3">
      <?php foreach ($others as $s): ?>
        <div class="col-md-6">
          <label class="form-label small"><?= e($s['setting_key']) ?></label>
          <textarea name="settings[<?= e($s['setting_key']) ?>]" class="form-control form-control-sm" rows="2"><?= e($s['setting_value']) ?></textarea>
          <div class="form-text"><?= e($s['description']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <button class="btn text-white" style="background:var(--fac-primary)">Simpan Pengaturan</button>
</form>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
