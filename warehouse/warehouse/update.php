<?php
session_start();
include 'db.php'; // Database connection

// Get the product ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM inventory WHERE id=$id";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
}

// Update the product
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $threshold = $_POST['threshold'];
    $products_per_box = $_POST['products_per_box'];
    $query = "UPDATE inventory SET product_name='$product_name', quantity='$quantity', product_description='$description', threshold='$threshold', products_per_box='$products_per_box' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: inventory.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Update Product</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="product_name" class="form-control" value="<?php echo $product['product_name']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="<?php echo $product['product_description']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Threshold</label>
                <input type="number" name="threshold" class="form-control" value="<?php echo $product['threshold']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Products per Box</label>
                <input type="number" name="products_per_box" class="form-control" value="<?php echo $product['products_per_box']; ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-success">Update Product</button>
            <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>