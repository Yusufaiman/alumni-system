<?php
require_once "config/db.php";

$tables = ['event', 'eventregistration', 'notification'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'] . " Key:" . $row['Key'] . " Extra:" . $row['Extra'] . "\n";
        }
    } else {
        echo "Table $table does not exist.\n";
    }
    echo "\n";
}
?>