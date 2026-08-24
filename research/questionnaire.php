<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role(['admin', 'msme', 'validator']);
$u = current_user();
$db = Database::connection();

if ($u['role_code'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $questions = [];
    foreach ($_POST['q_text'] ?? [] as $i => $text) {
        $text = trim($text);
        if ($text === '') continue;
        $questions[] = ['code' => 'Q' . ($i + 1), 'text' => $text, 'type' => $_POST['q_type'][$i] ?? 'text'];
    }
    if (trim($_POST['title'] ?? '') === '' || !$questions) {
        flash('danger', 'Judul kuesioner dan minimal 1 pertanyaan wajib diisi.');
    } else {
        $stmt = $db->prepare('INSERT INTO questionnaires (title, description, target_role, questions_json) VALUES (?, ?, ?, ?)');
        $stmt->execute([trim($_POST['title']), trim($_POST['description'] ?? ''), $_POST['target_role'], json_encode($questions, JSON_UNESCAPED_UNICODE)]);
        flash('success', 'Kuesioner berhasil dibuat.');
    }
    redirect('research/questionnaire.php');
}

$list = $db->query(
    'SELECT q.*, (SELECT COUNT(*) FROM questionnaire_responses r WHERE r.questionnaire_id = q.id) AS response_count
     FROM questionnaires q ORDER BY q.id DESC'
)->fetchAll();

$pageTitle = 'Kuesioner Riset';
$activeMenu = 'questionnaires';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-bold mb-0">Daftar Kuesioner</h6>
  <?php if ($u['role_code'] === 'admin'): ?>
  <button class="btn btn-sm text-white" style="background:var(--fac-primary)" data-bs-toggle="modal" data-bs-target="#addQ"><i class="fa-solid fa-plus me-1"></i>Buat Kuesioner</button>
  <?php endif; ?>
</div>

<div class="row g-3">
<?php foreach ($list as $q): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card fac-card p-3 h-100">
      <span class="badge text-bg-secondary mb-2 align-self-start"><?= e(ucfirst($q['target_role'])) ?></span>
      <h6 class="fw-bold"><?= e($q['title']) ?></h6>
      <p class="small text-muted"><?= e($q['description']) ?></p>
      <p class="small mb-3"><i class="fa-solid fa-list-check me-1"></i><?= $q['response_count'] ?> respons masuk</p>
      <div class="d-flex gap-2 mt-auto">
        <a href="<?= base_url('research/questionnaire_fill.php?id=' . $q['id']) ?>" class="btn btn-sm btn-outline-primary flex-fill">Isi/Input Data</a>
        <?php if ($u['role_code'] === 'admin'): ?>
        <a href="<?= base_url('research/questionnaire_responses.php?id=' . $q['id']) ?>" class="btn btn-sm btn-outline-secondary flex-fill">Lihat Respons</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php if ($u['role_code'] === 'admin'): ?>
<div class="modal fade" id="addQ" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Buat Kuesioner Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-8"><label class="form-label">Judul</label><input name="title" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Target Responden</label>
              <select name="target_role" class="form-select">
                <option value="msme">UMKM</option>
                <option value="validator">Validator/Ahli</option>
                <option value="general">Umum</option>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Deskripsi</label><input name="description" class="form-control"></div>
          </div>
          <label class="form-label">Pertanyaan</label>
          <div id="qWrap">
            <?php for ($i=0;$i<3;$i++): ?>
            <div class="input-group mb-2">
              <input name="q_text[]" class="form-control" placeholder="Teks pertanyaan <?= $i+1 ?>">
              <select name="q_type[]" class="form-select" style="max-width:160px">
                <option value="scale_1_5">Skala 1-5</option>
                <option value="yes_no">Ya/Tidak</option>
                <option value="text">Isian Bebas</option>
              </select>
            </div>
            <?php endfor; ?>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addQRow()">+ Tambah Pertanyaan</button>
        </div>
        <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan Kuesioner</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function addQRow(){
  const wrap = document.getElementById('qWrap');
  const div = document.createElement('div');
  div.className = 'input-group mb-2';
  div.innerHTML = '<input name="q_text[]" class="form-control" placeholder="Teks pertanyaan"><select name="q_type[]" class="form-select" style="max-width:160px"><option value="scale_1_5">Skala 1-5</option><option value="yes_no">Ya/Tidak</option><option value="text">Isian Bebas</option></select>';
  wrap.appendChild(div);
}
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
