<?php
// Start session (optional, as config.php already handles it)
session_start();

// Include database configuration
require_once 'include/config.php';
require_once 'issue_db.php';   // <-- Now provides generateIssueNumber() and is safe to include

$conn = db_connect();
$is_edit_mode = false;
$edit_issue = [];
$edit_issue_number = trim($_GET['edit'] ?? '');

if ($edit_issue_number !== '') {
    $stmt = $conn->prepare("SELECT * FROM tbl_raiseissue WHERE issue_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $edit_issue_number);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $edit_issue = $row;
            $is_edit_mode = true;
        }
        $stmt->close();
    }
}

// Fetch logged-in user details if session is active
$user_id = '';
$user_taluka = '';
$user_village = '';
$user_designation = '';
$user_department = '';
$user_mobile = '';
$user_role_display = '';

if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT id, name, taluka, village, designation, department, mobile_no, system_role, role FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $user = $res->fetch_assoc()) {
            $user_id = $user['id'];
            $user_taluka = $user['taluka'];
            $user_village = $user['village'];
            $user_designation = $user['designation'];
            $user_department = $user['department'];
            $user_mobile = $user['mobile_no'];

            // Determine readable role to display at पद
            $user_role_display = !empty($user['role']) ? $user['role'] : (!empty($user['system_role']) ? $user['system_role'] : $user['designation']);

            // Set header session variables dynamically for real-time update
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = !empty($user['system_role']) ? $user['system_role'] : $user['designation'];
            $_SESSION['user_dept'] = $user['department'];
        }
        $stmt->close();
    }
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

// Fetch department to designation mappings from users table (taluka specific for tho/bdo, global for hod)
$dept_designations = [];

// 1. Get all HOD designations (district wide)
$hod_designations = [];
$hod_res = $conn->query("SELECT department, designation FROM users WHERE LOWER(system_role) = 'hod' AND designation IS NOT NULL AND designation != ''");
if ($hod_res) {
    while ($row = $hod_res->fetch_assoc()) {
        $dept = trim($row['department']);
        $desg = trim($row['designation']);
        $hod_designations[$dept][] = $desg;
    }
}

// 2. Get all THO designations for this user's taluka only
$tho_designations = [];
if (!empty($user_taluka)) {
    $stmt = $conn->prepare("SELECT department, designation FROM users WHERE LOWER(system_role) = 'tho' AND taluka = ? AND designation IS NOT NULL AND designation != ''");
    if ($stmt) {
        $stmt->bind_param("s", $user_taluka);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $dept = trim($row['department']);
            $desg = trim($row['designation']);
            $tho_designations[$dept][] = $desg;
        }
        $stmt->close();
    }
}

// 3. Get all BDO designations for this user's taluka only
$bdo_designations = [];
if (!empty($user_taluka)) {
    $stmt = $conn->prepare("SELECT designation FROM users WHERE LOWER(system_role) = 'bdo' AND taluka = ? AND designation IS NOT NULL AND designation != ''");
    if ($stmt) {
        $stmt->bind_param("s", $user_taluka);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $bdo_designations[] = trim($row['designation']);
        }
        $stmt->close();
    }
}

// Combine mappings per department
foreach ($distinct_departments as $dept) {
    $designations_for_dept = [];
    
    // Add THO of that department and taluka
    if (isset($tho_designations[$dept])) {
        foreach ($tho_designations[$dept] as $d) {
            if (!in_array($d, $designations_for_dept)) {
                $designations_for_dept[] = $d;
            }
        }
    }
    
    // Add BDO of that taluka
    foreach ($bdo_designations as $d) {
        if (!in_array($d, $designations_for_dept)) {
            $designations_for_dept[] = $d;
        }
    }
    
    // Add HOD of that department
    if (isset($hod_designations[$dept])) {
        foreach ($hod_designations[$dept] as $d) {
            if (!in_array($d, $designations_for_dept)) {
                $designations_for_dept[] = $d;
            }
        }
    }
    
    sort($designations_for_dept);
    $dept_designations[$dept] = $designations_for_dept;
}

$nextIssueNumber = generateIssueNumber($conn);
$conn->close();

// Include header (This handles opening HTML and BODY tags properly)
include 'include/header.php';
?>

<style>
    body {
        padding-top: 0 !important;
        background: #f3f7fb;
        display: block;
    }

    .container {
        max-width: 850px;
        width: 100%;
        margin: 0 auto;
        background: linear-gradient(180deg, #ffffff 0%, #fcfeff 100%);
        border-radius: 20px;
        padding: 36px 30px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        border: 1px solid #e6eef6;
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 18px;
        border-bottom: 2px solid #e2e8f0;
    }

    .header h1 {
        color: #0f172a;
        font-size: 32px;
        letter-spacing: 0.2px;
        font-weight: 600;
    }

    .header h1 i {
        color: #0284c7;
        margin-right: 10px;
    }

    .header p {
        color: #64748b;
        font-size: 14px;
        margin-top: 6px;
    }

    .header .subtitle {
        display: block;
        color: #6b7280;
        font-size: 13px;
        margin-top: 6px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    label {
        display: block;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 14px;
    }

    label .required {
        color: #e53e3e;
        margin-left: 3px;
    }

    label i {
        color: #0284c7;
        margin-right: 6px;
        width: 18px;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        transition: all 0.22s ease;
        background: #ffffff;
        font-family: inherit;
        color: #1e293b;
        box-shadow: none;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #0284c7;
        background: #f8fafc;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    }

    input[readonly] {
        background: #f1f5f9;
        cursor: not-allowed;
        color: #64748b;
    }

    textarea {
        resize: vertical;
        min-height: 120px;
    }

    .file-upload-wrapper {
        position: relative;
        border: 2px dashed #0284c7;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        background: #f0f9ff;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .file-upload-wrapper:hover {
        border-color: #0284c7;
        background: #e0f2fe;
    }

    .file-upload-wrapper i {
        font-size: 40px;
        color: #0284c7;
        margin-bottom: 10px;
    }

    .file-upload-wrapper p {
        color: #64748b;
        font-size: 14px;
    }

    .file-upload-wrapper input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 28px;
        padding-top: 18px;
        border-top: 1px solid #eef4fb;
        justify-content: center;
        align-items: center;
    }

    .btn {
        padding: 10px 18px;
        border: none;
        border-radius: 999px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-submit {
        background: linear-gradient(90deg, #0284c7 0%, #0ea5e9 60%);
        color: white;
        flex: 0 0 auto;
        box-shadow: 0 12px 28px rgba(2, 132, 199, 0.16);
        min-width: 150px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 34px rgba(2, 132, 199, 0.22);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-reset {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e6eef6;
        padding: 8px 16px;
        font-size: 14px;
        flex: 0 0 auto;
        min-width: 110px;
    }

    .btn-reset:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        display: none;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e6eef6;
        width: 100%;
    }

    .message-box.show {
        display: block;
    }

    .message-box.success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .message-box.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .message-box i {
        font-size: 24px;
    }

    .message-box .msg-content {
        flex: 1;
    }

    .message-box .msg-content strong {
        display: block;
        font-size: 16px;
    }

    .spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .btn-submit.loading .spinner {
        display: inline-block;
    }

    .btn-submit.loading .btn-text {
        display: none;
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    /* Drag & drop highlight */
    .file-upload-wrapper.dragover {
        border-color: #0284c7;
        background: #e0f2fe;
        transform: translateY(-4px);
        transition: all 0.18s ease;
    }

    /* Success modal */
    #successModal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.4);
        z-index: 99999;
        /* Increased z-index */
        opacity: 0;
        pointer-events: none;
        /* Blocks clicks when hidden */
        transition: opacity 220ms ease;
    }

    #successModal.show {
        display: flex;
        opacity: 1;
        pointer-events: auto;
        /* Allow clicks when shown */
    }

    .modal-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 18px;
        width: 92%;
        max-width: 900px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
        transform: translateY(10px);
        animation: popIn 240ms ease forwards;
        color: #1e293b;
    }

    @keyframes popIn {
        from {
            transform: translateY(18px) scale(0.98);
            opacity: 0
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1
        }
    }

    .crop-preview {
        width: 100%;
        height: 420px;
        background: #f8fafc;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
    }

    .crop-preview img {
        user-select: none;
        -webkit-user-drag: none;
        will-change: transform;
        transform-origin: center center;
        cursor: grab;
    }

    .controls-row {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-top: 12px
    }

    .success-card {
        text-align: center;
        padding: 28px 18px;
    }

    .success-card .big-check {
        font-size: 56px;
        color: #10b981;
        margin-bottom: 8px;
        animation: popIn 360ms ease;
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .header h1 {
            font-size: 22px;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn {
            justify-content: center;
        }
    }

    .container1 {
        width: 100%;
        margin: 0 auto;
    }
</style>

<?php include 'include/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-ticket-alt"></i>समस्या नोंदणी प्रणाली</h1>
            <p>कृपया आपली समस्या खालील फॉर्म मध्ये नोंदवा</p>
        </div>

        <form id="issueForm" enctype="multipart/form-data">
            <input type="hidden" id="editMode" name="edit_mode" value="<?php echo $is_edit_mode ? '1' : '0'; ?>">
            <input type="hidden" id="existingPhoto" name="existing_photo" value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['photo'] ?? '') : ''); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i>समस्या क्रमांक</label>
                    <input type="text" id="issueNumber" name="issue_number" value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue_number ?: $nextIssueNumber) : $nextIssueNumber); ?>"
                        readonly>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i>तारीख <span class="required">*</span></label>
                    <input type="date" id="issueDate" name="issue_date" value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['issue_date'] ?? '') : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i>तालुका <span class="required">*</span></label>
                    <input type="text" id="taluka" name="taluka" placeholder="तालुका नाव"
                        pattern="[A-Za-z\u0900-\u097F ]+" title="कृपया फक्त अक्षरे आणि स्पेस वापरा"
                        value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['taluka'] ?? $user_taluka) : $user_taluka); ?>" readonly required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-city"></i>गाव <span class="required">*</span></label>
                    <input type="text" id="village" name="village" placeholder="गावाचे नाव"
                        pattern="[A-Za-z\u0900-\u097F ]+" title="कृपया फक्त अक्षरे आणि स्पेस वापरा"
                        value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['village'] ?? $user_village) : $user_village); ?>" readonly required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-building"></i>विभाग / पंचायत समिती <span class="required">*</span></label>
                    <select id="department" name="department" required>
                        <option value="">-- निवडा विभाग --</option>
                        <?php foreach ($distinct_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (($is_edit_mode ? ($edit_issue['department'] ?? '') : $user_department) === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i>संबंधित विभाग प्रमुख <span class="required">*</span></label>
                    <select id="deptHead" name="department_head" required>
                        <option value="">-- निवडा विभाग प्रमुख --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-tag"></i>नोंदणी प्रकार <span class="required">*</span></label>
                    <select id="regType" name="registration_type" required>
                        <option value="">-- निवडा --</option>
                        <option value="कर्मचारी समस्या">कर्मचारी समस्या</option>
                        <option value="कर्मचारी मागणी">कर्मचारी मागणी</option>
                        <option value="तक्रार">तक्रार</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-briefcase"></i>पद <span class="required">*</span></label>
                    <input type="text" id="position" name="position" placeholder="पद"
                        value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['position'] ?? $user_role_display) : $user_role_display); ?>" readonly required>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-phone"></i>तक्रारकर्त्याचे मोबाईल क्र <span
                            class="required">*</span></label>
                    <input type="tel" id="mobile" name="mobile" placeholder="10 अंकी मोबाईल क्रमांक" pattern="[0-9]{10}"
                        maxlength="10" value="<?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['mobile'] ?? $user_mobile) : $user_mobile); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-pen"></i>सविस्तर समस्या <span class="required">*</span></label>
                    <textarea id="description" name="description" placeholder="समस्येचे सविस्तर वर्णन करा..."
                        required><?php echo htmlspecialchars($is_edit_mode ? ($edit_issue['description'] ?? '') : ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-image"></i>समस्या ठिकाण फोटो अपलोड करा</label>
                    <div class="file-upload-wrapper">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><?php echo $is_edit_mode ? 'नवीन फोटो निवडा, किंवा जुना फोटो ठेवण्यासाठी रिकामा सोडा' : 'फोटो क्लिक करा किंवा ड्रॅग करा'; ?></p>
                        <p style="font-size: 12px; color: #a0aec0;">JPG, PNG, GIF (Max 5MB)</p>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-submit" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> <?php echo $is_edit_mode ? 'समस्या अद्यतन करा' : 'समस्या नोंदवा'; ?></span>
                    <span class="spinner"></span>
                </button>
                <button type="reset" class="btn btn-reset">
                    <i class="fas fa-undo"></i> रीसेट करा
                </button>
            </div>
        </form>
    </div>

    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Logged in user info for form prefill and restoration on reset
        const loggedInUser = {
            id: <?php echo json_encode($user_id); ?>,
            taluka: <?php echo json_encode($user_taluka); ?>,
            village: <?php echo json_encode($user_village); ?>,
            department: <?php echo json_encode($user_department); ?>,
            position: <?php echo json_encode($user_role_display); ?>,
            mobile: <?php echo json_encode($user_mobile); ?>
        };

        const isEditMode = <?php echo json_encode($is_edit_mode); ?>;
        const editIssue = <?php echo json_encode($edit_issue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        const deptDesignations = <?php echo json_encode($dept_designations); ?>;

        const departmentSelect = document.getElementById('department');
        const positionSelect = document.getElementById('position');
        const deptHeadSelect = document.getElementById('deptHead');

        departmentSelect.addEventListener('change', function () {
            populateDeptHeads(this.value);
        });

        // populateDesignations is deprecated as position/designation field is now readonly

        function populateDeptHeads(selectedDept, selectedHead = '') {
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

        function restorePrefilledValues() {
            const source = isEditMode ? editIssue : loggedInUser;

            if (source.taluka) {
                document.getElementById('taluka').value = source.taluka;
            }
            if (source.village) {
                document.getElementById('village').value = source.village;
            }
            if (source.department) {
                document.getElementById('department').value = source.department;
            }

            if (source.position) {
                document.getElementById('position').value = source.position;
            }
            populateDeptHeads(source.department || '', source.department_head || '');

            if (source.mobile) {
                document.getElementById('mobile').value = source.mobile;
            }

            if (isEditMode && source.description) {
                document.getElementById('description').value = source.description;
            }
            if (isEditMode && source.registration_type) {
                document.getElementById('regType').value = source.registration_type;
            }
            if (isEditMode && source.issue_date) {
                document.getElementById('issueDate').value = source.issue_date;
            }
        }

        // Prefill values on initial load
        restorePrefilledValues();

        if (!isEditMode) {
            document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];
        }

        // File upload and drag/drop handlers
        const photoInput = document.getElementById('photo');
        const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
        const allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        const maxFileSize = 5 * 1024 * 1024; // 5MB

        function validatePhoto(file) {
            if (!file) return true;
            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowedExt.includes(ext)) {
                Swal.fire({
                    title: 'त्रुटी',
                    text: 'केवळ JPG, JPEG, PNG किंवा GIF फाइल्स अपलोड करा.',
                    icon: 'error',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'बंद करा'
                });
                photoInput.value = '';
                return false;
            }
            if (file.size > maxFileSize) {
                Swal.fire({
                    title: 'त्रुटी',
                    text: 'फाइल 5MB पेक्षा जास्त नसावी.',
                    icon: 'error',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'बंद करा'
                });
                photoInput.value = '';
                return false;
            }
            return true;
        }

        function updateFileUploadUI(file) {
            const p = fileUploadWrapper.querySelector('p');
            if (file) {
                p.textContent = 'निवडलेली फाइल: ' + file.name;
                fileUploadWrapper.style.borderColor = '#10b981';
                fileUploadWrapper.style.background = '#f0fdf4';
            } else {
                p.textContent = 'फोटो क्लिक करा किंवा ड्रॅग करा';
                fileUploadWrapper.style.borderColor = '#0284c7';
                fileUploadWrapper.style.background = '#f0f9ff';
            }
        }

        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (validatePhoto(file)) {
                updateFileUploadUI(file);
            } else {
                updateFileUploadUI(null);
            }
        });

        // Drag & drop handlers
        ['dragenter', 'dragover'].forEach(ev => {
            fileUploadWrapper.addEventListener(ev, function (e) {
                e.preventDefault(); e.stopPropagation();
                this.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(ev => {
            fileUploadWrapper.addEventListener(ev, function (e) {
                e.preventDefault(); e.stopPropagation();
                this.classList.remove('dragover');
            });
        });

        fileUploadWrapper.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                const f = dt.files[0];
                if (validatePhoto(f)) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(f);
                    photoInput.files = dataTransfer.files;
                    updateFileUploadUI(f);
                } else {
                    updateFileUploadUI(null);
                }
            }
        });

        // Form submission
        document.getElementById('issueForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);

                const response = await fetch('issue_db.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (isEditMode) {
                        Swal.fire({
                            title: 'यशस्वी!',
                            text: result.message,
                            icon: 'success',
                            confirmButtonColor: '#0284c7',
                            confirmButtonText: 'ठीक आहे'
                        }).then(() => {
                            window.location.href = 'complaint_report.php';
                        });
                    } else {
                        Swal.fire({
                            title: 'यशस्वी!',
                            icon: 'success',
                            confirmButtonColor: '#0284c7',
                            confirmButtonText: 'बंद करा'
                        });

                        document.getElementById('issueNumber').value = result.issue_number;
                        const currentIssueNumber = result.issue_number;
                        document.getElementById('issueForm').reset();
                        restorePrefilledValues();
                        document.getElementById('issueNumber').value = currentIssueNumber;
                        document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];
                    }
                } else {
                    Swal.fire({
                        title: 'त्रुटी',
                        text: result.message,
                        icon: 'error',
                        confirmButtonColor: '#dc2626',
                        confirmButtonText: 'बंद करा'
                    });
                }

            } catch (error) {
                Swal.fire({
                    title: 'त्रुटी',
                    text: 'सर्व्हरशी संपर्क साधताना त्रुटी आली. कृपया पुन्हा प्रयत्न करा.',
                    icon: 'error',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'बंद करा'
                });
                console.error('Error:', error);
            }

            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        });

        // Mobile number validation (only numbers)
        document.getElementById('mobile').addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Text-only validation for Marathi/letter fields
        ['taluka', 'village'].forEach(function (fieldId) {
            const field = document.getElementById(fieldId);
            if (!field) return;

            field.addEventListener('input', function () {
                const sanitized = this.value.replace(/[^A-Za-z\u0900-\u097F ]/g, '');
                if (this.value !== sanitized) {
                    this.value = sanitized;
                }
                this.setCustomValidity('');
            });

            field.addEventListener('invalid', function () {
                if (this.validity.valueMissing) {
                    this.setCustomValidity('हा फील्ड आवश्यक आहे.');
                } else if (this.validity.patternMismatch) {
                    this.setCustomValidity('कृपया फक्त अक्षरे आणि स्पेस वापरा.');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        // Reset handler
        document.querySelector('.btn-reset').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('issueForm').reset();
            restorePrefilledValues();
            document.getElementById('issueDate').value = isEditMode ? (editIssue.issue_date || '') : new Date().toISOString().split('T')[0];
            photoInput.value = '';
            updateFileUploadUI(null);
        });
    </script>

    <?php include 'include/footer.php'; ?>
</main>
