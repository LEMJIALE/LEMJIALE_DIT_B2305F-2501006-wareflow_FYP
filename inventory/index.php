<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_login(); require_clothing_warehouse_access();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_product') {
    verify_csrf();
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $pdo->prepare("UPDATE products SET status = 'Inactive' WHERE id = ?")->execute([$productId]);
        flash('Clothing item removed from active inventory. Its stock-movement history was kept.');
    }
    header('Location: index.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$scanned = null;
if ($q !== '') {
    $scan = $pdo->prepare("SELECT p.*, l.location_code FROM products p LEFT JOIN warehouse_locations l ON l.id = p.location_id WHERE p.status = 'Active' AND (p.barcode = ? OR p.product_code = ?) LIMIT 1");
    $scan->execute([$q, $q]);
    $scanned = $scan->fetch(PDO::FETCH_ASSOC);
}
$sql = "SELECT p.*, l.location_code FROM products p LEFT JOIN warehouse_locations l ON l.id = p.location_id WHERE p.status = 'Active'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_code LIKE ? OR p.product_name LIKE ? OR p.barcode LIKE ? OR p.company_name LIKE ?)';
    $params = ["%$q%", "%$q%", "%$q%", "%$q%"];
}
$sql .= ' ORDER BY p.product_name';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$products = $statement->fetchAll(PDO::FETCH_ASSOC);
$page_title = tl('Clothing Inventory', '服装库存');
require __DIR__ . '/../includes/header.php';
$inventorySuccess = $_SESSION['inventory_success'] ?? '';
unset($_SESSION['inventory_success']);
?>
<?php if ($inventorySuccess): ?><div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert"><i class="bi bi-check-circle-fill fs-4"></i><div><b>Clothing item saved successfully.</b><br><span><?= e($inventorySuccess) ?></span></div><button class="btn-close ms-auto" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-heading"><?= e(tl('Clothing Inventory', '服装库存')) ?></h1><p class="page-subtitle mb-0"><?= e(tl('Manage clothing products, stock quantities, storage locations and barcode scanning.', '管理服装货品、库存数量、存储库位和条码扫描。')) ?></p></div><a class="btn btn-primary" href="product_form.php"><i class="bi bi-plus-lg"></i> Add Clothing Item</a></div>
<div class="panel"><form class="row g-2 mb-4" id="inventorySearchForm"><div class="col-md-7"><label class="form-label"><i class="bi bi-search me-1"></i><?= e(tl('Search Clothing', '搜索服装')) ?></label><input class="form-control" id="inventorySearchInput" autofocus name="q" value="<?= e($q) ?>" placeholder="<?= e(tl('Barcode, SKU, clothing name, or brand', '条码、SKU、服装名称或品牌')) ?>"></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-dark me-2"><?= e(tl('Search', '搜索')) ?></button><a class="btn btn-light" href="index.php"><?= e(tl('Reset', '重置')) ?></a></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="button" id="inventoryCameraButton"><i class="bi bi-camera"></i> Scan Barcode</button></div><div class="col-12 d-none" id="inventoryCameraPanel"><div class="border rounded p-2"><video id="inventoryCameraVideo" class="w-100 rounded bg-dark" style="max-height:300px;object-fit:cover" playsinline muted></video><div class="d-flex justify-content-between align-items-center mt-2"><small id="inventoryCameraStatus" class="text-muted">Requesting camera permission…</small><button class="btn btn-sm btn-outline-danger" type="button" id="inventoryStopCameraButton">Stop Camera</button></div></div></div></form><?php if ($scanned): ?><div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill fs-4 me-3"></i><div><b><?= e($scanned['product_name']) ?></b><br><span class="small">Available stock: <b><?= number_format((float) $scanned['current_quantity'], 0) ?> <?= e($scanned['unit']) ?></b> · Storage location: <?= e($scanned['location_code'] ?? '—') ?></span></div></div><?php endif; ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>SKU</th><th><?= e(tl('Clothing Item', '服装货品')) ?></th><th><?= e(tl('Brand / Company', '品牌 / 公司')) ?></th><th>Barcode</th><th><?= e(tl('Available Stock', '可用库存')) ?></th><th><?= e(tl('Storage Location', '存储库位')) ?></th><th><?= e(tl('Action', '操作')) ?></th></tr></thead><tbody><?php foreach ($products as $product): ?><tr class="<?= ($scanned && $product['id'] === $scanned['id']) ? 'table-warning' : '' ?>"><td><b><?= e($product['product_code']) ?></b></td><td><?= e($product['product_name']) ?><br><small class="text-muted"><?= e($product['category'] ?? '') ?></small></td><td><?= e($product['company_name'] ?? '—') ?></td><td><?= e($product['barcode'] ?? '—') ?></td><td><b><?= number_format((float) $product['current_quantity'], 0) ?></b> <?= e($product['unit']) ?></td><td><?= e($product['location_code'] ?? '—') ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $product['id'] ?>">View</a> <a class="btn btn-sm btn-light" href="product_form.php?id=<?= $product['id'] ?>"><?= e(tl('Edit', '编辑')) ?></a><form method="post" class="d-inline" onsubmit="return confirm('<?= e(tl('Remove this clothing item from active inventory?', '确定要从活动库存移除此服装货品吗？')) ?>');"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="remove_product"><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><button class="btn btn-sm btn-outline-danger"><?= e(tl('Remove', '移除')) ?></button></form></td></tr><?php endforeach; if (!$products): ?><tr><td colspan="7" class="empty"><?= e(tl('No active clothing items found.', '未找到活动服装货品。')) ?></td></tr><?php endif; ?></tbody></table></div></div>
<script>
const inventoryCameraButton = document.getElementById('inventoryCameraButton'), inventoryCameraPanel = document.getElementById('inventoryCameraPanel'), inventoryCameraVideo = document.getElementById('inventoryCameraVideo'), inventoryCameraStatus = document.getElementById('inventoryCameraStatus'), inventorySearchInput = document.getElementById('inventorySearchInput');
let inventoryCameraStream = null, inventoryCameraFrame = null, inventoryBarcodeDetector = null;
function stopInventoryCamera() { if (inventoryCameraFrame) cancelAnimationFrame(inventoryCameraFrame); inventoryCameraFrame = null; if (inventoryCameraStream) inventoryCameraStream.getTracks().forEach(track => track.stop()); inventoryCameraStream = null; inventoryCameraVideo.srcObject = null; inventoryCameraPanel.classList.add('d-none'); }
async function detectInventoryBarcode() { if (!inventoryCameraStream || !inventoryBarcodeDetector) return; try { const results = await inventoryBarcodeDetector.detect(inventoryCameraVideo); if (results.length) { inventorySearchInput.value = results[0].rawValue; stopInventoryCamera(); document.getElementById('inventorySearchForm').submit(); return; } } catch (error) { inventoryCameraStatus.textContent = 'Keep the barcode clearly inside the camera frame.'; } inventoryCameraFrame = requestAnimationFrame(detectInventoryBarcode); }
async function startInventoryCamera() { if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { inventoryCameraStatus.textContent = 'Camera access needs Android Chrome through the USB localhost connection.'; inventoryCameraPanel.classList.remove('d-none'); return; } try { inventoryCameraPanel.classList.remove('d-none'); inventoryCameraStatus.textContent = 'Requesting camera permission…'; inventoryCameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }); inventoryCameraVideo.srcObject = inventoryCameraStream; await inventoryCameraVideo.play(); if (!('BarcodeDetector' in window)) { inventoryCameraStatus.textContent = 'Camera is on, but this browser cannot read barcodes automatically.'; return; } const preferred = ['code_39', 'code_128', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code']; const supported = await BarcodeDetector.getSupportedFormats(); const formats = preferred.filter(format => supported.includes(format)); if (!formats.length) { inventoryCameraStatus.textContent = 'Camera is on, but this browser has no supported barcode format.'; return; } inventoryBarcodeDetector = new BarcodeDetector({ formats }); inventoryCameraStatus.textContent = 'Camera is on. Point the barcode at the camera.'; detectInventoryBarcode(); } catch (error) { inventoryCameraStatus.textContent = error && error.name === 'NotAllowedError' ? 'Camera permission was denied. Allow it in Chrome site settings.' : 'Unable to start camera: ' + (error.message || 'no camera is available.'); inventoryCameraPanel.classList.remove('d-none'); } }
inventoryCameraButton.addEventListener('click', startInventoryCamera); document.getElementById('inventoryStopCameraButton').addEventListener('click', stopInventoryCamera); window.addEventListener('beforeunload', stopInventoryCamera);
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
