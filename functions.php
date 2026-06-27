<?php
// functions.php – Helper functions dan komponen HTML bersama
if (session_status() === PHP_SESSION_NONE) session_start();

// Inisialisasi user aktif dinamis untuk mendukung multi-account per tab
initActiveUser();

// Daftarkan callback untuk otomatis menyisipkan uid pada redirect Location header
header_register_callback(function() {
    if (isset($GLOBALS['active_user']['id'])) {
        $uid = $GLOBALS['active_user']['id'];
        foreach (headers_list() as $header) {
            if (stripos($header, 'Location:') === 0) {
                $url = trim(substr($header, 9));
                $isExternal = preg_match('/^(https?:|mailto:|tel:|#|javascript:|\/\/)/i', $url);
                if (!$isExternal && $url !== '') {
                    if (strpos($url, 'uid=') === false) {
                        $separator = (strpos($url, '?') === false) ? '?' : '&';
                        $url .= $separator . 'uid=' . $uid;
                        header_remove('Location');
                        header("Location: $url");
                        break;
                    }
                }
            }
        }
    }
});

// Aktifkan output buffering untuk menyisipkan ?uid=... otomatis ke semua link internal
ob_start('appendUidToLinks');

function initActiveUser() {
    // Cari uid dari parameter GET/POST saja – TIDAK dari Referer (mencegah kontaminasi lintas tab)
    $uid = isset($_GET['uid']) ? (int)$_GET['uid'] : (isset($_POST['uid']) ? (int)$_POST['uid'] : null);

    // Pastikan session accounts ada
    if (empty($_SESSION['accounts'])) {
        $_SESSION['accounts'] = [];
    }

    // Migrasi sesi lama tanpa token ke format baru (backwards compat)
    if (!empty($_SESSION['user_id']) && empty($_SESSION['accounts'][$_SESSION['user_id']])) {
        $_SESSION['accounts'][$_SESSION['user_id']] = [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'] ?? '',
            'level'     => $_SESSION['level'] ?? 'admin_cadangan',
            'branch_id' => $_SESSION['branch_id'] ?? null,
            'token'     => null, // token kosong – akan divalidasi secara longgar
        ];
    }

    $activeUser = null;

    if ($uid && isset($_SESSION['accounts'][$uid])) {
        // uid valid dari URL → validasi token ke DB untuk keamanan
        $candidate = $_SESSION['accounts'][$uid];
        if (!empty($candidate['token'])) {
            // Akun baru dengan token → validasi ke database
            $activeUser = validateSessionToken($uid, $candidate['token']);
            if ($activeUser) {
                // Perbarui data di session (sinkron dengan DB)
                $_SESSION['accounts'][$uid] = array_merge($candidate, $activeUser);
                $activeUser = $_SESSION['accounts'][$uid];
            } else {
                // Token tidak valid / expired → hapus akun dari sesi
                unset($_SESSION['accounts'][$uid]);
            }
        } else {
            // Akun lama tanpa token (migrasi) → percaya session saja
            $activeUser = $candidate;
        }
    } elseif ($uid === null && !empty($_SESSION['accounts'])) {
        // Tidak ada uid di URL → pakai akun pertama yang valid
        foreach ($_SESSION['accounts'] as $accId => $candidate) {
            if (!empty($candidate['token'])) {
                $validated = validateSessionToken($accId, $candidate['token']);
                if ($validated) {
                    $activeUser = array_merge($candidate, $validated);
                    $_SESSION['accounts'][$accId] = $activeUser;
                    break;
                } else {
                    unset($_SESSION['accounts'][$accId]);
                }
            } else {
                $activeUser = $candidate;
                break;
            }
        }
    }

    // Auto-login via cookie jika belum ada user aktif sama sekali
    if (!$activeUser && !empty($_COOKIE['fs_user']) && !empty($_COOKIE['fs_token'])) {
        global $conn;
        if (isset($conn)) {
            $u = $_COOKIE['fs_user'];
            $t = $_COOKIE['fs_token'];
            $stmt = $conn->prepare("SELECT id, username, level, branch_id, password FROM users WHERE username=?");
            if ($stmt) {
                $stmt->bind_param('s', $u);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row && hash('sha256', $row['password']) === $t) {
                    // Auto-login via cookie: buat token baru di DB
                    $newToken = bin2hex(random_bytes(32));
                    $del = $conn->prepare("DELETE FROM user_sessions WHERE user_id=?");
                    $del->bind_param('i', $row['id']);
                    $del->execute();
                    $ins = $conn->prepare("INSERT INTO user_sessions (user_id, token) VALUES (?, ?)");
                    $ins->bind_param('is', $row['id'], $newToken);
                    $ins->execute();

                    $activeUser = [
                        'id'        => $row['id'],
                        'username'  => $row['username'],
                        'level'     => $row['level'],
                        'branch_id' => $row['branch_id'],
                        'token'     => $newToken,
                    ];
                    $_SESSION['accounts'][$row['id']] = $activeUser;
                }
            }
        }
    }

    $GLOBALS['active_user'] = $activeUser;
}

// Validasi token akun ke database
function validateSessionToken(int $userId, string $token): ?array {
    global $conn;
    if (!isset($conn)) return null;

    $stmt = $conn->prepare(
        "SELECT u.id, u.username, u.level, u.branch_id 
         FROM user_sessions s 
         JOIN users u ON u.id = s.user_id 
         WHERE s.user_id=? AND s.token=?
         LIMIT 1"
    );
    if (!$stmt) return null;
    $stmt->bind_param('is', $userId, $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return null;

    // Perbarui last_active
    $upd = $conn->prepare("UPDATE user_sessions SET last_active=NOW() WHERE user_id=? AND token=?");
    $upd->bind_param('is', $userId, $token);
    $upd->execute();

    return $row;
}

// Callback output buffering: sisipkan uid ke href/action DAN hidden input di setiap <form>
function appendUidToLinks(string $buffer): string {
    if (!isset($GLOBALS['active_user']['id'])) return $buffer;

    // Skip if buffer looks like JSON or is empty
    $trimmed = trim($buffer);
    if ($trimmed === '') return $buffer;
    if (($trimmed[0] === '{' && substr($trimmed, -1) === '}') || 
        ($trimmed[0] === '[' && substr($trimmed, -1) === ']')) {
        return $buffer;
    }

    // Skip processing if Content-Type header is JSON, JavaScript, or CSS
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            if (stripos($header, 'json') !== false || 
                stripos($header, 'javascript') !== false || 
                stripos($header, 'css') !== false) {
                return $buffer;
            }
        }
    }

    $uid = (int)$GLOBALS['active_user']['id'];

    // 1. Tambahkan ?uid=X ke semua href dan action internal
    $buffer = preg_replace_callback(
        '/\b(href|action)=(["\'])([^"\']*)(\2)/i',
        function($m) use ($uid) {
            $attr  = $m[1];
            $quote = $m[2];
            $url   = $m[3];
            $isExternal = preg_match('/^(https?:|mailto:|tel:|#|javascript:|\/\/)/i', $url);
            if (!$isExternal && $url !== '' && strpos($url, 'uid=') === false) {
                $sep = strpos($url, '?') === false ? '?' : '&';
                $url .= $sep . 'uid=' . $uid;
            }
            return $attr . '=' . $quote . $url . $quote;
        },
        $buffer
    );

    // 2. Sisipkan hidden <input name="uid"> ke dalam setiap <form> yang belum punya
    $buffer = preg_replace_callback(
        '/<form(\s[^>]*)?(>)/i',
        function($m) use ($uid) {
            // Jangan tambah jika form sudah punya uid (misal dari action URL)
            // Tetap tambahkan hidden field agar POST request juga membawa uid
            return '<form' . ($m[1] ?? '') . $m[2]
                 . '<input type="hidden" name="uid" value="' . $uid . '">';
        },
        $buffer
    );

    return $buffer;
}

// ─── Auth Helpers ────────────────────────────────────────────

function isLoggedIn(): bool {
    return !empty($GLOBALS['active_user']);
}

function requireLogin(string $redirect = 'index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect?page=login");
        exit;
    }
}

function requireLevel(array $levels) {
    requireLogin();
    $lvl = $GLOBALS['active_user']['level'] ?? '';
    if ($lvl === 'superadmin') return; // Superadmin bypasses level checks
    if (!in_array($lvl, $levels)) {
        header("Location: index.php?page=dashboard&err=access");
        exit;
    }
}

function currentUser(): array {
    return [
        'id'        => $GLOBALS['active_user']['id']        ?? 0,
        'username'  => $GLOBALS['active_user']['username']  ?? '',
        'level'     => $GLOBALS['active_user']['level']     ?? 'admin_cadangan',
        'branch_id' => $GLOBALS['active_user']['branch_id'] ?? null,
    ];
}

function isSuperadmin(): bool {
    return ($GLOBALS['active_user']['level'] ?? '') === 'superadmin';
}

function isOwner(): bool {
    return in_array($GLOBALS['active_user']['level'] ?? '', ['superadmin', 'owner']); // Treat superadmin as owner for legacy checks
}

function isAdmin(): bool {
    return in_array($GLOBALS['active_user']['level'] ?? '', ['superadmin', 'owner', 'admin']);
}

function hasPermission(string $feature, string $action = 'read'): bool {
    if (isSuperadmin()) return true; // Superadmin has all permissions

    global $conn;
    $uid = $GLOBALS['active_user']['id'] ?? 0;
    if (!$uid) return false;

    // Check specific permission
    $col = match($action) {
        'create' => 'can_create',
        'update' => 'can_update',
        'delete' => 'can_delete',
        default  => 'can_read'
    };

    $stmt = $conn->prepare("SELECT $col FROM user_permissions WHERE user_id=? AND feature=?");
    $stmt->bind_param('is', $uid, $feature);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return !empty($res[$col]);
}

// ─── Activity Log ────────────────────────────────────────────

function logActivity(string $action, string $module, ?int $targetId = null, string $description = ''): void {
    global $conn;
    if (!isset($conn)) return;

    $user = currentUser();
    $userId = $user['id'] ?: null;
    $username = $user['username'] ?: 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (user_id, username, action, module, target_id, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssiss', $userId, $username, $action, $module, $targetId, $description, $ip);
    $stmt->execute();
}

function levelLabel(string $l): string {
    return match($l) {
        'superadmin'     => '⭐ Superadmin',
        'owner'          => '👑 Owner',
        'admin'          => '🛡️ Admin',
        'admin_cadangan' => '🔵 Admin Cadangan',
        default          => $l,
    };
}

function levelBadgeClass(string $l): string {
    return match($l) {
        'superadmin'     => 'badge-superadmin',
        'owner'          => 'badge-owner',
        'admin'          => 'badge-admin',
        'admin_cadangan' => 'badge-cadangan',
        default          => 'badge-default',
    };
}

// ─── Format Helpers ──────────────────────────────────────────

function rupiah(int|float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function safePost(string $k, string $default = ''): string {
    return isset($_POST[$k]) ? htmlspecialchars(trim($_POST[$k])) : $default;
}

function safeGet(string $k, string $default = ''): string {
    return isset($_GET[$k]) ? htmlspecialchars(trim($_GET[$k])) : $default;
}

// ─── Flash ───────────────────────────────────────────────────

function flashSet(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flashGet(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icon = $f['type'] === 'success' ? '✅' : '❌';
    $cls  = $f['type'] === 'success' ? 'flash-success' : 'flash-error';
    return "<div class='flash $cls'>$icon " . htmlspecialchars($f['msg']) . "</div>";
}

// ─── HTML Components ─────────────────────────────────────────

function renderHeader(string $pageTitle = 'DapurKu POS', string $active = ''): void {
    $user  = currentUser();
    $lvl   = levelLabel($user['level']);
    $flash = flashGet();
    $u     = $user['username'];
    $initial = strtoupper(mb_substr($u, 0, 1));

    // Menghasilkan HTML untuk akun-akun lain yang sedang aktif login
    $otherAccountsHtml = '';
    if (!empty($_SESSION['accounts']) && count($_SESSION['accounts']) > 1) {
        $otherAccountsHtml .= '<div class="other-accounts-list" style="margin-top: 12px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px; width: 100%;">';
        $otherAccountsHtml .= '<div style="font-size: 0.7rem; color: var(--text3); margin-bottom: 6px; font-weight: 600; letter-spacing: 0.5px;">AKUN LAIN:</div>';
        foreach ($_SESSION['accounts'] as $accId => $acc) {
            if ($accId == $user['id']) continue;
            $accInitial = strtoupper(mb_substr($acc['username'], 0, 1));
            $otherAccountsHtml .= "
            <a href='index.php?page=dashboard&uid={$accId}' class='other-account-item' style='display: flex; align-items: center; gap: 8px; text-decoration: none; padding: 6px 8px; border-radius: 6px; transition: all 0.2s; margin-bottom: 4px; color: var(--text2);' onmouseover=\"this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text1)';\" onmouseout=\"this.style.background='transparent'; this.style.color='var(--text2)';\">
                <div class='user-avatar-mini' style='width: 22px; height: 22px; border-radius: 50%; background: var(--accent); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; flex-shrink: 0;'>{$accInitial}</div>
                <div style='font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>{$acc['username']} <span style='font-size: 0.65rem; color: var(--text3);'>(" . levelLabel($acc['level']) . ")</span></div>
            </a>";
        }
        $otherAccountsHtml .= '</div>';
    }

    $addAccountHtml = "
    <div style='margin-top: 10px; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.08); width: 100%; font-size: 0.8rem;'>
        <a href='index.php?page=login&add_account=1' style='color: var(--accent); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 500; transition: opacity 0.2s;' onmouseover=\"this.style.opacity='0.8'\" onmouseout=\"this.style.opacity='1'\">
            <span>➕</span> Tambah Akun Baru
        </a>
    </div>";

    $navItems = [
        'dashboard'    => ['icon' => '🏠', 'label' => 'Dashboard',      'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'kasir'        => ['icon' => '🧾', 'label' => 'Kasir',           'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'produk'       => ['icon' => '🍔', 'label' => 'Produk',          'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'stok'         => ['icon' => '📦', 'label' => 'Stok',            'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'produksi'     => ['icon' => '🛒', 'label' => 'Produksi',        'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'operasional'  => ['icon' => '🔧', 'label' => 'Operasional',     'levels' => ['superadmin','owner','admin']],
        'home_manager'  => ['icon' => '📝', 'label' => 'Kelola Konten',   'levels' => ['superadmin','owner','admin']],
        'users'         => ['icon' => '👥', 'label' => 'Pengguna',        'levels' => ['superadmin','owner','admin','admin_cadangan']],
        'akses'         => ['icon' => '🔑', 'label' => 'Kelola Akses',    'levels' => ['superadmin']],
        'activity_log'  => ['icon' => '📊', 'label' => 'Log Aktivitas',   'levels' => ['superadmin','owner']],
    ];

    $navHtml = '';
    foreach ($navItems as $key => $item) {
        if (!in_array($user['level'], $item['levels'])) continue;
        
        // Cek permission Read untuk menyembunyikan/memunculkan modul dari sidebar
        // (Dashboard, Users, dan Akses tetap dibiarkan sesuai level)
        if (in_array($key, ['produk', 'kasir', 'stok', 'produksi', 'operasional'])) {
            if (!hasPermission($key, 'read')) continue;
        }

        $cls  = ($active === $key) ? 'active' : '';
        $navHtml .= "<a href='index.php?page=$key' class='nav-item $cls'>
            <span class='nav-icon'>{$item['icon']}</span>
            <span class='nav-label'>{$item['label']}</span>
        </a>";
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="DapurKu POS - Sistem Penjualan Makanan Digital">
<title>{$pageTitle} – DapurKu POS</title>
<script>document.documentElement.setAttribute('data-theme', localStorage.getItem('fs_theme') || 'dark');</script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css?v=<?= time() ?>">
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="index.php?page=home" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
      <div class="sidebar-logo">
        <span class="logo-icon">🍢</span>
        <span class="logo-text">DapurKu</span>
      </div>
    </a>
    <button class="sidebar-close" onclick="closeSidebar()" aria-label="Tutup sidebar">✕</button>
  </div>
  <nav class="sidebar-nav">
    {$navHtml}
  </nav>
  <div class="sidebar-footer" style="display: flex; flex-direction: column; align-items: stretch; height: auto; padding: 15px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.12);">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
      <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
        <div class="user-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; flex-shrink: 0;">{$initial}</div>
        <div class="user-details" style="overflow: hidden;">
          <div class="user-name" style="font-weight: 600; font-size: 0.9rem; color: var(--text1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{$u}</div>
          <div class="user-level" style="font-size: 0.72rem; color: var(--text3);">{$lvl}</div>
        </div>
      </div>
      <a href="index.php?page=logout" class="logout-btn" title="Keluar" style="text-decoration: none; font-size: 1.25rem; color: var(--text3); transition: color 0.2s;" onmouseover="this.style.color='var(--error)'" onmouseout="this.style.color='var(--text3)'">
        <span>⏻</span>
      </a>
    </div>
    {$otherAccountsHtml}
    {$addAccountHtml}
  </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrap" id="mainWrap">
  <header class="topbar">
    <button class="hamburger" onclick="openSidebar()" aria-label="Buka menu">
      <span></span><span></span><span></span>
    </button>
    <h1 class="topbar-title">{$pageTitle}</h1>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" id="themeIcon" aria-label="Toggle Theme">☀️</button>
      <span class="level-badge level-{$user['level']}">{$user['level']}</span>
      <span class="topbar-username">{$u}</span>
    </div>
  </header>
  <main class="main-content">
    {$flash}
HTML;
}

function renderFooter(): void {
    echo <<<HTML
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
HTML;
}
