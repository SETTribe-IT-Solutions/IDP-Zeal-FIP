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
$transfer_to_username = trim($_POST['department'] ?? ''); // This is actually the target user's username
$department_head = trim($_POST['department_head'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (empty($issue_number) || empty($transfer_to_username)) {
    echo json_encode(['success' => false, 'message' => 'सर्व आवश्यक डेटा पाठवला नाही (Missing required data)']);
    exit;
}

try {
    $conn = db_connect();

    // Check if the issue exists and get its internal ID
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
    $issue_row = $res->fetch_assoc();
    $issue_id = $issue_row['id'];
    $check_stmt->close();

    // Look up the target user's actual name, department, and designation from users table
    $department = $transfer_to_username; // fallback: use the username itself
    $transfer_to_name = $transfer_to_username; // fallback for transfer table
    $user_lookup_stmt = $conn->prepare("SELECT name, department, designation FROM users WHERE username = ? LIMIT 1");
    if ($user_lookup_stmt) {
        $user_lookup_stmt->bind_param("s", $transfer_to_username);
        $user_lookup_stmt->execute();
        $user_lookup_res = $user_lookup_stmt->get_result();
        if ($user_row = $user_lookup_res->fetch_assoc()) {
            // Use actual department from users table
            if (!empty($user_row['department'])) {
                $department = $user_row['department'];
            }
            // Use actual designation as department_head if not provided
            if (empty($department_head) && !empty($user_row['designation'])) {
                $department_head = $user_row['designation'];
            }
            // Use actual name for transfer table display
            if (!empty($user_row['name'])) {
                $transfer_to_name = $user_row['name'];
            }
        }
        $user_lookup_stmt->close();
    }

    if (empty($department_head)) {
        $department_head = 'विभाग प्रमुख'; // Fallback
    }

    // Update the issue department, department head, transfer_to (username), and reset status to Transfer
    $update_sql = "UPDATE tbl_raiseissue SET department = ?, department_head = ?, transfer_to = ?, status = 'Transfer' WHERE issue_number = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        throw new Exception("Update preparation failed: " . $conn->error);
    }
    $update_stmt->bind_param("ssss", $department, $department_head, $transfer_to_username, $issue_number);

    if ($update_stmt->execute()) {
        // Insert log in transfer table
        $insert_transfer_sql = "INSERT INTO transfer (issue_id, issue_no, transfer_to, transfer_by, reason) VALUES (?, ?, ?, ?, ?)";
        $insert_transfer_stmt = $conn->prepare($insert_transfer_sql);
        if ($insert_transfer_stmt) {
            // Look up the current user's name for transfer_by
            $transfer_by_username = $_SESSION['username'] ?? '';
            $transfer_by_name = $transfer_by_username; // fallback
            $by_lookup_stmt = $conn->prepare("SELECT name FROM users WHERE username = ? LIMIT 1");
            if ($by_lookup_stmt) {
                $by_lookup_stmt->bind_param("s", $transfer_by_username);
                $by_lookup_stmt->execute();
                $by_lookup_res = $by_lookup_stmt->get_result();
                if ($by_row = $by_lookup_res->fetch_assoc()) {
                    if (!empty($by_row['name'])) {
                        $transfer_by_name = $by_row['name'];
                    }
                }
                $by_lookup_stmt->close();
            }
            $insert_transfer_stmt->bind_param("issss", $issue_id, $issue_number, $transfer_to_name, $transfer_by_name, $notes);
            $insert_transfer_stmt->execute();
            $insert_transfer_stmt->close();
        }
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