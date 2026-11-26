USE warehouse_maintenance;

-- Run this after inventory_migration.sql.
ALTER TABLE stock_movements ADD COLUMN partner_company VARCHAR(150) NULL AFTER reference_no;
ALTER TABLE stock_movements ADD COLUMN partner_address TEXT NULL AFTER partner_company;
