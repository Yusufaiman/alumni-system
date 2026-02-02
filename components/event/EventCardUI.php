<?php
/**
 * SHARED COMPONENT: EventCardUI
 * Displays an event card for the browsing list.
 */
?>

<div class="event-card"
    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); overflow: hidden; box-shadow: var(--card-shadow); transition: transform 0.2s;">
    <div style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <h3 style="font-size: 1.25rem; color: var(--text-dark); font-weight: 700; margin: 0;">
                <?php echo htmlspecialchars($event['title']); ?>
            </h3>
            <span
                style="font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 9999px; 
                <?php echo $event['status'] === 'OPEN' ? 'background: #DCFCE7; color: #166534;' : 'background: #FEE2E2; color: #991B1B;'; ?>">
                <?php echo $event['status']; ?>
            </span>
        </div>

        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.5;">
            <?php echo htmlspecialchars($event['description']); ?>
        </p>

        <div
            style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                📅 <span>
                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                📍 <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($event['location']); ?>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                👥 <span>Cap:
                    <?php echo $event['capacity']; ?>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                🎟️ <span>Left:
                    <?php echo max(0, $event['capacity'] - $event['confirmedCount']); ?>
                </span>
            </div>
        </div>

        <?php
        $remaining = $event['capacity'] - $event['confirmedCount'];
        $isRegistered = $event['userRegistrationStatus'] === 'CONFIRMED';
        ?>

        <?php if ($isRegistered): ?>
            <button disabled
                style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; font-weight: 700; cursor: not-allowed;">
                ✓ Registered
            </button>
        <?php elseif ($event['status'] !== 'OPEN' || $remaining <= 0): ?>
            <button disabled
                style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-light); background: #F3F4F6; color: #9CA3AF; font-weight: 700; cursor: not-allowed;">
                Event Full / Closed
            </button>
        <?php else: ?>
            <form action="" method="POST">
                <input type="hidden" name="eventID" value="<?php echo $event['eventID']; ?>">
                <input type="hidden" name="action" value="register">
                <button type="submit"
                    style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: none; background: var(--primary-blue); color: white; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                    Register Now
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>