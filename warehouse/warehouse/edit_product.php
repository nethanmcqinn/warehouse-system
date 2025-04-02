<?php
session_start();
include 'db.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}

$product_id = $_GET['id'] ?? '';
if (empty($product_id)) {
    header("Location: manage_products.php");
    exit();
}

// Handle form submission
if (isset($_POST['update'])) {
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $cost_price = $_POST['cost_price'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $threshold = $_POST['threshold'];
    $products_per_box = $_POST['products_per_box'];
    $minimum_order_quantity = $_POST['minimum_order_quantity'];

    // Generate new QR Code if product details changed
    $qr_data = json_encode([
        'product_id' => $product_id,
        'name' => $product_name,
        'price' => $price
    ]);
    $qr_code_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($qr_data);

    // Update product information
    $query = "UPDATE inventory SET 
              product_name = ?, 
              quantity = ?, 
              cost_price = ?, 
              price = ?, 
              product_description = ?, 
              qr_code = ?,
              threshold = ?, 
              category = ?, 
              products_per_box = ?, 
              minimum_order_quantity = ? 
              WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiddssiiis", 
        $product_name, $quantity, $cost_price, $price, $description, 
        $qr_code_url, $threshold, $category, $products_per_box, 
        $minimum_order_quantity, $product_id
    );
    $stmt->execute();

    // Handle new image uploads
    if (!empty($_FILES['new_images']['name'][0])) {
        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_images']['error'][$key] === 0) {
                $file_extension = pathinfo($_FILES['new_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = $product_id . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    // Insert new image
                    $image_query = "INSERT INTO product_images (product_id, image_url) VALUES (?, ?)";
                    $stmt = $conn->prepare($image_query);
                    $stmt->bind_param("ss", $product_id, $target_path);
                    $stmt->execute();
                }
            }
        }
    }

    // Handle image deletions
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_id) {
            // Get image path before deletion
            $query = "SELECT image_url FROM product_images WHERE id = ? AND product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $image_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($image = $result->fetch_assoc()) {
                // Delete file from server
                if (file_exists($image['image_url'])) {
                    unlink($image['image_url']);
                }
                // Delete from database
                $delete_query = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("is", $image_id, $product_id);
                $stmt->execute();
            }
        }
    }

    header("Location: manage_products.php");
    exit();
}

// Fetch product information
$query = "SELECT i.*, GROUP_CONCAT(pi.id) as image_ids, GROUP_CONCAT(pi.image_url) as images 
          FROM inventory i 
          LEFT JOIN product_images pi ON i.product_id = pi.product_id 
          WHERE i.product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: manage_products.php");
    exit();
}

$image_ids = $product['image_ids'] ? explode(',', $product['image_ids']) : [];
$images = $product['images'] ? explode(',', $product['images']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .current-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .image-container {
            position: relative;
            aspect-ratio: 1;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-code {
            max-width: 150px;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h1 class="mb-4">Edit Product</h1>

    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="product_name">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" 
                           value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="category">Category</label>
                    <input type="text" class="form-control" id="category" name="category" 
                           value="<?php echo htmlspecialchars($product['category']); ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="quantity">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" 
                           value="<?php echo $product['quantity']; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="cost_price">Cost Price</label>
                    <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" 
                           value="<?php echo $product['cost_price']; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="price">Selling Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" 
                           value="<?php echo $product['price']; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="threshold">Stock Threshold</label>
                    <input type="number" class="form-control" id="threshold" name="threshold" 
                           value="<?php echo $product['threshold']; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="products_per_box">Products Per Box</label>
                    <input type="number" class="form-control" id="products_per_box" name="products_per_box" 
                           value="<?php echo $product['products_per_box']; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="minimum_order_quantity">Minimum Order Quantity</label>
                    <input type="number" class="form-control" id="minimum_order_quantity" name="minimum_order_quantity" 
                           value="<?php echo $product['minimum_order_quantity']; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                        echo htmlspecialchars($product['product_description']); 
                    ?></textarea>
                </div>
            </div>
        </div>

        <!-- Current Images -->
        <h4 class="mt-4">Current Images</h4>
        <div class="current-images">
            <?php foreach ($images as $index => $image) { ?>
                <div class="image-container">
                    <img src="<?php echo $image; ?>" alt="Product Image">
                    <button type="button" class="delete-image" onclick="toggleImageDeletion(<?php echo $image_ids[$index]; ?>)">&times;</button>
                    <input type="checkbox" name="delete_images[]" value="<?php echo $image_ids[$index]; ?>" style="display: none;">
                </div>
            <?php } ?>
        </div>

        <!-- New Images -->
        <div class="form-group mb-3">
            <label for="new_images">Add New Images</label>
            <input type="file" class="form-control" id="new_images" name="new_images[]" multiple accept="image/*">
        </div>

        <!-- QR Code -->
        <div class="mb-3">
            <h4>Product QR Code</h4>
            <img src="<?php echo $product['qr_code']; ?>" class="qr-code" alt="Product QR Code">
            <p class="text-muted">QR Code will be automatically updated when product details change.</p>
        </div>

        <button type="submit" name="update" class="btn btn-primary">Update Product</button>
        <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleImageDeletion(imageId) {
    const checkbox = document.querySelector(`input[value="${imageId}"]`);
    checkbox.checked = !checkbox.checked;
    const button = checkbox.parentElement.querySelector('.delete-image');
    button.style.background = checkbox.checked ? 'rgba(0, 255, 0, 0.7)' : 'rgba(255, 0, 0, 0.7)';
}
</script>
</body>
</html>