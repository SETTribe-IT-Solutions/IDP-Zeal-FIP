<?php
// complain_action.php - Complaint action and transfer page
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'include/config.php';

$conn = db_connect();
$complaints = [];
$dbError = '';

$view = $_GET['view'] ?? 'assigned';

// Determine query filter based on user role and view tab
$role = !empty($_SESSION['user_system_role']) ? $_SESSION['user_system_role'] : ($_SESSION['user_role'] ?? '');
$where = "1=1";
$params = [];
$types = "";

$normalizedRole = strtolower(trim($role));

if ($view === 'transfer') {
    $where = "transfer_to = ?";
    $params[] = $_SESSION['username'] ?? '';
    $types .= "s";
} else {
    // view === 'assigned'
    if ($normalizedRole === 'ceo') {
        $where = "(transfer_to IS NULL OR transfer_to = '')";
    } elseif ($role === 'ग्रामपंचायत अधिकारी' || $role === 'अंगणवाडी सेविका' || $role === 'शिक्षक' || $normalizedRole === 'teacher') {
        $where = "mobile = ? AND (transfer_to IS NULL OR transfer_to = '')";
        $params[] = $_SESSION['user_mobile'] ?? '';
        $types .= "s";
    } else {
        // BDO, THO, HoD
        $user_desg = $_SESSION['user_designation'] ?? '';
        $user_taluka = $_SESSION['user_taluka'] ?? '';
        if (!empty($user_taluka)) {
            $where = "department_head = ? AND taluka = ? AND (transfer_to IS NULL OR transfer_to = '')";
            $params[] = $user_desg;
            $params[] = $user_taluka;
            $types .= "ss";
        } else {
            $where = "department_head = ? AND (transfer_to IS NULL OR transfer_to = '')";
            $params[] = $user_desg;
            $types .= "s";
        }
    }
}

$query = "SELECT * FROM tbl_raiseissue WHERE $where ORDER BY issue_date DESC";
$stmt = $conn->prepare($query);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $complaints = $result->fetch_all(MYSQLI_ASSOC);
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

// Fetch all users with department and designation for transfer mapping
$dept_users_list = [];
$username_to_dept = [];
$username_to_name = [];
$users_res = $conn->query("SELECT username, name, designation, department, taluka FROM users WHERE department IS NOT NULL AND department != '' AND designation IS NOT NULL AND designation != '' ORDER BY name ASC");
if ($users_res) {
    while ($row = $users_res->fetch_assoc()) {
        $username = trim($row['username']);
        $dept = trim($row['department']);
        $name = trim($row['name']);

        $username_to_dept[$username] = $dept;
        $username_to_name[$username] = $name;

        if (!isset($dept_users_list[$dept])) {
            $dept_users_list[$dept] = [];
        }
        $dept_users_list[$dept][] = [
            'username' => $username,
            'name' => $name,
            'designation' => $row['designation'],
            'taluka' => $row['taluka']
        ];
    }
}

$conn->close();

$can_perform_actions = false;
if (in_array(strtolower($role), ['bdo', 'tho', 'hod'])) {
    $can_perform_actions = true;
}

function badgeClass($status, $transfer_to = '')
{
    if (!empty($transfer_to) && strtolower(trim($status)) === 'pending') {
        return 'transfer';
    }
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'pending';
        case 'in progress':
            return 'in-progress';
        case 'resolved':
            return 'resolved';
        case 'closed':
            return 'closed';
        default:
            return 'open';
    }
}

function formatDate($dateString)
{
    $timestamp = strtotime($dateString);
    return $timestamp ? date('d F Y', $timestamp) : htmlspecialchars($dateString);
}
?>

<?php include('include/header.php'); ?>
<!-- DataTables Responsive CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<?php include('include/sidebar.php'); ?>

<main class="main-content">
    <?php
    $view = $_GET['view'] ?? 'assigned';
    if ($view === 'transfer') {
        $page_header_title = '📋 तक्रार हस्तांतरण (Transfer Issues)';
        $page_header_desc = 'इतर विभागांकडे वर्ग करावयाच्या तक्रारींचे हस्तांतरण करा';
    } else {
        $page_header_title = '📋 नियुक्त तक्रारी (Assigned Issues)';
        $page_header_desc = 'आपल्या विभागातील नियुक्त तक्रारींचे निवारण व योग्य ती क्रिया करा';
    }
    ?>
    <!-- Page Header -->
    <div class="page-header-container">
        <div class="page-title">
            <h1><?php echo htmlspecialchars($page_header_title); ?></h1>
            <p><?php echo htmlspecialchars($page_header_desc); ?></p>
        </div>

    </div>

    <!-- Filters & Search Section -->
    <div class="filter-section">
        <div class="filter-group">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="समस्या क्रमांक, विषय किंवा गाव वारून शोधा...">
                <span class="search-icon">🔍</span>
            </div>

            <div class="filter-controls">
                <select id="statusFilter" class="filter-select">
                    <option value="">सर्व स्थिती</option>
                    <option value="Pending">🟡 प्रलंबित</option>
                    <option value="Resolved">🟣 निराकृत</option>
                    <option value="Transfer">🔵 हस्तांतरित</option>
                </select>

                <select id="departmentFilter" class="filter-select">
                    <option value="">सर्व विभाग</option>
                    <?php foreach ($distinct_departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn-secondary" onclick="resetFilters()">🔄 रीसेट</button>
                <button class="btn-secondary" onclick="exportComplaints()">📥 निर्यात</button>
            </div>
        </div>
    </div>

    <!-- Complaints Table -->
    <div class="table-wrapper">
        <table id="complaintsTable" class="complaints-table">
            <thead>
                <tr>
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
                    <th>क्रिया</th>
                </tr>
            </thead>
            <tbody id="complaintTableBody">
                <?php if (!empty($complaints)): ?>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php
                        $status = $complaint['status'] ?? 'Open';
                        $display_status = $status;
                        if (!empty($complaint['transfer_to']) && strtolower(trim($status)) === 'pending') {
                            $display_status = 'Transfer';
                        }
                        $badgeClass = badgeClass($status, $complaint['transfer_to'] ?? '');

                        $dept_display = $complaint['department'];
                        if (isset($username_to_dept[$dept_display])) {
                            $dept_display = $username_to_dept[$dept_display];
                        }
                        ?>
                        <tr class="complaint-row" data-status="<?= htmlspecialchars($display_status); ?>"
                            data-department="<?= htmlspecialchars($dept_display); ?>"
                            data-village="<?= htmlspecialchars($complaint['village']); ?>">
                            <td class="complaint-id"><?= htmlspecialchars($complaint['issue_number']); ?></td>
                            <td class="photo-cell">
                                <?php if (!empty($complaint['photo'])): ?>
                                    <img src="<?= htmlspecialchars($complaint['photo']); ?>" alt="तक्रार फोटो"
                                        class="complaint-photo">
                                <?php else: ?>
                                    <span class="no-file-text" style="color: #64748b; font-size: 0.85rem; font-style: italic;">No
                                        File</span>
                                <?php endif; ?>
                            </td>
                            <td class="complaint-subject">
                                <strong><?= htmlspecialchars($complaint['description']); ?></strong>
                                <p class="complaint-desc"><?= htmlspecialchars($complaint['description']); ?></p>
                            </td>
                            <td><?= htmlspecialchars($dept_display); ?></td>
                            <td><?= htmlspecialchars($complaint['department_head'] ?? 'विभाग प्रमुख'); ?></td>
                            <td><?= htmlspecialchars($complaint['village']); ?></td>
                            <td><?= htmlspecialchars($complaint['taluka'] ?? 'Hingoli'); ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($complaint['registration_type']); ?></span></td>
                            <td><?= formatDate($complaint['issue_date']); ?></td>
                            <td><span class="badge-status <?= $badgeClass; ?>"><?= htmlspecialchars($display_status); ?></span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <?php if (in_array(strtolower($status), ['resolved', 'closed'])): ?>
                                        <button class="btn-icon btn-action" disabled
                                            style="background-color: #cbd5e1; border-color: #cbd5e1; color: #94a3b8; cursor: not-allowed;"
                                            title="<?= htmlspecialchars($status); ?>">
                                            <i class="fa-solid fa-check"></i> <?= htmlspecialchars($status); ?>
                                        </button>
                                        <button class="btn-icon btn-transfer" disabled
                                            style="background-color: #cbd5e1; border-color: #cbd5e1; color: #94a3b8; cursor: not-allowed;"
                                            title="हस्तांतरण">
                                            <i class="fa-solid fa-right-left"></i> Transfer
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-icon btn-action" onclick="handleResolveClick(this)"
                                            data-status="<?= strtolower($status); ?>" data-issue='<?= json_encode($complaint); ?>'>
                                            <i class="fa-solid fa-check"></i> Resolve
                                        </button>
                                        <button class="btn-icon btn-transfer" title="हस्तांतरण" onclick="openTransferModal('<?= htmlspecialchars($complaint['issue_number']); ?>', '<?= htmlspecialchars($complaint['department']); ?>', '<?= htmlspecialchars($complaint['taluka']); ?>')">
                                                        <i class="fa-solid fa-right-left"></i> Transfer
                                                    </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Empty State Message -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">📭</div>
        <h3>कोणत्याही तक्रारी नाहीत</h3>
        <p>आपल्यासाठी आता कोणत्याही तक्रारी रेकॉर्ड नाहीत।</p>
        <button class="btn-primary" onclick="openNewComplaintForm()">नवीन तक्रार दाखल करा</button>
    </div>

    <!-- Pagination is handled automatically by DataTables -->

    <!-- Transfer Modal -->
    <div id="transferModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeTransferModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>समस्या हस्तांतरित करा</h2>
                <button class="modal-close" onclick="closeTransferModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="transferForm">
                    <input type="hidden" id="complaintIdTransfer" />

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; color: #1e293b; font-size: 1rem;">
                            समस्या क्रमांक: <span id="transferIssueNumVal"
                                style="font-weight: bold; color: #000; margin-left: 5px;"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="transferUser"
                            style="font-weight: 600; margin-bottom: 6px; display: block; color: #1e293b;">लक्ष्य अधिकारी
                            निवडा (Select Target Officer):</label>
                        <select id="transferUser" class="form-control" required>
                            <option value="">-- अधिकारी निवडा --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transferNotes"
                            style="font-weight: 600; margin-bottom: 6px; display: block; color: #1e293b;">हस्तांतरण
                            कारण:</label>
                        <textarea id="transferNotes" class="form-control" rows="4" required
                            style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px;"
                            placeholder="हस्तांतरणाचे कारण लिहा..."></textarea>
                    </div>

                    <div class="modal-footer" style="border-top: none; padding-top: 10px;">
                        <button type="button" class="btn-secondary" onclick="closeTransferModal()"
                            style="background-color: #64748b; color: white; border: none; border-radius: 6px; padding: 10px 20px;">रद्द
                            करा</button>
                        <button type="submit" class="btn-primary"
                            style="background-color: #eab308; border-color: #ca8a04; color: white; border: none; border-radius: 6px; padding: 10px 20px;">हस्तांतरित
                            करा</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    <div id="resolveModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeResolveModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>समस्या निवारण करा (Resolve Issue)</h2>
                <button class="modal-close" onclick="closeResolveModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="resolveForm" enctype="multipart/form-data">
                    <input type="hidden" name="issue_number" id="resolveIssueNumber" />

                    <div class="form-group-row">
                        <strong>समस्या क्रमांक:</strong>
                        <span id="resolveIssueNumDisplay"></span>
                    </div>
                    <div class="form-group-row">
                        <strong>समस्या विषय:</strong>
                        <span id="resolveDescDisplay"></span>
                    </div>
                    <div class="form-group-row">
                        <strong>विभाग:</strong>
                        <span id="resolveDeptDisplay"></span>
                    </div>
                    <div class="form-group-row">
                        <strong>गाव:</strong>
                        <span id="resolveVillageDisplay"></span>
                    </div>
                    <div class="form-group-row">
                        <strong>प्रकार:</strong>
                        <span id="resolveTypeDisplay"></span>
                    </div>
                    <div class="form-group-row">
                        <strong>स्थिती:</strong>
                        <span class="badge-status resolved">Resolved</span>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label for="resolvePhoto"
                            style="font-weight: 600; margin-bottom: 6px; display: block; color: #1e293b;">निवारण फोटो
                            अपलोड करा (Upload Photo):</label>
                        <input type="file" name="photo" id="resolvePhoto" class="form-control" accept="image/*" required
                            style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px;" />
                    </div>

                    <div class="form-group">
                        <label for="resolveRemark"
                            style="font-weight: 600; margin-bottom: 6px; display: block; color: #1e293b;">निवारण टिप्पणी
                            (Resolved Remark):</label>
                        <textarea name="resolved_remark" id="resolveRemark" class="form-control" rows="4" required
                            style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px;"
                            placeholder="निवारणाचे कारण किंवा टिप्पणी लिहा..."></textarea>
                    </div>

                    <div class="modal-footer" style="border-top: none; padding-top: 10px;">
                        <button type="button" class="btn-secondary" onclick="closeResolveModal()"
                            style="background-color: #64748b; color: white; border: none; border-radius: 6px; padding: 10px 20px;">रद्द
                            करा</button>
                        <button type="submit" class="btn-primary"
                            style="background-color: #22c55e; border: none; border-radius: 6px; padding: 10px 20px; color: white;">निवारण
                            करा</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Styles -->
    <style>
        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }

        .filter-controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Table Wrapper */
        .table-wrapper {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        /* Table Styles */
        .complaints-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .complaints-table thead {
            background: linear-gradient(90deg, #f8fafc, #f1f5f9);
            border-bottom: 2px solid #e2e8f0;
        }

        .complaints-table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .complaints-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .complaints-table tbody tr:hover {
            background-color: #f8fafc;
            transition: background 0.2s ease;
        }

        .complaint-id {
            font-weight: 700;
            color: #3b82f6;
            font-family: 'Courier New', monospace;
        }

        /* Photo Cell */
        .photo-cell {
            text-align: center;
        }

        .complaint-photo {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .complaint-photo:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Complaint Subject */
        .complaint-subject {
            max-width: 280px;
        }

        .complaint-subject strong {
            display: block;
            color: #1e293b;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .complaint-desc {
            color: #64748b;
            font-size: 0.85rem;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Badge Styles */
        .badge-type,
        .badge-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-type {
            background: #f3f4f6;
            color: #374151;
        }

        .badge-status {
            background: #f3f4f6;
            color: #374151;
        }

        .badge-status.pending {
            background: #ffedd5;
            color: #ea580c;
        }

        .badge-status.open {
            background: #dcfce7;
            color: #166534;
        }

        .badge-status.in-progress {
            background: #fef3c7;
            color: #854d0e;
        }

        .badge-status.resolved {
            background: #ddd6fe;
            color: #5b21b6;
        }

        .badge-status.closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-status.transfer {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* --- DataTables Custom Styling --- */
        .dataTables_wrapper {
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
            color: #475569;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: white;
            color: #1e293b;
            outline: none;
            transition: all 0.3s ease;
            margin-left: 8px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: white;
            color: #1e293b;
            outline: none;
            margin: 0 4px;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 20px;
            color: #64748b;
            font-size: 13px;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            background: white !important;
            color: #475569 !important;
            cursor: pointer;
            transition: all 0.2s ease !important;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #2563eb !important;
            color: white !important;
            border-color: #2563eb !important;
            font-weight: 700;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #cbd5e1 !important;
            cursor: not-allowed;
        }

        /* Action Cell */
        .action-cell {
            display: flex;
            gap: 6px;
        }

        .btn-icon {
            background: none;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: 35px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.15s ease, transform 0.12s ease;
            gap: 6px;
        }

        .btn-action {
            background-color: #22c55e;
            color: white;
            border: 1px solid #16a34a;
        }

        .btn-action:hover {
            background-color: #16a34a;
        }

        .btn-transfer {
            background-color: #f59e0b;
            color: white;
            border: 1px solid #d97706;
        }

        .btn-transfer:hover {
            background-color: #d97706;
        }

        /* Modal Styles Redesign */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }

        /* Ensure SweetAlert dialogs show on top of the modals */
        .swal2-container {
            z-index: 999999 !important;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            cursor: pointer;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            z-index: 2001;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            animation: modalSlideUp 0.3s ease-out;
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            border-bottom: none;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.01em;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
            color: white;
        }

        .modal-body {
            padding: 24px;
            background-color: #ffffff;
        }

        /* Form Row Layout inside Modals */
        .form-group-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 10px;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            align-items: center;
            font-size: 0.95rem;
        }

        .form-group-row strong {
            color: #64748b;
            font-weight: 600;
        }

        .form-group-row span {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        /* Modern Input Styling for Modals */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease-in-out;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
            background-color: #fff;
            color: #0f172a;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        /* Modal Footer Styling */
        .modal-footer {
            display: flex;
            gap: 12px;
            padding: 16px 24px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            justify-content: flex-end;
            margin-top: 20px;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }

        .modal-footer button {
            margin: 0;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-footer .btn-secondary {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
        }

        .modal-footer .btn-secondary:hover {
            background: #e2e8f0 !important;
            color: #1e293b !important;
        }

        /* Specific Submission Button Styling */
        #transferForm .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: white !important;
            border: 1px solid #d97706 !important;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.25) !important;
        }

        #transferForm .btn-primary:hover {
            background: linear-gradient(135deg, #d97706, #b45309) !important;
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.35) !important;
            transform: translateY(-1px);
        }

        #resolveForm .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white !important;
            border: 1px solid #059669 !important;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25) !important;
        }

        #resolveForm .btn-primary:hover {
            background: linear-gradient(135deg, #059669, #047857) !important;
            box-shadow: 0 6px 14px rgba(16, 185, 129, 0.35) !important;
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 24px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        .page-btn {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .page-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .page-info {
            color: #64748b;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .complaint-subject {
                max-width: 200px;
            }

            .filter-controls {
                flex-direction: column;
            }

            .filter-select {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .page-header-container {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-primary {
                width: 100%;
            }

            .complaints-table {
                font-size: 0.85rem;
            }

            .complaints-table th,
            .complaints-table td {
                padding: 12px 8px;
            }

            .complaint-subject {
                max-width: 150px;
            }

            .action-cell {
                flex-direction: column;
            }

            .btn-icon {
                width: 100%;
                text-align: left;
                padding: 8px 6px;
            }

            /* Prevent DataTable controls overflow and center them on mobile */
            .dataTables_wrapper {
                padding: 12px !important;
            }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center;
                float: none;
                margin-bottom: 12px;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 6px;
                box-sizing: border-box;
            }
            .dataTables_wrapper .dataTables_paginate {
                justify-content: center !important;
                flex-wrap: wrap;
                gap: 4px;
            }
        }

        @media (max-width: 480px) {
            .page-title h1 {
                font-size: 1.5rem;
            }

            .complaints-table {
                font-size: 0.8rem;
            }

            .complaints-table th,
            .complaints-table td {
                padding: 8px 6px;
            }

            .complaint-id {
                font-size: 0.85rem;
            }

            .complaint-photo {
                width: 40px;
                height: 40px;
            }

            .badge-type,
            .badge-status {
                font-size: 0.75rem;
                padding: 4px 8px;
            }

            .form-group-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.95rem;
            }

            .form-group-row strong {
                color: #475569;
                font-weight: 600;
            }

            .form-group-row span {
                color: #0f172a;
                font-weight: 500;
                text-align: right;
                max-width: 60%;
                word-break: break-word;
            }
        }

        /* Custom DataTables Responsive Styles */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control {
            position: relative;
            padding-left: 35px !important;
            cursor: pointer;
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            top: 50%;
            left: 10px;
            height: 18px;
            width: 18px;
            margin-top: -9px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            color: white !important;
            border: 2px solid var(--bg-card, #ffffff);
            border-radius: 50%;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
            box-sizing: border-box;
            font-family: var(--font-body), sans-serif;
            content: "+";
            background-color: var(--primary-light, #2563eb);
            font-weight: 700;
            font-size: 14px;
            line-height: 1;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
            content: "−";
            background-color: var(--danger-color, #dc2626);
            transform: rotate(180deg);
        }

        /* Styling for the expanded child row */
        table.dataTable > tbody > tr.child {
            background-color: var(--bg-hover, #f1f5f9) !important;
        }

        table.dataTable > tbody > tr.child:hover {
            background-color: var(--bg-hover, #f1f5f9) !important;
        }

        table.dataTable > tbody > tr.child ul.dtr-details {
            display: block;
            list-style-type: none;
            margin: 0;
            padding: 12px 16px;
            width: 100%;
        }

        table.dataTable > tbody > tr.child ul.dtr-details > li {
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            gap: 15px;
        }

        table.dataTable > tbody > tr.child ul.dtr-details > li:last-child {
            border-bottom: none;
        }

        table.dataTable > tbody > tr.child span.dtr-title {
            font-weight: 600;
            color: var(--text-secondary, #475569);
            min-width: 120px;
        }

        table.dataTable > tbody > tr.child span.dtr-data {
            color: var(--text-primary, #0f172a);
            text-align: right;
            word-break: break-word;
        }
    </style>

    <!-- JavaScript Functions -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const deptUsersList = <?php echo json_encode($dept_users_list); ?>;

        $(document).ready(function () {
            const table = $('#complaintsTable').DataTable({
                "dom": 'lrtip',
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "ordering": true,
                "order": [],
                "responsive": true,
                "columnDefs": [
                    { "responsivePriority": 1, "targets": 0 }, // समस्या क्रमांक
                    { "responsivePriority": 1, "targets": 2 }, // समस्या विषय (description)
                    { "responsivePriority": 2, "targets": 10 }, // क्रिया (buttons) - collapses on smaller screens
                    { "responsivePriority": 3, "targets": 9 }  // स्थिती
                ],
                "language": {
                    "lengthMenu": "दाखवा _MENU_ नोंदी",
                    "paginate": {
                        "previous": "← मागे",
                        "next": "पुढे →"
                    },
                    "info": "एकूण _TOTAL_ पैकी _START_ ते _END_ दाखवत आहे",
                    "infoEmpty": "माहिती उपलब्ध नाही",
                    "zeroRecords": "कोणतेही रेकॉर्ड सापडले नाहीत"
                }
            });

            $('#searchInput').on('keyup', function () {
                table.search(this.value).draw();
            });

            $('#statusFilter').on('change', function () {
                // Exact matching for status column (Index 9) ignoring surrounding whitespace
                const val = this.value;
                table.column(9).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
            });

            $('#departmentFilter').on('change', function () {
                // Exact matching for department column (Index 3) ignoring surrounding whitespace
                const val = this.value;
                table.column(3).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
            });
        });

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            const table = $('#complaintsTable').DataTable();
            table.search('').columns().search('').draw();
        }

        function actionComplaint(issueNumber) {
            if (confirm("तक्रार #" + issueNumber + " चे निराकरण झाले आहे का? (Is the complaint resolved?)")) {
                fetch('resolve_complaint.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'issue_number=' + encodeURIComponent(issueNumber) + '&status=Resolved'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('तक्रार यशस्वीरित्या निराकृत केली गेली!');
                            location.reload();
                        } else {
                            alert('त्रुटी: ' + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('निराकरण करताना त्रुटी आली.');
                    });
            }
        }

        function openNewComplaintForm() {
            window.location.href = 'issueform.php';
        }

        function exportComplaints() {
            let csv = 'समस्या क्रमांक,विषय,विभाग,नियुक्त अधिकारी,गाव,तालुका,प्रकार,दिनांक,स्थिती\n';
            const table = $('#complaintsTable').DataTable();
            const filteredRows = table.rows({ search: 'applied' }).nodes();

            filteredRows.each(function (row) {
                const id = row.querySelector('.complaint-id').textContent.trim();
                const subject = row.querySelector('.complaint-subject strong').textContent.trim();
                const cells = row.querySelectorAll('td');
                const department = cells[3].textContent.trim();
                const deptHead = cells[4].textContent.trim();
                const village = cells[5].textContent.trim();
                const taluka = cells[6].textContent.trim();
                const type = cells[7].textContent.trim();
                const date = cells[8].textContent.trim();
                const status = cells[9].textContent.trim();

                csv += '"' + id + '","' + subject + '","' + department + '","' + deptHead + '","' + village + '","' + taluka + '","' + type + '","' + date + '","' + status + '"\n';
            });

            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            link.download = 'तक्रारी.csv';
            link.click();
        }

        function openTransferModal(complaintId, department, taluka) {
            document.getElementById('complaintIdTransfer').value = complaintId;
            document.getElementById('transferIssueNumVal').textContent = complaintId;

            const transferUserSelect = document.getElementById('transferUser');
            transferUserSelect.innerHTML = '<option value="">-- अधिकारी निवडा --</option>';

            // Find the actual department if a username was passed
            let targetDept = department;
            for (const deptName in deptUsersList) {
                const userFound = deptUsersList[deptName].find(u => u.username === department);
                if (userFound) {
                    targetDept = deptName;
                    break;
                }
            }

            const currentLoggedUser = '<?php echo $_SESSION['username'] ?? ''; ?>';

            function formatUserOptionText(user) {
                let text = user.designation || '';
                if (user.taluka) {
                    text += ' - ' + user.taluka;
                }
                return text;
            }

            const issueTaluka = (taluka || '').trim().toLowerCase();

            // 1. Related designations of that department
            if (targetDept && deptUsersList[targetDept]) {
                deptUsersList[targetDept].forEach(function (user) {
                    if (user.username !== currentLoggedUser && user.username !== department) {
                        const userTaluka = (user.taluka || '').trim().toLowerCase();

                        // Filter by taluka if it is defined for both the issue and target user (or if target user is district-level)
                        if (!issueTaluka || issueTaluka === userTaluka || !userTaluka) {
                            if (!transferUserSelect.querySelector('option[value="' + user.username + '"]')) {
                                const option = document.createElement('option');
                                option.value = user.username;
                                option.textContent = formatUserOptionText(user);
                                transferUserSelect.appendChild(option);
                            }
                        }
                    }
                });
            }

            // 2. BDO of that taluka
            for (const deptName in deptUsersList) {
                deptUsersList[deptName].forEach(function (user) {
                    if (user.username !== currentLoggedUser && user.username !== department) {
                        const isBDO = user.username.toLowerCase().startsWith('bdo') ||
                            user.designation.includes('गट विकास अधिकारी') ||
                            user.username.toLowerCase().includes('bdo');
                        if (isBDO) {
                            const userTaluka = (user.taluka || '').trim().toLowerCase();

                            if (issueTaluka === userTaluka || !userTaluka) {
                                // Prevent duplicate option
                                if (!transferUserSelect.querySelector('option[value="' + user.username + '"]')) {
                                    const option = document.createElement('option');
                                    option.value = user.username;
                                    option.textContent = formatUserOptionText(user);
                                    transferUserSelect.appendChild(option);
                                }
                            }
                        }
                    }
                });
            }

            document.getElementById('transferModal').style.display = 'flex';
        }

        function closeTransferModal() {
            document.getElementById('transferModal').style.display = 'none';
            document.getElementById('transferForm').reset();
        }

        // Handle transfer form submission via AJAX
        document.getElementById('transferForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const complaintId = document.getElementById('complaintIdTransfer').value;
            const transferTo = document.getElementById('transferUser').value;
            const notes = document.getElementById('transferNotes').value;

            if (!transferTo) {
                Swal.fire({
                    title: 'त्रुटी!',
                    text: 'कृपया अधिकारी निवडा',
                    icon: 'warning',
                    confirmButtonText: 'ठीक आहे'
                });
                return;
            }

            fetch('transfer_complaint.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'issue_number=' + encodeURIComponent(complaintId) +
                    '&department=' + encodeURIComponent(transferTo) +
                    '&notes=' + encodeURIComponent(notes)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'यशस्वी!',
                            text: 'तक्रार यशस्वीरित्या हस्तांतरित केली गेली!',
                            icon: 'success',
                            confirmButtonText: 'ठीक आहे'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'त्रुटी!',
                            text: 'त्रुटी: ' + data.message,
                            icon: 'error',
                            confirmButtonText: 'ठीक आहे'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        title: 'त्रुटी!',
                        text: 'हस्तांतरण प्रक्रिया दरम्यान त्रुटी आली.',
                        icon: 'error',
                        confirmButtonText: 'ठीक आहे'
                    });
                });
        });

        // Resolve Modal JS Handlers
        function openResolveModal(complaint) {
            document.getElementById('resolveIssueNumber').value = complaint.issue_number;
            document.getElementById('resolveIssueNumDisplay').textContent = complaint.issue_number;
            document.getElementById('resolveDescDisplay').textContent = complaint.description;
            document.getElementById('resolveDeptDisplay').textContent = complaint.department;
            document.getElementById('resolveVillageDisplay').textContent = complaint.village;
            document.getElementById('resolveTypeDisplay').textContent = complaint.registration_type;
            document.getElementById('resolveModal').style.display = 'flex';
        }

        function closeResolveModal() {
            document.getElementById('resolveModal').style.display = 'none';
            document.getElementById('resolveForm').reset();
        }

        // Handle resolve form submission via AJAX
        document.getElementById('resolveForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('resolve_complaint.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Issue is successfully resolved',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'त्रुटी!',
                            text: 'त्रुटी: ' + data.message,
                            icon: 'error',
                            confirmButtonText: 'ठीक आहे'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        title: 'त्रुटी!',
                        text: 'निराकरण प्रक्रिया दरम्यान त्रुटी आली.',
                        icon: 'error',
                        confirmButtonText: 'ठीक आहे'
                    });
                });
        });

        // Close modal when clicking overlay
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeTransferModal();
                closeResolveModal();
            }
        });

        // Initialize on page load
        window.addEventListener('load', function () {
            // Already handled by DataTable initialization
        });
        function handleResolveClick(btn) {

            let status = btn.getAttribute("data-status");

            // ❌ STOP popup if resolved
            if (status === "resolved") {
                alert("Already resolved");
                return;
            }

            let complaint = JSON.parse(btn.getAttribute("data-issue"));

            // ✅ open modal
            openResolveModal(complaint);
        }
    </script>

    <?php include('include/footer.php'); ?>
</main>