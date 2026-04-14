<?php
// db.php - File ini dibuat otomatis oleh install.php
// Atau isi manual sesuai konfigurasi server Anda

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_sales_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Redirect ke installer jika DB belum ada
    if (!file_exists(__DIR__ . '/install.php')) {
        die("❌ Koneksi database gagal. Hubungi administrator.");
    }
    header("Location: install.php");
    exit;
}

$conn->set_charset('utf8mb4');
