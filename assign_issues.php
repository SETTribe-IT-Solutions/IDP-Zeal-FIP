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

// Determine query filter based on user role
$role = !empty($_SESSION['user_system_role']) ? $_SESSION['user_system_role'] : ($_SESSION['user_role'] ?? '');
$where = "1=1";
$params = [];
$types = "";

$normalizedRole = strtolower(trim($role));

if ($normalizedRole === 'ceo') {
    $where = "1=1";
} elseif ($role === 'ग्रामपंचायत अधिकारी' || $role === 'अंगणवाडी सेविका' || $role === 'शिक्षक' || $normalizedRole === 'teacher') {
    $where = "mobile = ?";
    $params[] = $_SESSION['user_mobile'] ?? '';
    $types .= "s";
} else {
    // BDO, THO, HoD
    $user_dept = $_SESSION['user_dept'] ?? '';
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

// Fetch distinct departments from database
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
if (in_array($role, ['CEO', 'BDO', 'THO', 'HoD'])) {
    $can_perform_actions = true;
}

function badgeClass($status)
{
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
                    <option value="">सर्व स्थिती</option>
                    <option value="Open">🟢 उघडलेली</option>
                    <option value="In Progress">🟡 प्रक्रियेतील</option>
                    <option value="Resolved">🟣 निराकृत</option>
                    <option value="Closed">🔴 बंद</option>
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
        <table class="complaints-table">
            <thead>
                <tr>
                    <th>समस्या क्रमांक</th>
                    <th>फोटो</th>
                    <th>समस्या विषय</th>
                    <th>विभाग</th>
                    <th>गाव</th>
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
                        $badgeClass = badgeClass($status);
                        $photoSrc = !empty($complaint['photo']) ? htmlspecialchars($complaint['photo']) : 'https://via.placeholder.com/50?text=Photo';
                        ?>
                        <tr class="complaint-row" data-status="<?= htmlspecialchars($status); ?>"
                            data-department="<?= htmlspecialchars($complaint['department']); ?>"
                            data-village="<?= htmlspecialchars($complaint['village']); ?>">
                            <td class="complaint-id"><?= htmlspecialchars($complaint['issue_number']); ?></td>
                            <td class="photo-cell">
                                <img src="<?= $photoSrc; ?>" alt="तक्रार फोटो" class="complaint-photo">
                            </td>
                            <td class="complaint-subject">
                                <strong><?= htmlspecialchars($complaint['description']); ?></strong>
                                <p class="complaint-desc"><?= htmlspecialchars($complaint['description']); ?></p>
                            </td>
                            <td><?= htmlspecialchars($complaint['department']); ?></td>
                            <td><?= htmlspecialchars($complaint['village']); ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($complaint['registration_type']); ?></span></td>
                            <td><?= formatDate($complaint['issue_date']); ?></td>
                            <td><span class="badge-status <?= $badgeClass; ?>"><?= htmlspecialchars($status); ?></span></td>
                            <td class="action-cell">
                                <?php if ($can_perform_actions): ?>
                                    <?php if ($view === 'transfer'): ?>
                                        <button class="btn-icon btn-transfer" title="हस्तांतरण"
                                            onclick="openTransferModal('<?= htmlspecialchars($complaint['issue_number']); ?>')">हस्तांतरण</button>
                                    <?php else: ?>
                                        <button class="btn-icon btn-action" title="क्रिया"
                                            onclick="actionComplaint('<?= htmlspecialchars($complaint['issue_number']); ?>')">निराकरण</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge-status open">View Only</span>
                                <?php endif; ?>
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

    <!-- Pagination -->
    <div class="pagination">
        <button class="page-btn" onclick="previousPage()">← मागे</button>
        <span class="page-info">पृष्ठ <span id="currentPage">1</span> / <span id="totalPages">1</span></span>
        <button class="page-btn" onclick="nextPage()">पुढे →</button>
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
            width: 60px;
            height: 35px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.15s ease, transform 0.12s ease;
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

        // Search Functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            filterComplaints();
        });

        // Filter Functionality
        document.getElementById('statusFilter').addEventListener('change', filterComplaints);
        document.getElementById('departmentFilter').addEventListener('change', filterComplaints);

        function filterComplaints() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;
            const rows = document.querySelectorAll('.complaint-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const complaintId = row.querySelector('.complaint-id').textContent.toLowerCase();
                const subject = row.querySelector('.complaint-subject').textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const department = row.getAttribute('data-department');

                const matchesSearch = complaintId.includes(searchTerm) || subject.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesDepartment = !departmentFilter || department === departmentFilter;

                if (matchesSearch && matchesStatus && matchesDepartment) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show empty state if no results
            const emptyState = document.getElementById('emptyState');
            if (visibleCount === 0) {
                emptyState.style.display = 'block';
                document.querySelector('.table-wrapper').style.display = 'none';
            } else {
                emptyState.style.display = 'none';
                document.querySelector('.table-wrapper').style.display = 'block';
            }
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            filterComplaints();
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
            let csv = 'समस्या क्रमांक,विषय,विभाग,गाव,प्रकार,दिनांक,स्थिती\n';
            const rows = document.querySelectorAll('.complaint-row');

            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const id = row.querySelector('.complaint-id').textContent;
                    const subject = row.querySelector('.complaint-subject strong').textContent;
                    const cells = row.querySelectorAll('td');
                    const department = cells[3].textContent.trim();
                    const village = cells[4].textContent.trim();
                    const type = cells[5].textContent.trim();
                    const date = cells[6].textContent.trim();
                    const status = cells[7].textContent.trim();

                    csv += '"' + id + '","' + subject + '","' + department + '","' + village + '","' + type + '","' + date + '","' + status + '"\n';
                }
            });

            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            link.download = 'तक्रारी.csv';
            link.click();
        }

        function previousPage() {
            alert('मागील पृष्ठकडे जाणे...');
        }

        function nextPage() {
            alert('पुढील पृष्ठकडे जाणे...');
        }

        function openTransferModal(complaintId) {
            document.getElementById('complaintIdTransfer').value = complaintId;
            document.getElementById('transferIssueNumDisplay').value = complaintId;
            setCurrentDateTime();
            document.getElementById('transferModal').style.display = 'flex';
            // Populate dept head if department was preset
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
            filterComplaints();
        });
    </script>

    <?php include('include/footer.php'); ?>
</main>