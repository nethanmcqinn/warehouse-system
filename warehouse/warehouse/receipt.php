<?php
session_start();
include 'db.php';

if (!isset($_SESSION['last_receipt'])) {
    header("Location: index.php");
    exit();
}

$receipt = $_SESSION['last_receipt'];
$order_id = $receipt['order_id'];

// Fetch order items
$query = "SELECT oi.quantity, oi.unit_price, i.product_name 
          FROM order_items oi 
          JOIN inventory i ON oi.product_id = i.product_id 
          WHERE oi.order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();

// Fetch customer details
$customer_id = $_SESSION['customer_id'];
$query = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - #<?php echo $receipt['receipt_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .receipt {
                box-shadow: none !important;
            }
        }
        .receipt {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .receipt-header {
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .receipt-footer {
            border-top: 2px solid #ddd;
            margin-top: 20px;
            padding-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="receipt bg-white">
            <div class="receipt-header">
                <div class="row">
                    <div class="col-6">
                        <h1>Olympus Warehouse</h1>
                        <p>123 Warehouse Street<br>
                        City, State 12345<br>
                        Phone: (123) 456-7890</p>
                    </div>
                    <div class="col-6 text-end">
                        <h4>Receipt #<?php echo $receipt['receipt_number']; ?></h4>
                        <p>Date: <?php echo date('F j, Y g:i A', strtotime($receipt['date'])); ?><br>
                        Order #<?php echo $order_id; ?></p>
                    </div>
                </div>
            </div>

            <div class="customer-info mb-4">
                <h5>Bill To:</h5>
                <p><?php echo htmlspecialchars($customer['fname'] . ' ' . $customer['lname']); ?><br>
                <?php echo htmlspecialchars($customer['address_line1']); ?><br>
                <?php if ($customer['address_line2']) echo htmlspecialchars($customer['address_line2']) . '<br>'; ?>
                <?php echo htmlspecialchars($customer['city'] . ', ' . $customer['state'] . ' ' . $customer['postal_code']); ?><br>
                <?php echo htmlspecialchars($customer['email']); ?></p>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end">$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end"><strong>$<?php echo number_format($receipt['total_amount'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="payment-info mb-4">
                <h5>Payment Information:</h5>
                <p>Method: <?php echo htmlspecialchars($receipt['payment_method']); ?><br>
                Status: Completed</p>
            </div>

            <div class="receipt-footer">
                <p>Thank you for your business!</p>
                <small>This is a computer-generated receipt and requires no signature.</small>
            </div>

            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Clear the receipt data from session after displaying
unset($_SESSION['last_receipt']);
?> 