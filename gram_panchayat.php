<?php
session_start();
require_once __DIR__ . '/include/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'ग्रामपंचायत अधिकारी') {
    header('Location: login.php');
    exit;
}

// $active_page = 'user_dashboard';
$page_title = 'Gram Panchayat Dashboard';
$page_description = 'Gram Panchayat role dashboard with local issue tracking and field updates.';
$dashboard_title = 'Gram Panchayat Dashboard';
$dashboard_description = 'Gram Panchayat-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-landmark';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>