<?php
// db.php – dibuat otomatis oleh install.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_sales_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header('Location: install.php');
    exit;
}
$conn->set_charset('utf8mb4');
