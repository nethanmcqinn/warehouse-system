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

// Read - Fetch all orders
$query = "SELECT * FROM orders";
$result = $conn->query($query);
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
    <a href="inventory.php">Manage Inventory</a>
    <a href="orders.php">Manage Orders</a>
    <a href="reports.php">Reports</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>

<div class="main-content">
    <h1>Manage Orders</h1>
    
    <!-- Add Order Form -->
    <form method="POST" class="mb-3">
        <div class="input-group">
            <input type="text" name="customer_name" class="form-control" placeholder="Customer Name" required>
            <input type="date" name="order_date" class="form-control" placeholder="Order Date" required>
            <input type="number" step="0.01" name="total_amount" class="form-control" placeholder="Total Amount" required>
            <select name="status" class="form-control" required>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
            </select>
            <button type="submit" name="add" class="btn btn-primary">Add Order</button>
        </div>
    </form>

    <!-- Orders Table -->
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo $row['total_amount']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <a href="orders.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Edit</button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="orders.php" method="POST">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo $row['customer_name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="order_date" class="form-label">Order Date</label>
                                        <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo $row['order_date']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount</label>
                                        <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" value="<?php echo $row['total_amount']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Completed" <?php if ($row['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update" class="btn btn-primary">Update Order</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>