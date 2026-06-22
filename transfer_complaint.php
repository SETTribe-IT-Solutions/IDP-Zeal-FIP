<?php
// transfer_complaint.php - Endpoint to update issue department and department head
session_start();

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/include/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$issue_number = trim($_POST['issue_number'] ?? '');
$department = trim($_POST['department'] ?? '');
$department_head = trim($_POST['department_head'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (empty($issue_number) || empty($department) || empty($department_head)) {
    echo json_encode(['success' => false, 'message' => 'सर्व आवश्यक डेटा पाठवला नाही (Missing required data)']);
    exit;
}

try {
    $conn = db_connect();
    
    // Check if the issue exists
    $check_sql = "SELECT id, description FROM tbl_raiseissue WHERE issue_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    $check_stmt->bind_param("s", $issue_number);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'तक्रार सापडली नाही (Complaint not found)']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    $check_stmt->close();

    // Update the issue department, department head and reset status to Pending
    $update_sql = "UPDATE tbl_raiseissue SET department = ?, department_head = ?, status = 'Pending' WHERE issue_number = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        throw new Exception("Update preparation failed: " . $conn->error);
    }
    $update_stmt->bind_param("sss", $department, $department_head, $issue_number);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'तक्रार यशस्वीरित्या हस्तांतरित झाली!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'हस्तांतरण अयशस्वी: ' . $update_stmt->error]);
    }
    
    $update_stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'त्रुटी: ' . $e->getMessage()]);
}
?>
