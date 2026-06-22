<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || ($_SESSION['user_role'] ?? '') !== 'BDO') {
    header('Location: login.php');
    exit;
}

$active_page = 'user_dashboard';
$page_title = 'BDO Dashboard';
$page_description = 'BDO role dashboard with issue summary and quick actions.';
$dashboard_title = 'BDO Dashboard';
$dashboard_description = 'BDO-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-building';

include 'include/header.php';
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>
