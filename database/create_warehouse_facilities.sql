-- Creates the clothing warehouse facilities table without changing products or maintenance data.
USE warehouse_maintenance;

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
