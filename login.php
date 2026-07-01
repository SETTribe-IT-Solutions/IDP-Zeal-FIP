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

include __DIR__ . '/include/header.php';
?>
<!-- Page-specific styles (Bootstrap and custom overrides) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    :root {
        --card-width: 380px;
        --card-radius: 12px;
        --bg-top: #1f3a47; /* dark teal */
        --bg-bottom: #2fa5a0; /* light teal */
        --primary: #0d6efd;
    }

    .main-content {
        min-height: calc(100vh - var(--header-height));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.72), rgba(30, 58, 138, 0.78)), url('assets/hingoli_building.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        transition: background var(--transition-normal);
    }

    .login-card {
        width: 100%;
        max-width: var(--card-width);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: var(--card-radius);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        padding: 2.2rem 2rem;
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.35);
    }

    .portal-title {
        font-weight: 700;
        font-size: 1.25rem;
        text-align: center;
        margin-bottom: 0.25rem;
        color: #1f2937;
    }

    .portal-sub {
        font-size: 0.95rem;
        text-align: center;
        color: #6b7280;
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .form-control {
        height: 48px;
        font-size: 1rem;
        border-radius: 8px;
    }

    .input-group-text {
        background: #fff;
        border-right: 0;
    }

    .btn-login {
        background: var(--primary);
        border-color: var(--primary);
        font-weight: 700;
        padding: 0.6rem;
        font-size: 1rem;
    }

    .btn-login:hover {
        background: #0b5ed7;
        border-color: #0b5ed7;
    }

    .forgot-link {
        display: block;
        text-align: center;
        margin-top: .75rem;
        font-size: 0.9rem;
        color: #2563eb;
    }

    .divider {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin: 1rem 0;
        color: #9ca3af;
        font-size: 0.85rem;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }

    .btn-register {
        display: block;
        width: 100%;
        background: transparent;
        border: 2px solid #2fa5a0;
        color: #1f3a47;
        font-weight: 700;
        padding: 0.55rem;
        font-size: 0.95rem;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
    }

    .btn-register:hover {
        background: #2fa5a0;
        color: #fff;
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

    @media (max-width: 420px) {
        .form-control {
            height: 56px;
            font-size: 1.05rem;
        }

        .portal-title {
            font-size: 1.1rem;
        }

        .portal-sub {
            font-size: 0.95rem;
        }

        .btn-login {
            font-size: 1.05rem;
        }
    }

    /* --- Dark Theme Integration --- */
    body.dark-theme .main-content {
        background: linear-gradient(135deg, rgba(11, 15, 25, 0.82), rgba(15, 23, 42, 0.88)), url('assets/hingoli_building.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    body.dark-theme .login-card {
        background: rgba(15, 23, 42, 0.85) !important;
        color: var(--text-primary) !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    body.dark-theme .portal-title {
        color: var(--text-primary) !important;
    }

    body.dark-theme .portal-sub {
        color: var(--text-secondary) !important;
    }

    body.dark-theme .form-control {
        background-color: var(--bg-input) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }

    body.dark-theme .input-group-text {
        background-color: var(--bg-input) !important;
        color: var(--text-secondary) !important;
        border-color: var(--border-color) !important;
    }

    body.dark-theme .btn-register {
        border-color: var(--border-color);
        color: var(--text-primary);
    }

    body.dark-theme .btn-register:hover {
        background: var(--bg-hover);
        color: var(--text-primary);
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

            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input id="username" name="username" type="text" class="form-control" placeholder="Enter Username" required>
                </div>
            </div>

            <div class="mb-3">
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
