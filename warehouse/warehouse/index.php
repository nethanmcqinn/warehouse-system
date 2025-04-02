<?php
session_start();
include 'db.php';

// Check if user is logged in and get role
$is_logged_in = isset($_SESSION['customer_id']) || isset($_SESSION['admin_id']);
$is_admin = isset($_SESSION['admin_id']);

// Fetch available products with prepared statement if not admin
if (!$is_admin) {
    $products_query = "SELECT * FROM inventory WHERE status = 'Available' AND quantity > 0";
    $result = $conn->query($products_query);
    
    if (!$result) {
        die("Error fetching products: " . $conn->error);
    }

    // If customer is logged in, fetch their information and orders
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
        
        // Fetch customer information
        $customer_query = "SELECT * FROM customers WHERE customer_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $customer_result = $stmt->get_result();
        $customer_info = $customer_result->fetch_assoc();

        // Fetch recent orders
        $orders_query = "SELECT o.*, i.product_name FROM orders o 
        JOIN inventory i ON o.product_id = i.product_id 
        WHERE o.customer_id = ? ORDER BY o.order_date DESC LIMIT 5";
        $stmt = $conn->prepare($orders_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $recent_orders = $stmt->get_result();

        // Fetch recent deliveries
        $deliveries_query = "SELECT d.*, o.product_id, i.product_name FROM deliveries d 
        JOIN orders o ON d.order_id = o.id 
        JOIN inventory i ON o.product_id = i.product_id 
        WHERE o.customer_id = ? ORDER BY d.delivery_date DESC LIMIT 5";
        $stmt = $conn->prepare($deliveries_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $recent_deliveries = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Warehouse-themed styles */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e67e22;
            --accent-color: #3498db;
            --light-gray: #ecf0f1;
            --dark-gray: #34495e;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-gray);
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .navbar-brand {
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand:before {
            content: '\f494';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 10px;
            font-size: 1.5rem;
            color: var(--secondary-color);
        }
        
        .hero-section {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 40px;
            position: relative;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }
        
        .service-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            background-color: white;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .service-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .product-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
            overflow: hidden;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--secondary-color);
        }
        
        .product-card .card-title {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #d35400;
            border-color: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
            color: var(--primary-color);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
        }
        
        .warehouse-stats {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0;
            margin: 40px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        footer {
            background-color: var(--dark-gray);
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer-title {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--secondary-color);
        }
        
        .social-icons a {
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        
        .social-icons a:hover {
            color: var(--secondary-color);
        }
        
        .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Olympus Warehouse</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($is_admin): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                        <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                    <?php elseif (isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="customer_orders.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="request_delivery.php">Request Delivery</a></li>
                        <li class="nav-item"><a class="nav-link" href="customer_profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="customer_logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="warehouse_login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="customer_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Welcome to Olympus Warehouse</h1>
            <p class="hero-subtitle">Your Trusted Partner in Storage and Logistics</p>
            <?php if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])): ?>
            <a href="customer_register.php" class="btn btn-primary btn-lg">Get Started</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warehouse Stats -->
    <div class="warehouse-stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">Square Feet</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Products</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">99%</div>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($is_admin): ?>
            <!-- Admin Redirect -->
            <script>window.location.href = 'admin_dashboard.php';</script>
        <?php elseif (isset($_SESSION['customer_id'])): ?>
            <!-- Customer Dashboard -->
            <h2>Welcome, <?php echo htmlspecialchars($customer_info['customer_name']); ?></h2>
            
            <!-- Dashboard Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Recent Orders</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order Date</th>
                                            <th>Product</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td><span class="badge bg-<?php echo $order['status'] == 'Completed' ? 'success' : 'warning'; ?>"><?php echo $order['status']; ?></span></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Recent Deliveries</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Delivery Date</th>
                                            <th>Product</th>
                                            <th>Status</th>
                                            <th>Tracking</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($delivery = $recent_deliveries->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($delivery['delivery_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($delivery['product_name']); ?></td>
                                            <td><span class="badge bg-<?php echo $delivery['status'] == 'Delivered' ? 'success' : 'info'; ?>"><?php echo $delivery['status']; ?></span></td>
                                            <td><?php echo $delivery['tracking_number'] ? $delivery['tracking_number'] : 'N/A'; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$is_admin): ?>
            <!-- Services Section -->
            <section class="mb-5">
                <h3 class="mb-4">Our Services</h3>
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-truck service-icon"></i>
                                <h5 class="card-title">Standard Delivery</h5>
                                <p class="card-text">Regular delivery service within 3-5 business days</p>
                                <p class="card-text"><strong>Price:</strong> $10.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shipping-fast service-icon"></i>
                                <h5 class="card-title">Express Delivery</h5>
                                <p class="card-text">Fast delivery service within 1-2 business days</p>
                                <p class="card-text"><strong>Price:</strong> $25.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-warehouse service-icon"></i>
                                <h5 class="card-title">Storage Service</h5>
                                <p class="card-text">Secure storage for your products</p>
                                <p class="card-text"><strong>Price:</strong> $50.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-box service-icon"></i>
                                <h5 class="card-title">Packaging Service</h5>
                                <p class="card-text">Professional packaging for fragile items</p>
                                <p class="card-text"><strong>Price:</strong> $15.00</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (isset($_SESSION['customer_id'])): ?>
                <div class="text-center">
                    <a href="request_delivery.php" class="btn btn-primary">Request Delivery Service</a>
                </div>
                <?php else: ?>
                <div class="text-center">
                    <a href="customer_login.php" class="btn btn-primary">Login to Request Service</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Available Products Section -->
            <section class="mb-5">
                <h3 class="mb-4">Available Products</h3>
                <div class="row">
                    <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($product['product_description']); ?></p>
                                <p class="card-text">
                                    <strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?><br>
                                    <strong>Available:</strong> <?php echo $product['quantity']; ?> units
                                </p>
                                <?php if (isset($_SESSION['customer_id'])): ?>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="<?php echo $product['quantity']; ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                                <?php else: ?>
                                <a href="customer_login.php" class="btn btn-primary">Login to Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <!-- Chatbot Toggle Button -->
    <button id="chatbot-toggle" class="chatbot-toggle">💬</button>

    <!-- Chatbot Interface -->
    <div id="chatbot" class="chatbot-hidden">
        <div id="chatbot-messages"></div>
        <input type="text" id="chatbot-input" placeholder="Ask about stock...">
        <button id="chatbot-send">Send</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts.js"></script>
</body>
</html>
