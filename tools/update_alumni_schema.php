<?php
require_once "config/db.php";

$sql = "ALTER TABLE alumni 
        ADD COLUMN faculty VARCHAR(100) AFTER userID,
        ADD COLUMN programme VARCHAR(100) AFTER faculty,
        ADD COLUMN yearsExperience INT DEFAULT 0 AFTER industry,
        ADD COLUMN phone VARCHAR(20) AFTER yearsExperience,
        ADD COLUMN linkedinURL VARCHAR(200) AFTER phone,
        ADD COLUMN website VARCHAR(200) AFTER linkedinURL,
        ADD COLUMN openMentorship ENUM('YES', 'NO') DEFAULT 'NO' AFTER website,
        ADD COLUMN openReferral ENUM('YES', 'NO') DEFAULT 'NO' AFTER openMentorship,
        ADD COLUMN bio TEXT AFTER openReferral,
        ADD COLUMN profilePhoto VARCHAR(255) AFTER bio";

if ($conn->query($sql)) {
    echo "Alumni table updated successfully.";
} else {
    echo "Error updating table: " . $conn->error;
}
