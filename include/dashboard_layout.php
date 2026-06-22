<?php
if (!isset($dashboard_title)) {
    $dashboard_title = 'Dashboard';
}
if (!isset($dashboard_description)) {
    $dashboard_description = 'Welcome to your portal dashboard.';
}
if (!isset($dashboard_icon)) {
    $dashboard_icon = 'fa-solid fa-chart-pie';
}
?>
<main class="main-content">
    <style>
        .main-content {
            margin-left: 260px;
            padding: 30px 24px;
            min-height: calc(100vh - var(--header-height));
            background-color: var(--bg-body);
            transition: margin-left var(--transition-normal), background-color var(--transition-normal);
        }

        @media screen and (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 220px;
            }

            .main-content.collapsed {
                margin-left: 72px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-left: 220px;
            }
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

        .welcome-bg-overlay {
            display: none;
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
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .welcome-title {
            font-family: var(--font-heading);
            font-size: 32px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 12px;
            color: var(--text-primary);
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
            flex-shrink: 0;
        }

        .welcome-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 14px;
            font-family: var(--font-heading);
            transition: all var(--transition-fast);
            cursor: pointer;
            border: none;
        }

        .welcome-btn-primary {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: #ffffff;
            box-shadow: var(--shadow-sm);
        }

        .welcome-btn-primary:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
            font-weight: 500;
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
            position: relative;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal), border-color var(--transition-fast);
            overflow: hidden;
        }

        .stat-card-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(80px circle at 0px 0px, rgba(var(--primary-rgb), 0.08), transparent 80%);
            opacity: 0;
            transition: opacity var(--transition-normal);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .stat-card:hover .stat-card-glow {
            opacity: 1;
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

        .stat-total .stat-icon-wrapper {
            background-color: rgba(30, 58, 138, 0.1);
            color: #1e3a8a;
        }

        .stat-open .stat-icon-wrapper {
            background-color: rgba(2, 132, 199, 0.1);
            color: #0284c7;
        }

        .stat-progress .stat-icon-wrapper {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-resolved .stat-icon-wrapper {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

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
            line-height: 1.1;
        }

        .section-container {
            margin-bottom: 40px;
        }

        .section-title {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary-light);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .action-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal), border-color var(--transition-fast);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .action-card-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 16px;
        }

        .bg-blue {
            background-color: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }

        .bg-purple {
            background-color: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .bg-emerald {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .bg-amber {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .action-card h4 {
            font-family: var(--font-heading);
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .action-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            flex-grow: 1;
            margin-bottom: 16px;
        }

        .action-card-link {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap var(--transition-fast);
        }

        .action-card:hover .action-card-link {
            gap: 10px;
        }

        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .view-all-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 4px;
            transition: gap var(--transition-fast);
        }

        .view-all-link:hover {
            gap: 8px;
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
            font-size: 14px;
        }

        .recent-table th {
            background-color: var(--bg-hover);
            padding: 14px 20px;
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }

        .recent-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .recent-table tbody tr:last-child td {
            border-bottom: none;
        }

        .recent-table tbody tr:hover {
            background-color: var(--bg-hover);
        }

        .issue-no {
            font-weight: 700;
            color: var(--primary-light);
            font-family: monospace;
        }

        .issue-desc strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .dept-badge {
            display: inline-block;
            padding: 4px 10px;
            background-color: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-open {
            background-color: rgba(2, 132, 199, 0.12);
            color: #0284c7;
        }

        .badge-pending {
            background-color: rgba(234, 88, 12, 0.12);
            color: #ea580c;
        }

        .badge-in-progress {
            background-color: rgba(245, 158, 11, 0.12);
            color: #d97706;
        }

        .badge-resolved {
            background-color: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .empty-feed {
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-feed i {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .empty-feed p {
            font-size: 14px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .welcome-container {
                padding: 24px;
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-title {
                font-size: 26px;
            }

            .welcome-actions {
                width: 100%;
                flex-direction: column;
            }

            .welcome-btn {
                width: 100%;
                justify-content: center;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="welcome-container">
        <div class="welcome-bg-overlay"></div>
        <div class="welcome-info">
            <span class="welcome-badge">
                <i class="fa-solid fa-circle-check"></i> जिल्हा परिषद हिंगोली - आय.डी.पी.
            </span>
            <h1 class="welcome-title">
                <span class="highlight"><?php echo htmlspecialchars(preg_replace('/^Shri\.\s+/i', '', $user_display_name)); ?></span>
            </h1>
            <p class="welcome-desc"><?php echo htmlspecialchars($dashboard_description); ?></p>
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

    <div class="stats-header">
        <h2><i class="<?php echo htmlspecialchars($dashboard_icon); ?>"></i> <?php echo htmlspecialchars($dashboard_title); ?> आकडेवारी</h2>
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

    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-gears"></i> जलद प्रवेश</h2>
        <div class="action-grid">
            <a href="issueform.php" class="action-card">
                <div class="action-card-icon bg-blue">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h4>समस्या नोंदणी फॉर्म</h4>
                <p>काही नवीन तांत्रिक किंवा प्रशासकीय समस्या असल्यास येथे फॉर्म भरा आणि फोटो अपलोड करा.</p>
                <span class="action-card-link">उघडा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
            <a href="complaint_report.php" class="action-card">
                <div class="action-card-icon bg-purple">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <h4>तक्रार अहवाल आणि ट्रॅकिंग</h4>
                <p>नोंदवलेल्या तक्रारींचा सविस्तर अहवाल पहा, फिल्टर करा आणि CSV फाईल निर्यात करा.</p>
                <span class="action-card-link">तपासा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
            <a href="create_user.php" class="action-card">
                <div class="action-card-icon bg-emerald">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h4>नवीन अधिकारी नोंदणी</h4>
                <p>पोर्टलवर नवीन अधिकारी किंवा कर्मचाऱ्यांची माहिती समाविष्ट करून खाते तयार करा.</p>
                <span class="action-card-link">नोंदणी करा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
            <a href="forgetpassward.php" class="action-card">
                <div class="action-card-icon bg-amber">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h4>खाते सुरक्षा आणि पासवर्ड</h4>
                <p>तुमच्या लॉगिन सुरक्षिततेसाठी पासवर्ड बदला किंवा इतर सुरक्षा पर्याय निवडा.</p>
                <span class="action-card-link">बदला <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
        </div>
    </div>

    <div class="section-container">
        <div class="recent-header">
            <h2 class="section-title"><i class="fa-solid fa-list-check"></i> अलीकडील नोंदवलेल्या समस्या</h2>
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
                                <td class="issue-desc"><strong><?php echo htmlspecialchars(mb_strimwidth($issue['description'], 0, 80, '...')); ?></strong></td>
                                <td><span class="dept-badge"><?php echo htmlspecialchars($issue['department']); ?></span></td>
                                <td><?php echo htmlspecialchars($issue['village'] . ', ' . ($issue['taluka'] ?? 'Hingoli')); ?></td>
                                <td><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></td>
                                <td><span class="status-badge <?php echo getStatusBadgeClass($issue['status'] ?? 'Open'); ?>"><?php echo translateStatus($issue['status'] ?? 'Open'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-feed">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>सध्या नोंदवलेली कोणतीही समस्या उपलब्ध नाही.</p>
                    <a href="issueform.php" class="btn btn-primary" style="margin-top:10px;">पहिली समस्या नोंदवा</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
