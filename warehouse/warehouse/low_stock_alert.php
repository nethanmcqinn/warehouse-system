<?php
include 'db.php'; // Include the database connection

// Query to fetch products with low stock
$query = "SELECT product_name, quantity, threshold FROM inventory WHERE quantity < threshold";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $product_name = $row['product_name'];
        $quantity = $row['quantity'];
        $threshold = $row['threshold'];

        // Send alert (for simplicity, we'll just echo the alert)
        echo "Alert: The stock of $product_name is low. Current quantity: $quantity, Threshold: $threshold.<br>";
    }
} else {
    echo "All products are sufficiently stocked.";
}
?>