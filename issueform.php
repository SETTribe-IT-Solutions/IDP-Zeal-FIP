<?php
// Start session
session_start();

// Include database configuration
require_once 'include/config.php';

$conn = db_connect();

// Fetch logged-in user details if session is active
$user_id = '';
$user_taluka = '';
$user_village = '';
$user_designation = '';
$user_department = '';
$user_mobile = '';
$user_system_role = ''; // Add this for system role

if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT Id, Name, Talika, Village, Designation, Department, `Mobile No`, Role, `System Role` FROM users WHERE Username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $user = $res->fetch_assoc()) {
            $user_id = $user['Id'];
            $user_taluka = $user['Talika'];
            $user_village = $user['Village'];
            $user_designation = $user['Designation'];
            $user_department = $user['Department'];
            $user_mobile = $user['Mobile No'];
            $user_system_role = $user['System Role'] ?? ''; // Fetch system role

            // Set header session variables dynamically for real-time update
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_role'] = !empty($user['Role']) ? $user['Role'] : $user['Designation'];
            $_SESSION['user_dept'] = $user['Department'];
            $_SESSION['user_system_role'] = $user['System Role'] ?? ''; // Store system role in session
        }
        $stmt->close();
    }
}

// Fetch distinct departments from database
$distinct_departments = [];
$dept_res = $conn->query("SELECT DISTINCT Department AS dept FROM users WHERE Department IS NOT NULL AND Department != ''");
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
$map_res = $conn->query("SELECT DISTINCT Department, Designation FROM users WHERE Department IS NOT NULL AND Department != '' AND Designation IS NOT NULL AND Designation != '' ORDER BY Designation ASC");
if ($map_res) {
    while ($row = $map_res->fetch_assoc()) {
        $dept = trim($row['Department']);
        $desg = trim($row['Designation']);
        if (!isset($dept_designations[$dept])) {
            $dept_designations[$dept] = [];
        }
        $dept_designations[$dept][] = $desg;
    }
}

$nextIssueNumber = generateIssueNumber($conn);
$conn->close();

// Include header (This handles opening HTML and BODY tags properly)
include 'include/header.php';
include 'include/sidebar.php';
?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <h1><i class="fas fa-ticket-alt"></i> समस्या नोंदणी प्रणाली</h1>
            <p>कृपया आपली समस्या खालील फॉर्म मध्ये नोंदवा</p>
        </div>
        <div class="page-header-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> डॅशबोर्डवर जा
            </a>
        </div>
    </div>

    <!-- Message Box -->
    <div id="messageBox" class="message-box">
        <i id="msgIcon" class="fas fa-check-circle"></i>
        <div class="msg-content">
            <strong id="msgTitle">यशस्वी!</strong>
            <span id="msgText">आपली समस्या नोंदवली गेली.</span>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="form-card">
        <form id="issueForm" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i>समस्या क्रमांक</label>
                    <input type="text" id="issueNumber" value="<?php echo htmlspecialchars($nextIssueNumber); ?>" readonly>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i>तारीख <span class="required">*</span></label>
                    <input type="date" id="issueDate" name="issue_date" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i>तालुका <span class="required">*</span></label>
                    <input type="text" id="taluka" name="taluka" placeholder="तालुका नाव" value="<?php echo htmlspecialchars($user_taluka); ?>" readonly>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-city"></i>गाव <span class="required">*</span></label>
                    <input type="text" id="village" name="village" placeholder="गावाचे नाव" value="<?php echo htmlspecialchars($user_village); ?>" readonly>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-building"></i>विभाग / पंचायत समिती <span class="required">*</span></label>
                    <select id="department" name="department" required>
                        <option value="">-- निवडा विभाग --</option>
                        <?php foreach ($distinct_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($user_department === $dept) ? 'selected' : ''; ?>>
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
                    <!-- Position is now READONLY and displays System Role from user session -->
                    <input type="text" id="position" name="position" placeholder="पद" value="<?php echo htmlspecialchars($user_system_role ?: $user_designation); ?>" readonly>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-phone"></i>तक्रारकर्त्याचे मोबाईल क्र <span class="required">*</span></label>
                    <input type="tel" id="mobile" name="mobile" placeholder="10 अंकी मोबाईल क्रमांक" pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($user_mobile); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-pen"></i>सविस्तर समस्या <span class="required">*</span></label>
                    <textarea id="description" name="description" placeholder="समस्येचे सविस्तर वर्णन करा..." required></textarea>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-image"></i>समस्या ठिकाण फोटो अपलोड करा</label>
                    <div class="file-upload-wrapper">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>फोटो क्लिक करा किंवा ड्रॅग करा</p>
                        <p style="font-size: 12px; color: #a0aec0; margin-top: 4px;">JPG, PNG, GIF (Max 5MB)</p>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-submit" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> समस्या नोंदवा</span>
                    <span class="spinner"></span>
                </button>
                <button type="reset" class="btn btn-reset">
                    <i class="fas fa-undo"></i> रीसेट करा
                </button>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <div id="successModal" aria-hidden="true">
        <div class="modal-overlay">
            <div class="modal-card success-card">
                <div class="big-check"><i class="fas fa-check-circle"></i></div>
                <h2 id="successTitle">यश!</h2>
                <p id="successMessage"></p>
                <div style="margin-top:16px;display:flex;gap:10px;justify-content:center">
                    <button id="closeSuccess" class="btn btn-reset">बंद करा</button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    /* Main Content - Same as Dashboard */
    .main-content {
        margin-left: 260px;
        padding: 30px 24px;
        min-height: calc(100vh - var(--header-height));
        background-color: var(--bg-body);
        transition: margin-left var(--transition-normal), background-color var(--transition-normal);
    }
    
    .main-content.collapsed {
        margin-left: 72px;
    }
    
    @media screen and (max-width: 1024px) {
        .main-content {
            margin-left: 240px;
        }
        .main-content.collapsed {
            margin-left: 72px;
        }
    }

    @media screen and (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding: 20px 16px;
        }
        .main-content.collapsed {
            margin-left: 0;
        }
    }

    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-sm);
        flex-wrap: wrap;
        gap: 15px;
    }

    .page-header-content h1 {
        font-family: var(--font-heading);
        font-size: 24px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 4px 0;
    }

    .page-header-content h1 i {
        color: var(--primary-light);
        margin-right: 10px;
    }

    .page-header-content p {
        font-size: 14px;
        color: var(--text-secondary);
        margin: 0;
    }

    .page-header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-secondary {
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all var(--transition-fast);
        text-decoration: none;
    }

    .btn-secondary:hover {
        background-color: var(--border-color);
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    /* Form Card */
    .form-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 30px;
        box-shadow: var(--shadow-sm);
    }

    /* Message Box */
    .message-box {
        padding: 12px 16px;
        border-radius: var(--radius-md);
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 12px;
        background: var(--bg-hover);
        border: 1px solid var(--border-color);
        width: 100%;
        box-sizing: border-box;
    }

    .message-box.show {
        display: flex;
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
        font-size: 20px;
    }
    
    .message-box .msg-content {
        flex: 1;
    }
    
    .message-box .msg-content strong {
        display: block;
        font-size: 14px;
    }

    /* Form Layout */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 18px;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    label {
        display: block;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
        font-size: 13px;
    }

    label .required {
        color: #e53e3e;
        margin-left: 3px;
    }
    
    label i {
        color: var(--primary-light);
        margin-right: 6px;
        width: 18px;
    }
    
    input, select, textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 14px;
        transition: all 0.22s ease;
        background: var(--bg-body);
        font-family: inherit;
        color: var(--text-primary);
        box-shadow: none;
        box-sizing: border-box;
    }
    
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--primary-light);
        background: var(--bg-card);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    input[readonly] {
        background: var(--bg-hover);
        cursor: not-allowed;
        color: var(--text-muted);
        border-color: var(--border-color);
    }
    
    input[readonly]:focus {
        box-shadow: none;
        border-color: var(--border-color);
        background: var(--bg-hover);
    }
    
    textarea {
        resize: vertical;
        min-height: 100px;
    }

    /* File Upload */
    .file-upload-wrapper {
        position: relative;
        border: 2px dashed var(--primary-light);
        border-radius: var(--radius-md);
        padding: 25px;
        text-align: center;
        background: rgba(37, 99, 235, 0.04);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .file-upload-wrapper:hover {
        border-color: var(--primary-color);
        background: rgba(37, 99, 235, 0.08);
    }
    
    .file-upload-wrapper i {
        font-size: 32px;
        color: var(--primary-light);
        margin-bottom: 6px;
        display: block;
    }
    
    .file-upload-wrapper p {
        color: var(--text-secondary);
        font-size: 13px;
        margin: 0;
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

    .file-upload-wrapper.dragover {
        border-color: var(--primary-color);
        background: rgba(37, 99, 235, 0.12);
        transform: translateY(-3px);
    }

    /* Buttons */
    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        padding-top: 18px;
        border-top: 1px solid var(--border-color);
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 10px 22px;
        border: none;
        border-radius: var(--radius-full);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all var(--transition-fast);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: #ffffff;
        box-shadow: var(--shadow-sm);
        min-width: 160px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    .btn-reset {
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        min-width: 120px;
    }
    
    .btn-reset:hover {
        background-color: var(--border-color);
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    /* Spinner */
    .spinner {
        display: none;
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: #ffffff;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .btn-submit.loading .spinner {
        display: inline-block;
    }
    
    .btn-submit.loading .btn-text {
        display: none;
    }

    /* Success Modal */
    #successModal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.5);
        z-index: 99999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 220ms ease;
    }

    #successModal.show {
        display: flex;
        opacity: 1;
        pointer-events: auto;
    }

    .modal-overlay {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        padding: 20px;
    }

    .modal-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 30px;
        width: 100%;
        max-width: 500px;
        box-shadow: var(--shadow-lg);
        transform: translateY(10px);
        animation: popIn 240ms ease forwards;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    @keyframes popIn {
        from { transform: translateY(18px) scale(0.98); opacity: 0 }
        to { transform: translateY(0) scale(1); opacity: 1 }
    }

    .success-card {
        text-align: center;
    }

    .success-card .big-check {
        font-size: 56px;
        color: #10b981;
        margin-bottom: 8px;
        animation: popIn 360ms ease;
    }

    .success-card h2 {
        font-family: var(--font-heading);
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .success-card p {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .form-row {
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 18px 20px;
        }

        .page-header-actions {
            width: 100%;
        }

        .page-header-actions .btn-secondary {
            width: 100%;
            justify-content: center;
        }

        .form-card {
            padding: 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 12px;
        }

        .form-card {
            padding: 15px;
        }

        .page-header-content h1 {
            font-size: 20px;
        }
    }
</style>

<script>
// Logged in user info for form prefill and restoration on reset
const loggedInUser = {
    id: <?php echo json_encode($user_id); ?>,
    taluka: <?php echo json_encode($user_taluka); ?>,
    village: <?php echo json_encode($user_village); ?>,
    department: <?php echo json_encode($user_department); ?>,
    position: <?php echo json_encode($user_system_role ?: $user_designation); ?>,
    mobile: <?php echo json_encode($user_mobile); ?>,
    system_role: <?php echo json_encode($user_system_role); ?>
};

const deptDesignations = <?php echo json_encode($dept_designations); ?>;

const departmentSelect = document.getElementById('department');
const positionSelect = document.getElementById('position');
const deptHeadSelect = document.getElementById('deptHead');

// Since position is now readonly, we just need to populate dept heads
departmentSelect.addEventListener('change', function() {
    populateDeptHeads(this.value);
});

function populateDeptHeads(selectedDept, selectedHead = '') {
    deptHeadSelect.innerHTML = '<option value="">-- निवडा विभाग प्रमुख --</option>';
    if (selectedDept && deptDesignations[selectedDept]) {
        deptDesignations[selectedDept].forEach(function(desg) {
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
    // Taluka and Village are now readonly inputs - just set their values
    if (loggedInUser.taluka) {
        document.getElementById('taluka').value = loggedInUser.taluka;
    }
    if (loggedInUser.village) {
        document.getElementById('village').value = loggedInUser.village;
    }
    if (loggedInUser.department) {
        document.getElementById('department').value = loggedInUser.department;
    }
    
    // Position is now readonly input - set from system_role or designation
    if (loggedInUser.system_role) {
        document.getElementById('position').value = loggedInUser.system_role;
    } else if (loggedInUser.position) {
        document.getElementById('position').value = loggedInUser.position;
    }
    
    // Populate dept heads based on department
    populateDeptHeads(loggedInUser.department);
    
    if (loggedInUser.mobile) {
        document.getElementById('mobile').value = loggedInUser.mobile;
    }
}

// Prefill values on initial load
restorePrefilledValues();

// Set today's date
document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];

// File upload handlers
const photoInput = document.getElementById('photo');
const fileUploadWrapper = document.querySelector('.file-upload-wrapper');

photoInput.addEventListener('change', function() {
    const file = this.files[0];
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
});

// Drag & drop
['dragenter','dragover'].forEach(ev => {
    fileUploadWrapper.addEventListener(ev, function(e){
        e.preventDefault(); e.stopPropagation();
        this.classList.add('dragover');
    });
});

['dragleave','drop'].forEach(ev => {
    fileUploadWrapper.addEventListener(ev, function(e){
        e.preventDefault(); e.stopPropagation();
        this.classList.remove('dragover');
    });
});

fileUploadWrapper.addEventListener('drop', function(e){
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) {
        const f = dt.files[0];
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(f);
        photoInput.files = dataTransfer.files;
        const p = this.querySelector('p');
        p.textContent = 'निवडलेली फाइल: ' + f.name;
        this.style.borderColor = '#10b981';
        this.style.background = '#f0fdf4';
        this.classList.remove('dragover');
    }
});

// Form submission
document.getElementById('issueForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const messageBox = document.getElementById('messageBox');
    const msgTitle = document.getElementById('msgTitle');
    const msgText = document.getElementById('msgText');
    const msgIcon = document.getElementById('msgIcon');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    messageBox.classList.remove('show', 'success', 'error');
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('issue_db.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const successModal = document.getElementById('successModal');
            document.getElementById('successTitle').textContent = '✅ यशस्वी!';
            document.getElementById('successMessage').innerHTML = result.message + '<br><strong>समस्या क्रमांक:</strong> ' + result.issue_number;
            successModal.classList.add('show');

            document.getElementById('issueNumber').value = result.issue_number;
            const currentIssueNumber = result.issue_number;
            document.getElementById('issueForm').reset();
            restorePrefilledValues();
            document.getElementById('issueNumber').value = currentIssueNumber;
            document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];
        } else {
            messageBox.className = 'message-box show error';
            msgIcon.className = 'fas fa-exclamation-circle';
            msgTitle.textContent = '❌ त्रुटी';
            msgText.textContent = result.message;
        }
        
    } catch (error) {
        messageBox.className = 'message-box show error';
        msgIcon.className = 'fas fa-exclamation-circle';
        msgTitle.textContent = '❌ त्रुटी';
        msgText.textContent = 'सर्व्हरशी संपर्क साधताना त्रुटी आली. कृपया पुन्हा प्रयत्न करा.';
        console.error('Error:', error);
    }
    
    submitBtn.classList.remove('loading');
    submitBtn.disabled = false;
});

// Mobile number validation
document.getElementById('mobile').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Reset handler
document.querySelector('.btn-reset').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('issueForm').reset();
    restorePrefilledValues();
    document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];
    photoInput.value = '';
    const p = fileUploadWrapper.querySelector('p');
    p.textContent = 'फोटो क्लिक करा किंवा ड्रॅग करा';
    fileUploadWrapper.style.borderColor = '#0284c7';
    fileUploadWrapper.style.background = '#f0f9ff';
    document.getElementById('successModal').classList.remove('show');
});

// Close modal and refresh
document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('closeSuccess');
    if(closeBtn) {
        closeBtn.addEventListener('click', function() {
            window.location.reload(); 
        });
    }
});
</script>

<?php include 'include/footer.php'; ?>