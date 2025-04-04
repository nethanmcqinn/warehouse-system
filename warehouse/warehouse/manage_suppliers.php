<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php';
include 'sidebar.php';

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
$suppliers_query = "SELECT id, supplier_name, contact_name, contact_email, contact_phone, address, business_type FROM suppliers ORDER BY supplier_name";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content-wrapper {
            width: 95%;
            max-width: 1400px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 2rem;
            font-weight: 600;
        }

        .add-supplier-btn {
            margin-bottom: 20px;
            text-align: right;
        }

        .add-supplier-btn .btn {
            padding: 8px 20px;
            font-weight: 500;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #333;
            color: white;
            padding: 12px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .table td {
            padding: 12px;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .action-buttons {
            white-space: nowrap;
            text-align: center;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            margin: 0 2px;
            font-size: 0.85rem;
        }

        .table-responsive {
            overflow-x: auto;
            margin: 0 -25px;
            padding: 0 25px;
        }

        @media (max-width: 1200px) {
            .content-wrapper {
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="content-wrapper">
            <h1 class="page-title">Manage Suppliers/Partners</h1>
            
            <div class="add-supplier-btn">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="bi bi-plus-circle"></i> Add New Supplier
                </button>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Business Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM suppliers ORDER BY id ASC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['supplier_name']}</td>";
                            echo "<td>{$row['contact_name']}</td>";
                            echo "<td>{$row['contact_email']}</td>";
                            echo "<td>{$row['contact_phone']}</td>";
                            echo "<td>{$row['address']}</td>";
                            echo "<td>{$row['business_type']}</td>";
                            echo "<td><span class='status-badge status-active'>Active</span></td>";
                            echo "<td class='action-buttons'>";
                            echo "<button class='btn btn-info btn-sm' title='View'><i class='bi bi-eye'></i></button> ";
                            echo "<button class='btn btn-warning btn-sm' title='Edit'><i class='bi bi-pencil'></i></button> ";
                            echo "<button class='btn btn-success btn-sm' title='Inventory'><i class='bi bi-box'></i></button> ";
                            echo "<button class='btn btn-danger btn-sm' title='Delete'><i class='bi bi-trash'></i></button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="business_type" class="form-label">Business Type</label>
                            <select class="form-select" id="business_type" name="business_type" required>
                                <option value="">Select Business Type</option>
                                <option value="Manufacturer">Manufacturer</option>
                                <option value="Wholesaler">Wholesaler</option>
                                <option value="Distributor">Distributor</option>
                                <option value="Retailer">Retailer</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier?')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html> 