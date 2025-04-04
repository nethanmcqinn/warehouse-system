<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
?>
<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="manage_products.php">Manage Products</a>
    <a href="inventory.php">Manage Inventory</a>
    <a href="orders.php">Manage Orders</a>
    <a href="manage_suppliers.php">Manage Suppliers</a>
    <a href="reports.php">Reports</a>
    <a href="settings.php">Settings</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>

<style>
.sidebar {
    width: 250px;
    background-color: #333;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 20px;
    min-height: 100vh;
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
</style> 