<?php
session_start();
include 'db.php'; // Include the database connection
require_once 'notifications.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: warehouse_login.php");
    exit();
}

// Get the order ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM orders WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
}

// Update the order
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $customer_name = $_POST['customer_name'];
    $order_date = $_POST['order_date'];
    $total_amount = $_POST['total_amount'];
    $status = $_POST['status'];
    $query = "UPDATE orders SET customer_name=?, order_date=?, total_amount=?, status=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdsi", $customer_name, $order_date, $total_amount, $status, $id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    if ($orderId && $status) {
        // Update order status and create notification
        $success = updateOrderStatus($orderId, $status, $_SESSION['user_id'], $conn);

        if ($success) {
            // Add notes if provided
            if ($notes) {
                $stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $orderId, $status, $notes, $_SESSION['user_id']);
                $stmt->execute();
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating order status']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or status']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Update Order</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
            <div class="mb-3">
                <label class="form-label">Customer Name</label>
                <input type="text" name="customer_name" class="form-control" value="<?php echo $order['customer_name']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Order Date</label>
                <input type="date" name="order_date" class="form-control" value="<?php echo $order['order_date']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Total Amount</label>
                <input type="number" step="0.01" name="total_amount" class="form-control" value="<?php echo $order['total_amount']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Pending" <?php if ($order['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Completed" <?php if ($order['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>
            <button type="submit" name="update" class="btn btn-success">Update Order</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>