<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_clothing_warehouse_access();
$statement=$pdo->prepare('SELECT p.*,l.location_code FROM products p LEFT JOIN warehouse_locations l ON l.id=p.location_id WHERE p.id=?');
$statement->execute([(int)($_GET['id']??0)]);
$product=$statement->fetch(PDO::FETCH_ASSOC);
if(!$product){http_response_code(404);exit('Clothing item not found.');}
$page_title='Clothing Item Details';
require __DIR__.'/../includes/header.php';
$sections=[
 'Product Identity'=>['product_code'=>'SKU / Product Code','product_name'=>'Clothing Item Name','barcode'=>'Barcode','status'=>'Status'],
 'Company Details'=>['company_name'=>'Company / Supplier','company_contact'=>'Contact','brand'=>'Brand Label'],
 'Clothing Variant'=>['category'=>'Category','garment_type'=>'Garment Type','target_group'=>'Gender / Target Group','size_label'=>'Size','colour'=>'Colour','unit'=>'Unit'],
 'Inventory Control'=>['current_quantity'=>'Available Stock','minimum_stock'=>'Minimum Stock','maximum_stock'=>'Maximum Stock','location_code'=>'Storage Location','unit_cost'=>'Unit Cost','selling_price'=>'Selling Price'],
];
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><h1 class="page-heading"><?=e($product['product_name'])?></h1><p class="page-subtitle mb-0"><?=e($product['product_code'])?></p></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="index.php">Back to Inventory</a><a class="btn btn-primary" href="product_form.php?id=<?=(int)$product['id']?>">Edit</a></div></div>
<div class="row g-4">
<?php foreach($sections as $title=>$fields):?><div class="col-lg-6"><div class="panel h-100"><h2 class="h6 border-bottom pb-3 mb-3"><?=e($title)?></h2><dl class="row mb-0">
<?php foreach($fields as $field=>$label):
 $value=$product[$field]??null;
 if($value===null||$value==='')$display=$field==='maximum_stock'?'No limit':'Not recorded';
 elseif(in_array($field,['unit_cost','selling_price'],true))$display='RM '.number_format((float)$value,2);
 elseif(in_array($field,['current_quantity','minimum_stock','maximum_stock'],true))$display=number_format((float)$value,0);
 else $display=(string)$value;
?><dt class="col-sm-5 text-muted fw-normal"><?=e($label)?></dt><dd class="col-sm-7 fw-semibold"><?=e($display)?></dd><?php endforeach?></dl></div></div><?php endforeach?>
<div class="col-12"><div class="panel"><h2 class="h6">Clothing Notes</h2><div style="white-space:pre-wrap"><?=e($product['description']?:'No notes.')?></div></div></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
