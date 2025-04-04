<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: warehouse_login.php");
    exit();
}
include 'db.php';
include 'sidebar.php';

// Handle image upload and product creation
if (isset($_POST['add'])) {
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $cost_price = $_POST['cost_price'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $threshold = $_POST['threshold'];
    $products_per_box = $_POST['products_per_box'];
    $minimum_order_quantity = $_POST['minimum_order_quantity'];
    $product_id = uniqid('PROD');

    // First, insert the product
    $query = "INSERT INTO inventory (product_id, product_name, quantity, cost_price, price, product_description, 
              threshold, category, products_per_box, minimum_order_quantity) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiiddsisi", $product_id, $product_name, $quantity, $cost_price, $price, $description, 
                      $threshold, $category, $products_per_box, $minimum_order_quantity);
    $stmt->execute();

    // Handle multiple image uploads
    if (isset($_FILES['product_images'])) {
        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $total_files = count($_FILES['product_images']['name']);
        
        for($i = 0; $i < $total_files; $i++) {
            if ($_FILES['product_images']['error'][$i] === 0) {
                $file_extension = pathinfo($_FILES['product_images']['name'][$i], PATHINFO_EXTENSION);
                $file_name = $product_id . '_' . $i . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['product_images']['tmp_name'][$i], $target_path)) {
                    // Insert image record
                    $is_primary = ($i === 0) ? 1 : 0; // First image is primary
                    $query = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssi", $product_id, $target_path, $is_primary);
                    $stmt->execute();
                }
            }
        }
    }
    
    header("Location: manage_products.php");
    exit();
}

// Fetch all products with their images
$query = "SELECT i.*, GROUP_CONCAT(pi.image_url) as images 
          FROM inventory i 
          LEFT JOIN product_images pi ON i.product_id = pi.product_id 
          GROUP BY i.product_id 
          ORDER BY i.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px; /* Match sidebar width */
            width: calc(100% - 250px);
        }

        .product-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .carousel-inner {
            height: 200px;
        }
        .carousel-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .carousel-control-prev,
        .carousel-control-next {
            width: 10%;
            background: rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Products</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                Add New Product
            </button>
        </div>

        <!-- Products Grid -->
        <div class="product-grid">
            <?php while ($row = $result->fetch_assoc()) { 
                $images = $row['images'] ? explode(',', $row['images']) : [];
            ?>
                <div class="product-card">
                    <?php if (!empty($images)) { ?>
                        <div id="carousel-<?php echo $row['product_id']; ?>" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach($images as $index => $image) { ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>" 
                                             class="d-block w-100">
                                    </div>
                                <?php } ?>
                            </div>
                            <?php if (count($images) > 1) { ?>
                                <button class="carousel-control-prev" type="button" 
                                        data-bs-target="#carousel-<?php echo $row['product_id']; ?>" 
                                        data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" 
                                        data-bs-target="#carousel-<?php echo $row['product_id']; ?>" 
                                        data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="product-image bg-secondary d-flex align-items-center justify-content-center text-white">
                            No Image
                        </div>
                    <?php } ?>
                    <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($row['price'], 2); ?></p>
                    <p><strong>Stock:</strong> <?php echo $row['quantity']; ?></p>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-warning">Edit</button>
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_products.php" method="POST" enctype="multipart/form-data" id="addProductForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="product_name">Product Name</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="category">Category</label>
                                    <input type="text" class="form-control" id="category" name="category" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="quantity">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="cost_price">Cost Price</label>
                                    <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="price">Selling Price</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="threshold">Low Stock Threshold</label>
                                    <input type="number" class="form-control" id="threshold" name="threshold" value="10">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="products_per_box">Products Per Box</label>
                                    <input type="number" class="form-control" id="products_per_box" name="products_per_box" value="1">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="minimum_order_quantity">Minimum Order Quantity</label>
                                    <input type="number" class="form-control" id="minimum_order_quantity" name="minimum_order_quantity" value="1">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="product_images">Product Images</label>
                                    <input type="file" class="form-control" id="product_images" name="product_images[]" 
                                           accept="image/*" multiple required>
                                    <small class="form-text text-muted">
                                        You can select multiple images. The first image will be the primary image.
                                    </small>
                                </div>
                                <div id="image-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addProductForm" name="add" class="btn btn-primary">Add Product</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Image preview functionality
    document.getElementById('product_images').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        
        for(let i = 0; i < e.target.files.length; i++) {
            const file = e.target.files[i];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.style.width = '100px';
                    div.style.height = '100px';
                    div.style.overflow = 'hidden';
                    div.style.borderRadius = '4px';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    div.appendChild(img);
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            }
        }
    });

    // Show success message if product was added
    <?php if (isset($_POST['add'])) { ?>
        window.onload = function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            if (modal) {
                modal.hide();
            }
        }
    <?php } ?>
    </script>
</body>
</html>