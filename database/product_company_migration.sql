USE warehouse_maintenance;

-- Run this after inventory_migration.sql.
ALTER TABLE products ADD COLUMN company_name VARCHAR(150) NULL AFTER category;
