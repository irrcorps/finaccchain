<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role('admin');
$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) $_POST['id'];
    $db->prepare('UPDATE msmes SET business_status = ? WHERE id = ?')->execute([$_POST['business_status'], $id]);
    flash('success', 'Status UMKM diperbarui.');
    redirect('admin/msmes.php');
}

$list = $db->query(
    'SELECT m.*, u.email, (SELECT COUNT(*) FROM transactions t WHERE t.msme_id=m.id) AS trx_count
     FROM msmes m JOIN users u ON u.id = m.user_id ORDER BY m.id DESC'
)->fetchAll();

$pageTitle = 'Manajemen UMKM';
$activeMenu = 'msmes';
require __DIR__ . '/../includes/app_header.php';
?>
<div class="card fac-card">
  <div class="table-responsive">
    <table class="table table-fac mb-0">
      <thead><tr><th>Nama Usaha</th><th>Pemilik</th><th>Sektor</th><th>Email</th><th>Transaksi</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?><tr><td colspan="7"><?php render_empty_state('fa-shop','Belum ada UMKM terdaftar.'); ?></td></tr><?php endif; ?>
      <?php foreach ($list as $m): ?>
        <tr>
          <td><?= e($m['business_name']) ?> <?= $m['is_demo'] ? '<span class="badge text-bg-light text-muted border">demo</span>' : '' ?></td>
          <td><?= e($m['owner_name']) ?></td>
          <td><?= e($m['sector']) ?></td>
          <td><?= e($m['email']) ?></td>
          <td><?= (int) $m['trx_count'] ?></td>
          <td><?= status_badge($m['business_status']) ?></td>
          <td class="text-end">
            <a href="<?= base_url('admin/transactions.php?msme_id=' . $m['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-right-left"></i></a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#status<?= $m['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          </td>
        </tr>
        <div class="modal fade" id="status<?= $m['id'] ?>" tabindex="-1">
          <div class="modal-dialog"><div class="modal-content">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
              <div class="modal-header"><h6 class="modal-title">Ubah Status: <?= e($m['business_name']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <select name="business_status" class="form-select">
                  <?php foreach (['active'=>'Aktif','inactive'=>'Tidak Aktif','suspended'=>'Ditangguhkan'] as $k=>$v): ?>
                  <option value="<?= $k ?>" <?= $m['business_status']===$k?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="modal-footer"><button class="btn btn-primary" style="background:var(--fac-primary);border-color:var(--fac-primary)">Simpan</button></div>
            </form>
          </div></div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/app_footer.php'; ?>
