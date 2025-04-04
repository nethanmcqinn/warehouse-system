<?php
session_start();
require_once 'db.php';
require_once 'notifications.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

    if ($notificationId) {
        $notifications = new Notifications($conn);
        $success = $notifications->markAsRead($notificationId);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error marking notification as read']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 