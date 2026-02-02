<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE jobposting");
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'];
}
file_put_contents("job_cols.txt", implode("\n", $out));
echo "Done\n";
