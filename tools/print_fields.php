<?php
require_once "config/db.php";
$table = 'jobposting';
$desc = $conn->query("DESCRIBE $table");
$fields = [];
while ($d = $desc->fetch_assoc()) {
    $fields[] = $d['Field'] . " (" . $d['Type'] . ")";
}
echo implode(", ", $fields);
