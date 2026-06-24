<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['username']) || strtolower($_SESSION['user_role'] ?? '') !== 'bdo') {
    header('Location: login.php');
    exit;
}

// $active_page = 'user_dashboard';
$page_title = 'BDO Dashboard';
$page_description = 'BDO role dashboard with issue summary and quick actions.';
$dashboard_title = 'BDO Dashboard';
$dashboard_description = 'BDO-specific portal summary and live issue insights.';
$dashboard_icon = 'fa-solid fa-building';

include 'include/header.php';
?>

<style>
    /* ===== MOBILE RESPONSIVE OVERRIDES ===== */
    /* These styles only apply on smaller screens and do not affect desktop layout */

    /* --- Small phones (portrait) --- */
    @media (max-width: 575.98px) {
        body {
            font-size: 14px;
        }

        .container,
        .container-fluid {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        /* Sidebar - assume common classes */
        .sidebar,
        .side-nav,
        .navbar-side,
        .main-sidebar,
        .left-sidebar {
            width: 100% !important;
            position: relative !important;
            height: auto !important;
            padding: 0 !important;
            margin-bottom: 10px !important;
            float: none !important;
        }

        /* Main content area */
        .main-content,
        .content-wrapper,
        .page-content,
        .right-side,
        .content-area {
            margin-left: 0 !important;
            padding: 10px !important;
            width: 100% !important;
            float: none !important;
        }

        /* Navigation */
        .navbar,
        .navbar-header,
        .navbar-nav {
            flex-wrap: wrap;
        }

        .navbar-brand {
            font-size: 1.2rem;
        }

        .navbar-toggler {
            display: inline-block !important;
        }

        .navbar-collapse {
            flex-basis: 100%;
        }

        /* Forms */
        .form-control,
        .form-select,
        select,
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="datetime-local"],
        textarea,
        .input-group {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }

        .form-group,
        .form-row,
        .row>.col,
        .row>[class*="col-"],
        .row>.col-* {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            margin-bottom: 10px;
        }

        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        /* Buttons */
        .btn,
        button,
        input[type="submit"],
        input[type="button"],
        .btn-group .btn {
            width: 100% !important;
            display: block !important;
            margin-bottom: 5px;
            white-space: normal !important;
            box-sizing: border-box;
        }

        .btn-group {
            display: block !important;
            width: 100% !important;
        }

        .btn-group .btn:first-child {
            border-top-left-radius: 0.25rem !important;
            border-top-right-radius: 0.25rem !important;
            border-bottom-left-radius: 0 !important;
        }

        .btn-group .btn:last-child {
            border-bottom-left-radius: 0.25rem !important;
            border-bottom-right-radius: 0.25rem !important;
            border-top-right-radius: 0 !important;
        }

        /* Tables */
        .table-responsive,
        .table-wrapper {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            display: block !important;
            width: 100% !important;
        }

        .table {
            width: 100% !important;
            min-width: 600px;
            /* force horizontal scroll if table has many columns */
        }

        .table th,
        .table td {
            white-space: nowrap;
        }

        /* Cards / Panels */
        .card,
        .panel,
        .box,
        .widget {
            margin-bottom: 15px;
        }

        .card-body,
        .panel-body {
            padding: 10px !important;
        }

        /* Images */
        img,
        .img-fluid {
            max-width: 100% !important;
            height: auto !important;
        }

        /* Dashboard specific */
        .dashboard-stats .col,
        .stats-row .col,
        .stat-item,
        .metric-card {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        .quick-actions .btn {
            margin-bottom: 8px;
        }

        /* Spacing helpers */
        .mb-3,
        .mb-4,
        .mb-5 {
            margin-bottom: 0.75rem !important;
        }

        .mt-3,
        .mt-4,
        .mt-5 {
            margin-top: 0.75rem !important;
        }

        /* Floats */
        .float-left,
        .float-right,
        .float-start,
        .float-end {
            float: none !important;
        }

        .text-right,
        .text-end {
            text-align: left !important;
        }

        /* Dropdown menus inside navbar */
        .dropdown-menu {
            position: static !important;
            float: none !important;
            width: 100% !important;
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }

    /* --- Tablets (portrait and small landscape) --- */
    @media (min-width: 576px) and (max-width: 991.98px) {
        .container {
            max-width: 100%;
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Adjust column layout to 2 per row on tablets */
        .row>.col-md-6,
        .row>.col-sm-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        .row>.col-md-4,
        .row>.col-sm-4 {
            flex: 0 0 33.3333% !important;
            max-width: 33.3333% !important;
        }

        /* Keep buttons inline on tablet */
        .btn {
            width: auto !important;
            display: inline-block !important;
        }

        .btn-group .btn {
            width: auto !important;
            display: inline-block !important;
            border-radius: 0.25rem !important;
        }

        .table-responsive {
            overflow-x: auto;
        }

        /* Sidebar can be collapsible or we keep it as is */
    }

    /* --- Large screens (desktop) --- */
    @media (min-width: 992px) {
        /* Ensure desktop styles remain untouched */
        /* No overrides needed - original CSS takes precedence */
    }
</style>

<?php
include 'include/sidebar.php';
include 'include/dashboard_common.php';
include 'include/dashboard_layout.php';
?>