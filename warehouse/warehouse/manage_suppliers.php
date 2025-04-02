<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php';

// Handle supplier actions
if (isset($_POST['add_supplier'])) {
    $name = $_POST['name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $business_type = $_POST['business_type'];

    $query = "INSERT INTO suppliers (supplier_name, contact_name, contact_email, contact_phone, address, business_type) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $name, $contact_person, $email, $phone, $address, $business_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Supplier added successfully!";
    } else {
        $_SESSION['error'] = "Error adding supplier: " . $conn->error;
    }
}

// Handle supplier inventory updates
if (isset($_POST['update_inventory'])) {
    $supplier_id = $_POST['supplier_id'];
    $product_id = $_POST['product_id'];
    $current_stock = $_POST['current_stock'];
    $threshold = $_POST['threshold'];

    // Check if record exists
    $check_query = "SELECT * FROM supplier_inventory WHERE supplier_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("is", $supplier_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE supplier_inventory SET current_stock = ?, threshold = ? WHERE supplier_id = ? AND product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiis", $current_stock, $threshold, $supplier_id, $product_id);
    } else {
        // Insert new record
        $query = "INSERT INTO supplier_inventory (supplier_id, product_id, current_stock, threshold) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isii", $supplier_id, $product_id, $current_stock, $threshold);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Inventory updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating inventory: " . $conn->error;
    }
}

// Handle inventory supply to supplier
if (isset($_POST['supply_inventory'])) {
    $supplier_id = $_POST['supplier_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get product price
        $price_query = "SELECT price FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($price_query);
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $price_result = $stmt->get_result();
        
        if ($price_result->num_rows == 0) {
            throw new Exception("Product not found");
        }
        
        $price = $price_result->fetch_assoc()['price'];
        $total_amount = $price * $quantity;
        
        // Check if we have enough in our inventory
        $inventory_query = "SELECT quantity FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($inventory_query);
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $inventory_result = $stmt->get_result();
        $available_qty = $inventory_result->fetch_assoc()['quantity'];
        
        if ($available_qty < $quantity) {
            throw new Exception("Not enough stock in inventory. Available: $available_qty");
        }
        
        // Reduce our inventory
        $update_inventory = "UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?";
        $stmt = $conn->prepare($update_inventory);
        $stmt->bind_param("is", $quantity, $product_id);
        $stmt->execute();
        
        // Increase supplier inventory
        $update_supplier_inv = "UPDATE supplier_inventory SET current_stock = current_stock + ? 
                               WHERE supplier_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_supplier_inv);
        $stmt->bind_param("iis", $quantity, $supplier_id, $product_id);
        
        if ($stmt->execute() && $stmt->affected_rows == 0) {
            // If no rows affected, insert new record
            $insert_supplier_inv = "INSERT INTO supplier_inventory (supplier_id, product_id, current_stock, threshold) 
                                   VALUES (?, ?, ?, 10)";
            $stmt = $conn->prepare($insert_supplier_inv);
            $stmt->bind_param("isi", $supplier_id, $product_id, $quantity);
            $stmt->execute();
        }
        
        // Create supply record
        $supply_query = "INSERT INTO supplier_orders (supplier_id, total_amount, status) VALUES (?, ?, 'Delivered')";
        $stmt = $conn->prepare($supply_query);
        $stmt->bind_param("id", $supplier_id, $total_amount);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add order items
        $items_query = "INSERT INTO supplier_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("isid", $order_id, $product_id, $quantity, $price);
        $stmt->execute();
        
        // Add delivery record
        $delivery_query = "INSERT INTO supplier_deliveries (order_id, status) VALUES (?, 'Delivered')";
        $stmt = $conn->prepare($delivery_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Successfully supplied $quantity units to supplier!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error supplying inventory: " . $e->getMessage();
    }
}

// Fetch suppliers
$suppliers_query = "SELECT * FROM suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_query);

// Fetch products
$products_query = "SELECT * FROM inventory ORDER BY product_name";
$products_result = $conn->query($products_query);

// Fetch supplier inventory with low stock
$low_stock_query = "SELECT si.*, s.supplier_name, i.product_name, i.price
                   FROM supplier_inventory si
                   JOIN suppliers s ON si.supplier_id = s.id
                   JOIN inventory i ON si.product_id = i.product_id
                   WHERE si.current_stock <= si.threshold
                   ORDER BY si.current_stock ASC";
$low_stock_result = $conn->query($low_stock_query);

// Fetch recent supplier orders
$orders_query = "SELECT so.*, s.supplier_name, 
                GROUP_CONCAT(i.product_name SEPARATOR '<br>') as products,
                GROUP_CONCAT(soi.quantity SEPARATOR '<br>') as quantities
                FROM supplier_orders so
                JOIN suppliers s ON so.supplier_id = s.id
                JOIN supplier_order_items soi ON so.id = soi.order_id
                JOIN inventory i ON soi.product_id = i.product_id
                GROUP BY so.id
                ORDER BY so.order_date DESC LIMIT 10";
$orders_result = $conn->query($orders_query);

// Handle URL parameters for supply action
if (isset($_GET['action']) && $_GET['action'] == 'supply') {
    $supplier_id = $_GET['supplier_id'];
    $product_id = $_GET['product_id'];
    
    // Get supplier and product details
    $details_query = "SELECT s.supplier_name, i.product_name 
                     FROM suppliers s 
                     JOIN inventory i ON i.product_id = ?
                     WHERE s.id = ?";
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("si", $product_id, $supplier_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    
    if ($details) {
        $_SESSION['supply_details'] = [
            'supplier_id' => $supplier_id,
            'product_id' => $product_id,
            'supplier_name' => $details['supplier_name'],
            'product_name' => $details['product_name']
        ];
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('supply_supplier_id').value = '$supplier_id';
                document.getElementById('supply_product_id').value = '$product_id';
                new bootstrap.Modal(document.getElementById('supplyModal')).show();
            });
        </script>";
    }
}

// Fetch all supplier inventory for inventory management
$all_inventory_query = "SELECT si.*, s.supplier_name, i.product_name, i.price
                       FROM supplier_inventory si
                       JOIN suppliers s ON si.supplier_id = s.id
                       JOIN inventory i ON si.product_id = i.product_id
                       ORDER BY s.supplier_name, i.product_name";
$all_inventory_result = $conn->query($all_inventory_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - Olympus Warehouse</title>
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

        .card {
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #333;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .nav-tabs .nav-link {
            color: #333;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #007bff;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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
        <h1>Manage Business Partners</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="supplierTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="low-stock-tab" data-bs-toggle="tab" data-bs-target="#low-stock" type="button" role="tab">
                    Low Stock Alerts
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-supplier-tab" data-bs-toggle="tab" data-bs-target="#add-supplier" type="button" role="tab">
                    Add New Partner
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="all-inventory-tab" data-bs-toggle="tab" data-bs-target="#all-inventory" type="button" role="tab">
                    Partner Inventory
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recent-orders-tab" data-bs-toggle="tab" data-bs-target="#recent-orders" type="button" role="tab">
                    Recent Supply History
                </button>
            </li>
        </ul>

        <div class="tab-content" id="supplierTabContent">
            <!-- Low Stock Alerts Tab -->
            <div class="tab-pane fade show active" id="low-stock" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3>Low Stock Alerts</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Business Partner</th>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Threshold</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $low_stock_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $row['current_stock']; ?></span>
                                            </td>
                                            <td><?php echo $row['threshold']; ?></td>
                                            <td>
                                                <a href="?action=supply&supplier_id=<?php echo $row['supplier_id']; ?>&product_id=<?php echo $row['product_id']; ?>" 
                                                   class="btn btn-primary btn-sm">Supply Inventory</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-success mb-0">No low stock alerts at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add New Supplier Tab -->
            <div class="tab-pane fade" id="add-supplier" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3>Add New Business Partner</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label>Business Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Business Type</label>
                                <select name="business_type" class="form-control" required>
                                    <option value="Mall">Mall</option>
                                    <option value="Retail Store">Retail Store</option>
                                    <option value="Distributor">Distributor</option>
                                    <option value="Wholesaler">Wholesaler</option>
                                    <option value="Manufacturer">Manufacturer</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control" required></textarea>
                            </div>
                            <button type="submit" name="add_supplier" class="btn btn-primary">Add Business Partner</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- All Inventory Tab -->
            <div class="tab-pane fade" id="all-inventory" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3>Partner Inventory Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h4>Supply Inventory to Partner</h4>
                            <form method="POST" class="row g-3">
                                <div class="col-md-4">
                                    <label>Select Partner</label>
                                    <select name="supplier_id" class="form-control" required>
                                        <option value="">-- Select Partner --</option>
                                        <?php
                                        $suppliers_result->data_seek(0);
                                        while ($row = $suppliers_result->fetch_assoc()): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['supplier_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Select Product</label>
                                    <select name="product_id" class="form-control" required>
                                        <option value="">-- Select Product --</option>
                                        <?php
                                        $products_result->data_seek(0);
                                        while ($row = $products_result->fetch_assoc()): ?>
                                            <option value="<?php echo $row['product_id']; ?>"><?php echo htmlspecialchars($row['product_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity" class="form-control" required min="1">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="supply_inventory" class="btn btn-primary form-control">Supply</button>
                                </div>
                            </form>
                        </div>

                        <h4 class="mt-4">Current Partner Inventory</h4>
                        <?php if ($all_inventory_result && $all_inventory_result->num_rows > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Business Partner</th>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Threshold</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $all_inventory_result->fetch_assoc()): 
                                        $stock_status = $row['current_stock'] <= $row['threshold'] ? 'danger' : 
                                                        ($row['current_stock'] <= $row['threshold']*1.5 ? 'warning' : 'success');
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                            <td><?php echo $row['current_stock']; ?></td>
                                            <td><?php echo $row['threshold']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $stock_status; ?>">
                                                    <?php 
                                                    echo $stock_status == 'danger' ? 'Low Stock' : 
                                                        ($stock_status == 'warning' ? 'Running Low' : 'In Stock'); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?action=supply&supplier_id=<?php echo $row['supplier_id']; ?>&product_id=<?php echo $row['product_id']; ?>" 
                                                   class="btn btn-primary btn-sm">Supply</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No inventory records found. Supply inventory to partners to see records here.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Tab -->
            <div class="tab-pane fade" id="recent-orders" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Supply History</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Partner</th>
                                        <th>Date</th>
                                        <th>Products</th>
                                        <th>Quantities</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                            <td><?php echo $row['order_date']; ?></td>
                                            <td><?php echo $row['products']; ?></td>
                                            <td><?php echo $row['quantities']; ?></td>
                                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] == 'Delivered' ? 'success' : 
                                                        ($row['status'] == 'Processing' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No supply history found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supply Inventory Modal -->
    <div class="modal fade" id="supplyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supply Inventory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($_SESSION['supply_details'])): ?>
                        <p><strong>Partner:</strong> <?php echo htmlspecialchars($_SESSION['supply_details']['supplier_name']); ?></p>
                        <p><strong>Product:</strong> <?php echo htmlspecialchars($_SESSION['supply_details']['product_name']); ?></p>
                    <?php endif; ?>
                    <form method="POST" id="supplyForm">
                        <input type="hidden" name="supplier_id" id="supply_supplier_id">
                        <input type="hidden" name="product_id" id="supply_product_id">
                        <div class="mb-3">
                            <label>Quantity to Supply</label>
                            <input type="number" name="quantity" class="form-control" required min="1">
                        </div>
                        <button type="submit" name="supply_inventory" class="btn btn-primary">Supply Inventory</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get the tab from URL if present
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const tabElement = document.querySelector(`#${tab}-tab`);
                if (tabElement) {
                    const tabInstance = new bootstrap.Tab(tabElement);
                    tabInstance.show();
                }
            }
        });
    </script>
</body>
</html> 