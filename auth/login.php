<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (is_logged_in()) {
    $roleCode = current_user()['role_code'];
    redirect($roleCode . '/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email dan kata sandi wajib diisi.';
    } else {
        $err = Auth::attempt($email, $password);
        if ($err) {
            $errors[] = $err;
        } else {
            flash('success', 'Selamat datang kembali, ' . current_user()['name'] . '!');
            redirect(current_user()['role_code'] . '/dashboard.php');
        }
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/../includes/guest_header.php';
?>
<div class="d-flex align-items-center" style="min-height:80vh; background:var(--fac-bg);">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card fac-card shadow-sm border-0 p-4">
          <div class="text-center mb-3">
            <div class="fac-icon-tile mx-auto mb-2"><i class="fa-solid fa-right-to-bracket"></i></div>
            <h4 class="fw-bold mb-0">Masuk ke <?= e(APP_NAME) ?></h4>
            <p class="text-muted small">Gunakan akun Admin/Peneliti, UMKM, atau Validator.</p>
          </div>

          <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
          <?php endforeach; ?>
          <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div>
          <?php endif; ?>

          <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required value="<?= old('email') ?>" placeholder="nama@contoh.com">
            </div>
            <div class="mb-3">
              <label class="form-label">Kata Sandi</label>
              <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <div class="d-flex justify-content-between mb-3 small">
              <a href="<?= base_url('auth/forgot_password.php') ?>">Lupa kata sandi?</a>
              <a href="<?= base_url('auth/register.php') ?>">Belum punya akun UMKM?</a>
            </div>
            <button class="btn btn-primary w-100" style="background:var(--fac-primary); border-color:var(--fac-primary)">Masuk</button>
          </form>

          <hr>
          <div class="small text-muted">
            <strong>Akun demo</strong> (password: <code>Demo@12345</code>)<br>
            Admin: admin@finaccchain.demo<br>
            UMKM: umkm1@finaccchain.demo<br>
            Validator: validator@finaccchain.demo
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/guest_footer.php'; ?>
