<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php'; // Include the database connection

// Handle order actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $order_id = $_GET['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch the order details
        $order_query = "SELECT * FROM orders WHERE id=?";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        $order = $order_result->fetch_assoc();

        if ($action == 'accept') {
            // Update order status to 'Processing'
            $update_order_query = "UPDATE orders SET status = 'Processing' WHERE id = ?";
            $stmt = $conn->prepare($update_order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // Get order items and customer info
            $order_items_query = "SELECT oi.*, i.product_name, c.fname, c.lname, c.address_line1, c.address_line2, c.city, c.state, c.postal_code 
                                FROM order_items oi 
                                JOIN inventory i ON oi.product_id = i.product_id
                                JOIN orders o ON oi.order_id = o.id
                                JOIN customers c ON o.customer_id = c.customer_id
                                WHERE oi.order_id = ?";
            $stmt = $conn->prepare($order_items_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_items = $stmt->get_result();

            // Process each order item
            while ($item = $order_items->fetch_assoc()) {
                // Update inventory
                $update_inventory_query = "UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_inventory_query);
                $stmt->bind_param("is", $item['quantity'], $item['product_id']);
                $stmt->execute();

                // Create delivery record
                $delivery_date = date('Y-m-d');
                $status = 'Pending';
                $shipping_address = $item['address_line1'] . 
                                  ($item['address_line2'] ? ', ' . $item['address_line2'] : '') . 
                                  ', ' . $item['city'] . ', ' . $item['state'] . ' ' . $item['postal_code'];
                $shipping_notes = "Order #" . $order_id . " - " . $item['product_name'] . " (Qty: " . $item['quantity'] . ")";
                
                // Check if delivery already exists for this order
                $check_delivery_query = "SELECT id FROM deliveries WHERE order_id = ?";
                $stmt = $conn->prepare($check_delivery_query);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $delivery_exists = $stmt->get_result()->num_rows > 0;

                if (!$delivery_exists) {
                    $add_delivery_query = "INSERT INTO deliveries (order_id, delivery_date, status, shipping_address, shipping_notes) 
                                         VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($add_delivery_query);
                    $stmt->bind_param("issss", $order_id, $delivery_date, $status, $shipping_address, $shipping_notes);
                    $stmt->execute();
                }
            }
        } elseif ($action == 'cancel') {
            // Update order status to 'Cancelled'
            $update_order_query = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
            $stmt = $conn->prepare($update_order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        } elseif ($action == 'pending') {
            // Update order status to 'Pending'
            $update_order_query = "UPDATE orders SET status = 'Pending' WHERE id = ?";
            $stmt = $conn->prepare($update_order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        } elseif ($action == 'delete') {
            // Delete associated deliveries first
            $delete_deliveries_query = "DELETE FROM deliveries WHERE order_id=?";
            $stmt = $conn->prepare($delete_deliveries_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // Delete the order
            $delete_order_query = "DELETE FROM orders WHERE id=?";
            $stmt = $conn->prepare($delete_order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        } elseif ($action == 'deliver') {
            // Update delivery status to 'Delivered' and order status to 'Completed'
            $update_delivery_query = "UPDATE deliveries SET status='Delivered' WHERE order_id=?";
            $stmt = $conn->prepare($update_delivery_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $update_order_query = "UPDATE orders SET status='Completed' WHERE id=?";
            $stmt = $conn->prepare($update_order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        } elseif ($action == 'delete_delivery') {
            // Delete the delivery
            $delete_delivery_query = "DELETE FROM deliveries WHERE id=?";
            $stmt = $conn->prepare($delete_delivery_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Order status updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
    }

    header("Location: admin_dashboard.php");
    exit();
}

// Handle order status update
if (isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update order status
        $update_order = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_order);
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        
        // If status is changed to Processing, create delivery record if it doesn't exist
        if ($new_status == 'Processing') {
            $check_delivery = "SELECT id FROM deliveries WHERE order_id = ?";
            $stmt = $conn->prepare($check_delivery);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $delivery_exists = $stmt->get_result()->num_rows > 0;
            
            if (!$delivery_exists) {
                // Get order details for delivery
                $order_details = "SELECT o.customer_id, o.total_amount, c.address_line1, c.address_line2, c.city, c.state, c.postal_code 
                                FROM orders o 
                                JOIN customers c ON o.customer_id = c.customer_id 
                                WHERE o.id = ?";
                $stmt = $conn->prepare($order_details);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $order = $stmt->get_result()->fetch_assoc();
                
                // Create delivery record
                $create_delivery = "INSERT INTO deliveries (order_id, customer_id, delivery_date, status, shipping_address, shipping_notes) 
                                  VALUES (?, ?, NOW(), 'Pending', ?, 'Order is being processed')";
                $stmt = $conn->prepare($create_delivery);
                
                // Format shipping address
                $shipping_address = $order['address_line1'];
                if (!empty($order['address_line2'])) {
                    $shipping_address .= ", " . $order['address_line2'];
                }
                $shipping_address .= ", " . $order['city'] . ", " . $order['state'] . " " . $order['postal_code'];
                
                $stmt->bind_param("iis", $order_id, $order['customer_id'], $shipping_address);
                $stmt->execute();
            }
        }
        
        // If status is changed to Completed, update delivery status
        if ($new_status == 'Completed') {
            $update_delivery = "UPDATE deliveries SET status = 'Delivered', delivery_date = NOW() WHERE order_id = ?";
            $stmt = $conn->prepare($update_delivery);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Order status updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get orders with customer and product information
$order_query = "SELECT o.id, o.order_date, o.total_amount, o.status, 
                CONCAT(c.fname, ' ', c.lname) as customer_name,
                GROUP_CONCAT(i.product_name) as products,
                GROUP_CONCAT(oi.quantity) as quantities
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN inventory i ON oi.product_id = i.product_id
                GROUP BY o.id
                ORDER BY o.order_date DESC";
$order_result = $conn->query($order_query);

// Fetch deliveries from the deliveries table
$delivery_query = "SELECT * FROM deliveries";
$delivery_result = $conn->query($delivery_query);

// Fetch supplier deliveries
$query = "SELECT * FROM supplier_deliveries";
$result = $conn->query($query);

// Fetch inventory data to check for low stock
$inventory_query = "SELECT 
                        i.product_id,
                        i.product_name,
                        i.quantity as current_stock,
                        i.threshold,
                        i.price,
                        'Internal' as source,
                        NULL as supplier_name,
                        NULL as supplier_id
                    FROM inventory i 
                    WHERE i.quantity <= i.threshold
                    UNION ALL
                    SELECT 
                        i.product_id,
                        i.product_name,
                        si.current_stock,
                        si.threshold,
                        i.price,
                        'Supplier' as source,
                        s.supplier_name,
                        s.id as supplier_id
                    FROM supplier_inventory si
                    JOIN inventory i ON si.product_id = i.product_id
                    JOIN suppliers s ON si.supplier_id = s.id
                    WHERE si.current_stock <= si.threshold
                    ORDER BY current_stock ASC";
$low_stock_result = $conn->query($inventory_query);

// Fetch suppliers from the suppliers table
$supplier_query = "SELECT * FROM suppliers";
$supplier_result = $conn->query($supplier_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .btn-warning {
            background-color: #ffc107;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-weight: bold;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-weight: bold;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-sm {
            font-size: 12px;
        }

        .bg-dark {
            background-color: #333 !important;
        }

        .text-light {
            color: #fff !important;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="manage_products.php">Manage Products</a>
    <a href="inventory.php">Manage Inventory</a>
    <a href="orders.php">Manage Orders</a>
    <a href="manage_suppliers.php">Manage Suppliers</a>
    <a href="reports.php">Reports</a>
    <a href="warehouse_logout.php" class="logout">Logout</a>
</div>
<div class="main-content">
    <h1>Welcome, Admin</h1>
    
    <!-- Low Stock Alerts -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">Low Stock Alerts</h3>
        </div>
        <div class="card-body">
            <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Threshold</th>
                                <th>Supplier</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $low_stock_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['source']; ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $row['current_stock']; ?></span>
                                    </td>
                                    <td><?php echo $row['threshold']; ?></td>
                                    <td><?php echo $row['supplier_name'] ? htmlspecialchars($row['supplier_name']) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($row['source'] == 'Internal'): ?>
                                            <a href="manage_products.php?action=edit&id=<?php echo $row['product_id']; ?>" 
                                               class="btn btn-primary btn-sm">Update Stock</a>
                                        <?php else: ?>
                                            <a href="manage_suppliers.php?action=create_order&supplier_id=<?php echo $row['supplier_id']; ?>&product_id=<?php echo $row['product_id']; ?>" 
                                               class="btn btn-primary btn-sm">Create Order</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-success mb-0">No low stock alerts at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orders Table -->
    <h2>Orders</h2>
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Products</th>
                <th>Quantities</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $order_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo htmlspecialchars($row['products']); ?></td>
                    <td><?php echo $row['quantities']; ?></td>
                    <td>
                        <a href="admin_dashboard.php?action=accept&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                        <a href="admin_dashboard.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Cancel</a>
                        <a href="admin_dashboard.php?action=pending&id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Pending</a>
                        <a href="admin_dashboard.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Deliveries Table -->
    <h2>Deliveries</h2>
    <table class="table table-bordered">
        <thead class="bg-dark text-light">
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Shipping Address</th>
                <th>Shipping Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Get deliveries with order information
            $delivery_query = "SELECT d.*, o.id as order_id, 
                             CONCAT(c.fname, ' ', c.lname) as customer_name,
                             GROUP_CONCAT(i.product_name) as products,
                             GROUP_CONCAT(oi.quantity) as quantities
                             FROM deliveries d
                             JOIN orders o ON d.order_id = o.id
                             JOIN customers c ON o.customer_id = c.customer_id
                             JOIN order_items oi ON o.id = oi.order_id
                             JOIN inventory i ON oi.product_id = i.product_id
                             GROUP BY d.id
                             ORDER BY d.delivery_date DESC";
            $delivery_result = $conn->query($delivery_query);
            
            while ($row = $delivery_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['order_id']; ?></td>
                    <td><?php echo $row['delivery_date']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo htmlspecialchars($row['shipping_address']); ?></td>
                    <td><?php echo htmlspecialchars($row['shipping_notes']); ?></td>
                    <td>
                        <a href="admin_dashboard.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Mark as Delivered</a>
                        <a href="admin_dashboard.php?action=delete_delivery&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
