<?php $editing=isset($item);$categories=['Material Handling','Conveyor System','Storage System','Loading Dock','Facility Equipment','Power Equipment','Safety Equipment','Garment / Apparel Equipment','Picking & Sorting Equipment','QC & Security Equipment','Other'];$locations=['Loading Bay A','Loading Bay B','Dock 1','Dock 2','Dock 3','Sorting Zone','Receiving Area','Dispatch Area','High Bay Storage','Aisle 1','Aisle 2','Aisle 3','Aisle 4','Office Block','Utility Room'];?>
<?php
foreach ($pdo->query("SELECT DISTINCT category FROM equipment WHERE category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN) as $savedCategory) {
 if (!in_array($savedCategory, $categories, true)) array_splice($categories, count($categories)-1, 0, [$savedCategory]);
}
if (!empty($item['location']) && !in_array($item['location'], $locations, true)) $locations[]=$item['location'];
?>
<div class="panel"><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="col-md-12"><label class="form-label"><?=e(t('equipment_name'))?></label><input class="form-control" name="equipment_name" required value="<?=e($item['equipment_name']??'')?>"></div><div class="col-md-4"><label class="form-label"><?=e(t('category'))?></label><select class="form-select" name="category" id="equipmentCategory" required><option value=""><?=e(t('select_category'))?></option><?php foreach($categories as $x):?><option value="<?=e($x)?>" <?=($item['category']??'')===$x?'selected':''?>><?=e($x)?></option><?php endforeach?></select><div id="equipmentCustomCategoryWrap" class="mt-2 d-none"><label class="form-label" for="equipmentCustomCategory">Category Name *</label><input class="form-control" name="custom_category" id="equipmentCustomCategory" maxlength="80" placeholder="Enter equipment category" disabled></div></div><div class="col-md-4"><label class="form-label"><?=e(t('serial_number'))?></label><input class="form-control" name="serial_number" value="<?=e($item['serial_number']??'')?>"></div><div class="col-md-4"><label class="form-label"><?=e(t('location'))?></label><select class="form-select" name="location" required><option value=""><?=e(t('select_location'))?></option><?php foreach($locations as $x):?><option value="<?=e($x)?>" <?=($item['location']??'')===$x?'selected':''?>><?=e($x)?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label"><?=e(t('purchase_date'))?></label><input type="date" class="form-control" name="purchase_date" value="<?=e($item['purchase_date']??'')?>"></div><div class="col-md-3"><label class="form-label"><?=e(t('warranty_expiry'))?></label><input type="date" class="form-control" name="warranty_expiry" value="<?=e($item['warranty_expiry']??'')?>"></div><div class="col-md-3"><label class="form-label"><?=e(t('status'))?></label><select class="form-select" name="status"><?php foreach(['Operational','Under Maintenance','Breakdown','Retired'] as $x):?><option <?=($item['status']??'Operational')===$x?'selected':''?> value="<?=$x?>"><?=e(tv($x))?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label"><?=e(t('condition'))?></label><select class="form-select" name="condition_status"><?php foreach(['Good','Fair','Poor'] as $x):?><option <?=($item['condition_status']??'Good')===$x?'selected':''?> value="<?=$x?>"><?=e(tv($x))?></option><?php endforeach?></select></div><div class="col-12"><label class="form-label"><?=e(t('description'))?></label><textarea class="form-control" name="description" rows="3"><?=e($item['description']??'')?></textarea></div><div><button class="btn btn-primary"><?=e($editing?t('save_changes'):t('add_equipment'))?></button><a href="index.php" class="btn btn-light"><?=e(t('cancel'))?></a></div></form></div>


<script>
const equipmentCategory=document.getElementById('equipmentCategory');
const equipmentCustomCategory=document.getElementById('equipmentCustomCategory');
function toggleEquipmentCustomCategory(){
 const custom=equipmentCategory.value==='Other';
 document.getElementById('equipmentCustomCategoryWrap').classList.toggle('d-none',!custom);
 equipmentCustomCategory.disabled=!custom;
 equipmentCustomCategory.required=custom;
}
equipmentCategory.addEventListener('change',toggleEquipmentCustomCategory);
toggleEquipmentCustomCategory();
</script>
