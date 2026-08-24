<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$msmeCount = (int) $db->query('SELECT COUNT(*) FROM msmes')->fetchColumn();
$trxCount = (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
$recordedCount = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status='recorded'")->fetchColumn();
$rejectedCount = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status='rejected'")->fetchColumn();
$validationRate = ($recordedCount + $rejectedCount) > 0 ? round($recordedCount / ($recordedCount + $rejectedCount) * 100, 1) : 0;

$avgScore = (float) $db->query(
    "SELECT AVG(overall_score) FROM accountability_assessments aa
     WHERE aa.id IN (SELECT MAX(id) FROM accountability_assessments GROUP BY msme_id)"
)->fetchColumn();

$avgIndicators = $db->query(
    "SELECT ad.indicator_code, AVG(ad.score) avg_score FROM accountability_details ad
     WHERE ad.assessment_id IN (SELECT MAX(id) FROM accountability_assessments GROUP BY msme_id)
     GROUP BY ad.indicator_code"
)->fetchAll();

$accountingMethods = $db->query('SELECT accounting_method, COUNT(*) c FROM msmes GROUP BY accounting_method')->fetchAll();
$fintechUsage = $db->query('SELECT fintech_usage, COUNT(*) c FROM msmes GROUP BY fintech_usage')->fetchAll();

$expertCount = (int) $db->query('SELECT COUNT(*) FROM expert_validations')->fetchColumn();
$expertAvg = $expertCount ? (float) $db->query(
    "SELECT AVG((relevance+clarity+feasibility+accounting_adequacy+technological_adequacy+fintech_integration+smart_contract_logic+accountability_contribution+usefulness)/9.0) FROM expert_validations"
)->fetchColumn() : 0;

$pageTitle = 'Dashboard Riset';
$activeMenu = 'research_dashboard';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $msmeCount ?></div><div class="fac-stat-label">UMKM Terdaftar</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $trxCount ?></div><div class="fac-stat-label">Total Transaksi</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $validationRate ?>%</div><div class="fac-stat-label">Tingkat Validasi</div></div></div>
  <div class="col-6 col-lg-3"><div class="fac-stat-card p-3"><div class="fac-stat-value"><?= $avgScore ? number_format($avgScore,1) : '-' ?></div><div class="fac-stat-label">Rata-rata Skor Akuntabilitas</div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Profil Metode Akuntansi UMKM</h6>
      <div id="chartAccMethod"></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Profil Adopsi Fintech UMKM</h6>
      <div id="chartFintech"></div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Kesenjangan Akuntabilitas (Rata-rata per Indikator)</h6>
      <div id="chartGaps"></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card fac-card p-4">
      <h6 class="fw-bold mb-3">Validasi Model (Expert Judgment)</h6>
      <div class="text-center py-4">
        <div class="display-5 fw-bold" style="color:var(--fac-primary)"><?= $expertCount ? number_format($expertAvg,2).'/5' : '-' ?></div>
        <p class="text-muted small mb-0"><?= $expertCount ?> penilaian ahli tercatat. <?= $expertCount ? '' : 'Belum ada data validasi ahli — indeks model belum dapat diklaim tervalidasi.' ?></p>
        <a href="<?= base_url('research/expert_summary.php') ?>" class="btn btn-sm btn-outline-primary mt-3">Lihat Detail Validasi Ahli</a>
      </div>
    </div>
  </div>
</div>

<?php
$extraScripts = '<script>
new ApexCharts(document.querySelector("#chartAccMethod"), {
  chart:{type:"pie",height:260},
  series: ' . json_encode(array_column($accountingMethods, 'c')) . ',
  labels: ' . json_encode(array_column($accountingMethods, 'accounting_method')) . ',
  legend:{position:"bottom"}
}).render();
new ApexCharts(document.querySelector("#chartFintech"), {
  chart:{type:"pie",height:260},
  series: ' . json_encode(array_column($fintechUsage, 'c')) . ',
  labels: ' . json_encode(array_column($fintechUsage, 'fintech_usage')) . ',
  legend:{position:"bottom"}
}).render();
new ApexCharts(document.querySelector("#chartGaps"), {
  chart:{type:"bar",height:280,toolbar:{show:false}},
  series:[{name:"Rata-rata Skor", data: ' . json_encode(array_map('floatval', array_column($avgIndicators, 'avg_score'))) . '}],
  xaxis:{categories: ' . json_encode(array_column($avgIndicators, 'indicator_code')) . '},
  colors:["#0ec9b0"]
}).render();
</script>';
require __DIR__ . '/../includes/app_footer.php';
