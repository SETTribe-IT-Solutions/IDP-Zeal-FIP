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
    
    <!-- Page Header -->
    <div class="page-header-container">
        <div class="page-title">
            <div class="title-icon-wrapper">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="title-text">
                <h1>माझी तक्रारी</h1>
                <p>आपल्या सर्व तक्रारीचे रेकॉर्ड पहा आणि व्यवस्थापित करा</p>
            </div>
        </div>
        <button class="btn-primary" onclick="openNewComplaintForm()">
            <span class="plus-icon">+</span> नवीन तक्रार दाखल करा
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">एकूण</div>
            <div class="stat-value"><?php echo $total_complaints; ?></div>
            <div class="stat-bg stat-bg-blue"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">PENDING</div>
            <div class="stat-value"><?php echo $pending_count; ?></div>
            <div class="stat-bg stat-bg-orange"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">TRANSFER</div>
            <div class="stat-value"><?php echo $transferred_count; ?></div>
            <div class="stat-bg stat-bg-cyan"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">RESOLVED</div>
            <div class="stat-value"><?php echo $resolved_count; ?></div>
            <div class="stat-bg stat-bg-green"></div>
        </div>
    </div>

    <!-- Filters & Search Section -->
    <div class="filter-section">
        <div class="search-box-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="समस्या क्रमांक, विषय किंवा गाव वारून शोधा...">
        </div>

        <div class="filter-controls-wrapper">
            <div class="filter-select-group">
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
            </div>

            <div class="action-buttons">
                <button class="btn-reset" onclick="resetFilters()">
                    <span class="reset-icon">↺</span> रीसेट
                </button>
                <button class="btn-export" onclick="exportComplaints()">
                    <span class="export-icon">⬇</span> निर्यात
                </button>
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
                            <td class="photo-cell">
                                <?php if (!empty($complaint['photo'])): ?>
                                    <img src="<?= htmlspecialchars($complaint['photo']); ?>" alt="तक्रार फोटो"
                                        class="complaint-photo">
                                <?php else: ?>
                                    <span class="no-file-text">No File</span>
                                <?php endif; ?>
                            </td>
                            <td class="complaint-subject">
                                <strong><?= htmlspecialchars($complaint['description']); ?></strong>
                            </td>
                            <td><?= htmlspecialchars($complaint['department']); ?></td>
                            <td><?= htmlspecialchars($complaint['department_head'] ?? 'विभाग प्रमुख'); ?></td>
                            <td><?= htmlspecialchars($complaint['village']); ?></td>
                            <td><?= htmlspecialchars($complaint['taluka'] ?? 'Hingoli'); ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($complaint['registration_type']); ?></span></td>
                            <td><?= formatDate($complaint['issue_date']); ?></td>
                            <td>
                                <span class="badge-status <?= $badgeClass; ?>">
                                    <?php if(strtolower($status) == 'transfer' || strtolower($status) == 'transferred'): ?>
                                        Transfer
                                    <?php else: ?>
                                        <?= htmlspecialchars($status); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <button type="button" class="btn-icon btn-edit" title="<?= $editDisabled ? 'Edit disabled' : 'Edit'; ?>"
                                        data-issue="<?= htmlspecialchars(json_encode($complaint), ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= $editDisabled ? 'disabled aria-disabled="true"' : 'onclick="openEditModalFromButton(this)"'; ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon btn-delete" title="<?= $deleteDisabled ? 'Delete disabled' : 'Delete'; ?>"
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
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">📭</div>
        <h3>कोणत्याही तक्रारी नाहीत</h3>
        <p>आपल्यासाठी आता कोणत्याही तक्रारी रेकॉर्ड नाहीत।</p>
        <button class="btn-primary" onclick="openNewComplaintForm()">नवीन तक्रार दाखल करा</button>
    </div>

    <!-- Edit Modal -->
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
        /* Main Layout */
        .main-content {
            background: #ffffff;
            min-height: calc(100vh - 76px);
            padding: 30px 40px 40px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        /* Page Header */
        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .title-icon-wrapper {
            width: 48px;
            height: 48px;
            background: #2563eb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .title-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 2px 0;
        }

        .title-text p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        /* Buttons */
        .btn-primary {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { background: #4338ca; }
        .plus-icon { font-size: 18px; line-height: 1; }

        /* Stats Grid (4 Cards) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
        }

        .stat-bg {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.5;
            pointer-events: none;
        }
        .stat-bg-blue { background: #dbeafe; }
        .stat-bg-orange { background: #ffedd5; }
        .stat-bg-cyan { background: #cffafe; }
        .stat-bg-green { background: #dcfce7; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* Filter Section (Single line design) */
        .filter-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box-wrapper {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-box-wrapper input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: #f8fafc;
            transition: all 0.2s;
        }
        .search-box-wrapper input:focus {
            background: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        .filter-controls-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-select-group {
            display: flex;
            gap: 12px;
        }

        .filter-select {
            padding: 9px 32px 9px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            min-width: 140px;
            color: #334155;
        }
        .filter-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-reset {
            background: transparent;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-reset:hover { background: #f1f5f9; border-color: #cbd5e1; }

        .btn-export {
            background: #10b981; /* Green to match image */
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-export:hover { background: #059669; }

        @media (max-width: 992px) {
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-controls-wrapper { flex-direction: column; align-items: stretch; }
            .filter-select-group { flex-direction: column; }
            .filter-select { width: 100%; }
            .action-buttons { flex-direction: row; }
            .btn-export { flex: 1; justify-content: center; }
        }

        /* Table Wrapper */
        .table-wrapper {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 0 0 0;
            overflow-x: auto;
        }

        /* DataTables Override */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 0 20px;
        }

        .dataTables_wrapper .dataTables_length { float: left; margin-bottom: 16px; }
        .dataTables_wrapper .dataTables_filter { float: right; margin-bottom: 16px; }
        .dataTables_wrapper .dataTables_info { float: left; padding-top: 14px; color: #64748b; font-size: 13px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }

        .dataTables_wrapper .dataTables_filter input {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            margin-left: 8px;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #2563eb; outline: none;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 5px 24px 5px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            margin: 0 4px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px !important;
            border: 1px solid transparent !important;
            border-radius: 6px !important;
            background: transparent !important;
            color: #64748b !important;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2563eb !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #1d4ed8 !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5; cursor: default;
        }

        /* Complaints Table */
        .complaints-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .complaints-table thead tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .complaints-table th {
            padding: 14px 20px;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: transparent;
        }

        .complaints-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .complaints-table tbody tr:last-child td { border-bottom: none; }
        .complaints-table tbody tr:hover { background-color: #f8fafc; }

        .complaints-table td.dtr-control { width: 30px; text-align: center; }
        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before {
            background-color: #e2e8f0;
            color: #475569;
            border: none;
            line-height: 16px;
            width: 16px; height: 16px;
            top: 50%; transform: translateY(-50%);
        }

        .complaint-id {
            color: #1e40af;
            font-weight: 700;
            font-size: 13px;
        }

        .complaint-photo {
            width: 36px; height: 36px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
        .no-file-text { color: #94a3b8; font-size: 12px; font-style: italic; }

        .complaint-subject strong {
            display: block;
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
        }

        .badge-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .badge-status.pending { background: #ffedd5; color: #c2410c; }
        .badge-status.resolved { background: #e0e7ff; color: #4338ca; }
        .badge-status.transferred { background: #e0f2fe; color: #0369a1; }
        .badge-status.rejected { background: #fee2e2; color: #b91c1c; }
        .badge-status.default { background: #f1f5f9; color: #475569; }

        .action-cell { display: flex; gap: 8px; align-items: center; }

        .btn-icon {
            background: transparent; border: 1px solid transparent;
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; cursor: pointer; border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-icon svg { width: 18px; height: 18px; }
        .btn-edit { color: #2563eb; }
        .btn-edit:hover:not(:disabled) { background: #eff6ff; border-color: #bfdbfe; }
        .btn-delete { color: #dc2626; }
        .btn-delete:hover:not(:disabled) { background: #fef2f2; border-color: #fecaca; }
        .btn-icon:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .page-header-container { flex-direction: column; align-items: stretch; }
            .btn-primary { width: 100%; justify-content: center; }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none; text-align: left; width: 100%;
            }
            .dataTables_wrapper .dataTables_filter input { width: calc(100% - 80px); }
            .dataTables_wrapper .dataTables_info { float: none; text-align: left; }
            .dataTables_wrapper .dataTables_paginate { float: none; text-align: left; }
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

        // This function is triggered by the green "निर्यात" button
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
