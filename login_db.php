<?php
session_start();

require_once __DIR__ . '/include/config.php';

// Database Connection
$conn = db_connect();

// Get Form Data
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Check empty fields
if (empty($username) || empty($password)) {
    echo "<script>
            alert('Please enter username and password');
            window.location.href='login.php';
          </script>";
    exit;
}

// Prepared Statement
$sql = "SELECT Username AS username, Password AS password FROM users WHERE Username = ?";
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

            // Redirect
            header("Location: index.php");
            exit;

        } else {
            echo "<script>
                    alert('Wrong Username or Password');
                    window.location.href='login.php';
                  </script>";
        }

    } else {
        echo "<script>
                alert('User Not Found');
                window.location.href='login.php';
              </script>";
    }

} else {
    echo "Query Error";
}

mysqli_close($conn);
?>