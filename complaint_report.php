<?php
// complaint.php - User Complaint Records Page
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'include/config.php';

$can_perform_actions = true;
$role = $_SESSION['role'] ?? '';

$conn = db_connect();
$complaints = [];
$dbError = '';

$user_village = '';
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT village FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $user_village = trim($row['village'] ?? '');
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];
    $issue_number = trim($_POST['issue_number'] ?? '');

    if ($issue_number === '') {
        echo json_encode(['success' => false, 'message' => 'समस्या क्रमांक आवश्यक आहे.'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $villageClause = '';
    if (!empty($user_village)) {
        $villageClause = ' AND village = ?';
    }

    if ($action === 'delete_complaint') {
        $stmt = $conn->prepare("DELETE FROM tbl_raiseissue WHERE issue_number = ?" . $villageClause);
        if ($stmt) {
            if (!empty($user_village)) {
                $stmt->bind_param("ss", $issue_number, $user_village);
            } else {
                $stmt->bind_param("s", $issue_number);
            }
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            echo json_encode([
                'success' => $success && $affected > 0,
                'message' => ($success && $affected > 0) ? 'तक्रार यशस्वीरित्या हटवली.' : 'तक्रार सापडली नाही किंवा हटवता आली नाही.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'डेटाबेस त्रुटी: ' . $conn->error], JSON_UNESCAPED_UNICODE);
        }
        $conn->close();
        exit;
    }

    if ($action === 'update_complaint') {
        $description = trim($_POST['description'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $department_head = trim($_POST['department_head'] ?? '');
        $village = trim($_POST['village'] ?? '');
        $taluka = trim($_POST['taluka'] ?? '');
        $registration_type = trim($_POST['registration_type'] ?? '');
        $issue_date = trim($_POST['issue_date'] ?? '');
        $status = trim($_POST['status'] ?? '');

        if ($description === '' || $department === '' || $village === '' || $taluka === '' || $registration_type === '' || $issue_date === '' || $status === '') {
            echo json_encode(['success' => false, 'message' => 'कृपया सर्व आवश्यक फील्ड भरा.'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        $stmt = $conn->prepare("UPDATE tbl_raiseissue SET description = ?, department = ?, department_head = ?, village = ?, taluka = ?, registration_type = ?, issue_date = ?, status = ? WHERE issue_number = ?" . $villageClause);
        if ($stmt) {
            if (!empty($user_village)) {
                $stmt->bind_param("ssssssssss", $description, $department, $department_head, $village, $taluka, $registration_type, $issue_date, $status, $issue_number, $user_village);
            } else {
                $stmt->bind_param("sssssssss", $description, $department, $department_head, $village, $taluka, $registration_type, $issue_date, $status, $issue_number);
            }
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'तक्रार यशस्वीरित्या अद्यतनित केली.' : 'तक्रार अद्यतनित करता आली नाही.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'डेटाबेस त्रुटी: ' . $conn->error], JSON_UNESCAPED_UNICODE);
        }
        $conn->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'अवैध कृती.'], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

$sql = "SELECT
            issue_number,
            photo,
            description,
            department,
            department_head,
            village,
            taluka,
            registration_type,
            issue_date,
            status
        FROM tbl_raiseissue";

if (!empty($user_village)) {
    $sql .= " WHERE village = ?";
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($user_village)) {
        $stmt->bind_param("s", $user_village);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $complaints[] = $row;
        }
        $result->free();
    } else {
        $dbError = $conn->error;
    }
    $stmt->close();
} else {
    $dbError = $conn->error;
}

// Fetch distinct departments from users table
$distinct_departments = [];
$dept_res = $conn->query("SELECT DISTINCT department AS dept FROM users WHERE department IS NOT NULL AND department != ''");
if ($dept_res) {
    while ($row = $dept_res->fetch_assoc()) {
        $dept = trim($row['dept']);
        if ($dept !== '' && !in_array($dept, $distinct_departments)) {
            $distinct_departments[] = $dept;
        }
    }
}
sort($distinct_departments);

// Fetch department to designation mappings from users table
$dept_designations = [];
$map_res = $conn->query("SELECT DISTINCT department, designation FROM users WHERE department IS NOT NULL AND department != '' AND designation IS NOT NULL AND designation != '' ORDER BY designation ASC");
if ($map_res) {
    while ($row = $map_res->fetch_assoc()) {
        $dept = trim($row['department']);
        $desg = trim($row['designation']);
        if (!isset($dept_designations[$dept])) {
            $dept_designations[$dept] = [];
        }
        $dept_designations[$dept][] = $desg;
    }
}

$conn->close();

$can_perform_actions = false;
if (in_array(strtolower($role), ['ceo', 'bdo', 'tho', 'hod'])) {
    $can_perform_actions = true;
}

$total_complaints = count($complaints);
$pending_count = 0;
$resolved_count = 0;
$transferred_count = 0;
foreach ($complaints as $complaint) {
    $normalizedStatus = strtolower(trim((string) ($complaint['status'] ?? '')));
    if ($normalizedStatus === 'pending') {
        $pending_count++;
    } elseif ($normalizedStatus === 'resolved') {
        $resolved_count++;
    } elseif (in_array($normalizedStatus, ['transfer', 'transferred', 'transfered'], true)) {
        $transferred_count++;
    }
}

function badgeClass($status)
{
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'pending';
        case 'resolved':
            return 'resolved';
        case 'transfer':
        case 'transferred':
            return 'transferred';
        case 'rejected':
            return 'rejected';
        default:
            return 'default';
    }
}

function formatDate($dateString)
{
    $timestamp = strtotime($dateString);
    return $timestamp ? date('d F Y', $timestamp) : htmlspecialchars($dateString);
}

function isEditDisabled($status)
{
    $normalized = strtolower(trim((string) $status));
    return in_array($normalized, ['resolved', 'transferred', 'transfered'], true);
}

function isDeleteDisabled($status)
{
    return isEditDisabled($status);
}
?>

<?php include('include/header.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<?php include('include/sidebar.php'); ?>

<main class="main-content">
    
    <style>
        /* =========================================
           EXACT UI RE-CREATION FOR COMPLAINT REPORT
           ========================================= */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        .main-content {
            background: #eef5ff;
            padding: 24px 32px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        /* 1. Header & New Button */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-box {
            background: #1e88e5;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .icon-box svg {
            width: 24px;
            height: 24px;
            stroke-width: 2;
        }

        .page-title h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0a2540;
            margin: 0;
        }

        .page-title p {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0 0 0;
        }

        .btn-primary-new {
            background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
            border: none;
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(30, 136, 229, 0.3);
        }
        .btn-primary-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(30, 136, 229, 0.4);
        }

        /* 2. Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 22px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .stat-card h4 {
            font-size: 13px;
            font-weight: 600;
            color: #4a5b6e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px 0;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .stat-bg-shape {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.6;
            pointer-events: none;
        }

        .bg-blue { background: #dbeafe; }
        .bg-orange { background: #fee2e2; }
        .bg-cyan { background: #cffafe; }
        .bg-green { background: #dcfce7; }

        /* 3. Search & Filters */
        .filters-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .search-group {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-group input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.2s;
            color: #334155;
        }

        .search-group input:focus {
            outline: none;
            border-color: #1e88e5;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }

        .search-icon-pos {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .select-group {
            display: flex;
            gap: 12px;
        }

        .custom-select {
            appearance: none;
            padding: 10px 32px 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2 4L6 8L10 4' stroke='%23475569' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 12px center;
            font-size: 14px;
            color: #1e293b;
            cursor: pointer;
            min-width: 130px;
            background-color: #fafcff;
        }

        .custom-select:focus {
            outline: none;
            border-color: #1e88e5;
        }

        .btn-reset {
            background: transparent;
            border: 1px solid #e2e8f0;
            padding: 9px 18px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-reset:hover { background: #f1f5f9; }

        .btn-export {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-export:hover { background: #1d4ed8; }

        /* 4. Table */
        .table-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 0 0 8px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .complaints-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .complaints-table thead th {
            background: #1e3a8a;
            color: #ffffff;
            padding: 14px 18px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: none;
            text-align: left;
            white-space: nowrap;
        }

        .complaints-table thead th.sorting:after,
        .complaints-table thead th.sorting_asc:after,
        .complaints-table thead th.sorting_desc:after {
            color: #a5b4fc !important;
        }

        .complaints-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .complaints-table tbody tr:last-child td {
            border-bottom: none;
        }

        .complaints-table tbody tr:hover {
            background-color: #f8faff;
        }

        .complaint-id {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 13px;
        }

        .photo-preview {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .photo-preview:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .photo-placeholder {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
            color: #94a3b8;
            font-size: 10px;
        }

        .subject-text {
            font-weight: 500;
            color: #0f172a;
        }

        .badge-type-custom {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.pending { background: #fef3c7; color: #b45309; }
        .status-badge.pending:before { content:''; width:6px; height:6px; background:#b45309; border-radius:50%; display:inline-block; }
        
        .status-badge.transferred { background: #dbeafe; color: #1e40af; }
        .status-badge.transferred:before { content:''; width:6px; height:6px; background:#1e40af; border-radius:50%; display:inline-block; }
        
        .status-badge.resolved { background: #d1fae5; color: #047857; }
        .status-badge.resolved:before { content:''; width:6px; height:6px; background:#047857; border-radius:50%; display:inline-block; }
        
        .status-badge.default { background: #f1f5f9; color: #475569; }
        .status-badge.default:before { content:''; width:6px; height:6px; background:#475569; border-radius:50%; display:inline-block; }

        .action-group {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-icon-action {
            background: transparent;
            border: none;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-icon-action svg { width: 18px; height: 18px; }
        .btn-icon-action.edit { color: #2563eb; }
        .btn-icon-action.edit:hover { background: #eff6ff; }
        
        .btn-icon-action.delete { color: #dc2626; }
        .btn-icon-action.delete:hover { background: #fef2f2; }

        .btn-icon-action:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Photo Popup Modal */
        .photo-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .photo-popup.active {
            display: flex;
        }

        .photo-popup .popup-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: transparent;
        }

        .photo-popup .popup-content img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            display: block;
            border-radius: 8px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            animation: zoomIn 0.3s ease;
        }

        .photo-popup .popup-close {
            position: absolute;
            top: -50px;
            right: 0;
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 24px;
            cursor: pointer;
            transition: 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .photo-popup .popup-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .photo-popup .popup-caption {
            position: absolute;
            bottom: -45px;
            left: 0;
            right: 0;
            text-align: center;
            color: #fff;
            font-size: 14px;
            padding: 10px 16px;
            background: rgba(0,0,0,0.4);
            border-radius: 8px;
            backdrop-filter: blur(4px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Datatables Override */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_paginate {
            padding: 14px 18px;
        }
        .dataTables_wrapper .dataTables_length { color: #475569; font-size: 13px; }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            margin-left: 8px;
        }
        .dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: #2563eb; }
        .dataTables_wrapper .dataTables_info { font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            background: #fff !important;
            color: #475569 !important;
            font-weight: 500;
            font-size: 13px;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #1e3a8a !important;
            color: #fff !important;
            border-color: #1e3a8a !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #1e3a8a !important;
            color: #fff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5; cursor: default;
        }

        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .page-header { flex-direction: column; align-items: stretch; gap: 16px; }
            .stats-container { grid-template-columns: repeat(2, 1fr); }
            .filters-wrapper { flex-direction: column; align-items: stretch; }
            .filter-actions { flex-direction: column; }
            .select-group { flex-direction: column; width: 100%; }
            .custom-select { width: 100%; }
            .btn-export, .btn-reset { justify-content: center; width: 100%; }
            .photo-popup .popup-close {
                top: 10px;
                right: 10px;
                background: rgba(0,0,0,0.5);
            }
            .photo-popup .popup-caption {
                bottom: 10px;
                font-size: 12px;
                padding: 6px 12px;
            }
        }
        @media (max-width: 480px) {
            .stats-container { grid-template-columns: 1fr; }
        }
    </style>

    <!-- Photo Popup Modal -->
    <div id="photoPopup" class="photo-popup" onclick="closePhotoPopup()">
        <div class="popup-content" onclick="event.stopPropagation();">
            <button class="popup-close" onclick="closePhotoPopup()">✕</button>
            <img id="popupImage" src="" alt="Complaint Photo">
            <div class="popup-caption" id="popupCaption">तक्रार फोटो</div>
        </div>
    </div>

    <!-- 1. Header -->
    <div class="page-header">
        <div class="page-header-left">
            <div class="icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="page-title">
                <h1>माझी तक्रारी</h1>
                <p>आपल्या सर्व तक्रारीचे रेकॉर्ड पहा आणि व्यवस्थापित करा</p>
            </div>
        </div>
        <button class="btn-primary-new" onclick="openNewComplaintForm()">
            <span style="font-size:20px; line-height:1;">+</span> नवीन तक्रार दाखल करा
        </button>
    </div>

    <!-- 2. Stats Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h4>एकूण</h4>
            <div class="number"><?php echo $total_complaints; ?></div>
            <div class="stat-bg-shape bg-blue"></div>
        </div>
        <div class="stat-card">
            <h4>PENDING</h4>
            <div class="number"><?php echo $pending_count; ?></div>
            <div class="stat-bg-shape bg-orange"></div>
        </div>
        <div class="stat-card">
            <h4>TRANSFER</h4>
            <div class="number"><?php echo $transferred_count; ?></div>
            <div class="stat-bg-shape bg-cyan"></div>
        </div>
        <div class="stat-card">
            <h4>RESOLVED</h4>
            <div class="number"><?php echo $resolved_count; ?></div>
            <div class="stat-bg-shape bg-green"></div>
        </div>
    </div>

    <!-- 3. Search & Filters -->
    <div class="filters-wrapper">
        <div class="search-group">
            <span class="search-icon-pos">🔍</span>
            <input type="text" id="searchInput" placeholder="समस्या क्रमांक, विषय किंवा गाव वारून शोधा...">
        </div>
        <div class="filter-actions">
            <div class="select-group">
                <select id="statusFilter" class="custom-select">
                    <option value="">🟢 एकूण</option>
                    <option value="Pending">🟡 प्रलंबित</option>
                    <option value="Resolved">🟣 निराकृत</option>
                    <option value="Transfer">🔵 हस्तांतरित</option>
                </select>
                <select id="departmentFilter" class="custom-select">
                    <option value="">सर्व विभाग</option>
                    <?php foreach ($distinct_departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn-reset" onclick="resetFilters()">
                <span style="font-size:16px;">↺</span> रीसेट
            </button>
            <button class="btn-export" onclick="exportComplaints()">
                <span style="font-size:16px;">⬇</span> निर्यात
            </button>
        </div>
    </div>

    <!-- 4. Table -->
    <div class="table-card">
        <table id="complaintsTable" class="complaints-table">
            <thead>
                <tr>
                    <th aria-label="Details"></th>
                    <th>समस्या क्रमांक</th>
                    <th>फोटो</th>
                    <th>समस्या विषय</th>
                    <th>विभाग</th>
                    <th>नियुक्त अधिकारी</th>
                    <th>गाव</th>
                    <th>तालुका</th>
                    <th>प्रकार</th>
                    <th>दिनांक</th>
                    <th>स्थिती</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="complaintTableBody">
                <?php if (!empty($complaints)): ?>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php
                        $status = $complaint['status'] ?? 'Open';
                        $badgeClass = badgeClass($status);
                        $editDisabled = isEditDisabled($status);
                        $deleteDisabled = isDeleteDisabled($status);
                        ?>
                        <tr class="complaint-row" data-status="<?= strtolower(trim($status)); ?>"
                            data-department="<?= htmlspecialchars($complaint['department']); ?>"
                            data-village="<?= htmlspecialchars($complaint['village']); ?>">
                            
                            <td class="dtr-control" tabindex="0"></td>
                            <td class="complaint-id"><?= htmlspecialchars($complaint['issue_number']); ?></td>
                            <td>
                                <?php if (!empty($complaint['photo'])): ?>
                                    <img src="<?= htmlspecialchars($complaint['photo']); ?>" 
                                         alt="तक्रार फोटो" 
                                         class="photo-preview" 
                                         onclick="openPhotoPopup('<?= htmlspecialchars($complaint['photo']); ?>', '<?= htmlspecialchars($complaint['issue_number']); ?>')"
                                         title="फोटो पाहण्यासाठी क्लिक करा">
                                <?php else: ?>
                                    <span class="photo-placeholder">No File</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="subject-text"><?= htmlspecialchars($complaint['description']); ?></span></td>
                            <td><?= htmlspecialchars($complaint['department']); ?></td>
                            <td><?= htmlspecialchars($complaint['department_head'] ?? 'विभाग प्रमुख'); ?></td>
                            <td><?= htmlspecialchars($complaint['village']); ?></td>
                            <td><?= htmlspecialchars($complaint['taluka'] ?? 'Hingoli'); ?></td>
                            <td><span class="badge-type-custom"><?= htmlspecialchars($complaint['registration_type']); ?></span></td>
                            <td><?= formatDate($complaint['issue_date']); ?></td>
                            <td>
                                <span class="status-badge <?= $badgeClass; ?>">
                                    <?php if(strtolower($status) == 'transfer' || strtolower($status) == 'transferred'): ?>
                                        Transfer
                                    <?php else: ?>
                                        <?= htmlspecialchars($status); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button type="button" class="btn-icon-action edit" title="<?= $editDisabled ? 'Edit disabled' : 'Edit'; ?>"
                                        data-issue="<?= htmlspecialchars(json_encode($complaint), ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= $editDisabled ? 'disabled aria-disabled="true"' : 'onclick="openEditModalFromButton(this)"'; ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="<?= $deleteDisabled ? 'Delete disabled' : 'Delete'; ?>"
                                        <?= $deleteDisabled ? 'disabled aria-disabled="true"' : 'onclick="deleteComplaint(' . htmlspecialchars(json_encode($complaint['issue_number']), ENT_QUOTES, 'UTF-8') . ')"'; ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Empty State Message -->
    <div id="emptyState" class="empty-state" style="display: none; text-align:center; padding:40px; background:#fff; border-radius:16px; margin-top:20px;">
        <div class="empty-icon" style="font-size:48px;">📭</div>
        <h3 style="margin:10px 0; color:#1e293b;">कोणत्याही तक्रारी नाहीत</h3>
        <p style="color:#64748b;">आपल्यासाठी आता कोणत्याही तक्रारी रेकॉर्ड नाहीत।</p>
        <button class="btn-primary-new" style="margin:16px auto 0;" onclick="openNewComplaintForm()">नवीन तक्रार दाखल करा</button>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.4);" onclick="closeEditModal()"></div>
        <div class="modal-content" style="position:relative; background:#fff; width:100%; max-width:600px; border-radius:16px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f1f5f9; padding-bottom:16px; margin-bottom:20px;">
                <h2 style="margin:0; font-size:20px;">Edit Complaint</h2>
                <button class="modal-close" onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">×</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editIssueNumber" />
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Issue Number</label>
                        <input type="text" id="editIssueDisplay" class="form-control" readonly style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;" />
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="editDescription" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Description</label>
                        <textarea id="editDescription" class="form-control" rows="4" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="editDepartment" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Department</label>
                        <select id="editDepartment" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($distinct_departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="editDepartmentHead" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Assigned Officer</label>
                        <select id="editDepartmentHead" class="form-control" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                            <option value="">-- Select Officer --</option>
                        </select>
                    </div>
                    <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="editVillage" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Village</label>
                            <input type="text" id="editVillage" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;" />
                        </div>
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="editTaluka" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Taluka</label>
                            <input type="text" id="editTaluka" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;" />
                        </div>
                    </div>
                    <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="editRegistrationType" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Type</label>
                            <input type="text" id="editRegistrationType" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;" />
                        </div>
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="editIssueDate" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Date</label>
                            <input type="date" id="editIssueDate" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;" />
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="editStatus" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">Status</label>
                        <select id="editStatus" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                            <option value="Pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Open">Open</option>
                            <option value="In-Progress">In-Progress</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                        <button type="button" class="btn-secondary" onclick="closeEditModal()" style="padding:10px 20px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer;">Cancel</button>
                        <button type="submit" class="btn-primary-new" style="border-radius:8px;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div id="transferModal" class="modal" style="display: none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.4);" onclick="closeTransferModal()"></div>
        <div class="modal-content" style="position:relative; background:#fff; width:100%; max-width:600px; border-radius:16px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f1f5f9; padding-bottom:16px; margin-bottom:20px;">
                <h2 style="margin:0; font-size:20px;">तक्रार हस्तांतरण</h2>
                <button class="modal-close" onclick="closeTransferModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">×</button>
            </div>
            <div class="modal-body">
                <form id="transferForm">
                    <input type="hidden" id="complaintIdTransfer" />
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">समस्या क्रमांक</label>
                        <input type="text" id="transferIssueNumDisplay" class="form-control" readonly style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;" />
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="transferDepartment" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">विभाग निवडा:</label>
                        <select id="transferDepartment" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                            <option value="">-- विभाग निवडा --</option>
                            <?php foreach ($distinct_departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="transferDeptHead" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">संबंधित विभाग प्रमुख:</label>
                        <select id="transferDeptHead" class="form-control" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                            <option value="">-- निवडा विभाग प्रमुख --</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="transferDate" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">हस्तांतरण दिनांक:</label>
                        <input type="text" id="transferDate" class="form-control" readonly style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;" />
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="transferNotes" style="font-weight:600; display:block; margin-bottom:6px; color:#334155;">टिप्पणी:</label>
                        <textarea id="transferNotes" class="form-control" rows="4" placeholder="हस्तांतरणाचे कारण लिहा..." style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;"></textarea>
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                        <button type="button" class="btn-secondary" onclick="closeTransferModal()" style="padding:10px 20px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer;">रद्द करा</button>
                        <button type="submit" class="btn-primary-new" style="border-radius:8px;">हस्तांतरण करा</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const deptDesignations = <?php echo json_encode($dept_designations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const complaintsExportData = <?php echo json_encode($complaints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        // Photo Popup Functions
        function openPhotoPopup(imageUrl, issueNumber) {
            const popup = document.getElementById('photoPopup');
            const popupImage = document.getElementById('popupImage');
            const caption = document.getElementById('popupCaption');
            
            if (popup && popupImage) {
                popupImage.src = imageUrl;
                caption.textContent = 'तक्रार क्रमांक: ' + (issueNumber || 'N/A');
                popup.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePhotoPopup() {
            const popup = document.getElementById('photoPopup');
            if (popup) {
                popup.classList.remove('active');
                document.body.style.overflow = '';
                const popupImage = document.getElementById('popupImage');
                if (popupImage) {
                    popupImage.src = '';
                }
            }
        }

        // Keyboard shortcut: ESC to close photo popup
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const popup = document.getElementById('photoPopup');
                if (popup && popup.classList.contains('active')) {
                    closePhotoPopup();
                }
            }
        });

        const departmentSelect = document.getElementById('transferDepartment');
        const deptHeadSelect = document.getElementById('transferDeptHead');

        if (departmentSelect) {
            departmentSelect.addEventListener('change', function () {
                populateDeptHeads(this.value);
            });
        }

        function populateDeptHeads(selectedDept, selectedHead = '') {
            if (!deptHeadSelect) return;
            deptHeadSelect.innerHTML = '<option value="">-- निवडा विभाग प्रमुख --</option>';
            if (selectedDept && deptDesignations[selectedDept]) {
                deptDesignations[selectedDept].forEach(function (desg) {
                    const option = document.createElement('option');
                    option.value = desg;
                    option.textContent = desg;
                    if (desg === selectedHead) {
                        option.selected = true;
                    }
                    deptHeadSelect.appendChild(option);
                });
            }
        }

        $(document).ready(function() {
            const table = $('#complaintsTable').DataTable({
                "dom": 'lrtip',
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "ordering": true,
                "order": [],
                "responsive": {
                    "details": {
                        "type": "column",
                        "target": 0
                    }
                },
                "columnDefs": [
                    { "targets": 0, "className": "dtr-control", "orderable": false, "searchable": false },
                    { "targets": 1, "responsivePriority": 1 },
                    { "targets": 3, "responsivePriority": 2 },
                    { "targets": 10, "responsivePriority": 3 },
                    { "targets": 11, "orderable": false, "searchable": false, "responsivePriority": 4 },
                    { "targets": 2, "responsivePriority": 10001 }
                ],
                "language": {
                    "lengthMenu": "दाखवा _MENU_ नोंदी",
                    "paginate": {
                        "previous": "< मागे",
                        "next": "पुढे >"
                    },
                    "info": "एकूण _TOTAL_ पैकी _START_ ते _END_ दाखवत आहे",
                    "infoEmpty": "माहिती उपलब्ध नाही",
                    "zeroRecords": "कोणतेही रेकॉर्ड सापडले नाहीत"
                }
            });

            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#statusFilter').on('change', function() {
                table.column(10).search(this.value).draw();
            });

            $('#departmentFilter').on('change', function() {
                table.column(4).search(this.value).draw();
            });
        });

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            const table = $('#complaintsTable').DataTable();
            table.search('').columns().search('').draw();
        }

        function openEditModalFromButton(button) {
            const complaint = JSON.parse(button.getAttribute('data-issue'));
            window.location.href = 'issueform.php?edit=' + encodeURIComponent(complaint.issue_number || '');
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
        }

        function deleteComplaint(id) {
            Swal.fire({
                title: 'तक्रार हटवायची का?',
                text: 'ही कृती परत करता येणार नाही.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'हटवा',
                cancelButtonText: 'रद्द करा',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch('complaint_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_complaint&issue_number=' + encodeURIComponent(id)
                })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            title: data.success ? 'हटवले' : 'हटवले नाही',
                            text: data.message,
                            icon: data.success ? 'success' : 'error',
                            confirmButtonText: 'ठीक आहे',
                            confirmButtonColor: data.success ? '#0284c7' : '#dc2626'
                        }).then(() => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            title: 'त्रुटी',
                            text: 'तक्रार हटवता आली नाही. कृपया पुन्हा प्रयत्न करा.',
                            icon: 'error',
                            confirmButtonText: 'ठीक आहे',
                            confirmButtonColor: '#dc2626'
                        });
                    });
            });
        }

        function openNewComplaintForm() {
            window.location.href = 'issueform.php';
        }

        function exportComplaints() {
            const columns = [
                { key: 'issue_number', label: 'Issue Number' },
                { key: 'photo', label: 'Photo' },
                { key: 'description', label: 'Description' },
                { key: 'department', label: 'Department' },
                { key: 'department_head', label: 'Assigned Officer' },
                { key: 'village', label: 'Village' },
                { key: 'taluka', label: 'Taluka' },
                { key: 'registration_type', label: 'Type' },
                { key: 'issue_date', label: 'Date' },
                { key: 'status', label: 'Status' }
            ];
            const table = $('#complaintsTable').DataTable();
            const filteredIssueNumbers = new Set();

            table.rows({ search: 'applied' }).nodes().each(function(row) {
                const issueCell = row.querySelector('.complaint-id');
                if (issueCell) {
                    filteredIssueNumbers.add(issueCell.textContent.trim());
                }
            });

            const rowsToExport = complaintsExportData.filter(function(complaint) {
                return filteredIssueNumbers.has(String(complaint.issue_number || '').trim());
            });

            const escapeHtml = function(value) {
                const text = value === null || value === undefined ? '' : String(value);
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const headerCells = columns.map(function(column) {
                return '<th>' + escapeHtml(column.label) + '</th>';
            }).join('');

            const bodyRows = rowsToExport.map(function(complaint) {
                const cells = columns.map(function(column) {
                    return '<td>' + escapeHtml(complaint[column.key]) + '</td>';
                }).join('');
                return '<tr>' + cells + '</tr>';
            }).join('');

            const excelContent =
                '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
                'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
                'xmlns="http://www.w3.org/TR/REC-html40">' +
                '<head><meta charset="UTF-8"></head>' +
                '<body><table border="1"><thead><tr>' + headerCells +
                '</tr></thead><tbody>' + bodyRows + '</tbody></table></body></html>';

            const blob = new Blob(['\uFEFF' + excelContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'complaints_report.xls';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        function openTransferModal(complaintId) {
            document.getElementById('complaintIdTransfer').value = complaintId;
            document.getElementById('transferIssueNumDisplay').value = complaintId;
            setCurrentDateTime();
            document.getElementById('transferModal').style.display = 'flex';
            const deptVal = document.getElementById('transferDepartment').value;
            if (deptVal) {
                populateDeptHeads(deptVal);
            }
        }

        function closeTransferModal() {
            document.getElementById('transferModal').style.display = 'none';
            document.getElementById('transferForm').reset();
        }

        function setCurrentDateTime() {
            const now = new Date();
            const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const dateTimeString = now.toLocaleDateString('en-GB', options).replace(/(\d+)\/(\d+)\/(\d+)/, '$3-$2-$1') + ' ' +
                now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
            document.getElementById('transferDate').value = dateTimeString;
        }

        document.getElementById('editForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const payload = new URLSearchParams();
            payload.append('action', 'update_complaint');
            payload.append('issue_number', document.getElementById('editIssueNumber').value);
            payload.append('description', document.getElementById('editDescription').value);
            payload.append('department', document.getElementById('editDepartment').value);
            payload.append('department_head', document.getElementById('editDepartmentHead').value);
            payload.append('village', document.getElementById('editVillage').value);
            payload.append('taluka', document.getElementById('editTaluka').value);
            payload.append('registration_type', document.getElementById('editRegistrationType').value);
            payload.append('issue_date', document.getElementById('editIssueDate').value);
            payload.append('status', document.getElementById('editStatus').value);

            fetch('complaint_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            })
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: data.success ? 'यशस्वी!' : 'त्रुटी',
                        text: data.message,
                        icon: data.success ? 'success' : 'error',
                        confirmButtonText: 'ठीक आहे',
                        confirmButtonColor: data.success ? '#0284c7' : '#dc2626'
                    });
                    if (data.success) {
                        setTimeout(() => location.reload(), 900);
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        title: 'त्रुटी',
                        text: 'अद्यतन करता आले नाही. कृपया पुन्हा प्रयत्न करा.',
                        icon: 'error',
                        confirmButtonText: 'ठीक आहे',
                        confirmButtonColor: '#dc2626'
                    });
                });
        });

        document.getElementById('transferForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const complaintId = document.getElementById('complaintIdTransfer').value;
            const department = document.getElementById('transferDepartment').value;
            const deptHead = document.getElementById('transferDeptHead').value;
            const notes = document.getElementById('transferNotes').value;

            if (!department || !deptHead) {
                Swal.fire({
                    title: 'त्रुटी',
                    text: 'कृपया विभाग आणि संबंधित विभाग प्रमुख निवडा.',
                    icon: 'error',
                    confirmButtonText: 'ठीक आहे',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }

            fetch('transfer_complaint.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'issue_number=' + encodeURIComponent(complaintId) +
                    '&department=' + encodeURIComponent(department) +
                    '&department_head=' + encodeURIComponent(deptHead) +
                    '&notes=' + encodeURIComponent(notes)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'यशस्वी!',
                            text: 'तक्रार यशस्वीरित्या हस्तांतरित केली गेली!',
                            icon: 'success',
                            confirmButtonText: 'ठीक आहे',
                            confirmButtonColor: '#0284c7'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: 'त्रुटी',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'ठीक आहे',
                            confirmButtonColor: '#dc2626'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        title: 'त्रुटी',
                        text: 'हस्तांतरण प्रक्रिया दरम्यान त्रुटी आली.',
                        icon: 'error',
                        confirmButtonText: 'ठीक आहे',
                        confirmButtonColor: '#dc2626'
                    });
                });
        });

        document.addEventListener('click', function (e) {
            const modal = document.getElementById('transferModal');
            if (e.target === document.querySelector('.modal-overlay')) {
                closeTransferModal();
            }
        });
    </script>

    <?php include('include/footer.php'); ?>
</main>