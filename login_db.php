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
    header('Location: login.php?error=required');
    exit;
}

// Prepared Statement
$sql = "SELECT name, designation, department, mobile_no, username, password, role FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Location: login.php?error=invalid');
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    $isPasswordValid = false;
    if (password_verify($password, $user['password'])) {
        $isPasswordValid = true;
    } elseif ($password === $user['password']) {
        // Plain-text password fallback for older records.
        $isPasswordValid = true;
    }

    if ($isPasswordValid) {
        $storedRole = !empty($user['role']) ? $user['role'] : ($user['system_role'] ?? '');
        $normalizedRole = strtolower(trim($storedRole));

        // Canonicalize the session role so role page guards match.
        switch ($normalizedRole) {
            case 'bdo':
                $canonicalRole = 'BDO';
                $redirect = 'BDO.php';
                break;
            case 'tho':
                $canonicalRole = 'THO';
                $redirect = 'THO.php';
                break;
            case 'ceo':
                $canonicalRole = 'CEO';
                $redirect = 'CEO.php';
                break;
            case 'hod':
                $canonicalRole = 'Hod';
                $redirect = 'Hod.php';
                break;
            case 'ग्रामपंचायत अधिकारी':
                $canonicalRole = 'ग्रामपंचायत अधिकारी';
                $redirect = 'gram_panchayat.php';
                break;
            case 'अंगणवाडी सेविका':
                $canonicalRole = 'अंगणवाडी सेविका';
                $redirect = 'anganwadi.php';
                break;
            case 'admin':
                $canonicalRole = 'admin';
                $redirect = 'admin.php';
                break;
            default:
                $canonicalRole = $storedRole;
                $redirect = 'user_dashboard.php';
                break;
        }

        // Store session
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $canonicalRole;
        $_SESSION['user_system_role'] = $user['system_role'] ?? '';
        $_SESSION['user_dept'] = $user['department'];
        $_SESSION['user_designation'] = $user['designation'];
        $_SESSION['user_mobile'] = $user['mobile_no'];

        header('Location: ' . $redirect);
        exit;
    }
}

mysqli_close($conn);
header('Location: login.php?error=invalid');
exit;
?>