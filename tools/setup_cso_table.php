<?php
require_once "config/db.php";
$conn->query("CREATE TABLE IF NOT EXISTS career_service_officer (
    csoID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    staffID VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    createdDate DATE,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
)");
echo "Table checked/created.";
