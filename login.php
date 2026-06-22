<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inter Department Portal — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{
            --card-width: 380px;
            --card-radius: 12px;
            --bg-top: #1f3a47; /* dark teal */
            --bg-bottom: #2fa5a0; /* light teal */
            --primary: #0d6efd;
        }

        html,body{height:100%;}
        body{
            margin:0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(180deg,var(--bg-top),var(--bg-bottom));
            -webkit-font-smoothing:antialiased;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:1rem;
        }

        .login-card{
            width:100%;
            max-width:var(--card-width);
            background:#fff;
            border-radius:var(--card-radius);
            box-shadow:0 10px 30px rgba(0,0,0,0.15);
            padding:1.6rem;
        }

        .portal-title{font-weight:700;font-size:1.25rem;text-align:center;margin-bottom:0.25rem;color:#1f2937}
        .portal-sub{font-size:0.95rem;text-align:center;color:#6b7280;margin-bottom:1rem}

        .form-label{font-weight:600;font-size:0.95rem}
        .form-control{height:48px;font-size:1rem;border-radius:8px}
        .input-group-text{background:#fff;border-right:0}

        .btn-login{background:var(--primary);border-color:var(--primary);font-weight:700;padding:0.6rem; font-size:1rem}
        .btn-login:hover{background:#0b5ed7;border-color:#0b5ed7}

        .forgot-link{display:block;text-align:center;margin-top:.75rem;font-size:0.9rem;color:#2563eb}

        @media (max-width:420px){
            :root{--card-width:calc(100vw - 1.5rem)}
            .form-control{height:56px;font-size:1.05rem}
            .portal-title{font-size:1.1rem}
            .portal-sub{font-size:0.95rem}
            .btn-login{font-size:1.05rem}
        }
    </style>
</head>

<body>
    <main class="login-card card">
        <h1 class="portal-title">Inter Department Portal</h1>
        <p class="portal-sub">Login to continue</p>

        <form action="login_db.php" method="POST" novalidate>

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
        </form>
    </main>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function(){
            var p = document.getElementById('password');
            var icon = document.getElementById('eyeIcon');
            if(p.type === 'password'){
                p.type = 'text';
                icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash');
            } else {
                p.type = 'password';
                icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye');
            }
        });
    </script>

</body>

</html>
