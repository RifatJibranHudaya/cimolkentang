<?php
// modules/produksi/produksi_handler.php – CRUD Logic Produksi
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;
$u   = currentUser();
$bid = (int)($u['branch_id'] ?? 1);
$action = $_POST['action'] ?? '';

// ── Simpan Baru ──────────────────────────────────────────────
if ($action === 'save') {
    $nama   = trim($_POST['nama_item']  ?? '');
    $harga  = (int)($_POST['harga']     ?? 0);
    $supp   = trim($_POST['supplier']   ?? '');
    $tempat = trim($_POST['tempat']     ?? '');
    $tgl    = $_POST['tanggal']         ?? date('Y-m-d');
    $ket    = trim($_POST['keterangan'] ?? '');

    if ($nama && $harga > 0) {
        $stmt = $conn->prepare("INSERT INTO production (user_id, branch_id, nama_item, harga, supplier, tempat, tanggal, keterangan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iiisssss', $u['id'], $bid, $nama, $harga, $supp, $tempat, $tgl, $ket);
        $stmt->execute();
        flashSet('success', "✅ Catatan belanja berhasil disimpan!");
    } else {
        flashSet('error', "❌ Nama item dan harga wajib diisi!");
    }
    header('Location: index.php?page=produksi');
    exit;
}

// ── Edit / Update ─────────────────────────────────────────────
if ($action === 'update') {
    $id     = (int)($_POST['id']         ?? 0);
    $nama   = trim($_POST['nama_item']   ?? '');
    $harga  = (int)($_POST['harga']      ?? 0);
    $supp   = trim($_POST['supplier']    ?? '');
    $tempat = trim($_POST['tempat']      ?? '');
    $tgl    = $_POST['tanggal']          ?? date('Y-m-d');
    $ket    = trim($_POST['keterangan']  ?? '');
    $now    = date('Y-m-d H:i:s');

    if ($id > 0 && $nama && $harga > 0) {
        $stmt = $conn->prepare("UPDATE production SET nama_item=?, harga=?, supplier=?, tempat=?, tanggal=?, keterangan=?, edited_by=?, edited_at=? WHERE id=?");
        $stmt->bind_param('sissssisi', $nama, $harga, $supp, $tempat, $tgl, $ket, $u['id'], $now, $id);
        $stmt->execute();
        flashSet('success', "✅ Data berhasil diperbarui! (Diedit oleh: {$u['username']})");
    } else {
        flashSet('error', "❌ Nama item dan harga wajib diisi!");
    }
    header('Location: index.php?page=produksi');
    exit;
}

// ── Hapus ─────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        // Owner bisa hapus semua, admin/cadangan hanya punyanya sendiri
        $where = isOwner() ? "id=$id" : "id=$id AND user_id={$u['id']}";
        $conn->query("DELETE FROM production WHERE $where");
        flashSet('success', "Data berhasil dihapus.");
    }
    header('Location: index.php?page=produksi');
    exit;
}

header('Location: index.php?page=produksi');
exit;
