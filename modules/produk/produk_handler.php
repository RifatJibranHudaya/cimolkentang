<?php
// modules/produk/produk_handler.php – CRUD Logic Produk
require_once __DIR__ . '/../../functions.php';
requireLogin();
global $conn;
$u = currentUser();
$action = $_POST['action'] ?? '';

// Security check based on action
$permRequired = match($action) {
    'save', 'branch_add' => 'create',
    'update', 'toggle', 'branch_edit' => 'update',
    'delete', 'branch_delete' => 'delete',
    default => 'read'
};

if (!isOwner() && !hasPermission('produk', $permRequired)) {
    flashSet('error', "Anda tidak memiliki hak $permRequired untuk Produk.");
    header('Location: index.php?page=produk');
    exit;
}

if ($action === 'save') {
    $nama  = trim($_POST['nama']          ?? '');
    $emoji = trim($_POST['emoji']         ?? '🍡');
    $harga = (int)($_POST['harga_default']?? 0);
    $desc  = trim($_POST['deskripsi']     ?? '');
    $urut  = (int)($_POST['urutan']       ?? 0);

    if ($nama) {
        $stmt = $conn->prepare("INSERT INTO products (nama,emoji,harga_default,deskripsi,urutan) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssdsi', $nama, $emoji, $harga, $desc, $urut);
        $stmt->execute();
        flashSet('success', "✅ Produk '$nama' berhasil ditambahkan!");
    } else {
        flashSet('error', '❌ Nama produk wajib diisi!');
    }
    header('Location: index.php?page=produk'); exit;
}

if ($action === 'update') {
    $id    = (int)($_POST['id']           ?? 0);
    $nama  = trim($_POST['nama']          ?? '');
    $emoji = trim($_POST['emoji']         ?? '🍡');
    $harga = (int)($_POST['harga_default']?? 0);
    $desc  = trim($_POST['deskripsi']     ?? '');
    $urut  = (int)($_POST['urutan']       ?? 0);
    $aktif = isset($_POST['is_active']) ? 1 : 0;

    if ($id && $nama) {
        $stmt = $conn->prepare("UPDATE products SET nama=?,emoji=?,harga_default=?,deskripsi=?,urutan=?,is_active=? WHERE id=?");
        $stmt->bind_param('ssdsiis', $nama, $emoji, $harga, $desc, $urut, $aktif, $id);
        $stmt->execute();
        flashSet('success', "✅ Produk berhasil diperbarui!");
    }
    header('Location: index.php?page=produk'); exit;
}

if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $conn->query("UPDATE products SET is_active = 1 - is_active WHERE id=$id");
        flashSet('success', 'Status produk diperbarui.');
    }
    header('Location: index.php?page=produk'); exit;
}

if ($action === 'delete') {
    if (!isOwner() && !hasPermission('produk', 'delete')) { flashSet('error','Hanya Owner/Superadmin yang bisa menghapus produk.'); header('Location: index.php?page=produk'); exit; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $conn->query("DELETE FROM products WHERE id=$id");
        flashSet('success', 'Produk berhasil dihapus.');
    }
    header('Location: index.php?page=produk'); exit;
}

// ── CRUD Cabang ─────────────────────────────────────────────
if ($action === 'branch_add') {
    $nama = trim($_POST['nama_cabang'] ?? '');
    $almt = trim($_POST['alamat'] ?? '');
    if ($nama) {
        $stmt = $conn->prepare("INSERT INTO branches (nama_cabang, alamat) VALUES (?, ?)");
        $stmt->bind_param('ss', $nama, $almt);
        $stmt->execute();
        flashSet('success', "Cabang '$nama' berhasil ditambahkan.");
    }
    header('Location: index.php?page=produk'); exit;
}

if ($action === 'branch_edit') {
    $id   = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_cabang'] ?? '');
    $almt = trim($_POST['alamat'] ?? '');
    $map  = trim($_POST['map_url'] ?? '');
    
    if ($id && $nama) {
        $stmt = $conn->prepare("UPDATE branches SET nama_cabang=?, alamat=?, map_url=? WHERE id=?");
        $stmt->bind_param('sssi', $nama, $almt, $map, $id);
        $stmt->execute();
        flashSet('success', "Cabang berhasil diperbarui.");
    }
    header('Location: index.php?page=produk'); exit;
}

if ($action === 'branch_delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM branches WHERE id=$id");
        flashSet('success', 'Cabang berhasil dihapus.');
    }
    header('Location: index.php?page=produk'); exit;
}

header('Location: index.php?page=produk'); exit;
