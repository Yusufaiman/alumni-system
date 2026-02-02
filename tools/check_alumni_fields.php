<?php
require_once "config/db.php";
$result = $conn->query("SHOW COLUMNS FROM alumni");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'] . " (" . $row['Type'] . ")";
}
file_put_contents("alumni_fields.txt", implode(", ", $fields));
