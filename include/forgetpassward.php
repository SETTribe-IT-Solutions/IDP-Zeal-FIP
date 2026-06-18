<?php
session_start();

$errors = [];
$success = '';
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($newPassword === '') {
        $errors[] = 'Please enter a new password.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your new password.';
    }

    if ($newPassword !== '' && strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if ($newPassword !== '' && $confirmPassword !== '' && $newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match. Please try again.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // TODO: Replace this block with real database update logic.
        // Example:
        // require_once __DIR__ . '/config.php';
        // $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
        // $stmt->execute([$passwordHash, $email]);

        $success = 'Your password has been updated successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            padding: 32px;
        }
        h1 {
            margin-top: 0;
            font-size: 26px;
            margin-bottom: 12px;
        }
        p.subtitle {
            margin: 0 0 24px;
            color: #6b7280;
        }
        .field {
            margin-bottom: 18px;
        }
        .field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .field input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }
        .field input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 10px;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .small-note {
            margin-top: 18px;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .small-note a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="card">
            <h1>Reset Password</h1>
            <p class="subtitle">Enter your new password and confirm it to change your account password.</p>

            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="field">
                    <label for="new_password">Create New Password</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" placeholder="New password" required>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" placeholder="Confirm password" required>
                </div>
                <?php if ($email !== ''): ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
                <?php endif; ?>
                <button type="submit" class="button">Save New Password</button>
            </form>

            <p class="small-note">Remembered your password? <a href="../login.php">Sign in</a>.</p>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
