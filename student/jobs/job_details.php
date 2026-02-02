<?php
/**
 * JOB DETAILS PAGE
 * Displays full details of a specific job posting.
 */
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['userID']) || !isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$jobID = intval($_GET['id']);
$userID = $_SESSION['userID'];
$role = $_SESSION['role'];

// Fetch job details
$stmt = $conn->prepare("
    SELECT j.*, u.name as posterName, u.email as posterEmail
    FROM jobposting j
    JOIN user u ON j.postedByID = u.userID
    WHERE j.jobID = ?
");
$stmt->bind_param("i", $jobID);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    die("Job not found.");
}

// Check if applied
$appStmt = $conn->prepare("SELECT status FROM job_application WHERE jobID = ? AND applicantID = ? AND applicantRole = ?");
$appStmt->bind_param("iis", $jobID, $userID, $role);
$appStmt->execute();
$application = $appStmt->get_result()->fetch_assoc();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($job['title']); ?> | Details
    </title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .details-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
        }

        .details-header {
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .details-header h1 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .company-info {
            font-size: 1.125rem;
            color: #4F46E5;
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }

        .details-meta {
            display: flex;
            gap: 1.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 2rem 0 1rem;
            border-left: 4px solid #4F46E5;
            padding-left: 1rem;
        }

        .content-box {
            line-height: 1.6;
            color: var(--text-muted);
            white-space: pre-line;
        }

        .apply-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-apply-now {
            background: #4F46E5;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-apply-now:hover {
            background: #4338CA;
            transform: translateY(-1px);
        }

        .btn-apply-now:disabled {
            background: #9CA3AF;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="details-container">
        <div class="details-header">
            <h1>
                <?php echo htmlspecialchars($job['title']); ?>
            </h1>
            <span class="company-info">
                <?php echo htmlspecialchars($job['company']); ?>
            </span>
            <div class="details-meta">
                <div class="meta-item">📍
                    <?php echo htmlspecialchars($job['location'] ?: 'Not specified'); ?>
                </div>
                <div class="meta-item">💼
                    <?php echo htmlspecialchars($job['jobType']); ?>
                </div>
                <div class="meta-item">📅 Posted
                    <?php echo date('M d, Y', strtotime($job['datePosted'])); ?>
                </div>
            </div>
        </div>

        <div class="section-title">Description</div>
        <div class="content-box">
            <?php echo htmlspecialchars($job['description']); ?>
        </div>

        <div class="section-title">Requirements</div>
        <div class="content-box">
            <?php echo htmlspecialchars($job['requirements'] ?: 'No specific requirements listed.'); ?>
        </div>

        <div class="apply-section">
            <div>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Posted by:</p>
                <p style="font-weight: 600; color: var(--text-dark);">
                    <?php echo htmlspecialchars($job['posterName']); ?>
                </p>
            </div>

            <?php if ($role === 'STUDENT' || $role === 'ALUMNI'): ?>
                <?php if ($application): ?>
                    <button class="btn-apply-now" disabled>Applied (
                        <?php echo $application['status']; ?>)
                    </button>
                <?php else: ?>
                    <form action="apply_job.php" method="POST">
                        <input type="hidden" name="jobID" value="<?php echo $jobID; ?>">
                        <button type="submit" class="btn-apply-now">Apply for this Position</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: var(--text-muted); font-style: italic;">Viewing as
                    <?php echo $role; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>