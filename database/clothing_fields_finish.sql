-- Run this once after clothing_warehouse_repair.sql reports a duplicate column.
-- MariaDB will add only missing clothing fields and keep existing fields/data.
USE warehouse_maintenance;

ALTER TABLE products ADD COLUMN IF NOT EXISTS brand VARCHAR(100) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS garment_type VARCHAR(80) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS target_group ENUM('Men','Women','Kids','Unisex') NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS size_label VARCHAR(30) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS colour VARCHAR(50) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS season VARCHAR(40) NULL;
