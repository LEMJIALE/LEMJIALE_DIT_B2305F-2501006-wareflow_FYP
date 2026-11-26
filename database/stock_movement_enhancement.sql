-- Run once in phpMyAdmin after selecting the warehouse_maintenance database.
-- Allows detailed inbound/outbound types and internal location transfers.
ALTER TABLE stock_movements
    MODIFY COLUMN movement_type VARCHAR(80) NOT NULL;
