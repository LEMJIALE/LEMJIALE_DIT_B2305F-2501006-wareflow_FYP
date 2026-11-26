USE warehouse_maintenance;

-- Set every existing storage location capacity to 500 pcs.
UPDATE warehouse_locations SET capacity = 500;

-- Ensure newly created locations default to 500 pcs.
ALTER TABLE warehouse_locations MODIFY capacity INT NOT NULL DEFAULT 500;
