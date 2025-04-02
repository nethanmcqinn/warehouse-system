<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: warehouse_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Check if product exists and has enough stock
    $stmt = $conn->prepare("SELECT quantity, price FROM inventory WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product || $quantity > $product['quantity']) {
        $_SESSION['error'] = "Invalid product or insufficient stock!";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Check if product already in cart
    if (isset($_SESSION['cart'][$product_id])) {
        // Update quantity if total doesn't exceed stock
        $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
        if ($new_quantity > $product['quantity']) {
            $_SESSION['error'] = "Cannot add more items. Exceeds available stock!";
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        // Add new product to cart
        $_SESSION['cart'][$product_id] = array(
            'quantity' => $quantity,
            'price' => $product['price']
        );
    }

    $_SESSION['success'] = "Item added to cart successfully!";
}

// Redirect back to previous page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit(); 