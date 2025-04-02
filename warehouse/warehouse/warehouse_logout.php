<?php
session_start();
session_destroy();
header("Location: warehouse_login.php");
exit();
?>
