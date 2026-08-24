<footer class="fac-footer py-5 mt-auto">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <h5 class="text-white"><i class="fa-solid fa-link-slash me-1"></i><?= e(APP_NAME) ?></h5>
        <p class="small mb-1"><?= e(APP_TAGLINE) ?></p>
        <p class="small text-white-50">Prototipe riset Tingkat Kesiapterapan Teknologi (TKT) 3 — model integrasi smart contract berbasis fintech untuk penguatan akuntabilitas keuangan UMKM, studi kasus ekosistem hilirisasi ekonomi digital Kota Medan.</p>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="text-white">Navigasi</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= base_url('index.php#overview') ?>">Ringkasan</a></li>
          <li><a href="<?= base_url('index.php#how-it-works') ?>">Cara Kerja</a></li>
          <li><a href="<?= base_url('index.php#features') ?>">Fitur</a></li>
          <li><a href="<?= base_url('index.php#faq') ?>">FAQ</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="text-white">Akses</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= base_url('auth/login.php') ?>">Masuk</a></li>
          <li><a href="<?= base_url('auth/register.php') ?>">Daftar UMKM</a></li>
          <li><a href="<?= base_url('auth/forgot_password.php') ?>">Lupa Kata Sandi</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="text-white">Disclaimer Riset</h6>
        <p class="small text-white-50 mb-0"><?= e(RESEARCH_DISCLAIMER) ?></p>
      </div>
    </div>
    <hr class="border-secondary my-4">
    <p class="small text-white-50 mb-0 text-center">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Penelitian PDP 2026, Kota Medan. Seluruh data pada mode demo bersifat simulasi.</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
