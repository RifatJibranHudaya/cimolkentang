<?php
// ============================================================
// index.php – Main Router DapurKu POS
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$page = $_GET['page'] ?? 'home';

// ── Logout ────────────────────────────────────────────────────
if ($page === 'logout') {
    global $conn;
    $uid = isset($_GET['uid']) ? (int)$_GET['uid'] : null;

    // Tentukan uid yang akan di-logout
    $targetUid = null;
    if ($uid && isset($_SESSION['accounts'][$uid])) {
        $targetUid = $uid;
    } elseif (isset($GLOBALS['active_user']['id'])) {
        $targetUid = (int)$GLOBALS['active_user']['id'];
    }

    if ($targetUid) {
        // Hapus token dari DB agar tidak bisa dipakai lagi
        if (isset($conn)) {
            $del = $conn->prepare("DELETE FROM user_sessions WHERE user_id=?");
            $del->bind_param('i', $targetUid);
            $del->execute();
        }
        unset($_SESSION['accounts'][$targetUid]);
    }

    if (empty($_SESSION['accounts'])) {
        session_destroy();
        setcookie('fs_user',  '', time() - 3600, '/');
        setcookie('fs_token', '', time() - 3600, '/');
        header('Location: index.php?page=login');
    } else {
        // Masih ada akun lain yang aktif login
        $nextUser = reset($_SESSION['accounts']);
        header('Location: index.php?page=dashboard&uid=' . $nextUser['id']);
    }
    exit;
}

// ── Public Pages (no auth required) ──────────────────────────
if ($page === 'home')     { require __DIR__ . '/home.php'; exit; }
if ($page === 'login')    { require __DIR__ . '/modules/auth/login.php'; exit; }
if ($page === 'register') { require __DIR__ . '/modules/auth/register.php'; exit; }

// ── Auth required pages ───────────────────────────────────────
requireLogin();

switch ($page) {
    case 'dashboard':        require __DIR__ . '/modules/dashboard/dashboard.php'; break;
    case 'home_manager':     require __DIR__ . '/modules/home/home_manager.php'; break;
    case 'kasir':            require __DIR__ . '/modules/kasir/kasir.php';         break;
    case 'stok':             require __DIR__ . '/modules/stok/stok.php';           break;
    case 'produksi':         require __DIR__ . '/modules/produksi/produksi.php';   break;
    case 'produksi_detail':  require __DIR__ . '/modules/produksi/produksi_detail.php'; break;
    case 'operasional':      require __DIR__ . '/modules/operasional/operasional.php'; break;
    case 'users':            require __DIR__ . '/modules/users/users.php';         break;
    case 'akses':            require __DIR__ . '/modules/kelola_akses/kelola_akses.php'; break;
    case 'produk':           require __DIR__ . '/modules/produk/produk.php';       break;
    default:                 require __DIR__ . '/modules/dashboard/dashboard.php'; break;
}
