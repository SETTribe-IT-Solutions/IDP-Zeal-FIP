<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || strtolower($_SESSION['user_role'] ?? '') !== 'ceo') {
    header('Location: login.php');
    exit;
}

// $active_page = 'user_dashboard';
$page_title = 'CEO Dashboard';
$page_description = 'CEO role dashboard with executive summary and performance insights.';
$dashboard_title = 'CEO Dashboard';
$dashboard_description = 'CEO-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-suitcase';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>