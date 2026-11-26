-- Clothing product enhancement: run once after selecting warehouse_maintenance.
-- Keeps all existing products and adds optional, warehouse-focused fields.

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS minimum_stock INT NOT NULL DEFAULT 10 AFTER current_quantity,
  ADD COLUMN IF NOT EXISTS maximum_stock INT NULL AFTER minimum_stock,
  ADD COLUMN IF NOT EXISTS material_composition VARCHAR(255) NULL AFTER season,
  ADD COLUMN IF NOT EXISTS fit_type VARCHAR(80) NULL AFTER target_group,
  ADD COLUMN IF NOT EXISTS weight_grams DECIMAL(10,2) NULL AFTER unit,
  ADD COLUMN IF NOT EXISTS package_length_cm DECIMAL(10,2) NULL AFTER weight_grams,
  ADD COLUMN IF NOT EXISTS package_width_cm DECIMAL(10,2) NULL AFTER package_length_cm,
  ADD COLUMN IF NOT EXISTS package_height_cm DECIMAL(10,2) NULL AFTER package_width_cm,
  ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) NULL AFTER description;
