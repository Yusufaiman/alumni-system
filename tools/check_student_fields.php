<?php
require_once "config/db.php";
$result = $conn->query("SHOW COLUMNS FROM student");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}
file_put_contents("student_fields.txt", implode(", ", $fields));
