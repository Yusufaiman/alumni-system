<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo isset($pageTitle) ? $pageTitle : 'Admin'; ?> | Alumni System
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons: Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Charts: Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-body: #F5F7FA;
            --bg-card: #FFFFFF;
            --bg-sidebar: #0F172A;
            /* Dark Navy */

            --primary: #2563EB;
            /* Blue */
            --primary-hover: #1D4ED8;

            --text-dark: #1E293B;
            --text-medium: #64748B;
            --text-light: #94A3B8;
            --text-sidebar: #E2E8F0;

            --border: #E2E8F0;
            --border-sidebar: #1E293B;

            --success-bg: #DCFCE7;
            --success-text: #166534;
            --warning-bg: #FEF3C7;
            --warning-text: #B45309;
            --danger-bg: #FEE2E2;
            --danger-text: #991B1B;

            --radius-xl: 16px;
            --radius-lg: 12px;
            --radius-md: 8px;

            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            outline: none;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            color: var(--text-sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: width 0.3s;
        }

        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-sidebar);
        }

        .sidebar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #FFFFFF;
            letter-spacing: -0.025em;
        }

        .nav-menu {
            padding: 1.5rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #FFFFFF;
        }

        .nav-item.active {
            background: var(--primary);
            color: #FFFFFF;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-sidebar);
            margin-top: auto;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        /* MAIN AREA */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* TOP NAV */
        .top-nav {
            height: 72px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .search-container {
            position: relative;
            width: 320px;
        }

        .search-input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            color: var(--text-dark);
            background: #F8FAFC;
            transition: border 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            width: 16px;
            height: 16px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-medium);
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: 0.2s;
        }

        .btn-icon:hover {
            background: #F1F5F9;
            color: var(--text-dark);
        }

        .badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--danger-text);
            border-radius: 50%;
            border: 2px solid white;
        }

        /* DASHBOARD CONTENT */
        .dashboard-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Common Components */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .page-desc {
            color: var(--text-medium);
            font-size: 0.875rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Table Styles */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .clean-table {
            width: 100%;
            border-collapse: collapse;
        }

        .clean-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-medium);
            font-weight: 600;
            background: #F8FAFC;
            border-bottom: 1px solid var(--border);
        }

        .clean-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-dark);
        }

        .clean-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-open,
        .status-active {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .status-closed,
        .status-inactive {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .status-cancelled,
        .status-deleted {
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        /* KPI & Grid Utilities */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #CBD5E1;
        }

        /* ENHANCED QUICK ACTIONS */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.5rem 1rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .action-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            background: #F8FAFC;
        }

        .action-card-icon {
            color: var(--primary);
            padding: 0.75rem;
            background: #EFF6FF;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .action-card:hover .action-card-icon {
            background: var(--primary);
            color: #fff;
        }

        .action-card-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* ENHANCED SYSTEM HEALTH */
        .health-list {
            display: flex;
            flex-direction: column;
        }

        .health-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .health-item:last-child {
            border-bottom: none;
        }

        .health-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .health-icon {
            color: var(--text-medium);
        }

        .health-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .health-pill {
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .health-ok {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .health-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .health-critical {
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        /* VIEW ALL BUTTON */
        .card-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            transition: background 0.2s;
        }

        .card-action-btn:hover {
            background: #EFF6FF;
        }

        .card-action-btn i {
            transition: transform 0.2s;
        }

        .card-action-btn:hover i {
            transform: translateX(4px);
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 0;
            }

            .main-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <div class="dashboard-content">
            <?php echo $content; ?>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>