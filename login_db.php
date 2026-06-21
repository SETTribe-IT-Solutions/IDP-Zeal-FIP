<?php
session_start();

require_once __DIR__ . '/include/config.php';

// Helper function to render SweetAlert2 redirects
function show_sweetalert($title, $text, $icon, $redirect) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Loading...</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: " . json_encode($title) . ",
                    text: " . json_encode($text) . ",
                    icon: " . json_encode($icon) . ",
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'OK'
                }).then(function () {
                    window.location.href = " . json_encode($redirect) . ";
                });
            });
        </script>
    </body>
    </html>";
    exit;
}

// Database Connection
$conn = db_connect();

// Get Form Data
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Check empty fields
if (empty($username) || empty($password)) {
    show_sweetalert('Required!', 'Please enter username and password', 'warning', 'login.php');
}

// Prepared Statement
$sql = "SELECT name, designation, department, mobile_no, username, password, system_role, role FROM users WHERE Username = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Verify Password
        if (password_verify($password, $user['password']) || $password === $user['password']) {

            // Store session
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_dept'] = $user['department'];
            $_SESSION['user_designation'] = $user['designation'];
            $_SESSION['user_mobile'] = $user['mobile_no'];
            $_SESSION['user_system_role'] = $user['system_role'];

            // Initial generation (first letters of name, capitalized, e.g. "Rajesh Patil" -> "RP")
            $name_parts = explode(' ', trim($user['name']));
            $initials = '';
            if (count($name_parts) > 1) {
                $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
            } elseif (count($name_parts) === 1 && !empty($name_parts[0])) {
                $initials = strtoupper(substr($name_parts[0], 0, 2));
            } else {
                $initials = 'US';
            }
            $_SESSION['user_initials'] = $initials;

            // Redirect
            header("Location: user_dashboard.php");
            exit;

        } else {
            show_sweetalert('Login Failed!', 'Wrong Username or Password', 'error', 'login.php');
        }

    } else {
        show_sweetalert('Not Found!', 'User Not Found', 'error', 'login.php');
    }

} else {
    echo "Query Error";
}

mysqli_close($conn);
?>