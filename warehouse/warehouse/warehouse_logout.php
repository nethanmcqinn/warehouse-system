<?php
session_start();
session_destroy();
session_unset();

// Clear all session variables
$_SESSION = array();

// Redirect to the login page
header("Location: warehouse_login.php");
exit();
?>
