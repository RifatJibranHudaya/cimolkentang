<?php
// modules/kelola_akses/akses_handler.php – AJAX Handler for Permissions
require_once __DIR__ . '/../../functions.php';
requireLogin();

if (!isSuperadmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Akses ditolak. Hanya Superadmin.']);
    exit;
}

header('Content-Type: application/json');
global $conn;

$action = $_POST['action'] ?? '';

// ── Get Permissions for a user ────────────────────────────────
if ($action === 'get_permissions') {
    $uid = (int)($_POST['id'] ?? 0);
    $perms = $conn->query("SELECT * FROM user_permissions WHERE user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $perms]);
    exit;
}

// ── Toggle single permission ──────────────────────────────────
if ($action === 'toggle_perm') {
    $uid     = (int)($_POST['user_id'] ?? 0);
    $feature = trim($_POST['feature'] ?? '');
    $perm    = trim($_POST['perm'] ?? '');     // create, read, update, delete
    $value   = (int)($_POST['value'] ?? 0);    // 0 or 1

    if (!$uid || !$feature || !in_array($perm, ['can_create','can_read','can_update','can_delete'])) {
        echo json_encode(['success' => false, 'msg' => 'Parameter tidak valid.']);
        exit;
    }

    // Check if row exists
    $check = $conn->prepare("SELECT id FROM user_permissions WHERE user_id=? AND feature=?");
    $check->bind_param('is', $uid, $feature);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $stmt = $conn->prepare("UPDATE user_permissions SET $perm=? WHERE user_id=? AND feature=?");
        $stmt->bind_param('iis', $value, $uid, $feature);
        $stmt->execute();
    } else {
        // Insert new row with this perm
        $c = ($perm === 'can_create') ? $value : 0;
        $r = ($perm === 'can_read')   ? $value : 0;
        $u = ($perm === 'can_update') ? $value : 0;
        $d = ($perm === 'can_delete') ? $value : 0;
        $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, feature, can_create, can_read, can_update, can_delete) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isiiii', $uid, $feature, $c, $r, $u, $d);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'msg' => 'Hak akses diperbarui.']);
    exit;
}

// ── Bulk toggle (all features for a user) ─────────────────────
if ($action === 'toggle_bulk') {
    $uid  = (int)($_POST['user_id'] ?? 0);
    $perm = trim($_POST['perm'] ?? '');
    $value = (int)($_POST['value'] ?? 0);

    if (!$uid || !in_array($perm, ['can_create','can_read','can_update','can_delete'])) {
        echo json_encode(['success' => false, 'msg' => 'Parameter tidak valid.']);
        exit;
    }

    $features = ['produk', 'kasir', 'stok', 'produksi', 'operasional'];
    foreach ($features as $feat) {
        $check = $conn->prepare("SELECT id FROM user_permissions WHERE user_id=? AND feature=?");
        $check->bind_param('is', $uid, $feat);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE user_permissions SET $perm=? WHERE user_id=? AND feature=?");
            $stmt->bind_param('iis', $value, $uid, $feat);
            $stmt->execute();
        } else {
            $c = ($perm === 'can_create') ? $value : 0;
            $r = ($perm === 'can_read')   ? $value : 0;
            $u = ($perm === 'can_update') ? $value : 0;
            $d = ($perm === 'can_delete') ? $value : 0;
            $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, feature, can_create, can_read, can_update, can_delete) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isiiii', $uid, $feat, $c, $r, $u, $d);
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true, 'msg' => "Semua akses $perm diperbarui."]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenali.']);
