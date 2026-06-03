<?php
// modules/home/home_handler.php – Handle CRUD operations for home content
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../functions.php';

$user = currentUser();

// Only owner and admin (not admin_cadangan) can manage home content
if (!in_array($user['level'], ['owner', 'admin'])) {
    die(json_encode(['success' => false, 'msg' => 'Akses ditolak!']));
}

global $conn;
$action = $_POST['action'] ?? '';

// ─── CREATE ──────────────────────────────────────────────────
if ($action === 'create') {
    $section    = safePost('section', 'feature');
    $title      = safePost('title');
    $subtitle   = safePost('subtitle');
    $content    = isset($_POST['content']) ? trim($_POST['content']) : '';
    $icon       = safePost('icon', '⭐');
    $order_idx  = (int)($_POST['order_index'] ?? 0);
    
    if (empty($title)) {
        die(json_encode(['success' => false, 'msg' => 'Judul tidak boleh kosong!']));
    }
    
    $stmt = $conn->prepare(
        "INSERT INTO home_content (section, title, subtitle, content, icon, order_index, created_by, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param('ssssiii', $section, $title, $subtitle, $content, $icon, $order_idx, $user['id']);
    
    if ($stmt->execute()) {
        flashSet('success', 'Konten berhasil ditambahkan!');
        die(json_encode(['success' => true, 'msg' => 'Konten berhasil ditambahkan!']));
    } else {
        die(json_encode(['success' => false, 'msg' => 'Gagal menambahkan konten: ' . $stmt->error]));
    }
}

// ─── READ ──────────────────────────────────────────────────
if ($action === 'get_by_id') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        die(json_encode(['success' => false, 'msg' => 'ID tidak valid!']));
    }
    
    $stmt = $conn->prepare("SELECT * FROM home_content WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        die(json_encode(['success' => true, 'data' => $result]));
    } else {
        die(json_encode(['success' => false, 'msg' => 'Konten tidak ditemukan!']));
    }
}

// ─── UPDATE ──────────────────────────────────────────────────
if ($action === 'update') {
    $id         = (int)($_POST['id'] ?? 0);
    $section    = safePost('section', 'feature');
    $title      = safePost('title');
    $subtitle   = safePost('subtitle');
    $content    = isset($_POST['content']) ? trim($_POST['content']) : '';
    $icon       = safePost('icon', '⭐');
    $order_idx  = (int)($_POST['order_index'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0) {
        die(json_encode(['success' => false, 'msg' => 'ID tidak valid!']));
    }
    
    if (empty($title)) {
        die(json_encode(['success' => false, 'msg' => 'Judul tidak boleh kosong!']));
    }
    
    $stmt = $conn->prepare(
        "UPDATE home_content 
         SET section = ?, title = ?, subtitle = ?, content = ?, icon = ?, 
             order_index = ?, is_active = ?, updated_by = ?, updated_at = NOW()
         WHERE id = ?"
    );
    $stmt->bind_param('sssssiiiii', $section, $title, $subtitle, $content, $icon, $order_idx, $is_active, $user['id'], $id);
    
    if ($stmt->execute()) {
        flashSet('success', 'Konten berhasil diperbarui!');
        die(json_encode(['success' => true, 'msg' => 'Konten berhasil diperbarui!']));
    } else {
        die(json_encode(['success' => false, 'msg' => 'Gagal memperbarui konten: ' . $stmt->error]));
    }
}

// ─── DELETE ──────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        die(json_encode(['success' => false, 'msg' => 'ID tidak valid!']));
    }
    
    // Prevent deleting hero or footer (but allow editing)
    $stmt = $conn->prepare("SELECT section FROM home_content WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        die(json_encode(['success' => false, 'msg' => 'Konten tidak ditemukan!']));
    }
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE home_content SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ii', $user['id'], $id);
    
    if ($stmt->execute()) {
        flashSet('success', 'Konten berhasil dihapus!');
        die(json_encode(['success' => true, 'msg' => 'Konten berhasil dihapus!']));
    } else {
        die(json_encode(['success' => false, 'msg' => 'Gagal menghapus konten: ' . $stmt->error]));
    }
}

// ─── GET ALL ──────────────────────────────────────────────────
if ($action === 'get_all') {
    $section = safeGet('section', '');
    
    if ($section) {
        $stmt = $conn->prepare("SELECT * FROM home_content WHERE section = ? ORDER BY order_index ASC");
        $stmt->bind_param('s', $section);
    } else {
        $stmt = $conn->prepare("SELECT * FROM home_content ORDER BY section, order_index ASC");
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    die(json_encode(['success' => true, 'data' => $result]));
}

die(json_encode(['success' => false, 'msg' => 'Action tidak valid!']));
