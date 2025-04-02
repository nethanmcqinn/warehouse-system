<?php
session_start();
include 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: warehouse_login.php");
    exit();
}

// Read - Fetch user activity data
$query = "SELECT * FROM user_activity"; // Replace with your actual user activity query
$result = $conn->query($query);

// Check if the table exists
if (!$result) {
    $error = $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Report - Olympus Warehouse</title>
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

        .summary-card {
            padding: 20px;
            border-radius: 8px;
            color: white;
            height: 100%;
            margin-bottom: 20px;
        }

        .summary-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-card p {
            margin: 0;
            opacity: 0.8;
        }

        .summary-card i {
            font-size: 2.5rem;
            opacity: 0.4;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .blue-gradient {
            background: linear-gradient(45deg, #4e73df, #6c8aef);
        }

        .green-gradient {
            background: linear-gradient(45deg, #1cc88a, #36e9b6);
        }

        .yellow-gradient {
            background: linear-gradient(45deg, #f6c23e, #f8d472);
        }

        .red-gradient {
            background: linear-gradient(45deg, #e74a3b, #ed766a);
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
    <h1>User Activity Report</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            Error: <?php echo $error; ?>
        </div>
    <?php else: ?>
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="summary-card blue-gradient position-relative">
                    <div>
                        <p>Total Users</p>
                        <h3>1,200</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 5.2%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card green-gradient position-relative">
                    <div>
                        <p>Active Users</p>
                        <h3>950</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 3.8%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card yellow-gradient position-relative">
                    <div>
                        <p>New Users</p>
                        <h3>150</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 2.1%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card red-gradient position-relative">
                    <div>
                        <p>Inactive Users</p>
                        <h3>100</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-down"></i> 1.5%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-user-times"></i>
                </div>
            </div>
        </div>

        <!-- User Activity Table -->
        <table class="table table-bordered">
            <thead class="bg-dark text-light">
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Activity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['activity']; ?></td>
                        <td><?php echo $row['timestamp']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>