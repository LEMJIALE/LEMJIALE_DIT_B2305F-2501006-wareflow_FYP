USE warehouse_maintenance;

-- Run this after inventory_migration.sql.
ALTER TABLE products ADD COLUMN unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit;
ALTER TABLE products ADD COLUMN selling_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit_cost;
ALTER TABLE stock_movements ADD COLUMN unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantity;
ALTER TABLE stock_movements ADD COLUMN selling_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit_cost;

UPDATE products SET unit_cost=35.00,selling_price=59.90 WHERE product_code='PRD-001';
UPDATE products SET unit_cost=12.00,selling_price=24.90 WHERE product_code='PRD-002';
UPDATE products SET unit_cost=8.00,selling_price=19.90 WHERE product_code='PRD-003';
UPDATE stock_movements m JOIN products p ON p.id=m.product_id
SET m.unit_cost=p.unit_cost, m.selling_price=p.selling_price
WHERE m.unit_cost=0 AND m.selling_price=0;
