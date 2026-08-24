<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM questionnaires WHERE id = ?');
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) { http_response_code(404); die('Kuesioner tidak ditemukan.'); }
$questions = json_decode($q['questions_json'], true) ?: [];

$respStmt = $db->prepare('SELECT * FROM questionnaire_responses WHERE questionnaire_id = ? ORDER BY id DESC');
$respStmt->execute([$id]);
$responses = $respStmt->fetchAll();

$pageTitle = 'Respons: ' . $q['title'];
$activeMenu = 'questionnaires';
require __DIR__ . '/../includes/app_header.php';
?>
<a href="<?= base_url('research/questionnaire.php') ?>" class="small"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
<div class="card fac-card p-3 mt-2">
  <div class="table-responsive">
  <table class="table table-fac table-sm">
    <thead><tr><th>Responden</th><th>Tipe</th><th>Waktu</th><?php foreach ($questions as $item): ?><th><?= e($item['code']) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php if (!$responses): ?><tr><td colspan="<?= 3+count($questions) ?>"><?php render_empty_state('fa-inbox','Belum ada respons.'); ?></td></tr><?php endif; ?>
    <?php foreach ($responses as $r): $ans = json_decode($r['answers_json'], true) ?: []; ?>
      <tr>
        <td><?= e($r['respondent_name']) ?></td>
        <td><?= e($r['respondent_type']) ?></td>
        <td><?= format_datetime($r['submitted_at']) ?></td>
        <?php foreach ($questions as $item): ?><td class="small"><?= e($ans[$item['code']] ?? '-') ?></td><?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
