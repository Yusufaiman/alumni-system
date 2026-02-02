<?php
/**
 * SHARED COMPONENT: MyApplicationsTable
 * Displays the list of job applications for the logged-in user.
 */
?>

<div class="applications-container"
    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); overflow: hidden; box-shadow: var(--card-shadow); margin-top: 2rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--background-light); border-bottom: 1px solid var(--border-light);">
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Job Title</th>
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Company</th>
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Location</th>
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Applied Date</th>
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Status</th>
                    <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($applications && $applications->num_rows > 0): ?>
                    <?php while ($row = $applications->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--border-light); transition: background 0.2s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                            <td style="padding: 1rem 1.5rem; font-weight: 600; color: var(--text-dark);">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($row['company']); ?>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                <span style="display: flex; align-items: center; gap: 0.375rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?php echo htmlspecialchars($row['location'] ?: 'Not specified'); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                <?php echo date('M d, Y', strtotime($row['appliedDate'])); ?>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <?php
                                $status = $row['status'];
                                $badgeStyle = "";
                                if ($status === 'PENDING') {
                                    $badgeStyle = "background: #FEF9C3; color: #713F12; border: 1px solid #FEF08A;";
                                } elseif ($status === 'APPROVED') {
                                    $badgeStyle = "background: #DCFCE7; color: #14532D; border: 1px solid #BBF7D0;";
                                } elseif ($status === 'REJECTED') {
                                    $badgeStyle = "background: #FEE2E2; color: #7F1D1D; border: 1px solid #FECACA;";
                                }
                                ?>
                                <span
                                    style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; <?php echo $badgeStyle; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <a href="job_details.php?id=<?php echo $row['jobID']; ?>"
                                    style="color: #4F46E5; text-decoration: none; font-weight: 600;">View Job</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                            You haven't applied for any jobs yet.
                            <br>
                            <a href="jobs.php"
                                style="color: #4F46E5; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 1rem;">Browse
                                Opportunities</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>