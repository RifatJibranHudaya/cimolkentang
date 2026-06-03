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
    if (in_array($lvl, ['superadmin','owner','admin','admin_cadangan']) && $id !== $u['id']) {
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

// ── Tambah Pengguna (Owner saja) ────────────────────────────────
if ($action === 'add_user') {
    if (!isOwner()) {
        flashSet('error', 'Hanya Owner yang bisa menambah karyawan.');
        header('Location: index.php?page=users'); exit;
    }
    $usr = trim($_POST['username'] ?? '');
    $eml = trim($_POST['email'] ?? '');
    $phn = trim($_POST['phone'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $lvl = $_POST['level'] ?? 'admin_cadangan';
    $bid = (int)($_POST['branch_id'] ?? 0);
    $bidVal = $bid > 0 ? $bid : null;

    if ($usr && $eml && $pwd) {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, level, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssi', $usr, $eml, $phn, $hash, $lvl, $bidVal);
            $stmt->execute();
            flashSet('success', 'Karyawan baru berhasil ditambahkan.');
        } catch (Exception $e) {
            flashSet('error', 'Gagal menambah karyawan. Username/Email mungkin sudah terdaftar.');
        }
    } else {
        flashSet('error', 'Mohon lengkapi username, email, dan password.');
    }
    header('Location: index.php?page=users'); exit;
}



// ── Permissions (Superadmin saja) ───────────────────────────────
if ($action === 'get_permissions') {
    $uid = (int)$_POST['id'];
    $perms = $conn->query("SELECT * FROM user_permissions WHERE user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $perms]);
    exit;
}

if ($action === 'save_permissions') {
    if (!isSuperadmin()) {
        echo json_encode(['success' => false, 'msg' => 'Hanya Superadmin yang bisa mengatur hak akses khusus.']);
        exit;
    }
    
    $uid = (int)$_POST['id'];
    $perms = json_decode($_POST['permissions'], true);
    
    if ($uid && is_array($perms)) {
        // Hapus permission lama
        $conn->query("DELETE FROM user_permissions WHERE user_id=$uid");
        
        // Insert permission baru
        $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, feature, can_create, can_read, can_update, can_delete) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($perms as $feat => $acts) {
            $c = !empty($acts['create']) ? 1 : 0;
            $r = !empty($acts['read']) ? 1 : 0;
            $u = !empty($acts['update']) ? 1 : 0;
            $d = !empty($acts['delete']) ? 1 : 0;
            
            // Jika ada aksi apapun, simpan
            if ($c || $r || $u || $d) {
                $stmt->bind_param('isiiii', $uid, $feat, $c, $r, $u, $d);
                $stmt->execute();
            }
        }
        echo json_encode(['success' => true, 'msg' => 'Hak akses berhasil disimpan.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Data tidak valid.']);
    }
    exit;
}

header('Location: index.php?page=users');
exit;
