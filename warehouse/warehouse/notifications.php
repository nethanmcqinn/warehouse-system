<?php
require_once 'db.php';

class Notifications {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function createNotification($userId, $message, $type, $link = null) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $message, $type, $link);
        return $stmt->execute();
    }

    public function getUnreadNotifications($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function markAsRead($notificationId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }

    public function createOrderStatusNotification($orderId, $status, $userId) {
        $message = "";
        switch($status) {
            case 'accepted':
                $message = "Your order #$orderId has been accepted and is being processed.";
                break;
            case 'processing':
                $message = "Your order #$orderId is now being processed.";
                break;
            case 'shipped':
                $message = "Your order #$orderId has been shipped!";
                break;
            case 'delivered':
                $message = "Your order #$orderId has been delivered.";
                break;
            case 'cancelled':
                $message = "Your order #$orderId has been cancelled.";
                break;
            default:
                $message = "Your order #$orderId status has been updated to: $status";
        }
        
        return $this->createNotification(
            $userId,
            $message,
            'order_update',
            "my_orders.php?order_id=$orderId"
        );
    }
}

// Function to update order status and create notification
function updateOrderStatus($orderId, $status, $adminId, $conn) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();

        // Add to order status history
        $stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $orderId, $status, $adminId);
        $stmt->execute();

        // Get customer ID for the order
        $stmt = $conn->prepare("SELECT customer_id FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        // Create notification
        $notifications = new Notifications($conn);
        $notifications->createOrderStatusNotification($orderId, $status, $order['customer_id']);

        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

// Function to get user notifications
function getUserNotifications($userId, $conn) {
    $notifications = new Notifications($conn);
    return $notifications->getUnreadNotifications($userId);
}
?> 