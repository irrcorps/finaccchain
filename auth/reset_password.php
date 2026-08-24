<?php
require_once __DIR__ . '/../core/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$message = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($password !== $confirm) {
        $message = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        [$success, $message] = Auth::resetPassword($token, $password);
    }
}

$pageTitle = 'Reset Kata Sandi';
require __DIR__ . '/../includes/guest_header.php';
?>
<div class="py-5" style="background:var(--fac-bg); min-height:80vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card fac-card shadow-sm border-0 p-4">
          <div class="text-center mb-3">
            <div class="fac-icon-tile mx-auto mb-2"><i class="fa-solid fa-lock"></i></div>
            <h4 class="fw-bold mb-0">Atur Ulang Kata Sandi</h4>
          </div>

          <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?> small"><?= e($message) ?></div>
          <?php endif; ?>

          <?php if (!$success): ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3">
              <label class="form-label">Kata Sandi Baru</label>
              <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="mb-3">
              <label class="form-label">Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="password_confirm" class="form-control" required minlength="8">
            </div>
            <button class="btn btn-primary w-100" style="background:var(--fac-primary); border-color:var(--fac-primary)">Simpan Kata Sandi</button>
          </form>
          <?php else: ?>
            <a href="<?= base_url('auth/login.php') ?>" class="btn btn-primary w-100" style="background:var(--fac-primary); border-color:var(--fac-primary)">Ke Halaman Masuk</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/guest_footer.php'; ?>
