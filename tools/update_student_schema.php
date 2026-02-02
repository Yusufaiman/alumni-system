<?php
require_once "config/db.php";

$sql = "ALTER TABLE student 
        ADD COLUMN faculty VARCHAR(100) AFTER programme,
        ADD COLUMN enrollmentStatus ENUM('ACTIVE', 'GRADUATED') DEFAULT 'ACTIVE' AFTER yearOfStudy,
        ADD COLUMN phone VARCHAR(20) AFTER studentEmail,
        ADD COLUMN linkedinURL VARCHAR(200) AFTER phone,
        ADD COLUMN careerInterest VARCHAR(100) AFTER linkedinURL,
        ADD COLUMN skills TEXT AFTER careerInterest,
        ADD COLUMN internshipAvailability ENUM('YES', 'NO') DEFAULT 'NO' AFTER skills,
        ADD COLUMN profilePhoto VARCHAR(255) AFTER internshipAvailability";

if ($conn->query($sql)) {
    echo "Student table updated successfully.";
} else {
    echo "Error updating table: " . $conn->error;
}
