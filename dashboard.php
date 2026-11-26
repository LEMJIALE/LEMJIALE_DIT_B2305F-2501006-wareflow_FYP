<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/language.php';
require_clothing_warehouse_access();

function warehouse_table_exists(PDO $pdo, string $table): bool {
    $query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $query->execute([$table]);
    return (bool) $query->fetchColumn();
}
function warehouse_column_exists(PDO $pdo, string $table, string $column): bool {
    $query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $query->execute([$table, $column]);
    return (bool) $query->fetchColumn();
}
function warehouse_value(PDO $pdo, string $sql, array $params = []) {
    try {
        $query = $pdo->prepare($sql);
        $query->execute($params);
        return $query->fetchColumn() ?? 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

$hasProducts = warehouse_table_exists($pdo, 'products');
$hasMovements = warehouse_table_exists($pdo, 'stock_movements');
$hasProductCost = $hasProducts && warehouse_column_exists($pdo, 'products', 'unit_cost');
$hasProductPrice = $hasProducts && warehouse_column_exists($pdo, 'products', 'selling_price');
$hasMinimumStock = $hasProducts && warehouse_column_exists($pdo, 'products', 'minimum_stock');
$hasMovementCost = $hasMovements && warehouse_column_exists($pdo, 'stock_movements', 'unit_cost');
$hasMovementPrice = $hasMovements && warehouse_column_exists($pdo, 'stock_movements', 'selling_price');
$salesMovementCondition = "m.movement_type IN ('Outbound', 'Picking', 'Outbound - Retail Store Delivery', 'Outbound - Customer Order Picking')";

$skuCount = $hasProducts ? warehouse_value($pdo, "SELECT COUNT(*) FROM products WHERE status = 'Active'") : 0;
$stockUnits = $hasProducts ? warehouse_value($pdo, 'SELECT COALESCE(SUM(current_quantity), 0) FROM products') : 0;
$inventoryValue = ($hasProducts && $hasProductCost) ? warehouse_value($pdo, 'SELECT COALESCE(SUM(current_quantity * unit_cost), 0) FROM products') : 0;
$lowStock = $hasProducts ? warehouse_value($pdo, $hasMinimumStock ? "SELECT COUNT(*) FROM products WHERE status = 'Active' AND current_quantity <= minimum_stock" : "SELECT COUNT(*) FROM products WHERE status = 'Active' AND current_quantity <= 10") : 0;
$outgoingUnits = $hasMovements ? warehouse_value($pdo, "SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE movement_type IN ('Outbound', 'Picking') OR movement_type LIKE 'Outbound - %'") : 0;
$lowStockItems = [];
if ($hasProducts) {
    try {
        $lowStockItems = $pdo->query($hasMinimumStock
            ? "SELECT product_code, product_name, current_quantity, minimum_stock FROM products WHERE status = 'Active' AND current_quantity <= minimum_stock ORDER BY (minimum_stock - current_quantity) DESC, product_name LIMIT 8"
            : "SELECT product_code, product_name, current_quantity, 10 AS minimum_stock FROM products WHERE status = 'Active' AND current_quantity <= 10 ORDER BY current_quantity ASC, product_name LIMIT 8"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) { $lowStockItems = []; }
}

$priceExpression = $hasMovementPrice ? 'm.selling_price' : ($hasProductPrice ? 'p.selling_price' : '0');
$costExpression = $hasMovementCost ? 'm.unit_cost' : ($hasProductCost ? 'p.unit_cost' : '0');
$salesRevenue = 0;
$grossProfit = 0;
$salesRevenueLast7Days = 0;
$grossProfitLast7Days = 0;
$recentSales = [];
$dailySales = [];
$profitFrom = $_GET['profit_from'] ?? date('Y-m-01');
$profitTo = $_GET['profit_to'] ?? date('Y-m-d');
$showProfitPanel = isset($_GET['profit_from']) || isset($_GET['profit_to']);
if (!strtotime($profitFrom) || !strtotime($profitTo)) {
    $profitFrom = date('Y-m-01');
    $profitTo = date('Y-m-d');
}
if ($profitFrom > $profitTo) {
    [$profitFrom, $profitTo] = [$profitTo, $profitFrom];
}
$chartFrom = $showProfitPanel ? $profitFrom : date('Y-m-d', strtotime('-6 days'));
$chartTo = $showProfitPanel ? $profitTo : date('Y-m-d');
if ($hasMovements && $hasProducts) {
    $salesRevenue = warehouse_value($pdo, "SELECT COALESCE(SUM(m.quantity * $priceExpression), 0) FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition");
    $grossProfit = warehouse_value($pdo, "SELECT COALESCE(SUM(m.quantity * ($priceExpression - $costExpression)), 0) FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition");
    $salesRevenueLast7Days = warehouse_value($pdo, "SELECT COALESCE(SUM(m.quantity * $priceExpression), 0) FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition AND m.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
    $grossProfitLast7Days = warehouse_value($pdo, "SELECT COALESCE(SUM(m.quantity * ($priceExpression - $costExpression)), 0) FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition AND m.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
    try {
        $recentSales = $pdo->query("SELECT m.movement_code, m.movement_type, m.quantity, m.created_at, p.product_code, p.product_name, ($priceExpression) AS unit_price, (m.quantity * $priceExpression) AS revenue FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition ORDER BY m.created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        $dailySalesStatement = $pdo->prepare("SELECT DATE(m.created_at) AS sale_date, COALESCE(SUM(m.quantity * ($priceExpression - $costExpression)), 0) AS profit FROM stock_movements m JOIN products p ON p.id = m.product_id WHERE $salesMovementCondition AND DATE(m.created_at) BETWEEN ? AND ? GROUP BY DATE(m.created_at) ORDER BY sale_date");
        $dailySalesStatement->execute([$chartFrom, $chartTo]);
        $dailySales = $dailySalesStatement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        $recentSales = [];
        $dailySales = [];
    }
}

$dailyProfitByDate = [];
foreach ($dailySales as $sale) { $dailyProfitByDate[$sale['sale_date']] = (float) $sale['profit']; }
$chartLabels = [];
$chartData = [];
$chartCursor = new DateTimeImmutable($chartFrom);
$chartEnd = new DateTimeImmutable($chartTo);
while ($chartCursor <= $chartEnd) {
    $date = $chartCursor->format('Y-m-d');
    $chartLabels[] = $chartCursor->format('d M');
    $chartData[] = $dailyProfitByDate[$date] ?? 0;
    $chartCursor = $chartCursor->modify('+1 day');
}
$canViewProfitReport = in_array($_SESSION['user']['role'] ?? '', ['Admin', 'Warehouse Staff'], true);
$profitRows = [];
$inboundCost = 0;
$reportRevenue = 0;
$costOfSales = 0;
if ($canViewProfitReport && $hasMovements && $hasProducts) {
    try {
        $profitStatement = $pdo->prepare("SELECT m.movement_code, m.movement_type, m.quantity, m.created_at, p.product_code, p.product_name, ($costExpression) AS unit_cost, ($priceExpression) AS selling_price FROM stock_movements m JOIN products p ON p.id=m.product_id WHERE DATE(m.created_at) BETWEEN ? AND ? ORDER BY m.created_at DESC");
        $profitStatement->execute([$profitFrom, $profitTo]);
        $profitRows = $profitStatement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($profitRows as $profitRow) {
            if ($profitRow['movement_type'] === 'Inbound' || str_starts_with($profitRow['movement_type'], 'Inbound - ')) $inboundCost += (float) $profitRow['quantity'] * (float) $profitRow['unit_cost'];
            if (in_array($profitRow['movement_type'], ['Outbound', 'Picking', 'Outbound - Retail Store Delivery', 'Outbound - Customer Order Picking'], true)) {
                $reportRevenue += (float) $profitRow['quantity'] * (float) $profitRow['selling_price'];
                $costOfSales += (float) $profitRow['quantity'] * (float) $profitRow['unit_cost'];
            }
        }
    } catch (PDOException $exception) { $profitRows = []; }
}
$reportProfit = $reportRevenue - $costOfSales;
$netCashFlow = $reportRevenue - $inboundCost;
$page_title = tl('Sales Dashboard', '销售仪表板');
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="page-heading"><?= e(tl('Sales & Inventory Dashboard', '销售与库存仪表板')) ?></h1><p class="page-subtitle mb-0"><?= e(tl('Clothing warehouse overview: stock availability, outbound sales and estimated gross profit.', '服装仓库总览：可用库存、出库销售和预计毛利润。')) ?></p></div><a class="btn btn-primary" href="/warehouse-maintenance/inventory/movements.php"><i class="bi bi-arrow-left-right"></i> <?= e(tl('Record Stock Movement', '记录库存作业')) ?></a></div>

<div class="row g-3 mb-4">
  <?php $cards = [
    [tl('Clothing SKUs', '服装 SKU 数量'), $skuCount, 'bi-bag-check', '/warehouse-maintenance/inventory/index.php'],
    [tl('Units in Stock', '库存件数'), number_format((float) $stockUnits, 0), 'bi-boxes', '/warehouse-maintenance/inventory/index.php'],
    [tl('Inventory Value', '库存价值'), 'RM ' . number_format((float) $inventoryValue, 2), 'bi-box-seam', '/warehouse-maintenance/inventory/index.php'],
    [tl('Total Sales Revenue', '总销售收入'), 'RM ' . number_format((float) $salesRevenue, 2), 'bi-cash-coin', '#salesSummary'],
    [tl('Total Estimated Gross Profit', '总预计毛利润'), 'RM ' . number_format((float) $grossProfit, 2), 'bi-graph-up-arrow', '#salesSummary'],
    [tl('Outbound Units', '出库件数'), number_format((float) $outgoingUnits, 0), 'bi-truck', '/warehouse-maintenance/inventory/movements.php'],
    [tl('Low Stock Items', '低库存货品'), $lowStock, 'bi-exclamation-triangle', '#lowStockDetails', true]
  ]; foreach ($cards as $card): ?>
    <div class="col-6 col-md-4 col-xl"><?php if (!empty($card[4])): ?><button class="border-0 bg-transparent p-0 text-start w-100 h-100" type="button" data-bs-toggle="collapse" data-bs-target="#lowStockDetails" aria-controls="lowStockDetails"><div class="metric-card h-100"><div class="d-flex justify-content-between"><div><div class="metric-number"><?= e((string) $card[1]) ?></div><div class="metric-label"><?= e($card[0]) ?></div></div><div class="metric-icon"><i class="bi <?= e($card[2]) ?>"></i></div></div></div></button><?php else: ?><a class="text-decoration-none text-reset d-block h-100" href="<?= e($card[3]) ?>"><div class="metric-card h-100"><div class="d-flex justify-content-between"><div><div class="metric-number <?= $card[0] === tl('Total Estimated Gross Profit', '总预计毛利润') && $grossProfit > 0 ? 'text-success' : '' ?>"><?= e((string) $card[1]) ?></div><div class="metric-label"><?= e($card[0]) ?></div></div><div class="metric-icon"><i class="bi <?= e($card[2]) ?>"></i></div></div></div></a><?php endif; ?></div>
  <?php endforeach; ?>
</div>

<div class="collapse mb-4" id="lowStockDetails"><div class="alert <?= $lowStockItems ? 'alert-warning border-warning' : 'alert-success border-success' ?> d-flex align-items-start gap-3 mb-0" role="alert"><i class="bi <?= $lowStockItems ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?> fs-4"></i><div class="flex-grow-1"><b><?= e($lowStockItems ? tl('Low stock items', '低库存货品') : tl('No low stock items', '没有低库存货品')) ?></b><?php if ($lowStockItems): ?><div class="small mb-2"><?= e(tl('These clothing items have reached or fallen below their minimum stock level. Please arrange replenishment.', '以下服装货品已达到或低于最低库存量，请安排补货。')) ?></div><div class="d-flex flex-wrap gap-2"><?php foreach ($lowStockItems as $lowItem): ?><span class="badge text-bg-warning border border-warning-subtle text-dark p-2"><?= e($lowItem['product_code']) ?> — <b><?= e($lowItem['product_name']) ?></b>: <?= number_format((float) $lowItem['current_quantity'], 0) ?> / min <?= number_format((float) $lowItem['minimum_stock'], 0) ?></span><?php endforeach; ?></div><?php else: ?><div class="small mt-1"><?= e(tl('All active clothing items are above their minimum stock level.', '所有启用中的服装货品都高于最低库存量。')) ?></div><?php endif; ?></div><a class="btn btn-sm btn-outline-dark" href="/warehouse-maintenance/inventory/index.php"><?= e(tl('View inventory', '查看库存')) ?></a></div></div>

<div class="row g-4" id="salesSummary"><div class="col-lg-5"><div class="panel h-100"><div class="d-flex justify-content-between mb-3"><h2 class="h6 mb-0"><?= e($showProfitPanel ? tl('Estimated Gross Profit — Selected Period', '预计毛利润 — 所选期间') : tl('Estimated Gross Profit — Last 7 Days', '预计毛利润 — 最近 7 天')) ?></h2><?php if ($canViewProfitReport): ?><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#profitReportPanel" aria-expanded="<?= $showProfitPanel ? 'true' : 'false' ?>" aria-controls="profitReportPanel"><i class="bi bi-cash-coin"></i> <?= e(tl('Profit report', '利润报告')) ?></button><?php endif; ?></div><canvas id="salesChart"></canvas><p class="text-muted small mt-3 mb-0"><?= e($showProfitPanel ? tl('Chart period: ', '图表期间：') . $chartFrom . ' to ' . $chartTo : tl('Profit is calculated from sales price minus unit cost for Retail Store Delivery and Customer Order Picking.', '利润根据「门店配送」和「客户订单拣货」的售价减去单位成本计算。')) ?></p></div></div><div class="col-lg-7"><div class="panel"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 mb-0"><?= e(tl('Recent Clothing Sales', '最近服装销售')) ?></h2><a class="small" href="/warehouse-maintenance/inventory/movements.php"><?= e(tl('View movements', '查看库存作业')) ?></a></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th><?= e(tl('Movement', '作业编号')) ?></th><th><?= e(tl('Clothing Item', '服装货品')) ?></th><th><?= e(tl('Type', '类型')) ?></th><th><?= e(tl('Qty', '数量')) ?></th><th><?= e(tl('Revenue', '收入')) ?></th></tr></thead><tbody><?php foreach ($recentSales as $sale): ?><tr><td><b><?= e($sale['movement_code']) ?></b><br><small class="text-muted"><?= e($sale['created_at']) ?></small></td><td><?= e($sale['product_name']) ?><br><small class="text-muted"><?= e($sale['product_code']) ?></small></td><td><span class="badge text-bg-primary"><?= e(tv($sale['movement_type'])) ?></span></td><td><?= number_format((float) $sale['quantity'], 0) ?></td><td class="text-success">RM <?= number_format((float) $sale['revenue'], 2) ?></td></tr><?php endforeach; if (!$recentSales): ?><tr><td colspan="5" class="empty"><?= e(tl('No sales movements yet. Record Retail Store Delivery or Customer Order Picking to see sales here.', '暂时没有销售作业。请记录「门店配送」或「客户订单拣货」。')) ?></td></tr><?php endif; ?></tbody></table></div></div></div></div>

<?php if ($canViewProfitReport): ?>
<div class="collapse <?= $showProfitPanel ? 'show' : '' ?> mt-4" id="profitReportPanel"><div class="panel border-start border-4 border-warning">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1"><i class="bi bi-cash-stack text-warning"></i> Inventory Profit Report</h2><p class="text-muted small mb-0">Inbound purchases are shown as cash paid. Gross profit is recognised only when clothing is sold.</p></div><button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#profitReportPanel">Close</button></div>
  <form class="row g-2 align-items-end mb-4" method="get"><div class="col-md-3"><label class="form-label">From</label><input class="form-control" type="date" name="profit_from" value="<?= e($profitFrom) ?>"></div><div class="col-md-3"><label class="form-label">To</label><input class="form-control" type="date" name="profit_to" value="<?= e($profitTo) ?>"></div><div class="col-md-2"><button class="btn btn-dark w-100">Apply</button></div><div class="col-md-4 text-md-end"><span class="text-muted small">Current period: <?= e($profitFrom) ?> to <?= e($profitTo) ?></span></div></form>
  <div class="row g-3 mb-4"><div class="col-md-3"><div class="metric-card"><div class="metric-number text-danger">- RM <?= number_format($inboundCost, 2) ?></div><div class="metric-label">Cash Paid for Inbound Stock</div></div></div><div class="col-md-3"><div class="metric-card"><div class="metric-number">RM <?= number_format($reportRevenue, 2) ?></div><div class="metric-label">Sales Revenue</div></div></div><div class="col-md-3"><div class="metric-card"><div class="metric-number <?= $reportProfit >= 0 ? 'text-success' : 'text-danger' ?>">RM <?= number_format($reportProfit, 2) ?></div><div class="metric-label">Estimated Gross Profit</div></div></div><div class="col-md-3"><div class="metric-card"><div class="metric-number <?= $netCashFlow >= 0 ? 'text-success' : 'text-danger' ?>"><?= $netCashFlow < 0 ? '- ' : '' ?>RM <?= number_format(abs($netCashFlow), 2) ?></div><div class="metric-label">Net Cash Flow</div></div></div></div>
  <div class="table-responsive" style="max-height:420px;overflow-y:auto"><table class="table table-hover mb-0"><thead class="sticky-top"><tr><th>Date</th><th>Clothing Item</th><th>Type</th><th>Qty</th><th>Unit Cost</th><th>Selling Price</th><th>Sales Revenue</th><th>Cash In / Out</th><th>Gross Profit</th></tr></thead><tbody><?php foreach ($profitRows as $profitRow): $isSale = in_array($profitRow['movement_type'], ['Outbound', 'Picking', 'Outbound - Retail Store Delivery', 'Outbound - Customer Order Picking'], true); $isInbound = $profitRow['movement_type'] === 'Inbound' || str_starts_with($profitRow['movement_type'], 'Inbound - '); $rowRevenue = $isSale ? (float) $profitRow['quantity'] * (float) $profitRow['selling_price'] : 0; $rowCashFlow = $isSale ? $rowRevenue : ($isInbound ? -((float) $profitRow['quantity'] * (float) $profitRow['unit_cost']) : 0); $rowProfit = $isSale ? $rowRevenue - ((float) $profitRow['quantity'] * (float) $profitRow['unit_cost']) : 0; ?><tr><td><?= e($profitRow['created_at']) ?></td><td><b><?= e($profitRow['product_name']) ?></b><br><small class="text-muted"><?= e($profitRow['product_code']) ?></small></td><td><?= e(tv($profitRow['movement_type'])) ?></td><td><?= number_format((float) $profitRow['quantity'], 0) ?></td><td>RM <?= number_format((float) $profitRow['unit_cost'], 2) ?></td><td>RM <?= number_format((float) $profitRow['selling_price'], 2) ?></td><td>RM <?= number_format($rowRevenue, 2) ?></td><td class="<?= $rowCashFlow > 0 ? 'text-success' : ($rowCashFlow < 0 ? 'text-danger' : '') ?>"><?= $rowCashFlow < 0 ? '- ' : '' ?>RM <?= number_format(abs($rowCashFlow), 2) ?></td><td class="<?= $rowProfit >= 0 ? 'text-success' : 'text-danger' ?>">RM <?= number_format($rowProfit, 2) ?></td></tr><?php endforeach; if (!$profitRows): ?><tr><td colspan="9" class="empty">No stock movements found for this date range.</td></tr><?php endif ?></tbody></table></div>
</div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>new Chart(document.getElementById('salesChart'),{type:'line',data:{labels:<?= json_encode($chartLabels) ?>,datasets:[{label:<?= json_encode(tl('Daily Gross Profit (RM)', '每日毛利润（RM）')) ?>,data:<?= json_encode($chartData) ?>,borderColor:'#28a977',backgroundColor:'rgba(40,169,119,.14)',fill:true,tension:.35,pointRadius:4,pointHoverRadius:6,pointBackgroundColor:'#28a977',borderWidth:3}]},options:{plugins:{legend:{display:false},tooltip:{callbacks:{label:function(context){return 'RM '+Number(context.raw).toFixed(2);}}}},scales:{y:{beginAtZero:true,ticks:{callback:function(value){return 'RM '+value;}}}}}});</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
