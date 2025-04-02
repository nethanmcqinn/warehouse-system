<?php
session_start();
include 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: warehouse_login.php");
    exit();
}

// Read - Fetch sales data
$query = "SELECT * FROM sales"; // Replace with your actual sales query
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
    <title>Sales Report - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 15px;
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
    <h1>Sales Report</h1>

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
                        <p>Total Sales</p>
                        <h3>$428,651</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 12.8%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card green-gradient position-relative">
                    <div>
                        <p>Orders Processed</p>
                        <h3>1,485</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 8.3%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card yellow-gradient position-relative">
                    <div>
                        <p>Average Order Value</p>
                        <h3>$289</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-up"></i> 4.2%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-card red-gradient position-relative">
                    <div>
                        <p>Return Rate</p>
                        <h3>4.8%</h3>
                        <p><span class="text-white"><i class="fas fa-arrow-down"></i> 1.5%</span> vs previous period</p>
                    </div>
                    <i class="fas fa-undo"></i>
                </div>
            </div>
        </div>

        <!-- Sales Trend Chart -->
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Sales Trend</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <table class="table table-bordered">
            <thead class="bg-dark text-light">
                <tr>
                    <th>Month</th>
                    <th>Total Sales</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['month']; ?></td>
                        <td><?php echo $row['total_sales']; ?></td>
                        <td><?php echo $row['profit']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sales Trend Chart
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const salesTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            datasets: [{
                label: 'Total Sales',
                data: [10000, 12000, 15000, 13000, 14000, 16000, 17000, 18000, 19000, 20000, 21000, 22000],
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>