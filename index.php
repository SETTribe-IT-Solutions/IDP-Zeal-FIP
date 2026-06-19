<?php
session_start();
require_once __DIR__ . '/include/config.php';

// Simple router to redirect users based on login state and role
if (isset($_SESSION['username'])) {
    $redirectPage = get_role_redirect_page($_SESSION['system_role'] ?? 'user');
    header("Location: " . $redirectPage);
} else {
    header("Location: login.php");
}
exit;
?>
