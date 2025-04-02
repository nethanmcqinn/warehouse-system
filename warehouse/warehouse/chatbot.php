<?php
include 'db.php'; // Include the database connection

header('Content-Type: application/json');

$request = json_decode(file_get_contents('php://input'), true);
$message = $request['message'] ?? '';

error_log("Received message: $message");

// Extract the item name from the message (simple keyword extraction)
preg_match('/stock of (.+)/i', $message, $matches);
$item_name = $matches[1] ?? '';

if ($item_name) {
    // Query the database for the item stock
    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE product_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $stmt->bind_result($quantity);
    $stmt->fetch();
    $stmt->close();

    error_log("Queried item: $item_name, Quantity: $quantity");

    if ($quantity !== null) {
        $reply = "The stock of $item_name is $quantity.";
    } else {
        $reply = "Sorry, I couldn't find the item '$item_name' in the inventory.";
    }
} else {
    $reply = "Please ask about the stock of an item, e.g., 'stock of Widget A'.";
}

echo json_encode(['reply' => $reply]);
?>