<?php
session_start();
include 'db.php'; // Database connection

// Check user role
if (!isset($_SESSION['user_id'])) {
    header("Location: warehouse_login.php");
    exit();
}
$is_admin = ($_SESSION['role'] == 'admin');

// Create - Add a new product (Admin only)
if ($is_admin && isset($_POST['add'])) {
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $threshold = $_POST['threshold'];
    $products_per_box = $_POST['products_per_box'];
    $price = $_POST['price']; // New field for price
    $query = "INSERT INTO inventory (product_name, quantity, product_description, threshold, products_per_box, price) VALUES ('$product_name', '$quantity', '$description', '$threshold', '$products_per_box', '$price')";
    mysqli_query($conn, $query);
    header("Location: inventory.php");
}

// Delete - Remove a product (Admin only)
if ($is_admin && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM inventory WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: inventory.php");
}

// Update - Modify an existing product (Admin only)
if ($is_admin && isset($_POST['update'])) {
    $id = $_POST['id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $threshold = $_POST['threshold'];
    $products_per_box = $_POST['products_per_box'];
    $price = $_POST['price']; // New field for price
    $query = "UPDATE inventory SET product_name='$product_name', quantity='$quantity', product_description='$description', threshold='$threshold', products_per_box='$products_per_box', price='$price' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: inventory.php");
}

// Read - Fetch all products
$query = "SELECT * FROM inventory";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Olympus Warehouse</title>
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
    <h1>Warehouse Inventory</h1>
    
    <!-- Product Form Removed -->

    <!-- Inventory Table -->
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Description</th>
                <th>Threshold</th>
                <th>Products per Box</th>
                <th>Price</th> <!-- New column for price -->
                <?php if ($is_admin): ?>
                    <th>Action</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['product_name']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['product_description']; ?></td>
                    <td><?php echo $row['threshold']; ?></td>
                    <td><?php echo $row['products_per_box']; ?></td>
                    <td><?php echo $row['price']; ?></td> <!-- New field for price -->
                    <?php if ($is_admin): ?>
                        <td>
                            <a href="update.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="inventory.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>