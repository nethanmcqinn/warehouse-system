<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Reports</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .container {
            margin-top: 30px;
        }
        .report-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,.1);
            height: 100%;
            border: none;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.15);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .card-body {
            padding: 25px;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card-text {
            opacity: 0.8;
            margin-bottom: 20px;
        }
        .btn-view {
            background-color: #fff;
            color: #333;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-view:hover {
            background-color: rgba(255, 255, 255, 0.8);
        }
        .header-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
        }
        .bg-inventory {
            background: linear-gradient(45deg, #4e73df, #6c8aef);
        }
        .bg-sales {
            background: linear-gradient(45deg, #1cc88a, #36e9b6);
        }
        .bg-orders {
            background: linear-gradient(45deg, #f6c23e, #f8d472);
        }
        .bg-suppliers {
            background: linear-gradient(45deg, #e74a3b, #ed766a);
        }
        .bg-customers {
            background: linear-gradient(45deg, #36b9cc, #5dd7ea);
        }
        .bg-activity {
            background: linear-gradient(45deg, #6f42c1, #9071d4);
        }
        .bg-financial {
            background: linear-gradient(45deg, #5a5c69, #858796);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-pie me-2"></i>Warehouse Reports</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            Reports
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item" href="inventory_report.php">Inventory Report</a></li>
                            <li><a class="dropdown-item" href="sales_report.php">Sales Report</a></li>
                            <li><a class="dropdown-item" href="order_report.php">Order Report</a></li>
                            <li><a class="dropdown-item" href="supplier_report.php">Supplier Report</a></li>
                            <li><a class="dropdown-item" href="customer_report.php">Customer Report</a></li>
                            <li><a class="dropdown-item" href="user_activity.php">User Activity Log</a></li>
                            <li><a class="dropdown-item" href="financial_report.php">Financial Report</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php"><i class="fas fa-cog me-1"></i>Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-question-circle me-1"></i>Help</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Warehouse Reports Dashboard</h2>
                    <p class="text-muted mb-0">Select a report category to view detailed analytics and insights</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary"><i class="fas fa-sync-alt me-2"></i>Refresh Data</button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Inventory Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-inventory text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                        <h5 class="card-title">Inventory Report</h5>
                        <p class="card-text">Track stock levels, product inventory, and low stock alerts.</p>
                        <a href="inventory_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- Sales Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-sales text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="card-title">Sales Report</h5>
                        <p class="card-text">Analyze sales performance, trends, and revenue metrics.</p>
                        <a href="sales_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- Order Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-orders text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-cart-shopping"></i>
                        </div>
                        <h5 class="card-title">Order Report</h5>
                        <p class="card-text">Monitor order status, processing times, and fulfillment rates.</p>
                        <a href="order_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- Supplier Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-suppliers text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h5 class="card-title">Supplier Report</h5>
                        <p class="card-text">Evaluate supplier performance, delivery times, and reliability.</p>
                        <a href="supplier_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- Customer Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-customers text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title">Customer Report</h5>
                        <p class="card-text">Analyze customer demographics, purchasing patterns, and loyalty.</p>
                        <a href="customer_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- User Activity Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-activity text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <h5 class="card-title">User Activity Log</h5>
                        <p class="card-text">Track user actions, system access, and security events.</p>
                        <a href="user_activity.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>

            <!-- Financial Report Card -->
            <div class="col-lg-4 col-md-6">
                <div class="report-card bg-financial text-white">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h5 class="card-title">Financial Report</h5>
                        <p class="card-text">Review financial metrics, profit margins, and expense analysis.</p>
                        <a href="financial_report.php" class="btn btn-view">View Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>