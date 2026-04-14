<?php
// index.php – Main Router (Clean Version)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$page = $_GET['page'] ?? 'dashboard';

// ── Logout ──────────────────────────────────────────────────
if ($page === 'logout') {
    session_destroy();
    setcookie('fs_user', '', time() - 3600, '/');
    setcookie('fs_token', '', time() - 3600, '/');
    header("Location: index.php?page=login");
    exit;
}

// ── Router ──────────────────────────────────────────────────
$routes = [
    'login'           => 'modules/auth/login.php',
    'register'        => 'modules/auth/register.php',
    'dashboard'       => 'modules/dashboard/dashboard.php',
    'kasir'           => 'modules/kasir/kasir.php',
    'stok'            => 'modules/stok/stok.php',
    'produksi'        => 'modules/produksi/produksi.php',
    'produksi_detail' => 'modules/produksi/produksi_detail.php',
    'operasional'     => 'modules/operasional/operasional.php',
    'users'           => 'modules/users/users.php',
];

if (array_key_exists($page, $routes)) {
    // Auth check for non-auth pages
    if (!in_array($page, ['login', 'register'])) {
        requireLogin();
    }
    
    $file = __DIR__ . '/' . $routes[$page];
    if (file_exists($file)) {
        require_once $file;
    } else {
        die("404 - Module '$page' not found.");
    }
} else {
    // Default fallback
    requireLogin();
    require_once __DIR__ . '/modules/dashboard/dashboard.php';
}
