<?php
// functions.php – Helper functions dan komponen HTML bersama
if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Auth Helpers ────────────────────────────────────────────

function isLoggedIn(): bool {
    if (!empty($_SESSION['user_id'])) return true;

    // Auto-login via cookie
    if (!empty($_COOKIE['fs_user']) && !empty($_COOKIE['fs_token'])) {
        global $conn;
        $u = $_COOKIE['fs_user'];
        $t = $_COOKIE['fs_token'];
        $stmt = $conn->prepare("SELECT id, username, level, branch_id, password FROM users WHERE username=?");
        $stmt->bind_param('s', $u);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && hash('sha256', $row['password']) === $t) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['username']  = $row['username'];
            $_SESSION['level']     = $row['level'];
            $_SESSION['branch_id'] = $row['branch_id'];
            return true;
        }
    }
    return false;
}

function requireLogin(string $redirect = 'index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect?page=login");
        exit;
    }
}

function requireLevel(array $levels) {
    requireLogin();
    if (!in_array($_SESSION['level'] ?? '', $levels)) {
        header("Location: index.php?page=dashboard&err=access");
        exit;
    }
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? 0,
        'username'  => $_SESSION['username']  ?? '',
        'level'     => $_SESSION['level']     ?? 'admin_cadangan',
        'branch_id' => $_SESSION['branch_id'] ?? null,
    ];
}

function isOwner(): bool {
    return ($_SESSION['level'] ?? '') === 'owner';
}

function isAdmin(): bool {
    return in_array($_SESSION['level'] ?? '', ['owner', 'admin']);
}

function levelLabel(string $l): string {
    return match($l) {
        'owner'          => '👑 Owner',
        'admin'          => '🛡️ Admin',
        'admin_cadangan' => '🔵 Admin Cadangan',
        default          => $l,
    };
}

function levelBadgeClass(string $l): string {
    return match($l) {
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

    $navItems = [
        'dashboard'   => ['icon' => '🏠', 'label' => 'Dashboard',   'levels' => ['owner','admin','admin_cadangan']],
        'kasir'       => ['icon' => '🧾', 'label' => 'Kasir',        'levels' => ['owner','admin','admin_cadangan']],
        'stok'        => ['icon' => '📦', 'label' => 'Stok',         'levels' => ['owner','admin','admin_cadangan']],
        'produksi'    => ['icon' => '🛒', 'label' => 'Produksi',     'levels' => ['owner','admin','admin_cadangan']],
        'operasional' => ['icon' => '🔧', 'label' => 'Operasional',  'levels' => ['owner','admin']],
        'users'       => ['icon' => '👥', 'label' => 'Pengguna',     'levels' => ['owner','admin','admin_cadangan']],
    ];

    $navHtml = '';
    foreach ($navItems as $key => $item) {
        if (!in_array($user['level'], $item['levels'])) continue;
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
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <span class="logo-icon">🍢</span>
      <span class="logo-text">DapurKu</span>
    </div>
    <button class="sidebar-close" onclick="closeSidebar()" aria-label="Tutup sidebar">✕</button>
  </div>
  <nav class="sidebar-nav">
    {$navHtml}
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar">{$initial}</div>
      <div class="user-details">
        <div class="user-name">{$u}</div>
        <div class="user-level">{$lvl}</div>
      </div>
    </div>
    <a href="index.php?page=logout" class="logout-btn" title="Keluar">
      <span>⏻</span>
    </a>
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
<script src="assets/js/main.js"></script>
</body>
</html>
HTML;
}
