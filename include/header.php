<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Portal - Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #f8fafc;
            --bg-sidebar: #0f172a;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #1e293b;
            --sidebar-active: #38bdf8;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #e2e8f0;
            
            /* Status Colors */
            --status-resolved-bg: #dcfce7;
            --status-resolved-text: #166534;
            --status-pending-bg: #fef9c3;
            --status-pending-text: #854d0e;
            --status-open-bg: #fee2e2;
            --status-open-text: #991b1b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            background-image: radial-gradient(circle at top left, rgba(56,189,248,0.12), transparent 28%),
                              radial-gradient(circle at bottom right, rgba(59,130,246,0.08), transparent 24%);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR SYSTEM --- */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: var(--white);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            box-shadow: 4px 0 30px rgba(15, 23, 42, 0.18);
        }

        .sidebar-brand {
            padding: 28px 24px;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand span {
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            list-style: none;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-weight: 500;
            border-radius: 14px;
            transition: all 0.25s ease;
            position: relative;
        }

        .sidebar-item a:hover {
            background-color: rgba(59, 130, 246, 0.14);
            color: var(--white);
            transform: translateX(2px);
        }

        .sidebar-item.active a {
            background-color: rgba(56, 189, 248, 0.18);
            color: #7dd3fc;
            font-weight: 600;
            box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.16);
        }

        .sidebar-item.active a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12px;
            bottom: 12px;
            width: 4px;
            border-radius: 0 999px 999px 0;
            background: linear-gradient(180deg, #38bdf8, #60a5fa);
        }

        /* --- MAIN CONTENT AREA --- */
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-title h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 4px;
        }

        /* --- REPORT UTILITIES (Filters/Search) --- */
        .table-toolbar {
            background: var(--white);
            padding: 16px 24px;
            border-radius: 12px 12px 0 0;
            border: 1px solid var(--border-color);
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box input {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            width: 300px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-box input:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(165, 180, 252, 0.2);
        }

        .export-btn {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .export-btn:hover {
            background-color: #f8fafc;
        }

        /* --- PROFESSIONAL DATA TABLE --- */
        .table-container {
            background: var(--white);
            border-radius: 0 0 12px 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        .report-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            padding: 16px 24px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        .report-table td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .report-table tr:hover td {
            background-color: #f8fafc;
        }

        .complaint-id {
            font-weight: 600;
            color: #4f46e5;
        }

        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.resolved {
            background-color: var(--status-resolved-bg);
            color: var(--status-resolved-text);
        }

        .badge.pending {
            background-color: var(--status-pending-bg);
            color: var(--status-pending-text);
        }

        .badge.open {
            background-color: var(--status-open-bg);
            color: var(--status-open-text);
        }

        /* Action Buttons */
        .action-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        /* --- SIDEBAR TOGGLE BUTTON --- */
        .toggle-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #fff;
            border: 2px solid rgba(56, 189, 248, 0.3);
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 8px;
            font-size: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
        }

        .toggle-btn:hover {
            background: linear-gradient(135deg, #334155, #1e293b);
            border-color: rgba(56, 189, 248, 0.6);
            box-shadow: 0 6px 16px rgba(56, 189, 248, 0.2);
            transform: scale(1.05);
        }

        .toggle-btn:active {
            transform: scale(0.98);
        }

        /* Sidebar closed state */
        .sidebar.closed {
            transform: translateX(-100%);
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-btn {
                top: 15px;
                left: 15px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 220px;
            }

            .toggle-btn {
                width: 44px;
                height: 44px;
                font-size: 18px;
                padding: 10px 14px;
            }
        }
    </style>
</head>
<body>
