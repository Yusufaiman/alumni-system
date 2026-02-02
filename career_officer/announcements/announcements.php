<?php
/**
 * ANNOUNCEMENTS PAGE
 * Accessible by Student and Alumni.
 * Read-only view of official updates.
 */
session_start();
require_once dirname(__DIR__, 2) . "/config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || !in_array($_SESSION['role'], ['STUDENT', 'ALUMNI'])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Fetch Active Announcements
$sql = "SELECT announcementID, title, content, createdRole, createdDate 
        FROM announcement 
        WHERE status = 'ACTIVE' 
        ORDER BY createdDate DESC";
$result = $conn->query($sql);

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .announcements-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .announcements-header {
            margin-bottom: 3rem;
            text-align: center;
        }

        .announcements-header h1 {
            font-size: 2.25rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .announcements-header p {
            color: var(--text-muted);
            font-size: 1.125rem;
        }

        .announcements-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .announcement-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: var(--text-dark);
            font-weight: 700;
            margin: 0;
            padding-right: 1rem;
        }

        .role-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-career {
            background: #E0F2FE;
            color: #0369A1;
        }

        .badge-admin {
            background: #F3E8FF;
            color: #7E22CE;
        }

        .meta-info {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .content-preview {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 0.875rem;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .read-more-btn:hover {
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            background-color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 1.5rem;
            background: #F3F4F6;
            border: none;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            transition: 0.2s;
        }

        .close-modal:hover {
            background: #E5E7EB;
            color: var(--text-dark);
        }

        .modal-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .modal-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .modal-body {
            line-height: 1.8;
            color: var(--text-dark);
            white-space: pre-wrap;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 1rem;
            border: 1px dashed var(--border-light);
        }
    </style>
</head>

<body>
    <?php include_once dirname(__DIR__, 2) . '/components/topbar.php'; ?>

    <div class="announcements-container">
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo ($_SESSION['role'] === 'STUDENT' ? '../../student/dashboard.php' : '../../alumni/dashboard.php'); ?>"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">
                &larr; Back to Dashboard
            </a>
        </div>

        <header class="announcements-header">
            <h1>Announcements</h1>
            <p>Official updates and career notices from the university.</p>
        </header>

        <div class="announcements-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="announcement-card">
                        <div class="card-header">
                            <h2>
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h2>
                            <span
                                class="role-badge <?php echo $row['createdRole'] === 'ADMIN' ? 'badge-admin' : 'badge-career'; ?>">
                                Published by
                                <?php echo $row['createdRole'] === 'ADMIN' ? 'Admin' : 'Career Office'; ?>
                            </span>
                        </div>

                        <div class="meta-info">
                            📅 Published on
                            <?php echo date('d M Y', strtotime($row['createdDate'])); ?>
                        </div>

                        <div class="content-preview">
                            <?php echo htmlspecialchars($row['content']); ?>
                        </div>

                        <button class="read-more-btn"
                            onclick="openAnnouncement(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            Read More &rarr;
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📢</div>
                    <p style="color: var(--text-muted); font-size: 1.125rem;">No announcements available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeAnnouncement()">&times;</button>
            <h2 id="modalTitle" class="modal-title"></h2>
            <div class="modal-meta">
                <span id="modalBadge" class="role-badge"></span>
                <span id="modalDate" style="font-size: 0.875rem; color: var(--text-muted);"></span>
            </div>
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('announcementModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBadge = document.getElementById('modalBadge');
        const modalDate = document.getElementById('modalDate');
        const modalBody = document.getElementById('modalBody');

        function openAnnouncement(data) {
            modalTitle.innerText = data.title;
            modalDate.innerText = 'Published on ' + formatDate(data.createdDate);
            modalBody.innerText = data.content;

            // Badge logic
            modalBadge.className = 'role-badge ' + (data.createdRole === 'ADMIN' ? 'badge-admin' : 'badge-career');
            modalBadge.innerText = 'Published by ' + (data.createdRole === 'ADMIN' ? 'Admin' : 'Career Office');

            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeAnnouncement() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('en-GB', options);
        }

        // Close on outside click
        window.onclick = function (event) {
            if (event.target == modal) {
                closeAnnouncement();
            }
        }

        // Profile dropdown logic
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