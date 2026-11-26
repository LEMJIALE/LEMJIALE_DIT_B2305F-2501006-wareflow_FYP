<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
$query=$pdo->prepare('SELECT w.*,e.equipment_name,e.serial_number,u.name assigned_staff,r.request_code FROM work_orders w JOIN equipment e ON e.id=w.equipment_id LEFT JOIN users u ON u.id=w.assigned_to LEFT JOIN maintenance_requests r ON r.id=w.request_id WHERE w.id=?');
$query->execute([(int)($_GET['id']??0)]);
$order=$query->fetch(PDO::FETCH_ASSOC);
if(!$order){http_response_code(404);exit('Work order not found.');}
$page_title='Work Order Details';
require __DIR__.'/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="page-heading">Work Order Details</h1><p class="page-subtitle mb-0"><?=e($order['work_order_code'])?></p></div><a class="btn btn-outline-primary" href="work_orders.php">Back to Work Orders</a></div>
<div class="panel mb-4 border-start border-4 border-warning"><div class="text-muted mb-1">Assigned To</div><h2 class="h4 mb-0"><i class="bi bi-person-check me-2"></i><?=e($order['assigned_staff']??'Unassigned')?></h2></div>
<div class="panel"><dl class="row">
<?php foreach(['equipment_name'=>'Equipment','serial_number'=>'Serial Number','request_code'=>'Request','start_date'=>'Start Date','due_date'=>'Due Date','completion_date'=>'Completion Date'] as $field=>$label):?><dt class="col-md-3"><?=e($label)?></dt><dd class="col-md-9"><?=e($order[$field]??'Not recorded')?></dd><?php endforeach?>
<dt class="col-md-3">Priority</dt><dd class="col-md-9"><span class="badge status-<?=e($order['priority'])?>"><?=e(tv($order['priority']))?></span></dd><dt class="col-md-3">Status</dt><dd class="col-md-9"><span class="badge status-<?=e(str_replace(' ','-',$order['status']))?>"><?=e(tv($order['status']))?></span></dd>
<dt class="col-md-3">Problem Description</dt><dd class="col-md-9" style="white-space:pre-wrap"><?=e($order['problem_description'])?></dd><dt class="col-md-3">Repair Notes</dt><dd class="col-md-9" style="white-space:pre-wrap"><?=e($order['repair_notes']?:'No repair notes yet.')?></dd></dl></div>
<?php require __DIR__.'/../includes/footer.php';?>
