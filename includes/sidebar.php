<?php
$role = $_SESSION['user']['role'];
$current_menu_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function menu_active(array $paths): string {
    global $current_menu_path;
    foreach ($paths as $path) {
        if (str_ends_with($current_menu_path, $path)) return 'active';
    }
    return '';
}
?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= can_access_clothing_warehouse() ? '/warehouse-maintenance/dashboard.php' : '/warehouse-maintenance/maintenance/work_orders.php' ?>"><i class="bi bi-box-seam-fill"></i><span>WARE<span>FLOW</span></span></a>
  <?php if (can_access_clothing_warehouse()): ?><p class="nav-label"><?= e(tl('CLOTHING WAREHOUSE', '服装仓库')) ?></p>
  <a class="<?= menu_active(['/dashboard.php']) ?>" href="/warehouse-maintenance/dashboard.php"><i class="bi bi-grid-1x2"></i> <?= e(t('dashboard')) ?></a>
  <a class="<?= menu_active(['/inventory/index.php', '/inventory/product_form.php', '/inventory/view.php']) ?>" href="/warehouse-maintenance/inventory/index.php"><i class="bi bi-bag-check"></i> <?= e(tl('Clothing Inventory', '服装库存')) ?></a>
  <a class="<?= menu_active(['/inventory/locations.php']) ?>" href="/warehouse-maintenance/inventory/locations.php"><i class="bi bi-geo-alt"></i> <?= e(tl('Storage Locations', '存储库位')) ?></a>
  <a class="<?= menu_active(['/inventory/movements.php']) ?>" href="/warehouse-maintenance/inventory/movements.php"><i class="bi bi-arrow-left-right"></i> <?= e(tl('Stock Movement', '库存作业')) ?></a>
  <a class="<?= menu_active(['/inventory/stock_take.php']) ?>" href="/warehouse-maintenance/inventory/stock_take.php"><i class="bi bi-clipboard-check"></i> Stock Take</a>
  <?php endif; ?>
  <p class="nav-label"><?= e(tl('MAINTENANCE OPERATIONS', '维护作业')) ?></p>
  <a class="<?= menu_active(['/equipment/index.php', '/equipment/add.php', '/equipment/edit.php', '/equipment/view.php']) ?>" href="/warehouse-maintenance/equipment/index.php"><i class="bi bi-tools"></i> <?= e(t('equipment')) ?></a>
  <a class="<?= menu_active(['/maintenance/requests.php', '/maintenance/add_request.php', '/maintenance/update_request.php']) ?>" href="/warehouse-maintenance/maintenance/requests.php"><i class="bi bi-clipboard-plus"></i> <?= e(t('requests')) ?></a>
  <a class="<?= menu_active(['/maintenance/work_orders.php', '/maintenance/view_work_order.php', '/maintenance/add_work_order.php', '/maintenance/update_work_order.php']) ?>" href="/warehouse-maintenance/maintenance/work_orders.php"><i class="bi bi-wrench-adjustable"></i> <?= e(t('work_orders')) ?></a>
  <?php if (is_system_admin()): ?><p class="nav-label">ADMIN PANEL</p><a class="<?= menu_active(['/users/index.php', '/users/add.php', '/users/edit.php', '/users/reset_password.php']) ?>" href="/warehouse-maintenance/users/index.php"><i class="bi bi-people"></i> Manage Users</a><a class="<?= menu_active(['/reports/index.php']) ?>" href="/warehouse-maintenance/reports/index.php"><i class="bi bi-bar-chart"></i> <?= e(t('reports')) ?></a><?php endif; ?>
  <div class="sidebar-bottom"><small><?= e(tl('Clothing distribution centre', '服装配送中心')) ?></small></div>
</aside>
