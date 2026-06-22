<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'THO') {
    header('Location: login.php');
    exit;
}

$active_page = 'user_dashboard';
$page_title = 'THO Dashboard';
$page_description = 'THO role dashboard with issue summary and quick actions.';
$dashboard_title = 'THO Dashboard';
$dashboard_description = 'THO-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-building-columns';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>
