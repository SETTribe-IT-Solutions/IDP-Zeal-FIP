<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'अंगणवाडी सेविका') {
    header('Location: login.php');
    exit;
}

$active_page = 'user_dashboard';
$page_title = 'Anganwadi Dashboard';
$page_description = 'Anganwadi role dashboard with community health and childcare updates.';
$dashboard_title = 'Anganwadi Dashboard';
$dashboard_description = 'Anganwadi-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-baby-carriage';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>
