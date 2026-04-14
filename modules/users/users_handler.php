<?php
// modules/users/users_handler.php – CRUD Logic Users
require_once __DIR__ . '/../../functions.php';
requireLogin();
global $conn;
$u      = currentUser();
$action = $_POST['action'] ?? '';

// ── Ubah Level (Owner saja) ───────────────────────────────────
if ($action === 'change_level') {
    if (!isOwner()) {
        flashSet('error', 'Hanya Owner yang bisa mengubah level pengguna.');
        header('Location: index.php?page=users'); exit;
    }
    $id  = (int)$_POST['id'];
    $lvl = $_POST['level'] ?? '';
    if (in_array($lvl, ['owner','admin','admin_cadangan']) && $id !== $u['id']) {
        $stmt = $conn->prepare("UPDATE users SET level=? WHERE id=?");
        $stmt->bind_param('si', $lvl, $id);
        $stmt->execute();
        flashSet('success', "Level pengguna berhasil diubah menjadi " . levelLabel($lvl) . ".");
    }
    header('Location: index.php?page=users'); exit;
}

// ── Assign Cabang (Owner saja) ────────────────────────────────
if ($action === 'assign_branch') {
    if (!isOwner()) {
        flashSet('error', 'Hanya Owner yang bisa mengatur cabang.');
        header('Location: index.php?page=users'); exit;
    }
    $id  = (int)$_POST['id'];
    $bid = (int)$_POST['branch_id'];
    if ($id > 0 && $id !== $u['id']) {
        $bidVal = $bid > 0 ? $bid : null;
        $stmt = $conn->prepare("UPDATE users SET branch_id=? WHERE id=?");
        $stmt->bind_param('ii', $bidVal, $id);
        $stmt->execute();
        flashSet('success', "Cabang pengguna berhasil diperbarui.");
    }
    header('Location: index.php?page=users'); exit;
}

// ── Hapus Pengguna (Owner saja) ───────────────────────────────
if ($action === 'delete') {
    if (!isOwner()) {
        flashSet('error', 'Hanya Owner yang bisa menghapus pengguna.');
        header('Location: index.php?page=users'); exit;
    }
    $id = (int)$_POST['id'];
    if ($id > 0 && $id !== $u['id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
        flashSet('success', 'Pengguna berhasil dihapus.');
    } else {
        flashSet('error', 'Tidak dapat menghapus akun sendiri.');
    }
    header('Location: index.php?page=users'); exit;
}

header('Location: index.php?page=users');
exit;
