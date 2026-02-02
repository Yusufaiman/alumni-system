<?php
session_start();
require_once "../../config/db.php";

// Access Control: Student Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

// Fetch user's feedback history
$userID = $_SESSION['userID'];
$stmtHistory = $conn->prepare("SELECT feedbackID, subject, message, status, adminResponse, createdDate, handledDate FROM system_feedback WHERE userID = ? ORDER BY createdDate DESC");
$stmtHistory->bind_param("i", $userID);
$stmtHistory->execute();
$feedbackHistory = $stmtHistory->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | Student Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .page-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .header-section {
            margin-bottom: 2rem;
        }

        .header-section h1 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .header-section p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-success {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBF7D0;
        }

        .form-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .char-counter {
            text-align: right;
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #2563eb;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .feedback-table {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .feedback-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .feedback-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid #e2e8f0;
        }

        .feedback-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .feedback-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
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

        .admin-response-box {
            background: #EFF6FF;
            border-left: 3px solid #3b82f6;
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 0.375rem;
        }

        .admin-response-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .admin-response-text {
            color: #1e293b;
            line-height: 1.6;
        }

        .no-response {
            color: #94a3b8;
            font-style: italic;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <?php include_once '../../components/topbar.php'; ?>

    <div class="page-container">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header-section">
            <h1>Submit Feedback</h1>
            <p>Share your feedback or report an issue to the admin team</p>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success">
                Your feedback has been submitted successfully. The admin team will review it shortly.
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="../../feedback/submit.php">
                <div class="form-group">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="subject" class="form-input" placeholder="Brief summary of your feedback"
                        maxlength="150" required>
                    <div class="char-counter">Max 150 characters</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea name="message" class="form-textarea" placeholder="Describe your feedback in detail..."
                        required></textarea>
                    <div class="char-counter">Please be as detailed as possible</div>
                </div>

                <button type="submit" class="btn-submit">Submit Feedback</button>
            </form>
        </div>

        <!-- Feedback Status Section -->
        <div class="section-title">Feedback Status</div>

        <div class="feedback-table">
            <?php if ($feedbackHistory->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($feedback = $feedbackHistory->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($feedback['subject']); ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: #64748b;">
                                        <?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?>
                                        <?php if (strlen($feedback['message']) > 100)
                                            echo '...'; ?>
                                    </div>
                                </td>
                                <td style="font-size: 0.875rem; color: #64748b;">
                                    <?php echo date('d M Y', strtotime($feedback['createdDate'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($feedback['status']); ?>">
                                        <?php echo str_replace('_', ' ', $feedback['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($feedback['adminResponse']): ?>
                                        <div class="admin-response-box">
                                            <div class="admin-response-label">Admin Response</div>
                                            <div class="admin-response-text">
                                                <?php echo nl2br(htmlspecialchars($feedback['adminResponse'])); ?>
                                            </div>
                                            <?php if ($feedback['handledDate']): ?>
                                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                                                    Responded on <?php echo date('d M Y', strtotime($feedback['handledDate'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-response">Awaiting admin response</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No feedback submitted yet. Use the form above to share your thoughts.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>