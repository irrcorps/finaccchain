<?php
require_once __DIR__ . '/core/bootstrap.php';
if (is_logged_in()) {
    redirect(current_user()['role_code'] . '/dashboard.php');
}
$pageTitle = 'Beranda';
require __DIR__ . '/includes/guest_header.php';
?>

<!-- HERO -->
<section class="fac-hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge badge-soft rounded-pill px-3 py-2 mb-3"><i class="fa-solid fa-flask me-1"></i> Prototipe Riset · TKT 3</span>
        <h1 class="fw-bold display-6 mb-3">Penguatan Akuntabilitas Keuangan UMKM melalui Integrasi Smart Contract &amp; Fintech</h1>
        <p class="lead" style="color:rgba(255,255,255,.85)">FinAccChain mendemonstrasikan bagaimana transaksi fintech UMKM dapat divalidasi oleh rule engine deterministik, dicatat secara akuntansi otomatis, dan dijaga integritasnya lewat simulasi rantai hash — mendukung ekosistem hilirisasi ekonomi digital di Kota Medan.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="<?= base_url('auth/register.php') ?>" class="btn btn-lg text-white" style="background:var(--fac-primary)"><i class="fa-solid fa-shop me-2"></i>Daftar sebagai UMKM</a>
          <a href="<?= base_url('auth/login.php') ?>" class="btn btn-lg btn-outline-light"><i class="fa-solid fa-right-to-bracket me-2"></i>Masuk</a>
        </div>
        <p class="small mt-3" style="color:rgba(255,255,255,.6)"><i class="fa-solid fa-circle-info me-1"></i><?= e(RESEARCH_DISCLAIMER) ?></p>
      </div>
      <div class="col-lg-5">
        <div class="card border-0 shadow-lg" style="border-radius:18px;">
          <div class="card-body p-4">
            <h6 class="fw-bold text-muted text-uppercase small">Alur Model Singkat</h6>
            <ol class="small ps-3 mb-0">
              <li class="mb-2">Transaksi fintech UMKM disimulasikan (transfer bank / QR / e-wallet / pembiayaan digital)</li>
              <li class="mb-2">Rule engine memvalidasi kelengkapan, duplikasi, otorisasi, bukti, dan ambang nominal</li>
              <li class="mb-2">Klasifikasi akuntansi otomatis &amp; jurnal tercatat</li>
              <li class="mb-2">Hash transaksi &amp; audit trail tersimpan berantai (append-only)</li>
              <li>Indeks akuntabilitas keuangan diperbarui pada dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- OVERVIEW -->
<section class="fac-section" id="overview">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <p class="fac-eyebrow">Ringkasan Produk &amp; Riset</p>
        <h2 class="fac-section-title mb-3">Model Integrasi Smart Contract Berbasis Fintech</h2>
        <p class="text-muted">Penelitian ini merancang dan menguji model integrasi antara layanan fintech dan mekanisme rule-based "smart contract" untuk memperkuat akuntabilitas keuangan UMKM — sebagai bagian dari penguatan ekosistem hilirisasi ekonomi digital di Kota Medan. FinAccChain adalah purwarupa fungsional (functional research prototype) yang mendemonstrasikan model tersebut secara end-to-end.</p>
        <ul class="list-unstyled small text-muted">
          <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Bukan sistem keuangan produksi — untuk demonstrasi &amp; pengujian model riset.</li>
          <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Rule engine deterministik menggantikan smart contract on-chain.</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>Rantai hash SHA-256 mensimulasikan sifat tamper-evident buku besar terdistribusi.</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="card fac-card p-4">
          <h6 class="fw-bold text-uppercase small text-muted mb-3">Masalah yang Diangkat</h6>
          <div class="d-flex gap-3 mb-3">
            <div class="fac-icon-tile"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
              <strong>Rendahnya akuntabilitas pencatatan keuangan UMKM</strong>
              <p class="small text-muted mb-0">Sebagian besar UMKM belum memiliki mekanisme pencatatan yang lengkap, akurat, transparan, dan mudah ditelusuri.</p>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <div class="fac-icon-tile"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
              <strong>Minimnya jejak audit yang dapat dipercaya</strong>
              <p class="small text-muted mb-0">Perubahan data rawan tanpa mekanisme audit trail yang konsisten dan sulit dimanipulasi.</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="fac-icon-tile"><i class="fa-solid fa-plug-circle-bolt"></i></div>
            <div>
              <strong>Adopsi fintech belum terhubung ke akuntansi</strong>
              <p class="small text-muted mb-0">Transaksi fintech (QRIS, e-wallet, pembiayaan digital) jarang terintegrasi otomatis ke pencatatan akuntansi UMKM.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="fac-section bg-white" id="how-it-works">
  <div class="container">
    <div class="text-center mb-5">
      <p class="fac-eyebrow">Cara Kerja</p>
      <h2 class="fac-section-title">Alur Model FinAccChain</h2>
      <p class="text-muted mx-auto" style="max-width:640px">Simulasi rule engine ini merepresentasikan logika smart contract secara konseptual dan deterministik.</p>
    </div>
    <div class="row g-4">
      <?php
      $steps = [
        ['Transaksi Diajukan', 'UMKM mencatat transaksi manual, impor CSV, atau simulasi transaksi fintech.'],
        ['Cek Kelengkapan Data', 'Rule engine memastikan field wajib terisi (tanggal, jenis, nominal, channel).'],
        ['Cek Duplikasi &amp; Otorisasi', 'Mendeteksi potensi duplikasi dan memverifikasi hak akses pengguna.'],
        ['Cek Bukti &amp; Ambang Nominal', 'Bukti transaksi diverifikasi; nominal besar ditandai untuk validator.'],
        ['Klasifikasi Akuntansi', 'Debit/kredit akun ditentukan otomatis sesuai jenis transaksi.'],
        ['Persetujuan Validator (jika perlu)', 'Transaksi berisiko/nilai besar menunggu keputusan validator/auditor.'],
        ['Hash &amp; Jurnal Tercatat', 'Hash transaksi dibuat, entri jurnal diposting ke buku besar.'],
        ['Audit Trail &amp; Dashboard', 'Rantai audit trail bertambah; skor akuntabilitas diperbarui.'],
      ];
      foreach ($steps as $i => $s): ?>
        <div class="col-md-6 col-lg-3">
          <div class="d-flex gap-3">
            <div class="fac-step-num"><?= $i + 1 ?></div>
            <div>
              <h6 class="fw-bold mb-1"><?= $s[0] ?></h6>
              <p class="small text-muted"><?= $s[1] ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="fac-section" id="features">
  <div class="container">
    <div class="text-center mb-5">
      <p class="fac-eyebrow">Fitur Utama</p>
      <h2 class="fac-section-title">Semua yang Dibutuhkan untuk Mendemonstrasikan Model</h2>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['fa-shop', 'Profil &amp; Onboarding UMKM', 'Pencatatan profil usaha lengkap: sektor, omzet, penggunaan digital payment dan fintech.'],
        ['fa-right-left', 'Transaksi Keuangan Multi-Jenis', 'Penjualan, pembelian, beban, piutang, utang, modal, pembiayaan, dan lainnya.'],
        ['fa-mobile-screen-button', 'Simulasi Kanal Fintech', 'Transfer bank, QRIS, e-wallet, dan pembiayaan digital — tanpa terhubung ke API nyata.'],
        ['fa-gears', 'Rule Engine "Smart Contract"', 'Pipeline validasi deterministik: kelengkapan, duplikasi, otorisasi, bukti, ambang, klasifikasi.'],
        ['fa-link', 'Hash-Chain Audit Trail', 'Rantai SHA-256 append-only untuk menjaga jejak audit tetap tamper-evident.'],
        ['fa-book', 'Modul Akuntansi Ringkas', 'Chart of accounts, jurnal, buku besar, dan ringkasan arus kas otomatis.'],
        ['fa-shield-halved', 'Indeks Akuntabilitas Keuangan', '8 indikator riset dengan bobot yang dapat dikonfigurasi peneliti.'],
        ['fa-user-graduate', 'Validasi Ahli (Expert Judgment)', 'Penilaian relevansi, kelayakan, dan kontribusi model oleh pakar.'],
        ['fa-file-pdf', 'Laporan Riset Siap Cetak', '9 jenis laporan PDF untuk kebutuhan dokumentasi dan publikasi penelitian.'],
      ];
      foreach ($features as $f): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card fac-card h-100 p-4">
            <div class="fac-icon-tile mb-3"><i class="fa-solid <?= $f[0] ?>"></i></div>
            <h6 class="fw-bold"><?= $f[1] ?></h6>
            <p class="small text-muted mb-0"><?= $f[2] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ACCOUNTABILITY BENEFITS -->
<section class="fac-section bg-white">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <p class="fac-eyebrow">Manfaat Akuntabilitas</p>
        <h2 class="fac-section-title mb-3">8 Dimensi Akuntabilitas yang Diukur</h2>
        <p class="text-muted">Indeks akuntabilitas dihitung dari data transaksi riil di sistem — bukan estimasi subjektif — dan bobotnya dapat disesuaikan peneliti untuk keperluan validasi lebih lanjut.</p>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach (['Completeness','Accuracy','Transparency','Traceability','Timeliness','Authorization','Internal Control','Auditability'] as $ind): ?>
            <div class="col-6 col-md-3">
              <div class="border rounded-3 p-3 text-center h-100">
                <i class="fa-solid fa-gauge fa-lg mb-2" style="color:var(--fac-primary)"></i>
                <div class="small fw-semibold"><?= $ind ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WORKFLOW / ROLES -->
<section class="fac-section" id="workflow">
  <div class="container">
    <div class="text-center mb-5">
      <p class="fac-eyebrow">Alur Kerja Pengguna</p>
      <h2 class="fac-section-title">Tiga Peran, Satu Ekosistem Akuntabilitas</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card fac-card h-100 p-4">
          <div class="fac-icon-tile mb-3"><i class="fa-solid fa-user-shield"></i></div>
          <h6 class="fw-bold">Admin/Peneliti</h6>
          <p class="small text-muted">Mengelola pengguna, UMKM, validator, aturan rule engine, serta memantau dashboard riset dan hasil validasi ahli.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card fac-card h-100 p-4">
          <div class="fac-icon-tile mb-3"><i class="fa-solid fa-shop"></i></div>
          <h6 class="fw-bold">UMKM</h6>
          <p class="small text-muted">Mengelola profil usaha, mencatat transaksi &amp; simulasi fintech, melampirkan bukti, serta memantau akuntansi dan akuntabilitas.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card fac-card h-100 p-4">
          <div class="fac-icon-tile mb-3"><i class="fa-solid fa-user-graduate"></i></div>
          <h6 class="fw-bold">Validator/Auditor</h6>
          <p class="small text-muted">Memverifikasi transaksi berisiko, menelusuri audit trail, dan memberikan penilaian ahli terhadap model.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- RESEARCH / PROTOTYPE INFO -->
<section class="fac-section bg-white" id="research">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <p class="fac-eyebrow">Informasi Riset &amp; Prototipe</p>
        <h2 class="fac-section-title mb-3">Perancangan Model Integrasi Smart Contract Berbasis Fintech</h2>
        <p class="text-muted">Judul Penelitian: <em>"Perancangan Model Integrasi Smart Contract Berbasis Fintech untuk Penguatan Akuntabilitas Keuangan UMKM dalam Ekosistem Hilirisasi Ekonomi Digital di Kota Medan."</em></p>
        <div class="fac-disclaimer p-3 mt-3">
          <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= e(RESEARCH_DISCLAIMER) ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card fac-card p-4">
          <h6 class="fw-bold text-uppercase small text-muted mb-3">Status Tingkat Kesiapterapan Teknologi</h6>
          <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge text-bg-primary fs-6">TKT 3</span>
            <span class="small text-muted">Pembuktian konsep fungsional (proof-of-concept) dalam lingkungan riset/lab, belum diuji pada lingkungan operasional nyata.</span>
          </div>
          <ul class="small text-muted mt-3 ps-3 mb-0">
            <li>Belum terhubung ke jaringan blockchain publik/privat.</li>
            <li>Belum terintegrasi dengan API fintech/payment gateway sungguhan.</li>
            <li>Indeks akuntabilitas adalah formula riset yang dapat dikonfigurasi, bukan standar baku tervalidasi (sampai data validasi ahli tersedia).</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="fac-section" id="faq">
  <div class="container">
    <div class="text-center mb-5">
      <p class="fac-eyebrow">FAQ</p>
      <h2 class="fac-section-title">Pertanyaan yang Sering Diajukan</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="facFaq">
          <?php
          $faqs = [
            ['Apakah FinAccChain menggunakan blockchain sungguhan?', 'Tidak. Lapisan "smart contract" pada prototipe ini disimulasikan menggunakan rule engine deterministik dan rantai hash SHA-256 yang tersimpan di basis data relasional (MySQL), bukan jaringan blockchain terdesentralisasi.'],
            ['Apakah transaksi fintech di sini nyata?', 'Tidak. Kanal transfer bank, QRIS, e-wallet, dan pembiayaan digital hanya disimulasikan untuk keperluan riset dan tidak terhubung ke penyedia layanan fintech sungguhan.'],
            ['Apakah skor akuntabilitas sudah tervalidasi secara ilmiah?', 'Formula skor bersifat konfigurasi awal peneliti. Skor tidak diklaim sebagai instrumen yang tervalidasi secara ilmiah sampai data validasi ahli (expert judgment) benar-benar dimasukkan ke sistem.'],
            ['Bagaimana cara data transaksi tidak bisa dihapus begitu saja?', 'Transaksi yang sudah tervalidasi tidak dapat dihapus langsung. Koreksi dilakukan melalui entri pembalikan/penyesuaian (reversal), dan seluruh riwayat tetap tercatat pada audit trail.'],
            ['Siapa saja yang bisa menggunakan sistem ini?', 'Tiga peran: Admin/Peneliti, UMKM, dan Validator/Auditor — masing-masing dengan hak akses dan dashboard yang berbeda.'],
          ];
          foreach ($faqs as $i => $f): ?>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                  <?= $f[0] ?>
                </button>
              </h2>
              <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#facFaq">
                <div class="accordion-body small text-muted"><?= $f[1] ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="fac-section bg-white">
  <div class="container">
    <div class="p-5 rounded-4 text-center text-white" style="background:linear-gradient(135deg, var(--fac-navy), #1a3a6b);">
      <h3 class="fw-bold mb-2">Coba Purwarupa FinAccChain Sekarang</h3>
      <p class="mb-4" style="color:rgba(255,255,255,.8)">Gunakan akun demo atau daftarkan UMKM Anda untuk menjelajahi seluruh alur model.</p>
      <a href="<?= base_url('auth/register.php') ?>" class="btn btn-lg text-white me-2" style="background:var(--fac-primary)">Daftar UMKM</a>
      <a href="<?= base_url('auth/login.php') ?>" class="btn btn-lg btn-outline-light">Masuk</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/guest_footer.php'; ?>
