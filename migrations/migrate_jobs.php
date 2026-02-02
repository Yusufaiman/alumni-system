<?php
require_once "config/db.php";

// 1. Update postedByRole enum to include ALUMNI
$conn->query("ALTER TABLE jobposting MODIFY COLUMN postedByRole ENUM('CAREER_OFFICER', 'ALUMNI') DEFAULT 'ALUMNI'");

// 2. Add columns if they don't exist (safety check)
// (Already checked they exist except alumniID seems to be replaced by postedByID)

echo "Table jobposting updated successfully.";
