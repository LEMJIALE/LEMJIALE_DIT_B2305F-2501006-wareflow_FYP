<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $workOrderId = (int) ($_POST['work_order_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    if (($_SESSION['user']['role'] ?? '') !== 'Maintenance Staff' || !in_array($decision, ['accept', 'decline'], true)) {
        http_response_code(403);
        exit('Only the assigned Maintenance Staff member can respond to this work order.');
    }
    $check = $pdo->prepare("SELECT request_id FROM work_orders WHERE id=? AND assigned_to=? AND status='Assigned'");
    $check->execute([$workOrderId, $_SESSION['user']['id']]);
    $workOrder = $check->fetch(PDO::FETCH_ASSOC);
    if (!$workOrder) {
        flash('This work order is no longer available for a response.');
        header('Location: work_orders.php');
        exit;
    }
    if ($decision === 'accept') {
        $pdo->prepare("UPDATE work_orders SET status='In Progress' WHERE id=?")->execute([$workOrderId]);
        if ($workOrder['request_id']) $pdo->prepare("UPDATE maintenance_requests SET status='In Progress' WHERE id=?")->execute([$workOrder['request_id']]);
        flash('Work order accepted. You can now update the repair work.');
    } else {
        $pdo->prepare("UPDATE work_orders SET status='Cancelled' WHERE id=?")->execute([$workOrderId]);
        if ($workOrder['request_id']) $pdo->prepare("UPDATE maintenance_requests SET status='Pending' WHERE id=?")->execute([$workOrder['request_id']]);
        flash('Work order declined. The request is Pending so the admin can assign it again.');
    }
    header('Location: work_orders.php');
    exit;
}

$page_title = 'Work Orders';
require __DIR__ . '/../includes/header.php';
$sql = 'SELECT w.*, e.equipment_name, u.name assigned_staff, r.request_code FROM work_orders w JOIN equipment e ON e.id=w.equipment_id LEFT JOIN users u ON u.id=w.assigned_to LEFT JOIN maintenance_requests r ON r.id=w.request_id ORDER BY w.due_date ASC';
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-heading"><?= e(t('work_orders')) ?></h1><p class="page-subtitle mb-0">View work assignments. Assigned Maintenance Staff can accept, decline, and complete their repair work.</p></div><?php if (is_system_admin()): ?><a class="btn btn-primary" href="add_work_order.php"><?= e(t('create_work_order')) ?></a><?php endif ?></div>
<div class="panel table-responsive"><table class="table table-hover mb-0"><thead><tr><th><?= e(t('work_order')) ?></th><th><?= e(t('equipment')) ?></th><th>Assigned To</th><th><?= e(t('priority')) ?></th><th><?= e(t('due_date')) ?></th><th><?= e(t('status')) ?></th><th class="text-end">Action</th></tr></thead><tbody><?php foreach ($rows as $w): ?><tr><td><b><?= e($w['work_order_code']) ?></b><br><small><?= e($w['request_code'] ?? '—') ?></small></td><td><?= e($w['equipment_name']) ?></td><td><?= e($w['assigned_staff'] ?? 'Unassigned') ?></td><td><span class="badge status-<?= e($w['priority']) ?>"><?= e(tv($w['priority'])) ?></span></td><td><?= e($w['due_date'] ?? '—') ?></td><td><span class="badge status-<?= str_replace(' ', '-', $w['status']) ?>"><?= e(tv($w['status'])) ?></span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary me-1" href="view_work_order.php?id=<?= (int) $w['id'] ?>">View</a><?php if (($_SESSION['user']['role'] ?? '') === 'Maintenance Staff' && (int) $w['assigned_to'] === (int) $_SESSION['user']['id'] && $w['status'] === 'Assigned'): ?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="work_order_id" value="<?= $w['id'] ?>"><input type="hidden" name="decision" value="accept"><button class="btn btn-sm btn-success">Accept</button></form><form method="post" class="d-inline ms-1"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="work_order_id" value="<?= $w['id'] ?>"><input type="hidden" name="decision" value="decline"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Decline this work order?')">Decline</button></form><?php elseif (($_SESSION['user']['role'] ?? '') === 'Maintenance Staff' && (int) $w['assigned_to'] === (int) $_SESSION['user']['id'] && $w['status'] === 'In Progress'): ?><a class="btn btn-sm btn-primary" href="update_work_order.php?id=<?= $w['id'] ?>">Update Repair</a><?php else: ?><?php endif ?></td></tr><?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">No work orders found.</td></tr><?php endif ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
