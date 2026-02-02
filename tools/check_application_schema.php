<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE job_application");
$schema = "";
while ($row = $res->fetch_assoc()) {
    $schema .= $row['Field'] . " (" . $row['Type'] . "), ";
}
file_put_contents("job_app_schema.txt", $schema);
