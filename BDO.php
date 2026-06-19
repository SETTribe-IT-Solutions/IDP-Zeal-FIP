<?php
session_start();
require_once 'include/config.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Check role authorization
$allowed_role = 'BDO';
$user_system_role = $_SESSION['system_role'] ?? 'user';
if (strtolower(trim($user_system_role)) !== strtolower($allowed_role)) {
    $redirectPage = get_role_redirect_page($user_system_role);
    header("Location: " . $redirectPage);
    exit;
}

$user_taluka = $_SESSION['user_taluka'] ?? '';

// Fetch stats and recent items from DB with block filters
$total_issues = 0;
$in_progress_issues = 0;
$resolved_issues = 0;
$open_issues = 0;
$active_depts = 0;
$recent_issues = [];

try {
    $conn = db_connect();

    // Query stats filtered by Taluka
    $count_stmt = $conn->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(status) = 'in progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN LOWER(status) = 'resolved' OR LOWER(status) = 'closed' THEN 1 ELSE 0 END) as resolved
    FROM tbl_raiseissue WHERE taluka = ?");
    
    if ($count_stmt) {
        $count_stmt->bind_param("s", $user_taluka);
        $count_stmt->execute();
        $count_res = $count_stmt->get_result();
        if ($count_res && $row = $count_res->fetch_assoc()) {
            $total_issues = (int)$row['total'];
            $in_progress_issues = (int)$row['in_progress'];
            $resolved_issues = (int)$row['resolved'];
            $open_issues = $total_issues - ($in_progress_issues + $resolved_issues);
            if ($open_issues < 0) $open_issues = 0;
        }
        $count_stmt->close();
    }

    // Active departments filtered by Taluka
    $dept_stmt = $conn->prepare("SELECT COUNT(DISTINCT department) as dept_count FROM tbl_raiseissue WHERE taluka = ? AND department IS NOT NULL AND department != ''");
    if ($dept_stmt) {
        $dept_stmt->bind_param("s", $user_taluka);
        $dept_stmt->execute();
        $dept_res = $dept_stmt->get_result();
        if ($dept_res && $row = $dept_res->fetch_assoc()) {
            $active_depts = (int)$row['dept_count'];
        }
        $dept_stmt->close();
    }

    // Recent 5 issues filtered by Taluka
    $recent_stmt = $conn->prepare("SELECT * FROM tbl_raiseissue WHERE taluka = ? ORDER BY issue_date DESC, id DESC LIMIT 5");
    if ($recent_stmt) {
        $recent_stmt->bind_param("s", $user_taluka);
        $recent_stmt->execute();
        $recent_res = $recent_stmt->get_result();
        if ($recent_res) {
            $recent_issues = $recent_res->fetch_all(MYSQLI_ASSOC);
        }
        $recent_stmt->close();
    }
    
    $conn->close();
} catch (Exception $e) {
    // Graceful fallbacks
    $total_issues = 0;
    $in_progress_issues = 0;
    $resolved_issues = 0;
    $open_issues = 0;
    $active_depts = 0;
    $recent_issues = [];
}

// Function to determine badge class
function getStatusBadgeClass($status) {
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'badge-pending';
        case 'in progress':
            return 'badge-in-progress';
        case 'resolved':
        case 'closed':
            return 'badge-resolved';
        case 'open':
        default:
            return 'badge-open';
    }
}

// Marathi translation mapping helper
function translateStatus($status) {
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'प्रलंबित (Pending)';
        case 'in progress':
            return 'प्रक्रियेत (In Progress)';
        case 'resolved':
            return 'निराकरण (Resolved)';
        case 'closed':
            return 'बंद (Closed)';
        case 'open':
        default:
            return 'उघडलेली (Open)';
    }
}

$user_display_name = $_SESSION['user_name'] ?? "BDO Officer";

// Include header & sidebar templates
include 'include/header.php';
include 'include/sidebar.php';
?>

<main class="main-content">
    <!-- Premium Welcome Section -->
    <div class="welcome-container">
        <div class="welcome-bg-overlay"></div>
        <div class="welcome-info">
            <span class="welcome-badge">
                <i class="fa-solid fa-circle-check"></i> गट विकास अधिकारी डॅशबोर्ड | BDO Dashboard
            </span>
            <h1 class="welcome-title">
                <span class="highlight"><?php echo htmlspecialchars($user_display_name); ?></span>
            </h1>
            <p class="welcome-desc">
                आपण <strong><?php echo htmlspecialchars($user_taluka); ?></strong> तालुक्याचे गट विकास अधिकारी (BDO) म्हणून लॉग इन आहात. खाली आपल्या तालुक्याची प्रलंबित आणि निराकरण झालेली कामे पहा.
            </p>
        </div>
        <div class="welcome-actions">
            <a href="issueform.php" class="welcome-btn welcome-btn-primary">
                <i class="fa-solid fa-plus-circle"></i> नवीन समस्या नोंदवा
            </a>
            <a href="complaint_report.php" class="welcome-btn welcome-btn-secondary">
                <i class="fa-solid fa-file-invoice"></i> अहवाल पहा
            </a>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-header">
        <h2><i class="fa-solid fa-chart-pie"></i> तालुक्याची आकडेवारी (Taluka Stats for <?php echo htmlspecialchars($user_taluka); ?>)</h2>
        <span class="refresh-indicator"><i class="fa-solid fa-arrows-rotate"></i> रीअल-टाइम अपडेट</span>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div class="stat-info">
                <h3>एकूण समस्या</h3>
                <p class="stat-number"><?php echo $total_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-open">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-envelope-open"></i>
            </div>
            <div class="stat-info">
                <h3>उघडलेल्या समस्या</h3>
                <p class="stat-number"><?php echo $open_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-progress">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-spinner"></i>
            </div>
            <div class="stat-info">
                <h3>प्रक्रियेत</h3>
                <p class="stat-number"><?php echo $in_progress_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-resolved">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-info">
                <h3>निराकरण झालेल्या</h3>
                <p class="stat-number"><?php echo $resolved_issues; ?></p>
            </div>
        </div>
    </div>

    <!-- Recent Issues Feed -->
    <div class="section-container">
        <div class="recent-header">
            <h2 class="section-title"><i class="fa-solid fa-list-check"></i> तालुक्यामधील अलीकडील समस्या (Recent Issues in <?php echo htmlspecialchars($user_taluka); ?>)</h2>
            <a href="complaint_report.php" class="view-all-link">सर्व समस्या पहा <i class="fa-solid fa-angles-right"></i></a>
        </div>
        
        <div class="issues-table-card">
            <?php if (!empty($recent_issues)): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>समस्या क्र.</th>
                            <th>तपशील</th>
                            <th>विभाग</th>
                            <th>गाव / तालुका</th>
                            <th>तारीख</th>
                            <th>स्थिती</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_issues as $issue): ?>
                            <tr>
                                <td class="issue-no">#<?php echo htmlspecialchars($issue['issue_number']); ?></td>
                                <td class="issue-desc">
                                    <strong><?php echo htmlspecialchars(mb_strimwidth($issue['description'], 0, 80, "...")); ?></strong>
                                </td>
                                <td><span class="dept-badge"><?php echo htmlspecialchars($issue['department']); ?></span></td>
                                <td><?php echo htmlspecialchars($issue['village'] . ', ' . ($issue['taluka'] ?? 'Hingoli')); ?></td>
                                <td><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($issue['status'] ?? 'Open'); ?>">
                                        <?php echo translateStatus($issue['status'] ?? 'Open'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-feed">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>सध्या तालुक्यामध्ये कोणतीही समस्या नोंदवलेली नाही.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    /* Spacing wrapper for content */
    .main-content {
        margin-left: 260px;
        padding: 30px 24px;
        min-height: calc(100vh - var(--header-height));
        background-color: var(--bg-body);
        transition: margin-left var(--transition-normal), background-color var(--transition-normal);
    }
    
    .main-content.collapsed {
        margin-left: 72px;
    }
    
    .welcome-container {
        position: relative;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 40px;
        color: var(--text-primary);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 35px;
    }

    .welcome-info {
        max-width: 600px;
        z-index: 1;
    }

    .welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 6px 14px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    .welcome-title {
        font-family: var(--font-heading);
        font-size: 32px;
        font-weight: 800;
        line-height: 1.25;
        margin-bottom: 12px;
    }

    .welcome-title .highlight {
        color: var(--primary-light);
    }

    .welcome-desc {
        font-size: 15px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .welcome-actions {
        display: flex;
        gap: 14px;
        z-index: 1;
    }

    .welcome-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 14px;
        transition: all var(--transition-fast);
        cursor: pointer;
        border: none;
    }

    .welcome-btn-primary {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: #ffffff;
    }

    .welcome-btn-primary:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
    }

    .welcome-btn-secondary {
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }

    .welcome-btn-secondary:hover {
        background-color: var(--border-color);
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .stats-header h2 {
        font-family: var(--font-heading);
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stats-header h2 i {
        color: var(--primary-light);
        margin-right: 8px;
    }

    .refresh-indicator {
        font-size: 12px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .stat-icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-total .stat-icon-wrapper { background-color: rgba(30, 58, 138, 0.1); color: #1e3a8a; }
    .stat-open .stat-icon-wrapper { background-color: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .stat-progress .stat-icon-wrapper { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .stat-resolved .stat-icon-wrapper { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }

    .stat-info h3 {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .stat-number {
        font-family: var(--font-heading);
        font-size: 28px;
        font-weight: 800;
        color: var(--text-primary);
    }

    .section-container {
        margin-bottom: 40px;
    }

    .recent-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .view-all-link {
        font-size: 13px;
        color: var(--primary-light);
        font-weight: 600;
    }

    .issues-table-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        overflow-x: auto;
    }

    .recent-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .recent-table th, .recent-table td {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .recent-table th {
        font-family: var(--font-heading);
        font-weight: 700;
        color: var(--text-secondary);
        background-color: var(--bg-hover);
        font-size: 13px;
    }

    .recent-table td {
        font-size: 14px;
        color: var(--text-primary);
    }

    .issue-no {
        font-weight: 700;
        color: var(--primary-light);
    }

    .dept-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-weight: 600;
        background-color: var(--bg-hover);
        color: var(--text-secondary);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-open { background-color: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .badge-in-progress { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .badge-resolved { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-pending { background-color: rgba(220, 38, 38, 0.1); color: #dc2626; }

    .empty-feed {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }

    .empty-feed i {
        font-size: 32px;
        margin-bottom: 10px;
    }

    @media (max-width: 1024px) {
        .main-content { margin-left: 240px; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .main-content { margin-left: 72px; }
        .stats-grid { grid-template-columns: 1fr; }
        .welcome-container { padding: 20px; }
    }
</style>
