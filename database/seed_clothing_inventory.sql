-- Import once in phpMyAdmin after selecting warehouse_maintenance.
-- Adds sample clothing items with opening inventory quantities. Existing product codes are not changed.
USE warehouse_maintenance;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-TSH-BLK-M', 'Classic Cotton T-Shirt', '9557001000011', 'Tops', 'WareFlow Apparel', 'WareFlow Basics', 'T-Shirt', 'Unisex', 'M', 'Black', 'All Season', 'pcs', 18.00, 39.90, 120, id, '100% cotton basic T-shirt. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'A-01-01' LIMIT 1;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-TSH-WHT-L', 'Classic Cotton T-Shirt', '9557001000028', 'Tops', 'WareFlow Apparel', 'WareFlow Basics', 'T-Shirt', 'Unisex', 'L', 'White', 'All Season', 'pcs', 18.00, 39.90, 95, id, '100% cotton basic T-shirt. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'A-01-02' LIMIT 1;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-JNS-BLU-32', 'Slim Fit Denim Jeans', '9557001000035', 'Bottoms', 'WareFlow Apparel', 'Urban Denim', 'Jeans', 'Men', '32', 'Blue', 'Autumn/Winter', 'pcs', 45.00, 89.90, 60, id, 'Slim fit stretch denim jeans. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'B-01-01' LIMIT 1;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-DRS-RED-M', 'Floral Midi Dress', '9557001000042', 'Dresses', 'WareFlow Apparel', 'Luna Fashion', 'Midi Dress', 'Women', 'M', 'Red', 'Spring/Summer', 'pcs', 55.00, 119.90, 42, id, 'Floral printed midi dress. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'C-01-01' LIMIT 1;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-HDY-GRY-L', 'Fleece Pullover Hoodie', '9557001000059', 'Outerwear', 'WareFlow Apparel', 'Comfort Line', 'Hoodie', 'Unisex', 'L', 'Grey', 'Autumn/Winter', 'pcs', 42.00, 79.90, 75, id, 'Brushed fleece pullover hoodie. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'A-01-01' LIMIT 1;

INSERT IGNORE INTO products
  (product_code, product_name, barcode, category, company_name, brand, garment_type, target_group, size_label, colour, season, unit, unit_cost, selling_price, current_quantity, location_id, description, status)
SELECT 'APP-CAP-NVY-OS', 'Adjustable Baseball Cap', '9557001000066', 'Accessories', 'WareFlow Apparel', 'Street Gear', 'Cap', 'Unisex', 'One Size', 'Navy Blue', 'All Season', 'pcs', 12.00, 29.90, 150, id, 'Adjustable embroidered baseball cap. Opening inventory.', 'Active'
FROM warehouse_locations WHERE location_code = 'B-01-01' LIMIT 1;
