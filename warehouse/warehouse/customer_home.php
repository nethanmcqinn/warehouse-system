<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Fetch customer information
$customer_id = $_SESSION['customer_id'];
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

// Fetch available products with prepared statement
$products_query = "SELECT * FROM inventory WHERE status = 'Available' AND quantity > 0";
$result = $conn->query($products_query);

if (!$result) {
    die("Error fetching products: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Home - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <li class="nav-item">
                        <a class="nav-link" href="customer_orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_delivery.php">Request Delivery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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

        <!-- Services Section -->
        <section class="mb-5">
            <h3 class="mb-4">Our Services</h3>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Standard Delivery</h5>
                            <p class="card-text">Regular delivery service within 3-5 business days</p>
                            <p class="card-text"><strong>Price:</strong> $10.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Express Delivery</h5>
                            <p class="card-text">Fast delivery service within 1-2 business days</p>
                            <p class="card-text"><strong>Price:</strong> $25.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Storage Service</h5>
                            <p class="card-text">Secure storage for your products</p>
                            <p class="card-text"><strong>Price:</strong> $50.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Packaging Service</h5>
                            <p class="card-text">Professional packaging for fragile items</p>
                            <p class="card-text"><strong>Price:</strong> $15.00</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <a href="request_delivery.php" class="btn btn-primary">Request Delivery Service</a>
            </div>
        </section>

        <h3 class="mb-4">Available Products</h3>

        <div class="row">
            <?php while ($product = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['product_name']; ?></h5>
                        <p class="card-text"><?php echo $product['product_description']; ?></p>
                        <p class="card-text">
                            <strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?><br>
                            <strong>Available:</strong> <?php echo $product['quantity']; ?> units
                        </p>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="<?php echo $product['quantity']; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>