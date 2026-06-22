<?php
/**
 * Zilla Parishad Hingoli - Inter-Department Portal (IDP)
 * Common Header Template (Inline CSS + Responsive)
 */

// --- Start session if not already started ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Include database connection (adjust path to your config) ---
require_once __DIR__ . '/../include/config.php';  // change to your actual path
$conn = db_connect();  // change to your connection function / variable

// --- Fetch role from database if user is logged in ---
$user_role_dynamic = "Guest";  // fallback
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT role, system_role, designation FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Priority: role → system_role → designation
            $user_role_dynamic = !empty($row['role']) ? $row['role'] 
                                : (!empty($row['system_role']) ? $row['system_role'] 
                                : $row['designation']);
            if (empty($user_role_dynamic)) {
                $user_role_dynamic = 'User';
            }
        }
        $stmt->close();
    }
    $_SESSION['user_role'] = $user_role_dynamic;
}

// --- The rest is exactly your original code (page titles, user data) ---
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF'], '.php');
}
$page_titles = [
    'landingpage' => 'Home',
    'user_dashboard' => 'Dashboard',
    'issueform' => 'Add Issue',
    'complaint_report' => 'Issue Report',
    'create_user' => 'Create User',
    'forgetpassward' => 'Change Password',
    'login' => 'Login',
    'logout' => 'Logout'
];
if (!isset($page_title)) {
    $page_title = isset($page_titles[$active_page]) ? $page_titles[$active_page] : 'Portal';
}
if (!isset($page_description)) {
    $page_description = '';
}

// User data from session (fallback values)
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Shri. Rajesh Patil";
$user_role = $user_role_dynamic;  // dynamic role
$user_dept = isset($_SESSION['user_dept']) ? $_SESSION['user_dept'] : "Finance Dept"; // kept for compatibility
$user_initials = isset($_SESSION['user_initials']) ? $_SESSION['user_initials'] : "RP";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ZP Hingoli Inter Department Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== YOUR ORIGINAL CSS – UNCHANGED ========== */
        /* This is exactly the CSS from your first message – keep it all */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap');

        :root {
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            --primary-color: #1e3a8a;
            --primary-light: #2563eb;
            --primary-rgb: 30, 58, 138;
            --accent-color: #d97706;
            --accent-light: #f59e0b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --info-color: #0284c7;
            --bg-body: #f8fafc;
            --bg-header: rgba(255, 255, 255, 0.85);
            --bg-card: #ffffff;
            --bg-dropdown: #ffffff;
            --bg-hover: #f1f5f9;
            --bg-input: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --glass-blur: 16px;
            --header-height: 76px;
            --sidebar-width: 260px;
            --sidebar-collapsed: 72px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.08);
            --shadow-dropdown: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            --transition-fast: 0.15s ease;
            --transition-normal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #0b0f19;
                --bg-header: rgba(15,23,42,0.8);
                --bg-card: #1e293b;
                --bg-dropdown: #1e293b;
                --bg-hover: #334155;
                --bg-input: #1e293b;
                --text-primary: #f8fafc;
                --text-secondary: #cbd5e1;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.3), 0 2px 4px -2px rgba(0,0,0,0.3);
                --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.4), 0 4px 6px -4px rgba(0,0,0,0.4);
            }
        }
        body.dark-theme { --bg-body: #0b0f19; --bg-header: rgba(15,23,42,0.8); --bg-card: #1e293b; --bg-dropdown: #1e293b; --bg-hover: #334155; --bg-input: #1e293b; --text-primary: #f8fafc; --text-secondary: #cbd5e1; --text-muted: #94a3b8; --border-color: #334155; }
        body.light-theme { --bg-body: #f8fafc; --bg-header: rgba(255,255,255,0.85); --bg-card: #ffffff; --bg-dropdown: #ffffff; --bg-hover: #f1f5f9; --bg-input: #f1f5f9; --text-primary: #0f172a; --text-secondary: #475569; --text-muted: #64748b; --border-color: #e2e8f0; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-body); color: var(--text-primary); font-family: var(--font-body); font-size: 15px; line-height: 1.5; transition: background-color var(--transition-normal), color var(--transition-normal); overflow-x: hidden; }
        a { color: inherit; text-decoration: none; }

        /* ---- Header ---- */
        .idp-header {
            position: sticky; top: 0; z-index: 1000; height: var(--header-height);
            background-color: var(--bg-header); backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border-bottom: 1px solid var(--border-color); box-shadow: var(--shadow-sm);
            transition: background-color var(--transition-normal), border-color var(--transition-normal);
        }
        .header-container {
            max-width: 1440px; height: 100%; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }
        .header-left { display: flex; align-items: center; flex-shrink: 0; gap: 12px; }
        .brand-emblem-img { height: 48px; width: auto; object-fit: contain; transition: transform var(--transition-fast); }
        .brand-emblem-img:hover { transform: scale(1.05); }
        .header-middle { flex-grow: 1; display: flex; justify-content: center; text-align: center; }
        .brand-text-wrapper { display: flex; flex-direction: column; align-items: center; }
        .brand-title { font-family: var(--font-heading); font-weight: 800; font-size: 20px; color: var(--text-primary); line-height: 1.2; letter-spacing: -0.01em; }
        .brand-subtitle { font-size: 11px; font-weight: 600; color: var(--accent-color); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
        .brand-logo-img { height: 44px; width: auto; object-fit: contain; transition: transform var(--transition-fast); }
        .brand-logo-img:hover { transform: scale(1.05); }
        .header-divider { width: 1px; height: 28px; background-color: var(--border-color); }

        /* ---- Right actions ---- */
        .header-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .action-btn {
            width: 40px; height: 40px; border-radius: var(--radius-md); border: 1px solid var(--border-color);
            background: transparent; color: var(--text-secondary); display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 16px; transition: all var(--transition-fast);
        }
        .action-btn:hover { background-color: var(--bg-hover); color: var(--text-primary); border-color: var(--text-muted); }

        /* --- Profile display (no dropdown) --- */
        .profile-display {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 12px 4px 8px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border-color);
            background: transparent;
        }
        .profile-avatar {
            width: 32px; height: 32px; border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--accent-light), var(--accent-color));
            color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .profile-info { display: flex; flex-direction: column; padding-right: 4px; }
        .profile-name { font-size: 13px; font-weight: 600; color: var(--text-primary); line-height: 1.2; }
        .profile-role { font-size: 10px; font-weight: 500; color: var(--text-muted); line-height: 1.1; margin-top: 1px; }

        /* Mobile toggle button (hamburger) */
        .mobile-toggle-btn {
            display: none; width: 40px; height: 40px; border-radius: var(--radius-md);
            border: 1px solid var(--border-color); background: transparent; color: var(--text-secondary);
            align-items: center; justify-content: center; cursor: pointer; font-size: 18px;
            transition: all var(--transition-fast); flex-shrink: 0;
        }
        .mobile-toggle-btn:hover { background-color: var(--bg-hover); }

        /* Login/Register buttons */
        .header-btn {
            padding: 8px 18px; font-size: 13px; font-weight: 700; border-radius: 30px;
            cursor: pointer; transition: all var(--transition-fast); display: inline-flex;
            align-items: center; justify-content: center;
        }
        .header-btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #2563eb); color: #ffffff; border: none;
        }
        .header-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); background: linear-gradient(135deg, #38bdf8, #3b82f6); }
        .header-btn-outline { border: 1.5px solid #2563eb; color: #2563eb; background: transparent; }
        .header-btn-outline:hover { transform: translateY(-1px); background: #2563eb; color: #ffffff; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }

        /* ========== SIDEBAR STYLES (shared) ========== */
        .sidebar-backdrop {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px); z-index: 999;
            opacity: 0; visibility: hidden;
            transition: opacity var(--transition-normal), visibility var(--transition-normal);
        }
        .sidebar-backdrop.show { opacity: 1; visibility: visible; }

        .page-wrapper { display: flex; min-height: calc(100vh - var(--header-height)); }

        .sidebar {
            width: var(--sidebar-width); height: calc(100vh - var(--header-height));
            background: linear-gradient(180deg, #1e3c72, #2a5298);
            position: sticky; top: var(--header-height); flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; justify-content: space-between;
            padding-bottom: 100px;
            transition: transform 0.3s ease, width 0.3s ease, margin 0.3s ease;
            z-index: 1000; overflow-x: hidden; overflow-y: auto;
        }
        .sidebar.closed { width: var(--sidebar-collapsed); }
        .sidebar.closed .sidebar-brand span,
        .sidebar.closed .sidebar-menu .sidebar-item a span,
        .sidebar.closed .sidebar-menu .sidebar-item a i.fa-chevron-down,
        .sidebar.closed .sidebar-bottom a span,
        .sidebar.closed .sidebar-bottom button span { display: none; }
        .sidebar.closed .sidebar-menu .sidebar-item a { justify-content: center; padding: 14px 0; }
        .sidebar.closed .sidebar-menu .sidebar-item a i { margin-right: 0; font-size: 18px; }
        .sidebar.closed .sidebar-bottom { justify-content: center; padding: 12px 6px; }
        .sidebar.closed .sidebar-bottom a,
        .sidebar.closed .sidebar-bottom button { padding: 10px 0; flex: 0 0 auto; width: 44px; font-size: 16px; }
        .sidebar.closed .sidebar-bottom a i,
        .sidebar.closed .sidebar-bottom button i { margin-right: 0; }
        .sidebar.closed .sidebar-bottom .btn-label { display: none; }
        .sidebar.closed::-webkit-scrollbar { width: 0; }
        .sidebar.closed { scrollbar-width: none; }

        .sidebar-brand { display: none; }
        .sidebar-menu { list-style: none; padding: 20px 0; flex: 1; overflow-y: auto; }
        .sidebar-item { margin: 4px 12px; }
        .sidebar-item a {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: #fff; text-decoration: none; border-radius: 10px;
            transition: all 0.3s ease; font-size: 15px; font-weight: 500; white-space: nowrap;
        }
        .sidebar-item a i { width: 20px; text-align: center; font-size: 16px; flex-shrink: 0; }
        .sidebar-item a:hover { background: rgba(255,255,255,0.15); transform: translateX(5px); }
        .sidebar-item a .nav-label { flex: 1; }
        .sidebar-item.active > a { background: #fff; color: #1e3c72; font-weight: 700; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .sidebar-item.active > a i { color: #1e3c72; }

        .sidebar-bottom {
            position: sticky; bottom: 0; left: 0; width: 100%;
            padding: 12px 15px; display: flex; gap: 8px; justify-content: center; align-items: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(30,60,114,0.5); backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px); flex-shrink: 0;
        }
        .sidebar-bottom a, .sidebar-bottom button {
            flex: 1; padding: 10px 12px; background: rgba(255,255,255,0.08);
            color: #fff; border: 1px solid rgba(255,255,255,0.15);
            text-decoration: none; text-align: center; cursor: pointer;
            border-radius: 8px; font-size: 13px; font-weight: 500;
            transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;
            gap: 8px; white-space: nowrap;
        }
        .sidebar-bottom a:hover, .sidebar-bottom button:hover { background: rgba(255,255,255,0.15); transform: translateY(-1px); }
        .sidebar-bottom a i, .sidebar-bottom button i { font-size: 14px; }
        .sidebar-bottom button { min-height: 36px; }
        .sidebar-bottom .btn-label { display: inline; }

        .main-content { flex: 1; padding: 30px; transition: margin 0.3s ease, width 0.3s ease; min-width: 0; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .mobile-toggle-btn { display: flex !important; }
            .profile-info { display: none; }
            .brand-title { font-size: 17px; }
            .brand-subtitle { font-size: 10px; }
        }
        @media (max-width: 768px) {
            :root { --header-height: 64px; }
            .header-container { padding: 0 12px; gap: 10px; }
            .brand-title { font-size: 14px; }
            .brand-subtitle { font-size: 8px; letter-spacing: 0.05em; }
            .brand-emblem-img { height: 36px; }
            .brand-logo-img { height: 32px; }
            .header-divider { display: none; }
            .header-actions { gap: 6px; }
            .action-btn { width: 34px; height: 34px; font-size: 14px; }
            .profile-display { padding: 2px 8px 2px 4px; gap: 6px; }
            .profile-avatar { width: 28px; height: 28px; font-size: 10px; }
            .mobile-toggle-btn { width: 34px; height: 34px; font-size: 16px; }
            .header-btn { font-size: 11px; padding: 6px 12px; }

            .sidebar {
                position: fixed; top: var(--header-height); left: 0;
                width: 280px !important; height: calc(100vh - var(--header-height));
                transform: translateX(-100%);
                transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
                border-radius: 0 12px 12px 0;
                box-shadow: 4px 0 30px rgba(0,0,0,0.25);
                padding-bottom: 80px; z-index: 1000;
            }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.closed { width: 280px !important; }
            .sidebar.closed .sidebar-menu .sidebar-item a span,
            .sidebar.closed .sidebar-menu .sidebar-item a i.fa-chevron-down,
            .sidebar.closed .sidebar-bottom a span,
            .sidebar.closed .sidebar-bottom button span { display: inline !important; }
            .sidebar.closed .sidebar-menu .sidebar-item a { justify-content: flex-start; padding: 12px 16px; }
            .sidebar.closed .sidebar-menu .sidebar-item a i { margin-right: 12px; font-size: 16px; }
            .sidebar.closed .sidebar-bottom { justify-content: center; padding: 12px 15px; }
            .sidebar.closed .sidebar-bottom a,
            .sidebar.closed .sidebar-bottom button { flex: 1; padding: 10px 12px; width: auto; font-size: 13px; }
            .sidebar.closed .sidebar-bottom .btn-label { display: inline; }

            .main-content { padding: 16px; margin-left: 0 !important; width: 100%; }
            .page-wrapper { display: block; }
            .sidebar-backdrop.show { opacity: 1; visibility: visible; }
        }
        @media (max-width: 480px) {
            .header-container { padding: 0 8px; gap: 6px; }
            .brand-title { font-size: 12px; }
            .brand-subtitle { font-size: 7px; }
            .brand-emblem-img { height: 30px; }
            .brand-logo-img { height: 26px; }
            .header-actions .action-btn:not(#themeToggleBtn) { display: none; }
            .action-btn { width: 30px; height: 30px; font-size: 12px; }
            .mobile-toggle-btn { width: 30px; height: 30px; font-size: 14px; }
            .profile-display { padding: 2px 6px 2px 4px; }
            .profile-avatar { width: 24px; height: 24px; font-size: 9px; }
            .header-btn { font-size: 10px; padding: 4px 10px; }
            .sidebar { width: 260px !important; }
            .sidebar.closed { width: 260px !important; }
            .main-content { padding: 12px; }
        }
        @media (max-width: 360px) {
            .brand-title { font-size: 10px; }
            .brand-subtitle { font-size: 6px; }
            .brand-emblem-img { height: 24px; }
            .brand-logo-img { height: 20px; }
            .sidebar { width: 220px !important; }
            .sidebar.closed { width: 220px !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="idp-header">
    <div class="header-container">

        <!-- Left: Mobile Toggle + Emblem -->
        <div class="header-left">
            <button class="mobile-toggle-btn" id="mobileSidebarToggle" aria-label="Toggle Navigation" title="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="user_dashboard.php">
                <img src="assets/maharashtra-emblem.png" alt="Maharashtra State Emblem" class="brand-emblem-img">
            </a>
        </div>

        <!-- Middle: Title -->
        <div class="header-middle">
            <a href="user_dashboard.php" class="brand-text-wrapper">
                <h1 class="brand-title">Zilla Parishad Hingoli</h1>
                <p class="brand-subtitle">Inter-Department Portal</p>
            </a>
        </div>

        <!-- Right: Logo + Actions -->
        <div class="header-right">
            <img src="assets/zp-logo.png" alt="ZP Hingoli Logo" class="brand-logo-img">
            <div class="header-divider"></div>

            <div class="header-actions">
                <!-- Theme Toggle -->
                <button class="action-btn" id="themeToggleBtn" title="Toggle Theme" onclick="toggleTheme()">
                    <i class="fa-solid fa-moon"></i>
                </button>

                <?php if (isset($_SESSION['username'])): ?>
                    <!-- Static profile display (no dropdown) -->
                    <div class="profile-display">
                        <div class="profile-avatar"><?php echo $user_initials; ?></div>
                        <div class="profile-info">
                            <span class="profile-name"><?php echo $user_name; ?></span>
                            <span class="profile-role"><?php echo htmlspecialchars($user_role); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="header-btn header-btn-outline">लॉगिन / Login</a>
                    <a href="create_user.php" class="header-btn header-btn-primary">नोंदणी / Register</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</header>
<!-- ===== END HEADER ===== -->

<!-- Theme toggle script (unchanged) -->
<script>
    function toggleTheme() {
        const body = document.body;
        const icon = document.querySelector('#themeToggleBtn i');
        if (body.classList.contains('dark-theme')) {
            body.classList.remove('dark-theme'); body.classList.add('light-theme');
            if (icon) icon.className = 'fa-solid fa-moon';
            localStorage.setItem('idp-theme', 'light');
        } else {
            body.classList.remove('light-theme'); body.classList.add('dark-theme');
            if (icon) icon.className = 'fa-solid fa-sun';
            localStorage.setItem('idp-theme', 'dark');
        }
    }
    (function initTheme() {
        const saved = localStorage.getItem('idp-theme');
        const body = document.body;
        const icon = document.querySelector('#themeToggleBtn i');
        if (saved === 'dark') { body.classList.add('dark-theme'); if(icon) icon.className='fa-solid fa-sun'; }
        else if (saved === 'light') { body.classList.add('light-theme'); if(icon) icon.className='fa-solid fa-moon'; }
        else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) { body.classList.add('dark-theme'); if(icon) icon.className='fa-solid fa-sun'; }
            else { body.classList.add('light-theme'); if(icon) icon.className='fa-solid fa-moon'; }
        }
    })();
</script>
<!-- ===== NO CLOSING </body> or </html> ===== -->