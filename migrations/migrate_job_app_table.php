<?php
require_once "config/db.php";

$sql = "DROP TABLE IF EXISTS job_application;
CREATE TABLE job_application (
    applicationID INT AUTO_INCREMENT PRIMARY KEY,
    jobID INT NOT NULL,
    applicantID INT NOT NULL,
    applicantRole ENUM('STUDENT', 'ALUMNI') NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    appliedDate DATE NOT NULL,
    FOREIGN KEY (jobID) REFERENCES jobposting(jobID) ON DELETE CASCADE,
    FOREIGN KEY (applicantID) REFERENCES user(userID) ON DELETE CASCADE
)";

if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "job_application table migrated successfully.";
} else {
    echo "Error migrating table: " . $conn->error;
}
