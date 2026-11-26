<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/capacity_guard.php';
require_login();
require_clothing_warehouse_access();

function product_column_exists(PDO $pdo, string $column): bool {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $statement->execute(['products', $column]);
    return (bool) $statement->fetchColumn();
}

$enhancementColumns = ['minimum_stock', 'maximum_stock', 'material_composition', 'fit_type', 'weight_grams', 'package_length_cm', 'package_width_cm', 'package_height_cm', 'image_path'];
$enhancementsReady = !array_filter($enhancementColumns, fn ($column) => !product_column_exists($pdo, $column));
$id = (int) ($_GET['id'] ?? 0);
$item = [];
if ($id) {
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $statement->execute([$id]);
    $item = $statement->fetch(PDO::FETCH_ASSOC) ?: exit('Product not found.');
}

$categories = [
    'Tops' => ['T-Shirt', 'Polo Shirt', 'Shirt', 'Blouse', 'Tank Top', 'Sweater', 'Hoodie'],
    'Bottoms' => ['Jeans', 'Trousers', 'Shorts', 'Skirt', 'Leggings'],
    'Dresses' => ['Casual Dress', 'Formal Dress', 'Maxi Dress'],
    'Outerwear' => ['Jacket', 'Coat', 'Cardigan', 'Windbreaker'],
    'Underwear' => ['T-Shirt Bra', 'Briefs', 'Socks', 'Undershirt'],
    'Activewear' => ['Sports T-Shirt', 'Sports Shorts', 'Tracksuit', 'Yoga Wear'],
    'Uniforms' => ['Polo Uniform', 'Safety Uniform', 'Corporate Shirt', 'Apron'],
    'Footwear' => ['Sneakers', 'Sandals', 'Boots'],
    'Accessories' => ['Cap', 'Belt', 'Bag', 'Scarf'],
];
// Reuse custom categories and garment types from previously saved products.
foreach ($pdo->query("SELECT DISTINCT category, garment_type FROM products WHERE category IS NOT NULL AND category <> ''")->fetchAll(PDO::FETCH_ASSOC) as $savedVariant) {
    $savedCategory = $savedVariant['category'];
    $normalisedCategory = strtolower(preg_replace('/[^a-z]/i', '', $savedCategory));
    if (in_array($normalisedCategory, ['living', 'liveliving', 'homeliving', 'electronics', 'electronic', 'eletronics'], true) && ($item['category'] ?? '') !== $savedCategory) continue;
    if (!isset($categories[$savedCategory])) $categories[$savedCategory] = [];
    if ($savedVariant['garment_type'] && !in_array($savedVariant['garment_type'], $categories[$savedCategory], true)) $categories[$savedCategory][] = $savedVariant['garment_type'];
}
$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size'];
$colours = ['Black', 'White', 'Grey', 'Navy Blue', 'Blue', 'Red', 'Green', 'Beige', 'Brown', 'Pink', 'Yellow', 'Multi-colour'];
$targetGroups = ['Men', 'Women', 'Kids', 'Unisex'];
$fitTypes = ['Regular Fit', 'Slim Fit', 'Relaxed Fit', 'Oversized', 'Stretch Fit'];
$units = ['pcs'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['category'] ?? '') === '__other__') {
        $_POST['category'] = trim($_POST['custom_category'] ?? '');
        $_POST['garment_type'] = trim($_POST['custom_garment_type'] ?? '');
        if ($_POST['category'] === '' || $_POST['category'] === '__other__' || $_POST['garment_type'] === '') exit('Please enter a category name and garment type.');
        if (strlen($_POST['category']) > 80 || strlen($_POST['garment_type']) > 80) exit('Category and garment type must be at most 80 characters.');
    }
    $minimumStock = max(0, (int) ($_POST['minimum_stock'] ?? 10));
    $maximumStock = trim($_POST['maximum_stock'] ?? '') === '' ? null : max(0, (int) $_POST['maximum_stock']);
    if ($maximumStock !== null && $maximumStock < $minimumStock) exit('Maximum stock cannot be lower than minimum stock.');

    $locationId = (int) ($_POST['location_id'] ?? 0) ?: null;
    $baseData = [
        trim($_POST['product_code']),
        trim($_POST['product_name']),
        trim($_POST['barcode']) ?: null,
        trim($_POST['category']) ?: null,
        trim($_POST['company_name']) ?: null,
        trim($_POST['brand']) ?: null,
        trim($_POST['garment_type']) ?: null,
        $_POST['target_group'] ?: null,
        trim($_POST['size_label']) ?: null,
        trim($_POST['colour']) ?: null,
        'pcs',
        (float) $_POST['unit_cost'],
        (float) $_POST['selling_price'],
        $locationId,
        trim($_POST['description']) ?: null,
        trim($_POST['company_contact'] ?? '') ?: null,
    ];

    if ($baseData[0] === '') exit('SKU / Product Code is required. Use Generate SKU if you need one.');
    $skuCheck = $pdo->prepare('SELECT COUNT(*) FROM products WHERE product_code = ? AND id <> ?');
    $skuCheck->execute([$baseData[0], $id]);
    if ((int) $skuCheck->fetchColumn() > 0) exit('This SKU already exists. Click Generate SKU to create a new one.');
    if ($baseData[2] !== null) {
        $barcodeCheck = $pdo->prepare('SELECT COUNT(*) FROM products WHERE barcode = ? AND id <> ?');
        $barcodeCheck->execute([$baseData[2], $id]);
        if ((int) $barcodeCheck->fetchColumn() > 0) exit('This barcode already exists. Click Generate Barcode to create a new one.');
    }

    try {
        $pdo->beginTransaction();
        $successMessage = '';
        if ($id) {
            $columns = 'product_code=?, product_name=?, barcode=?, category=?, company_name=?, brand=?, garment_type=?, target_group=?, size_label=?, colour=?, unit=?, unit_cost=?, selling_price=?, location_id=?, description=?, company_contact=?';
            $data = $baseData;
            if ($enhancementsReady) {
                $columns .= ', minimum_stock=?, maximum_stock=?';
                $data = [...$data, $minimumStock, $maximumStock];
            }
            $data[] = $id;
            $pdo->prepare("UPDATE products SET $columns WHERE id=?")->execute($data);
            $successMessage = 'Clothing item updated: ' . $baseData[0] . ' (' . $baseData[1] . ').';
        } else {
            $initialQuantity = $enhancementsReady ? max(0, (int) ($_POST['initial_quantity'] ?? 0)) : 0;
            if ($initialQuantity > 0) {
                if (($_POST['confirm_opening_stock'] ?? '') !== '1') throw new RuntimeException('Please confirm that this is the first stock record before saving opening stock.');
                if (!$locationId) throw new RuntimeException('Choose a storage location before adding opening stock.');
                guard_location_capacity($pdo, $locationId, $initialQuantity);
            }
            $columns = 'product_code,product_name,barcode,category,company_name,brand,garment_type,target_group,size_label,colour,unit,unit_cost,selling_price,location_id,description,company_contact';
            $data = $baseData;
            if ($enhancementsReady) {
                $columns .= ',minimum_stock,maximum_stock';
                $data = [...$data, $minimumStock, $maximumStock];
            }
            $placeholders = implode(',', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO products($columns) VALUES($placeholders)")->execute($data);
            $newProductId = (int) $pdo->lastInsertId();
            if ($initialQuantity > 0) {
                $movementCode = 'OPEN-' . date('Ymd') . '-' . random_int(100, 999);
                $pdo->prepare("INSERT INTO stock_movements(movement_code,product_id,location_id,movement_type,quantity,unit_cost,selling_price,reference_no,partner_company,partner_address,notes,created_by,partner_contact) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$movementCode, $newProductId, $locationId, 'Inbound', $initialQuantity, (float) $_POST['unit_cost'], (float) $_POST['selling_price'], 'OPENING-STOCK', trim($_POST['company_name']) ?: null, null, 'Opening stock created with clothing item.', $_SESSION['user']['id'], trim($_POST['company_contact'] ?? '') ?: null]);
                $pdo->prepare('UPDATE products SET current_quantity=current_quantity+? WHERE id=?')->execute([$initialQuantity, $newProductId]);
            }
            $successMessage = $initialQuantity > 0
                ? 'Clothing item and opening stock added: ' . $baseData[0] . ' (' . $baseData[1] . ').'
                : 'Clothing item added successfully: ' . $baseData[0] . ' (' . $baseData[1] . '). Add stock later in Stock Movement.';
        }
        $pdo->commit();
        $_SESSION['inventory_success'] = $successMessage;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['inventory_error'] = 'Unable to save clothing item: ' . $exception->getMessage();
        header('Location: product_form.php' . ($id ? '?id=' . $id : ''));
        exit;
    }
    header('Location:index.php');
    exit;
}

$locations = $pdo->query("SELECT l.id, l.location_code, l.capacity, COALESCE(SUM(p.current_quantity), 0) AS current_stock FROM warehouse_locations l LEFT JOIN products p ON p.location_id = l.id AND p.status = 'Active' WHERE l.status = 'Active' GROUP BY l.id ORDER BY l.location_code")->fetchAll(PDO::FETCH_ASSOC);
$existingBarcodes = $pdo->query("SELECT barcode FROM products WHERE barcode IS NOT NULL AND barcode <> ''")->fetchAll(PDO::FETCH_COLUMN);
$page_title = 'Clothing Item';
require __DIR__ . '/../includes/header.php';
$inventoryError = $_SESSION['inventory_error'] ?? '';
unset($_SESSION['inventory_error']);
?>
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="page-heading"><?= e($id ? 'Edit Clothing Item' : 'Add Clothing Item') ?></h1><p class="page-subtitle mb-0">One SKU represents one clothing variant: one style, colour and size combination.</p></div><a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to Inventory</a></div>
<?php if ($inventoryError): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= e($inventoryError) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<?php if (!$enhancementsReady): ?><div class="alert alert-warning"><b>Upgrade needed:</b> the original form still works, but the new clothing fields need the database upgrade. Import <code>product_enhancement.sql</code> in phpMyAdmin, then refresh this page.</div><?php endif; ?>
<form method="post" enctype="multipart/form-data" id="productForm"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><div class="alert alert-danger d-none" id="requiredFieldsError" role="alert"><b>Please complete the required fields:</b><span id="requiredFieldsList"></span></div>
<div class="panel mb-4"><div class="d-flex align-items-center gap-2 mb-3"><span class="badge text-bg-primary">1</span><h2 class="h6 mb-0">Product Identity</h2><span class="text-muted small">Fields marked * are required</span></div><div class="row g-3"><div class="col-md-4"><label class="form-label">SKU / Product Code *</label><div class="input-group"><input class="form-control" id="skuInput" name="product_code" required placeholder="e.g. CAP-NVY-OS" value="<?= e($item['product_code'] ?? '') ?>"><button class="btn btn-outline-primary" type="button" id="generateSkuButton"><i class="bi bi-magic"></i> Generate SKU</button></div><div class="form-text">Format: garment type – colour – size.</div></div><div class="col-md-8"><label class="form-label">Clothing Item Name *</label><input class="form-control" name="product_name" required placeholder="e.g. Classic Cotton T-Shirt" value="<?= e($item['product_name'] ?? '') ?>"></div><div class="col-md-4"><label class="form-label">Company / Supplier *</label><input class="form-control" name="company_name" required list="companySuggestions" placeholder="e.g. ABC Fashion Sdn. Bhd." value="<?= e($item['company_name'] ?? '') ?>"><datalist id="companySuggestions"><option value="WareFlow Apparel"><option value="ABC Fashion Sdn. Bhd."><option value="Local Brand Supplier"></datalist><label class="form-label mt-2" for="companyContact">Contact</label><input class="form-control" id="companyContact" name="company_contact" maxlength="160" placeholder="Phone number or email" value="<?= e($item['company_contact'] ?? '') ?>"></div><div class="col-md-4"><label class="form-label">Brand Label</label><input class="form-control" name="brand" placeholder="e.g. WareFlow Basics" value="<?= e($item['brand'] ?? '') ?>"></div><div class="col-md-4"><label class="form-label">Barcode</label><div class="input-group"><input class="form-control" id="barcodeInput" name="barcode" inputmode="numeric" placeholder="Optional barcode number" value="<?= e($item['barcode'] ?? '') ?>"><button class="btn btn-outline-primary" type="button" id="generateBarcodeButton"><i class="bi bi-upc"></i> Generate Barcode</button></div><div class="form-text">Generates a unique 13-digit EAN barcode. You may also enter a supplier barcode.</div></div></div></div>
<div class="panel mb-4"><div class="d-flex align-items-center gap-2 mb-3"><span class="badge text-bg-primary">2</span><h2 class="h6 mb-0">Clothing Variant</h2><span class="text-muted small">Category controls the available garment types</span></div><div class="row g-3"><div class="col-md-4"><label class="form-label">Category</label><select class="form-select" name="category" id="categorySelect"><option value="">Choose category</option><?php foreach (array_keys($categories) as $value): ?><option <?= ($item['category'] ?? '') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach ?><option value="__other__">Other — Add your own category</option></select><div id="customCategoryFields" class="d-none mt-2"><label class="form-label" for="customCategory">Category Name *</label><input class="form-control" id="customCategory" name="custom_category" maxlength="80" disabled placeholder="Enter category name"></div></div><div class="col-md-4"><label class="form-label">Garment Type</label><select class="form-select" name="garment_type" id="garmentTypeSelect" data-current="<?= e($item['garment_type'] ?? '') ?>"><option value="">Choose category first</option></select><input class="form-control d-none" id="customGarmentType" name="custom_garment_type" maxlength="80" disabled placeholder="Enter garment type"></div><div class="col-md-4"><label class="form-label">Gender / Target Group</label><select class="form-select" name="target_group"><option value="">Choose group</option><?php foreach ($targetGroups as $value): ?><option <?= ($item['target_group'] ?? '') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach ?></select></div><div class="col-md-4"><label class="form-label">Size</label><select class="form-select" name="size_label"><option value="">Choose size</option><?php foreach ($sizes as $value): ?><option <?= ($item['size_label'] ?? '') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach ?></select></div><div class="col-md-4"><label class="form-label">Colour</label><input class="form-control" name="colour" list="colourOptions" placeholder="e.g. Navy Blue" value="<?= e($item['colour'] ?? '') ?>"><datalist id="colourOptions"><?php foreach ($colours as $value): ?><option value="<?= e($value) ?>"><?php endforeach ?></datalist></div><div class="col-md-4"><label class="form-label">Unit</label><select class="form-select" name="unit"><?php foreach ($units as $value): ?><option <?= ($item['unit'] ?? 'pcs') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach ?></select></div></div></div>
<?php if ($enhancementsReady): ?><div class="panel mb-4"><div class="d-flex align-items-center gap-2 mb-3"><span class="badge text-bg-primary">3</span><h2 class="h6 mb-0">Inventory Control</h2><span class="text-muted small">Set stock limits for this SKU variant</span></div><div class="row g-3"><?php if (!$id): ?><div class="col-md-4"><label class="form-label">Opening Stock</label><input class="form-control" id="openingStockInput" type="number" min="0" step="1" name="initial_quantity" value="0"><div class="form-text">Only use this for physical stock already in the warehouse when this SKU is first created.</div><div class="alert alert-danger small mt-2 mb-0 d-none" id="openingStockCapacityWarning" role="alert"></div></div><div class="col-md-8 d-none" id="openingStockConfirm"><div class="alert alert-warning mb-0 py-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="confirm_opening_stock" value="1" id="confirmOpeningStock"><label class="form-check-label" for="confirmOpeningStock"><b>I confirm this is the first record for this SKU.</b> I have checked that the physical stock quantity is correct.</label></div><div class="text-danger small mt-2 d-none" id="openingStockError">Please tick the confirmation box before saving an opening stock quantity.</div></div></div><?php endif; ?><div class="col-md-4"><label class="form-label">Minimum Stock / Reorder Point</label><input class="form-control" id="minimumStockInput" type="number" min="0" step="1" name="minimum_stock" value="<?= e($item['minimum_stock'] ?? '10') ?>"><div class="form-text">Dashboard flags this item when stock falls below this level.</div></div><div class="col-md-4"><label class="form-label">Maximum Stock (Optional)</label><input class="form-control" id="maximumStockInput" type="number" min="0" step="1" name="maximum_stock" placeholder="Optional" value="<?= e($item['maximum_stock'] ?? '') ?>"><div class="form-text">Leave blank for no maximum stock limit; you can still save this item.</div><div class="text-danger small mt-1 d-none" id="stockRangeError">Maximum Stock cannot be lower than Minimum Stock.</div></div><div class="col-md-4"><label class="form-label">Storage Location</label><select class="form-select" id="productLocationSelect" name="location_id"><option value="">— Assign location later —</option><?php foreach ($locations as $location): ?><option value="<?= $location['id'] ?>" data-capacity="<?= (int) $location['capacity'] ?>" data-current-stock="<?= (int) $location['current_stock'] ?>" <?= ($item['location_id'] ?? 0) == $location['id'] ? 'selected' : '' ?>><?= e($location['location_code']) ?></option><?php endforeach ?></select></div><div class="col-md-4"><label class="form-label">Unit Cost (RM)</label><input class="form-control" type="number" min="0" step="0.01" name="unit_cost" value="<?= e($item['unit_cost'] ?? '0') ?>"></div><div class="col-md-4"><label class="form-label">Selling Price (RM)</label><input class="form-control" type="number" min="0" step="0.01" name="selling_price" value="<?= e($item['selling_price'] ?? '0') ?>"></div></div></div><?php else: ?><input type="hidden" name="location_id" value="<?= e($item['location_id'] ?? '') ?>"><input type="hidden" name="unit_cost" value="<?= e($item['unit_cost'] ?? '0') ?>"><input type="hidden" name="selling_price" value="<?= e($item['selling_price'] ?? '0') ?>"><?php endif; ?>
<div class="panel mb-4"><div class="d-flex align-items-center gap-2 mb-3"><span class="badge text-bg-primary"><?= $enhancementsReady ? '4' : '3' ?></span><h2 class="h6 mb-0">Optional Notes</h2></div><label class="form-label">Description or Handling Notes</label><textarea class="form-control" rows="4" name="description" placeholder="e.g. Keep dry, fold before storage, display notes."><?= e($item['description'] ?? '') ?></textarea></div>
<div class="d-flex gap-2 justify-content-end"><a href="index.php" class="btn btn-light">Cancel</a><button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> <?= e($id ? 'Save Changes' : 'Save Clothing Item') ?></button></div></form>
<script>
const categoryTypesData = <?= json_encode($categories) ?>;
const categorySelect = document.getElementById('categorySelect');
const garmentTypeSelect = document.getElementById('garmentTypeSelect');
function loadGarmentTypes() {
  const selectedCategory = categorySelect.value;
  const isOther = selectedCategory === '__other__';
  document.getElementById('customCategoryFields').classList.toggle('d-none', !isOther);
  document.getElementById('customCategory').disabled = !isOther;
  document.getElementById('customGarmentType').disabled = !isOther;
  garmentTypeSelect.disabled = isOther;
  garmentTypeSelect.classList.toggle('d-none', isOther);
  document.getElementById('customGarmentType').classList.toggle('d-none', !isOther);
  const currentType = garmentTypeSelect.dataset.current || '';
  const types = [...(categoryTypesData[selectedCategory] || [])];
  garmentTypeSelect.innerHTML = '<option value="">Choose garment type</option>';
  if (currentType && !types.includes(currentType)) types.unshift(currentType);
  types.forEach(type => {
    const option = document.createElement('option');
    option.value = type;
    option.textContent = type;
    option.selected = type === currentType;
    garmentTypeSelect.appendChild(option);
  });
  garmentTypeSelect.dataset.current = '';
}
categorySelect.addEventListener('change', loadGarmentTypes);
loadGarmentTypes();
const skuInput = document.getElementById('skuInput');
const generateSkuButton = document.getElementById('generateSkuButton');
function skuPart(value, fallback, length = 4) {
  const cleaned = (value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
  return (cleaned || fallback).slice(0, length);
}
if (generateSkuButton) generateSkuButton.addEventListener('click', () => {
  const garmentTypeValue = categorySelect.value === '__other__' ? document.getElementById('customGarmentType').value : garmentTypeSelect.value;
  const type = skuPart(garmentTypeValue, 'GEN', 3);
  const colourNames = { 'BLACK': 'BLK', 'WHITE': 'WHT', 'GREY': 'GRY', 'NAVY BLUE': 'NVY', 'BLUE': 'BLU', 'RED': 'RED', 'GREEN': 'GRN', 'BEIGE': 'BEI', 'BROWN': 'BRN', 'PINK': 'PNK', 'YELLOW': 'YLW', 'MULTI-COLOUR': 'MLT' };
  const colourValue = document.querySelector('[name="colour"]').value.trim().toUpperCase();
  const colour = colourNames[colourValue] || skuPart(colourValue, 'CLR', 3);
  const sizeValue = document.querySelector('[name="size_label"]').value.trim().toUpperCase();
  const size = sizeValue === 'FREE SIZE' ? 'OS' : skuPart(sizeValue, 'OS', 3);
  skuInput.value = `${type}-${colour}-${size}`;
  skuInput.focus();
});
const barcodeInput = document.getElementById('barcodeInput');
const generateBarcodeButton = document.getElementById('generateBarcodeButton');
const existingBarcodes = new Set(<?= json_encode($existingBarcodes) ?>.map(String));
function ean13CheckDigit(twelveDigits) {
  let total = 0;
  for (let index = 0; index < twelveDigits.length; index += 1) total += Number(twelveDigits[index]) * (index % 2 === 0 ? 1 : 3);
  return (10 - (total % 10)) % 10;
}
if (generateBarcodeButton) generateBarcodeButton.addEventListener('click', () => {
  let barcode = '';
  for (let attempt = 0; attempt < 100; attempt += 1) {
    const base = '955' + String(Math.floor(Math.random() * 1000000000)).padStart(9, '0');
    barcode = base + ean13CheckDigit(base);
    if (!existingBarcodes.has(barcode)) break;
  }
  if (!barcode || existingBarcodes.has(barcode)) { alert('Unable to generate a new barcode. Please try again.'); return; }
  barcodeInput.value = barcode;
  existingBarcodes.add(barcode);
  barcodeInput.focus();
});
const productForm = document.getElementById('productForm');
const minimumStockInput = document.getElementById('minimumStockInput');
const maximumStockInput = document.getElementById('maximumStockInput');
const stockRangeError = document.getElementById('stockRangeError');
function validateStockRange() {
  if (!minimumStockInput || !maximumStockInput) return true;
  const maximumIsSet = maximumStockInput.value.trim() !== '';
  const invalid = maximumIsSet && Number(maximumStockInput.value) < Number(minimumStockInput.value || 0);
  stockRangeError.classList.toggle('d-none', !invalid);
  maximumStockInput.classList.toggle('is-invalid', invalid);
  return !invalid;
}
if (minimumStockInput) { minimumStockInput.addEventListener('input', validateStockRange); maximumStockInput.addEventListener('input', validateStockRange); productForm.addEventListener('submit', event => { if (!validateStockRange()) { event.preventDefault(); maximumStockInput.focus(); } }); }
const requiredFields = [
  ['product_code', 'SKU / Product Code'], ['product_name', 'Clothing Item Name'], ['company_name', 'Company / Supplier'],
  ['category', 'Category'], ['custom_category', 'Category Name'], ['custom_garment_type', 'Custom Garment Type'], ['garment_type', 'Garment Type'], ['target_group', 'Gender / Target Group'],
  ['size_label', 'Size'], ['colour', 'Colour'], ['unit', 'Unit']
];
const requiredFieldsError = document.getElementById('requiredFieldsError');
const requiredFieldsList = document.getElementById('requiredFieldsList');
function validateRequiredFields() {
  const missing = requiredFields.filter(([name]) => !productForm.elements[name]?.disabled && !String(productForm.elements[name]?.value || '').trim());
  requiredFields.forEach(([name]) => productForm.elements[name]?.classList.remove('is-invalid'));
  if (!missing.length) { requiredFieldsError.classList.add('d-none'); return true; }
  missing.forEach(([name]) => productForm.elements[name]?.classList.add('is-invalid'));
  requiredFieldsList.textContent = ' ' + missing.map(([, label]) => label).join(', ') + '.';
  requiredFieldsError.classList.remove('d-none');
  missing[0] && productForm.elements[missing[0][0]].focus();
  requiredFieldsError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  return false;
}
productForm.noValidate = true;
let hasTriedToSubmit = false;
function updateRequiredFieldState(name) {
  if (!hasTriedToSubmit) return;
  const field = productForm.elements[name];
  if (!field) return;
  field.classList.toggle('is-invalid', !String(field.value || '').trim());
  const missing = requiredFields.filter(([fieldName]) => !productForm.elements[fieldName]?.disabled && !String(productForm.elements[fieldName]?.value || '').trim());
  if (!missing.length) {
    requiredFieldsError.classList.add('d-none');
  } else {
    requiredFieldsList.textContent = ' ' + missing.map(([, label]) => label).join(', ') + '.';
  }
}
productForm.addEventListener('submit', event => {
  hasTriedToSubmit = true;
  if (!validateRequiredFields()) event.preventDefault();
});
requiredFields.forEach(([name]) => {
  const field = productForm.elements[name];
  field?.addEventListener('input', () => updateRequiredFieldState(name));
  field?.addEventListener('change', () => updateRequiredFieldState(name));
});
const openingStockInput = document.getElementById('openingStockInput');
if (openingStockInput) {
  const openingStockConfirm = document.getElementById('openingStockConfirm');
  const confirmOpeningStock = document.getElementById('confirmOpeningStock');
  const openingStockError = document.getElementById('openingStockError');
  function toggleOpeningStockConfirmation() { const needed = Number(openingStockInput.value || 0) > 0; openingStockConfirm.classList.toggle('d-none', !needed); confirmOpeningStock.required = needed; if (!needed) { confirmOpeningStock.checked = false; confirmOpeningStock.classList.remove('is-invalid'); openingStockError.classList.add('d-none'); } }
  function validateOpeningStockConfirmation(showMessage = false) {
    const needed = Number(openingStockInput.value || 0) > 0;
    const valid = !needed || confirmOpeningStock.checked;
    confirmOpeningStock.classList.toggle('is-invalid', showMessage && !valid);
    openingStockError.classList.toggle('d-none', !showMessage || valid);
    return valid;
  }
  openingStockInput.addEventListener('input', toggleOpeningStockConfirmation);
  confirmOpeningStock.addEventListener('change', () => validateOpeningStockConfirmation(true));
  productForm.addEventListener('submit', event => { if (!validateOpeningStockConfirmation(true)) { event.preventDefault(); openingStockConfirm.focus(); } });
  toggleOpeningStockConfirmation();
}
const openingStockLocation = document.getElementById('productLocationSelect');
const openingStockCapacityWarning = document.getElementById('openingStockCapacityWarning');
function validateOpeningStockCapacity() {
  if (!openingStockInput || !openingStockCapacityWarning) return true;
  const quantity = Number(openingStockInput.value || 0);
  const option = openingStockLocation?.options[openingStockLocation.selectedIndex];
  openingStockCapacityWarning.classList.add('d-none');
  openingStockCapacityWarning.textContent = '';
  if (quantity <= 0) return true;
  if (!option?.value) {
    openingStockCapacityWarning.textContent = 'Choose a storage location before adding opening stock.';
    openingStockCapacityWarning.classList.remove('d-none');
    return false;
  }
  const capacity = Number(option.dataset.capacity || 0);
  const current = Number(option.dataset.currentStock || 0);
  const projected = current + quantity;
  if (projected >= capacity * 0.9) {
    openingStockCapacityWarning.textContent = `Storage location ${option.textContent.trim()} is full or nearly full (${current} / ${capacity} pcs). Adding ${quantity} pcs would bring it to ${projected} pcs. The 90% limit prevents this stock addition.`;
    openingStockCapacityWarning.classList.remove('d-none');
    return false;
  }
  return true;
}
if (openingStockInput) {
  openingStockInput.addEventListener('input', validateOpeningStockCapacity);
  openingStockLocation?.addEventListener('change', validateOpeningStockCapacity);
  productForm.addEventListener('submit', event => {
    if (!validateOpeningStockCapacity()) {
      event.preventDefault();
      openingStockCapacityWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
