<?php
/**
 * REUSABLE COMPONENT: Career Update Card for Student View
 * Displays a single career update shared by an alumni.
 */
?>
<div class="career-update-card"
    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); padding: 1.5rem; box-shadow: var(--card-shadow); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.25rem 0;">
                <?php echo htmlspecialchars($row['title']); ?>
            </h3>
            <span style="font-size: 0.875rem; color: var(--text-muted);">
                Shared by <span style="font-weight: 600; color: var(--primary-blue);">
                    <?php echo htmlspecialchars($row['alumniName']); ?>
                </span>
                •
                <?php echo date('d M Y', strtotime($row['createdDate'])); ?>
            </span>
        </div>

        <?php
        $postedDate = strtotime($row['createdDate']);
        $isNew = (time() - $postedDate) < (7 * 24 * 60 * 60); // New if < 7 days old
        if ($isNew): ?>
            <span
                style="font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 9999px; text-transform: uppercase; background: #E0F2FE; color: #0369A1;">
                NEW
            </span>
        <?php endif; ?>
    </div>

    <div class="update-content" style="color: var(--text-dark); line-height: 1.6; margin-bottom: 1.5rem;">
        <?php
        $fullContent = htmlspecialchars($row['content']);
        $shortContent = (strlen($fullContent) > 250) ? substr($fullContent, 0, 250) . '...' : $fullContent;
        ?>
        <p class="content-text">
            <?php echo $shortContent; ?>
        </p>

        <?php if (strlen($fullContent) > 250): ?>
            <button onclick="toggleContent(this, '<?php echo base64_encode($fullContent); ?>')"
                style="background: none; border: none; color: var(--primary-blue); font-weight: 600; cursor: pointer; padding: 0; margin-top: 0.5rem;">
                Read more &darr;
            </button>
        <?php endif; ?>
    </div>

    <div
        style="border-top: 1px solid var(--border-light); padding-top: 1rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
        Career Update
    </div>
</div>