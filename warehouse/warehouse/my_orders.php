<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get customer's orders with product details
$orders_query = "SELECT o.*, 
                GROUP_CONCAT(i.product_name) as products,
                GROUP_CONCAT(oi.quantity) as quantities,
                GROUP_CONCAT(oi.unit_price) as prices
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN inventory i ON oi.product_id = i.product_id
                WHERE o.customer_id = ?
                GROUP BY o.id
                ORDER BY o.order_date DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Olympus Warehouse</title>
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
        <h2 class="section-title mb-4">My Orders</h2>
        
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

        <?php if ($orders_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-dark text-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Products</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): 
                            $products = explode(',', $order['products']);
                            $quantities = explode(',', $order['quantities']);
                            $prices = explode(',', $order['prices']);
                        ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <ul class="list-unstyled">
                                    <?php for ($i = 0; $i < count($products); $i++): ?>
                                        <li><?php echo $products[$i] . ' (Qty: ' . $quantities[$i] . ' @ $' . number_format($prices[$i], 2) . ')'; ?></li>
                                    <?php endfor; ?>
                                </ul>
                            </td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] == 'Completed' ? 'success' : 
                                        ($order['status'] == 'Processing' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['payment_status'] == 'Paid' ? 'success' : 
                                        ($order['payment_status'] == 'Pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo $order['payment_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't placed any orders yet. <a href="index.php">Start shopping</a>!
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 