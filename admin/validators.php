<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$validatorRoleId = (int) $db->query("SELECT id FROM roles WHERE code='validator'")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
            flash('danger', 'Data tidak valid.');
        } else {
            $chk = $db->prepare('SELECT id FROM users WHERE email=?'); $chk->execute([$email]);
            if ($chk->fetch()) { flash('danger', 'Email sudah terdaftar.'); }
            else {
                $db->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)')
                    ->execute([$validatorRoleId, $name, $email, password_hash($password, PASSWORD_BCRYPT)]);
                flash('success', 'Akun validator/auditor berhasil ditambahkan.');
            }
        }
    } elseif ($action === 'toggle') {
        $db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role_id = ?')->execute([(int) $_POST['id'], $validatorRoleId]);
        flash('success', 'Status validator diperbarui.');
    }
    redirect('admin/validators.php');
}

$list = $db->prepare('SELECT * FROM users WHERE role_id = ? ORDER BY id DESC');
$list->execute([$validatorRoleId]);
$validators = $list->fetchAll();

$pageTitle = 'Manajemen Validator';
$activeMenu = 'validators';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="d-flex justify-content-between mb-3">
  <h6 class="fw-bold mb-0">Daftar Validator/Auditor</h6>
  <button class="btn btn-sm text-white" style="background:var(--fac-primary)" data-bs-toggle="modal" data-bs-target="#addV"><i class="fa-solid fa-plus me-1"></i>Tambah Validator</button>
</div>
<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>Nama</th><th>Email</th><th>Status</th><th>Login Terakhir</th><th></th></tr></thead>
      <tbody>
      <?php if (!$validators): ?><tr><td colspan="5"><?php render_empty_state('fa-user-shield','Belum ada akun validator.'); ?></td></tr><?php endif; ?>
      <?php foreach ($validators as $v): ?>
        <tr>
          <td><?= e($v['name']) ?> <?= $v['is_demo'] ? '<span class="badge text-bg-light text-muted border">demo</span>' : '' ?></td>
          <td><?= e($v['email']) ?></td>
          <td><?= $v['is_active'] ? status_badge('active') : status_badge('inactive') ?></td>
          <td class="small text-muted"><?= $v['last_login_at'] ? format_datetime($v['last_login_at']) : '-' ?></td>
          <td>
            <form method="post" data-confirm="Ubah status validator ini?">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $v['id'] ?>">
              <button class="btn btn-sm btn-outline-<?= $v['is_active']?'danger':'success' ?>"><i class="fa-solid fa-power-off"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="addV" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="modal-header"><h5 class="modal-title">Tambah Validator/Auditor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Kata Sandi</label><input type="password" name="password" class="form-control" required minlength="8"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan</button></div>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
