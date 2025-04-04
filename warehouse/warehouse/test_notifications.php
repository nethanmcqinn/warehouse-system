<?php
require_once 'db.php';
require_once 'notifications.php';

// Test creating a notification
try {
    $notifications = new Notifications($conn);
    
    // Create a test notification
    $result = $notifications->createNotification(
        1, // Assuming user ID 1 exists
        "This is a test notification",
        "test",
        "index.php"
    );
    
    if ($result) {
        echo "Test notification created successfully!<br>";
        
        // Get unread notifications
        $unread = $notifications->getUnreadNotifications(1);
        echo "Unread notifications for user 1: " . count($unread) . "<br>";
        
        // Display notifications
        foreach ($unread as $notification) {
            echo "Notification: " . $notification['message'] . " (Created at: " . $notification['created_at'] . ")<br>";
        }
    } else {
        echo "Failed to create test notification<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 