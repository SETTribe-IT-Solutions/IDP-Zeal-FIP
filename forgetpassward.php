<?php
session_start();
require_once __DIR__ . '/include/config.php';

$errors = [];
$success = '';
$username = trim($_GET['username'] ?? $_POST['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($username === '') {
        $errors[] = 'Please enter a username.';
    }

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
                $errors[] = 'Username not found.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                // Update user password
                $update_stmt = $mysqli->prepare('UPDATE users SET Password = ? WHERE Username = ?');
                if (!$update_stmt) {
                    throw new Exception('Database prepare failed: ' . $mysqli->error);
                }
                $update_stmt->bind_param('ss', $passwordHash, $username);

                if ($update_stmt->execute()) {
                    $success = 'Your password has been updated successfully.';
                } else {
                    throw new Exception('Failed to update password: ' . $update_stmt->error);
                }
                $update_stmt->close();
            }
            $stmt->close();
            $mysqli->close();
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Include Portal Header
include __DIR__ . '/include/header.php';
?>

<style>
    /* Layout integration */
    .password-reset-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - var(--header-height) - 120px);
        padding: 40px 20px;
        box-sizing: border-box;
    }

    .card {
        width: 100%;
        max-width: 440px;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 40px;
        box-sizing: border-box;
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        transition: background-color var(--transition-normal), border-color var(--transition-normal), box-shadow var(--transition-normal);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    h1 {
        margin: 0 0 10px;
        font-family: var(--font-heading);
        font-size: 24px;
        font-weight: 700;
        color: var(--text-primary);
        text-align: center;
    }

    p.subtitle {
        margin: 0 0 28px;
        color: var(--text-muted);
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }

    .field {
        margin-bottom: 20px;
    }

    .field label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .input-wrapper {
        position: relative;
        width: 100%;
    }

    .field input {
        width: 100%;
        height: 46px;
        padding: 10px 16px;
        background-color: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-family: var(--font-body);
        font-size: 14px;
        color: var(--text-primary);
        outline: none;
        box-sizing: border-box;
        transition: border-color var(--transition-fast), background-color var(--transition-fast), box-shadow var(--transition-fast);
    }

    .field input::placeholder {
        color: var(--text-muted);
    }

    .field input:focus {
        border-color: var(--primary-light);
        background-color: var(--bg-card);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .input-wrapper.password-wrapper input {
        padding-right: 50px;
    }

    .toggle-password {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: var(--radius-sm);
        transition: color var(--transition-fast), background-color var(--transition-fast);
    }

    .toggle-password:hover {
        color: var(--text-primary);
        background-color: var(--bg-hover);
    }

    .button {
        width: 100%;
        height: 48px;
        border: none;
        border-radius: var(--radius-md);
        background-color: var(--primary-light);
        color: #ffffff;
        font-family: var(--font-body);
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        transition: background-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .button:hover {
        background-color: var(--primary-hover);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        transform: translateY(-1px);
    }

    .button:active {
        transform: translateY(0);
    }

    .message {
        margin-bottom: 24px;
        padding: 14px 16px;
        border-radius: var(--radius-md);
        font-size: 14px;
        line-height: 1.5;
        border: 1px solid transparent;
    }

    .message.error {
        background-color: rgba(220, 38, 38, 0.08);
        color: var(--danger-color);
        border-color: rgba(220, 38, 38, 0.16);
    }

    .message.success {
        background-color: rgba(5, 150, 105, 0.08);
        color: var(--success-color);
        border-color: rgba(5, 150, 105, 0.16);
    }

    .message ul {
        margin: 0;
        padding-left: 20px;
    }

    .small-note {
        margin-top: 28px;
        font-size: 13px;
        color: var(--text-muted);
        text-align: center;
    }

    .small-note a {
        color: var(--primary-light);
        font-weight: 600;
        text-decoration: none;
        transition: color var(--transition-fast);
    }

    .small-note a:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }
</style>

<!-- Include Portal Sidebar -->


<!-- Main Workspace Container -->
<main class="main-content">
    <div class="password-reset-wrapper">
        <div class="card">
            <h1>Reset Password</h1>
            <p class="subtitle">Enter the username and new credentials to reset the password.</p>

            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul>
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
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="Enter username"
                            value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>" required
                            autocomplete="username">
                    </div>
                </div>
                <div class="field">
                    <label for="new_password">Create New Password</label>
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                            placeholder="New password" required>
                        <button type="button" class="toggle-password" data-target="new_password"
                            aria-label="Toggle password visibility">👁️</button>
                    </div>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
                            placeholder="Confirm password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password"
                            aria-label="Toggle password visibility">👁️</button>
                    </div>
                </div>
                <button type="submit" class="button">Save New Password</button>
            </form>

            <p class="small-note">Remembered your password? <a href="login.php">Sign in</a>.</p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    button.textContent = '🙈';
                } else {
                    input.type = 'password';
                    button.textContent = '👁️';
                }
            });
        });
    </script>

    <?php
    if (file_exists(__DIR__ . '/include/footer.php')) {
        include __DIR__ . '/include/footer.php';
    }
    ?>
</main>