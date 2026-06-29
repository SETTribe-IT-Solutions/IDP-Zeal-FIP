<?php
// Include configuration
require_once __DIR__ . '/include/config.php';

function generateIssueNumber($conn) {
    $prefix = 'ISSUE-';
    $nextNumeric = 1;
    $paddingLength = 4;

    $sql = "SELECT issue_number FROM tbl_raiseissue ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $lastNumber = $row['issue_number'];
        if (preg_match('/(\d+)$/', $lastNumber, $match)) {
            $nextNumeric = intval($match[1]) + 1;
            $paddingLength = max(4, strlen($match[1]));
        }
    }

    return $prefix . str_pad((string)$nextNumeric, $paddingLength, '0', STR_PAD_LEFT);
}
// -------------------------------------------------

// Only run the API handler if this file is executed directly (not included)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Set header for JSON response
    header('Content-Type: application/json');

    try {
        // Get database connection
        $conn = getDBConnection();
        
                $edit_mode = ($_POST['edit_mode'] ?? '0') === '1';
        $issue_number = trim($_POST['issue_number'] ?? '');
        $issue_date = trim($_POST['issue_date'] ?? '');
        $taluka = trim($_POST['taluka'] ?? '');
        $village = trim($_POST['village'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $department_head = trim($_POST['department_head'] ?? '');
        $registration_type = trim($_POST['registration_type'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $existing_photo = trim($_POST['existing_photo'] ?? '');

        if (empty($issue_date) || empty($taluka) || empty($village) || empty($department) ||
            empty($department_head) || empty($registration_type) || empty($position) ||
            empty($mobile) || empty($description)) {
            echo json_encode([
                'success' => false,
                'message' => 'à¤•à¥ƒà¤ªà¤¯à¤¾ à¤¸à¤°à¥à¤µ à¤†à¤µà¤¶à¥à¤¯à¤• à¤«à¥€à¤²à¥à¤¡ à¤­à¤°à¤¾'
            ]);
            exit;
        }

        if (!preg_match('/^[6789][0-9]{9}$/', $mobile)) {
            echo json_encode([
                'success' => false,
                'message' => 'à¤•à¥ƒà¤ªà¤¯à¤¾ 6, 7, 8 à¤•à¤¿à¤‚à¤µà¤¾ 9 à¤¨à¥‡ à¤¸à¥à¤°à¥‚ à¤¹à¥‹à¤£à¤¾à¤°à¤¾ 10 à¤…à¤‚à¤•à¥€ à¤µà¥ˆà¤§ à¤®à¥‹à¤¬à¤¾à¤‡à¤² à¤•à¥à¤°à¤®à¤¾à¤‚à¤• à¤Ÿà¤¾à¤•à¤¾'
            ]);
            exit;
        }

        $photo = $existing_photo !== '' ? $existing_photo : null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $_FILES['photo']['error'];
            if ($uploadError !== UPLOAD_ERR_OK) {
                throw new Exception('Photo upload failed with error code: ' . $uploadError);
            }

            $upload_dir = __DIR__ . '/issue_photos/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('Unable to create upload directory.');
            }

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $maxFileSize = 5 * 1024 * 1024;

            if (!in_array($ext, $allowed_ext)) {
                throw new Exception('à¤•à¥‡à¤µà¤³ JPG, JPEG, PNG à¤•à¤¿à¤‚à¤µà¤¾ GIF à¤«à¤¾à¤ˆà¤²à¥à¤¸ à¤…à¤¨à¥à¤®à¤¤à¥€ à¤†à¤¹à¥‡à¤¤.');
            }

            if ($_FILES['photo']['size'] > $maxFileSize) {
                throw new Exception('à¤«à¤¾à¤‡à¤² 5MB à¤ªà¥‡à¤•à¥à¤·à¤¾ à¤œà¤¾à¤¸à¥à¤¤ à¤¨à¤¸à¤¾à¤µà¥€.');
            }

            if ($issue_number === '') {
                $issue_number = generateIssueNumber($conn);
            }

            $new_name = $issue_number . '.' . $ext;
            $target_path = $upload_dir . $new_name;
            $photo_db = 'issue_photos/' . $new_name;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                throw new Exception('à¤«à¥‹à¤Ÿà¥‹ à¤¸à¥‡à¤µà¥à¤¹ à¤•à¤°à¤¤à¤¾à¤¨à¤¾ à¤¤à¥à¤°à¥à¤Ÿà¥€ à¤†à¤²à¥€.');
            }

            $photo = $photo_db;
        }

        if ($edit_mode) {
            if ($issue_number === '') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Issue number is required for update.'
                ]);
                exit;
            }

            $sql = "UPDATE tbl_raiseissue SET issue_date = ?, taluka = ?, village = ?, department = ?, department_head = ?, registration_type = ?, position = ?, mobile = ?, description = ?, photo = ? WHERE issue_number = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $stmt->bind_param(
                "sssssssssss",
                $issue_date, $taluka, $village, $department, $department_head,
                $registration_type, $position, $mobile, $description, $photo, $issue_number
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'à¤¸à¤®à¤¸à¥à¤¯à¤¾ à¤¯à¤¶à¤¸à¥à¤µà¥€à¤°à¤¿à¤¤à¥à¤¯à¤¾ à¤…à¤¦à¥à¤¯à¤¤à¤¨à¤¿à¤¤ à¤•à¥‡à¤²à¥€!',
                    'issue_number' => $issue_number
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $stmt->error
                ]);
            }
            $stmt->close();
            $conn->close();
            exit;
        }

        if ($issue_number === '') {
            $issue_number = generateIssueNumber($conn);
        }

        $sql = "INSERT INTO tbl_raiseissue (
            issue_number, issue_date, taluka, village, department, department_head, registration_type, position, mobile, description, photo, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $status = 'Pending';
        $stmt->bind_param(
            "ssssssssssss",
            $issue_number, $issue_date, $taluka, $village, $department, $department_head,
            $registration_type, $position, $mobile, $description, $photo, $status
        );

        if ($stmt->execute()) {
            $next_issue_number = generateIssueNumber($conn);
            echo json_encode([
                'success' => true,
                'message' => 'समस्या यशस्वीरित्या नोंदवली गेली!',
                'issue_number' => $issue_number,
                'next_issue_number' => $next_issue_number
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $stmt->error
            ]);
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>