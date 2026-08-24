<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('msme');
$u = current_user();
$msmeId = (int) $u['msme_id'];
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $db->prepare(
        'UPDATE msmes SET business_name=?, owner_name=?, sector=?, address=?, business_age_years=?, employee_count=?,
         monthly_turnover_category=?, digital_payment_usage=?, fintech_usage=?, accounting_method=?, business_status=? WHERE id=?'
    );
    $stmt->execute([
        trim($_POST['business_name']), trim($_POST['owner_name']), trim($_POST['sector']), trim($_POST['address']),
        (int) $_POST['business_age_years'] ?: null, (int) $_POST['employee_count'] ?: null,
        $_POST['monthly_turnover_category'], $_POST['digital_payment_usage'], $_POST['fintech_usage'],
        $_POST['accounting_method'], $_POST['business_status'], $msmeId,
    ]);
    flash('success', 'Profil usaha berhasil diperbarui.');
    redirect('msme/profile.php');
}

$stmt = $db->prepare('SELECT * FROM msmes WHERE id = ?');
$stmt->execute([$msmeId]);
$m = $stmt->fetch();

$pageTitle = 'Profil Usaha';
$activeMenu = 'profile';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="card fac-card p-4" style="max-width:900px">
  <?php if ($m['is_demo']): ?><div class="alert alert-secondary small"><i class="fa-solid fa-flask me-1"></i>Ini adalah data DEMO bawaan prototipe.</div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nama Usaha</label>
        <input class="form-control" name="business_name" required value="<?= e($m['business_name']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Nama Pemilik</label>
        <input class="form-control" name="owner_name" required value="<?= e($m['owner_name']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Sektor Usaha</label>
        <input class="form-control" name="sector" required value="<?= e($m['sector']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Status Usaha</label>
        <select class="form-select" name="business_status">
          <?php foreach (['active'=>'Aktif','inactive'=>'Tidak Aktif','suspended'=>'Ditangguhkan'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $m['business_status']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Alamat</label>
        <input class="form-control" name="address" value="<?= e($m['address']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Usia Usaha (tahun)</label>
        <input type="number" min="0" class="form-control" name="business_age_years" value="<?= e((string)$m['business_age_years']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Jumlah Karyawan</label>
        <input type="number" min="0" class="form-control" name="employee_count" value="<?= e((string)$m['employee_count']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Omzet Bulanan</label>
        <select class="form-select" name="monthly_turnover_category">
          <?php foreach (['<5jt'=>'< Rp5 juta','5-10jt'=>'Rp5-10 juta','10-50jt'=>'Rp10-50 juta','50-300jt'=>'Rp50-300 juta','>300jt'=>'> Rp300 juta'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $m['monthly_turnover_category']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Penggunaan Pembayaran Digital</label>
        <select class="form-select" name="digital_payment_usage">
          <?php foreach (['none'=>'Belum','partial'=>'Sebagian','full'=>'Penuh'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $m['digital_payment_usage']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Penggunaan Fintech</label>
        <select class="form-select" name="fintech_usage">
          <?php foreach (['none'=>'Belum','payment_only'=>'Pembayaran saja','payment_financing'=>'Pembayaran & Pembiayaan','full_integration'=>'Terintegrasi penuh'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $m['fintech_usage']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Metode Akuntansi</label>
        <select class="form-select" name="accounting_method">
          <?php foreach (['manual'=>'Manual','spreadsheet'=>'Spreadsheet','accounting_app'=>'Aplikasi Akuntansi','none'=>'Belum ada'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $m['accounting_method']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn-primary mt-4" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan Perubahan</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
