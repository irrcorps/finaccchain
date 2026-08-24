<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_login();
$u = current_user();
$db = Database::connection();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM questionnaires WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) { http_response_code(404); die('Kuesioner tidak ditemukan.'); }
$questions = json_decode($q['questions_json'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $answers = [];
    foreach ($questions as $item) {
        $answers[$item['code']] = trim($_POST['ans_' . $item['code']] ?? '');
    }
    $respondentName = $u['role_code'] === 'admin' ? trim($_POST['respondent_name'] ?? $u['name']) : $u['name'];
    $respondentType = $u['role_code'] === 'admin' ? ($_POST['respondent_type'] ?? 'interview') : $u['role_code'];

    $ins = $db->prepare('INSERT INTO questionnaire_responses (questionnaire_id, respondent_user_id, respondent_name, respondent_type, answers_json) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$id, $u['id'], $respondentName, $respondentType, json_encode($answers, JSON_UNESCAPED_UNICODE)]);
    flash('success', 'Terima kasih, respons Anda telah tersimpan untuk keperluan riset.');
    redirect('research/questionnaire.php');
}

$pageTitle = $q['title'];
$activeMenu = 'questionnaires';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="card fac-card p-4" style="max-width:760px">
  <h5 class="fw-bold"><?= e($q['title']) ?></h5>
  <p class="text-muted small"><?= e($q['description']) ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <?php if ($u['role_code'] === 'admin'): ?>
    <div class="row g-3 mb-3 border-bottom pb-3">
      <div class="col-md-6"><label class="form-label">Nama Responden (entri wawancara)</label><input name="respondent_name" class="form-control" placeholder="Nama narasumber"></div>
      <div class="col-md-6"><label class="form-label">Tipe Responden</label>
        <select name="respondent_type" class="form-select">
          <option value="interview">Wawancara Lapangan</option>
          <option value="msme">UMKM</option>
          <option value="validator">Ahli/Validator</option>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <?php foreach ($questions as $item): ?>
      <div class="mb-3">
        <label class="form-label"><?= e($item['text']) ?></label>
        <?php if ($item['type'] === 'scale_1_5'): ?>
          <select name="ans_<?= e($item['code']) ?>" class="form-select" required>
            <option value="">Pilih (1=Sangat Rendah - 5=Sangat Tinggi)</option>
            <?php for ($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
          </select>
        <?php elseif ($item['type'] === 'yes_no'): ?>
          <select name="ans_<?= e($item['code']) ?>" class="form-select" required>
            <option value="">Pilih</option><option value="Ya">Ya</option><option value="Tidak">Tidak</option>
          </select>
        <?php else: ?>
          <textarea name="ans_<?= e($item['code']) ?>" class="form-control" rows="2"></textarea>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn text-white" style="background:var(--fac-primary)">Kirim Jawaban</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
