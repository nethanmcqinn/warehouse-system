<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php'; // Include the database connection

// Handle order actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $order_id = $_GET['id'];

    // Fetch the order details
    $order_query = "SELECT * FROM orders WHERE id=?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();

    if ($action == 'accept') {
        // Update order status to 'Confirmed'
        $update_order_query = "UPDATE orders SET status='Confirmed' WHERE id=?";
        $stmt = $conn->prepare($update_order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Reduce the quantity in the inventory
        $product_name = $order['product'];
        $quantity = $order['quantity'];
        $update_inventory_query = "UPDATE inventory SET quantity = quantity - ? WHERE product_name = ?";
        $stmt = $conn->prepare($update_inventory_query);
        $stmt->bind_param("is", $quantity, $product_name);
        $stmt->execute();

        // Add to deliveries table
        $delivery_date = date('Y-m-d');
        $status = 'Pending';
        $add_delivery_query = "INSERT INTO deliveries (order_id, customer_name, product, quantity, delivery_date, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($add_delivery_query);
        $stmt->bind_param("ississ", $order_id, $order['customer_name'], $order['product'], $order['quantity'], $delivery_date, $status);
        $stmt->execute();
    } elseif ($action == 'cancel') {
        // Update order status to 'Canceled'
        $update_order_query = "UPDATE orders SET status='Canceled' WHERE id=?";
        $stmt = $conn->prepare($update_order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Increase the quantity in the inventory
        $product_name = $order['product'];
        $quantity = $order['quantity'];
        $update_inventory_query = "UPDATE inventory SET quantity = quantity + ? WHERE product_name = ?";
        $stmt = $conn->prepare($update_inventory_query);
        $stmt->bind_param("is", $quantity, $product_name);
        $stmt->execute();
    } elseif ($action == 'pending') {
        // Update order status to 'Pending'
        $update_order_query = "UPDATE orders SET status='Pending' WHERE id=?";
        $stmt = $conn->prepare($update_order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    } elseif ($action == 'delete') {
        // Delete associated deliveries first
        $delete_deliveries_query = "DELETE FROM deliveries WHERE order_id=?";
        $stmt = $conn->prepare($delete_deliveries_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Delete the order
        $delete_order_query = "DELETE FROM orders WHERE id=?";
        $stmt = $conn->prepare($delete_order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    } elseif ($action == 'deliver') {
        // Update delivery status to 'Delivered'
        $update_delivery_query = "UPDATE deliveries SET status='Delivered' WHERE id=?";
        $stmt = $conn->prepare($update_delivery_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    } elseif ($action == 'delete_delivery') {
        // Delete the delivery
        $delete_delivery_query = "DELETE FROM deliveries WHERE id=?";
        $stmt = $conn->prepare($delete_delivery_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    }

    header("Location: admin_dashboard.php");
    exit();
}

// Fetch orders from the orders table
$order_query = "SELECT * FROM orders";
$order_result = $conn->query($order_query);

// Fetch deliveries from the deliveries table
$delivery_query = "SELECT * FROM deliveries";
$delivery_result = $conn->query($delivery_query);

// Fetch supplier deliveries
$query = "SELECT * FROM supplier_deliveries";
$result = $conn->query($query);

// Fetch inventory data to check for low stock
$inventory_query = "SELECT * FROM inventory";
$inventory_result = $conn->query($inventory_query);
$low_stock_alerts = [];
while ($inventory_row = $inventory_result->fetch_assoc()) {
    if ($inventory_row['quantity'] < $inventory_row['threshold']) {
        $low_stock_alerts[] = $inventory_row;
    }
}

// Fetch suppliers from the suppliers table
$supplier_query = "SELECT * FROM suppliers";
$supplier_result = $conn->query($supplier_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            display: block;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .sidebar a:hover {
            background: #555;
        }

        .sidebar .logout {
            margin-top: auto;
            background-color: #d9534f;
        }

        .sidebar .logout:hover {
            background-color: #c9302c;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .main-content h1 {
            margin-bottom: 1.5rem;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 2rem;
        }

        .table {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #333;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table tr:hover {
            background-color: #f5f5f5;
        }

        .btn-warning {
            background-color: #ffc107;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-weight: bold;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-weight: bold;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-sm {
            font-size: 12px;
        }

        .bg-dark {
            background-color: #333 !important;
        }

        .text-light {
            color: #fff !important;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="inventory.php">Manage Inventory</a>
    <a href="orders.php">Manage Orders</a>
    <a href="reports.php">Reports</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>
<div class="main-content">
    <h1>Welcome, Admin</h1>
        <!-- Low Stock Alerts -->
        <?php if (!empty($low_stock_alerts)): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Low Stock Alert!</h4>
            <p>The following products have low stock:</p>
            <ul>
                <?php foreach ($low_stock_alerts as $alert): ?>
                    <li><?php echo $alert['product_name']; ?>: <?php echo $alert['quantity']; ?> remaining (Threshold: <?php echo $alert['threshold']; ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <h2>Orders</h2>
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $order_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo $row['total_amount']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['product']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>
                        <a href="admin_dashboard.php?action=accept&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                        <a href="admin_dashboard.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Cancel</a>
                        <a href="admin_dashboard.php?action=pending&id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Pending</a>
                        <a href="admin_dashboard.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Deliveries Table -->
    <h2>Deliveries</h2>
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $delivery_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['order_id']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['product']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['delivery_date']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <a href="admin_dashboard.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Mark as Delivered</a>
                        <a href="admin_dashboard.php?action=delete_delivery&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
