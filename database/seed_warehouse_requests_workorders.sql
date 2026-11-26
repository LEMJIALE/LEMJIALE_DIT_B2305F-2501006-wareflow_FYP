-- Import once after seed_clothing_warehouse_equipment.sql.
-- Adds realistic maintenance requests and work orders without deleting existing records.
USE warehouse_maintenance;

INSERT IGNORE INTO maintenance_requests
  (request_code, equipment_id, reported_by, problem_description, priority, status)
SELECT 'REQ-202610-001', e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Forklift hydraulic lift is moving slowly when lifting clothing pallets.', 'High', 'Pending'
FROM equipment e WHERE e.equipment_code = 'FLT-001';

INSERT IGNORE INTO maintenance_requests
  (request_code, equipment_id, reported_by, problem_description, priority, status)
SELECT 'REQ-202610-002', e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Thermal label printer produces faded barcode labels in the packing area.', 'Medium', 'Assigned'
FROM equipment e WHERE e.equipment_code = 'PRN-001';

INSERT IGNORE INTO maintenance_requests
  (request_code, equipment_id, reported_by, problem_description, priority, status)
SELECT 'REQ-202610-003', e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Packing conveyor belt makes abnormal noise and stops briefly.', 'High', 'In Progress'
FROM equipment e WHERE e.equipment_code = 'CVY-001';

INSERT IGNORE INTO maintenance_requests
  (request_code, equipment_id, reported_by, problem_description, priority, status)
SELECT 'REQ-202610-004', e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Handheld barcode scanner battery drains before the receiving shift ends.', 'Medium', 'Completed'
FROM equipment e WHERE e.equipment_code = 'BSC-001';

INSERT IGNORE INTO work_orders
  (work_order_code, request_id, equipment_id, assigned_to, problem_description, priority, start_date, due_date, status, repair_notes)
SELECT 'WO-202610-001', r.id, e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Clean printer head, inspect thermal ribbon, and perform a barcode test print.', 'Medium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Assigned',
       'Scheduled for packing area maintenance window.'
FROM maintenance_requests r JOIN equipment e ON e.id = r.equipment_id
WHERE r.request_code = 'REQ-202610-002';

INSERT IGNORE INTO work_orders
  (work_order_code, request_id, equipment_id, assigned_to, problem_description, priority, start_date, due_date, status, repair_notes)
SELECT 'WO-202610-002', r.id, e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Inspect conveyor belt alignment, rollers, motor and emergency stop switch.', 'High', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'In Progress',
       'Technician is checking the drive roller and belt tension.'
FROM maintenance_requests r JOIN equipment e ON e.id = r.equipment_id
WHERE r.request_code = 'REQ-202610-003';

INSERT IGNORE INTO work_orders
  (work_order_code, request_id, equipment_id, assigned_to, problem_description, priority, start_date, due_date, completion_date, status, repair_notes)
SELECT 'WO-202610-003', r.id, e.id,
       (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1),
       'Replace barcode scanner battery and confirm scanning performance.', 'Medium', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(), 'Completed',
       'Battery replaced. Scanner tested with clothing SKU labels and operating normally.'
FROM maintenance_requests r JOIN equipment e ON e.id = r.equipment_id
WHERE r.request_code = 'REQ-202610-004';
