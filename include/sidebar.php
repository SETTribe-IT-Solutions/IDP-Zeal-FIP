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
    /* ===== SIDEBAR ===== */
    .sidebar {
        width: 260px;
        height: calc(100vh - var(--header-height, 70px));
        background: linear-gradient(180deg, #1e3c72, #2a5298);
        position: fixed;
        top: var(--header-height, 70px);
        left: 0;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        transition: width 0.3s ease, transform 0.3s ease;
        z-index: 1000;
        overflow: hidden;
    }

    /* Desktop minimized state */
    .sidebar.minimized {
        width: 0;
    }

    /* ===== SIDEBAR MENU ===== */
    .sidebar-menu {
        list-style: none;
        padding: 20px 0;
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        white-space: nowrap;
    }

    .sidebar-item {
        margin: 8px 15px;
    }

    .sidebar-item a {
        display: block;
        padding: 14px 20px;
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-size: 16px;
        font-weight: 500;
    }

    .sidebar-item a i {
        margin-right: 12px;
        width: 20px;
        text-align: center;
        font-size: 16px;
        transition: transform 0.3s ease;
    }

    .sidebar-item a:hover i {
        transform: scale(1.1);
    }

    .sidebar-item a:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
    }

    .sidebar-item.active a {
        background: #fff;
        color: #1e3c72;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    /* ===== SIDEBAR BOTTOM ===== */
    .sidebar-bottom {
        padding: 12px 15px;
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(30, 60, 114, 0.5);
        flex-shrink: 0;
    }

    .sidebar-bottom a {
        flex: 1;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-bottom a:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    /* ===== MAIN CONTENT ===== */
    .main-content {
        margin-left: 260px;
        padding: 30px;
        transition: margin-left 0.3s ease;
        min-height: calc(100vh - var(--header-height, 70px));
    }

    .main-content.minimized {
        margin-left: 0;
    }

    /* ===== MOBILE SIDEBAR TOGGLE BUTTON (in header) ===== */
    .mobile-sidebar-toggle {
        display: none;
        background: transparent;
        border: none;
        color: inherit;
        font-size: 1.4rem;
        cursor: pointer;
        padding: 6px 10px;
        line-height: 1;
        z-index: 1100;
    }

    /* ===== OVERLAY for mobile ===== */
    #sidebarOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 999;
    }

    #sidebarOverlay.active {
        display: block;
    }

    /* ===== RESPONSIVE ===== */
    @media screen and (max-width: 768px) {

        /* Hide the sidebar off-canvas by default on mobile */
        .sidebar {
            width: 260px;
            transform: translateX(-100%);
            top: var(--header-height, 70px);
            height: calc(100vh - var(--header-height, 70px));
        }

        /* When open on mobile */
        .sidebar.mobile-open {
            transform: translateX(0);
        }

        /* Main content always full width on mobile */
        .main-content,
        .main-content.minimized {
            margin-left: 0;
        }

        /* Show mobile toggle button in header */
        .mobile-sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<!-- Overlay (mobile backdrop) -->
<div id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
    <div>
        <div class="sidebar-brand">
            <span>
            </span>
        </div>

        <ul class="sidebar-menu">


            <!-- Role-based items -->
            <?php if ($normalizedRole === 'ग्रामपंचायत अधिकारी' || $normalizedRole === 'शिक्षक' || $normalizedRole === 'अंगणवाडी सेविका' || $normalizedRole === 'teacher'): ?>
                <li
                    class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'gram_panchayat.php', 'anganwadi.php']) ? 'active' : ''; ?>">
                    <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-chart-line"></i> डॅशबोर्ड (Dashboard)</a>
                </li>
                <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'issueform.php') ? 'active' : ''; ?>">
                    <a href="issueform.php"><i class="fa-solid fa-plus-circle"></i>समस्या नोंदवा(Raise Issue)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaint_report.php') ? 'active' : ''; ?>">
                    <a href="complaint_report.php"><i class="fa-solid fa-file-invoice"></i>माझ्या तक्रारी(My Issues)</a>
                </li>
            <?php elseif (in_array($normalizedRole, ['bdo', 'tho', 'hod'])): ?>
                <li
                    class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'BDO.php', 'THO.php', 'Hod.php']) ? 'active' : ''; ?>">
                    <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-chart-line"></i>डॅशबोर्ड(Dashboard)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') !== 'transfer') ? 'active' : ''; ?>">
                    <a href="assign_issues.php?view=assigned"><i class="fa-solid fa-list-check"></i> नियुक्त तक्रारी
                        (Assigned Issues)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') === 'transfer') ? 'active' : ''; ?>">
                    <a href="assign_issues.php?view=transfer"><i class="fa-solid fa-right-left"></i> तक्रार हस्तांतरण
                        (Transfer Issues)</a>
                </li>
            <?php else: ?>
                <!-- Default / CEO / Admin fallback view -->
                <li
                    class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'BDO.php', 'THO.php', 'CEO.php', 'Hod.php', 'gram_panchayat.php', 'anganwadi.php']) ? 'active' : ''; ?>">
                    <a href="<?php echo $dashboard_url; ?>"><i class="fa-solid fa-chart-line"></i> डॅशबोर्ड (Dashboard)</a>
                </li>
                <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'issueform.php') ? 'active' : ''; ?>">
                    <a href="issueform.php"><i class="fa-solid fa-plus-circle"></i> समस्या नोंदवा (Raise Issue)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaint_report.php') ? 'active' : ''; ?>">
                    <a href="complaint_report.php"><i class="fa-solid fa-file-invoice"></i> तक्रार अहवाल (Issue Report)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') !== 'transfer') ? 'active' : ''; ?>">
                    <a href="assign_issues.php?view=assigned"><i class="fa-solid fa-list-check"></i> नियुक्त तक्रारी
                        (Assigned Issues)</a>
                </li>
                <li
                    class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php' && ($_GET['view'] ?? '') === 'transfer') ? 'active' : ''; ?>">
                    <a href="assign_issues.php?view=transfer"><i class="fa-solid fa-right-left"></i> तक्रार हस्तांतरण
                        (Transfer Issues)</a>
                </li>
            <?php endif; ?>


        </ul>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" title="Logout">Logout</a>
    </div>

</aside>


<!-- JavaScript for Toggle Functionality -->
<script>
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.querySelector(".main-content");
    const overlay = document.getElementById("sidebarOverlay");
    const isMobile = () => window.innerWidth <= 768;

    /* ---- DESKTOP TOGGLE ---- */
    // Desktop toggle button lives in the sidebar bottom or header
    // We use a single button with id="desktopSidebarToggle" placed in header (non-mobile)
    const desktopBtn = document.getElementById("desktopSidebarToggle");
    if (desktopBtn) {
        desktopBtn.addEventListener("click", function () {
            if (isMobile()) return; // handled by mobile toggle
            sidebar.classList.toggle("minimized");
            if (mainContent) mainContent.classList.toggle("minimized");
        });
    }

    /* ---- MOBILE TOGGLE ---- */
    const mobileBtn = document.getElementById("mobileSidebarToggle");
    if (mobileBtn) {
        mobileBtn.addEventListener("click", function () {
            const isOpen = sidebar.classList.toggle("mobile-open");
            overlay.classList.toggle("active", isOpen);
        });
    }

    /* ---- CLOSE sidebar when overlay is clicked (mobile) ---- */
    overlay.addEventListener("click", function () {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
    });

    /* ---- Handle resize ---- */
    window.addEventListener("resize", function () {
        if (!isMobile()) {
            // On desktop: remove mobile classes
            sidebar.classList.remove("mobile-open");
            overlay.classList.remove("active");
        } else {
            // On mobile: remove desktop classes
            sidebar.classList.remove("minimized");
            if (mainContent) mainContent.classList.remove("minimized");
        }
    });
</script>