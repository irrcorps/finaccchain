<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (is_logged_in()) {
    redirect(current_user()['role_code'] . '/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$ok, $result] = Auth::registerMsme($_POST);
    if ($ok) {
        flash('success', 'Registrasi berhasil! Silakan login menggunakan akun UMKM Anda.');
        redirect('auth/login.php');
    }
    $errors = $result;
    set_old($_POST);
}

$pageTitle = 'Daftar UMKM';
require __DIR__ . '/../includes/guest_header.php';
?>
<div class="py-5" style="background:var(--fac-bg);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card fac-card shadow-sm border-0 p-4">
          <div class="text-center mb-3">
            <div class="fac-icon-tile mx-auto mb-2"><i class="fa-solid fa-shop"></i></div>
            <h4 class="fw-bold mb-0">Registrasi UMKM</h4>
            <p class="text-muted small mb-0">Bergabung sebagai peserta riset prototipe FinAccChain.</p>
            <p class="text-muted small">Akun admin/peneliti dan validator disediakan oleh pengelola riset.</p>
          </div>

          <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div>
          <?php endif; ?>

          <form method="post" novalidate>
            <?= csrf_field() ?>
            <h6 class="text-muted text-uppercase small fw-bold mb-2">Akun</h6>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Nama Pemilik</label>
                <input type="text" name="name" class="form-control" required value="<?= old('name') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= old('email') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Kata Sandi</label>
                <input type="password" name="password" class="form-control" required minlength="8">
              </div>
              <div class="col-md-6">
                <label class="form-label">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirm" class="form-control" required minlength="8">
              </div>
            </div>

            <h6 class="text-muted text-uppercase small fw-bold mb-2">Profil Usaha</h6>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Nama Usaha</label>
                <input type="text" name="business_name" class="form-control" required value="<?= old('business_name') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Sektor Usaha</label>
                <input type="text" name="sector" class="form-control" required placeholder="cth. Kuliner, Konveksi" value="<?= old('sector') ?>">
              </div>
              <div class="col-md-12">
                <label class="form-label">Alamat Usaha</label>
                <input type="text" name="address" class="form-control" value="<?= old('address') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Usia Usaha (tahun)</label>
                <input type="number" min="0" name="business_age_years" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Jumlah Karyawan</label>
                <input type="number" min="0" name="employee_count" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Omzet Bulanan</label>
                <select name="monthly_turnover_category" class="form-select">
                  <option value="<5jt">&lt; Rp5 juta</option>
                  <option value="5-10jt">Rp5 - 10 juta</option>
                  <option value="10-50jt">Rp10 - 50 juta</option>
                  <option value="50-300jt">Rp50 - 300 juta</option>
                  <option value=">300jt">&gt; Rp300 juta</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Penggunaan Pembayaran Digital</label>
                <select name="digital_payment_usage" class="form-select">
                  <option value="none">Belum digunakan</option>
                  <option value="partial" selected>Sebagian</option>
                  <option value="full">Penuh</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Penggunaan Fintech</label>
                <select name="fintech_usage" class="form-select">
                  <option value="none">Belum digunakan</option>
                  <option value="payment_only">Pembayaran saja</option>
                  <option value="payment_financing">Pembayaran & Pembiayaan</option>
                  <option value="full_integration">Terintegrasi penuh</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Metode Akuntansi Saat Ini</label>
                <select name="accounting_method" class="form-select">
                  <option value="manual">Manual (buku catatan)</option>
                  <option value="spreadsheet">Spreadsheet</option>
                  <option value="accounting_app">Aplikasi Akuntansi</option>
                  <option value="none">Belum ada</option>
                </select>
              </div>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" required id="agree">
              <label class="form-check-label small" for="agree">Saya memahami bahwa ini adalah data untuk keperluan prototipe riset (bukan sistem produksi).</label>
            </div>

            <button class="btn btn-primary w-100" style="background:var(--fac-primary); border-color:var(--fac-primary)">Daftar</button>
            <p class="text-center small text-muted mt-3 mb-0">Sudah punya akun? <a href="<?= base_url('auth/login.php') ?>">Masuk di sini</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/guest_footer.php'; ?>
