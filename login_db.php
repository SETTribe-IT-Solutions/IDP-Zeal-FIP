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
$sql = "SELECT Username AS username, Password AS password, Name AS name, Designation AS designation, Department AS department, Village AS village, Grampanchayat AS grampanchayat, Talika AS talika, `Mobile No` AS mobile, `System Role` AS system_role, Role AS role FROM users WHERE Username = ?";
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
            $_SESSION['user_name'] = $user['name'] ?? '';
            $_SESSION['user_designation'] = $user['designation'] ?? '';
            $_SESSION['user_dept'] = $user['department'] ?? '';
            $_SESSION['user_village'] = $user['village'] ?? '';
            $_SESSION['user_grampanchayat'] = $user['grampanchayat'] ?? '';
            $_SESSION['user_taluka'] = $user['talika'] ?? '';
            $_SESSION['user_mobile'] = $user['mobile'] ?? '';
            $_SESSION['system_role'] = !empty($user['system_role']) ? $user['system_role'] : 'user';
            
            // Set user_role (display role) to custom Role if specified, otherwise Designation/System Role
            $_SESSION['user_role'] = !empty($user['role']) ? $user['role'] : (!empty($user['designation']) ? $user['designation'] : $_SESSION['system_role']);

            // Redirect based on role
            $redirectPage = get_role_redirect_page($_SESSION['system_role']);
            header("Location: " . $redirectPage);
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