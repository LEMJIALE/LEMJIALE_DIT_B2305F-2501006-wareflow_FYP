<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_system_admin();
$page_title = 'Users';
$users = $pdo->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY role, name')->fetchAll(PDO::FETCH_ASSOC);
$roleOrder = ['Admin', 'Maintenance Staff', 'Warehouse Staff'];
$usersByRole = array_fill_keys($roleOrder, []);
foreach ($users as $user) {
    if (!isset($usersByRole[$user['role']])) $usersByRole[$user['role']] = [];
    $usersByRole[$user['role']][] = $user;
}

require __DIR__ . '/../includes/header.php';
?>
<style>
.manage-users-table{table-layout:fixed;width:100%;min-width:780px}
.manage-users-table th,.manage-users-table td{padding:12px 10px;vertical-align:middle}
.manage-users-table td:nth-child(1),.manage-users-table td:nth-child(2){overflow-wrap:anywhere}
.manage-users-table td:last-child{white-space:nowrap}
.manage-users-table td:last-child .btn{height:34px;display:inline-flex;align-items:center;justify-content:center}
.manage-users-table td:last-child .btn-light{min-width:64px}
.manage-users-table td:last-child .btn-outline-danger{min-width:145px}
</style>
<div class="d-flex justify-content-between mb-4">
  <div><h1 class="page-heading"><?= e(t('users')) ?></h1><p class="page-subtitle mb-0"><?= e(t('manage_users')) ?> — grouped by role.</p></div>
  <a href="add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> <?= e(t('add_user')) ?></a>
</div>

<?php foreach ($usersByRole as $role => $roleUsers): ?>
  <section class="panel mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h6 mb-0"><i class="bi <?= $role === 'Admin' ? 'bi-shield-lock' : ($role === 'Maintenance Staff' ? 'bi-tools' : 'bi-box-seam') ?> text-warning"></i> <?= e(tv($role)) ?></h2>
      <span class="badge text-bg-dark"><?= count($roleUsers) ?> user<?= count($roleUsers) === 1 ? '' : 's' ?></span>
    </div>
    <?php if ($roleUsers): ?><div class="table-responsive"><table class="table table-hover align-middle mb-0 manage-users-table"><colgroup><col style="width:23%"><col style="width:32%"><col style="width:17%"><col style="width:28%"></colgroup><thead><tr><th><?= e(t('name')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('user_status')) ?></th><th class="text-end"><?= e(t('action')) ?></th></tr></thead><tbody>
      <?php foreach ($roleUsers as $user): ?><tr><td><b><?= e($user['name']) ?></b></td><td><?= e($user['email']) ?></td><td><span class="badge <?= $user['is_active'] ? 'status-Completed' : 'status-Retired' ?>"><?= $user['is_active'] ? e(t('active')) : e(t('inactive')) ?></span></td><td class="text-end"><a class="btn btn-sm btn-light" href="edit.php?id=<?= $user['id'] ?>"><i class="bi bi-pencil"></i> <?= e(t('edit')) ?></a> <a class="btn btn-sm btn-outline-danger" href="reset_password.php?id=<?= (int) $user['id'] ?>"><i class="bi bi-key"></i> Reset Password</a></td></tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><p class="text-muted small mb-0">No users in this role yet.</p><?php endif; ?>
  </section>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
