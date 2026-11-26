USE warehouse_maintenance;

CREATE TABLE IF NOT EXISTS warehouse_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(40) NOT NULL UNIQUE,
    zone_name VARCHAR(80) NOT NULL,
    rack_number VARCHAR(40) NULL,
    shelf_number VARCHAR(40) NULL,
    capacity INT NOT NULL DEFAULT 500,
    status ENUM('Active','Blocked','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(150) NOT NULL,
    barcode VARCHAR(100) NULL UNIQUE,
    category VARCHAR(80) NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    current_quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
    location_id INT NULL,
    description TEXT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_code VARCHAR(50) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    location_id INT NULL,
    movement_type ENUM('Inbound','Outbound','Picking','Adjustment') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    reference_no VARCHAR(80) NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO warehouse_locations(location_code,zone_name,rack_number,shelf_number,capacity,status) VALUES
('A-01-01','Zone A','01','01',500,'Active'),('A-01-02','Zone A','01','02',500,'Active'),
('B-01-01','Zone B','01','01',500,'Active'),('C-01-01','Zone C','01','01',500,'Active'),('RECEIVING-01','Receiving','—','—',500,'Active');

INSERT IGNORE INTO products(product_code,product_name,barcode,category,unit,current_quantity,location_id,description) VALUES
('PRD-001','Wireless Headphones','9556001000011','Electronics','pcs',120,1,'Consumer electronics product.'),
('PRD-002','Stainless Steel Water Bottle','9556001000028','Home & Living','pcs',75,2,'750ml insulated bottle.'),
('PRD-003','Cotton T-Shirt','9556001000035','Apparel','pcs',180,3,'Assorted sizes and colours.');

INSERT IGNORE INTO stock_movements(movement_code,product_id,location_id,movement_type,quantity,reference_no,notes,created_by) VALUES
('IN-202609-001',1,1,'Inbound',120,'GRN-001','Opening stock',1),
('IN-202609-002',2,2,'Inbound',75,'GRN-002','Opening stock',1),
('IN-202609-003',3,3,'Inbound',180,'GRN-003','Opening stock',1);
