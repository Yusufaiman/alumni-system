<?php
require_once "config/db.php";

// Add onboarded column to admin table if not exists
$res = $conn->query("SHOW COLUMNS FROM admin LIKE 'onboarded'");
if ($res->num_rows == 0) {
    echo "Adding 'onboarded' to admin table...\n";
    $conn->query("ALTER TABLE admin ADD COLUMN onboarded BOOLEAN DEFAULT FALSE");
} else {
    echo "'onboarded' already exists in admin table.\n";
}

// Ensure admin_system_settings is robust
$conn->query("CREATE TABLE IF NOT EXISTS admin_system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Ensure admin profile fields exist (contact, department, photo)
// Based on step 1: Contact Number, Department, Profile Photo.
// Let's check if these columns exist in `admin` table.
$cols = [];
$res = $conn->query("DESCRIBE admin");
while ($row = $res->fetch_assoc())
    $cols[] = $row['Field'];

if (!in_array('contact_number', $cols)) {
    $conn->query("ALTER TABLE admin ADD COLUMN contact_number VARCHAR(50)");
}
if (!in_array('department', $cols)) {
    $conn->query("ALTER TABLE admin ADD COLUMN department VARCHAR(100)");
}
if (!in_array('profile_photo', $cols)) {
    $conn->query("ALTER TABLE admin ADD COLUMN profile_photo VARCHAR(255)");
}
// Add full name if not in users (usually users has name, but prompt says "Full Name (required)" in Step 1.
// If users has name, we might update users.name or admin.full_name?
// Let's assume we update users.name OR add full_name to admin if decoupled.
// I'll check if `full_name` exists in admin or just use `users.name`.
if (!in_array('full_name', $cols)) {
    $conn->query("ALTER TABLE admin ADD COLUMN full_name VARCHAR(150)");
}

echo "Admin schema updated.\n";
?>