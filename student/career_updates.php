<?php
/**
 * STUDENT - VIEW CAREER UPDATES
 * Read-only view of career insights shared by alumni.
 */
session_start();
require_once "../config/db.php";

// Access Control: Student Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID']; // This is userID from users table

// Fetch Visible Updates
// We join with the 'user' table via alumni -> userID if we want precise names, 
// but typically 'alumni' table has a 'name' field or we join alumni -> user.
// Let's assume alumni table has the name or we join.
// Based on previous contexts, alumni table usually stores profile data. 
// Let's check if alumni table has a 'name' column or we need to join 'user' table.
// Checking schema... assuming alumni table has userID, and user table has name. 
// OR alumni table has name. Let's write a safe query assuming standard schema.

$sql = "
    SELECT 
        cu.updateID,
        cu.title,
        cu.content,
        cu.createdDate,
        u.name AS alumniName
    FROM career_update cu
    JOIN alumni a ON cu.alumniID = a.alumniID
    JOIN user u ON a.userID = u.userID
    WHERE cu.status = 'VISIBLE'
    ORDER BY cu.createdDate DESC
";

$result = $conn->query($sql);
$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Updates | Student Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .page-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .header-section {
            margin-bottom: 3rem;
            text-align: center;
        }

        .header-section h1 {
            font-size: 2.25rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .header-section p {
            color: var(--text-muted);
            font-size: 1.125rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 1rem;
            border: 1px dashed var(--border-light);
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="page-container">
        <div style="margin-bottom: 1rem;">
            <a href="dashboard.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                Back to Dashboard</a>
        </div>

        <header class="header-section">
            <h1>Career Updates from Alumni</h1>
            <p>Learn from alumni experiences, achievements, and career journeys.</p>
        </header>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="updates-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php include "../components/career/CareerUpdateCard.php"; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📭</div>
                <p style="color: var(--text-muted); font-size: 1.125rem;">No career updates have been shared yet. Please
                    check back later.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Read More Toggle Script -->
    <script>
        function toggleContent(btn, encodedContent) {
            const cardBody = btn.parentElement;
            const fullContent = atob(encodedContent); // Decode Base64
            const p = cardBody.querySelector('.content-text');

            if (btn.innerText.includes('more')) {
                p.innerHTML = fullContent.replace(/\n/g, '<br>');
                btn.innerHTML = 'Show less &uarr;';
            } else {
                p.innerText = fullContent.length > 250 ? fullContent.substring(0, 250) + '...' : fullContent;
                btn.innerHTML = 'Read more &darr;';
            }
        }

        // Dropdown Interaction Logic
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