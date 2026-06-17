<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Coordination Portal</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #2c3e50, #4ca1af);
            height: 100vh;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .form-control {
            border-radius: 10px;
        }

        .btn-login {
            border-radius: 10px;
            font-weight: bold;
        }

        .portal-title {
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center">

<div class="col-md-4">
    <div class="card login-card p-4">

        <!-- Title -->
        <h3 class="text-center portal-title">Inter Department Portal</h3>
        <p class="text-center text-muted">Login to continue</p>

        <!-- Form -->
        <form action="login_db.php" method="POST">

            <!-- Username -->
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter Username" required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter Password" required>
                    <span class="input-group-text" onclick="togglePassword()" style="cursor:pointer;">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Login Button -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-login">Login</button>
            </div>

            <!-- Forgot Password -->
            <div class="text-center mt-3">
                <a href="#" class="text-decoration-none">Forgot Password?</a>
            </div>

        </form>
    </div>
</div>

<script>
function togglePassword() {
    var pass = document.getElementById("password");
    var icon = document.getElementById("eyeIcon");

    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        pass.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>