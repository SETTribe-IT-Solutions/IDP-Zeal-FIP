<?php
// complaint.php - User Complaint Records Page
require_once 'include/config.php';

$conn = db_connect();
$complaints = [];
$dbError = '';

$query = "SELECT * FROM tbl_raiseissue ORDER BY issue_date DESC";
$result = $conn->query($query);
if ($result) {
    $complaints = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    $dbError = $conn->error;
}
$conn->close();

function badgeClass($status) {
    switch (strtolower($status)) {
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

function formatDate($dateString) {
    $timestamp = strtotime($dateString);
    return $timestamp ? date('d F Y', $timestamp) : htmlspecialchars($dateString);
}
?>

<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<main class="main-content">
    <!-- Page Header -->
    <div class="header-container">
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
                    <option value="">सर्व स्थिती</option>
                    <option value="Open">🟢 उघडलेली</option>
                    <option value="In Progress">🟡 प्रक्रियेतील</option>
                    <option value="Resolved">🟣 निराकृत</option>
                    <option value="Closed">🔴 बंद</option>
                </select>

                <select id="departmentFilter" class="filter-select">
                    <option value="">सर्व विभाग</option>
                    <option value="नगर विकास">नगर विकास</option>
                    <option value="जलप्रणाली">जलप्रणाली</option>
                    <option value="स्वच्छता">स्वच्छता</option>
                    <option value="विद्युत">विद्युत</option>
                    <option value="रस्ते">रस्ते</option>
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
                    <th>कृती</th>
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
                        <tr class="complaint-row" data-status="<?= htmlspecialchars($status); ?>" data-department="<?= htmlspecialchars($complaint['department']); ?>" data-village="<?= htmlspecialchars($complaint['village']); ?>">
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
                                <button class="btn-icon btn-edit" title="संपादित करा" onclick="editComplaint('<?= htmlspecialchars($complaint['issue_number']); ?>')">✏️</button>
                                <button class="btn-icon btn-view" title="तपशील पहा" onclick="viewComplaint('<?= htmlspecialchars($complaint['issue_number']); ?>')">👁️</button>
                                <button class="btn-icon btn-delete" title="हटवा" onclick="deleteComplaint('<?= htmlspecialchars($complaint['issue_number']); ?>')">🗑️</button>
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
</main>

<!-- Styles -->
<style>
    /* Header Container */
    .header-container {
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

    /* Buttons */
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

    .badge-type.urgent {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-type.medium {
        background: #fef3c7;
        color: #854d0e;
    }

    .badge-type.low {
        background: #dcfce7;
        color: #166534;
    }

    .badge-status {
        background: #f3f4f6;
        color: #374151;
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
        font-size: 1.1rem;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 6px;
        transition: all 0.3s ease;
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
        .header-container {
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
    // Search Functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
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

    function editComplaint(id) {
        alert('तक्रार #' + id + ' संपादित करण्याचे फॉर्म उघडणे...');
        // Redirect to edit page
        // window.location.href = 'edit-complaint.php?id=' + id;
    }

    function viewComplaint(id) {
        alert('तक्रार #' + id + ' चे तपशील पहाणे...');
        // Redirect to detail page
        // window.location.href = 'complaint-detail.php?id=' + id;
    }

    function deleteComplaint(id) {
        if (confirm('क्या आप खरोखर हे तक्रार हटवू शकता?')) {
            alert('तक्रार #' + id + ' हटवल्या गेले आहे');
            // Call delete API
        }
    }

    function openNewComplaintForm() {
        alert('नवीन तक्रार फॉर्म उघडणे...');
        // window.location.href = 'new-complaint.php';
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

                csv += `"${id}","${subject}","${department}","${village}","${type}","${date}","${status}"\n`;
            }
        });

        const link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
        link.download = 'माझी_तक्रारी.csv';
        link.click();
    }

    function previousPage() {
        alert('मागील पृष्ठकडे जाणे...');
    }

    function nextPage() {
        alert('पुढील पृष्ठकडे जाणे...');
    }

    // Initialize on page load
    window.addEventListener('load', function() {
        filterComplaints();
    });
</script>

<?php include('include/footer.php'); ?>
