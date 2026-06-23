<?php
// complaint.php - User Complaint Records Page
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'include/config.php';

$role = $_SESSION['user_system_role'] ?? $_SESSION['user_role'] ?? '';

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

function badgeClass($status)
{
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'pending';
        case 'resolved':
            return 'resolved';
        default:
            return 'Total';
    }
}

function formatDate($dateString)
{
    $timestamp = strtotime($dateString);
    return $timestamp ? date('d F Y', $timestamp) : htmlspecialchars($dateString);
}
?>

<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header-container">
        <div class="page-title">
            <h1>📋 माझी तक्रारी</h1>
            <p>आपल्या सर्व तक्रारीचे रेकॉर्ड पहा आणि व्यवस्थापित करा</p>
        </div>
        <button class="btn-primary" onclick="openNewComplaintForm()">
            ➕ नवीन तक्रार दाखल करा
        </button>
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
                    <option value="">🟢 एकूण</option>
                    <option value="Pending">🟡 प्रलंबित</option>
                    <option value="Resolved">🟣 निराकृत</option>
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
                </tr>
            </thead>
            <tbody id="complaintTableBody">
                <?php if (!empty($complaints)): ?>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php
                        $status = $complaint['status'] ?? 'Open';
                        $badgeClass = badgeClass($status);
                        ?>
                        <tr class="complaint-row" data-status="<?= strtolower(trim($status)); ?>"
                            data-department="<?= htmlspecialchars($complaint['department']); ?>"
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
                            <td><?= htmlspecialchars($complaint['department']); ?></td>
                            <td><?= htmlspecialchars($complaint['department_head'] ?? 'विभाग प्रमुख'); ?></td>
                            <td><?= htmlspecialchars($complaint['village']); ?></td>
                            <td><?= htmlspecialchars($complaint['taluka'] ?? 'Hingoli'); ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($complaint['registration_type']); ?></span></td>
                            <td><?= formatDate($complaint['issue_date']); ?></td>
                            <td><span class="badge-status <?= $badgeClass; ?>"><?= htmlspecialchars($status); ?></span></td>
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



    <!-- Transfer Modal -->
    <div id="transferModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeTransferModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>तक्रार हस्तांतरण</h2>
                <button class="modal-close" onclick="closeTransferModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="transferForm">
                    <input type="hidden" id="complaintIdTransfer" />

                    <div class="form-group">
                        <label>समस्या क्रमांक</label>
                        <input type="text" id="transferIssueNumDisplay" class="form-control" readonly />
                    </div>

                    <div class="form-group">
                        <label for="transferDepartment">विभाग निवडा:</label>
                        <select id="transferDepartment" class="form-control" required>
                            <option value="">-- विभाग निवडा --</option>
                            <?php foreach ($distinct_departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transferDeptHead">संबंधित विभाग प्रमुख:</label>
                        <select id="transferDeptHead" class="form-control" required>
                            <option value="">-- निवडा विभाग प्रमुख --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transferDate">हस्तांतरण दिनांक:</label>
                        <input type="text" id="transferDate" class="form-control" readonly />
                    </div>

                    <div class="form-group">
                        <label for="transferNotes">टिप्पणी:</label>
                        <textarea id="transferNotes" class="form-control" rows="4"
                            placeholder="हस्तांतरणाचे कारण लिहा..."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeTransferModal()">रद्द करा</button>
                        <button type="submit" class="btn-primary">हस्तांतरण करा</button>
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
            overflow-x: auto;
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
            width: 40px;
            height: 40px;
            cursor: pointer;
            padding: 0;
            border-radius: 8px;
            transition: background 0.15s ease, transform 0.12s ease;
        }

        .btn-icon svg {
            width: 20px;
            height: 20px;
            display: block;
            fill: currentColor;
        }

        .btn-edit {
            color: #3b82f6;
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-view {
            color: #8b5cf6;
        }

        .btn-view:hover {
            background: rgba(139, 92, 246, 0.1);
        }

        .btn-delete {
            color: #ef4444;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .btn-transfer {
            color: #f59e0b;
        }

        .btn-transfer:hover {
            background: rgba(245, 158, 11, 0.1);
        }

        /* Modal Styles */
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
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 2001;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-header h2 {
            margin: 0;
            color: #1e293b;
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #64748b;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control[readonly] {
            background: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            justify-content: flex-end;
        }

        .modal-footer .btn-primary,
        .modal-footer .btn-secondary {
            margin: 0;
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
        }
    </style>

    <!-- JavaScript Functions -->
    <script>
        const deptDesignations = <?php echo json_encode($dept_designations); ?>;

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

            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#statusFilter').on('change', function() {
                // Column index 9 is the status column (0-indexed) after adding Assigned Officer column
                table.column(9).search(this.value).draw();
            });

            $('#departmentFilter').on('change', function() {
                // Column index 3 is the department column
                table.column(3).search(this.value).draw();
            });
        });

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            const table = $('#complaintsTable').DataTable();
            table.search('').columns().search('').draw();
        }

        function editComplaint(id) {
            alert('तक्रार #' + id + ' संपादित करण्याचे फॉर्म उघडणे...');
        }

        function viewComplaint(id) {
            alert('तक्रार #' + id + ' चे तपशील पहाणे...');
        }

        function deleteComplaint(id) {
            if (confirm('क्या आप खरोखर हे तक्रार हटवू शकता?')) {
                alert('तक्रार #' + id + ' हटवल्या गेले आहे');
            }
        }

        function openNewComplaintForm() {
            window.location.href = 'issueform.php';
        }

        function exportComplaints() {
            let csv = 'समस्या क्रमांक,विषय,विभाग,नियुक्त अधिकारी,गाव,तालुका,प्रकार,दिनांक,स्थिती\n';
            const table = $('#complaintsTable').DataTable();
            const filteredRows = table.rows({ search: 'applied' }).nodes();

            filteredRows.each(function(row) {
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
            link.download = 'माझी_तक्रारी.csv';
            link.click();
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
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            const dateTimeString = now.toLocaleDateString('en-GB', options).replace(/(\d+)\/(\d+)\/(\d+)/, '$3-$2-$1') + ' ' +
                now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
            document.getElementById('transferDate').value = dateTimeString;
        }

        // Handle transfer form submission via AJAX
        document.getElementById('transferForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const complaintId = document.getElementById('complaintIdTransfer').value;
            const department = document.getElementById('transferDepartment').value;
            const deptHead = document.getElementById('transferDeptHead').value;
            const notes = document.getElementById('transferNotes').value;

            if (!department || !deptHead) {
                alert('कृपया विभाग आणि संबंधित विभाग प्रमुख निवडा');
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
                        alert('तक्रार यशस्वीरित्या हस्तांतरित केली गेली!');
                        location.reload();
                    } else {
                        alert('त्रुटी: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('हस्तांतरण प्रक्रिया दरम्यान त्रुटी आली.');
                });
        });

        // Close modal when clicking overlay
        document.addEventListener('click', function (e) {
            const modal = document.getElementById('transferModal');
            if (e.target === document.querySelector('.modal-overlay')) {
                closeTransferModal();
            }
        });

        // Initialize on page load
        window.addEventListener('load', function () {
            // Already initialized via jQuery document ready
        });
    </script>

    <?php include('include/footer.php'); ?>
</main>