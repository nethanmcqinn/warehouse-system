-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add business_type column if it doesn't exist
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS business_type VARCHAR(50) NOT NULL DEFAULT 'Retail Store' AFTER address;

-- Create supplier_inventory table
CREATE TABLE IF NOT EXISTS supplier_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    threshold INT NOT NULL DEFAULT 10,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES inventory(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_orders table
CREATE TABLE IF NOT EXISTS supplier_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Processing', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_order_items table
CREATE TABLE IF NOT EXISTS supplier_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES inventory(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_deliveries table
CREATE TABLE IF NOT EXISTS supplier_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'In Transit', 'Delivered') DEFAULT 'Pending',
    tracking_number VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Start transaction
START TRANSACTION;

-- First, clear existing data to avoid conflicts
DELETE FROM supplier_deliveries;
DELETE FROM supplier_order_items;
DELETE FROM supplier_orders;
DELETE FROM supplier_inventory;
DELETE FROM suppliers;

-- Insert sample suppliers and get their IDs
INSERT INTO suppliers (supplier_name, contact_name, contact_email, contact_phone, address, business_type) VALUES
('Mega Mall', 'John Smith', 'john@megamall.com', '0123456789', '123 Shopping Street, Kuala Lumpur', 'Mall'),
('Tech Hub', 'Sarah Lee', 'sarah@techhub.com', '0123456780', '456 Tech Park, Petaling Jaya', 'Retail Store'),
('Urban Market', 'Mike Chen', 'mike@urbanmarket.com', '0123456781', '789 Market Square, Subang Jaya', 'Supermarket'),
('Gadget World', 'Lisa Wong', 'lisa@gadgetworld.com', '0123456782', '321 Electronics Avenue, Penang', 'Electronics Store'),
('Fashion Central', 'David Tan', 'david@fashioncentral.com', '0123456783', '654 Fashion Street, Johor Bahru', 'Department Store');

-- Get the IDs of the inserted suppliers
SET @mega_mall_id = LAST_INSERT_ID();
SET @tech_hub_id = @mega_mall_id + 1;
SET @urban_market_id = @mega_mall_id + 2;
SET @gadget_world_id = @mega_mall_id + 3;
SET @fashion_central_id = @mega_mall_id + 4;

-- Insert sample supplier inventory using the actual supplier IDs
INSERT INTO supplier_inventory (supplier_id, product_id, current_stock, threshold) VALUES
(@mega_mall_id, 'PROD67ed42073261c', 15, 20),  -- Mega Mall - Iphone 16
(@tech_hub_id, 'PROD67ed42073261c', 8, 15),   -- Tech Hub - Iphone 16
(@urban_market_id, 'PROD67ed42073261c', 5, 10),   -- Urban Market - Iphone 16
(@gadget_world_id, 'PROD67ed42073261c', 12, 15),  -- Gadget World - Iphone 16
(@fashion_central_id, 'PROD67ed42073261c', 3, 10);   -- Fashion Central - Iphone 16

-- Insert sample supplier orders
INSERT INTO supplier_orders (supplier_id, order_date, status, total_amount, notes) VALUES
(@mega_mall_id, '2024-03-15 10:00:00', 'Delivered', 500000.00, 'Regular monthly order'),
(@tech_hub_id, '2024-03-20 14:30:00', 'Processing', 300000.00, 'Urgent order for weekend sale'),
(@urban_market_id, '2024-03-25 09:15:00', 'Pending', 200000.00, 'New store opening stock'),
(@gadget_world_id, '2024-03-28 11:45:00', 'Delivered', 400000.00, 'Replacement stock'),
(@fashion_central_id, '2024-04-01 16:20:00', 'Processing', 250000.00, 'Seasonal promotion');

-- Get the IDs of the inserted orders
SET @order1_id = LAST_INSERT_ID();
SET @order2_id = @order1_id + 1;
SET @order3_id = @order1_id + 2;
SET @order4_id = @order1_id + 3;
SET @order5_id = @order1_id + 4;

-- Insert sample supplier order items
INSERT INTO supplier_order_items (order_id, product_id, quantity, unit_price) VALUES
(@order1_id, 'PROD67ed42073261c', 5, 10000000.00),
(@order2_id, 'PROD67ed42073261c', 3, 10000000.00),
(@order3_id, 'PROD67ed42073261c', 2, 10000000.00),
(@order4_id, 'PROD67ed42073261c', 4, 10000000.00),
(@order5_id, 'PROD67ed42073261c', 2, 10000000.00);

-- Insert sample supplier deliveries
INSERT INTO supplier_deliveries (order_id, delivery_date, status, tracking_number, notes) VALUES
(@order1_id, '2024-03-16 15:00:00', 'Delivered', 'TRK001', 'Delivered to store manager'),
(@order2_id, '2024-03-21 10:00:00', 'In Transit', 'TRK002', 'Expected delivery today'),
(@order3_id, '2024-03-26 09:00:00', 'Pending', 'TRK003', 'Awaiting pickup'),
(@order4_id, '2024-03-29 14:00:00', 'Delivered', 'TRK004', 'Signed by security'),
(@order5_id, '2024-04-02 11:00:00', 'In Transit', 'TRK005', 'Out for delivery');

-- Commit the transaction
COMMIT; 