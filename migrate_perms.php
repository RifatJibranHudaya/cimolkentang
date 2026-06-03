<?php
require 'c:/laragon/www/food-app/db.php';

// Change enum in users table to include superadmin
$conn->query("ALTER TABLE users MODIFY COLUMN level ENUM('superadmin', 'owner', 'admin', 'admin_cadangan') NOT NULL DEFAULT 'admin'");

// Create user_permissions table
$conn->query("CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feature VARCHAR(50) NOT NULL,
    can_create TINYINT(1) DEFAULT 0,
    can_read TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY user_feature (user_id, feature)
)");

echo "Migration done.";
