<?php
/**
 * SHARED COMPONENT: JobApplicationsTable
 * Used by both Alumni and CSO.
 */

$userID = $_SESSION['userID'];
$role = $_SESSION['role'];

// Fetch Job Postings and their Applicants
$jobIDFilter = isset($_GET['jobID']) ? intval($_GET['jobID']) : 0;

if ($jobIDFilter > 0) {
    // Single Job View
    $jobs_sql = "SELECT jobID, title, company FROM jobposting WHERE postedByID = ? AND postedByRole = ? AND jobID = ? LIMIT 1";
    $jobsStmt = $conn->prepare($jobs_sql);
    $jobsStmt->bind_param("isi", $userID, $role, $jobIDFilter);
} else {
    // All Jobs View
    $jobs_sql = "SELECT jobID, title, company FROM jobposting WHERE postedByID = ? AND postedByRole = ? ORDER BY datePosted DESC";
    $jobsStmt = $conn->prepare($jobs_sql);
    $jobsStmt->bind_param("is", $userID, $role);
}
$jobsStmt->execute();
$jobsRes = $jobsStmt->get_result();
?>

<?php if ($jobsRes->num_rows > 0): ?>
    <?php while ($job = $jobsRes->fetch_assoc()): ?>
        <div class="job-section">
            <div class="job-section-header">
                <h2>
                    <?php echo htmlspecialchars($job['title']); ?> <span
                        style="font-weight: 400; color: var(--text-muted); font-size: 0.875rem;">at
                        <?php echo htmlspecialchars($job['company']); ?>
                    </span>
                </h2>
            </div>
            <div class="applicants-list">
                <?php
                $app_sql = "
                    SELECT 
                        ja.applicationID,
                        ja.status,
                        ja.appliedDate,
                        u.name AS applicantName,
                        u.email AS applicantEmail,
                        ja.applicantRole
                    FROM job_application ja
                    JOIN user u ON ja.applicantID = u.userID
                    WHERE ja.jobID = ?
                    ORDER BY ja.appliedDate DESC
                ";
                $appStmt = $conn->prepare($app_sql);
                $appStmt->bind_param("i", $job['jobID']);
                $appStmt->execute();
                $apps = $appStmt->get_result();
                ?>

                <?php if ($apps->num_rows > 0): ?>
                    <div class="applicant-row"
                        style="border-bottom: 2px solid var(--border-light); font-weight: 700; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <span>Applicant Name</span>
                        <span>Email</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>
                    <?php while ($app = $apps->fetch_assoc()): ?>
                        <div class="applicant-row">
                            <div style="color: var(--text-dark); font-weight: 600;">
                                <?php echo htmlspecialchars($app['applicantName']); ?>
                                <span
                                    style="font-size: 0.7rem; background: #E5E7EB; padding: 2px 6px; border-radius: 4px; margin-left: 5px;"><?php echo $app['applicantRole']; ?></span>
                                <div style="font-size: 0.75rem; font-weight: 400; color: var(--text-muted); margin-top: 2px;">
                                    Applied
                                    <?php echo date('M d, Y', strtotime($app['appliedDate'])); ?>
                                </div>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($app['applicantEmail']); ?>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo $app['status']; ?>
                                </span>
                            </div>
                            <div class="action-btns">
                                <?php if ($app['status'] === 'PENDING'): ?>
                                    <?php
                                    $updateUrl = ($_SESSION['role'] === 'ALUMNI') ? '../alumni/update_application_status.php' : '../career_officer/update_application_status.php';
                                    ?>
                                    <form action="<?php echo $updateUrl; ?>" method="POST" style="display: contents;">
                                        <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                                        <input type="hidden" name="status" value="APPROVED">
                                        <button type="submit" class="btn-sm btn-accept"
                                            onclick="return confirm('Approve this application?')">Approve</button>
                                    </form>
                                    <form action="<?php echo $updateUrl; ?>" method="POST" style="display: contents;">
                                        <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                                        <input type="hidden" name="status" value="REJECTED">
                                        <button type="submit" class="btn-sm btn-reject"
                                            onclick="return confirm('Reject this application?')">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">No actions available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 1rem;">No applications received for
                        this job yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div
        style="text-align: center; padding: 4rem; background: white; border: 1px dashed var(--border-light); border-radius: 1rem;">
        <p style="color: var(--text-muted); font-size: 1.125rem;">You haven't posted any jobs yet.</p>
    </div>
<?php endif; ?>