<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $db->prepare('UPDATE smart_contract_rules SET is_active = 1 - is_active WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Status rule diperbarui.');
    } elseif ($action === 'update_params') {
        $json = trim($_POST['parameters_json'] ?? '{}');
        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            flash('danger', 'Format JSON parameter tidak valid.');
        } else {
            $db->prepare('UPDATE smart_contract_rules SET parameters_json = ?, description = ? WHERE id = ?')
                ->execute([$json, trim($_POST['description']), (int) $_POST['id']]);
            flash('success', 'Parameter rule diperbarui.');
        }
    }
    redirect('admin/rules.php');
}

$rules = $db->query('SELECT * FROM smart_contract_rules ORDER BY sort_order')->fetchAll();

$pageTitle = 'Smart Contract Rule Engine';
$activeMenu = 'rules';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="fac-disclaimer p-3 mb-4">
  <i class="fa-solid fa-circle-info me-1"></i> Aturan berikut adalah konfigurasi rule engine deterministik yang mensimulasikan logika "smart contract" - dijalankan berurutan setiap kali transaksi diproses.
</div>

<div class="row g-3">
<?php foreach ($rules as $r): ?>
  <div class="col-lg-6">
    <div class="card fac-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="badge text-bg-secondary mb-2"><?= $r['sort_order'] ?>. <?= e($r['rule_code']) ?></span>
          <h6 class="fw-bold mb-1"><?= e($r['rule_name']) ?></h6>
        </div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn btn-sm btn-outline-<?= $r['is_active']?'success':'secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></button>
        </form>
      </div>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="update_params"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <label class="form-label small text-muted">Deskripsi</label>
        <textarea name="description" class="form-control form-control-sm mb-2" rows="2"><?= e($r['description']) ?></textarea>
        <label class="form-label small text-muted">Parameter (JSON)</label>
        <textarea name="parameters_json" class="form-control form-control-sm mb-2 font-monospace" rows="3"><?= e($r['parameters_json']) ?></textarea>
        <button class="btn btn-sm btn-outline-primary">Simpan Parameter</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
