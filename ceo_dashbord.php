<?php
session_start();

include("include/config.php");

$conn = getDBConnection();

// COUNTS
$total = $conn->query("
SELECT COUNT(*) total FROM tbl_raiseissue
")->fetch_assoc()['total'];

$pending = $conn->query("
SELECT COUNT(*) total 
FROM tbl_raiseissue
WHERE status IN('Pending','Received')
")->fetch_assoc()['total'];

$resolved = $conn->query("
SELECT COUNT(*) total
FROM tbl_raiseissue
WHERE status='Resolved'
")->fetch_assoc()['total'];

$transfer = $conn->query("
SELECT COUNT(*) total
FROM tbl_raiseissue
WHERE status='Transfer'
")->fetch_assoc()['total'];

// DEPARTMENT REPORT
$deptQuery=$conn->query("
SELECT
u.department,
GROUP_CONCAT(DISTINCT r.department_head SEPARATOR ', ') as department_head,
COUNT(r.id) total,
IFNULL(SUM(r.status IN('Pending','Received')), 0) pending,
IFNULL(SUM(r.status='Resolved'), 0) resolved,
IFNULL(SUM(r.status='Transfer'), 0) transfer
FROM (SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '') u
LEFT JOIN tbl_raiseissue r ON u.department COLLATE utf8mb4_general_ci = r.department COLLATE utf8mb4_general_ci
GROUP BY u.department
ORDER BY total DESC
");

$departments=[];

while($row=$deptQuery->fetch_assoc())
{
    $departments[]=$row;
}

// REAL TRANSFER COUNT PER DEPARTMENT (from transfer table via issue_id)
$realTransferQuery = $conn->query("
    SELECT ri.department, COUNT(t.issue_id) AS real_transfer
    FROM transfer t
    INNER JOIN tbl_raiseissue ri ON ri.id = t.issue_id
    WHERE ri.department IS NOT NULL AND ri.department != ''
    GROUP BY ri.department
");
$realTransferMap = [];
if ($realTransferQuery) {
    while ($trow = $realTransferQuery->fetch_assoc()) {
        $realTransferMap[$trow['department']] = (int)$trow['real_transfer'];
    }
}
foreach ($departments as &$dept) {
    $dept['transfer'] = $realTransferMap[$dept['department']] ?? 0;
}
unset($dept);

// CHART DATA
$chart=$conn->query("
SELECT u.department, COUNT(r.id) as total
FROM (SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '') u
LEFT JOIN tbl_raiseissue r ON u.department COLLATE utf8mb4_general_ci = r.department COLLATE utf8mb4_general_ci
GROUP BY u.department
");

$deptName=[];
$deptCount=[];

while($row=$chart->fetch_assoc())
{
$deptName[]=$row['department'];
$deptCount[]=$row['total'];
}

// 6-MONTH TREND DATA
$last6Months = [];
for ($i = 5; $i >= 0; $i--) {
    $last6Months[] = date('M Y', strtotime("first day of -$i month"));
}
$trendQuery = $conn->query("
    SELECT 
        department,
        DATE_FORMAT(created_at, '%b %Y') as month_name,
        SUM(status IN('Pending','Received')) as pending,
        SUM(status='Resolved') as resolved
    FROM tbl_raiseissue
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY department, month_name
");
$trendRows = [];
if($trendQuery){
    while($row = $trendQuery->fetch_assoc()) {
        $trendRows[] = $row;
    }
}

// ISSUE DATA
$issues=$conn->query("
SELECT *
FROM tbl_raiseissue
ORDER BY id DESC
");

// Page title for header include
$page_title = 'CEO Dashboard';
$active_page = 'ceo_dashbord';
?>
<?php include __DIR__ . '/include/header.php'; ?>
<?php include __DIR__ . '/include/sidebar.php'; ?>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<!-- DataTables Bootstrap 5 Integration -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <style>
        :root {
            --primary-bg: var(--bg-body, #f4f7f6);
            --card-bg: var(--bg-card, #ffffff);
            --text-main: var(--text-primary, #2b3445);
            --text-muted: var(--text-secondary, #7d879c);
            --border-color: var(--border-color, #e3e9ef);
            --blue-gradient: linear-gradient(135deg, #3a7bd5, #3a6073);
            --orange-gradient: linear-gradient(135deg, #f2994a, #f2c94c);
            --green-gradient: linear-gradient(135deg, #11998e, #38ef7d);
            --purple-gradient: linear-gradient(135deg, #8e2de2, #4a00e0);
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 15px 30px rgba(0,0,0,0.12);
            --border-radius: 16px;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Header Styling */
        .dashboard-header {
            background: transparent;
            padding: 1.25rem 0.5rem 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .dashboard-header h2 {
            font-weight: 700;
            margin-bottom: 0.3rem;
            letter-spacing: -0.5px;
            font-size: 1.75rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h2 i {
            color: #3a7bd5;
        }

        .dashboard-header p {
            margin: 0;
            color: var(--text-muted);
            font-weight: 400;
            font-size: 0.9rem;
        }

        /* Stat Cards */
        .stat-card {
            padding: 1.5rem;
            border-radius: var(--border-radius);
            color: white;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            min-height: 120px;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card .icon-box {
            font-size: 2.5rem;
            opacity: 0.8;
            transition: 0.3s;
        }

        .stat-card:hover .icon-box {
            transform: scale(1.1);
            opacity: 1;
        }

        .stat-card h5 {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .stat-card h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .card-blue { background: var(--blue-gradient); }
        .card-orange { background: var(--orange-gradient); }
        .card-green { background: var(--green-gradient); }
        .card-purple { background: var(--purple-gradient); }

        /* General Card Box */
        .card-box {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: box-shadow 0.3s ease;
        }

        .card-box:hover {
            box-shadow: var(--shadow-md);
        }

        .card-box-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Chart Area */
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Department Tracking Cards */
        .dept-track-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .dept-track-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .dept-track-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        .dept-track-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
            line-height: 1.3;
        }

        .dept-track-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3a7bd5;
            background: rgba(58, 123, 213, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .dept-stats-grid {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
             align-items: stretch;
        }
 
        .dept-stat-box {
            text-align: center;
            padding: 0.75rem 0.4rem;
            border-radius: 8px;
            background: var(--bg-hover, #f8f9fa);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 80px;
            box-sizing: border-box;
            flex: 1;
        }
 
        .dept-stat-box.resolved { border-bottom: 3px solid #11998e; }
        .dept-stat-box.pending { border-bottom: 3px solid #f2994a; }
        .dept-stat-box.transfer { border-bottom: 3px solid #8e2de2; }
 
        .dept-stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
            color: var(--text-main);
        }
 
        .dept-stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.4px;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
        }
 
        .progress-wrapper {
            margin-top: auto;
        }
 
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
 
        .progress {
            height: 8px;
            border-radius: 50px;
            background-color: var(--border-color, #e9ecef);
            overflow: hidden;
        }
 
        .progress-bar {
            background: var(--green-gradient);
            border-radius: 50px;
        }
 
        /* Tables styling */
        .table-responsive {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-card, white);
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        
        table.dataTable thead th {
            background-color: var(--bg-hover, #f8f9fa);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color) !important;
            padding: 1rem !important;
        }

        table.dataTable tbody td {
            padding: 1rem !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        table.dataTable tbody tr:last-child td {
            border-bottom: none;
        }

        table.dataTable tbody tr:hover {
            background-color: rgba(0,0,0,0.015) !important;
        }

        /* Custom Badges */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-resolved {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
        
        .badge-transferred {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .badge-received {
            background-color: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #d8b4fe;
        }

        /* DataTables Buttons styling */
        .dt-buttons .dt-button {
            background: var(--bg-card, white) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 6px !important;
            color: var(--text-main) !important;
            padding: 0.4rem 1rem !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            margin-bottom: 0.5rem;
        }
 
        .dt-buttons .dt-button:hover {
            background: var(--bg-hover, #f8f9fa) !important;
            color: var(--text-main) !important;
            box-shadow: var(--shadow-sm) !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            outline: none;
            background-color: var(--bg-input, white);
            color: var(--text-main);
            transition: border-color 0.2s;
            margin-bottom: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #3a7bd5;
            box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.1);
        }
 
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 0.75rem 1rem;
        }
 
        .dataTables_wrapper .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }
 
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.3rem 0.8rem;
            margin: 0 0.1rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-card, white) !important;
            color: var(--text-main) !important;
            display: inline-block;
        }
 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--bg-hover, #f8f9fa) !important;
            border-color: #3a7bd5;
            color: #3a7bd5 !important;
        }

        /* Bottom info+paginate row layout */
        .dataTables_wrapper .row:last-child {
            border-top: 1px solid var(--border-color);
            margin: 0;
        }

        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1rem 0.25rem 0.75rem;
            }
            .dashboard-header h2 {
                font-size: 1.2rem;
            }
            .dashboard-header p {
                font-size: 0.8rem;
            }
            .stat-card {
                margin-bottom: 0.5rem;
                min-height: 100px;
            }
            .stat-card h1 {
                font-size: 2rem;
            }
            .chart-container {
                height: 260px;
            }
            .card-box {
                padding: 1rem;
            }
            .card-box-title {
                font-size: 1.05rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            /* Department tracking cards: responsive on mobile */
            .dept-track-card {
                padding: 0.85rem;
            }
            .dept-track-header {
                flex-wrap: wrap;
                gap: 0.4rem;
            }
            .dept-track-title {
                font-size: 0.95rem;
            }
            .dept-stats-grid {
                gap: 0.35rem;
            }
            .dept-stat-box {
                height: auto;
                min-height: 64px;
                padding: 0.5rem 0.25rem;
            }
            .dept-stat-value {
                font-size: 1rem;
            }
            .dept-stat-label {
                font-size: 0.62rem;
                white-space: normal;
                letter-spacing: 0.2px;
            }
            /* DataTable controls stack vertically */
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length {
                text-align: left !important;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
            }
            /* Pagination: center on mobile, prevent cutoff */
            .dataTables_wrapper .dataTables_paginate {
                justify-content: center !important;
                padding: 0.75rem 0.5rem !important;
                overflow-x: auto;
            }
            .dataTables_wrapper .dataTables_info {
                text-align: center !important;
                padding: 0.5rem 0.5rem 0 !important;
                font-size: 0.82rem;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.25rem 0.6rem !important;
                margin: 0 0.1rem !important;
                font-size: 0.82rem;
            }
            /* Info + paginate stack vertically */
            .dataTables_wrapper .row:last-child > [class*="col-"] {
                text-align: center !important;
            }
            .dataTables_wrapper .d-flex.justify-content-end {
                justify-content: center !important;
            }
            /* Trend chart select on mobile */
            #trendDeptSelect {
                width: 100% !important;
                min-width: unset !important;
                margin-top: 0.5rem;
            }
            .card-box-title.d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            /* DT Buttons wrap nicely */
            .dt-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            .dt-buttons .dt-button {
                padding: 0.35rem 0.7rem !important;
                font-size: 0.78rem !important;
                margin-bottom: 0 !important;
            }
        }

        @media (max-width: 480px) {
            .dashboard-header h2 {
                font-size: 1.15rem;
            }
            .stat-card h1 {
                font-size: 1.7rem;
            }
            .stat-card .icon-box {
                font-size: 1.8rem;
            }
            .chart-container {
                height: 220px;
            }
            /* Dept stat boxes: even more compact on very small screens */
            .dept-stats-grid {
                gap: 0.25rem;
            }
            .dept-stat-box {
                min-height: 58px;
                padding: 0.4rem 0.15rem;
            }
            .dept-stat-value {
                font-size: 0.9rem;
            }
            .dept-stat-label {
                font-size: 0.58rem;
                letter-spacing: 0;
            }
            .dept-track-total {
                font-size: 1.2rem;
                padding: 0.15rem 0.6rem;
            }
        }

        /* DataTables Responsive child row styling */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            background-color: #3a7bd5;
            border-color: #3a7bd5;
            box-shadow: 0 0 3px rgba(58,123,213,0.4);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
            background-color: #11998e;
            border-color: #11998e;
        }

        /* Child row expanded content styling */
        table.dataTable > tbody > tr.child ul.dtr-details {
            width: 100%;
        }

        table.dataTable > tbody > tr.child ul.dtr-details > li {
            border-bottom: 1px solid var(--border-color);
            padding: 0.5rem 0;
            font-size: 0.88rem;
        }

        table.dataTable > tbody > tr.child ul.dtr-details > li:last-child {
            border-bottom: none;
        }

        table.dataTable > tbody > tr.child ul.dtr-details .dtr-title {
            font-weight: 600;
            color: var(--text-muted);
            min-width: 110px;
        }
    </style>

<main class="main-content">
<div class="container-fluid p-3 p-md-4">

    <!-- Header Section -->
    <div class="dashboard-header">
        <h2><i class="fa-solid fa-chart-line me-2"></i> CEO Issue Monitoring Dashboard</h2>
        <p>Department-wise complaint tracking & analysis</p>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card card-blue">
                <div>
                    <h5>Total Issues</h5>
                    <h1><?=$total?></h1>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card card-orange">
                <div>
                    <h5>Pending</h5>
                    <h1><?=$pending?></h1>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card card-green">
                <div>
                    <h5>Resolved</h5>
                    <h1><?=$resolved?></h1>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card card-purple">
                <div>
                    <h5>Transferred</h5>
                    <h1><?=$transfer?></h1>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Breakdown Cards Section -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12">
            <h4 class="mb-2 text-primary" style="font-weight: 600;"><i class="fa-solid fa-layer-group me-2"></i> Department Breakdown</h4>
        </div>
        <?php foreach($departments as $d){ 
            $totalIssues = $d['total'] > 0 ? $d['total'] : 1; // avoid division by zero
            $resolvePercent = round(($d['resolved'] / $totalIssues) * 100);
        ?>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="dept-track-card">
                <div class="dept-track-header">
                    <h4 class="dept-track-title"><?=$d['department']?></h4>
                    <span class="dept-track-total">
                        <?=$d['total']?> 
                        <small style="font-size: 0.8rem; font-weight: 500; color: #3a7bd5;">Total</small>
                    </span>
                </div>
                
                <div class="dept-stats-grid">
                    <div class="dept-stat-box resolved">
                        <div class="dept-stat-value"><?=$d['resolved']?></div>
                        <div class="dept-stat-label">Resolved</div>
                    </div>
                    <div class="dept-stat-box pending">
                        <div class="dept-stat-value"><?=$d['pending']?></div>
                        <div class="dept-stat-label">Pending</div>
                    </div>
                    <div class="dept-stat-box transfer">
                        <div class="dept-stat-value"><?=$d['transfer']?></div>
                        <div class="dept-stat-label">Transfer</div>
                    </div>
                </div>
                
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Charts Section -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card-box h-100 mb-0">
                <div class="card-box-title">
                    <i class="fa-solid fa-chart-pie text-primary"></i> Department Analysis
                </div>
                <div class="chart-container">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card-box h-100 mb-0">
                <div class="card-box-title d-flex justify-content-between align-items-center mb-3">
                    <div><i class="fa-solid fa-chart-line text-primary"></i> 6-Month Status Analysis</div>
                    <select id="trendDeptSelect" class="form-select form-select-sm w-auto shadow-sm" style="min-width: 150px;">
                        <option value="All">All Departments</option>
                        <?php foreach($departments as $d) { ?>
                            <option value="<?=htmlspecialchars($d['department'])?>"><?=htmlspecialchars($d['department'])?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Table Section -->
    <div class="card-box mt-4">
        <div class="card-box-title">
            <i class="fa-solid fa-building text-primary"></i> Department Performance
        </div>
        <div class="table-responsive">
            <table id="deptTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Head</th>
                        <th>Total</th>
                        <th>Pending</th>
                        <th>Resolved</th>
                        <th>Transferred</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($departments as $d){ ?>
                    <tr>
                        <td><strong><?=$d['department']?></strong></td>
                        <td><?=$d['department_head']?></td>
                        <td><span class="badge bg-primary rounded-pill dept-filter-btn shadow-sm" style="cursor: pointer;" data-dept="<?=htmlspecialchars($d['department'])?>" data-status="all" title="View all issues"><?=$d['total']?></span></td>
                        <td><span class="badge bg-warning text-dark rounded-pill dept-filter-btn shadow-sm" style="cursor: pointer;" data-dept="<?=htmlspecialchars($d['department'])?>" data-status="Pending|Received" title="View pending issues"><?=$d['pending']?></span></td>
                        <td><span class="badge bg-success rounded-pill dept-filter-btn shadow-sm" style="cursor: pointer;" data-dept="<?=htmlspecialchars($d['department'])?>" data-status="Resolved" title="View resolved issues"><?=$d['resolved']?></span></td>
                        <td><span class="badge rounded-pill dept-filter-btn shadow-sm" style="cursor: pointer; background-color: #8e2de2; color: #fff;" data-dept="<?=htmlspecialchars($d['department'])?>" data-status="Transferred" title="View transferred issues"><?=$d['transfer']?></span></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Issues Table Section -->
    <div class="card-box mt-4 mb-5">
        <div class="card-box-title d-flex justify-content-between align-items-center">
            <div><i class="fa-solid fa-list-check text-primary"></i> All Issues</div>
            <button class="btn btn-sm btn-outline-danger shadow-sm" id="clearIssueFilterBtn" style="display:none; font-size: 0.85rem;">
                <i class="fa-solid fa-filter-circle-xmark me-1"></i> Clear Filters
            </button>
        </div>
        <div class="table-responsive">
            <table id="issueTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Head</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($i=$issues->fetch_assoc()){ ?>
                    <tr>
                        <td><strong><?=$i['issue_number'] ?? $i['id']?></strong></td>
                        <td class="text-nowrap"><?=isset($i['created_at']) ? date('d M Y', strtotime($i['created_at'])) : '-'?></td>
                        <td><?=$i['department']?></td>
                        <td><?=$i['department_head']?></td>
                        <td><div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?=htmlspecialchars($i['description'] ?? '')?>"><?=htmlspecialchars($i['description'] ?? '')?></div></td>
                        <td>
                            <?php
                                if($i['status']=="Resolved") {
                                    echo "<span class='status-badge badge-resolved'>Resolved</span>";
                                } elseif($i['status']=="Transferred") {
                                    echo "<span class='status-badge badge-transferred'>Transferred</span>";
                                } elseif($i['status']=="Pending") {
                                    echo "<span class='status-badge badge-pending'>Pending</span>";
                                } elseif($i['status']=="Received") {
                                    echo "<span class='status-badge badge-received'>Received</span>";
                                } else {
                                    echo "<span class='badge bg-secondary'>".$i['status']."</span>";
                                }
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /container-fluid -->

<?php include __DIR__ . '/include/footer.php'; ?>

</main><!-- /main-content -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function(){
    // Initialize DataTables
    const dtConfig = {
        dom: '<"row align-items-center p-3 border-bottom mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6 text-md-end"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row align-items-center p-3 mt-1"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
        buttons: [
            { extend: 'excelHtml5', className: 'dt-button', text: '<i class="fa-solid fa-file-excel text-success me-1"></i> Excel' },
            { extend: 'csvHtml5', className: 'dt-button', text: '<i class="fa-solid fa-file-csv text-info me-1"></i> CSV' },
            { extend: 'print', className: 'dt-button', text: '<i class="fa-solid fa-print text-secondary me-1"></i> Print' }
        ],
        pageLength: 10,
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    };

    // Issue table needs its own config (more columns to hide on mobile)
    const issueTableConfig = {
        dom: '<"row align-items-center p-3 border-bottom mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6 text-md-end"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row align-items-center p-3 mt-1"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
        buttons: [
            { extend: 'excelHtml5', className: 'dt-button', text: '<i class="fa-solid fa-file-excel text-success me-1"></i> Excel' },
            { extend: 'csvHtml5', className: 'dt-button', text: '<i class="fa-solid fa-file-csv text-info me-1"></i> CSV' },
            { extend: 'print', className: 'dt-button', text: '<i class="fa-solid fa-print text-secondary me-1"></i> Print' }
        ],
        pageLength: 10,
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 },
            // On mobile: hide Head & Description columns first to save space
            { responsivePriority: 1, targets: 0 },  // No
            { responsivePriority: 2, targets: 5 },  // Status
            { responsivePriority: 3, targets: 2 },  // Department
            { responsivePriority: 4, targets: 1 },  // Date
            { responsivePriority: 5, targets: 4 },  // Description
            { responsivePriority: 6, targets: 3 }   // Head (hide first)
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    };

    $('#deptTable').DataTable(dtConfig);
    const issueTable = $('#issueTable').DataTable(issueTableConfig);

    // Filter issues table when clicking department stats
    $(document).on('click', '.dept-filter-btn', function() {
        const deptName = $(this).data('dept');
        const status = $(this).data('status');
        
        issueTable.search(''); // clear global search
        issueTable.column(2).search('^' + $.fn.dataTable.util.escapeRegex(deptName) + '$', true, false);
        
        if (status === 'all') {
            issueTable.column(5).search('');
        } else {
            issueTable.column(5).search('^(' + status + ')$', true, false);
        }
        
        issueTable.draw();
        $('#clearIssueFilterBtn').fadeIn();
        
        // Scroll to the All Issues section smoothly
        $('html, body').animate({
            scrollTop: $("#issueTable").closest('.card-box').offset().top - 80
        }, 500);
    });

    // Clear filters manually
    $('#clearIssueFilterBtn').on('click', function() {
        issueTable.search('').columns().search('').draw();
        $(this).fadeOut();
    });

    // Chart Global Defaults
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = "#7d879c";

    // Department Horizontal Bar Chart
    const deptCtx = document.getElementById('deptChart');
    if(deptCtx) {
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?=json_encode($deptName, JSON_UNESCAPED_UNICODE)?>,
                datasets: [{
                    label: 'Total Issues',
                    data: <?=json_encode($deptCount)?>,
                    backgroundColor: [
                        'rgba(58, 123, 213, 0.8)', 'rgba(242, 153, 74, 0.8)', 'rgba(17, 153, 142, 0.8)', 
                        'rgba(142, 45, 226, 0.8)', 'rgba(231, 76, 60, 0.8)', 'rgba(52, 73, 94, 0.8)', 
                        'rgba(241, 196, 15, 0.8)', 'rgba(26, 188, 156, 0.8)', 'rgba(232, 67, 147, 0.8)',
                        'rgba(108, 92, 231, 0.8)', 'rgba(0, 184, 148, 0.8)', 'rgba(253, 121, 168, 0.8)',
                        'rgba(214, 48, 49, 0.8)', 'rgba(9, 132, 227, 0.8)', 'rgba(225, 112, 85, 0.8)'
                    ],
                    borderColor: [
                        '#3a7bd5', '#f2994a', '#11998e', '#8e2de2', 
                        '#e74c3c', '#34495e', '#f1c40f', '#1abc9c', '#e84393',
                        '#6c5ce7', '#00b894', '#fd79a8', '#d63031', '#0984e3', '#e17055'
                    ],
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y', // This converts the bar chart to a horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // No need for legend, names are on the Y axis
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Total Issues: ' + context.parsed.x;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#e3e9ef', drawBorder: false }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            autoSkip: false // Ensures all department names are visible
                        }
                    }
                }
            }
        });
    }

    // 6-Month Status Trend Chart
    const rawTrendData = <?=json_encode($trendRows)?>;
    const monthsLabels = <?=json_encode($last6Months)?>;

    function getTrendData(dept) {
        let pendingData = Array(6).fill(0);
        let resolvedData = Array(6).fill(0);
        
        rawTrendData.forEach(row => {
            if (dept === 'All' || row.department === dept) {
                let monthIndex = monthsLabels.indexOf(row.month_name);
                if (monthIndex !== -1) {
                    pendingData[monthIndex] += parseInt(row.pending) || 0;
                    resolvedData[monthIndex] += parseInt(row.resolved) || 0;
                }
            }
        });
        return { pending: pendingData, resolved: resolvedData };
    }

    const statusCtx = document.getElementById('statusChart');
    let statusChart;
    if(statusCtx) {
        const initialData = getTrendData('All');
        
        statusChart = new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: monthsLabels,
                datasets: [
                    {
                        label: 'Pending',
                        data: initialData.pending,
                        backgroundColor: 'rgba(242, 153, 74, 0.8)',
                        borderColor: '#f2994a',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Resolved',
                        data: initialData.resolved,
                        backgroundColor: 'rgba(17, 153, 142, 0.8)',
                        borderColor: '#11998e',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 8 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e3e9ef', drawBorder: false }
                    }
                }
            }
        });
        
        $('#trendDeptSelect').on('change', function() {
            const dept = $(this).val();
            const newData = getTrendData(dept);
            statusChart.data.datasets[0].data = newData.pending;
            statusChart.data.datasets[1].data = newData.resolved;
            statusChart.update();
        });
    }
});
</script>

</body>
</html>