<?php
/**
 * SHARED UI COMPONENT: PostJobForm
 * Displays the job posting form.
 * Logic is handled by PostJobAction.php
 */
?>


<div class="form-container">
    <?php if (isset($errorMsg) && $errorMsg): ?>
        <div class="error-banner">
            <?php echo $errorMsg; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="title">Job Title *</label>
            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Software Engineer Intern"
                required>
        </div>

        <div class="form-group">
            <label for="company">Company Name *</label>
            <input type="text" name="company" id="company" class="form-control" placeholder="e.g. Tech Solutions Inc."
                required>
        </div>

        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" name="location" id="location" class="form-control"
                placeholder="e.g. Kuala Lumpur / Remote">
        </div>

        <div class="form-group">
            <label for="jobType">Job Type</label>
            <select name="jobType" id="jobType" class="form-control">
                <option value="FULL_TIME">Full Time</option>
                <option value="PART_TIME">Part Time</option>
                <option value="INTERNSHIP">Internship</option>
                <option value="CONTRACT">Contract</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Job Description *</label>
            <textarea name="description" id="description" class="form-control"
                placeholder="Describe the role, responsibilities, and team..." required></textarea>
        </div>

        <div class="form-group">
            <label for="requirements">Requirements</label>
            <textarea name="requirements" id="requirements" class="form-control"
                placeholder="List key skills, experience, or qualifications needed..."></textarea>
        </div>

        <button type="submit" name="post_job" class="btn-submit-job">Post Job Opportunity</button>
    </form>
</div>