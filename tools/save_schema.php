<?php
require_once "config/db.php";
$desc = $conn->query("DESCRIBE jobposting");
$output = "";
while ($d = $desc->fetch_assoc()) {
    $output .= implode(" | ", $d) . "\n";
}
file_put_contents("job_schema.txt", $output);
echo "Schema written to job_schema.txt";
