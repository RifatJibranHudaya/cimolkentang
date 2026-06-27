<?php
// modules/activity_log/activity_log.php – Halaman Log Aktivitas
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../db.php';

// Only superadmin and owner can access
requireLevel(['superadmin', 'owner']);

global $conn;
$user = currentUser();

// ── Filters ──────────────────────────────────────────────────
$filterUser   = isset($_GET['filter_user'])   ? (int)$_GET['filter_user']           : 0;
$filterAction = isset($_GET['filter_action']) ? trim($_GET['filter_action'])         : '';
$filterModule = isset($_GET['filter_module']) ? trim($_GET['filter_module'])         : '';
$filterDate   = isset($_GET['filter_date'])   ? trim($_GET['filter_date'])           : '';
$page         = isset($_GET['p'])             ? max(1, (int)$_GET['p'])              : 1;
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if ($filterUser > 0) {
    $where[] = 'l.user_id = ?';
    $params[] = $filterUser;
    $types .= 'i';
}
if ($filterAction !== '') {
    $where[] = 'l.action = ?';
    $params[] = $filterAction;
    $types .= 's';
}
if ($filterModule !== '') {
    $where[] = 'l.module = ?';
    $params[] = $filterModule;
    $types .= 's';
}
if ($filterDate !== '') {
    $where[] = 'DATE(l.created_at) = ?';
    $params[] = $filterDate;
    $types .= 's';
}

$whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSQL = "SELECT COUNT(*) as total FROM activity_logs l $whereSQL";
$countStmt = $conn->prepare($countSQL);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalLogs / $perPage));

// Fetch logs
$logSQL = "SELECT l.* FROM activity_logs l $whereSQL ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$logStmt = $conn->prepare($logSQL);
$logTypes = $types . 'ii';
$logParams = array_merge($params, [$perPage, $offset]);
$logStmt->bind_param($logTypes, ...$logParams);
$logStmt->execute();
$logs = $logStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all users for filter dropdown
$usersRes = $conn->query("SELECT id, username, level FROM users ORDER BY username");
$allUsers = $usersRes->fetch_all(MYSQLI_ASSOC);

// Fetch distinct actions and modules for filter
$actionsRes = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$allActions = $actionsRes->fetch_all(MYSQLI_ASSOC);
$modulesRes = $conn->query("SELECT DISTINCT module FROM activity_logs ORDER BY module");
$allModules = $modulesRes->fetch_all(MYSQLI_ASSOC);

// ── Online Users (based on user_sessions.last_active) ────────
$onlineSQL = "SELECT u.id, u.username, u.level, s.last_active, s.created_at as session_start
              FROM user_sessions s
              JOIN users u ON u.id = s.user_id
              ORDER BY s.last_active DESC";
$onlineRes = $conn->query($onlineSQL);
$onlineUsers = $onlineRes ? $onlineRes->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$todayLogins = $conn->query("SELECT COUNT(*) as c FROM activity_logs WHERE action='login' AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$todayActions = $conn->query("SELECT COUNT(*) as c FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];

renderHeader('Log Aktivitas', 'activity_log');
?>
<link rel="stylesheet" href="modules/activity_log/activity_log.css">

<div class="log-container">

    <!-- Header -->
    <div class="log-header">
        <div>
            <h2>📊 Log Aktivitas Pengguna</h2>
            <p style="font-size:.84rem;color:var(--text3);margin:4px 0 0">Pantau semua aktivitas login, logout, dan perubahan data</p>
        </div>
        <div class="log-stats">
            <span class="log-stat">📝 <?= number_format($totalLogs) ?> Total Log</span>
            <span class="log-stat" style="color:#22c55e;border-color:rgba(34,197,94,.3)">🔑 <?= $todayLogins ?> Login Hari Ini</span>
            <span class="log-stat" style="color:#3b82f6;border-color:rgba(59,130,246,.3)">⚡ <?= $todayActions ?> Aksi Hari Ini</span>
        </div>
    </div>

    <!-- Online Users Panel -->
    <div class="online-panel">
        <div class="online-panel-title">
            <span>🟢</span> Status Pengguna Aktif
            <span style="font-size:.78rem;color:var(--text3);font-weight:400;margin-left:8px">(<?= count($onlineUsers) ?> sesi aktif)</span>
        </div>
        <?php if (empty($onlineUsers)): ?>
            <p style="color:var(--text3);font-size:.88rem;text-align:center;padding:20px">Tidak ada pengguna yang sedang aktif</p>
        <?php else: ?>
            <div class="online-users-grid">
                <?php foreach ($onlineUsers as $ou):
                    $lastActive = strtotime($ou['last_active']);
                    $now = time();
                    $diff = $now - $lastActive;
                    $initial = strtoupper(mb_substr($ou['username'], 0, 1));
                    $avCls = match($ou['level']) {
                        'superadmin' => 'av-superadmin',
                        'owner' => 'av-owner',
                        'admin' => 'av-admin',
                        default => 'av-cadangan'
                    };

                    if ($diff < 300) { // < 5 menit
                        $statusCls = 'active';
                        $statusLabel = 'Online';
                        $statusBadge = 'status-online';
                    } elseif ($diff < 1800) { // < 30 menit
                        $statusCls = 'idle';
                        $statusLabel = 'Idle';
                        $statusBadge = 'status-idle';
                    } else {
                        $statusCls = 'inactive';
                        $statusLabel = 'Offline';
                        $statusBadge = 'status-offline';
                    }

                    // Time ago
                    if ($diff < 60) $timeAgo = 'baru saja';
                    elseif ($diff < 3600) $timeAgo = floor($diff / 60) . ' menit lalu';
                    elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . ' jam lalu';
                    else $timeAgo = floor($diff / 86400) . ' hari lalu';
                ?>
                <div class="online-user-card">
                    <div class="online-avatar <?= $avCls ?>">
                        <?= $initial ?>
                        <span class="online-indicator <?= $statusCls ?>"></span>
                    </div>
                    <div class="online-user-info">
                        <div class="online-user-name"><?= htmlspecialchars($ou['username']) ?></div>
                        <div class="online-user-meta">Terakhir aktif: <?= $timeAgo ?></div>
                    </div>
                    <span class="online-user-status <?= $statusBadge ?>"><?= $statusLabel ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php" class="log-filters" id="logFilterForm">
        <input type="hidden" name="page" value="activity_log">
        <?php if (isset($_GET['uid'])): ?>
            <input type="hidden" name="uid" value="<?= (int)$_GET['uid'] ?>">
        <?php endif; ?>

        <div class="filter-group">
            <label>Pengguna</label>
            <select name="filter_user">
                <option value="0">Semua Pengguna</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?> (<?= $u['level'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Aksi</label>
            <select name="filter_action">
                <option value="">Semua Aksi</option>
                <?php foreach ($allActions as $a): ?>
                    <option value="<?= htmlspecialchars($a['action']) ?>" <?= $filterAction === $a['action'] ? 'selected' : '' ?>>
                        <?= ucfirst($a['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Modul</label>
            <select name="filter_module">
                <option value="">Semua Modul</option>
                <?php foreach ($allModules as $m): ?>
                    <option value="<?= htmlspecialchars($m['module']) ?>" <?= $filterModule === $m['module'] ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $m['module'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Tanggal</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
        </div>

        <button type="submit" class="btn-filter">🔍 Filter</button>
        <a href="index.php?page=activity_log" class="btn-reset">↺ Reset</a>
    </form>

    <!-- Log Table -->
    <?php if (empty($logs)): ?>
        <div class="log-table-wrap">
            <div class="log-empty">
                <div class="log-empty-icon">📋</div>
                <p>Belum ada log aktivitas yang tercatat</p>
                <p style="font-size:.85rem;margin-top:8px;opacity:.7">Log akan muncul saat pengguna melakukan login, logout, atau perubahan data</p>
            </div>
        </div>
    <?php else: ?>
        <div class="log-table-wrap">
            <table class="log-table">
                <thead>
                    <tr>
                        <th style="width:5%">#</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Modul</th>
                        <th>Deskripsi</th>
                        <th>IP Address</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    foreach ($logs as $log):
                        $initial = strtoupper(mb_substr($log['username'], 0, 1));

                        // Action badge
                        $actionIcon = match($log['action']) {
                            'login'  => '🔑',
                            'logout' => '🚪',
                            'create' => '➕',
                            'update' => '✏️',
                            'delete' => '🗑️',
                            default  => '📌'
                        };
                        $actionCls = 'action-' . $log['action'];

                        // Module icon
                        $moduleIcon = match($log['module']) {
                            'auth'          => '🔐',
                            'home_content'  => '📝',
                            'produk'        => '🍔',
                            'kasir'         => '🧾',
                            'stok'          => '📦',
                            'produksi'      => '🛒',
                            'operasional'   => '🔧',
                            default         => '📁'
                        };

                        // Time
                        $time = strtotime($log['created_at']);
                        $timeFormatted = date('d/m/Y H:i:s', $time);
                        $diff = time() - $time;
                        if ($diff < 60) $timeAgo = 'baru saja';
                        elseif ($diff < 3600) $timeAgo = floor($diff / 60) . ' menit lalu';
                        elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . ' jam lalu';
                        else $timeAgo = floor($diff / 86400) . ' hari lalu';
                    ?>
                    <tr>
                        <td style="color:var(--text3);font-size:.82rem"><?= $no++ ?></td>
                        <td>
                            <div class="log-user">
                                <div class="log-avatar"><?= $initial ?></div>
                                <span class="log-username"><?= htmlspecialchars($log['username']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="action-badge <?= $actionCls ?>">
                                <?= $actionIcon ?> <?= ucfirst($log['action']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="module-badge">
                                <?= $moduleIcon ?> <?= ucfirst(str_replace('_', ' ', $log['module'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="log-description" title="<?= htmlspecialchars($log['description'] ?? '-') ?>">
                                <?= htmlspecialchars($log['description'] ?? '-') ?>
                            </span>
                        </td>
                        <td>
                            <span class="log-ip"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></span>
                        </td>
                        <td>
                            <div class="log-time"><?= $timeFormatted ?></div>
                            <span class="log-time-relative"><?= $timeAgo ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="log-pagination">
            <div class="pagination-info">
                Menampilkan <?= $offset + 1 ?> – <?= min($offset + $perPage, $totalLogs) ?> dari <?= number_format($totalLogs) ?> log
            </div>
            <div class="pagination-links">
                <?php
                // Build base URL for pagination
                $baseParams = ['page' => 'activity_log'];
                if ($filterUser > 0) $baseParams['filter_user'] = $filterUser;
                if ($filterAction !== '') $baseParams['filter_action'] = $filterAction;
                if ($filterModule !== '') $baseParams['filter_module'] = $filterModule;
                if ($filterDate !== '') $baseParams['filter_date'] = $filterDate;
                if (isset($_GET['uid'])) $baseParams['uid'] = (int)$_GET['uid'];

                function pagUrl($p, $baseParams) {
                    $baseParams['p'] = $p;
                    return 'index.php?' . http_build_query($baseParams);
                }

                // Previous
                if ($page > 1): ?>
                    <a href="<?= pagUrl($page - 1, $baseParams) ?>">‹</a>
                <?php else: ?>
                    <span class="disabled">‹</span>
                <?php endif;

                // Page numbers
                $startP = max(1, $page - 2);
                $endP = min($totalPages, $page + 2);

                if ($startP > 1): ?>
                    <a href="<?= pagUrl(1, $baseParams) ?>">1</a>
                    <?php if ($startP > 2): ?><span class="disabled">…</span><?php endif;
                endif;

                for ($i = $startP; $i <= $endP; $i++):
                    if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= pagUrl($i, $baseParams) ?>"><?= $i ?></a>
                    <?php endif;
                endfor;

                if ($endP < $totalPages):
                    if ($endP < $totalPages - 1): ?><span class="disabled">…</span><?php endif; ?>
                    <a href="<?= pagUrl($totalPages, $baseParams) ?>"><?= $totalPages ?></a>
                <?php endif;

                // Next
                if ($page < $totalPages): ?>
                    <a href="<?= pagUrl($page + 1, $baseParams) ?>">›</a>
                <?php else: ?>
                    <span class="disabled">›</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php renderFooter(); ?>
