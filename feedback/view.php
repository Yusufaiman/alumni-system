<?php
/**
 * VIEW MY FEEDBACK
 * Students and alumni can view their submitted feedback and admin responses
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || !in_array($_SESSION['role'], ['STUDENT', 'ALUMNI'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch user's feedback
$userID = $_SESSION['userID'];
$stmt = $conn->prepare("SELECT feedbackID, subject, message, status, adminResponse, createdDate, handledDate FROM system_feedback WHERE userID = ? ORDER BY createdDate DESC");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$dashboardLink = $_SESSION['role'] === 'STUDENT' ? '../student/dashboard.php' : '../alumni/dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feedback | Alumni Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.2s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .feedback-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .feedback-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .feedback-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .feedback-date {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-new {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .status-in_progress {
            background: #FEF3C7;
            color: #B45309;
        }

        .status-resolved {
            background: #DCFCE7;
            color: #166534;
        }

        .feedback-message {
            background: #F8FAFC;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            color: #475569;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .admin-response {
            background: #EFF6FF;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #2563EB;
            margin-top: 1rem;
        }

        .admin-response-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #1E40AF;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-response-text {
            color: #1e293b;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .empty-state i {
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem 0.5rem;
            }

            .header {
                padding: 1.5rem;
            }

            .feedback-card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="<?php echo $dashboardLink; ?>" class="back-link">
            <i data-lucide="arrow-left" size="16"></i>
            Back to Dashboard
        </a>

        <div class="header">
            <h1>My Feedback</h1>
            <p>View your submitted feedback and admin responses</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div>
                            <div class="feedback-title">
                                <?php echo htmlspecialchars($row['subject']); ?>
                            </div>
                            <div class="feedback-date">
                                Submitted on
                                <?php echo date('d M Y, h:i A', strtotime($row['createdDate'])); ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo str_replace('_', ' ', $row['status']); ?>
                        </span>
                    </div>

                    <div class="feedback-message">
                        <?php echo htmlspecialchars($row['message']); ?>
                    </div>

                    <?php if ($row['adminResponse']): ?>
                        <div class="admin-response">
                            <div class="admin-response-label">
                                <i data-lucide="shield-check" size="14"></i>
                                Admin Response
                                <?php if ($row['handledDate']): ?>
                                    <span style="font-weight:400; color:#64748b;">
                                        •
                                        <?php echo date('d M Y', strtotime($row['handledDate'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-response-text">
                                <?php echo htmlspecialchars($row['adminResponse']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div
                            style="padding:0.75rem; background:#FEF3C7; border-radius:6px; font-size:0.85rem; color:#B45309; display:flex; align-items:center; gap:0.5rem;">
                            <i data-lucide="clock" size="16"></i>
                            Waiting for admin response...
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i data-lucide="inbox" size="64"></i>
                <h3>No Feedback Yet</h3>
                <p>You haven't submitted any feedback. Share your thoughts with us!</p>
                <a href="submit.php"
                    style="display:inline-block; margin-top:1rem; padding:0.75rem 1.5rem; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; border-radius:8px; font-weight:600;">
                    Submit Feedback
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>