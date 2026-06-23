<?php
// Database connection configuration for the userdata database.
// Update these values if your local MySQL credentials differ.

define('DB_HOST', '82.25.121.144');
define('DB_USER', 'u196817721_IDP_FIP_Zeal_U');
define('DB_PASSWORD', 'IDP_FIP_Zeal_User@2026');
define('DB_NAME', 'u196817721_IDP_FIP_Zeal');

define('DB_CHARSET', 'utf8mb4');



function db_connect() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_errno) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}

function getDBConnection() {
    return db_connect();
}

function generateIssueNumber($conn) {
    $issueNumber = '0001';

    $sql = "SELECT issue_number FROM tbl_raiseissue ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $lastNumber = $row['issue_number'];
        if (preg_match('/(\d+)$/', $lastNumber, $match)) {
            $nextNumeric = intval($match[1]) + 1;
            $issueNumber = str_pad((string)$nextNumeric, strlen($match[1]), '0', STR_PAD_LEFT);
        }
    }

    return $issueNumber;
}