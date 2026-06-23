<?php 
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
$resolved_remark = trim($_POST['resolved_remark'] ?? '');

if (empty($issue_number)) {
    echo json_encode(['success' => false, 'message' => 'Issue number required']);
    exit;
}

try {
    $conn = db_connect();

    // ✅ Prevent re-resolving
    $status_check_sql = "SELECT status FROM tbl_raiseissue WHERE issue_number = ?";
    $status_stmt = $conn->prepare($status_check_sql);
    $status_stmt->bind_param("s", $issue_number);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $status_row = $status_result->fetch_assoc();

    if ($status_row && strtolower($status_row['status']) === 'resolved') {
        echo json_encode([
            'success' => false,
            'message' => 'Already resolved'
        ]);
        $status_stmt->close();
        $conn->close();
        exit;
    }
    $status_stmt->close();

    // Photo upload
    $resolved_photo = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed_ext)) {
            throw new Exception('Invalid file type');
        }

        $new_name = 'resolved_' . $issue_number . '.' . $ext;
        $target_path = $upload_dir . $new_name;

        move_uploaded_file($_FILES['photo']['tmp_name'], $target_path);

        $resolved_photo = 'uploads/' . $new_name;
    }

    // Update DB
    if ($resolved_photo) {
        $sql = "UPDATE tbl_raiseissue SET status='Resolved', resolved_remark=?, resolved_photo=? WHERE issue_number=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $resolved_remark, $resolved_photo, $issue_number);
    } else {
        $sql = "UPDATE tbl_raiseissue SET status='Resolved', resolved_remark=? WHERE issue_number=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $resolved_remark, $issue_number);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resolved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>