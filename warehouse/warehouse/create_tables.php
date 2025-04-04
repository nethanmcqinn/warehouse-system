<?php
require_once 'db.php';

// SQL to create notifications table
$notifications_table = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    link VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES customers(customer_id)
)";

// SQL to create order_status_history table
$order_status_history_table = "
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";

// SQL to create notification_preferences table
$notification_preferences_table = "
CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT PRIMARY KEY,
    email_notifications BOOLEAN DEFAULT TRUE,
    order_updates BOOLEAN DEFAULT TRUE,
    promotional_emails BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES customers(customer_id)
)";

// Execute the create table queries
try {
    $conn->query($notifications_table);
    echo "Notifications table created successfully<br>";
    
    $conn->query($order_status_history_table);
    echo "Order status history table created successfully<br>";
    
    $conn->query($notification_preferences_table);
    echo "Notification preferences table created successfully<br>";
    
    echo "All tables created successfully!";
} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?> 