git <?php
session_start();
require_once __DIR__ . '/include/config.php';

$sessionRoles = [
    strtolower(trim((string) ($_SESSION['user_role'] ?? ''))),
    strtolower(trim((string) ($_SESSION['user_system_role'] ?? ''))),
];

if (empty($_SESSION['username']) || !in_array('ceo', $sessionRoles, true)) {
    header('Location: login.php');
    exit;
}

$page_title = 'CEO Issue Report';
$page_description = 'Executive issue report with department-wise tracking.';

$conn = db_connect();

if (isset($_GET['transfer_issue'])) {
    header('Content-Type: application/json');
    $issueNumber = trim((string) $_GET['transfer_issue']);
    $records = [];

    if ($issueNumber !== '') {
        $transferStmt = $conn->prepare("
            SELECT issue_no, transfer_to, transfer_by, reason
            FROM transfer
            WHERE issue_no = ?
        ");

        if ($transferStmt) {
            $transferStmt->bind_param('s', $issueNumber);
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();
            while ($row = $transferResult->fetch_assoc()) {
                $records[] = [
                    'issue_no' => $row['issue_no'] ?? '',
                    'transfer_to' => $row['transfer_to'] ?? '',
                    'transfer_by' => $row['transfer_by'] ?? '',
                    'reason' => $row['reason'] ?? '',
                ];
            }
            $transferStmt->close();
        }
    }

    $conn->close();
    echo json_encode(['success' => true, 'records' => $records]);
    exit;
}

$issues = [];
$departments = [];
$talukas = [];
$villages = [];
$roles = [];
$dbError = '';

function ceoStatusClass($status)
{
    $status = strtolower(trim((string) $status));
    if ($status === 'resolved') {
        return 'resolved';
    }
    if ($status === 'in progress' || $status === 'transferred') {
        return 'progress';
    }
    if ($status === 'pending' || $status === 'received' || $status === 'open') {
        return 'pending';
    }
    return 'neutral';
}

function ceoFormatDate($date)
{
    $timestamp = strtotime((string) $date);
    return $timestamp ? date('d M Y', $timestamp) : '-';
}

function ceoShortText($text, $limit = 120)
{
    $text = trim((string) $text);
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit - 3) . '...';
}

// ----- DISTINCT DEPARTMENTS FROM `users` -----
$departmentResult = $conn->query("
    SELECT DISTINCT department
    FROM users
    WHERE department IS NOT NULL AND department != ''
    ORDER BY department ASC
");
if ($departmentResult) {
    while ($row = $departmentResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// ----- DISTINCT TALUKAS FROM `users` -----
$talukaResult = $conn->query("
    SELECT DISTINCT taluka
    FROM users
    WHERE taluka IS NOT NULL AND taluka != ''
    ORDER BY taluka ASC
");
if ($talukaResult) {
    while ($row = $talukaResult->fetch_assoc()) {
        $talukas[] = $row['taluka'];
    }
}

// ----- DISTINCT VILLAGES FROM `users` -----
$villageResult = $conn->query("
    SELECT DISTINCT village
    FROM users
    WHERE village IS NOT NULL AND village != ''
    ORDER BY village ASC
");
if ($villageResult) {
    while ($row = $villageResult->fetch_assoc()) {
        $villages[] = $row['village'];
    }
}

// ----- ROLES (still from tbl_raiseissue) -----
$roleResult = $conn->query("
    SELECT DISTINCT position
    FROM tbl_raiseissue
    WHERE position IS NOT NULL AND position != ''
    ORDER BY position ASC
");
if ($roleResult) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row['position'];
    }
}

// Transfer history (still needed for the Timeline modal)
$transferHistory = [];
$transferResult = $conn->query("
    SELECT issue_no, transfer_to, transfer_by, reason
    FROM transfer
");
if ($transferResult) {
    while ($row = $transferResult->fetch_assoc()) {
        $issueNo = (string) ($row['issue_no'] ?? '');
        if ($issueNo === '') {
            continue;
        }
        if (!isset($transferHistory[$issueNo])) {
            $transferHistory[$issueNo] = [];
        }
        $transferHistory[$issueNo][] = [
            'transfer_to' => $row['transfer_to'] ?? '',
            'transfer_by' => $row['transfer_by'] ?? '',
            'reason' => $row['reason'] ?? '',
        ];
    }
}

// Main issues – ordered by date (most recent first)
$issueSql = "
    SELECT
        issue_number,
        issue_date,
        taluka,
        village,
        department,
        department_head,
        registration_type,
        position,
        mobile,
        description,
        photo,
        status,
        resolved_remark
    FROM tbl_raiseissue
    ORDER BY issue_date DESC, issue_number DESC
";

$issueResult = $conn->query($issueSql);
if ($issueResult) {
    while ($row = $issueResult->fetch_assoc()) {
        $issues[] = $row;
    }
} else {
    $dbError = $conn->error;
}

$conn->close();
?>

<?php include __DIR__ . '/include/header.php'; ?>
<?php include __DIR__ . '/include/sidebar.php'; ?>

<!-- DataTables CSS & Responsive -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<main class="main-content ceo-report-page">
    <section class="report-hero">
        <div>
            <p class="report-kicker">CEO REPORT</p>
            <h1>Issue Monitoring Report</h1>
            <p>Track all issues raised across departments, talukas, villages, and current resolution status.</p>
        </div>
        <div class="report-actions">
            <button type="button" class="report-btn secondary" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                Print
            </button>
            <button type="button" class="report-btn primary" onclick="exportVisibleRows()">
                <i class="fa-solid fa-file-csv"></i>
                Export CSV
            </button>
        </div>
    </section>

    <?php if ($dbError): ?>
        <div class="report-alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Unable to load issue report: <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <section class="report-panel">
        <div class="panel-header">
            <div>
                <h2>Reported Issues</h2>
                <p><?php echo count($issues); ?> records fetched</p>
            </div>
            <div class="record-count" id="visibleCount"><?php echo count($issues); ?> visible</div>
        </div>

        <div class="filter-bar">
            <label class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="globalSearch" placeholder="Search any field...">
            </label>

            <!-- Status filter – only Pending, Transferred, Resolved -->
            <select id="statusFilter" class="filter-select" aria-label="Filter by status">
                <option value="">Select Status</option>
                <option value="pending">Pending</option>
                <option value="transferred">Transferred</option>
                <option value="resolved">Resolved</option>
            </select>

            <select id="departmentFilter" class="filter-select" aria-label="Filter by department">
                <option value="">Select Department</option>
                <?php foreach ($departments as $deptName): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($deptName)); ?>">
                        <?php echo htmlspecialchars($deptName); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="talukaFilter" class="filter-select" aria-label="Filter by taluka">
                <option value="">Select Taluka</option>
                <?php foreach ($talukas as $taluka): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($taluka)); ?>">
                        <?php echo htmlspecialchars($taluka); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="villageFilter" class="filter-select" aria-label="Filter by village">
                <option value="">Select Village</option>
                <?php foreach ($villages as $village): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($village)); ?>">
                        <?php echo htmlspecialchars($village); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="roleFilter" class="filter-select" aria-label="Filter by role">
                <option value="">Select Role</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($role)); ?>">
                        <?php echo htmlspecialchars($role); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" class="report-btn reset" onclick="resetFilters()">
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </button>
        </div>

        <div class="table-shell">
            <table class="ceo-report-table" id="ceoIssueTable">
                <thead>
                    <tr>
                        <th>Issue No.</th>
                        <th>Date</th>
                        <th>Issue Details</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Reporter</th>
                        <th>Photo</th>
                        <th>Status</th>
                        <th>Timeline</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                        <?php
                        $status = $issue['status'] ?: 'Open';
                        $statusClass = ceoStatusClass($status);
                        ?>
                        <tr>
                            <td><strong class="issue-number"><?php echo htmlspecialchars($issue['issue_number']); ?></strong></td>
                            <td><?php echo ceoFormatDate($issue['issue_date']); ?></td>
                            <td class="details-cell">
                                <strong><?php echo htmlspecialchars(ceoShortText($issue['description'])); ?></strong>
                                <?php if (!empty($issue['resolved_remark'])): ?>
                                    <small>Remark: <?php echo htmlspecialchars(ceoShortText($issue['resolved_remark'], 80)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="department-chip"><?php echo htmlspecialchars($issue['department'] ?: 'Not assigned'); ?></span>
                                <small><?php echo htmlspecialchars($issue['department_head'] ?: 'Department head pending'); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($issue['village'] ?: '-'); ?></strong>
                                <small><?php echo htmlspecialchars($issue['taluka'] ?: '-'); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($issue['registration_type'] ?: '-'); ?></strong>
                                <small><?php echo htmlspecialchars($issue['position'] ?: '-'); ?><?php echo !empty($issue['mobile']) ? ' | ' . htmlspecialchars($issue['mobile']) : ''; ?></small>
                            </td>
                            <td>
                                <?php if (!empty($issue['photo'])): ?>
                                    <a class="photo-link" href="#" onclick="openPhotoModal('<?php echo htmlspecialchars($issue['photo'], ENT_QUOTES); ?>'); return false;">
                                        <i class="fa-regular fa-image"></i>
                                        View
                                    </a>
                                <?php else: ?>
                                    <span class="muted-text">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="timeline-btn"
                                    onclick="openTransferHistory('<?php echo htmlspecialchars($issue['issue_number'], ENT_QUOTES); ?>')">
                                    <i class="fa-regular fa-clock"></i>
                                    Timeline
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="empty-state" id="emptyState" style="display:none;">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No issues found</h3>
                <p>No records match the selected report filters.</p>
            </div>
        </div>
    </section>
</main>

<!-- Transfer Modal (popup) -->
<div class="transfer-modal" id="transferModal" hidden>
    <div class="transfer-backdrop" onclick="closeTransferModal()"></div>
    <div class="transfer-content">
        <div class="transfer-header">
            <div>
                <p class="transfer-subtitle">ISSUE TIMELINE</p>
                <h2 id="transferTitle">Transfer History</h2>
            </div>
            <button type="button" class="transfer-close" onclick="closeTransferModal()" aria-label="Close timeline">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="transferHistoryBody" class="transfer-body"></div>
    </div>
</div>

<!-- Photo Modal -->
<div class="photo-modal" id="photoModal" hidden>
    <div class="photo-backdrop" onclick="closePhotoModal()"></div>
    <div class="photo-content">
        <button type="button" class="photo-close" onclick="closePhotoModal()" aria-label="Close photo">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <img id="modalPhotoImg" src="" alt="Issue photo">
    </div>
</div>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS & Responsive -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
    /* (All CSS remains exactly as before – no changes needed) */
    /* ----- Global / Reset ----- */
    .ceo-report-page {
        background: #f0f4ff;
        padding: 24px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* ----- Hero Section ----- */
    .report-hero {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
        border-radius: 20px;
        padding: 32px 40px;
        margin-bottom: 24px;
        box-shadow: 0 8px 30px rgba(30, 58, 138, 0.08);
        border-left: 6px solid #2563eb;
    }

    .report-kicker {
        margin: 0 0 6px;
        color: #d97706;
        font-weight: 800;
        font-size: 0.7rem;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .report-hero h1 {
        margin: 0;
        color: #0f172a;
        font-size: 2.2rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .report-hero p {
        margin: 8px 0 0;
        color: #475569;
        font-weight: 500;
        font-size: 1rem;
    }

    .report-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* ----- Buttons ----- */
    .report-btn {
        border: 0;
        border-radius: 10px;
        padding: 12px 22px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        transition: all 0.2s ease;
        font-size: 0.95rem;
        background: #eef2ff;
        color: #1e3a8a;
        border: 1px solid #dbe3ef;
    }

    .report-btn.primary {
        background: #2563eb;
        color: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    .report-btn.primary:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
    }

    .report-btn.secondary:hover,
    .report-btn.reset:hover {
        background: #dbeafe;
    }

    .report-btn.reset {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* ----- Alert ----- */
    .report-alert {
        margin-bottom: 20px;
        padding: 16px 20px;
        border-radius: 12px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-weight: 600;
    }

    /* ----- Main Panel ----- */
    .report-panel {
        background: #ffffff;
        border-radius: 20px;
        padding: 28px 32px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .panel-header h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .panel-header p {
        color: #64748b;
        font-weight: 500;
        margin: 4px 0 0;
    }

    .record-count {
        background: #e0e7ff;
        color: #1e3a8a;
        border-radius: 30px;
        padding: 8px 18px;
        font-weight: 700;
        font-size: 0.9rem;
        white-space: nowrap;
        border: 1px solid #c7d2fe;
    }

    /* ----- Filter Bar ----- */
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        padding: 16px 20px;
        background: #f8fafc;
        border-radius: 14px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .search-field {
        flex: 1 1 280px;
        min-width: 200px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        padding: 0 16px;
        min-height: 46px;
        transition: all 0.2s;
    }
    .search-field:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .search-field input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        font: inherit;
        color: #0f172a;
    }

    .filter-select {
        min-height: 46px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        padding: 0 16px;
        font-weight: 600;
        min-width: 140px;
        transition: all 0.2s;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .filter-select:focus {
        border-color: #2563eb;
        outline: 0;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* ----- DataTables Overrides ----- */
    .dataTables_wrapper .dataTables_filter {
        display: none; /* we use our own search */
    }
    .dataTables_wrapper .dataTables_info {
        padding: 10px 0;
        color: #475569;
        font-weight: 500;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        background: #f1f5f9 !important;
        color: #1e293b !important;
        border: 1px solid #e2e8f0 !important;
        margin: 0 2px;
        padding: 6px 14px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #2563eb !important;
        color: white !important;
        border-color: #2563eb !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #dbeafe !important;
        color: #1e3a8a !important;
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 10px;
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 8px;
        border: 1px solid #dbe3ef;
        padding: 4px 8px;
        background: white;
    }

    /* ---- CUSTOM "PLUS" TOGGLE BUTTON ---- */
    .dataTable .responsive-toggle {
        display: inline-block;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #2563eb;
        color: white;
        text-align: center;
        line-height: 28px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        transition: transform 0.2s ease, background 0.2s ease;
        user-select: none;
    }
    .dataTable .responsive-toggle:hover {
        background: #1d4ed8;
        transform: scale(1.05);
    }
    /* Plus sign when collapsed */
    .dataTable .responsive-toggle::before {
        content: "+";
        display: block;
    }
    /* Minus sign when expanded */
    .dataTable .responsive-toggle.dtr-expanded::before {
        content: "−";
    }

    /* Style the child row (expanded content) */
    .dataTables_wrapper .dataTable .child {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 10px 16px;
    }
    .dataTables_wrapper .dataTable .child ul {
        margin: 0;
        padding: 0;
        list-style: none;
        display: table;
        width: 100%;
    }
    .dataTables_wrapper .dataTable .child ul li {
        display: table-row;
    }
    .dataTables_wrapper .dataTable .child ul li .dtr-title {
        display: table-cell;
        font-weight: 700;
        padding: 4px 10px 4px 0;
        color: #1e293b;
        white-space: nowrap;
    }
    .dataTables_wrapper .dataTable .child ul li .dtr-data {
        display: table-cell;
        padding: 4px 0;
        color: #0f172a;
    }

    /* ----- Table ----- */
    .table-shell {
        overflow-x: auto;
        border-radius: 14px;
        border: 1px solid #eef2f7;
        background: #ffffff;
        -webkit-overflow-scrolling: touch;
    }

    .ceo-report-table {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 0.95rem;
        table-layout: fixed;
    }

    .ceo-report-table th {
        background: #f1f5f9;
        color: #1e293b;
        text-align: left;
        padding: 14px 18px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 700;
    }

    .ceo-report-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        line-height: 1.5;
        color: #1f2937;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .ceo-report-table tbody tr:hover {
        background: #f8fafc;
    }
    .ceo-report-table tbody tr:last-child td {
        border-bottom: 0;
    }

    /* ----- Cell Styles ----- */
    .issue-number {
        color: #2563eb;
        font-weight: 700;
        white-space: nowrap;
    }

    .details-cell {
        max-width: 260px;
    }
    .details-cell strong {
        display: block;
        font-weight: 600;
    }
    .details-cell small {
        color: #64748b;
        font-size: 0.8rem;
        display: block;
        margin-top: 4px;
    }

    .department-chip {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 0.8rem;
        margin-bottom: 4px;
    }

    .ceo-report-table td small {
        display: block;
        color: #64748b;
        font-size: 0.75rem;
    }

    /* ----- Status Pills ----- */
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 14px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        text-transform: capitalize;
    }
    .status-pill.pending {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fcd34d;
    }
    .status-pill.progress {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    .status-pill.resolved {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }
    .status-pill.neutral {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .photo-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #2563eb;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .photo-link:hover {
        text-decoration: underline;
    }

    .muted-text {
        color: #94a3b8;
        font-size: 0.85rem;
    }

    /* ----- Timeline Button ----- */
    .timeline-btn {
        border: 0;
        border-radius: 30px;
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
        color: #fff;
        min-height: 44px;
        min-width: 44px;
        padding: 8px 18px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        font-size: 0.9rem;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(30, 58, 138, 0.25);
    }
    .timeline-btn:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 14px rgba(30, 58, 138, 0.35);
    }

    /* ----- Transfer Modal (popup) ----- */
    .transfer-modal[hidden] {
        display: none;
    }
    .transfer-modal {
        position: fixed;
        inset: 0;
        z-index: 3000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .transfer-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
    }
    .transfer-content {
        position: relative;
        max-width: 560px;
        width: 100%;
        max-height: 80vh;
        background: #ffffff;
        border-radius: 20px;
        padding: 28px 30px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow-y: auto;
        animation: fadeIn 0.25s ease;
    }
    .transfer-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    .transfer-subtitle {
        margin: 0 0 4px;
        color: #d97706;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .transfer-header h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1.3rem;
        font-weight: 800;
    }
    .transfer-close {
        border: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #0f172a;
        cursor: pointer;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.2s;
    }
    .transfer-close:hover {
        background: #e2e8f0;
    }
    .transfer-body {
        padding: 4px 0;
    }

    /* transfer table inside modal */
    .transfer-table-wrap {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #eef2f7;
    }
    .transfer-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
        min-width: 360px;
    }
    .transfer-table th {
        background: #f1f5f9;
        color: #1e293b;
        text-align: left;
        padding: 12px 16px;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid #e2e8f0;
    }
    .transfer-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #1f2937;
    }
    .transfer-table tbody tr:nth-child(even) {
        background: #fafcff;
    }
    .transfer-table tbody tr:hover {
        background: #f1f5f9;
    }
    .transfer-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .transfer-table .serial {
        font-weight: 700;
        color: #2563eb;
        width: 40px;
    }

    .transfer-empty {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
    }
    .transfer-empty i {
        font-size: 2.2rem;
        color: #cbd5e1;
        margin-bottom: 12px;
    }
    .transfer-empty h3 {
        color: #0f172a;
        margin: 0 0 6px;
        font-weight: 700;
    }

    /* ----- Photo Modal ----- */
    .photo-modal[hidden] {
        display: none;
    }
    .photo-modal {
        position: fixed;
        inset: 0;
        z-index: 4000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .photo-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
    }
    .photo-content {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        background: #fff;
        border-radius: 16px;
        padding: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: fadeIn 0.2s ease;
    }
    .photo-content img {
        display: block;
        max-width: 100%;
        max-height: 80vh;
        border-radius: 10px;
        object-fit: contain;
    }
    .photo-close {
        position: absolute;
        top: -16px;
        right: -16px;
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 50%;
        background: #1e293b;
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        transition: background 0.2s;
    }
    .photo-close:hover {
        background: #0f172a;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    /* ----- Empty State (DataTables will manage) ----- */
    .dataTables_empty {
        padding: 30px !important;
        font-size: 1.1rem;
        color: #64748b;
    }

    /* ----- Responsive ----- */
    @media (max-width: 768px) {
        .ceo-report-page {
            padding: 12px;
        }

        .report-hero {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            padding: 24px 20px;
        }
        .report-actions {
            justify-content: center;
        }

        .report-panel {
            padding: 16px;
        }

        .panel-header {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
            padding: 14px;
        }
        .search-field {
            flex: 1 1 auto;
        }
        .filter-select {
            width: 100%;
            min-width: unset;
        }

        .transfer-content {
            padding: 20px;
            max-width: 100%;
            margin: 10px;
        }

        .report-hero h1 {
            font-size: 1.6rem;
        }

        .record-count {
            align-self: flex-start;
        }

        /* Mobile table tweaks */
        .ceo-report-table td.details-cell {
            max-width: 150px;
        }
        .ceo-report-table td.details-cell strong {
            font-size: 0.85rem;
        }
        .ceo-report-table th,
        .ceo-report-table td {
            padding: 10px 12px;
        }
    }

    @media (max-width: 480px) {
        .report-actions .report-btn {
            width: 100%;
            justify-content: center;
        }
        .transfer-content {
            padding: 16px;
        }
        .transfer-header h2 {
            font-size: 1.1rem;
        }
    }

    /* ----- Print ----- */
    @media print {
        header, .sidebar, #sidebarOverlay,
        .report-actions, .filter-bar,
        .dataTables_length, .dataTables_filter, .dataTables_paginate {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .report-hero, .report-panel {
            box-shadow: none;
            border: 1px solid #ccc;
        }
        .timeline-btn {
            background: #0f172a !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_info {
            display: none !important;
        }
        table.dataTable {
            width: 100% !important;
        }
        .dataTables_wrapper .dataTables_scroll {
            overflow: visible !important;
        }
    }
</style>

<script>
    // Pass transferHistory to JS (still needed for Timeline modal)
    const transferHistory = <?php echo json_encode($transferHistory, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    $(document).ready(function() {
        // Initialize DataTable with responsive = inline child row
        var table = $('#ceoIssueTable').DataTable({
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.childRowImmediate,
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            paging: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'desc']],
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 2 },
                { responsivePriority: 3, targets: 7 },
                { responsivePriority: 4, targets: 8 },
                { responsivePriority: 5, targets: [1, 3, 4, 5, 6] },
                { type: 'date', targets: 1 }
            ],
            language: {
                emptyTable: "No issues found",
                zeroRecords: "No matching issues found"
            },
            // ✅ UPDATED: Move info (i) to the top, above the table
            dom: '<"datatable-top"l><"datatable-info"i>t<"datatable-bottom"p>',
            searching: true
        });

        // Use our own search input
        $('#globalSearch').on('keyup', function() {
            table.search(this.value).draw();
        });

        // ----- Status filter – map dropdown to database values -----
        $('#statusFilter').on('change', function() {
            var val = $(this).val();
            if (val === 'transferred') {
                table.column(7).search('transfer', true, false);
            } else {
                table.column(7).search(val ? val : '', true, false);
            }
            table.draw();
            updateVisibleCount();
        });

        // Department filter (column index 3)
        $('#departmentFilter').on('change', function() {
            var val = $(this).val();
            table.column(3).search(val ? val : '', true, false).draw();
            updateVisibleCount();
        });

        // Taluka filter (column index 4 – location column)
        $('#talukaFilter').on('change', function() {
            var val = $(this).val();
            table.column(4).search(val ? val : '', true, false).draw();
            updateVisibleCount();
        });

        $('#villageFilter').on('change', function() {
            var val = $(this).val();
            table.column(4).search(val ? val : '', true, false).draw();
            updateVisibleCount();
        });

        // Role filter (column index 5 – Reporter column)
        $('#roleFilter').on('change', function() {
            var val = $(this).val();
            table.column(5).search(val ? val : '', true, false).draw();
            updateVisibleCount();
        });

        // Reset filters
        window.resetFilters = function() {
            $('#globalSearch').val('');
            $('#statusFilter').val('');
            $('#departmentFilter').val('');
            $('#talukaFilter').val('');
            $('#villageFilter').val('');
            $('#roleFilter').val('');
            table.search('').columns().search('').draw();
            updateVisibleCount();
        };

        // Update visible count on draw
        function updateVisibleCount() {
            var info = table.page.info();
            var total = info.recordsDisplay;
            $('#visibleCount').text(total + ' visible');
        }

        table.on('draw', function() {
            updateVisibleCount();
        });

        updateVisibleCount();

        // Export CSV (visible rows only) with UTF-8 BOM
        window.exportVisibleRows = function() {
            var header = ['Issue No', 'Date', 'Details', 'Department', 'Location', 'Reporter', 'Photo', 'Status', 'Timeline'];
            var lines = [header];

            table.rows({ search: 'applied' }).every(function() {
                var rowData = this.data();
                lines.push(rowData.map(cell => {
                    var text = $(cell).text().trim().replace(/\s+/g, ' ');
                    return '"' + text.replace(/"/g, '""') + '"';
                }));
            });

            var csv = lines.map(line => line.join(',')).join('\n');
            var bom = '\uFEFF';
            var blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'ceo_issue_report.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        };

        // Transfer history functions (popup modal)
        window.openTransferHistory = function(issueNumber) {
            var modal = document.getElementById('transferModal');
            var title = document.getElementById('transferTitle');
            var body = document.getElementById('transferHistoryBody');
            var history = transferHistory[issueNumber] || [];

            title.textContent = 'Issue ' + issueNumber;

            if (!history.length) {
                body.innerHTML = `
                    <div class="transfer-empty">
                        <i class="fa-regular fa-folder-open"></i>
                        <h3>No transfer records</h3>
                        <p>This issue has no timeline entries in the transfer table.</p>
                    </div>
                `;
            } else {
                var rowsHtml = history.map(function(item, index) {
                    return `
                        <tr>
                            <td class="serial">${index + 1}</td>
                            <td>${escapeHtml(item.transfer_by || '-')}</td>
                            <td>${escapeHtml(item.transfer_to || 'Not specified')}</td>
                            <td>${escapeHtml(item.reason || 'No reason added')}</td>
                        </tr>
                    `;
                }).join('');

                body.innerHTML = `
                    <div class="transfer-table-wrap">
                        <table class="transfer-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Transferred By</th>
                                    <th>Transferred To</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        };

        window.closeTransferModal = function() {
            document.getElementById('transferModal').hidden = true;
            document.body.style.overflow = '';
        };

        // Photo modal
        window.openPhotoModal = function(photoUrl) {
            var modal = document.getElementById('photoModal');
            var img = document.getElementById('modalPhotoImg');
            img.src = photoUrl;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        };

        window.closePhotoModal = function() {
            document.getElementById('photoModal').hidden = true;
            document.body.style.overflow = '';
        };

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function(char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!$('#transferModal')[0].hidden) {
                    closeTransferModal();
                }
                if (!$('#photoModal')[0].hidden) {
                    closePhotoModal();
                }
            }
        });
    });
</script>

</body>
</html>
