<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_roles('Admin');

$page_title = 'Equipment Reports';
$equipment = $pdo->query('SELECT status, COUNT(*) total FROM equipment GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$requests = $pdo->query('SELECT status, COUNT(*) total FROM maintenance_requests GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$priorities = $pdo->query('SELECT priority, COUNT(*) total FROM maintenance_requests GROUP BY priority')->fetchAll(PDO::FETCH_KEY_PAIR);
$total = (float) $pdo->query('SELECT COALESCE(SUM(total_cost), 0) FROM maintenance_records')->fetchColumn();

// Always show the last six calendar months, including months with no maintenance cost.
$monthlyRows = $pdo->query("SELECT DATE_FORMAT(maintenance_date, '%Y-%m') AS month_key, COALESCE(SUM(total_cost), 0) AS total_cost FROM maintenance_records WHERE maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY DATE_FORMAT(maintenance_date, '%Y-%m') ORDER BY month_key")->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyLabels = [];
$monthlyCosts = [];
for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
    $monthKey = date('Y-m', strtotime("first day of -$monthsAgo month"));
    $monthlyLabels[] = date('M Y', strtotime($monthKey . '-01'));
    $monthlyCosts[] = (float) ($monthlyRows[$monthKey] ?? 0);
}

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between mb-4">
  <div><h1 class="page-heading"><?= e(t('reports')) ?></h1><p class="page-subtitle mb-0"><?= e(t('report_summary')) ?></p></div>
  <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> <?= e(t('print_report')) ?></button>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="metric-card"><div class="metric-number">RM <?= number_format($total, 2) ?></div><div class="metric-label"><?= e(t('total_maintenance_cost')) ?></div></div></div>
  <div class="col-md-4"><div class="metric-card"><div class="metric-number"><?= array_sum($requests) ?></div><div class="metric-label"><?= e(t('total_requests')) ?></div></div></div>
  <div class="col-md-4"><div class="metric-card"><div class="metric-number"><?= $equipment['Breakdown'] ?? 0 ?></div><div class="metric-label"><?= e(t('breakdown_equipment')) ?></div></div></div>
</div>

<div class="row g-4">
  <div class="col-md-6"><div class="panel"><h2 class="h6">Monthly Maintenance Cost</h2><canvas id="maintenanceCostChart"></canvas><p class="text-muted small mt-3 mb-0">Total maintenance cost for each month. Months without a record show RM 0.</p></div></div>
  <div class="col-md-6"><div class="panel"><h2 class="h6"><?= e(t('requests_by_priority')) ?></h2><canvas id="priorityChart"></canvas></div></div>
  <div class="col-12"><div class="panel"><h2 class="h6"><?= e(t('requests_by_status')) ?></h2><div class="row"><?php foreach ($requests as $status => $count): ?><div class="col-md-3"><p class="mb-1 text-muted small"><?= e(tv($status)) ?></p><h3><?= $count ?></h3></div><?php endforeach; ?></div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartColours = ['#ff9a1b', '#42b883', '#e66c6c', '#7c8fa3', '#f6bd60'];
new Chart(document.getElementById('maintenanceCostChart'), {
  type: 'bar',
  data: { labels: <?= json_encode($monthlyLabels) ?>, datasets: [{ label: 'Maintenance Cost (RM)', data: <?= json_encode($monthlyCosts) ?>, backgroundColor: '#ff9a1b', borderRadius: 6 }] },
  options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => 'RM ' + Number(context.raw).toFixed(2) } } }, scales: { y: { beginAtZero: true, ticks: { callback: value => 'RM ' + value } } } }
});
new Chart(document.getElementById('priorityChart'), {
  type: 'doughnut',
  data: { labels: <?= json_encode(array_map('tv', array_keys($priorities))) ?>, datasets: [{ data: <?= json_encode(array_values($priorities)) ?>, backgroundColor: chartColours }] }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
