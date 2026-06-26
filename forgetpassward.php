<?php
session_start();
require_once __DIR__ . '/include/config.php';

$errors = [];
$success = '';
$username = trim($_GET['username'] ?? $_POST['username'] ?? '');
$step = (int) ($_POST['step'] ?? 1); // Step 1: verify identity, Step 2: set new password

// Security questions list (same as in create_user.php)
$security_questions = [
    'तुमच्या आईचे माहेरचे नाव काय आहे? (What is your mother\'s maiden name?)',
    'तुमच्या पहिल्या शाळेचे नाव काय आहे? (What is the name of your first school?)',
    'तुमच्या आवडत्या शिक्षकांचे नाव काय आहे? (What is the name of your favorite teacher?)',
    'तुमच्या बालपणीच्या सर्वात चांगल्या मित्राचे नाव काय आहे? (What is the name of your childhood best friend?)',
    'तुमचे जन्मस्थान कोणते आहे? (What is your place of birth?)',
    'तुमच्या पहिल्या पाळीव प्राण्याचे नाव काय आहे? (What is the name of your first pet?)'
];

$verified = false;
$user_question = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        // Step 1: Verify username + security answer
        $username = trim($_POST['username'] ?? '');
        $security_answer = trim($_POST['security_answer'] ?? '');

        if ($username === '') {
            $errors[] = 'कृपया वापरकर्तानाव प्रविष्ट करा (Please enter a username).';
        }
        if ($security_answer === '') {
            $errors[] = 'कृपया सुरक्षा उत्तर प्रविष्ट करा (Please enter your security answer).';
        }

        if (empty($errors)) {
            try {
                $mysqli = db_connect();
                $stmt = $mysqli->prepare('SELECT security_question, security_answer FROM users WHERE Username = ?');
                if (!$stmt) {
                    throw new Exception('Database prepare failed: ' . $mysqli->error);
                }
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $errors[] = 'वापरकर्तानाव सापडले नाही (Username not found).';
                } else {
                    $row = $result->fetch_assoc();
                    $stored_question = $row['security_question'];
                    $stored_answer = $row['security_answer'];

                    if (empty($stored_question) || empty($stored_answer)) {
                        $errors[] = 'या खात्यासाठी सुरक्षा प्रश्न सेट केलेला नाही. कृपया प्रशासकाशी संपर्क साधा. (Security question not set for this account. Please contact the administrator.)';
                    } else {
                        // Verify the answer (case-insensitive)
                        $answer_lower = mb_strtolower(trim($security_answer), 'UTF-8');
                        if (password_verify($answer_lower, $stored_answer)) {
                            // Answer correct — allow password reset
                            $verified = true;
                            $step = 2;
                            // Store a verification token in session
                            $_SESSION['password_reset_verified'] = true;
                            $_SESSION['password_reset_username'] = $username;
                            $_SESSION['password_reset_time'] = time();
                        } else {
                            $errors[] = 'सुरक्षा उत्तर चुकीचे आहे (Incorrect security answer). कृपया पुन्हा प्रयत्न करा.';
                        }
                    }
                }
                $stmt->close();
                $mysqli->close();
            } catch (Exception $e) {
                $errors[] = 'त्रुटी: ' . $e->getMessage();
            }
        }

    } elseif ($step === 2) {
        // Step 2: Set new password (only if verified via session)
        $username = trim($_POST['username'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Verify session token
        if (
            empty($_SESSION['password_reset_verified']) ||
            ($_SESSION['password_reset_username'] ?? '') !== $username ||
            (time() - ($_SESSION['password_reset_time'] ?? 0)) > 600 // 10-minute expiry
        ) {
            $errors[] = 'सत्र कालबाह्य झाले. कृपया पुन्हा सत्यापन करा. (Session expired. Please verify again.)';
            $step = 1;
        } else {
            if ($newPassword === '') {
                $errors[] = 'कृपया नवीन पासवर्ड प्रविष्ट करा (Please enter a new password).';
            } else {
                // Same validation rules as create_user.php
                if (strlen($newPassword) < 5 || strlen($newPassword) > 20) {
                    $errors[] = 'Password must be 5 to 20 characters.';
                }
                if (!preg_match('/[A-Z]/', $newPassword)) {
                    $errors[] = 'Password must contain at least one uppercase letter.';
                }
                if (!preg_match('/[a-z]/', $newPassword)) {
                    $errors[] = 'Password must contain at least one lowercase letter.';
                }
                if (!preg_match('/[0-9]/', $newPassword)) {
                    $errors[] = 'Password must contain at least one number.';
                }
                if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
                    $errors[] = 'Password must contain at least one special character.';
                }
            }
            if ($confirmPassword === '') {
                $errors[] = 'कृपया नवीन पासवर्ड पुष्टी करा (Please confirm your new password).';
            }
            if ($newPassword !== '' && $confirmPassword !== '' && $newPassword !== $confirmPassword) {
                $errors[] = 'पासवर्ड जुळत नाहीत (Passwords do not match). कृपया पुन्हा प्रयत्न करा.';
            }

            if (empty($errors)) {
                try {
                    $mysqli = db_connect();
                    // Store password as plain text (same as create_user.php)
                    $update_stmt = $mysqli->prepare('UPDATE users SET Password = ? WHERE Username = ?');
                    if (!$update_stmt) {
                        throw new Exception('Database prepare failed: ' . $mysqli->error);
                    }
                    $update_stmt->bind_param('ss', $newPassword, $username);

                    if ($update_stmt->execute()) {
                        $success = 'तुमचा पासवर्ड यशस्वीरित्या अपडेट झाला! (Your password has been updated successfully.)';
                        // Clear the session tokens
                        unset($_SESSION['password_reset_verified']);
                        unset($_SESSION['password_reset_username']);
                        unset($_SESSION['password_reset_time']);
                        $step = 1; // Reset to step 1
                    } else {
                        throw new Exception('Failed to update password: ' . $update_stmt->error);
                    }
                    $update_stmt->close();
                    $mysqli->close();
                } catch (Exception $e) {
                    $errors[] = 'त्रुटी: ' . $e->getMessage();
                }
            } else {
                // Keep on step 2 if there were validation errors
                $verified = true;
                $step = 2;
            }
        }
    }
}

// If step 1 and we have a username, fetch the user's security question to display
if ($step === 1 && $username !== '' && empty($errors)) {
    // Just to show the question — happens on GET with ?username= or after typing username
}

// Fetch security question for display if username is provided (GET request or after step 1 error)
$display_question = '';
if ($username !== '' && $step === 1) {
    try {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('SELECT security_question FROM users WHERE Username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $display_question = $row['security_question'] ?? '';
            }
            $stmt->close();
        }
        $mysqli->close();
    } catch (Exception $e) {
        // Silently fail
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
        max-width: 480px;
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

    .field input,
    .field select {
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

    .field input:focus,
    .field select:focus {
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

    /* Step indicators */
    .steps-indicator {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 28px;
    }

    .step-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        background: rgba(37, 99, 235, 0.06);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .step-badge.active {
        background: rgba(37, 99, 235, 0.12);
        color: var(--primary-light);
        border-color: var(--primary-light);
    }

    .step-badge.done {
        background: rgba(5, 150, 105, 0.1);
        color: var(--success-color);
        border-color: var(--success-color);
    }

    .step-badge i {
        font-size: 14px;
    }

    .security-question-display {
        background: rgba(37, 99, 235, 0.06);
        border: 1px solid rgba(37, 99, 235, 0.15);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        margin-bottom: 20px;
        font-size: 14px;
        color: var(--text-primary);
        line-height: 1.5;
    }

    .security-question-display i {
        color: var(--primary-light);
        margin-right: 8px;
    }

    /* Password requirements indicators */
    .pw-reqs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .pw-req {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.2s ease;
    }

    .pw-req i {
        font-size: 10px;
    }

    .pw-match-msg {
        margin-top: 8px;
        font-size: 13px;
        font-weight: 600;
        min-height: 20px;
    }
</style>

<!-- Include Portal Sidebar -->


<!-- Main Workspace Container -->
<main class="main-content">
    <div class="password-reset-wrapper">
        <div class="card">
            <h1><i class="fa-solid fa-shield-halved"></i> पासवर्ड रीसेट करा</h1>
            <p class="subtitle">Reset Password — सुरक्षा प्रश्नाचे उत्तर देऊन तुमचा पासवर्ड बदला.</p>

            <!-- Step indicators -->
            <div class="steps-indicator">
                <span class="step-badge <?php echo ($step === 1 && empty($success)) ? 'active' : ($step === 2 || !empty($success) ? 'done' : ''); ?>">
                    <i class="fa-solid <?php echo ($step === 2 || !empty($success)) ? 'fa-circle-check' : 'fa-1'; ?>"></i>
                    ओळख पडताळणी
                </span>
                <span class="step-badge <?php echo ($step === 2 && empty($success)) ? 'active' : (!empty($success) ? 'done' : ''); ?>">
                    <i class="fa-solid <?php echo !empty($success) ? 'fa-circle-check' : 'fa-2'; ?>"></i>
                    नवीन पासवर्ड
                </span>
            </div>

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
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1 && empty($success)): ?>
                <!-- STEP 1: Verify Identity -->
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="verifyForm">
                    <input type="hidden" name="step" value="1">
                    <div class="field">
                        <label for="username"><i class="fa-solid fa-user"></i> वापरकर्तानाव / Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" placeholder="तुमचे वापरकर्तानाव प्रविष्ट करा"
                                value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>" required
                                autocomplete="username">
                        </div>
                    </div>

                    <?php if ($display_question !== ''): ?>
                        <div class="security-question-display">
                            <i class="fa-solid fa-question-circle"></i>
                            <strong>तुमचा सुरक्षा प्रश्न:</strong><br>
                            <?php echo htmlspecialchars($display_question, ENT_QUOTES); ?>
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="security_answer"><i class="fa-solid fa-key"></i> सुरक्षा उत्तर / Security Answer</label>
                        <div class="input-wrapper">
                            <input type="text" id="security_answer" name="security_answer"
                                placeholder="तुमच्या सुरक्षा प्रश्नाचे उत्तर टाका" required>
                        </div>
                    </div>
                    <button type="submit" class="button"><i class="fa-solid fa-arrow-right"></i> ओळख पडताळा / Verify Identity</button>
                </form>

            <?php elseif ($step === 2 && empty($success)): ?>
                <!-- STEP 2: Set New Password -->
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>">

                    <div class="message success" style="margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> ओळख यशस्वीरित्या पडताळली! आता नवीन पासवर्ड तयार करा. (Identity verified! Now create a new password.)
                    </div>

                    <div class="field">
                        <label for="new_password"><i class="fa-solid fa-lock"></i> नवीन पासवर्ड / New Password</label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                                placeholder="नवीन पासवर्ड प्रविष्ट करा" required maxlength="20">
                            <button type="button" class="toggle-password" data-target="new_password"
                                aria-label="Toggle password visibility">👁️</button>
                        </div>
                        <div class="pw-reqs" id="pwReqs">
                            <span class="pw-req" id="req-len"><i class="fa-solid fa-circle"></i> 5-20 chars</span>
                            <span class="pw-req" id="req-upper"><i class="fa-solid fa-circle"></i> Uppercase</span>
                            <span class="pw-req" id="req-lower"><i class="fa-solid fa-circle"></i> Lowercase</span>
                            <span class="pw-req" id="req-num"><i class="fa-solid fa-circle"></i> Number</span>
                            <span class="pw-req" id="req-special"><i class="fa-solid fa-circle"></i> Special char</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="confirm_password"><i class="fa-solid fa-lock"></i> पासवर्ड पुष्टी करा / Confirm Password</label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
                                placeholder="पासवर्ड पुन्हा प्रविष्ट करा" required maxlength="20">
                            <button type="button" class="toggle-password" data-target="confirm_password"
                                aria-label="Toggle password visibility">👁️</button>
                        </div>
                        <div class="pw-match-msg" id="pwMatchMsg"></div>
                    </div>
                    <button type="submit" class="button" id="resetSubmitBtn"><i class="fa-solid fa-floppy-disk"></i> पासवर्ड सेव्ह करा / Save Password</button>
                </form>
            <?php endif; ?>

            <p class="small-note">पासवर्ड आठवत आहे? <a href="login.php">लॉगिन करा / Sign in</a>.</p>
        </div>
    </div>

    <script>
        // Toggle password visibility
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

        // Auto-fetch security question when username is entered
        var usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.addEventListener('blur', function () {
                var uname = usernameInput.value.trim();
                if (uname.length >= 3) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('username', uname);
                    if (!document.querySelector('.security-question-display')) {
                        window.location.href = currentUrl.toString();
                    }
                }
            });
        }

        // Password requirements checker (same logic as create_user.php)
        var pwInput = document.getElementById('new_password');
        var confirmInput = document.getElementById('confirm_password');
        var submitBtn = document.getElementById('resetSubmitBtn');
        if (pwInput) {
            function updatePwRequirements() {
                var v = pwInput.value;
                var checks = {
                    'req-len': v.length >= 5 && v.length <= 20,
                    'req-upper': /[A-Z]/.test(v),
                    'req-lower': /[a-z]/.test(v),
                    'req-num': /[0-9]/.test(v),
                    'req-special': /[^a-zA-Z0-9]/.test(v)
                };
                var allPassed = true;
                for (var id in checks) {
                    var el = document.getElementById(id);
                    if (el) {
                        var icon = el.querySelector('i');
                        if (checks[id]) {
                            el.style.color = '#059669';
                            if (icon) { icon.className = 'fa-solid fa-circle-check'; }
                        } else {
                            el.style.color = '#dc2626';
                            if (icon) { icon.className = 'fa-solid fa-circle'; }
                            allPassed = false;
                        }
                    }
                }
                updateMatchMsg();
                return allPassed;
            }

            function updateMatchMsg() {
                var matchEl = document.getElementById('pwMatchMsg');
                if (!matchEl || !confirmInput) return;
                if (confirmInput.value === '') {
                    matchEl.textContent = '';
                    matchEl.style.color = '';
                } else if (pwInput.value === confirmInput.value) {
                    matchEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Passwords match';
                    matchEl.style.color = '#059669';
                } else {
                    matchEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Passwords do not match';
                    matchEl.style.color = '#dc2626';
                }
            }

            pwInput.addEventListener('input', updatePwRequirements);
            if (confirmInput) {
                confirmInput.addEventListener('input', updateMatchMsg);
            }
        }
    </script>

    <?php
    if (file_exists(__DIR__ . '/include/footer.php')) {
        include __DIR__ . '/include/footer.php';
    }
    ?>
</main>