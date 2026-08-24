<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$criteria = [
    'relevance' => 'Relevansi', 'clarity' => 'Kejelasan', 'feasibility' => 'Kelayakan',
    'accounting_adequacy' => 'Kecukupan Akuntansi', 'technological_adequacy' => 'Kecukupan Teknologi',
    'fintech_integration' => 'Integrasi Fintech', 'smart_contract_logic' => 'Logika Smart Contract',
    'accountability_contribution' => 'Kontribusi Akuntabilitas', 'usefulness' => 'Kebermanfaatan',
];

$all = $db->query('SELECT * FROM expert_validations')->fetchAll();
$count = count($all);

$means = [];
foreach (array_keys($criteria) as $c) {
    $means[$c] = $count ? round(array_sum(array_column($all, $c)) / $count, 2) : 0;
}
$validationIndex = $count ? round(array_sum($means) / count($means) / 5 * 100, 1) : null;

$comments = $db->query(
    "SELECT ev.*, u.name AS expert_name, m.business_name FROM expert_validations ev
     JOIN users u ON u.id = ev.expert_user_id LEFT JOIN msmes m ON m.id = ev.msme_id
     WHERE ev.comments IS NOT NULL AND ev.comments != '' ORDER BY ev.id DESC"
)->fetchAll();

$pageTitle = 'Ringkasan Validasi Ahli';
$activeMenu = 'expert';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card fac-card p-4 text-center">
      <div class="fac-stat-value fs-1"><?= $count ?></div>
      <div class="fac-stat-label">Jumlah Penilaian Ahli</div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card fac-card p-4 text-center">
      <div class="fac-stat-value fs-1"><?= $validationIndex !== null ? $validationIndex . '%' : '-' ?></div>
      <div class="fac-stat-label">Indeks Validasi Model</div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card fac-card p-4 small text-muted d-flex align-items-center">
      <?= $count > 0 ? 'Data validasi ahli tersedia — indeks akuntabilitas dapat dirujuk sebagai indikasi awal tervalidasi.' : 'Belum ada data validasi ahli. Indeks akuntabilitas UMKM belum dapat diklaim tervalidasi secara ilmiah.' ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Rata-rata Skor per Kriteria</h6>
      <div id="chartExpert"></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Matriks Revisi (Kriteria dengan Rata-rata &lt; 3.5)</h6>
      <?php $needsRevision = array_filter($means, fn($v) => $v > 0 && $v < 3.5);
      if (!$needsRevision): ?>
        <?php render_empty_state('fa-thumbs-up', $count ? 'Tidak ada kriteria yang memerlukan revisi mendesak.' : 'Belum ada data untuk dianalisis.'); ?>
      <?php else: ?>
        <table class="table table-sm">
          <thead><tr><th>Kriteria</th><th>Rata-rata</th><th>Tindak Lanjut</th></tr></thead>
          <tbody>
          <?php foreach ($needsRevision as $code => $val): ?>
            <tr><td><?= e($criteria[$code]) ?></td><td><?= $val ?></td><td class="small text-muted">Perlu peninjauan &amp; revisi desain pada aspek ini.</td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card fac-card p-4 mt-3">
  <h6 class="fw-bold mb-3">Komentar Ahli</h6>
  <?php if (!$comments): render_empty_state('fa-comments', 'Belum ada komentar.'); else: foreach ($comments as $c): ?>
    <div class="border-bottom py-2">
      <strong><?= e($c['expert_name']) ?></strong> <span class="text-muted small">· <?= e($c['business_name'] ?? 'Model Umum') ?> · <?= format_datetime($c['created_at']) ?></span>
      <p class="small mb-0"><?= e($c['comments']) ?></p>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php
$extraScripts = '<script>
new ApexCharts(document.querySelector("#chartExpert"), {
  chart:{type:"bar",height:320,toolbar:{show:false}},
  series:[{name:"Rata-rata", data: ' . json_encode(array_values($means)) . '}],
  xaxis:{categories: ' . json_encode(array_values($criteria)) . ', max:5},
  plotOptions:{bar:{horizontal:true}},
  colors:["#2f5fff"]
}).render();
</script>';
require __DIR__ . '/../includes/app_footer.php';
