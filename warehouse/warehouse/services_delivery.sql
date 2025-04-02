-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL
);

-- Create delivery table
CREATE TABLE IF NOT EXISTS deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_date DATE NOT NULL,
    special_instructions TEXT,
    status ENUM('Pending', 'Processing', 'In Transit', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES inventory(id)
);

-- Insert sample services
INSERT INTO services (service_name, description, price) VALUES
('Standard Delivery', 'Regular delivery service within 3-5 business days', 10.00),
('Express Delivery', 'Fast delivery service within 1-2 business days', 25.00),
('Storage Service', 'Secure storage for your products', 50.00),
('Packaging Service', 'Professional packaging for fragile items', 15.00);