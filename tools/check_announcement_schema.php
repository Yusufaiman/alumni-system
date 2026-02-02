<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE announcement");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'] . "\n";
    }
} else {
    echo "Table announcement does not exist.\n";
    // If it doesn't exist, I should create it based on requirements
    $sql = "CREATE TABLE announcement (
        announcementID INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        createdBy INT NOT NULL,
        createdRole ENUM('CAREER_SERVICE_OFFICER', 'ADMIN') NOT NULL,
        createdDate DATE NOT NULL,
        status ENUM('ACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE'
    )";
    if ($conn->query($sql)) {
        echo "Table announcement created successfully.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}
?>