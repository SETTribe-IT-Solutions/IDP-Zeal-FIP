<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'Hod') {
    header('Location: login.php');
    exit;
}

$active_page = 'user_dashboard';
$page_title = 'HoD Dashboard';
$page_description = 'HoD role dashboard with department-specific issue tracking.';
$dashboard_title = 'HoD Dashboard';
$dashboard_description = 'HoD-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-chalkboard-user';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>
