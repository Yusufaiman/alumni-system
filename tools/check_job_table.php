<?php
require_once "config/db.php";
$table = 'jobposting';
echo "Table: $table\n";
$desc = $conn->query("DESCRIBE $table");
while ($d = $desc->fetch_assoc()) {
    echo "  - {$d['Field']} ({$d['Type']})\n";
}
echo "\n";
