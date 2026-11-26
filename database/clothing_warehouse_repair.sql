-- Run this once only if clothing_warehouse_migration.sql failed with
-- "Unknown column 'company_name' in 'products'".
USE warehouse_maintenance;

ALTER TABLE products
  ADD COLUMN company_name VARCHAR(150) NULL AFTER category;

ALTER TABLE products
  ADD COLUMN brand VARCHAR(100) NULL AFTER company_name,
  ADD COLUMN garment_type VARCHAR(80) NULL AFTER brand,
  ADD COLUMN target_group ENUM('Men','Women','Kids','Unisex') NULL AFTER garment_type,
  ADD COLUMN size_label VARCHAR(30) NULL AFTER target_group,
  ADD COLUMN colour VARCHAR(50) NULL AFTER size_label,
  ADD COLUMN season VARCHAR(40) NULL AFTER colour;

CREATE TABLE IF NOT EXISTS warehouse_facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_code VARCHAR(40) NOT NULL UNIQUE,
  facility_name VARCHAR(120) NOT NULL,
  facility_type ENUM('Receiving Area','Quality Control','Storage Zone','Packing Area','Dispatch Area','Returns Area','Maintenance Area') NOT NULL,
  supervisor_name VARCHAR(100) NULL,
  status ENUM('Active','Blocked','Maintenance') NOT NULL DEFAULT 'Active',
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO warehouse_facilities
  (facility_code, facility_name, facility_type, supervisor_name, status, notes)
VALUES
  ('RCV-01', 'Garment Receiving Bay', 'Receiving Area', 'Warehouse Supervisor', 'Active', 'Receive cartons from suppliers and verify quantities.'),
  ('QC-01', 'Apparel Quality Check', 'Quality Control', 'Quality Team', 'Active', 'Inspect fabric, colour, size and defects before storage.'),
  ('STO-A', 'Hanging Garment Storage', 'Storage Zone', 'Inventory Team', 'Active', 'For shirts, dresses, jackets and other hanging garments.'),
  ('PKG-01', 'Packing and Labelling Station', 'Packing Area', 'Dispatch Team', 'Active', 'Pick, pack, label and prepare customer orders.'),
  ('DSP-01', 'Outbound Dispatch Dock', 'Dispatch Area', 'Dispatch Team', 'Active', 'Handover packed orders to delivery partners.');
