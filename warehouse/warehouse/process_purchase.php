<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $conn->begin_transaction();

    $customer_id = $_SESSION['customer_id'];
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($payment_method)) {
        throw new Exception("Please select a payment method.");
    }

    // Generate a unique receipt number
    $receipt_number = 'RCP' . date('YmdHis') . rand(100, 999);
    
    // Handle direct purchase
    if (isset($_SESSION['direct_purchase'])) {
        $direct_purchase = $_SESSION['direct_purchase'];
        $product_id = $direct_purchase['product_id'];
        $quantity = $direct_purchase['quantity'];
        $price = $direct_purchase['price'];
        
        // Check stock availability
        $stock_query = "SELECT quantity, product_name FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($stock_query);
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product['quantity'] < $quantity) {
            throw new Exception("Not enough stock for " . $product['product_name']);
        }

        $total_amount = $price * $quantity;

        // Create the order
        $order_query = "INSERT INTO orders (customer_id, order_date, total_amount, status) VALUES (?, CURDATE(), ?, 'Pending')";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("id", $customer_id, $total_amount);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Add order item
        $order_items_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($order_items_query);
        $stmt->bind_param("isid", $order_id, $product_id, $quantity, $price);
        $stmt->execute();

        // Update stock
        $new_quantity = $product['quantity'] - $quantity;
        $update_stock = "UPDATE inventory SET quantity = ? WHERE product_id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("is", $new_quantity, $product_id);
        $stmt->execute();

        // Record the transaction
        $transaction_query = "INSERT INTO transactions (order_id, amount, payment_method, payment_status, transaction_reference, payment_date) 
                            VALUES (?, ?, ?, 'Completed', ?, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($transaction_query);
        $stmt->bind_param("idss", $order_id, $total_amount, $payment_method, $receipt_number);
        $stmt->execute();

        // Clear direct purchase from session
        unset($_SESSION['direct_purchase']);
        
        // Store receipt data in session for display
        $_SESSION['last_receipt'] = [
            'receipt_number' => $receipt_number,
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'date' => date('Y-m-d H:i:s')
        ];

        $_SESSION['success'] = "Order placed successfully! Receipt number: " . $receipt_number;
    }
    // For cart purchase
    else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $total_amount = 0;
        
        // Create the order
        $order_query = "INSERT INTO orders (customer_id, order_date, total_amount, status) VALUES (?, CURDATE(), 0, 'Pending')";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Process each item in cart
        foreach ($_SESSION['cart'] as $product_id => $item) {
            // Check stock availability
            $stock_query = "SELECT quantity, price, product_name FROM inventory WHERE product_id = ?";
            $stmt = $conn->prepare($stock_query);
            $stmt->bind_param("s", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if ($product['quantity'] < $item['quantity']) {
                throw new Exception("Not enough stock for " . $product['product_name']);
            }

            // Add order items
            $item_total = $product['price'] * $item['quantity'];
            $total_amount += $item_total;

            $order_items_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($order_items_query);
            $stmt->bind_param("isid", $order_id, $product_id, $item['quantity'], $product['price']);
            $stmt->execute();

            // Update stock
            $new_quantity = $product['quantity'] - $item['quantity'];
            $update_stock = "UPDATE inventory SET quantity = ? WHERE product_id = ?";
            $stmt = $conn->prepare($update_stock);
            $stmt->bind_param("is", $new_quantity, $product_id);
            $stmt->execute();
        }

        // Update order total
        $update_total = "UPDATE orders SET total_amount = ? WHERE id = ?";
        $stmt = $conn->prepare($update_total);
        $stmt->bind_param("di", $total_amount, $order_id);
        $stmt->execute();

        // Record the transaction
        $transaction_query = "INSERT INTO transactions (order_id, amount, payment_method, payment_status, transaction_reference, payment_date) 
                            VALUES (?, ?, ?, 'Completed', ?, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($transaction_query);
        $stmt->bind_param("idss", $order_id, $total_amount, $payment_method, $receipt_number);
        $stmt->execute();

        // Clear the cart
        unset($_SESSION['cart']);
        
        // Store receipt data in session for display
        $_SESSION['last_receipt'] = [
            'receipt_number' => $receipt_number,
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'date' => date('Y-m-d H:i:s')
        ];

        $_SESSION['success'] = "Order placed successfully! Receipt number: " . $receipt_number;
    } else {
        throw new Exception("No items to purchase.");
    }

    $conn->commit();
    header("Location: receipt.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . (isset($_SESSION['direct_purchase']) ? "index.php" : "view_cart.php"));
    exit();
}
?> 