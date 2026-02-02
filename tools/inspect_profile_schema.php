<?php
require_once "config/db.php";

function describeTable($conn, $table)
{
    echo "\n--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Error: Could not describe table $table\n";
    }
}

describeTable($conn, "user");
describeTable($conn, "student");
