<?php
/**
 * SHARED COMPONENT: MyEventTableUI
 * Displays the list of registered events.
 */
?>

<div class="events-table-container"
    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); overflow: hidden; box-shadow: var(--card-shadow);">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
        <thead>
            <tr style="background: var(--background-light); border-bottom: 1px solid var(--border-light);">
                <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Event Title</th>
                <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Date & Location</th>
                <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Registered On</th>
                <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Status</th>
                <th style="padding: 1rem 1.5rem; font-weight: 700; color: var(--text-dark);">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registrations && $registrations->num_rows > 0): ?>
                <?php while ($row = $registrations->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-light); transition: background 0.2s;"
                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                        <td style="padding: 1.25rem 1.5rem;">
                            <div style="font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4; max-width: 300px;">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 80)) . '...'; ?>
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1.5rem;">
                            <div style="color: var(--text-dark); margin-bottom: 0.25rem;">📅
                                <?php echo date('M d, Y', strtotime($row['date'])); ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.75rem;">📍
                                <?php echo htmlspecialchars($row['location']); ?>
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1.5rem; color: var(--text-muted);">
                            <?php echo date('M d, Y', strtotime($row['registrationDate'])); ?>
                        </td>
                        <td style="padding: 1.25rem 1.5rem;">
                            <span
                                style="font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 9999px; background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0;">
                                CONFIRMED
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1.5rem;">
                            <?php if ($row['eventStatus'] === 'OPEN'): ?>
                                <form action="" method="POST"
                                    onsubmit="return confirm('Are you sure you want to cancel your registration for this event?');">
                                    <input type="hidden" name="eventID" value="<?php echo $row['eventID']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit"
                                        style="background: none; border: none; color: #EF4444; font-weight: 600; cursor: pointer; font-size: 0.875rem; padding: 0;">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.75rem;">Event
                                    <?php echo $row['eventStatus']; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 4rem; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                        <p style="color: var(--text-muted); font-size: 1.125rem; margin-bottom: 1.5rem;">You haven't
                            registered for any events yet.</p>
                        <a href="events.php"
                            style="color: var(--primary-blue); text-decoration: none; font-weight: 700;">Browse Upcoming
                            Events &rarr;</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>