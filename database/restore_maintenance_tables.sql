-- Run once in phpMyAdmin after selecting warehouse_maintenance.
-- This creates only missing maintenance tables; it does not delete data.
USE warehouse_maintenance;

CREATE TABLE IF NOT EXISTS maintenance_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_code VARCHAR(40) NOT NULL UNIQUE,
  equipment_id INT NOT NULL,
  reported_by INT NOT NULL,
  problem_description TEXT NOT NULL,
  priority ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  status ENUM('Pending','Assigned','In Progress','Completed','Rejected') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  FOREIGN KEY (reported_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_code VARCHAR(40) NOT NULL UNIQUE,
  request_id INT NULL,
  equipment_id INT NOT NULL,
  assigned_to INT NULL,
  problem_description TEXT NOT NULL,
  priority ENUM('Low','Medium','High','Critical') NOT NULL,
  start_date DATE NULL,
  due_date DATE NULL,
  completion_date DATE NULL,
  status ENUM('Assigned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Assigned',
  repair_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES maintenance_requests(id),
  FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipment_id INT NOT NULL,
  work_order_id INT NULL,
  technician_id INT NULL,
  maintenance_date DATE NOT NULL,
  problem_description TEXT NOT NULL,
  action_taken TEXT NOT NULL,
  status ENUM('Completed','Cancelled') NOT NULL DEFAULT 'Completed',
  labour_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  other_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (technician_id) REFERENCES users(id)
) ENGINE=InnoDB;
