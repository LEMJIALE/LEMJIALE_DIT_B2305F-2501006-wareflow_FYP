-- Import once in phpMyAdmin after selecting warehouse_maintenance.
-- Adds example equipment for a clothing distribution centre without deleting existing equipment.
USE warehouse_maintenance;

INSERT IGNORE INTO equipment
  (equipment_code, equipment_name, category, serial_number, location, purchase_date, warranty_expiry, status, condition_status, description)
VALUES
  ('FLT-001', 'Electric Forklift', 'Material Handling', 'EF-2026-001', 'Receiving & Storage Zone', '2025-01-15', '2028-01-14', 'Operational', 'Good', 'Moves clothing pallets from receiving to storage racks.'),
  ('PJT-001', 'Electric Pallet Jack', 'Material Handling', 'EPJ-2026-001', 'Storage Zone A', '2025-03-10', '2027-03-09', 'Operational', 'Good', 'Used for short-distance pallet movement in apparel storage areas.'),
  ('CVY-001', 'Packing Conveyor Belt', 'Packing Equipment', 'CV-2026-001', 'Packing Area', '2025-02-20', '2028-02-19', 'Operational', 'Good', 'Transfers picked clothing cartons to the packing and dispatch stations.'),
  ('BSC-001', 'Handheld Barcode Scanner', 'IT Equipment', 'BS-2026-001', 'Receiving Area', '2025-04-05', '2027-04-04', 'Operational', 'Good', 'Scans clothing SKU barcodes during receiving and stock movements.'),
  ('PRN-001', 'Thermal Label Printer', 'IT Equipment', 'LP-2026-001', 'Packing Area', '2025-04-05', '2027-04-04', 'Operational', 'Good', 'Prints barcode labels, carton labels and delivery labels.'),
  ('PWS-001', 'Packing Workstation', 'Packing Equipment', 'PW-2026-001', 'Packing Area', '2025-05-12', '2027-05-11', 'Operational', 'Good', 'Packing table with weighing scale for clothing customer orders.'),
  ('DCK-001', 'Dock Leveler', 'Loading Equipment', 'DL-2026-001', 'Outbound Dispatch Dock', '2025-01-30', '2028-01-29', 'Operational', 'Good', 'Allows safe loading of clothing cartons into delivery vehicles.'),
  ('HVAC-001', 'Warehouse Air Conditioning Unit', 'Facility Equipment', 'AC-2026-001', 'Hanging Garment Storage', '2025-06-01', '2028-05-31', 'Operational', 'Good', 'Maintains suitable temperature and humidity for garment storage.');
