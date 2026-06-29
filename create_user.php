<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User form with DB save to userdata.users.
include __DIR__ . '/include/config.php';

$errors = [];
$submitted = false;
$data = [
    'name' => '',
    'designation' => '',
    'department' => '',
    'village' => '',
    'grampanchayat' => '',
    'taluka' => '',
    'mobile' => '',
    'username' => '',
    'password' => '',
    'security_question' => '',
    'security_answer' => '',
    'system_role' => '',
    'role' => ''
];

// Security questions list (used in both registration and forgot password)
$security_questions = [
    'तुमच्या आईचे माहेरचे नाव काय आहे? (What is your mother\'s maiden name?)',
    'तुमच्या पहिल्या शाळेचे नाव काय आहे? (What is the name of your first school?)',
    'तुमच्या आवडत्या शिक्षकांचे नाव काय आहे? (What is the name of your favorite teacher?)',
    'तुमच्या बालपणीच्या सर्वात चांगल्या मित्राचे नाव काय आहे? (What is the name of your childhood best friend?)',
    'तुमचे जन्मस्थान कोणते आहे? (What is your place of birth?)',
    'तुमच्या पहिल्या पाळीव प्राण्याचे नाव काय आहे? (What is the name of your first pet?)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $v) {
        $data[$k] = isset($_POST[$k]) ? trim($_POST[$k]) : '';
    }
    if ($data['system_role'] !== '') {
        $data['role'] = $data['system_role'];
    }

    // Basic validation
    if ($data['name'] === '') {
        $errors[] = 'Name is required.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $data['name'])) {
        $errors[] = 'Name should only contain alphabets and spaces.';
    }
    if ($data['username'] === '') {
        $errors[] = 'Username is required.';
    }
    if ($data['password'] === '') {
        $errors[] = 'Password is required.';
    } else {
        $pw = $data['password'];
        if (strlen($pw) < 5 || strlen($pw) > 20) {
            $errors[] = 'Password must be 5 to 20 characters.';
        }
        if (!preg_match('/[A-Z]/', $pw)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $pw)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $pw)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $pw)) {
            $errors[] = 'Password must contain at least one special character.';
        }
    }
    if ($data['security_question'] === '') {
        $errors[] = 'Security question is required.';
    }
    if ($data['security_answer'] === '') {
        $errors[] = 'Security answer is required.';
    } elseif (strlen($data['security_answer']) < 2) {
        $errors[] = 'Security answer must be at least 2 characters.';
    }
    if ($data['mobile'] !== '') {
        if (!preg_match('/^[0-9]{10}$/', $data['mobile'])) {
            $errors[] = 'Mobile number must be exactly 10 digits.';
        } elseif (!preg_match('/^[6789]/', $data['mobile'])) {
            $errors[] = 'Mobile number must start with 6, 7, 8, or 9.';
        }
    }
    if ($data['mobile'] === '') {
        $errors[] = 'Mobile number is required.';
    }

    if (empty($errors)) {
        $mysqli = db_connect();

        // Check if mobile number is already registered
        $check_stmt = $mysqli->prepare('SELECT COUNT(*) FROM users WHERE mobile_no = ?');
        if ($check_stmt) {
            $check_stmt->bind_param('s', $data['mobile']);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();
            if ($count > 0) {
                $errors[] = 'Mobile number is already registered.';
                $mobile_duplicate_error = true;
            }
        } else {
            $errors[] = 'DB unique check prepare failed: ' . $mysqli->error;
        }

        // Check if username is already registered
        $check_user_stmt = $mysqli->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        if ($check_user_stmt) {
            $check_user_stmt->bind_param('s', $data['username']);
            $check_user_stmt->execute();
            $check_user_stmt->bind_result($user_count);
            $check_user_stmt->fetch();
            $check_user_stmt->close();
            if ($user_count > 0) {
                $errors[] = 'Username is already registered.';
                $username_duplicate_error = true;
            }
        } else {
            $errors[] = 'DB unique check prepare failed: ' . $mysqli->error;
        }

        if (empty($errors)) {
            // Hash the security answer for secure storage (case-insensitive: store lowercase)
            $hashed_security_answer = password_hash(mb_strtolower(trim($data['security_answer']), 'UTF-8'), PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare(
                'INSERT INTO users (name, designation, department, village, grampanchayat, taluka, mobile_no, username, password, security_question, security_answer, system_role, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if (!$stmt) {
                $errors[] = 'DB prepare failed: ' . $mysqli->error;
            } else {
                $stmt->bind_param(
                    'sssssssssssss',
                    $data['name'],
                    $data['designation'],
                    $data['department'],
                    $data['village'],
                    $data['grampanchayat'],
                    $data['taluka'],
                    $data['mobile'],
                    $data['username'],
                    $data['password'],
                    $data['security_question'],
                    $hashed_security_answer,
                    $data['system_role'],
                    $data['role']
                );

                if ($stmt->execute()) {
                    $submitted = true;
                    $_SESSION['success_message'] = 'नवीन खाते यशस्वीरित्या तयार केले आहे! कृपया लॉगिन करा. (New account created successfully! Please login.)';
                    header("Location: landingpage.php");
                    exit;
                } else {
                    $errors[] = 'DB insert failed: ' . $stmt->error;
                }

                $stmt->close();
            }
        }

        $mysqli->close();
    }
}

// Include header *after* redirect check to prevent header already sent issue
include __DIR__ . '/include/header.php';


// ---------- DATA ARRAYS ----------
$designations = [
    'गट विकास अधिकारी',
    'तालुका आरोग्य अधिकारी',
    'गटशिक्षणाधिकारी',
    'बालविकास प्रकल्प अधिकारी',
    'विस्तार अधिकारी (कृषी)',
    'पशुवैद्यकीय अधिकारी',
    'उप. मुख्य कार्यकारी अधिकारी (सा)',
    'उप. मुख्य कार्यकारी अधिकारी (पं)',
    'जिल्हा आरोग्य अधिकारी',
    'प्रकल्प संचालक (जि.ग्रा.वि.य.)',
    'उप. मुख्य कार्यकारी अधिकारी (महिला आणि बाल विकास)',
    'कृषी विकास अधिकारी',
    'शिक्षणाधिकारी (प्राथमिक)',
    'शिक्षणाधिकारी (माध्यमिक)',
    'जिल्हा समाजकल्याण अधिकारी',
    'जिल्हा पशुसंवर्धन अधिकारी',
    'तालुका अभियान व्यवस्थापक'
];

$departments = [
    'पंचायत समिती',
    'आरोग्य विभाग',
    'शिक्षण विभाग',
    'महिला व बालकल्याण विभाग',
    'कृषी विभाग',
    'पशुसंवर्धन विभाग',
    'सामान्य प्रशासन विभाग',
    'ग्रामपंचायत विभाग',
    'जिल्हा ग्रामीण विकास यंत्रणा',
    'शिक्षण विभाग (प्राथमिक)',
    'शिक्षण विभाग (माध्यमिक)',
    'समाज कल्याण विभाग'
];

require_once __DIR__ . '/include/location_mapping.php';

$talukas = array_keys($location_mapping);
$villages = [];
$grampanchayats = [];
foreach ($location_mapping as $t => $vs) {
    foreach ($vs as $v => $gps) {
        $villages[] = $v;
        foreach ($gps as $g) {
            $grampanchayats[] = $g;
        }
    }
}
$villages = array_values(array_unique($villages));
sort($villages);
$grampanchayats = array_values(array_unique($grampanchayats));
sort($grampanchayats);

$temp_conn = db_connect();
$system_roles = [];
if ($temp_conn) {
    $res = $temp_conn->query("SELECT DISTINCT system_role FROM users WHERE system_role IS NOT NULL AND system_role != '' ORDER BY system_role ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $system_roles[] = trim($row['system_role']);
        }
    }
    $temp_conn->close();
}
if (empty($system_roles)) {
    $system_roles = ['अंगणवाडी सेविका', 'ग्रामपंचायत अधिकारी', 'शिक्षक', 'THO', 'BDO', 'HoD', 'CEO'];
}
?>

<style>
    /* ===== PREMIUM CREATE USER FORM ===== */
    .cu-card {
        max-width: 940px;
        margin: 2rem auto 3rem;
        background: var(--bg-card);
        padding: 0;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        font-family: var(--font-body);
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .cu-card:hover {
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
    }

    .cu-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light), #6366f1);
        padding: 2rem 2rem 1.75rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .cu-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
        animation: cu-shimmer 8s ease-in-out infinite;
    }

    @keyframes cu-shimmer {

        0%,
        100% {
            transform: translate(0, 0);
        }

        50% {
            transform: translate(25%, 25%);
        }
    }

    .cu-header h1 {
        color: #fff;
        font-family: var(--font-heading);
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        margin: 0 0 0.35rem;
        position: relative;
        z-index: 1;
    }

    .cu-header p {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.95rem;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .cu-progress {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.25rem 2rem 0;
        background: var(--bg-card);
    }

    .cu-step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 600;
        font-family: var(--font-heading);
        color: var(--text-muted);
        background: var(--bg-hover);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .cu-step.active {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary-light);
        border-color: var(--primary-light);
    }

    .cu-step.completed {
        background: rgba(5, 150, 105, 0.1);
        color: var(--success-color);
        border-color: var(--success-color);
    }

    .cu-step i {
        font-size: 0.85rem;
    }

    .cu-body {
        padding: 1.5rem 2rem 2rem;
    }

    .cu-section {
        margin-bottom: 2rem;
        padding-bottom: 1.75rem;
        border-bottom: 1px dashed var(--border-color);
    }

    .cu-section:last-of-type {
        border-bottom: none;
        margin-bottom: 1rem;
        padding-bottom: 0;
    }

    .cu-section-title {
        font-family: var(--font-heading);
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .cu-section-title i {
        color: var(--primary-light);
        font-size: 1.05rem;
        width: 1.5rem;
        text-align: center;
    }

    .cu-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1rem;
    }

    .cu-row.full {
        grid-template-columns: 1fr;
    }

    .cu-field {
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .cu-field label {
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: color 0.2s;
    }

    .cu-field label .req {
        color: var(--danger-color);
        font-weight: 700;
        font-size: 0.9rem;
    }

    .cu-field:focus-within label {
        color: var(--primary-light);
    }

    .cu-field label i {
        color: var(--text-muted);
        font-size: 0.85rem;
        transition: color 0.2s;
    }

    .cu-field:focus-within label i {
        color: var(--primary-light);
    }

    .cu-input,
    .cu-select {
        width: 100%;
        height: 2.75rem;
        padding: 0 1rem;
        border: 1.5px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        background: var(--bg-input);
        color: var(--text-primary);
        box-sizing: border-box;
        font-family: var(--font-body);
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .cu-input:focus,
    .cu-select:focus {
        border-color: var(--primary-light);
        background: var(--bg-card);
        box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
        outline: none;
    }

    .cu-input.invalid,
    .cu-select.invalid {
        border-color: var(--danger-color) !important;
        box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1) !important;
    }

    .cu-input.valid,
    .cu-select.valid {
        border-color: var(--success-color) !important;
    }

    .cu-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 1rem;
        padding-right: 2.5rem;
    }

    body.dark-theme .cu-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23cbd5e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    }

    .cu-msg {
        font-size: 0.78rem;
        margin-top: 0.3rem;
        min-height: 1.1rem;
        font-family: var(--font-body);
        display: flex;
        align-items: center;
        gap: 0.3rem;
        opacity: 0;
        transform: translateY(-4px);
        transition: opacity 0.25s, transform 0.25s;
    }

    .cu-msg.show {
        opacity: 1;
        transform: translateY(0);
    }

    .cu-msg.error {
        color: var(--danger-color);
    }

    .cu-msg.ok {
        color: var(--success-color);
    }

    .cu-pw-wrap {
        position: relative;
    }

    .cu-pw-wrap .cu-input {
        padding-right: 3rem;
    }

    .cu-pw-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 1.15rem;
        padding: 0;
        display: flex;
        align-items: center;
        transition: color 0.2s;
    }

    .cu-pw-toggle:hover {
        color: var(--primary-light);
    }

    .cu-pw-toggle:focus {
        outline: none;
    }

    .cu-strength {
        height: 4px;
        border-radius: 2px;
        background: var(--border-color);
        margin-top: 0.4rem;
        overflow: hidden;
    }

    .cu-strength-bar {
        height: 100%;
        width: 0;
        border-radius: 2px;
        transition: width 0.4s ease, background 0.4s ease;
    }

    .cu-pw-reqs {
        margin-top: 0.4rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 0.75rem;
    }

    .cu-pw-req {
        font-size: 0.72rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.2s;
    }

    .cu-pw-req.pass {
        color: var(--success-color);
    }

    .cu-pw-req.fail {
        color: var(--danger-color);
    }

    .cu-pw-req i {
        font-size: 0.7rem;
    }

    .cu-counter {
        font-size: 0.72rem;
        color: var(--text-muted);
        text-align: right;
        margin-top: 0.15rem;
    }

    .cu-actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
    }

    .cu-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: #fff;
        padding: 0.75rem 2.5rem;
        border-radius: var(--radius-md);
        border: none;
        cursor: pointer;
        font-weight: 700;
        font-family: var(--font-heading);
        font-size: 1rem;
        box-shadow: 0 4px 14px rgba(var(--primary-rgb), 0.3);
        transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
        letter-spacing: 0.01em;
    }

    .cu-btn:hover {
        filter: brightness(1.08);
        box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.4);
        transform: translateY(-2px);
    }

    .cu-btn:active {
        transform: translateY(1px);
    }

    .cu-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        filter: grayscale(0.3);
        transform: none !important;
    }

    .cu-btn-secondary {
        background: var(--bg-hover);
        color: var(--text-primary);
        box-shadow: none;
        border: 1px solid var(--border-color);
        text-decoration: none;
    }

    .cu-btn-secondary:hover {
        background: var(--border-color);
        box-shadow: none;
        transform: translateY(-1px);
    }

    .cu-alert {
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .cu-alert i {
        font-size: 1.15rem;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }

    .cu-alert-danger {
        background: rgba(220, 38, 38, 0.07);
        border: 1px solid var(--danger-color);
        color: var(--danger-color);
    }

    .cu-alert-success {
        background: rgba(5, 150, 105, 0.07);
        border: 1px solid var(--success-color);
        color: var(--success-color);
    }

    .cu-alert ul {
        margin: 0.35rem 0 0;
        padding-left: 1.25rem;
    }

    .cu-alert li {
        margin-top: 0.15rem;
    }

    .cu-summary {
        margin-top: 1.25rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .cu-summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.65rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
    }

    .cu-summary-row:last-child {
        border-bottom: none;
    }

    .cu-summary-row:nth-child(odd) {
        background: var(--bg-hover);
    }

    .cu-summary-row strong {
        color: var(--text-secondary);
        font-family: var(--font-heading);
        font-weight: 600;
    }

    .cu-summary-row span {
        color: var(--text-primary);
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .cu-card {
            margin: 1rem;
        }

        .cu-header {
            padding: 1.5rem 1.25rem 1.25rem;
        }

        .cu-header h1 {
            font-size: 1.35rem;
        }

        .cu-body {
            padding: 1.25rem;
        }

        .cu-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .cu-progress {
            flex-wrap: wrap;
            padding: 1rem 1rem 0;
            gap: 0.35rem;
        }

        .cu-step {
            font-size: 0.72rem;
            padding: 0.35rem 0.65rem;
        }

        .cu-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .cu-btn {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .cu-card {
            margin: 0.5rem;
            border-radius: var(--radius-md);
        }

        .cu-header h1 {
            font-size: 1.2rem;
        }

        .cu-header p {
            font-size: 0.85rem;
        }

        .cu-body {
            padding: 1rem;
        }

        .cu-progress {
            padding: 1rem 0.5rem 0;
            gap: 0.25rem;
        }

        .cu-step {
            font-size: 0.68rem;
            padding: 0.35rem 0.5rem;
            gap: 0.3rem;
            flex: 1;
            justify-content: center;
            text-align: center;
        }
    }
</style>



<main class="main-content">
    <div class="cu-card">
        <div class="cu-header">
            <h1><i class="fa-solid fa-user-plus"></i> Create User / Officer</h1>
            <p>Fill in the details below to register a new user in the system</p>
        </div>
        <div class="cu-progress">
            <div class="cu-step active" data-section="personal"><i class="fa-solid fa-user"></i> Personal</div>
            <div class="cu-step" data-section="location"><i class="fa-solid fa-map-marker-alt"></i> Location</div>
            <div class="cu-step" data-section="credentials"><i class="fa-solid fa-lock"></i> Credentials</div>
        </div>

        <?php if (!empty($errors)): ?>
            <?php
            $display_errors = array_filter($errors, function ($e) {
                return $e !== 'Mobile number is already registered.' && $e !== 'Username is already registered.';
            });
            ?>
            <?php if (!empty($display_errors)): ?>
                <div class="cu-body" style="padding-bottom:0">
                    <div class="cu-alert cu-alert-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul><?php foreach ($display_errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($mobile_duplicate_error) && $mobile_duplicate_error): ?>
            <!-- SweetAlert2 for duplicate mobile -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: 'Error!',
                        text: 'The mobile number is already registered.',
                        icon: 'error',
                        confirmButtonColor: '#dc2626',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if (isset($username_duplicate_error) && $username_duplicate_error): ?>
            <!-- SweetAlert2 for duplicate username -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Username is already registered. Please choose a different username.',
                        icon: 'error',
                        confirmButtonColor: '#dc2626',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if ($submitted): ?>
            <!-- SweetAlert2 -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: 'Success!',
                        text: <?php echo json_encode($data['name'] . " is successfully Registered", JSON_UNESCAPED_UNICODE); ?>,
                        icon: 'success',
                        confirmButtonColor: '#2563eb',
                        confirmButtonText: 'OK'
                    }).then(function () {
                        window.location.href = "create_user.php";
                    });
                });
            </script>
        <?php else: ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="createUserForm"
                novalidate>
                <div class="cu-body">

                    <!-- Section 1: Personal Details -->
                    <div class="cu-section" id="sec-personal">
                        <div class="cu-section-title"><i class="fa-solid fa-id-card"></i> Personal Details</div>
                        <div class="cu-row full">
                            <div class="cu-field">
                                <label for="name"><i class="fa-solid fa-user-tie"></i> Full Name <span
                                        class="req">*</span></label>
                                <input class="cu-input" id="name" name="name" type="text" placeholder="Enter full name"
                                    value="<?php echo htmlspecialchars($data['name']); ?>" />
                                <div class="cu-msg" id="msg-name"></div>
                            </div>
                        </div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="department"><i class="fa-solid fa-building-user"></i> Department</label>
                                <select class="cu-select" id="department" name="department">
                                    <option value="">-- निवडा विभाग --</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $data['department'] === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cu-field">
                                <label for="designation"><i class="fa-solid fa-briefcase"></i> Designation</label>
                                <select class="cu-select" id="designation" name="designation">
                                    <option value="">-- निवडा पदवी --</option>
                                    <?php foreach ($designations as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $data['designation'] === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Location Details -->
                    <div class="cu-section" id="sec-location">
                        <div class="cu-section-title"><i class="fa-solid fa-location-dot"></i> Location Details</div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="village"><i class="fa-solid fa-tree-city"></i> Village</label>
                                <select class="cu-select" id="village" name="village">
                                    <option value="">-- निवडा गांव --</option>
                                    <?php foreach ($villages as $v): ?>
                                        <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $data['village'] === $v ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cu-field">
                                <label for="grampanchayat"><i class="fa-solid fa-landmark"></i> Grampanchayat</label>
                                <select class="cu-select" id="grampanchayat" name="grampanchayat">
                                    <option value="">-- निवडा ग्रामपंचायत --</option>
                                    <?php foreach ($grampanchayats as $g): ?>
                                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $data['grampanchayat'] === $g ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($g); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="taluka"><i class="fa-solid fa-map-location-dot"></i> Taluka</label>
                                <select class="cu-select" id="taluka" name="taluka">
                                    <option value="">-- निवडा तालुका --</option>
                                    <?php foreach ($talukas as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $data['taluka'] === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cu-field">
                                <label for="mobile"><i class="fa-solid fa-phone"></i> Mobile No <span
                                        class="req">*</span></label>
                                <input class="cu-input" id="mobile" name="mobile" type="tel" inputmode="numeric"
                                    maxlength="10" placeholder="Enter 10 digit mobile number"
                                    value="<?php echo htmlspecialchars($data['mobile']); ?>" />
                                <div class="cu-msg" id="msg-mobile"></div>
                                <div class="cu-counter" id="counter-mobile">0 / 10</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Credentials -->
                    <div class="cu-section" id="sec-credentials">
                        <div class="cu-section-title"><i class="fa-solid fa-shield-halved"></i> Login Credentials</div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="username"><i class="fa-solid fa-user-gear"></i> Username <span
                                        class="req">*</span></label>
                                <input class="cu-input" id="username" name="username" type="text"
                                    placeholder="Choose a username"
                                    value="<?php echo htmlspecialchars($data['username']); ?>" />
                                <div class="cu-msg" id="msg-username"></div>
                            </div>
                            <div class="cu-field">
                                <label for="password"><i class="fa-solid fa-key"></i> Password <span
                                        class="req">*</span></label>
                                <div class="cu-pw-wrap">
                                    <input class="cu-input" id="password" name="password" type="password"
                                        placeholder="Create a strong password" maxlength="20" value="" />
                                    <button type="button" class="cu-pw-toggle" id="pwToggle" aria-label="Show password"><i
                                            class="fa-solid fa-eye"></i></button>
                                </div>
                                <div class="cu-strength">
                                    <div class="cu-strength-bar" id="pwStrengthBar"></div>
                                </div>
                                <div class="cu-pw-reqs" id="pwReqs">
                                    <span class="cu-pw-req" id="req-len"><i class="fa-solid fa-circle"></i> 5-20
                                        chars</span>
                                    <span class="cu-pw-req" id="req-upper"><i class="fa-solid fa-circle"></i>
                                        Uppercase</span>
                                    <span class="cu-pw-req" id="req-lower"><i class="fa-solid fa-circle"></i>
                                        Lowercase</span>
                                    <span class="cu-pw-req" id="req-num"><i class="fa-solid fa-circle"></i> Number</span>
                                    <span class="cu-pw-req" id="req-special"><i class="fa-solid fa-circle"></i> Special
                                        char</span>
                                </div>
                                <div class="cu-msg" id="msg-password"></div>
                            </div>
                        </div>

                        <!-- Security Question Section -->
                        <h3 class="cu-section-title"><i class="fa-solid fa-shield-halved"></i> सुरक्षा प्रश्न / Security Question</h3>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="security_question"><i class="fa-solid fa-question-circle"></i> सुरक्षा प्रश्न निवडा (Select Security Question) <span class="req">*</span></label>
                                <select class="cu-select" id="security_question" name="security_question" required>
                                    <option value="">-- प्रश्न निवडा / Select Question --</option>
                                    <?php foreach ($security_questions as $q): ?>
                                        <option value="<?php echo htmlspecialchars($q); ?>" <?php echo $data['security_question'] === $q ? 'selected' : ''; ?>><?php echo htmlspecialchars($q); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="cu-msg" id="msg-security_question"></div>
                            </div>
                        </div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="security_answer"><i class="fa-solid fa-key"></i> उत्तर (Answer) <span class="req">*</span></label>
                                <input class="cu-input" id="security_answer" name="security_answer" type="text"
                                    placeholder="तुमचे उत्तर येथे टाका / Enter your answer here"
                                    value="<?php echo htmlspecialchars($data['security_answer']); ?>" required />
                                <div class="cu-msg" id="msg-security_answer"></div>
                            </div>
                        </div>
                        <div class="cu-row">
                            <div class="cu-field">
                                <label for="system_role"><i class="fa-solid fa-user-shield"></i> System Role</label>
                                <select class="cu-select" id="system_role" name="system_role">
                                    <option value="">-- Select Role --</option>
                                    <?php foreach ($system_roles as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $data['system_role'] === $r ? 'selected' : ''; ?>><?php echo htmlspecialchars($r); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cu-field">
                                <label for="role"><i class="fa-solid fa-id-badge"></i> Role</label>
                                <input class="cu-input" id="role" name="role" type="text"
                                    placeholder="e.g. District Officer"
                                    value="<?php echo htmlspecialchars($data['role']); ?>" <?php echo ($data['system_role'] !== '') ? 'readonly' : ''; ?> />
                            </div>
                        </div>
                    </div>

                    <div class="cu-actions">
                        <button type="reset" class="cu-btn cu-btn-secondary" onclick="resetValidation()"><i
                                class="fa-solid fa-rotate-left"></i> Reset</button>
                        <button type="submit" class="cu-btn" id="submitBtn"><i class="fa-solid fa-floppy-disk"></i> Save
                            User</button>
                    </div>

                    <div style="text-align:center;margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                        <span style="font-size:0.92rem;color:var(--text-secondary);">Already have an account?</span>
                        <a href="login.php" id="btn-back-to-login"
                           style="display:inline-flex;align-items:center;gap:0.4rem;margin-left:0.5rem;font-size:0.95rem;font-weight:700;color:var(--primary);text-decoration:none;padding:0.35rem 0.9rem;border:2px solid var(--primary);border-radius:8px;transition:background 0.2s,color 0.2s;"
                           onmouseover="this.style.background='var(--primary)';this.style.color='#fff';"
                           onmouseout="this.style.background='transparent';this.style.color='var(--primary)';">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                        </a>
                    </div>
                </div>
            </form>

            <script>
                (function () {
                    'use strict';
                    const form = document.getElementById('createUserForm');
                    if (!form) return;

                    const rules = {
                        name: {
                            validate(v) {
                                if (!v.trim()) return 'Full name is required';
                                if (v.trim().length < 2) return 'Name must be at least 2 characters';
                                if (!/^[\p{L}\s]+$/u.test(v.trim())) return 'Name should only contain alphabets and spaces';
                                return '';
                            }
                        },
                        mobile: {
                            validate(v) {
                                if (!v) return 'Mobile number is required';
                                if (!/^\d+$/.test(v)) return 'Only digits are allowed';
                                var first = v.charAt(0);
                                if (first !== '6' && first !== '7' && first !== '8' && first !== '9') return 'Mobile number must start with 6, 7, 8, or 9';
                                if (v.length < 10) return 'Must be exactly 10 digits (' + v.length + '/10)';
                                if (v.length > 10) return 'Cannot exceed 10 digits';
                                return '';
                            }
                        },
                        username: {
                            validate(v) {
                                if (!v.trim()) return 'Username is required';
                                if (v.trim().length < 3) return 'Username must be at least 3 characters';
                                if (!/^[a-zA-Z0-9._@-]+$/.test(v.trim())) return 'Only letters, digits, . _ @ - allowed';
                                return '';
                            }
                        },
                        password: {
                            validate(v) {
                                if (!v) return 'Password is required';
                                var missing = [];
                                if (v.length < 5 || v.length > 20) missing.push('5-20 characters');
                                if (!/[A-Z]/.test(v)) missing.push('one uppercase');
                                if (!/[a-z]/.test(v)) missing.push('one lowercase');
                                if (!/[0-9]/.test(v)) missing.push('one number');
                                if (!/[^a-zA-Z0-9]/.test(v)) missing.push('one special character');
                                if (missing.length) return 'Missing: ' + missing.join(', ');
                                return '';
                            }
                        },
                        security_question: {
                            validate(v) {
                                if (!v) return 'Security question is required';
                                return '';
                            }
                        },
                        security_answer: {
                            validate(v) {
                                if (!v.trim()) return 'Security answer is required';
                                if (v.trim().length < 2) return 'Answer must be at least 2 characters';
                                return '';
                            }
                        }
                    };

                    function showMsg(id, msg, type) {
                        var el = document.getElementById('msg-' + id);
                        if (!el) return;
                        el.textContent = '';
                        if (msg) {
                            var icon = document.createElement('i');
                            icon.className = type === 'error' ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-circle-check';
                            el.appendChild(icon);
                            el.appendChild(document.createTextNode(' ' + msg));
                        }
                        el.className = 'cu-msg show ' + type;
                    }
                    function clearMsg(id) {
                        var el = document.getElementById('msg-' + id);
                        if (!el) return;
                        el.className = 'cu-msg';
                        el.textContent = '';
                    }
                    function validateField(field) {
                        var name = field.name, rule = rules[name];
                        if (!rule) return true;
                        var err = rule.validate(field.value);
                        if (err) { field.classList.remove('valid'); field.classList.add('invalid'); showMsg(name, err, 'error'); return false; }
                        else { field.classList.remove('invalid'); field.classList.add('valid'); showMsg(name, 'Looks good!', 'ok'); return true; }
                    }

                    Object.keys(rules).forEach(function (name) {
                        var field = form.querySelector('[name="' + name + '"]');
                        if (!field) return;
                        field.addEventListener('input', function () {
                            if (this.value) validateField(this);
                            else { this.classList.remove('valid', 'invalid'); clearMsg(name); }
                            updateProgress();
                            if (name === 'password') updatePwRequirements();
                        });
                        field.addEventListener('blur', function () { if (this.value) validateField(this); });
                    });

                    // Name: alphabets and spaces only (including Marathi letters)
                    var nameInput = document.getElementById('name');
                    if (nameInput) {
                        nameInput.addEventListener('input', function () {
                            this.value = this.value.replace(/[^\p{L}\s]/gu, '');
                        });
                    }

                    // Mobile: digits only
                    var mobileInput = document.getElementById('mobile');
                    if (mobileInput) {
                        mobileInput.addEventListener('input', function () {
                            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                            var counter = document.getElementById('counter-mobile');
                            if (counter) counter.textContent = this.value.length + ' / 10';
                        });
                    }

                    // Replicate System Role to Role
                    var systemRoleSelect = document.getElementById('system_role');
                    var roleInput = document.getElementById('role');
                    if (systemRoleSelect && roleInput) {
                        systemRoleSelect.addEventListener('change', function () {
                            roleInput.value = this.value;
                            if (this.value !== '') {
                                roleInput.readOnly = true;
                            } else {
                                roleInput.readOnly = false;
                            }
                            updateProgress();
                        });
                    }

                    // Password toggle with 2-second auto-hide
                    var pwToggle = document.getElementById('pwToggle');
                    var pwInput = document.getElementById('password');
                    var pwTimeout;
                    if (pwToggle && pwInput) {
                        pwToggle.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (pwTimeout) {
                                clearTimeout(pwTimeout);
                                pwTimeout = null;
                            }
                            var isHidden = pwInput.type === 'password';
                            if (isHidden) {
                                pwInput.type = 'text';
                                this.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
                                pwTimeout = setTimeout(function () {
                                    pwInput.type = 'password';
                                    pwToggle.innerHTML = '<i class="fa-solid fa-eye"></i>';
                                    pwTimeout = null;
                                }, 2000);
                            } else {
                                pwInput.type = 'password';
                                this.innerHTML = '<i class="fa-solid fa-eye"></i>';
                            }
                        });
                    }

                    // Password requirements checker
                    function updatePwRequirements() {
                        if (!pwInput) return;
                        var val = pwInput.value;
                        var checks = {
                            'req-len': val.length >= 5 && val.length <= 20,
                            'req-upper': /[A-Z]/.test(val),
                            'req-lower': /[a-z]/.test(val),
                            'req-num': /[0-9]/.test(val),
                            'req-special': /[^a-zA-Z0-9]/.test(val)
                        };
                        var score = 0;
                        Object.keys(checks).forEach(function (id) {
                            var el = document.getElementById(id);
                            if (!el) return;
                            if (checks[id]) {
                                el.classList.add('pass'); el.classList.remove('fail');
                                el.querySelector('i').className = 'fa-solid fa-circle-check';
                                score++;
                            } else if (val.length > 0) {
                                el.classList.add('fail'); el.classList.remove('pass');
                                el.querySelector('i').className = 'fa-solid fa-circle-xmark';
                            } else {
                                el.classList.remove('pass', 'fail');
                                el.querySelector('i').className = 'fa-solid fa-circle';
                            }
                        });
                        // Strength bar
                        var bar = document.getElementById('pwStrengthBar');
                        if (bar) {
                            var widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
                            var colors = ['transparent', '#dc2626', '#f59e0b', '#f59e0b', '#22c55e', '#059669'];
                            bar.style.width = widths[score] || '0%';
                            bar.style.background = colors[score] || 'transparent';
                        }
                    }

                    // Progress indicators
                    function updateProgress() {
                        var steps = document.querySelectorAll('.cu-step');
                        var sections = {
                            personal: { fields: ['name'], optional: ['designation', 'department'] },
                            location: { fields: [], optional: ['village', 'grampanchayat', 'taluka', 'mobile'] },
                            credentials: { fields: ['username', 'password', 'security_question', 'security_answer'], optional: ['system_role', 'role'] }
                        };
                        steps.forEach(function (step) {
                            var sName = step.getAttribute('data-section');
                            var sec = sections[sName]; if (!sec) return;
                            var all = sec.fields.concat(sec.optional), filled = 0;
                            all.forEach(function (f) { var el = form.querySelector('[name="' + f + '"]'); if (el && el.value.trim()) filled++; });
                            step.classList.remove('active', 'completed');
                            if (filled === all.length && all.length > 0) step.classList.add('completed');
                            else if (filled > 0) step.classList.add('active');
                        });
                    }

                    // Step click scroll
                    document.querySelectorAll('.cu-step').forEach(function (step) {
                        step.addEventListener('click', function () {
                            var target = document.getElementById('sec-' + this.getAttribute('data-section'));
                            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    });

                    // Form submit
                    form.addEventListener('submit', function (e) {
                        var allValid = true;
                        Object.keys(rules).forEach(function (name) {
                            var field = form.querySelector('[name="' + name + '"]');
                            if (field && !validateField(field)) allValid = false;
                        });
                        if (!allValid) {
                            e.preventDefault();
                            var first = form.querySelector('.invalid');
                            if (first) { first.focus(); first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        }
                    });

                    // Reset
                    window.resetValidation = function () {
                        Object.keys(rules).forEach(function (name) {
                            var field = form.querySelector('[name="' + name + '"]');
                            if (field) { field.classList.remove('valid', 'invalid'); clearMsg(name); }
                        });
                        var bar = document.getElementById('pwStrengthBar');
                        if (bar) { bar.style.width = '0%'; bar.style.background = 'transparent'; }
                        var counter = document.getElementById('counter-mobile');
                        if (counter) counter.textContent = '0 / 10';
                        document.querySelectorAll('.cu-step').forEach(function (s) { s.classList.remove('active', 'completed'); });
                        document.querySelectorAll('.cu-pw-req').forEach(function (r) { r.classList.remove('pass', 'fail'); r.querySelector('i').className = 'fa-solid fa-circle'; });
                        setTimeout(filterDesignations, 0);
                        setTimeout(function () {
                            if (systemRoleSelect && roleInput) {
                                roleInput.readOnly = (systemRoleSelect.value !== '');
                            }
                        }, 0);
                    };

                    // Dynamic department/designation filtering
                    var departmentSelect = document.getElementById('department');
                    var designationSelect = document.getElementById('designation');
                    var originalDesignations = <?php echo json_encode($designations, JSON_UNESCAPED_UNICODE); ?>;
                    var deptDesignationMap = {
                        'पंचायत समिती': ['गट विकास अधिकारी'],
                        'आरोग्य विभाग': ['गट विकास अधिकारी', 'तालुका आरोग्य अधिकारी', 'जिल्ха आरोग्य अधिकारी'],
                        'शिक्षण विभाग': ['गट विकास अधिकारी', 'गटशिक्षणाधिकारी'],
                        'महिला व बालकल्याण विभाग': ['गट विकास अधिकारी', 'बालविकास प्रकल्प अधिकारी', 'उप. मुख्य कार्यकारी अधिकारी (महिला आणि बाल विकास)'],
                        'कृषी विभाग': ['गट विकास अधिकारी', 'विस्तार अधिकारी (कृषी)', 'कृषी विकास अधिकारी'],
                        'पशुसंवर्धन विभाग': ['गट विकास अधिकारी', 'पशुवैद्यकीय अधिकारी', 'जिल्हा पशुसंवर्धन अधिकारी'],
                        'सामान्य प्रशासन विभाग': ['गट विकास अधिकारी', 'उप. मुख्य कार्यकारी अधिकारी (सा)'],
                        'ग्रामपंचायत विभाग': ['गट विकास अधिकारी', 'उप. मुख्य कार्यकारी अधिकारी (पं)'],
                        'जिल्हा ग्रामीण विकास यंत्रणा': ['गट विकास अधिकारी', 'प्रकल्प संचालक (जि.ग्रा.वि.य.)', 'तालुका अभियान व्यवस्थापक'],
                        'शिक्षण विभाग (प्राथमिक)': ['गट विकास अधिकारी', 'शिक्षणाधिकारी (प्राथमिक)'],
                        'शिक्षण विभाग (माध्यमिक)': ['गट विकास अधिकारी', 'शिक्षणाधिकारी (माध्यमिक)'],
                        'समाज कल्याण विभाग': ['गट विकास अधिकारी', 'जिल्हा समाजकल्याण अधिकारी']
                    };

                    function filterDesignations() {
                        if (!departmentSelect || !designationSelect) return;
                        var selectedDept = departmentSelect.value;
                        var currentDesignation = designationSelect.value;

                        // Clear current options except the first one
                        designationSelect.innerHTML = '<option value="">-- निवडा पदवी --</option>';

                        var allowed = deptDesignationMap[selectedDept];
                        if (selectedDept && allowed) {
                            allowed.forEach(function (d) {
                                var opt = document.createElement('option');
                                opt.value = d;
                                opt.textContent = d;
                                if (d === currentDesignation) {
                                    opt.selected = true;
                                }
                                designationSelect.appendChild(opt);
                            });
                        } else {
                            originalDesignations.forEach(function (d) {
                                var opt = document.createElement('option');
                                opt.value = d;
                                opt.textContent = d;
                                if (d === currentDesignation) {
                                    opt.selected = true;
                                }
                                designationSelect.appendChild(opt);
                            });
                        }
                    }

                    if (departmentSelect) {
                        departmentSelect.addEventListener('change', filterDesignations);
                    }
                    filterDesignations();

                    updateProgress();
                    if (mobileInput) { var c = document.getElementById('counter-mobile'); if (c) c.textContent = mobileInput.value.length + ' / 10'; }
                })();
            </script>

        <?php endif; ?>
    </div>

    <?php
    if (file_exists(__DIR__ . '/include/footer.php'))
        include __DIR__ . '/include/footer.php';
    ?>
</main>
