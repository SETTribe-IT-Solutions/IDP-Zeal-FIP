<?php
/**
 * Zilla Parishad Hingoli - Inter-Department Portal (IDP)
 * Common Header Template (Inline CSS Banner Style)
 */

// Establish current active page default
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF'], '.php');
}

// User details mockup
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Shri. Rajesh Patil";
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : "Admin Officer";
$user_dept = isset($_SESSION['user_dept']) ? $_SESSION['user_dept'] : "Finance Dept";
$user_initials = "RP";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZP Hingoli - Inter Department Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Embedded/Inline CSS Stylesheet -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap');

        /* --- Design System & CSS Variables --- */
        :root {
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;

            /* Colors */
            --primary-color: #1e3a8a;
            --primary-light: #2563eb;
            --primary-rgb: 30, 58, 138;
            --accent-color: #d97706;
            --accent-light: #f59e0b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --info-color: #0284c7;

            /* Light Mode */
            --bg-body: #f8fafc;
            --bg-header: rgba(255, 255, 255, 0.85);
            --bg-card: #ffffff;
            --bg-dropdown: #ffffff;
            --bg-hover: #f1f5f9;
            --bg-input: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --glass-blur: 16px;

            /* Layout */
            --header-height: 76px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-full: 9999px;

            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -2px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.08);
            --shadow-dropdown: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);

            /* Transitions */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* System Dark Mode Auto-Toggle */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #0b0f19;
                --bg-header: rgba(15, 23, 42, 0.8);
                --bg-card: #1e293b;
                --bg-dropdown: #1e293b;
                --bg-hover: #334155;
                --bg-input: #1e293b;
                --text-primary: #f8fafc;
                --text-secondary: #cbd5e1;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
            }
        }

        /* Override helper classes for explicit dark-mode class */
        body.dark-theme {
            --bg-body: #0b0f19;
            --bg-header: rgba(15, 23, 42, 0.8);
            --bg-card: #1e293b;
            --bg-dropdown: #1e293b;
            --bg-hover: #334155;
            --bg-input: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
        }

        body.light-theme {
            --bg-body: #f8fafc;
            --bg-header: rgba(255, 255, 255, 0.85);
            --bg-card: #ffffff;
            --bg-dropdown: #ffffff;
            --bg-hover: #f1f5f9;
            --bg-input: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        /* --- Global Resets & Typography --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 15px;
            line-height: 1.5;
            transition: background-color var(--transition-normal), color var(--transition-normal);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* --- Main Header Container --- */
        .idp-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--header-height);
            background-color: var(--bg-header);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: background-color var(--transition-normal), border-color var(--transition-normal);
        }

        .header-container {
            max-width: 1440px;
            height: 100%;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        /* --- Logo & Identity Section --- */
        .header-left {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .brand-emblem-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            transition: transform var(--transition-fast);
        }

        .brand-emblem-img:hover {
            transform: scale(1.05);
        }

        .header-middle {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            text-align: center;
        }

        .brand-text-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .brand-title {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 20px;
            color: var(--text-primary);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .brand-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: var(--accent-color);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 2px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .brand-logo-img {
            height: 44px;
            width: auto;
            object-fit: contain;
            transition: transform var(--transition-fast);
        }

        .brand-logo-img:hover {
            transform: scale(1.05);
        }

        .header-divider {
            width: 1px;
            height: 28px;
            background-color: var(--border-color);
        }

        /* --- Search Bar Container --- */
        .header-search {
            position: relative;
            width: 100%;
            max-width: 280px;
            transition: max-width var(--transition-normal);
        }

        .header-search:focus-within {
            max-width: 360px;
        }

        .search-input-wrapper {
            position: relative;
            width: 100%;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            height: 40px;
            padding: 0 40px 0 38px;
            background-color: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 14px;
            outline: none;
            transition: all var(--transition-fast);
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            background-color: var(--bg-card);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
        }

        .search-shortcut {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--border-color);
            color: var(--text-muted);
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 4px;
            font-weight: 600;
            pointer-events: none;
        }

        /* --- Navigation Links --- */
        .header-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 100%;
        }

        .nav-item {
            position: relative;
            padding: 8px 14px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .nav-item:hover,
        .nav-item.active {
            color: var(--primary-light);
            background-color: var(--bg-hover);
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -16px;
            left: 14px;
            right: 14px;
            height: 3px;
            border-radius: var(--radius-full) var(--radius-full) 0 0;
            background-color: var(--primary-light);
        }

        .nav-dropdown-indicator {
            font-size: 10px;
            transition: transform var(--transition-fast);
        }

        .nav-item:hover .nav-dropdown-indicator {
            transform: translateY(1px);
        }

        /* --- Dropdown Container System --- */
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background-color: var(--bg-dropdown);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-dropdown);
            width: 250px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: transform var(--transition-normal), opacity var(--transition-normal), visibility var(--transition-normal);
            overflow: hidden;
            z-index: 1010;
            padding: 8px;
        }

        .nav-item:hover .dropdown-menu,
        .dropdown-trigger:focus-within .dropdown-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        /* Departments Grid Menu (Double Width) */
        .nav-item.mega-menu {
            position: static;
        }

        .mega-dropdown {
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            width: 600px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .nav-item.mega-menu:hover .mega-dropdown {
            transform: translateX(-50%) translateY(0);
        }

        .dropdown-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .dropdown-link:hover {
            background-color: var(--bg-hover);
            color: var(--primary-light);
            transform: translateX(3px);
        }

        .dropdown-link-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background-color: var(--bg-hover);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all var(--transition-fast);
        }

        .dropdown-link:hover .dropdown-link-icon {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .dropdown-link-desc {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
            display: block;
            margin-top: 1px;
        }

        /* --- Right Utility Section --- */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        /* Language and Theme buttons */
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all var(--transition-fast);
            position: relative;
        }

        .action-btn:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--text-muted);
        }

        .lang-toggle {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        /* Notifications Bell */
        .notification-trigger {
            position: relative;
        }

        .badge-dot {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 8px;
            height: 8px;
            background-color: var(--danger-color);
            border-radius: var(--radius-full);
            border: 2px solid var(--bg-body);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
            }

            70% {
                box-shadow: 0 0 0 5px rgba(220, 38, 38, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
            }
        }

        .notifications-menu {
            width: 320px;
            right: 0;
            left: auto;
            transform: translateY(10px);
            padding: 0;
        }

        .notification-trigger:hover .notifications-menu,
        .notification-trigger:focus-within .notifications-menu {
            transform: translateY(0);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .notifications-title {
            font-weight: 600;
            font-size: 14px;
            font-family: var(--font-heading);
        }

        .mark-read-btn {
            font-size: 11px;
            color: var(--primary-light);
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
        }

        .notifications-list {
            max-height: 280px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color var(--transition-fast);
        }

        .notification-item:hover {
            background-color: var(--bg-hover);
        }

        .notification-item.unread {
            background-color: rgba(37, 99, 235, 0.04);
        }

        .notification-icon-wrapper {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background-color: rgba(var(--primary-rgb), 0.1);
            color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-content {
            display: flex;
            flex-direction: column;
        }

        .notification-text {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .notification-time {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .notifications-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .view-all-btn {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary-light);
        }

        /* --- Profile Action Area --- */
        .profile-trigger {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 6px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border-color);
            cursor: pointer;
            background-color: transparent;
            transition: all var(--transition-fast);
        }

        .profile-trigger:hover {
            background-color: var(--bg-hover);
            border-color: var(--text-muted);
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--accent-light), var(--accent-color));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            padding-right: 8px;
        }

        .profile-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .profile-dept {
            font-size: 10px;
            font-weight: 500;
            color: var(--text-muted);
            line-height: 1.1;
            margin-top: 1px;
        }

        .profile-dropdown {
            right: 0;
            left: auto;
            width: 220px;
            transform: translateY(10px);
        }

        .profile-trigger:hover .profile-dropdown,
        .profile-trigger:focus-within .profile-dropdown {
            transform: translateY(0);
        }

        .profile-dd-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-dd-role {
            font-size: 11px;
            color: var(--accent-color);
            font-weight: 600;
            text-transform: uppercase;
        }

        .profile-dd-name {
            font-weight: 600;
            font-size: 14px;
            margin-top: 2px;
        }

        .profile-dd-email {
            font-size: 11px;
            color: var(--text-muted);
        }

        .profile-dropdown-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .profile-dropdown-link:hover {
            background-color: var(--bg-hover);
            color: var(--primary-light);
        }

        .profile-dropdown-link.logout-link {
            color: var(--danger-color);
        }

        .profile-dropdown-link.logout-link:hover {
            background-color: rgba(220, 38, 38, 0.05);
        }

        /* --- Mobile Menu and Responsive Drawer --- */
        .mobile-toggle-btn {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: transparent;
            color: var(--text-secondary);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all var(--transition-fast);
        }

        .mobile-toggle-btn:hover {
            background-color: var(--bg-hover);
        }

        /* Mobile Sidebar Drawer */
        .mobile-nav-drawer {
            position: fixed;
            top: 0;
            right: -280px;
            width: 280px;
            height: 100vh;
            background-color: var(--bg-card);
            border-left: 1px solid var(--border-color);
            z-index: 2000;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            transition: right var(--transition-normal);
        }

        .mobile-nav-drawer.open {
            right: 0;
        }

        .drawer-header {
            height: var(--header-height);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }

        .drawer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-drawer-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            border: none;
            background: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }

        .close-drawer-btn:hover {
            background-color: var(--bg-hover);
        }

        .drawer-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .drawer-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .drawer-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            transition: all var(--transition-fast);
        }

        .drawer-nav-link:hover,
        .drawer-nav-link.active {
            background-color: var(--bg-hover);
            color: var(--primary-light);
        }

        /* Mobile Collapsible Submenu */
        .drawer-submenu-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
        }

        .drawer-submenu {
            list-style: none;
            padding-left: 36px;
            margin-top: 4px;
            display: none;
            flex-direction: column;
            gap: 4px;
        }

        .drawer-submenu.show {
            display: flex;
        }

        .drawer-sub-link {
            display: block;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .drawer-sub-link:hover {
            color: var(--primary-light);
            background-color: var(--bg-hover);
        }

        /* Backdrop overlay */
        .drawer-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-normal), visibility var(--transition-normal);
        }

        .drawer-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        /* --- Responsive Layout Breakpoints --- */

        @media (max-width: 1200px) {
            .header-search {
                max-width: 200px;
            }

            .header-search:focus-within {
                max-width: 250px;
            }
        }

        @media (max-width: 1024px) {

            .header-nav,
            .header-search {
                display: none;
            }

            .mobile-toggle-btn {
                display: flex;
            }

            .profile-info {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .brand-text {
                display: none;
            }

            .header-container {
                padding: 0 16px;
            }
        }

        /* --- General Workspace/Layout Classes --- */
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .welcome-section {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.05), rgba(37, 99, 235, 0.05));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .welcome-title {
            font-family: var(--font-heading);
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .welcome-subtitle {
            color: var(--text-secondary);
            font-size: 15px;
        }

        .welcome-meta {
            font-size: 12px;
            font-weight: 600;
            background-color: var(--primary-light);
            color: #ffffff;
            padding: 6px 14px;
            border-radius: var(--radius-full);
        }

        /* Dashboard Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.primary {
            background-color: rgba(30, 58, 138, 0.1);
            color: var(--primary-color);
        }

        .stat-icon.success {
            background-color: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .stat-icon.accent {
            background-color: rgba(217, 119, 6, 0.1);
            color: var(--accent-color);
        }

        .stat-icon.info {
            background-color: rgba(2, 132, 199, 0.1);
            color: var(--info-color);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Two Column Content Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .content-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-btn {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-light);
        }

        /* Content Lists */
        .data-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .data-list a {
            display: block;
        }

        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: background-color var(--transition-fast);
        }

        .data-item:hover {
            background-color: var(--bg-hover);
        }

        .item-left {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .item-type-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .item-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .item-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            display: flex;
            gap: 12px;
        }

        .item-badge {
            background-color: var(--border-color);
            color: var(--text-secondary);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-weight: 500;
        }

        .item-right .download-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .item-right .download-btn:hover {
            background-color: var(--primary-light);
            color: #ffffff;
            border-color: var(--primary-light);
        }

        /* Notice Board Feed */
        .notice-feed {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .notice-item {
            position: relative;
            padding-left: 16px;
            border-left: 3px solid var(--primary-light);
        }

        .notice-item.urgent {
            border-left-color: var(--danger-color);
        }

        .notice-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .notice-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Footer Section */
        .idp-footer {
            border-top: 1px solid var(--border-color);
            margin-top: 60px;
            padding: 30px 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Responsive Grid layouts */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <!-- Header Start -->
    <header class="idp-header">
        <div class="header-container">

            <!-- Left: Maharashtra Emblem -->
            <div class="header-left">
                <a href="user_dashboard.php">
                    <img src="assets/maharashtra-emblem.png" alt="Maharashtra State Emblem" class="brand-emblem-img">
                </a>
            </div>

            <!-- Middle: Center Title & Branding -->
            <div class="header-middle">
                <a href="user_dashboard.php" class="brand-text-wrapper">
                    <h1 class="brand-title">Zilla Parishad Hingoli</h1>
                    <p class="brand-subtitle">Inter-Department Portal</p>
                </a>
            </div>

            <!-- Right: ZP Logo & Actions -->
            <div class="header-right">
                <img src="assets/zp-logo.png" alt="ZP Hingoli Logo" class="brand-logo-img">

                <div class="header-divider"></div>

                <!-- Actions (Language, Theme, Notifications, Profile) -->
                <div class="header-actions">


                    <!-- Theme Toggle -->
                    <button class="action-btn" id="themeToggleBtn" title="Toggle Theme" onclick="toggleTheme()">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <!-- User Profile Menu -->
                    <div class="profile-trigger">
                        <div class="profile-avatar">
                            <?php echo $user_initials; ?>
                        </div>
                        <div class="profile-info">
                            <span class="profile-name"><?php echo $user_name; ?></span>
                            <span class="profile-dept"><?php echo $user_dept; ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down nav-dropdown-indicator"
                            style="margin-right: 6px; font-size: 10px;"></i>

                        <div class="dropdown-menu profile-dropdown">
                            <div class="profile-dd-header">
                                <span class="profile-dd-role"><?php echo $user_role; ?></span>
                                <div class="profile-dd-name"><?php echo $user_name; ?></div>
                                <div class="profile-dd-email">rajesh.patil@maharashtra.gov.in</div>
                            </div>
                            <a href="profile.php" class="profile-dropdown-link">
                                <i class="fa-regular fa-user"></i> My Profile
                            </a>
                            <a href="settings.php" class="profile-dropdown-link">
                                <i class="fa-solid fa-gear"></i> Settings
                            </a>
                            <a href="tasks.php" class="profile-dropdown-link">
                                <i class="fa-solid fa-list-check"></i> Tasks Assigned
                            </a>
                            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 6px 0;">
                            <a href="logout.php" class="profile-dropdown-link logout-link">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </header>
    <!-- Header End -->

    <!-- Core Portal Interactions Script -->
    <script>

        // 2. Theme Toggle (Light / Dark)
        function toggleTheme() {
            const body = document.body;
            const icon = document.querySelector('#themeToggleBtn i');

            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.className = 'fa-solid fa-moon';
                localStorage.setItem('idp-theme', 'light');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.className = 'fa-solid fa-sun';
                localStorage.setItem('idp-theme', 'dark');
            }
        }

        // Initialize Theme from localStorage or Preferences
        (function initTheme() {
            const savedTheme = localStorage.getItem('idp-theme');
            const body = document.body;
            const icon = document.querySelector('#themeToggleBtn i');

            if (savedTheme === 'dark') {
                body.classList.add('dark-theme');
                if (icon) icon.className = 'fa-solid fa-sun';
            } else if (savedTheme === 'light') {
                body.classList.add('light-theme');
                if (icon) icon.className = 'fa-solid fa-moon';
            } else {
                // Respect System Theme Settings
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    body.classList.add('dark-theme');
                    if (icon) icon.className = 'fa-solid fa-sun';
                } else {
                    body.classList.add('light-theme');
                    if (icon) icon.className = 'fa-solid fa-moon';
                }
            }
        })();
    </script>