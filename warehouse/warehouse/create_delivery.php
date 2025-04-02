<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'];
    $service_id = $_POST['service_id'];
    $quantity = $_POST['quantity'];
    $delivery_address = $_POST['delivery_address'];
    $delivery_date = $_POST['delivery_date'];
    $special_instructions = $_POST['special_instructions'];
    
    // Get customer name from session
    $customer_name = $_SESSION['customer_name'];

    // Insert into deliveries table with the correct column names
    $stmt = $conn->prepare("INSERT INTO deliveries (service_id, customer_name, quantity, delivery_date, delivery_address, special_instructions, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isisss", $service_id, $customer_name, $quantity, $delivery_date, $delivery_address, $special_instructions);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Delivery request created successfully!';
    } else {
        $_SESSION['error_message'] = 'Error creating delivery request.';
    }

    header("Location: customer_home.php");
    exit();
}