<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Fetch available services
$services_query = "SELECT * FROM services";
$services_result = $conn->query($services_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Delivery - Olympus Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Request Delivery</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form action="create_delivery.php" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="service_id" class="form-label">Select Service</label>
                <select class="form-select" id="service_id" name="service_id" required>
                    <option value="">Choose a service...</option>
                    <?php while ($service = $services_result->fetch_assoc()): ?>
                        <option value="<?php echo $service['id']; ?>">
                            <?php echo $service['service_name']; ?> - $<?php echo number_format($service['price'], 2); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
            </div>

            <div class="mb-3">
                <label for="delivery_address" class="form-label">Delivery Address</label>
                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required></textarea>
            </div>

            <div class="mb-3">
                <label for="delivery_date" class="form-label">Preferred Delivery Date</label>
                <input type="date" class="form-control" id="delivery_date" name="delivery_date" required>
            </div>

            <div class="mb-3">
                <label for="special_instructions" class="form-label">Special Instructions (Optional)</label>
                <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Delivery Request</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>