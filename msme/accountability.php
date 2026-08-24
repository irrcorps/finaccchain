<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    Accountability::computeAndStore($msmeId, $u['id']);
    flash('success', 'Skor akuntabilitas berhasil dihitung ulang.');
    redirect('msme/accountability.php');
}

$assessment = Accountability::latest($msmeId);
$hasExpert = Accountability::hasExpertValidation();

$pageTitle = 'Skor Akuntabilitas';
$activeMenu = 'accountability';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="fac-disclaimer p-3 mb-4">
  <i class="fa-solid fa-circle-info me-1"></i>
  Indeks ini adalah <strong>formula riset</strong> (bobot dapat dikonfigurasi peneliti) berdasarkan data transaksi aktual pada sistem.
  <?= $hasExpert ? 'Data validasi ahli sudah tersedia di sistem.' : 'Indeks ini <strong>belum divalidasi secara ilmiah oleh ahli</strong> karena data validasi ahli belum dimasukkan.' ?>
</div>

<?php if (!$assessment): ?>
  <div class="card fac-card p-4 text-center">
    <?php render_empty_state('fa-shield-halved', 'Belum ada penilaian akuntabilitas. Hitung skor setelah Anda memiliki transaksi tercatat.'); ?>
    <form method="post" class="mt-2"><?= csrf_field() ?><button class="btn text-white" style="background:var(--fac-primary)">Hitung Skor Sekarang</button></form>
  </div>
<?php else: ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card fac-card p-4 text-center">
      <div class="display-4 fw-bold" style="color:var(--fac-primary)"><?= number_format($assessment['overall_score'], 1) ?></div>
      <div class="text-muted mb-3">Indeks Akuntabilitas Keuangan (0-100)</div>
      <div class="progress mb-3" style="height:10px"><div class="progress-bar" style="width:<?= $assessment['overall_score'] ?>%; background:var(--fac-primary)"></div></div>
      <div class="small text-muted">Dihitung: <?= format_datetime($assessment['created_at']) ?> · Periode: <?= e($assessment['period_label']) ?></div>
      <form method="post" class="mt-3"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-rotate me-1"></i>Hitung Ulang</button></form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Radar 8 Indikator Akuntabilitas</h6>
      <div id="chartRadar"></div>
    </div>
  </div>
</div>

<div class="card fac-card p-4 mt-3">
  <h6 class="fw-bold mb-3">Rincian Indikator, Kelemahan &amp; Rekomendasi</h6>
  <div class="table-responsive">
  <table class="table table-fac">
    <thead><tr><th>Indikator</th><th>Skor</th><th>Bobot</th><th>Status</th><th>Rekomendasi</th></tr></thead>
    <tbody>
    <?php foreach ($assessment['details'] as $d): ?>
      <tr>
        <td><?= e(Accountability::INDICATORS[$d['indicator_code']] ?? $d['indicator_code']) ?></td>
        <td><?= number_format($d['score'], 1) ?></td>
        <td><?= number_format($d['weight'], 1) ?>%</td>
        <td><?= $d['score'] >= 85 ? '<span class="badge text-bg-success">Baik</span>' : ($d['score'] >= 70 ? '<span class="badge text-bg-warning">Cukup</span>' : '<span class="badge text-bg-danger">Lemah</span>') ?></td>
        <td class="small text-muted"><?= $d['score'] < 85 ? e(Accountability::recommendation($d['indicator_code'])) : '-' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php
$radarData = array_map(fn($d) => (float) $d['score'], $assessment['details']);
$radarLabels = array_map(fn($d) => Accountability::INDICATORS[$d['indicator_code']] ?? $d['indicator_code'], $assessment['details']);
$extraScripts = '<script>
new ApexCharts(document.querySelector("#chartRadar"), {
  chart:{type:"radar",height:320},
  series:[{name:"Skor", data: ' . json_encode($radarData) . '}],
  xaxis:{categories: ' . json_encode($radarLabels) . '},
  yaxis:{min:0,max:100},
  colors:["#2f5fff"]
}).render();
</script>';
endif;
require __DIR__ . '/../includes/app_footer.php';
