<?php
/**
 * SHARED COMPONENT: JobGridUI
 * Displays a grid of job cards.
 * Used by Student (Browse & Apply) and Alumni (Browse Only).
 */
?>

<div class="job-grid">
    <?php if ($jobsList && $jobsList->num_rows > 0): ?>
        <?php while ($row = $jobsList->fetch_assoc()): ?>
            <div class="job-card">
                <div class="job-card-header">
                    <div class="company-logo-placeholder">
                        <?php echo strtoupper(substr($row['company'], 0, 1)); ?>
                    </div>
                    <div>
                        <?php if (isset($row['applicationStatus']) && $row['applicationStatus']): ?>
                            <span class="job-badge badge-applied">Applied:
                                <?php echo $row['applicationStatus']; ?>
                            </span>
                        <?php else: ?>
                            <span class="job-badge badge-active">Active</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="job-info">
                    <h3>
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h3>
                    <span class="company-name">
                        <?php echo htmlspecialchars($row['company']); ?>
                    </span>
                    <div class="job-description">
                        <?php echo htmlspecialchars($row['description']); ?>
                    </div>
                </div>

                <div class="job-meta">
                    <div class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>
                            <?php echo htmlspecialchars($row['jobType'] ?? 'Full-time'); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Posted
                            <?php echo date('M d, Y', strtotime($row['datePosted'])); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>By
                            <?php echo htmlspecialchars($row['posterName'] ?? 'Anonymous'); ?>
                        </span>
                    </div>
                </div>

                <div class="job-actions">
                    <a href="job_details.php?id=<?php echo $row['jobID']; ?>" class="btn-job btn-view">View Details</a>

                    <?php if ($_SESSION['role'] === 'STUDENT' || $_SESSION['role'] === 'ALUMNI'): ?>
                        <?php if (isset($row['applicationStatus']) && $row['applicationStatus']): ?>
                            <button class="btn-job btn-apply" disabled>Applied</button>
                        <?php else: ?>
                            <form action="apply_job.php" method="POST" style="display: contents;">
                                <input type="hidden" name="jobID" value="<?php echo $row['jobID']; ?>">
                                <button type="submit" class="btn-job btn-apply"
                                    onclick="return confirm('Submit application for this position?')">Apply Now</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Other Roles (Admin, CSO) cannot apply -->
                        <button class="btn-job btn-apply" style="opacity: 0.5; cursor: not-allowed;" title="Administrative role"
                            disabled>Apply Now</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div
            style="grid-column: 1/-1; text-align: center; padding: 4rem; background: white; border: 1px dashed var(--border-light); border-radius: 1rem;">
            <p style="color: var(--text-muted); font-size: 1.125rem;">No active job opportunities found. Check back
                later!</p>
        </div>
    <?php endif; ?>
</div>