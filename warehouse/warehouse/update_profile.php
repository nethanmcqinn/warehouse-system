<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: warehouse_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'];
    $customer_name = $_POST['customer_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Update customer information (without address field)
    $update_query = "UPDATE customers 
                    SET customer_name = ?, 
                        email = ?, 
                        phone = ?
                    WHERE customer_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $customer_name, $email, $phone, $customer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
    }
}

// Redirect back to profile page
header('Location: my_profile.php');
exit(); 