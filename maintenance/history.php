<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$id = (int) ($_GET['equipment_id'] ?? 0);
header('Location: /warehouse-maintenance/equipment/' . ($id > 0 ? 'view.php?id=' . $id : 'index.php'));
exit;
