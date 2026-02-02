<?php
session_start();
/**
 * ALUMNI - SHARE CAREER UPDATES
 * Allow alumni to post updates about their career journey.
 */
require_once "../../config/db.php";

// Access Control: Alumni Only
// STRICT FIX: Checking user_id as requested, while keeping fallback to userID just in case (to be safe across files)
// Ideally one consistency should be used, but adhering to the prompt's explicit instruction for 'user_id'.
if ((!isset($_SESSION['user_id']) && !isset($_SESSION['userID'])) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'ALUMNI')) {
    header("Location: ../../auth/login.php");
    exit();
}

// Fetch ID from session (supporting both keys for robustness)
$userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['userID'];

$successMsg = "";
$errorMsg = "";

// Resolve Alumni ID
// We query the 'alumni' table to find the record linked to this user.
$stmt = $conn->prepare("SELECT alumniID FROM alumni WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $alumniID = $res->fetch_assoc()['alumniID'];
} else {
    // If we can't find the alumni profile, valid session or not, we can't proceed.
    die("Alumni profile not found for User ID: " . htmlspecialchars($userID));
}

// 1. Handle Form Submission (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_update'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $status = $_POST['status'];

        if (empty($title) || empty($content)) {
            // Redirect with error
            header("Location: share_career_updates.php?error=empty_fields");
            exit();
        } else {
            $sql = "INSERT INTO career_update (alumniID, title, content, createdDate, status) VALUES (?, ?, ?, CURDATE(), ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Binding params: i (int), s (string), s (string), s (string)
                $stmt->bind_param("isss", $alumniID, $title, $content, $status);

                if ($stmt->execute()) {
                    // STRICT FIX: Redirect with success=1
                    header("Location: share_career_updates.php?success=1");
                    exit();
                } else {
                    // STRICT FIX: Redirect with error=1 (or specific DB error)
                    header("Location: share_career_updates.php?error=db_error");
                    exit();
                }
            } else {
                header("Location: share_career_updates.php?error=stmt_error");
                exit();
            }
        }
    }
    // 2. Handle Actions (Toggle / Delete)
    elseif (isset($_POST['action_type'])) {
        $updateID = intval($_POST['update_id']);

        if ($_POST['action_type'] === 'toggle_visibility') {
            $currentStatus = $_POST['current_status'];
            $newStatus = ($currentStatus === 'VISIBLE') ? 'HIDDEN' : 'VISIBLE';

            $sql = "UPDATE career_update SET status = ? WHERE updateID = ? AND alumniID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $newStatus, $updateID, $alumniID);

            if ($stmt->execute()) {
                header("Location: share_career_updates.php?success=updated");
                exit();
            }
        } elseif ($_POST['action_type'] === 'delete') {
            $sql = "DELETE FROM career_update WHERE updateID = ? AND alumniID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $updateID, $alumniID);

            if ($stmt->execute()) {
                header("Location: share_career_updates.php?success=deleted");
                exit();
            }
        }
    }
}

// 3. Fetch My Updates
$sql = "SELECT updateID, title, content, createdDate, status FROM career_update WHERE alumniID = ? ORDER BY createdDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumniID);
$stmt->execute();
$result = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Career Updates | Alumni Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
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

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
        }

        .alert-success {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBF7D0;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="page-container">
        <div style="margin-bottom: 1rem;">
            <a href="../dashboard.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                Back to Dashboard</a>
        </div>

        <header class="header-section">
            <h1>Share Career Updates</h1>
            <p>Share your career progress, achievements, or advice to inspire students.</p>
        </header>

        <!-- UI FIX: Handling success=1 -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] == 1 || $_GET['success'] === 'created')
                    echo "Career update published successfully.";
                if ($_GET['success'] === 'updated')
                    echo "Visibility updated successfully.";
                if ($_GET['success'] === 'deleted')
                    echo "Career update deleted.";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                if ($_GET['error'] === 'empty_fields')
                    echo "Title and content cannot be empty.";
                elseif ($_GET['error'] === '1')
                    echo "An error occurred while publishing.";
                else
                    echo "Error processing request.";
                ?>
            </div>
        <?php endif; ?>

        <!-- Create Update Form -->
        <?php include "../../components/career/CareerUpdateForm.php"; ?>

        <!-- My Updates List -->
        <?php include "../../components/career/CareerUpdateList.php"; ?>
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