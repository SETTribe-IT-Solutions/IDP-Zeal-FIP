<aside class="sidebar">
    <div class="sidebar-brand">
        <span>IDP Zeal</span>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="index.php">Dashboard</a>
        </li>
        <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <a href="reports.php">Report</a>
        </li>
        <li class="sidebar-item">
            <a href="create-user.php">Create User</a>
        </li>
        <li class="sidebar-item">
            <a href="change-password.php">Change Password</a>
        </li>
    </ul>
</aside>
