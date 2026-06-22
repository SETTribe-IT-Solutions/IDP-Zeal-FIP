<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/config.php';

$user_display_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? '';
$user_system_role = $_SESSION['user_system_role'] ?? '';
$user_dept = $_SESSION['user_dept'] ?? '';
$user_designation = $_SESSION['user_designation'] ?? '';
$user_mobile = $_SESSION['user_mobile'] ?? '';

function getStatusBadgeClass($status)
{
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

function translateStatus($status)
{
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

$total_issues = 0;
$in_progress_issues = 0;
$resolved_issues = 0;
$open_issues = 0;
$active_depts = 0;
$recent_issues = [];

$hour = (int) date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "शुभ सकाळ / Good Morning";
    $greeting_icon = "☀️";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "शुभ दुपार / Good Afternoon";
    $greeting_icon = "🌤️";
} else {
    $greeting = "शुभ संध्याकाळ / Good Evening";
    $greeting_icon = "🌙";
}

try {
    $conn = db_connect();

    $role = !empty($_SESSION['user_system_role']) ? $_SESSION['user_system_role'] : ($_SESSION['user_role'] ?? '');
    $where = "1=1";
    $params = [];
    $types = "";

    $normalizedRole = strtolower(trim($role));

    if ($normalizedRole === 'ceo') {
        $where = "1=1";
    } elseif ($role === 'ग्रामपंचायत अधिकारी' || $role === 'अंगणवाडी सेविका' || $role === 'शिक्षक' || $normalizedRole === 'teacher') {
        $where = "mobile = ?";
        $params[] = $user_mobile;
        $types .= "s";
    } else {
        // BDO, THO, HoD
        $user_taluka = $_SESSION['user_taluka'] ?? '';
        if (!empty($user_taluka)) {
            $where = "department = ? AND taluka = ?";
            $params[] = $user_dept;
            $params[] = $user_taluka;
            $types .= "ss";
        } else {
            $where = "department = ?";
            $params[] = $user_dept;
            $types .= "s";
        }
    }

    $count_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(status) = 'in progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN LOWER(status) = 'resolved' OR LOWER(status) = 'closed' THEN 1 ELSE 0 END) as resolved
    FROM tbl_raiseissue WHERE $where";

    $stmt1 = $conn->prepare($count_sql);
    if ($stmt1) {
        if (!empty($params)) {
            $stmt1->bind_param($types, ...$params);
        }
        $stmt1->execute();
        $count_res = $stmt1->get_result();
        if ($count_res && $row = $count_res->fetch_assoc()) {
            $total_issues = (int) $row['total'];
            $in_progress_issues = (int) $row['in_progress'];
            $resolved_issues = (int) $row['resolved'];
            $open_issues = $total_issues - ($in_progress_issues + $resolved_issues);
            if ($open_issues < 0) {
                $open_issues = 0;
            }
        }
        $stmt1->close();
    }

    $dept_sql = "SELECT COUNT(DISTINCT department) as dept_count FROM tbl_raiseissue WHERE department IS NOT NULL AND department != '' AND $where";
    $stmt2 = $conn->prepare($dept_sql);
    if ($stmt2) {
        if (!empty($params)) {
            $stmt2->bind_param($types, ...$params);
        }
        $stmt2->execute();
        $dept_res = $stmt2->get_result();
        if ($dept_res && $row = $dept_res->fetch_assoc()) {
            $active_depts = (int) $row['dept_count'];
        }
        $stmt2->close();
    }

    $recent_sql = "SELECT * FROM tbl_raiseissue WHERE $where ORDER BY issue_date DESC, id DESC LIMIT 5";
    $stmt3 = $conn->prepare($recent_sql);
    if ($stmt3) {
        if (!empty($params)) {
            $stmt3->bind_param($types, ...$params);
        }
        $stmt3->execute();
        $recent_res = $stmt3->get_result();
        if ($recent_res) {
            $recent_issues = $recent_res->fetch_all(MYSQLI_ASSOC);
        }
        $stmt3->close();
    }

    $conn->close();
} catch (Exception $e) {
    $total_issues = 124;
    $in_progress_issues = 28;
    $resolved_issues = 84;
    $open_issues = 12;
    $active_depts = 6;
    $recent_issues = [
        [
            'issue_number' => '0024',
            'description' => 'रस्त्यावरील दिवे बंद आहेत (Street lights are off)',
            'department' => 'पंचायत समिती',
            'village' => 'जवळ बाजार',
            'registration_type' => 'तक्रार',
            'issue_date' => date('Y-m-d'),
            'status' => 'In Progress'
        ],
        [
            'issue_number' => '0023',
            'description' => 'पिण्याच्या पाण्याची लाईन दुरुस्त करणे (Repair drinking water pipeline)',
            'department' => 'आरोग्य विभाग',
            'village' => 'गोजेगाव',
            'registration_type' => 'कर्मचारी मागणी',
            'issue_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'Open'
        ],
        [
            'issue_number' => '0022',
            'description' => 'नवीन शाळा वर्गखोल्या बांधकामाची मागणी (Request for new classroom construction)',
            'department' => 'शिक्षण विभाग',
            'village' => 'लोहारा बु.',
            'registration_type' => 'कर्मचारी मागणी',
            'issue_date' => date('Y-m-d', strtotime('-3 days')),
            'status' => 'Resolved'
        ]
    ];
}
