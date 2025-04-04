-- Add QR code column to inventory table
ALTER TABLE inventory
ADD COLUMN qr_code VARCHAR(255) AFTER image_url;

-- Modify inventory id column to have AUTO_INCREMENT
ALTER TABLE inventory MODIFY id int(11) NOT NULL AUTO_INCREMENT;

-- First, create a temporary table with the correct structure
CREATE TABLE inventory_temp (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `product_description` text DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `threshold` int(11) DEFAULT 10,
  `category` varchar(100) NOT NULL,
  `status` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `products_per_box` int(11) NOT NULL DEFAULT 1,
  `minimum_order_quantity` int(11) DEFAULT 1,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copy data from the original table to the temporary table
INSERT INTO inventory_temp (product_id, product_name, quantity, cost_price, price, product_description, 
                          threshold, category, status, products_per_box, minimum_order_quantity, supplier_id, 
                          created_at, updated_at)
SELECT product_id, product_name, quantity, cost_price, price, product_description, 
       threshold, category, status, products_per_box, minimum_order_quantity, supplier_id, 
       created_at, updated_at
FROM inventory;

-- Drop the original table
DROP TABLE inventory;

-- Rename the temporary table to inventory
RENAME TABLE inventory_temp TO inventory;

-- Add foreign key constraint back
ALTER TABLE inventory
ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

-- Create product_images table
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Modify orders table to remove product-specific columns
ALTER TABLE `orders` 
DROP COLUMN `product_id`,
DROP COLUMN `quantity`,
DROP COLUMN `unit_price`;

-- First, create a temporary table with the correct structure
CREATE TABLE customers_temp (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Malaysia',
  `join_date` date NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copy data from the original table to the temporary table
INSERT INTO customers_temp (fname, lname, customer_name, email, phone, address_line1, address_line2, 
                          city, state, postal_code, country, join_date, status, password)
SELECT fname, lname, customer_name, email, phone, address_line1, address_line2, 
       city, state, postal_code, country, join_date, status, password
FROM customers;

-- Drop the original table
DROP TABLE customers;

-- Rename the temporary table to customers
RENAME TABLE customers_temp TO customers;

-- Add auto-increment to other tables if needed
ALTER TABLE customer_addresses MODIFY id INT AUTO_INCREMENT;
ALTER TABLE deliveries MODIFY id INT AUTO_INCREMENT;
ALTER TABLE financials MODIFY id INT AUTO_INCREMENT;
ALTER TABLE inventory MODIFY id INT AUTO_INCREMENT;
ALTER TABLE inventory_audit MODIFY id INT AUTO_INCREMENT;
ALTER TABLE orders MODIFY id INT AUTO_INCREMENT;
ALTER TABLE product_images MODIFY id INT AUTO_INCREMENT;
ALTER TABLE sales MODIFY id INT AUTO_INCREMENT;
ALTER TABLE services MODIFY id INT AUTO_INCREMENT;
ALTER TABLE suppliers MODIFY id INT AUTO_INCREMENT;
ALTER TABLE supplier_deliveries MODIFY id INT AUTO_INCREMENT;
ALTER TABLE transactions MODIFY id INT AUTO_INCREMENT;
ALTER TABLE users MODIFY id INT AUTO_INCREMENT;
ALTER TABLE user_activity MODIFY activity_id INT AUTO_INCREMENT;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    link VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES customers(customer_id)
);

-- Add order_status_history table for tracking order status changes
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Add notification_preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT PRIMARY KEY,
    email_notifications BOOLEAN DEFAULT TRUE,
    order_updates BOOLEAN DEFAULT TRUE,
    promotional_emails BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES customers(customer_id)
);