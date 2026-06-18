<?php
// Database configuration
define('DB_HOST', '82.25.121.144');
define('DB_USER', 'u196817721_IDP_FIP_Zeal_U');
define('DB_PASS', 'IDP_FIP_Zeal_User@2026');
define('DB_NAME', 'u196817721_IDP_FIP_Zeal');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

// Function to generate issue number
function generateIssueNumber($conn) {
    $query = "SELECT issue_number FROM tbl_raiseissue WHERE issue_number LIKE 'ISSUE-%' ORDER BY id DESC LIMIT 1";
    $next = 1;

    try {
        $result = $conn->query($query);
    } catch (mysqli_sql_exception $e) {
        // Table may not exist yet; start numbering from ISSUE-01.
        return 'ISSUE-' . str_pad($next, 2, '0', STR_PAD_LEFT);
    }

    if (!$result) {
        return 'ISSUE-' . str_pad($next, 2, '0', STR_PAD_LEFT);
    }

    if ($row = $result->fetch_assoc()) {
        if (preg_match('/ISSUE-(\d+)$/', $row['issue_number'], $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            try {
                $countResult = $conn->query("SELECT COUNT(*) as count FROM tbl_raiseissue");
            } catch (mysqli_sql_exception $e) {
                return 'ISSUE-' . str_pad($next, 2, '0', STR_PAD_LEFT);
            }
            if ($countResult && $countRow = $countResult->fetch_assoc()) {
                $next = $countRow['count'] + 1;
            }
        }
    }

    return 'ISSUE-' . str_pad($next, 2, '0', STR_PAD_LEFT);
}
?>