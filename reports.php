<?php
// reports.php - Reports Page
?>

<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<main class="main-content">
    <div class="header-container">
        <div class="page-title">
            <h1>Reports</h1>
            <p>View and manage all complaints and reports</p>
        </div>
    </div>

    <!-- Search & Export Toolbar -->
    <div class="table-toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by complaint ID, category, or status...">
        </div>
        <button class="export-btn" onclick="exportTable()">
            📥 Export
        </button>
    </div>

    <!-- Reports Table -->
    <div class="table-container">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>Category</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <!-- Sample Data -->
                <tr>
                    <td class="complaint-id">#CMP-2024-0001</td>
                    <td>System Bug</td>
                    <td>2024-01-15</td>
                    <td><span class="badge resolved">Resolved</span></td>
                    <td>High</td>
                    <td><a href="#" class="action-link">View Details</a></td>
                </tr>
                <tr>
                    <td class="complaint-id">#CMP-2024-0002</td>
                    <td>Feature Request</td>
                    <td>2024-01-16</td>
                    <td><span class="badge pending">Pending</span></td>
                    <td>Medium</td>
                    <td><a href="#" class="action-link">View Details</a></td>
                </tr>
                <tr>
                    <td class="complaint-id">#CMP-2024-0003</td>
                    <td>Login Issue</td>
                    <td>2024-01-17</td>
                    <td><span class="badge open">Open</span></td>
                    <td>Critical</td>
                    <td><a href="#" class="action-link">View Details</a></td>
                </tr>
                <tr>
                    <td class="complaint-id">#CMP-2024-0004</td>
                    <td>UI Enhancement</td>
                    <td>2024-01-18</td>
                    <td><span class="badge resolved">Resolved</span></td>
                    <td>Low</td>
                    <td><a href="#" class="action-link">View Details</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Search Functionality
    document.getElementById('searchInput').addEventListener('keyup', function(event) {
        const searchTerm = event.target.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Export Functionality
    function exportTable() {
        let csv = 'Complaint ID,Category,Date Submitted,Status,Priority\n';
        const rows = document.querySelectorAll('#tableBody tr');
        
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cols = row.querySelectorAll('td');
                const row_data = [
                    cols[0].innerText,
                    cols[1].innerText,
                    cols[2].innerText,
                    cols[3].innerText,
                    cols[4].innerText
                ];
                csv += row_data.join(',') + '\n';
            }
        });

        const link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
        link.download = 'reports.csv';
        link.click();
    }
</script>

</main>

<?php include('include/footer.php'); ?>
