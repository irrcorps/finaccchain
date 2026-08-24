<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$datasets = [
    'msmes' => 'Profil UMKM',
    'transactions' => 'Transaksi Keuangan',
    'questionnaire_responses' => 'Respons Kuesioner',
    'expert_validations' => 'Validasi Ahli',
    'accountability' => 'Skor Akuntabilitas',
];

if (isset($_GET['download'])) {
    $ds = $_GET['download'];
    if (!array_key_exists($ds, $datasets)) { die('Dataset tidak dikenal.'); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="finaccchain_' . $ds . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($ds === 'msmes') {
        $rows = $db->query('SELECT id,business_name,owner_name,sector,monthly_turnover_category,digital_payment_usage,fintech_usage,accounting_method,business_status,is_demo FROM msmes')->fetchAll();
    } elseif ($ds === 'transactions') {
        $rows = $db->query('SELECT id,msme_id,transaction_uid,transaction_date,type,amount,payment_channel,status,is_demo FROM transactions')->fetchAll();
    } elseif ($ds === 'questionnaire_responses') {
        $rows = $db->query('SELECT id,questionnaire_id,respondent_name,respondent_type,answers_json,submitted_at FROM questionnaire_responses')->fetchAll();
    } elseif ($ds === 'expert_validations') {
        $rows = $db->query('SELECT * FROM expert_validations')->fetchAll();
    } else {
        $rows = $db->query(
            'SELECT aa.msme_id, m.business_name, aa.assessment_date, aa.overall_score, ad.indicator_code, ad.score, ad.weight
             FROM accountability_assessments aa
             JOIN accountability_details ad ON ad.assessment_id = aa.id
             JOIN msmes m ON m.id = aa.msme_id ORDER BY aa.id'
        )->fetchAll();
    }

    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

$counts = [];
foreach ($datasets as $k => $label) {
    $table = $k === 'accountability' ? 'accountability_details' : $k;
    $counts[$k] = (int) $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}

$pageTitle = 'Ekspor Dataset Riset';
$activeMenu = 'research_export';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="row g-3">
<?php foreach ($datasets as $k => $label): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card fac-card p-4 h-100 d-flex flex-column">
      <div class="fac-icon-tile mb-3"><i class="fa-solid fa-file-csv"></i></div>
      <h6 class="fw-bold"><?= e($label) ?></h6>
      <p class="small text-muted mb-3"><?= $counts[$k] ?> baris data</p>
      <a href="?download=<?= $k ?>" class="btn btn-sm btn-outline-primary mt-auto">Unduh CSV</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<p class="small text-muted mt-3"><i class="fa-solid fa-circle-info me-1"></i>File CSV kompatibel dibuka di Excel/Google Sheets/SPSS untuk kebutuhan analisis riset.</p>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
