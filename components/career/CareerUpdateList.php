<?php
/**
 * REUSABLE COMPONENT: Career Update List
 * Displays a list of career updates with management actions.
 */
?>
<div class="career-updates-list">
    <h2 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 1.5rem;">My Career Updates</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="update-card"
                    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); padding: 1.5rem; box-shadow: var(--card-shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.25rem 0;">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                Published on
                                <?php echo date('d M Y', strtotime($row['createdDate'])); ?>
                            </span>
                        </div>
                        <span
                            style="font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 9999px; text-transform: uppercase; 
                            <?php echo $row['status'] === 'VISIBLE' ? 'background: #DCFCE7; color: #166534;' : 'background: #F3F4F6; color: #4B5563;'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </div>

                    <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;">
                        <?php
                        $content = htmlspecialchars($row['content']);
                        echo (strlen($content) > 200) ? substr($content, 0, 200) . '...' : $content;
                        ?>
                    </p>

                    <div style="display: flex; gap: 1rem; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="update_id" value="<?php echo $row['updateID']; ?>">
                            <input type="hidden" name="action_type" value="toggle_visibility">
                            <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                            <button type="submit"
                                style="background: none; border: none; font-weight: 600; font-size: 0.875rem; cursor: pointer; color: var(--primary-blue);">
                                <?php echo $row['status'] === 'VISIBLE' ? 'Hide Update' : 'Publish Update'; ?>
                            </button>
                        </form>

                        <form action="" method="POST" style="display: inline;"
                            onsubmit="return confirm('Are you sure you want to delete this career update?');">
                            <input type="hidden" name="update_id" value="<?php echo $row['updateID']; ?>">
                            <input type="hidden" name="action_type" value="delete">
                            <button type="submit"
                                style="background: none; border: none; font-weight: 600; font-size: 0.875rem; cursor: pointer; color: #EF4444;">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div
            style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 1rem; border: 1px dashed var(--border-light);">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚀</div>
            <p style="color: var(--text-muted); font-size: 1.125rem;">You have not shared any career updates yet.</p>
        </div>
    <?php endif; ?>
</div>