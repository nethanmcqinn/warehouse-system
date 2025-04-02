<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Handle direct purchase
if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Get product details
    $stmt = $conn->prepare("SELECT price FROM inventory WHERE product_id = ?");
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        $total = $product['price'] * $quantity;
        // Store direct purchase in session
        $_SESSION['direct_purchase'] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $product['price']
        ];
    } else {
        header("Location: index.php");
        exit();
    }
} else {
    // Get cart total from session
    $total = 0;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        if (!empty($product_ids)) {
            $ids_string = str_repeat('?,', count($product_ids) - 1) . '?';
            
            $stmt = $conn->prepare("SELECT product_id, price FROM inventory WHERE product_id IN ($ids_string)");
            $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($product = $result->fetch_assoc()) {
                if (isset($_SESSION['cart'][$product['product_id']]['quantity'])) {
                    $total += $product['price'] * $_SESSION['cart'][$product['product_id']]['quantity'];
                }
            }
        }
    }

    if ($total <= 0) {
        header("Location: view_cart.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .payment-form {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .payment-method {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            cursor: pointer;
        }
        .payment-method:hover {
            background-color: #f8f9fa;
        }
        .payment-method.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="payment-form bg-white">
            <h2 class="mb-4">Payment Information</h2>
            
            <div class="order-summary mb-4">
                <h5>Order Summary</h5>
                <p class="h4">Total Amount: $<?php echo number_format($total, 2); ?></p>
            </div>

            <form action="process_purchase.php" method="POST">
                <div class="mb-4">
                    <h5>Select Payment Method</h5>
                    
                    <div class="payment-method" onclick="selectPayment('credit_card')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="credit_card" value="credit_card" required>
                            <label class="form-check-label" for="credit_card">
                                <strong>Credit Card</strong><br>
                                <small class="text-muted">Pay securely with your credit card</small>
                            </label>
                        </div>
                    </div>

                    <div class="payment-method" onclick="selectPayment('bank_transfer')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="bank_transfer" value="bank_transfer">
                            <label class="form-check-label" for="bank_transfer">
                                <strong>Bank Transfer</strong><br>
                                <small class="text-muted">Direct bank transfer to our account</small>
                            </label>
                        </div>
                    </div>

                    <div class="payment-method" onclick="selectPayment('cash_on_delivery')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="cash_on_delivery" value="cash_on_delivery">
                            <label class="form-check-label" for="cash_on_delivery">
                                <strong>Cash on Delivery</strong><br>
                                <small class="text-muted">Pay when you receive your order</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Complete Purchase</button>
                    <a href="view_cart.php" class="btn btn-outline-secondary">Back to Cart</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked payment method
            document.getElementById(method).closest('.payment-method').classList.add('selected');
            
            // Check the radio button
            document.getElementById(method).checked = true;
        }
    </script>
</body>
</html> 