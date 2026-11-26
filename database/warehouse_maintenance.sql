CREATE DATABASE IF NOT EXISTS warehouse_maintenance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE warehouse_maintenance;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS maintenance_records,work_orders,maintenance_requests,equipment,users;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(120) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,role ENUM('Admin','Maintenance Staff','Warehouse Staff') NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE equipment (id INT AUTO_INCREMENT PRIMARY KEY,equipment_code VARCHAR(40) NOT NULL UNIQUE,equipment_name VARCHAR(120) NOT NULL,category VARCHAR(80) NOT NULL,serial_number VARCHAR(100) NULL,location VARCHAR(120) NOT NULL,purchase_date DATE NULL,warranty_expiry DATE NULL,status ENUM('Operational','Under Maintenance','Breakdown','Retired') NOT NULL DEFAULT 'Operational',condition_status ENUM('Good','Fair','Poor') NOT NULL DEFAULT 'Good',description TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE maintenance_requests (id INT AUTO_INCREMENT PRIMARY KEY,request_code VARCHAR(40) NOT NULL UNIQUE,equipment_id INT NOT NULL,reported_by INT NOT NULL,problem_description TEXT NOT NULL,priority ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',status ENUM('Pending','Assigned','In Progress','Completed','Rejected') NOT NULL DEFAULT 'Pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(equipment_id) REFERENCES equipment(id),FOREIGN KEY(reported_by) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE work_orders (id INT AUTO_INCREMENT PRIMARY KEY,work_order_code VARCHAR(40) NOT NULL UNIQUE,request_id INT NULL,equipment_id INT NOT NULL,assigned_to INT NULL,problem_description TEXT NOT NULL,priority ENUM('Low','Medium','High','Critical') NOT NULL,start_date DATE NULL,due_date DATE NULL,completion_date DATE NULL,status ENUM('Assigned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Assigned',repair_notes TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(request_id) REFERENCES maintenance_requests(id),FOREIGN KEY(equipment_id) REFERENCES equipment(id),FOREIGN KEY(assigned_to) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE maintenance_records (id INT AUTO_INCREMENT PRIMARY KEY,equipment_id INT NOT NULL,work_order_id INT NULL,technician_id INT NULL,maintenance_date DATE NOT NULL,problem_description TEXT NOT NULL,action_taken TEXT NOT NULL,status ENUM('Completed','Cancelled') NOT NULL DEFAULT 'Completed',labour_cost DECIMAL(10,2) NOT NULL DEFAULT 0,parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0,other_cost DECIMAL(10,2) NOT NULL DEFAULT 0,total_cost DECIMAL(10,2) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(equipment_id) REFERENCES equipment(id),FOREIGN KEY(work_order_id) REFERENCES work_orders(id),FOREIGN KEY(technician_id) REFERENCES users(id)) ENGINE=InnoDB;

-- The encrypted hash below verifies the demo password: password
INSERT INTO users(name,email,password,role) VALUES
('Aisha Rahman','admin@wareflow.test','$2y$12$tzEGS8wHFD9aGt0i2UxB/etTvHOjf8.P/Pfu.iF7lJ2xZohlcCIjG','Admin'),
('Daniel Lim','tech1@wareflow.test','$2y$12$tzEGS8wHFD9aGt0i2UxB/etTvHOjf8.P/Pfu.iF7lJ2xZohlcCIjG','Maintenance Staff'),
('Nur Izzah','tech2@wareflow.test','$2y$12$tzEGS8wHFD9aGt0i2UxB/etTvHOjf8.P/Pfu.iF7lJ2xZohlcCIjG','Maintenance Staff'),
('Jason Tan','warehouse1@wareflow.test','$2y$12$tzEGS8wHFD9aGt0i2UxB/etTvHOjf8.P/Pfu.iF7lJ2xZohlcCIjG','Warehouse Staff'),
('Siti Aminah','warehouse2@wareflow.test','$2y$12$tzEGS8wHFD9aGt0i2UxB/etTvHOjf8.P/Pfu.iF7lJ2xZohlcCIjG','Warehouse Staff');
INSERT INTO equipment(equipment_code,equipment_name,category,serial_number,location,purchase_date,warranty_expiry,status,condition_status,description) VALUES
('WH-FL-001','Electric Forklift','Material Handling','FLT-E-2201','Loading Bay A','2023-02-10','2027-02-10','Operational','Good','Electric counterbalance forklift.'),
('WH-FL-002','Diesel Forklift','Material Handling','FLT-D-1884','Loading Bay B','2021-06-18','2025-06-18','Under Maintenance','Fair','Diesel forklift for outdoor loading.'),
('WH-CB-001','Conveyor Belt Line 1','Conveyor System','CV-10098','Sorting Zone','2022-01-21','2026-01-21','Operational','Good','Main package sorting conveyor.'),
('WH-PJ-001','Manual Pallet Jack','Material Handling','PJ-2305','Aisle 4','2023-08-03','2025-08-03','Operational','Good','Standard 2.5-ton pallet jack.'),
('WH-AC-001','Air Conditioning Unit','Facility Equipment','AC-87501','Office Block','2020-04-12','2024-04-12','Breakdown','Poor','Central office air conditioner.'),
('WH-LD-001','Dock Leveler','Loading Dock','DL-4512','Dock 3','2021-10-10','2026-10-10','Operational','Fair','Hydraulic dock leveler.'),
('WH-SL-001','Storage Lift','Storage System','SL-1033','High Bay Storage','2022-05-07','2027-05-07','Under Maintenance','Fair','Vertical storage lift.'),
('WH-GN-001','Backup Generator','Power Equipment','GEN-2910','Utility Room','2019-09-09','2023-09-09','Operational','Fair','Emergency backup power generator.');
INSERT INTO maintenance_requests(request_code,equipment_id,reported_by,problem_description,priority,status,created_at) VALUES
('MR-202609-001',2,4,'Hydraulic lift is moving slowly and making noise.','High','Assigned','2026-09-01 09:30:00'),
('MR-202609-002',5,5,'Air conditioner has stopped cooling the office area.','Critical','In Progress','2026-09-02 10:15:00'),
('MR-202609-003',7,4,'Storage lift door sensor intermittently fails.','Medium','Pending','2026-09-04 14:00:00'),
('MR-202608-014',3,5,'Conveyor belt alignment was off.','Medium','Completed','2026-08-20 08:00:00'),
('MR-202608-015',8,4,'Generator battery warning displayed.','High','Completed','2026-08-25 11:30:00');
INSERT INTO work_orders(work_order_code,request_id,equipment_id,assigned_to,problem_description,priority,start_date,due_date,status,repair_notes) VALUES
('WO-202609-001',1,2,2,'Hydraulic lift is moving slowly and making noise.','High','2026-09-02','2026-09-09','Assigned',NULL),
('WO-202609-002',2,5,3,'Air conditioner has stopped cooling the office area.','Critical','2026-09-02','2026-09-05','In Progress','Diagnosing refrigerant leak.'),
('WO-202608-014',4,3,2,'Conveyor belt alignment was off.','Medium','2026-08-20','2026-08-21','Completed','Aligned belt and replaced worn roller.'),
('WO-202608-015',5,8,3,'Generator battery warning displayed.','High','2026-08-25','2026-08-26','Completed','Replaced starter battery and tested output.');
INSERT INTO maintenance_records(equipment_id,work_order_id,technician_id,maintenance_date,problem_description,action_taken,status,labour_cost,parts_cost,other_cost,total_cost) VALUES
(3,3,2,'2026-08-21','Conveyor belt alignment was off.','Aligned belt and replaced worn roller.','Completed',120.00,85.00,0.00,205.00),
(8,4,3,'2026-08-26','Generator battery warning displayed.','Replaced starter battery and tested output.','Completed',90.00,165.00,15.00,270.00),
(1,NULL,2,'2026-07-15','Forklift brake inspection due.','Completed scheduled brake inspection.','Completed',75.00,0.00,0.00,75.00);
