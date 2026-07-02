<?php
session_start();

$error = '';
if (isset($_GET['error'])) {
    $errorCode = $_GET['error'];
    if ($errorCode === 'required') {
        $error = 'Please enter both username and password.';
    } elseif ($errorCode === 'invalid') {
        $error = 'Invalid username or password.';
    } elseif ($errorCode === 'notfound') {
        $error = 'User not found.';
    } else {
        $error = htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8');
    }
}
?>
<?php
$active_page = 'login';
$page_title = 'Login';
$page_description = 'Login to the Zilla Parishad Hingoli Inter Department Portal.';

// include __DIR__ . '/include/header.php';
?>
<!-- Page-specific styles (Bootstrap and custom overrides) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    :root {
        --card-width: 420px;
        --card-radius: 20px;
        --primary: #005af0;
    }

    body {
        background: url('assets/login_background.png?v=2') no-repeat center center fixed !important;
        background-size: 100% 100% !important;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    .main-content {
        min-height: calc(100vh - var(--header-height));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 21rem;
        background: transparent !important;
        transition: background var(--transition-normal);
    }

    @media (min-width: 992px) {
        .login-card {
            margin-left: 1% !important;
            margin-right: auto !important;
        }
    }

    .login-card {
        width: 100%;
        max-width: var(--card-width);
        background: rgba(15, 23, 42, 0.18) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-radius: var(--card-radius);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2) !important;
        padding: 1.8rem 2rem;
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        margin-left: auto;
        margin-right: auto;
        transition: all 0.3s ease;
    }

    .portal-title {
        font-family: var(--font-heading), sans-serif;
        font-weight: 700;
        font-size: 1.6rem;
        text-align: center;
        margin-bottom: 0.2rem;
        color: #ffffff;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
    }

    .portal-sub {
        font-size: 0.95rem;
        text-align: center;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #f1f5f9;
        margin-bottom: 0.25rem;
    }

    .input-group {
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.08) !important;
        transition: all 0.3s ease;
    }

    .input-group:focus-within {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.25) !important;
    }

    .input-group-text {
        background: transparent;
        border: none;
        color: #38bdf8 !important;
        font-size: 1.1rem;
        padding-left: 1rem;
        padding-right: 0.5rem;
    }

    .form-control {
        background: transparent !important;
        border: none !important;
        height: 50px;
        font-size: 0.95rem;
        color: #ffffff !important;
        box-shadow: none !important;
        padding-left: 0.5rem;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.45) !important;
    }

    #togglePassword {
        background: transparent;
        border: none;
        color: #38bdf8;
        padding-right: 1rem;
        cursor: pointer;
    }

    .btn-login {
        background: linear-gradient(135deg, #005af0, #00c6ff) !important;
        border: none !important;
        font-weight: 600;
        height: 50px;
        font-size: 1rem;
        border-radius: 10px;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 90, 240, 0.3) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #004ecc, #00a8e0) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(0, 198, 255, 0.4) !important;
    }

    .btn-login:active {
        transform: translateY(0) scale(0.98) !important;
    }

    .forgot-link {
        display: block;
        text-align: center;
        margin-top: 0.8rem;
        font-size: 0.9rem;
        color: #38bdf8;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s;
    }

    .forgot-link:hover {
        color: #7dd3fc;
        text-decoration: underline;
    }

    .divider {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0.9rem 0;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #d1d5db;
    }

    .btn-register {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #ffffff;
        font-weight: 600;
        padding: 0.75rem;
        height: 50px;
        font-size: 0.95rem;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-register:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #38bdf8;
        border-color: #38bdf8;
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.05);
    }

    .error-box {
        background: #fde8e8;
        border: 1px solid #f5c2c7;
        color: #842029;
        border-radius: 8px;
        padding: 0.9rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        text-align: center;
    }

    @media (max-width: 1024px) {
        body {
            background-image: none !important;
            background-color: #ebf0f8 !important;
        }
        body.dark-theme {
            background-image: none !important;
            background-color: #0f172a !important;
        }
        .main-content {
            background: transparent !important;
        }
    }

    @media (max-width: 768px) {
        .login-card {
            max-width: 100% !important;
            width: 92% !important;
            padding: 2rem 1.5rem !important;
            margin: 1rem auto !important;
        }
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 1.5rem 1rem !important;
        }
        .portal-title {
            font-size: 1.35rem;
        }
    }

    /* --- Dark Theme Integration --- */
    body.dark-theme {
        background: url('assets/login_background.png?v=2') no-repeat center center fixed !important;
        background-size: 100% 100% !important;
    }

    body.dark-theme .main-content {
        background: transparent !important;
    }

    body.dark-theme .login-card {
        background: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6) !important;
    }

    body.dark-theme .portal-title {
        color: #f8fafc !important;
    }

    body.dark-theme .portal-sub {
        color: #94a3b8 !important;
    }

    body.dark-theme .form-label {
        color: #f8fafc !important;
    }

    body.dark-theme .input-group {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    body.dark-theme .input-group:focus-within {
        border-color: var(--primary) !important;
    }

    body.dark-theme .form-control {
        color: #f8fafc !important;
    }

    body.dark-theme .btn-register {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }

    body.dark-theme .btn-register:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: #94a3b8 !important;
        color: #fff !important;
    }
</style>

<main class="main-content">
    <div class="login-card card">
        <h1 class="portal-title">Inter Department Portal</h1>
        <p class="portal-sub">Login to continue</p>

        <form action="login_db.php" method="POST" novalidate>
            <?php if (!empty($error)): ?>
                <div class="error-box"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="mb-2">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input id="username" name="username" type="text" class="form-control" placeholder="Enter Username" required>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" name="password" type="password" class="form-control" placeholder="Enter Password" required>
                    <button type="button" class="input-group-text" id="togglePassword" aria-label="Show password" title="Show/Hide Password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-login">Login</button>
            </div>

            <a href="forgetpassward.php" class="forgot-link">Forgot Password?</a>

            <div class="divider">or</div>

            <a href="create_user.php" class="btn-register" id="btn-register">
                <i class="bi bi-person-plus me-1"></i> User Registration
            </a>
        </form>
    </div>
</main>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        var p = document.getElementById('password');
        var icon = document.getElementById('eyeIcon');
        if (p.type === 'password') {
            p.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            p.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>
</body>
</html>
