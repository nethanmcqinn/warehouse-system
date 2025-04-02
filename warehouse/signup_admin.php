<?php
require_once 'warehouse/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = 'Email already exists';
        } else {
            // Hash password using PHP's password_hash function (uses BCRYPT by default)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin user
            $stmt = $conn->prepare('INSERT INTO users (fname, lname, email, password, role, created_at) VALUES (?, ?, ?, ?, "admin", CURRENT_TIMESTAMP)');
            $stmt->bind_param('ssss', $fname, $lname, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $message = 'Admin account created successfully';
                // Redirect to login page after 2 seconds
                header('refresh:2;url=warehouse_login.php');
            } else {
                $message = 'Error creating account: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Create Admin Account</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="fname">First Name:</label>
                <input type="text" id="fname" name="fname" required>
            </div>
            <div class="form-group">
                <label for="lname">Last Name:</label>
                <input type="text" id="lname" name="lname" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Create Admin Account</button>
        </form>
    </div>
</body>
</html>