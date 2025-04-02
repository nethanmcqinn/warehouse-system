<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php'; // Include the database connection

// Fetch inventory data
$inventory_query = "SELECT * FROM inventory";
$inventory_result = $conn->query($inventory_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
    <h2>Staff Dashboard</h2>
    <a href="staff_dashboard.php">Dashboard</a>
    <a href="inventory.php">View Inventory</a>
    <a href="staff_orders.php">Manage Orders</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>

<div class="main-content">
    <h1>Welcome, Staff</h1>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="inventory-tab" data-bs-toggle="tab" href="#inventory">Inventory</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#orders">Manage Orders</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3">
        <!-- Inventory Section -->
        <div class="tab-pane fade show active" id="inventory">
            <h2>Inventory</h2>
            <table class="table table-bordered">
                <thead class="bg-dark text-light">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Description</th>
                        <th>Threshold</th>
                        <th>Products per Box</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $inventory_result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['product_name']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo $row['product_description']; ?></td>
                            <td><?php echo $row['threshold']; ?></td>
                            <td><?php echo $row['products_per_box']; ?></td>
                            <td><?php echo $row['price']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Orders Section -->
        <div class="tab-pane fade" id="orders">
            <h2>Manage Orders</h2>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders_query = "SELECT * FROM orders";
                    $orders_result = $conn->query($orders_query);
                    while ($row = $orders_result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td><?php echo $row['order_date']; ?></td>
                            <td><?php echo $row['total_amount']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
