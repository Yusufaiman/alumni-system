<?php
require_once "config/db.php";

// Add delivery_type if not exists
try {
    $conn->query("ALTER TABLE admin_notification ADD COLUMN delivery_type ENUM('IN-APP','EMAIL','BOTH') DEFAULT 'IN-APP'");
} catch (Exception $e) { /* ignore if exists */
}

// Modify status to include DRAFT and SENT if not present
try {
    $conn->query("ALTER TABLE admin_notification MODIFY COLUMN status ENUM('DRAFT','SENT','ARCHIVED') DEFAULT 'DRAFT'");
} catch (Exception $e) { /* ignore */
}

echo "Schema updated.";
?>