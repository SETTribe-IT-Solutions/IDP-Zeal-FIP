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
        default:
            return 'Total';
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
                    <th>Action</th>
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
                            <td>
                                <div class="action-cell">
                                    <button type="button" class="btn-icon btn-edit" title="<?= $editDisabled ? 'Edit disabled for resolved or transferred complaints' : 'Edit'; ?>"
                                        data-issue="<?= htmlspecialchars(json_encode($complaint), ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= $editDisabled ? 'disabled aria-disabled="true"' : 'onclick="openEditModalFromButton(this)"'; ?>>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm17.71-10.04a1 1 0 0 0 0-1.41L18.2 3.29a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75 2.13-1.79Z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon btn-delete" title="<?= $deleteDisabled ? 'Delete disabled for resolved or transferred complaints' : 'Delete'; ?>"
                                        <?= $deleteDisabled ? 'disabled aria-disabled="true"' : 'onclick="deleteComplaint(' . htmlspecialchars(json_encode($complaint['issue_number']), ENT_QUOTES, 'UTF-8') . ')"'; ?>>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12ZM8 9h8v10H8V9Zm7.5-5-1-1h-5l-1 1H5v2h14V4h-3.5Z" />
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
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">📭</div>
        <h3>कोणत्याही तक्रारी नाहीत</h3>
        <p>आपल्यासाठी आता कोणत्याही तक्रारी रेकॉर्ड नाहीत।</p>
        <button class="btn-primary" onclick="openNewComplaintForm()">नवीन तक्रार दाखल करा</button>
    </div>



    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeEditModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Complaint</h2>
                <button class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editIssueNumber" />
                    <div class="form-group">
                        <label>Issue Number</label>
                        <input type="text" id="editIssueDisplay" class="form-control" readonly />
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <textarea id="editDescription" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editDepartment">Department</label>
                        <select id="editDepartment" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($distinct_departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDepartmentHead">Assigned Officer</label>
                        <select id="editDepartmentHead" class="form-control">
                            <option value="">-- Select Officer --</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editVillage">Village</label>
                            <input type="text" id="editVillage" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="editTaluka">Taluka</label>
                            <input type="text" id="editTaluka" class="form-control" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editRegistrationType">Type</label>
                            <input type="text" id="editRegistrationType" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="editIssueDate">Date</label>
                            <input type="date" id="editIssueDate" class="form-control" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Open">Open</option>
                            <option value="In-Progress">In-Progress</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
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
        .main-content {
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 34%),
                linear-gradient(180deg, #f8fbff 0%, #f3f6fb 48%, #eef3f9 100%);
            min-height: calc(100vh - 76px);
            padding-bottom: 36px;
        }

        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 20px;
            padding: 8px 2px 2px;
            position: relative;
        }

        .page-title {
            position: relative;
            padding-left: 16px;
        }

        .page-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 8px;
            width: 4px;
            height: calc(100% - 12px);
            border-radius: 999px;
            background: linear-gradient(180deg, #2563eb, #f59e0b);
        }

        .page-title h1 {
            font-size: clamp(1.8rem, 2.4vw, 2.35rem);
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: 0;
            line-height: 1.15;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.98rem;
            line-height: 1.5;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 13px 24px;
            border-radius: 8px;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.24);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.3);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f8fafc;
            color: #1f365c;
            border: 1px solid #cbd8e8;
            padding: 11px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 650;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #ffffff;
            border-color: #94a3b8;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(203, 213, 225, 0.75);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 26px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(8px);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-box input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border: 1px solid #cbd8e8;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.11);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }

        .filter-controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-select {
            min-width: 170px;
            padding: 11px 38px 11px 14px;
            border: 1px solid #cbd8e8;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.11);
        }

        /* Table Wrapper */
        .table-wrapper {
            background: transparent;
            border: 0;
            border-radius: 0;
            overflow-x: auto;
            box-shadow: none;
            margin-bottom: 26px;
        }

        /* Table Styles */
        .complaints-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }

        .complaints-table thead {
            background: linear-gradient(90deg, #f8fafc, #eef4fb);
        }

        .complaints-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 800;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0;
            font-size: 0.78rem;
            border-bottom: 1px solid #d7e0ea;
            white-space: nowrap;
        }

        .complaints-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e8eef5;
            color: #1e293b;
            vertical-align: middle;
        }

        .complaints-table td.dtr-control,
        .complaints-table th:first-child {
            width: 42px;
            min-width: 42px;
            text-align: center;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
            background-color: #2563eb;
            border: 0;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28);
            line-height: 16px;
            top: 50%;
            transform: translateY(-50%);
        }

        table.dataTable>tbody>tr.child ul.dtr-details {
            display: grid;
            gap: 8px;
            width: 100%;
        }

        table.dataTable>tbody>tr.child ul.dtr-details>li {
            display: grid;
            grid-template-columns: minmax(112px, 34%) minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 0;
        }

        table.dataTable>tbody>tr.child span.dtr-title {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            white-space: normal;
        }

        table.dataTable>tbody>tr.child span.dtr-data {
            color: #1e293b;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .complaints-table tbody tr:hover {
            background-color: #f8fbff;
            transition: background 0.2s ease;
        }

        .complaint-id {
            font-weight: 700;
            color: #123766;
            font-family: 'Courier New', monospace;
            letter-spacing: 0;
        }

        /* Photo Cell */
        .photo-cell {
            text-align: center;
        }

        .complaint-photo {
            width: 46px;
            height: 46px;
            border-radius: 9px;
            object-fit: cover;
            border: 1px solid #d6e0ea;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
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
            color: #0f172a;
            margin-bottom: 4px;
            word-break: break-word;
            font-weight: 750;
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
            padding: 7px 13px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .badge-type {
            background: #eef2f7;
            color: #1f365c;
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
            gap: 8px;
            align-items: center;
        }

        .btn-icon {
            background: #f8fafc;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            cursor: pointer;
            padding: 0;
            border-radius: 8px;
            transition: background 0.15s ease, transform 0.12s ease, border-color 0.15s ease, box-shadow 0.15s ease;
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
            background: #eff6ff;
            border-color: #bfdbfe;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.12);
        }

        .btn-icon:disabled,
        .btn-icon[aria-disabled="true"] {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            background: transparent;
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
            background: #fef2f2;
            border-color: #fecaca;
            box-shadow: 0 6px 14px rgba(239, 68, 68, 0.1);
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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
            padding: 22px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(203, 213, 225, 0.75);
            border-radius: 12px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
            margin-bottom: 28px;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
            color: #475569;
            font-weight: 650;
        }
        .dataTables_wrapper .dataTables_filter input {
            padding: 9px 12px;
            border: 1px solid #cbd8e8;
            border-radius: 8px;
            background-color: white;
            color: #1e293b;
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
            margin-left: 8px;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.11);
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 8px 30px 8px 12px;
            border: 1px solid #cbd8e8;
            border-radius: 8px;
            background-color: white;
            color: #1e293b;
            outline: none;
            margin: 0 4px;
        }
        .dataTables_wrapper .dataTables_info {
            padding-top: 20px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 13px !important;
            border: 1px solid #cbd8e8 !important;
            border-radius: 8px !important;
            background: white !important;
            color: #1f365c !important;
            cursor: pointer;
            transition: all 0.2s ease !important;
            font-weight: 700;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #2563eb !important;
            color: white !important;
            border-color: #2563eb !important;
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
            .main-content {
                padding-left: 14px;
                padding-right: 14px;
            }

            .page-header-container {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }

            .btn-primary {
                width: 100%;
            }

            .filter-section,
            .dataTables_wrapper {
                padding: 16px;
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
                flex-direction: row;
                justify-content: flex-end;
            }

            .btn-icon {
                width: 38px;
                height: 38px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                padding-left: 12px;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .filter-section,
            .dataTables_wrapper {
                border-radius: 10px;
                padding: 14px;
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JavaScript Functions -->
    <script>
        const deptDesignations = <?php echo json_encode($dept_designations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const complaintsExportData = <?php echo json_encode($complaints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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
                // Column index 10 is the status column after adding the DTR control column.
                table.column(10).search(this.value).draw();
            });

            $('#departmentFilter').on('change', function() {
                // Column index 4 is the department column after adding the DTR control column.
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

        // Handle transfer form submission via AJAX
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
