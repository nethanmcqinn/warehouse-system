<?php
session_start();
include 'db.php';

// Check if user is logged in and get role
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['customer_id']);
$is_admin = isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin';

// Fetch available products with prepared statement
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
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e67e22;
            --accent-color: #3498db;
            --dark-gray: #34495e;
            --light-gray: #ecf0f1;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-gray);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            width: 100%;
            padding-bottom: 40px;
        }

        .navbar {
            background-color: var(--primary-color) !important;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--secondary-color) !important;
        }

        .hero-section {
            background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                            url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
            width: 100%;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
            margin: 20px 0 40px;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .section-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--secondary-color);
            margin: 20px auto;
        }

        .products-section {
            width: 100%;
            padding: 20px 0;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 500px; /* Set minimum height for consistency */
        }

        .product-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--secondary-color);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .product-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
            text-align: center;
            flex-grow: 1;
            line-height: 1.6;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .product-quantity {
            color: var(--dark-gray);
            font-size: 1rem;
            margin-bottom: 25px;
            text-align: center;
            padding: 8px;
            background: var(--light-gray);
            border-radius: 5px;
        }

        .product-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-input label {
            font-size: 0.9rem;
            color: var(--dark-gray);
            margin-bottom: 0;
        }

        .quantity-input input {
            width: 80px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .add-to-cart, .buy-now {
            width: 100%;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart {
            background-color: var(--primary-color);
            color: white;
        }

        .buy-now {
            background-color: var(--secondary-color);
            color: white;
        }

        .add-to-cart:disabled, .buy-now:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .button-icon {
            margin-right: 5px;
        }

        .footer {
            background-color: var(--primary-color);
            color: #fff;
            padding: 60px 0 20px;
            width: 100%;
            position: relative;
        }

        footer h5 {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        footer p {
            opacity: 0.9;
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                padding: 15px;
            }

            .product-card {
                min-height: auto;
            }

            .hero-section {
                padding: 60px 0;
                margin-bottom: 30px;
            }
        }

        .warehouse-features {
            padding: 50px 0;
            background: white;
            margin-bottom: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .feature-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h5 {
            color: var(--secondary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--secondary-color);
        }

        .footer-section p {
            color: #ecf0f1;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-link {
            color: #fff;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .social-link:hover {
            color: var(--secondary-color);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--secondary-color);
        }

        .contact-info {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .contact-info li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ecf0f1;
        }

        .contact-info li i {
            color: var(--secondary-color);
            width: 20px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            margin: 0;
            color: #ecf0f1;
        }

        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }

        .footer-bottom-links a {
            color: #ecf0f1;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .footer-content {
                gap: 30px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .footer-bottom-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="page-wrapper">
        <main>
            <section class="hero-section">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Welcome to Olympus Warehouse</h1>
                        <p class="hero-subtitle">Your Premier Destination for Quality Products and Efficient Service</p>
                        <?php if (!$is_logged_in): ?>
                            <a href="warehouse_login.php" class="btn btn-primary btn-lg">Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="warehouse-features">
                <div class="container">
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h3 class="feature-title">Fast Delivery</h3>
                            <p class="feature-description">Quick and reliable shipping to your doorstep</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <h3 class="feature-title">Quality Products</h3>
                            <p class="feature-description">Carefully selected and quality-assured items</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h3 class="feature-title">24/7 Support</h3>
                            <p class="feature-description">Always here to help with your queries</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="feature-title">Secure Shopping</h3>
                            <p class="feature-description">Safe and secure transaction process</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="products-section">
                <div class="container">
                    <h2 class="section-title">Available Products</h2>
                    <div class="products-grid">
                        <?php while ($product = $result->fetch_assoc()): ?>
                            <div class="product-card">
                                <?php if (!empty($product['images'])): ?>
                                    <img src="<?php echo explode(',', $product['images'])[0]; ?>" alt="<?php echo $product['product_name']; ?>" class="product-image">
                                <?php endif; ?>
                                <div class="product-info">
                                    <h2 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                                    <p class="product-description"><?php echo htmlspecialchars($product['details'] ?? 'No description available'); ?></p>
                                    <div class="product-price">Price: $<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="product-quantity">Quantity Available: <?php echo $product['quantity']; ?></div>
                                    <?php if ($is_logged_in && !$is_admin): ?>
                                        <div class="product-buttons">
                                            <div class="quantity-input mb-2">
                                                <label for="quantity-<?php echo $product['product_id']; ?>">Quantity:</label>
                                                <input type="number" id="quantity-<?php echo $product['product_id']; ?>" 
                                                       min="1" max="<?php echo $product['quantity']; ?>" value="1" 
                                                       class="form-control form-control-sm">
                                            </div>
                                            <button class="add-to-cart" onclick="addToCart('<?php echo $product['product_id']; ?>')" 
                                                    <?php echo ($product['quantity'] <= 0) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-cart-plus button-icon"></i>Add to Cart
                                            </button>
                                            <button onclick="buyNow('<?php echo $product['product_id']; ?>')"
                                               class="buy-now" 
                                               <?php echo ($product['quantity'] <= 0) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-shopping-bag button-icon"></i>Buy Now
                                            </button>
                                        </div>
                                    <?php elseif (!$is_logged_in): ?>
                                        <div class="product-buttons">
                                            <a href="warehouse_login.php" class="add-to-cart">
                                                <i class="fas fa-sign-in-alt button-icon"></i>Login to Purchase
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h5>About Olympus Warehouse</h5>
                        <p>Your trusted partner in warehouse management solutions. We provide quality products, reliable service, and efficient delivery to meet all your needs.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                    <div class="footer-section">
                        <h5>Quick Links</h5>
                        <ul class="footer-links">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="my_orders.php">My Orders</a></li>
                            <li><a href="my_profile.php">My Profile</a></li>
                            <li><a href="help.php">Help Center</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h5>Contact Information</h5>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-marker-alt"></i> 123 Warehouse Street, Business District</li>
                            <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                            <li><i class="fas fa-envelope"></i> info@olympuswarehouse.com</li>
                            <li><i class="fas fa-clock"></i> Mon - Fri: 9:00 AM - 6:00 PM</li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> Olympus Warehouse. All rights reserved.</p>
                    <div class="footer-bottom-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Shipping Information</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            const quantity = document.getElementById('quantity-' + productId).value;
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                alert('Product added to cart successfully!');
                // Optionally refresh the page or update cart count
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }

        function buyNow(productId) {
            const quantity = document.getElementById('quantity-' + productId).value;
            // First add to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Redirect to checkout
                window.location.href = 'process_purchase.php';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing purchase');
            });
        }
    </script>
</body>
</html>
