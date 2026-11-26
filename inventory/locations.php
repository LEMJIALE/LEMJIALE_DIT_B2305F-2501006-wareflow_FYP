<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(); require_clothing_warehouse_access();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'add_location';
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'Active';
        if (in_array($status, ['Active', 'Blocked', 'Inactive'], true)) {
            $pdo->prepare('UPDATE warehouse_locations SET status = ? WHERE id = ?')->execute([$status, (int) $_POST['location_id']]);
            flash('Storage location status updated.');
        }
        header('Location: locations.php');
        exit;
    }
    if ($action === 'delete_location') {
        $locationId = (int) $_POST['location_id'];
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE location_id = ?');
        $check->execute([$locationId]);
        if ((int) $check->fetchColumn() > 0) {
            flash('This location still contains products. Move the products first before removing it.');
        } else {
            $pdo->prepare('DELETE FROM warehouse_locations WHERE id = ?')->execute([$locationId]);
            flash('Storage location removed.');
        }
        header('Location: locations.php');
        exit;
    }
    $capacity = (int) ($_POST['capacity'] ?? 0);
    if ($capacity <= 300) {
        flash('Capacity must be greater than 300 pcs.');
        header('Location: locations.php');
        exit;
    }
    $pdo->prepare('INSERT INTO warehouse_locations(location_code,zone_name,rack_number,shelf_number,capacity,status) VALUES(?,?,?,?,?,?)')->execute([
        trim($_POST['location_code']), trim($_POST['zone_name']), trim($_POST['rack_number']) ?: null, trim($_POST['shelf_number']) ?: null, $capacity, $_POST['status'],
    ]);
    flash('Storage location added.');
    header('Location: locations.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT l.*, COUNT(p.id) AS product_count, COALESCE(SUM(p.current_quantity),0) AS total_quantity FROM warehouse_locations l LEFT JOIN products p ON p.location_id=l.id AND p.status='Active'";
$params = [];
if ($q !== '') {
    $sql .= ' WHERE l.location_code LIKE ? OR l.zone_name LIKE ? OR l.rack_number LIKE ? OR l.shelf_number LIKE ? OR p.product_code LIKE ? OR p.product_name LIKE ?';
    $params = array_fill(0, 6, "%$q%");
}
$sql .= ' GROUP BY l.id ORDER BY l.location_code';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$locations = $statement->fetchAll(PDO::FETCH_ASSOC);
$itemsByLocation = [];
$items = $pdo->query("SELECT product_code,product_name,colour,size_label,current_quantity,unit,location_id FROM products WHERE status='Active' ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($items as $item) $itemsByLocation[$item['location_id']][] = $item;
$page_title = 'Storage Locations';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="page-heading">Storage Locations</h1><p class="page-subtitle mb-0">Click a location to expand and see its stored clothing items.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#locationModal"><i class="bi bi-plus-lg"></i> Add Storage Location</button></div>
<div class="panel mb-4"><form class="row g-2"><div class="col-md-8"><label class="form-label">Search Location or Clothing Item</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Location code, zone, rack, shelf, SKU, or clothing name"></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-dark me-2"><i class="bi bi-search"></i> Search</button><a class="btn btn-light" href="locations.php">Reset</a></div></form></div>
<div class="panel table-responsive"><table class="table table-hover"><thead><tr><th>Storage Location</th><th>Zone / Rack / Shelf</th><th>Capacity</th><th>Clothing Items</th><th>Total Stock</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($locations as $location):
    $locationItems = $itemsByLocation[$location['id']] ?? [];
    $statusBadge = ['Active' => 'success', 'Blocked' => 'danger', 'Inactive' => 'secondary'][$location['status']] ?? 'secondary';
?>
<tr><td><b class="fs-6"><?= e($location['location_code']) ?></b><br><button class="btn btn-sm btn-outline-primary mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#location-<?= $location['id'] ?>"><i class="bi bi-chevron-down"></i> View Clothing</button></td><td><?= e($location['zone_name']) ?><br><small class="text-muted">Rack <?= e($location['rack_number'] ?? '—') ?> · Shelf <?= e($location['shelf_number'] ?? '—') ?></small></td><td><?= number_format((int) $location['capacity']) ?> pcs</td><td><b><?= (int) $location['product_count'] ?> item(s)</b></td><td><b><?= number_format((float) $location['total_quantity'],0) ?></b> pcs</td><td><span class="badge text-bg-<?= $statusBadge ?> px-3 py-2"><?= e($location['status']) ?></span></td><td><div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStatusModal" data-location-id="<?= $location['id'] ?>" data-location-code="<?= e($location['location_code']) ?>" data-location-status="<?= e($location['status']) ?>"><i class="bi bi-pencil-square"></i> Edit</button><form method="post" onsubmit="return confirm('Remove this empty storage location?');"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete_location"><input type="hidden" name="location_id" value="<?= $location['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></div></td></tr>
<tr><td colspan="7" class="p-0 border-0"><div class="collapse" id="location-<?= $location['id'] ?>"><div class="p-3 bg-light border-top border-bottom"><h2 class="h6 mb-3"><i class="bi bi-boxes text-primary"></i> Clothing stored in <?= e($location['location_code']) ?></h2><?php if ($locationItems): ?><div class="row g-2"><?php foreach ($locationItems as $item): ?><div class="col-md-6 col-xl-4"><div class="border rounded bg-white p-3 h-100"><div class="fw-bold fs-6 text-dark"><?= e($item['product_name']) ?></div><div class="text-primary fw-semibold small mb-2"><?= e($item['product_code']) ?></div><div class="small text-muted">Colour: <b><?= e($item['colour'] ?: '—') ?></b> · Size: <b><?= e($item['size_label'] ?: '—') ?></b></div><div class="mt-2"><span class="badge text-bg-success fs-6"><?= number_format((float) $item['current_quantity'],0) ?> <?= e($item['unit']) ?></span></div></div></div><?php endforeach ?></div><?php else: ?><p class="text-muted mb-0">No active clothing items are stored in this location.</p><?php endif ?></div></div></td></tr>
<?php endforeach; if (!$locations): ?><tr><td colspan="7" class="empty">No matching storage locations or clothing items found.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Location Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="location_id" id="editLocationId"><p class="mb-3">Storage location: <b id="editLocationCode"></b></p><label class="form-label" for="editLocationStatus">Status</label><select class="form-select" name="status" id="editLocationStatus"><option>Active</option><option>Blocked</option><option>Inactive</option></select><div class="form-text">Active is green, Blocked is red, and Inactive is grey.</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Status</button></div></form></div></div>
<div class="modal fade" id="locationModal"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Add Storage Location</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><label class="form-label">Location Code</label><input class="form-control mb-2" name="location_code" placeholder="A-02-01" required><label class="form-label">Zone</label><input class="form-control mb-2" name="zone_name" required><label class="form-label">Rack</label><input class="form-control mb-2" name="rack_number"><label class="form-label">Shelf</label><input class="form-control mb-2" name="shelf_number"><label class="form-label">Capacity (pcs)</label><input class="form-control mb-2" type="number" min="301" step="1" name="capacity" value="500" required><div class="form-text mb-2">Capacity must be more than 300 pcs.</div><label class="form-label">Status</label><select class="form-select" name="status"><option>Active</option><option>Blocked</option><option>Inactive</option></select></div><div class="modal-footer"><button class="btn btn-primary">Save Storage Location</button></div></form></div></div>
<script>document.getElementById('editStatusModal').addEventListener('show.bs.modal', function (event) { const button = event.relatedTarget; document.getElementById('editLocationId').value = button.dataset.locationId; document.getElementById('editLocationCode').textContent = button.dataset.locationCode; document.getElementById('editLocationStatus').value = button.dataset.locationStatus; });</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
