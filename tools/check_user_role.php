<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE user");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'role') {
        file_put_contents("role_type.txt", $row['Type']);
    }
}
