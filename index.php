<?php
session_start();

// Simple router to redirect users based on login state
if (isset($_SESSION['username'])) {
    header("Location: user_dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>
