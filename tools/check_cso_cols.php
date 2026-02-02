<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE career_service_officer");
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'];
}
file_put_contents("cso_cols.txt", implode("\n", $out));
echo "Done\n";
