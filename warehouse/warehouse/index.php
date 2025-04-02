<?php
session_start();
include 'db.php';

// Check if user is logged in and get role
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['customer_id']);
$is_admin = isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin';

// Fetch available products with prepared statement if not admin
if (!$is_admin) {
    $products_query = "SELECT i.*, GROUP_CONCAT(pi.image_url) as images 
                      FROM inventory i 
                      LEFT JOIN product_images pi ON i.product_id = pi.product_id 
                      WHERE i.status = 'Available' AND i.quantity > 0 
                      GROUP BY i.product_id";
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
        $orders_query = "SELECT o.id, o.order_date, o.total_amount, o.status, 
                        GROUP_CONCAT(i.product_name) as products,
                        GROUP_CONCAT(oi.quantity) as quantities
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id
                        JOIN inventory i ON oi.product_id = i.product_id
                        WHERE o.customer_id = ? 
                        GROUP BY o.id
                        ORDER BY o.order_date DESC LIMIT 5";
        $stmt = $conn->prepare($orders_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $recent_orders = $stmt->get_result();

        // Fetch recent deliveries
        $deliveries_query = "SELECT d.*, oi.product_id, i.product_name 
                           FROM deliveries d 
                           JOIN orders o ON d.order_id = o.id
                           JOIN order_items oi ON o.id = oi.order_id
                           JOIN inventory i ON oi.product_id = i.product_id
                           WHERE o.customer_id = ? 
                           ORDER BY d.delivery_date DESC LIMIT 5";
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
            cursor: pointer;
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

        .modal-body img {
            border-radius: 8px;
            max-height: 400px;
            object-fit: contain;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 10%;
            background: rgba(0,0,0,0.2);
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(0,0,0,0.3);
        }

        .modal-dialog {
            max-width: 800px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
                            <li class="nav-item"><a class="nav-link" href="manage_products.php">Manage Products</a></li>
                            <li class="nav-item"><a class="nav-link" href="inventory.php">Manage Inventory</a></li>
                            <li class="nav-item"><a class="nav-link" href="orders.php">Manage Orders</a></li>
                            <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                    <?php elseif (isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_orders.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="request_delivery.php">Request Delivery</a></li>
                        <li class="nav-item"><a class="nav-link" href="view_cart.php">
                            Cart <span class="badge bg-primary"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                        </a></li>
                        <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="customer_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <?php if (isset($_SESSION['user_id'])): ?>
                <h1 class="hero-title">WELCOME, <?php echo strtoupper($_SESSION['user_name']); ?></h1>
                <p class="hero-subtitle">Access Your Admin Dashboard</p>
                <a href="admin_dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
            <?php elseif (isset($_SESSION['customer_id'])): ?>
                <h1 class="hero-title">WELCOME, <?php echo strtoupper($_SESSION['customer_name']); ?></h1>
                <p class="hero-subtitle">Your Trusted Partner in Storage and Logistics</p>
                <a href="view_cart.php" class="btn btn-primary btn-lg">View Cart</a>
            <?php else: ?>
                <h1 class="hero-title">WELCOME TO OLYMPUS WAREHOUSE</h1>
                <p class="hero-subtitle">Your Trusted Partner in Storage and Logistics</p>
                <a href="login.php" class="btn btn-primary btn-lg">Get Started</a>
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
                <h3 class="section-title">Available Products</h3>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <?php while ($product = $result->fetch_assoc()): 
                        $images = $product['images'] ? explode(',', $product['images']) : [];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#productModal-<?php echo $product['product_id']; ?>">
                            <?php if (!empty($images)): ?>
                                <div id="carousel-<?php echo $product['product_id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($images as $index => $image): ?>
                                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                                     class="card-img-top" 
                                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                     style="height: 200px; object-fit: cover;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($images) > 1): ?>
                                        <button class="carousel-control-prev" type="button" 
                                                data-bs-target="#carousel-<?php echo $product['product_id']; ?>" 
                                                data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" 
                                                data-bs-target="#carousel-<?php echo $product['product_id']; ?>" 
                                                data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-box fa-3x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($product['category']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge bg-<?php echo $product['quantity'] > $product['threshold'] ? 'success' : 'warning'; ?>">
                                        <?php echo $product['quantity']; ?> in stock
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Modal -->
                        <div class="modal fade" id="productModal-<?php echo $product['product_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <?php if (!empty($images)): ?>
                                                    <div id="modalCarousel-<?php echo $product['product_id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            <?php foreach($images as $index => $image): ?>
                                                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                                                         class="d-block w-100" 
                                                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                                         style="height: 300px; object-fit: cover;">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <?php if (count($images) > 1): ?>
                                                            <button class="carousel-control-prev" type="button" 
                                                                    data-bs-target="#modalCarousel-<?php echo $product['product_id']; ?>" 
                                                                    data-bs-slide="prev">
                                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Previous</span>
                                                            </button>
                                                            <button class="carousel-control-next" type="button" 
                                                                    data-bs-target="#modalCarousel-<?php echo $product['product_id']; ?>" 
                                                                    data-bs-slide="next">
                                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Next</span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                                                        <i class="fas fa-box fa-5x text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h4 class="mb-3"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                                <p class="text-muted mb-2">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                                <p class="h3 mb-4">$<?php echo number_format($product['price'], 2); ?></p>
                                                <div class="mb-4">
                                                    <h5>Description:</h5>
                                                    <p><?php echo htmlspecialchars($product['product_description'] ?: 'No description available.'); ?></p>
                                                </div>
                                                <div class="mb-4">
                                                    <h5>Product Details:</h5>
                                                    <ul class="list-unstyled">
                                                        <li><strong>Stock:</strong> <?php echo $product['quantity']; ?> units</li>
                                                        <li><strong>Products per Box:</strong> <?php echo $product['products_per_box']; ?></li>
                                                        <li><strong>Minimum Order:</strong> <?php echo $product['minimum_order_quantity']; ?> units</li>
                                                    </ul>
                                                </div>
                                                <?php if (isset($_SESSION['customer_id'])): ?>
                                                    <div class="d-flex gap-2">
                                                        <form action="add_to_cart.php" method="POST" class="flex-grow-1">
                                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="quantity" min="1" max="<?php echo $product['quantity']; ?>" value="1" required>
                                                                <button type="submit" class="btn btn-outline-primary">
                                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                                </button>
                                                            </div>
                                                        </form>
                                                        <form action="payment.php" method="POST">
                                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                                            <input type="hidden" name="quantity" value="1">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-shopping-bag"></i> Buy Now
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="customer_login.php" class="btn btn-primary w-100">
                                                        <i class="fas fa-sign-in-alt"></i> Login to Order
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
