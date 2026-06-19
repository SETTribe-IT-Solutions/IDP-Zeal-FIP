<?php
// Forgot Password database handler - Process password reset requests

session_start();

include __DIR__ . '/include/config.php';

$response = [
    'success' => false,
    'message' => '',
    'step' => isset($_GET['step']) ? $_GET['step'] : 'request'
];

// Step 1: User requests password reset by providing username
if ($response['step'] === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';

    if (empty($username)) {
        $response['message'] = 'Username is required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    try {
        $mysqli = db_connect();

        // Check if username exists
        $stmt = $mysqli->prepare('SELECT Username FROM users WHERE Username = ?');
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $mysqli->error);
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'Username not found.';
        } else {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Insert token into database
            $insert_stmt = $mysqli->prepare(
                'INSERT INTO password_reset_tokens (username, token, token_expiry) VALUES (?, ?, ?)'
            );

            if (!$insert_stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }

            $insert_stmt->bind_param('sss', $username, $token, $token_expiry);

            if ($insert_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Reset link sent successfully. Check your email or use this token: ' . $token;
                $response['token'] = $token; // For development, remove in production
            } else {
                throw new Exception('Failed to generate reset token: ' . $insert_stmt->error);
            }

            $insert_stmt->close();
        }

        $stmt->close();
        $mysqli->close();

    } catch (Exception $e) {
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
}

// Step 2: Verify token and reset password
else if ($response['step'] === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'All fields are required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($new_password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (strlen($new_password) < 6) {
        $response['message'] = 'Password must be at least 6 characters.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    try {
        $mysqli = db_connect();

        // Verify token exists and is not expired
        $stmt = $mysqli->prepare(
            'SELECT username FROM password_reset_tokens WHERE token = ? AND token_expiry > NOW() AND is_used = FALSE'
        );

        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $mysqli->error);
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'Invalid or expired reset token.';
        } else {
            $token_row = $result->fetch_assoc();
            $username = $token_row['username'];

            // Hash the password (recommended) or use plain text
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            // For plain text: $hashed_password = $new_password;

            // Update user password
            $update_stmt = $mysqli->prepare('UPDATE users SET Password = ? WHERE Username = ?');
            if (!$update_stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }

            $update_stmt->bind_param('ss', $hashed_password, $username);

            if ($update_stmt->execute()) {
                // Mark token as used
                $mark_stmt = $mysqli->prepare('UPDATE password_reset_tokens SET is_used = TRUE WHERE token = ?');
                if ($mark_stmt) {
                    $mark_stmt->bind_param('s', $token);
                    $mark_stmt->execute();
                    $mark_stmt->close();
                }

                $response['success'] = true;
                $response['message'] = 'Password reset successful. Please login with your new password.';
                $response['redirect'] = 'login.php';
            } else {
                throw new Exception('Failed to update password: ' . $update_stmt->error);
            }

            $update_stmt->close();
        }

        $stmt->close();
        $mysqli->close();

    } catch (Exception $e) {
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
}

// Step 3: Verify token validity
else if ($response['step'] === 'verify-token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';

    if (empty($token)) {
        $response['message'] = 'Token is required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    try {
        $mysqli = db_connect();

        $stmt = $mysqli->prepare(
            'SELECT id FROM password_reset_tokens WHERE token = ? AND token_expiry > NOW() AND is_used = FALSE'
        );

        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $mysqli->error);
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Token is valid.';
        } else {
            $response['message'] = 'Invalid or expired token.';
        }

        $stmt->close();
        $mysqli->close();

    } catch (Exception $e) {
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
