<?php
require_once __DIR__ . '/../core/bootstrap.php';

$resetLink = null;
$notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $token = Auth::requestPasswordReset($email);
    // Generic message regardless of whether the e-mail exists (prevents user enumeration).
    $notice = 'Jika email terdaftar, tautan reset kata sandi telah dibuat.';
    if ($token) {
        // No mail transport is configured in this research prototype - the
        // reset link is shown directly on screen for demo/testing purposes.
        $resetLink = base_url('auth/reset_password.php?token=' . $token);
    }
}

$pageTitle = 'Lupa Kata Sandi';
require __DIR__ . '/../includes/guest_header.php';
?>
<div class="py-5" style="background:var(--fac-bg); min-height:80vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card fac-card shadow-sm border-0 p-4">
          <div class="text-center mb-3">
            <div class="fac-icon-tile mx-auto mb-2"><i class="fa-solid fa-key"></i></div>
            <h4 class="fw-bold mb-0">Lupa Kata Sandi</h4>
            <p class="text-muted small">Masukkan email akun Anda untuk membuat tautan reset.</p>
          </div>

          <?php if ($notice): ?>
            <div class="alert alert-info small"><?= e($notice) ?></div>
          <?php endif; ?>
          <?php if ($resetLink): ?>
            <div class="alert alert-warning small">
              <strong>Mode demo (tanpa server email):</strong><br>
              Tautan reset: <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
            </div>
          <?php endif; ?>

          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100" style="background:var(--fac-primary); border-color:var(--fac-primary)">Kirim Tautan Reset</button>
            <p class="text-center small text-muted mt-3 mb-0"><a href="<?= base_url('auth/login.php') ?>">Kembali ke halaman masuk</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/guest_footer.php'; ?>
