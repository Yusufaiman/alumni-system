<?php
/**
 * ENHANCED ALUMNI DIRECTORY
 * Features: Sticky Topbar, Functional Multi-filter, Dynamic Dropdowns, and Card UI.
 */
session_start();
require_once "../config/db.php";

/* Access control */
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

// --- 1. Fetch Dynamic Filter Options ---
$years_query = "SELECT DISTINCT graduationYear FROM alumni WHERE graduationYear IS NOT NULL ORDER BY graduationYear DESC";
$years_result = mysqli_query($conn, $years_query);

$industries_query = "SELECT DISTINCT industry FROM alumni WHERE industry IS NOT NULL AND industry != '' ORDER BY industry ASC";
$industries_result = mysqli_query($conn, $industries_query);

// --- 2. Handle Filtering Logic ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';
$industry = isset($_GET['industry']) ? mysqli_real_escape_string($conn, $_GET['industry']) : '';

$sql = "
    SELECT
        u.name,
        a.graduationYear,
        a.currentCompany,
        a.currentPosition,
        a.industry
    FROM alumni a
    INNER JOIN user u ON a.userID = u.userID
    WHERE 1=1
";

if ($search !== '') {
    $sql .= " AND (u.name LIKE '%$search%' OR a.currentCompany LIKE '%$search%' OR a.industry LIKE '%$search%' OR a.currentPosition LIKE '%$search%')";
}
if ($year !== '') {
    $sql .= " AND a.graduationYear = '$year'";
}
if ($industry !== '') {
    $sql .= " AND a.industry = '$industry'";
}

$sql .= " ORDER BY u.name ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Directory | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/directory.css">
</head>

<body>

    <!-- Shared Top Navigation Bar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="directory-container">

        <!-- Header Section -->
        <div class="directory-header" style="margin-bottom: 2rem;">
            <div class="header-content">
                <h1>Alumni Directory</h1>
                <p>Explore career paths and industry insights from our global network.</p>
            </div>
        </div>

        <!-- Functional Filter Bar -->
        <div class="filter-card">
            <form action="" method="GET" class="filter-grid" id="filterForm">
                <div class="filter-group">
                    <label>Keyword</label>
                    <input type="text" name="search" class="search-input"
                        style="padding: 10px 15px; border-radius: 12px; border: 1px solid var(--border-light);"
                        placeholder="Name, rank or company..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Graduation Year</label>
                    <select name="year" class="search-input" onchange="this.form.submit()"
                        style="padding: 10px 15px; border-radius: 12px; border: 1px solid var(--border-light); appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center;">
                        <option value="">All Years</option>
                        <?php while ($y = mysqli_fetch_assoc($years_result)): ?>
                            <option value="<?php echo $y['graduationYear']; ?>" <?php echo ($year == $y['graduationYear']) ? 'selected' : ''; ?>>
                                Class of <?php echo $y['graduationYear']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Industry</label>
                    <select name="industry" class="search-input" onchange="this.form.submit()"
                        style="padding: 10px 15px; border-radius: 12px; border: 1px solid var(--border-light); appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center;">
                        <option value="">All Industries</option>
                        <?php while ($ind = mysqli_fetch_assoc($industries_result)): ?>
                            <option value="<?php echo htmlspecialchars($ind['industry']); ?>" <?php echo ($industry == $ind['industry']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ind['industry']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Directory Grid -->
        <div class="directory-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="alumni-card"
                        style="background: var(--background-white); border: 1px solid var(--border-light); box-shadow: var(--card-shadow);">
                        <div class="alumni-info-header">
                            <div class="avatar-circle" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                            </div>
                            <div class="alumni-meta-name">
                                <h3 style="color: var(--text-dark);"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p style="color: var(--primary-blue);">Class of
                                    <?php echo htmlspecialchars($row['graduationYear']); ?></p>
                            </div>
                        </div>

                        <div class="alumni-details">
                            <div class="detail-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <div>
                                    <span class="detail-label">Current Role</span>
                                    <span
                                        class="detail-value"><?php echo htmlspecialchars($row['currentPosition'] ?: 'Not Specified'); ?></span>
                                </div>
                            </div>

                            <div class="detail-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                                <div>
                                    <span class="detail-label">Company</span>
                                    <span
                                        class="detail-value"><?php echo htmlspecialchars($row['currentCompany'] ?: 'Not Specified'); ?></span>
                                </div>
                            </div>

                            <div class="detail-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                    </path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <div>
                                    <span class="detail-label">Industry</span>
                                    <span class="badge"
                                        style="background: rgba(37, 99, 235, 0.05); color: var(--primary-blue);"><?php echo htmlspecialchars($row['industry'] ?: 'General'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h2>No alumni found</h2>
                    <p>Try adjusting your filters or search keywords to find what you're looking for.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dropdown Interaction Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function (e) {
                    if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>

</body>

</html>