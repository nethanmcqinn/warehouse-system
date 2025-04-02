<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get customer details
$customer_query = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Get order statistics
$stats_query = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.quantity) as total_items,
                SUM(oi.quantity * oi.unit_price) as total_spent,
                SUM(oi.quantity * oi.unit_price) as account_total
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.customer_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent orders
$recent_orders_query = "SELECT o.*, 
                       GROUP_CONCAT(i.product_name) as products,
                       GROUP_CONCAT(oi.quantity) as quantities
                       FROM orders o
                       JOIN order_items oi ON o.id = oi.order_id
                       JOIN inventory i ON oi.product_id = i.product_id
                       WHERE o.customer_id = ?
                       GROUP BY o.id
                       ORDER BY o.order_date DESC
                       LIMIT 5";
$stmt = $conn->prepare($recent_orders_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Olympus Warehouse</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_orders.php">My Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_cart.php">
                        Cart <span class="badge bg-primary"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                    </a></li>
                    <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2 class="section-title mb-4">My Profile</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['fname'] . ' ' . $customer['lname']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                        <p><strong>Address:</strong><br>
                            <?php 
                            echo htmlspecialchars($customer['address_line1']) . '<br>';
                            if (!empty($customer['address_line2'])) {
                                echo htmlspecialchars($customer['address_line2']) . '<br>';
                            }
                            echo htmlspecialchars($customer['city'] . ', ' . $customer['state'] . ' ' . $customer['postal_code']);
                            ?>
                        </p>
                        <a href="update_profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Order Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3><?php echo $stats['total_orders']; ?></h3>
                                <p class="text-muted">Total Orders</p>
                            </div>
                            <div class="col-md-3">
                                <h3><?php echo $stats['total_items']; ?></h3>
                                <p class="text-muted">Total Items</p>
                            </div>
                            <div class="col-md-3">
                                <h3>$<?php echo number_format($stats['total_spent'], 2); ?></h3>
                                <p class="text-muted">Total Spent</p>
                            </div>
                            <div class="col-md-3">
                                <h3>$<?php echo number_format($stats['account_total'], 2); ?></h3>
                                <p class="text-muted">Account Total</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_orders->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Products</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recent_orders->fetch_assoc()): 
                                            $products = explode(',', $order['products']);
                                            $quantities = explode(',', $order['quantities']);
                                        ?>
                                        <tr>
                                            <td><?php echo $order['id']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <ul class="list-unstyled">
                                                    <?php for ($i = 0; $i < count($products); $i++): ?>
                                                        <li><?php echo $products[$i] . ' (Qty: ' . $quantities[$i] . ')'; ?></li>
                                                    <?php endfor; ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'Completed' ? 'success' : 
                                                        ($order['status'] == 'Processing' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent orders found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 