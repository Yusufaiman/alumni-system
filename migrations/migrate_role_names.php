<?php
require_once "config/db.php";
$conn->query("UPDATE user SET role = 'CAREER_SERVICE_OFFICER' WHERE role = 'CAREER_OFFICER'");
$conn->query("UPDATE jobposting SET postedByRole = 'CAREER_SERVICE_OFFICER' WHERE postedByRole = 'CAREER_OFFICER'");
echo "Roles migrated successfully.\n";
