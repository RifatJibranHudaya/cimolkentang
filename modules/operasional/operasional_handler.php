<?php
// modules/operasional/operasional_handler.php – CRUD Logic Operasional
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin']);   // admin_cadangan tidak bisa akses
global $conn;
$u = currentUser();
$action = $_POST['action'] ?? '';

// ── Simpan Alat Baru ─────────────────────────────────────────
if ($action === 'save') {
    $nama   = trim($_POST['nama_alat']     ?? '');
    $harga  = (int)($_POST['harga']        ?? 0);
    $tmpat  = trim($_POST['tempat_beli']   ?? '');
    $merk   = trim($_POST['merk']          ?? '');
    $period = (int)($_POST['periode_ganti']?? 0);
    $tgl    = $_POST['tanggal_beli']       ?? date('Y-m-d');
    $ket    = trim($_POST['keterangan']    ?? '');

    if ($nama) {
        $stmt = $conn->prepare("INSERT INTO operational (user_id,nama_alat,harga,tempat_beli,merk,periode_ganti,tanggal_beli,keterangan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isdssiss', $u['id'], $nama, $harga, $tmpat, $merk, $period, $tgl, $ket);
        $stmt->execute();
        flashSet('success', "✅ Alat '{$nama}' berhasil dicatat!");
    } else {
        flashSet('error', "❌ Nama alat wajib diisi!");
    }
    header('Location: index.php?page=operasional');
    exit;
}

// ── Update Alat ───────────────────────────────────────────────
if ($action === 'update') {
    $id     = (int)($_POST['id']           ?? 0);
    $nama   = trim($_POST['nama_alat']     ?? '');
    $harga  = (int)($_POST['harga']        ?? 0);
    $tmpat  = trim($_POST['tempat_beli']   ?? '');
    $merk   = trim($_POST['merk']          ?? '');
    $period = (int)($_POST['periode_ganti']?? 0);
    $tgl    = $_POST['tanggal_beli']       ?? date('Y-m-d');
    $ket    = trim($_POST['keterangan']    ?? '');

    if ($id > 0 && $nama) {
        $stmt = $conn->prepare("UPDATE operational SET nama_alat=?,harga=?,tempat_beli=?,merk=?,periode_ganti=?,tanggal_beli=?,keterangan=? WHERE id=?");
        $stmt->bind_param('sdssissi', $nama, $harga, $tmpat, $merk, $period, $tgl, $ket, $id);
        $stmt->execute();
        flashSet('success', "✅ Data alat berhasil diperbarui!");
    } else {
        flashSet('error', "❌ Nama alat wajib diisi!");
    }
    header('Location: index.php?page=operasional');
    exit;
}

// ── Hapus Alat ────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM operational WHERE id=$id");
        flashSet('success', "Alat berhasil dihapus.");
    }
    header('Location: index.php?page=operasional');
    exit;
}

header('Location: index.php?page=operasional');
exit;
