<?php
/**
 * REUSABLE COMPONENT: Career Update Form
 * Used in share_career_updates.php for alumni.
 */
?>
<div class="career-update-form-card"
    style="background: white; border-radius: 1rem; border: 1px solid var(--border-light); padding: 2rem; box-shadow: var(--card-shadow); margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 0.5rem;">New Career Update</h2>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Share your latest achievements, job changes, or
        professional advice.</p>

    <form action="" method="POST">
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="title"
                style="display: block; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; font-size: 0.875rem;">Title
                *</label>
            <input type="text" name="title" id="title" maxlength="150"
                placeholder="e.g. Promoted to Senior Developer at TechCorp" required
                style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border-light); font-size: 1rem; font-family: inherit;">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="status"
                style="display: block; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; font-size: 0.875rem;">Visibility
                Status</label>
            <select name="status" id="status"
                style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border-light); font-size: 1rem; font-family: inherit; background-color: white;">
                <option value="VISIBLE">Visible (Students can see this)</option>
                <option value="HIDDEN">Hidden (Private draft)</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="content"
                style="display: block; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; font-size: 0.875rem;">Content
                *</label>
            <textarea name="content" id="content" rows="5"
                placeholder="Share your experience, tips, or details about your new role..." required
                style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border-light); font-size: 1rem; font-family: inherit; resize: vertical;"></textarea>
        </div>

        <button type="submit" name="create_update"
            style="background: var(--primary-blue); color: white; border: none; padding: 1rem 2rem; border-radius: 0.5rem; font-weight: 700; width: 100%; cursor: pointer; transition: 0.2s;">
            Publish Update
        </button>
    </form>
</div>