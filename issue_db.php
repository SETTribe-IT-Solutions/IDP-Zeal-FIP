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
        
        // Get form data
        $issue_date = $_POST['issue_date'] ?? '';
        $taluka = $_POST['taluka'] ?? '';
        $village = $_POST['village'] ?? '';
        $department = $_POST['department'] ?? '';
        $department_head = $_POST['department_head'] ?? '';
        $registration_type = $_POST['registration_type'] ?? '';
        $position = $_POST['position'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Validate required fields
        if (empty($issue_date) || empty($taluka) || empty($village) || empty($department) || 
            empty($department_head) || empty($registration_type) || empty($position) || 
            empty($mobile) || empty($description)) {
            echo json_encode([
                'success' => false,
                'message' => 'कृपया सर्व आवश्यक फील्ड भरा'
            ]);
            exit;
        }
        
        // Validate mobile
        if (!preg_match('/^[6789][0-9]{9}$/', $mobile)) {
            echo json_encode([
                'success' => false,
                'message' => 'कृपया 6, 7, 8 किंवा 9 ने सुरू होणारा 10 अंकी वैध मोबाईल क्रमांक टाका'
            ]);
            exit;
        }
        
        // Generate issue number
        $issue_number = generateIssueNumber($conn);
        
        // Handle photo upload
        $photo = null;
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
            $maxFileSize = 5 * 1024 * 1024; // 5 MB

            if (!in_array($ext, $allowed_ext)) {
                throw new Exception('केवळ JPG, JPEG, PNG किंवा GIF फाइल्स अनुमती आहेत.');
            }

            if ($_FILES['photo']['size'] > $maxFileSize) {
                throw new Exception('फाइल 5MB पेक्षा जास्त नसावी.');
            }

            $new_name = $issue_number . '.' . $ext;
            $target_path = $upload_dir . $new_name;
            $photo_db = 'issue_photos/' . $new_name; // path to store in DB (web relative)

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                throw new Exception('फोटो सेव्ह करताना त्रुटी आली.');
            }

            $photo = $photo_db;
        }
        
        // Prepare SQL statement
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
        
        // Execute and check
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'समस्या यशस्वीरित्या नोंदवली गेली!',
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
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>