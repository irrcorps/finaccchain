<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int) $_POST['role_id'];

        if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
            flash('danger', 'Data tidak valid. Pastikan nama, email, dan kata sandi (min. 8 karakter) benar.');
        } else {
            $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                flash('danger', 'Email sudah terdaftar.');
            } else {
                $ins = $db->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)');
                $ins->execute([$roleId, $name, $email, password_hash($password, PASSWORD_BCRYPT)]);
                flash('success', 'Pengguna berhasil ditambahkan.');
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int) $_POST['id'];
        if ($id !== (int) $u['id']) {
            $db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            flash('success', 'Status pengguna diperbarui.');
        } else {
            flash('danger', 'Tidak dapat menonaktifkan akun sendiri.');
        }
    }

    if ($action === 'reset_password') {
        $id = (int) $_POST['id'];
        $newPass = $_POST['new_password'] ?? '';
        if (mb_strlen($newPass) < 8) {
            flash('danger', 'Kata sandi baru minimal 8 karakter.');
        } else {
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPass, PASSWORD_BCRYPT), $id]);
            flash('success', 'Kata sandi pengguna berhasil direset.');
        }
    }

    redirect('admin/users.php');
}

$roles = $db->query('SELECT * FROM roles ORDER BY id')->fetchAll();
$list = $db->query('SELECT u.*, r.name AS role_name, r.code AS role_code FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id DESC')->fetchAll();

$pageTitle = 'Manajemen Pengguna';
$activeMenu = 'users';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="d-flex justify-content-between mb-3">
  <h6 class="fw-bold mb-0">Daftar Pengguna</h6>
  <button class="btn btn-sm text-white" style="background:var(--fac-primary)" data-bs-toggle="modal" data-bs-target="#addUser"><i class="fa-solid fa-plus me-1"></i>Tambah Pengguna</button>
</div>

<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Login Terakhir</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($list as $r): ?>
        <tr>
          <td><?= e($r['name']) ?> <?= $r['is_demo'] ? '<span class="badge text-bg-light text-muted border">demo</span>' : '' ?></td>
          <td><?= e($r['email']) ?></td>
          <td><span class="badge text-bg-secondary"><?= e($r['role_name']) ?></span></td>
          <td><?= $r['is_active'] ? status_badge('active') : status_badge('inactive') ?></td>
          <td class="small text-muted"><?= $r['last_login_at'] ? format_datetime($r['last_login_at']) : '-' ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reset<?= $r['id'] ?>"><i class="fa-solid fa-key"></i></button>
            <form method="post" class="d-inline" data-confirm="Ubah status aktif pengguna ini?">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-<?= $r['is_active']?'danger':'success' ?>"><i class="fa-solid fa-power-off"></i></button>
            </form>
          </td>
        </tr>
        <div class="modal fade" id="reset<?= $r['id'] ?>" tabindex="-1">
          <div class="modal-dialog"><div class="modal-content">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <div class="modal-header"><h6 class="modal-title">Reset Kata Sandi: <?= e($r['name']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body"><input type="password" name="new_password" class="form-control" placeholder="Kata sandi baru (min. 8 karakter)" required minlength="8"></div>
              <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Reset</button></div>
            </form>
          </div></div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addUser" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="modal-header"><h5 class="modal-title">Tambah Pengguna</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Kata Sandi</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="mb-2"><label class="form-label">Peran</label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $role): ?><option value="<?= $role['id'] ?>"><?= e($role['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <p class="small text-muted mb-0">Catatan: pengguna dengan peran UMKM yang dibuat di sini belum memiliki profil usaha - arahkan mereka melengkapi profil usaha setelah login, atau gunakan halaman Registrasi UMKM.</p>
      </div>
      <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan</button></div>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
