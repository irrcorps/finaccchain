<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

$stmt = $db->prepare('SELECT COUNT(*) FROM transactions WHERE msme_id = ?');
$stmt->execute([$msmeId]);
$totalTrx = (int) $stmt->fetchColumn();

$sumStmt = $db->prepare('SELECT * FROM (SELECT
    SUM(CASE WHEN type IN ("sales","receivable","other_income") AND status = "recorded" THEN amount ELSE 0 END) AS income,
    SUM(CASE WHEN type IN ("purchase","operating_expense","payable","other_expense") AND status = "recorded" THEN amount ELSE 0 END) AS expense,
    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count
    FROM transactions WHERE msme_id = ?) x');
$sumStmt->execute([$msmeId]);
$sums = $sumStmt->fetch() ?: ['income' => 0, 'expense' => 0, 'pending_count' => 0];

$assessment = Accountability::latest($msmeId);
$score = $assessment['overall_score'] ?? null;

$chain = HashChain::verifyChain();

// Chart data ---------------------------------------------------------------
$channelStmt = $db->prepare('SELECT payment_channel, COUNT(*) c FROM transactions WHERE msme_id = ? GROUP BY payment_channel');
$channelStmt->execute([$msmeId]);
$channelData = $channelStmt->fetchAll();

$statusStmt = $db->prepare('SELECT status, COUNT(*) c FROM transactions WHERE msme_id = ? GROUP BY status');
$statusStmt->execute([$msmeId]);
$statusData = $statusStmt->fetchAll();

$monthlyStmt = $db->prepare('SELECT DATE_FORMAT(transaction_date, "%Y-%m") ym, COUNT(*) c FROM transactions WHERE msme_id = ? GROUP BY ym ORDER BY ym ASC');
$monthlyStmt->execute([$msmeId]);
$monthlyData = $monthlyStmt->fetchAll();

$cashflow = Accounting::cashFlowSummary($msmeId);

$pageTitle = 'Dashboard UMKM';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value"><?= $totalTrx ?></div><div class="fac-stat-label">Total Transaksi</div></div>
        <div class="stat-icon" style="background:#eaf0ff;color:var(--fac-primary)"><i class="fa-solid fa-right-left"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value" style="font-size:1.2rem"><?= format_money($sums['income']) ?></div><div class="fac-stat-label">Pendapatan Tercatat</div></div>
        <div class="stat-icon" style="background:#e6faf6;color:var(--fac-teal)"><i class="fa-solid fa-arrow-trend-up"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value" style="font-size:1.2rem"><?= format_money($sums['expense']) ?></div><div class="fac-stat-label">Beban Tercatat</div></div>
        <div class="stat-icon" style="background:#fff1e6;color:#e07a2c"><i class="fa-solid fa-arrow-trend-down"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value"><?= (int) $sums['pending_count'] ?></div><div class="fac-stat-label">Menunggu Validasi</div></div>
        <div class="stat-icon" style="background:#fff8e0;color:#c99a00"><i class="fa-solid fa-hourglass-half"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value"><?= $score !== null ? number_format($score, 1) : '-' ?></div><div class="fac-stat-label">Skor Akuntabilitas</div></div>
        <div class="stat-icon" style="background:#eaf0ff;color:var(--fac-primary)"><i class="fa-solid fa-shield-halved"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="fac-stat-card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="fac-stat-value fs-6"><?= $chain['is_valid'] ? '<span class="text-success">Terverifikasi</span>' : '<span class="text-danger">Bermasalah</span>' ?></div><div class="fac-stat-label">Integritas Audit Trail</div></div>
        <div class="stat-icon" style="background:<?= $chain['is_valid'] ? '#e6faf6' : '#fdeaea' ?>;color:<?= $chain['is_valid'] ? 'var(--fac-teal)' : '#d9534f' ?>"><i class="fa-solid fa-link"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-3">Pendapatan vs Beban per Bulan</h6>
      <div id="chartIncomeExpense"></div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-3">Kanal Transaksi</h6>
      <div id="chartChannels"></div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-3">Status Transaksi</h6>
      <div id="chartStatus"></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-3">Jumlah Transaksi per Bulan</h6>
      <div id="chartMonthly"></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card fac-card p-3">
      <h6 class="fw-bold mb-3">Dimensi Akuntabilitas</h6>
      <div id="chartAccountability"></div>
    </div>
  </div>
</div>

<?php
$extraScripts = '<script>
const incomeExpense = ' . json_encode($cashflow) . ';
new ApexCharts(document.querySelector("#chartIncomeExpense"), {
  chart:{type:"bar",height:280,toolbar:{show:false}},
  series:[
    {name:"Pendapatan", data: incomeExpense.map(r=>parseFloat(r.inflow))},
    {name:"Beban", data: incomeExpense.map(r=>parseFloat(r.outflow))}
  ],
  xaxis:{categories: incomeExpense.map(r=>r.ym)},
  colors:["#2f5fff","#e07a2c"],
  legend:{position:"top"}
}).render();

const channelData = ' . json_encode($channelData) . ';
new ApexCharts(document.querySelector("#chartChannels"), {
  chart:{type:"donut",height:260},
  series: channelData.map(r=>parseInt(r.c)),
  labels: channelData.map(r=>r.payment_channel),
  legend:{position:"bottom", fontSize:"11px"}
}).render();

const statusData = ' . json_encode($statusData) . ';
new ApexCharts(document.querySelector("#chartStatus"), {
  chart:{type:"donut",height:260},
  series: statusData.map(r=>parseInt(r.c)),
  labels: statusData.map(r=>r.status),
  colors:["#6c757d","#f0ad4e","#5bc0de","#d9534f","#2f5fff","#343a40"],
  legend:{position:"bottom", fontSize:"11px"}
}).render();

const monthlyData = ' . json_encode($monthlyData) . ';
new ApexCharts(document.querySelector("#chartMonthly"), {
  chart:{type:"line",height:280,toolbar:{show:false}},
  series:[{name:"Transaksi", data: monthlyData.map(r=>parseInt(r.c))}],
  xaxis:{categories: monthlyData.map(r=>r.ym)},
  colors:["#0ec9b0"]
}).render();

const accIndicators = ' . json_encode($assessment['details'] ?? []) . ';
new ApexCharts(document.querySelector("#chartAccountability"), {
  chart:{type:"radar",height:280},
  series:[{name:"Skor", data: accIndicators.map(r=>parseFloat(r.score))}],
  xaxis:{categories: accIndicators.map(r=>r.indicator_code)},
  colors:["#2f5fff"]
}).render();
</script>';
require __DIR__ . '/../includes/app_footer.php';
