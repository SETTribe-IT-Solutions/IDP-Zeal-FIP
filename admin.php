<?php
session_start();
require_once __DIR__ . '/include/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:720px;">
        <div class="card-body">
            <h1 class="h3 mb-3">Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>.</p>
            <p>Your role is <strong>admin</strong>.</p>
            <a href="logout.php" class="btn btn-outline-primary">Logout</a>
        </div>
    </div>
</div>
</body>
</html>
