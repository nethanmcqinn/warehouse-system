<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Olympus Warehouse</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_products.php">Manage Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif (isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="my_orders.php">My Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="request_delivery.php">Request Delivery</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_cart.php">
                        Cart <span class="badge bg-primary"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                    </a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer_register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 