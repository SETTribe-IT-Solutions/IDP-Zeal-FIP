<style>
    /* Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f4f6f9;
    }

    /* Sidebar */
    .sidebar {
        width: 260px;
        height: calc(100vh - var(--header-height));
        background: linear-gradient(180deg, #1e3c72, #2a5298);
        position: fixed;
        top: var(--header-height);
        left: 0;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding-bottom: 100px;
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebar.closed {
        width: 72px;
    }

    .sidebar.closed .sidebar-brand {
        padding: 0;
    }

    .sidebar.closed .sidebar-brand span,
    .sidebar.closed .sidebar-menu,
    .sidebar.closed .sidebar-item {
        display: none;
    }

    .sidebar.closed .sidebar-bottom {
        justify-content: center;
    }

    .sidebar.closed .sidebar-bottom a {
        display: none;
    }

    .sidebar-brand {
        display: none;
    }

    .sidebar-menu {
        list-style: none;
        padding: 20px 0;
        flex: 1;
        overflow-y: auto;
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

    .sidebar-item a:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
    }

    .sidebar-submenu {
        list-style: none;
        margin: 6px 0 0 0;
        padding: 0 0 0 8px;
    }

    .sidebar-submenu .sidebar-item a {
        padding: 10px 20px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.95);
        background: transparent;
    }

    .sidebar-submenu .sidebar-item a:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: none;
    }

    .sidebar-item.active a {
        background: #fff;
        color: #1e3c72;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }



    .main-content {
        margin-left: 260px;
        padding: 30px;
        transition: margin-left 0.3s ease;
    }

    .main-content.collapsed {
        margin-left: 72px;
    }

    .page-title {
        color: #1e3c72;
        margin-bottom: 20px;
    }

    .sidebar-bottom {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 12px 15px;
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(30, 60, 114, 0.5);
    }

    .sidebar-bottom a,
    .sidebar-bottom button {
        flex: 1;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-bottom a:hover,
    .sidebar-bottom button:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    .sidebar-bottom button {
        min-height: 36px;
    }

    .toggle-btn {
        display: inline-flex;
        width: auto;
        height: auto;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        cursor: pointer;
        border-radius: 8px;
        font-size: 12px;
        padding: 10px 12px;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .toggle-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    .toggle-btn:active {
        transform: translateY(0);
    }

    @media screen and (max-width: 1024px) {
        .sidebar {
            width: 240px;
        }

        .main-content {
            margin-left: 240px;
        }
    }

    @media screen and (max-width: 768px) {
        .sidebar {
            width: 220px;
        }

        .main-content {
            margin-left: 220px;
        }

    }

    @media screen and (max-width: 480px) {
        .sidebar {
            width: 220px;
        }
    }
</style>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
    <div>
        <div class="sidebar-brand">
            <span>
            </span>
        </div>

        <ul class="sidebar-menu">
            <li
                class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'landingpage.php') ? 'active' : ''; ?>">
                <a href="landingpage.php">Home</a>
            </li>

            <li
                class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_dashboard.php', 'BDO.php', 'THO.php', 'CEO.php', 'Hod.php', 'gram_panchayat.php', 'anganwadi.php']) ? 'active' : ''; ?>">
                <a href="user_dashboard.php">Dashboard</a>
            </li>

            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'issueform.php') ? 'active' : ''; ?>">
                <a href="issueform.php">Add Issue</a>
            </li>

            <li
                class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaint_report.php') ? 'active' : ''; ?>">
                <a href="complaint_report.php">Issue Report</a>
            </li>

            <li
                class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'assign_issues.php') ? 'active' : ''; ?>">
                <a href="assign_issues.php">Assigned Issues</a>
            </li>



            <li
                class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'change-password.php') ? 'active' : ''; ?>">
                <a href="forgetpassward.php">Change Password</a>
            </li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" title="Logout">Logout</a>
        <button id="sidebarToggle" class="toggle-btn" title="Toggle Sidebar">☰</button>
    </div>

</aside>

<!-- JavaScript for Toggle Functionality -->
<script>
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.querySelector(".main-content");

    sidebarToggle.addEventListener("click", function () {
        const isClosed = sidebar.classList.toggle("closed");

        if (mainContent) {
            mainContent.classList.toggle("collapsed", isClosed);
        }
        sidebarToggle.textContent = isClosed ? "☰ " : "☰ ";
        localStorage.setItem("sidebarClosed", isClosed);
    });

    window.addEventListener("load", function () {
        const isClosed = localStorage.getItem("sidebarClosed") === "true";
        if (isClosed) {
            sidebar.classList.add("closed");
            if (mainContent) mainContent.classList.add("collapsed");
            if (sidebarToggle) sidebarToggle.textContent = "☰ ";
        } else {
            if (mainContent) mainContent.classList.remove("collapsed");
            if (sidebarToggle) sidebarToggle.textContent = "☰ ";
        }
    });
</script>