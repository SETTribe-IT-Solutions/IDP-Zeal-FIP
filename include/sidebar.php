<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = !empty($_SESSION['user_system_role']) ? $_SESSION['user_system_role'] : ($_SESSION['user_role'] ?? '');
$normalizedRole = strtolower(trim($role));

// Helper function to get the dashboard url based on role
if (!function_exists('getDashboardUrl')) {
    function getDashboardUrl($role)
    {
        $normalized = strtolower(trim($role));
        switch ($normalized) {
            case 'bdo':
                return 'BDO.php';
            case 'tho':
                return 'THO.php';
            case 'ceo':
                return 'CEO.php';
            case 'hod':
                return 'Hod.php';
            case 'ग्रामपंचायत अधिकारी':
                return 'gram_panchayat.php';
            default:
                return 'landingpage.php';
        }
    }
}

$dashboard_url = getDashboardUrl($role);
?>

<style>
    /* =========================================
       EXACT UI RE-CREATION FOR SIDEBAR
       ========================================= */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    /* Sidebar Container */
    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: linear-gradient(180deg, #1e277a 0%, #2b63b7 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        overflow: hidden;
    }

    /* Logo / Brand Section */
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 30px 20px 20px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 10px;
    }

    .brand-icon-box {
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .brand-icon-box svg {
        width: 22px;
        height: 22px;
        stroke: #fff;
        stroke-width: 2;
    }

    .brand-text {
        display: flex;
        flex-direction: column;
    }

    .brand-text .title-main {
        color: #ffffff;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: 0.3px;
    }

    .brand-text .title-sub {
        color: rgba(255, 255, 255, 0.5);
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 2px;
    }

    /* Sidebar Menu */
    .sidebar-menu {
        list-style: none;
        padding: 10px 16px;
        margin: 0;
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .sidebar-item {
        margin-bottom: 6px;
    }

    .sidebar-item a {
        display: flex;
        align-items: center;
        padding: 12px 18px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        border-radius: 14px;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        position: relative;
    }

    .sidebar-item a i {
        margin-right: 14px;
        width: 20px;
        text-align: center;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.5);
        transition: color 0.3s;
    }

    /* Hover state */
    .sidebar-item a:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
    }
    .sidebar-item a:hover i {
        color: #fff;
    }

    /* Active State - EXACT PILL STYLE FROM IMAGE */
    .sidebar-item.active a {
        background: linear-gradient(90deg, #3eb1e1 0%, #2b6dc9 100%);
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(62, 177, 225, 0.3);
    }

    .sidebar-item.active a i {
        color: #ffffff;
    }

    /* Active State White Dot (Notification dot from image) */
    .sidebar-item.active a::after {
        content: '';
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        background: #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    /* Logout Section */
    .sidebar-bottom {
        padding: 20px 16px 30px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: transparent;
        flex-shrink: 0;
    }

    .sidebar-bottom a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px;
        background: #f31f55; /* Pinkish red from image */
        color: #fff;
        border: none;
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
    }

    .sidebar-bottom a:hover {
        background: #d41949;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(243, 31, 85, 0.3);
    }

    /* ---------------------------
       Responsive & Toggle Logic
       --------------------------- */

    /* Mobile Hidden state */
    @media screen and (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }
        .sidebar.mobile-open {
            transform: translateX(0);
        }
        .main-content, .main-content.minimized {
            margin-left: 0 !important;
        }
        .mobile-sidebar-toggle {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
    }

    /* Desktop Minimized state */
    .sidebar.minimized {
        transform: translateX(-100%);
    }
    .main-content {
        margin-left: 260px;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
    }
    .main-content.minimized {
        margin-left: 0;
    }

    /* Overlay */
    #sidebarOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        backdrop-filter: blur(2px);
    }
    #sidebarOverlay.active {
        display: block;
    }
</style>

<!-- Overlay (mobile backdrop) -->
<div id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
    
    <!-- Brand / Logo Area -->
    <div class="sidebar-brand">
        <div class="brand-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </div>
        <div class="brand-text">
            <span class="title-main">ZP Hingoli</span>
            <span class="title-sub">PORTAL</span>
        </div>
    </div>

    <!-- Menu Items -->
    <ul class="sidebar-menu">

        <?php if ($normalizedRole === 'ग्रामपंचायत अधिकारी' || $normalizedRole === 'शिक्षक' || $normalizedRole === 'अंगणवाडी सेविका' || $normalizedRole === 'teacher'): ?>
            
            <li class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'gram_panchayat.php', 'anganwadi.php']) ? 'active' : ''; ?>">
                <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-border-all"></i> डॅशबोर्ड</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'issueform.php') ? 'active' : ''; ?>">
                <a href="issueform.php"><i class="fa-solid fa-pen-to-square"></i> समस्या नोंदवा</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaint_report.php') ? 'active' : ''; ?>">
                <a href="complaint_report.php"><i class="fa-solid fa-file-lines"></i> माझ्या तक्रारी</a>
            </li>

        <?php elseif (in_array($normalizedRole, ['bdo', 'tho', 'hod'])): ?>
            
            <li class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'BDO.php', 'THO.php', 'Hod.php']) ? 'active' : ''; ?>">
                <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-border-all"></i> डॅशबोर्ड</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') !== 'transfer') ? 'active' : ''; ?>">
                <a href="assign_issues.php?view=assigned"><i class="fa-solid fa-list-check"></i> नियुक्त तक्रारी</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') === 'transfer') ? 'active' : ''; ?>">
                <a href="assign_issues.php?view=transfer"><i class="fa-solid fa-arrow-right-arrow-left"></i> तक्रार हस्तांतरण</a>
            </li>

        <?php else: ?>
            
            <li class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'BDO.php', 'THO.php', 'CEO.php', 'Hod.php', 'gram_panchayat.php', 'anganwadi.php']) ? 'active' : ''; ?>">
                <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-border-all"></i> डॅशबोर्ड</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'issueform.php') ? 'active' : ''; ?>">
                <a href="issueform.php"><i class="fa-solid fa-pen-to-square"></i> समस्या नोंदवा</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaint_report.php') ? 'active' : ''; ?>">
                <a href="complaint_report.php"><i class="fa-solid fa-file-lines"></i> तक्रार अहवाल</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') !== 'transfer') ? 'active' : ''; ?>">
                <a href="assign_issues.php?view=assigned"><i class="fa-solid fa-list-check"></i> नियुक्त तक्रारी</a>
            </li>
            
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') === 'transfer') ? 'active' : ''; ?>">
                <a href="assign_issues.php?view=transfer"><i class="fa-solid fa-arrow-right-arrow-left"></i> तक्रार हस्तांतरण</a>
            </li>

        <?php endif; ?>

    </ul>

    <!-- Logout Button (Pink/Red Pill) -->
    <div class="sidebar-bottom">
        <a href="logout.php">
            <i class="fa-solid fa-arrow-right-from-bracket" style="font-size: 16px;"></i> Logout
        </a>
    </div>

</aside>

<!-- JavaScript for Toggle Functionality -->
<script>
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.querySelector(".main-content");
    const overlay = document.getElementById("sidebarOverlay");
    const isMobile = () => window.innerWidth <= 768;

    // Desktop toggle
    const desktopBtn = document.getElementById("desktopSidebarToggle");
    if (desktopBtn) {
        desktopBtn.addEventListener("click", function () {
            if (isMobile()) return;
            sidebar.classList.toggle("minimized");
            if (mainContent) mainContent.classList.toggle("minimized");
        });
    }

    // Mobile toggle
    const mobileBtn = document.getElementById("mobileSidebarToggle");
    if (mobileBtn) {
        mobileBtn.addEventListener("click", function () {
            const isOpen = sidebar.classList.toggle("mobile-open");
            overlay.classList.toggle("active", isOpen);
        });
    }

    // Close on overlay click
    overlay.addEventListener("click", function () {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
    });

    // Handle resize
    window.addEventListener("resize", function () {
        if (!isMobile()) {
            sidebar.classList.remove("mobile-open");
            overlay.classList.remove("active");
        } else {
            sidebar.classList.remove("minimized");
            if (mainContent) mainContent.classList.remove("minimized");
        }
    });
</script>