<?php
session_start();

// Logout user
if (isset($_SESSION['user_id'])) {
    session_destroy();
}

header('Location: login.php');
exit();
?>
