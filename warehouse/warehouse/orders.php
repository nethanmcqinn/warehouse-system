<?php
session_start();
include 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: warehouse_login.php");
    exit();
}

// Create - Add a new order
if (isset($_POST['add'])) {
    $customer_name = $_POST['customer_name'];
    $order_date = $_POST['order_date'];
    $total_amount = $_POST['total_amount'];
    $status = $_POST['status'];
    $query = "INSERT INTO orders (customer_name, order_date, total_amount, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssds", $customer_name, $order_date, $total_amount, $status);
    $stmt->execute();
    header("Location: orders.php");
}

// Delete - Remove an order
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM orders WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: orders.php");
}

// Update - Modify an existing order
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
    header("Location: orders.php");
}

// Get orders with customer and product information
$order_query = "SELECT o.id, o.order_date, o.total_amount, o.status, 
                CONCAT(c.fname, ' ', c.lname) as customer_name,
                GROUP_CONCAT(i.product_name) as products,
                GROUP_CONCAT(oi.quantity) as quantities
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN inventory i ON oi.product_id = i.product_id
                GROUP BY o.id
                ORDER BY o.order_date DESC";
$order_result = $conn->query($order_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Olympus Warehouse</title>
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

        .mt-5 {
            margin-top: 3rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .text-center {
            text-align: center !important;
        }

        .table-bordered {
            border: 1px solid #ddd;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="manage_products.php">Manage Products</a>
    <a href="inventory.php">Manage Inventory</a>
    <a href="orders.php">Manage Orders</a>
    <a href="reports.php">Reports</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>

<div class="main-content">
    <h1>Manage Orders</h1>
    
    <!-- Order Form Removed -->

    <!-- Orders Table -->
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Products</th>
                <th>Quantities</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $order_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown Customer'); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $row['status'] == 'Completed' ? 'success' : 
                                ($row['status'] == 'Processing' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['products'] ?? 'No products'); ?></td>
                    <td><?php echo $row['quantities'] ?? '0'; ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=accept&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                            <a href="?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Cancel</a>
                            <a href="?action=pending&id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Pending</a>
                            <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this order?');">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>