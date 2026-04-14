<?php
// modules/stok/stok_handler.php – CRUD Logic Stok
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;

$u      = currentUser();
$bid    = (int)($u['branch_id'] ?? 1);
$action = $_POST['action'] ?? '';
$today  = date('Y-m-d');

if ($action === 'save_pembukaan' || $action === 'save_penutupan') {
    $tipe    = ($action === 'save_pembukaan') ? 'pembukaan' : 'penutupan';
    $tanggal = $_POST['tanggal'] ?? $today;

    // Hapus record lama untuk hari + tipe yang sama dari user ini
    $stmt = $conn->prepare("DELETE FROM stock_records WHERE tanggal=? AND tipe=? AND user_id=?");
    $stmt->bind_param('ssi', $tanggal, $tipe, $u['id']);
    $stmt->execute();

    $produkData = $_POST['produk'] ?? [];
    $stmtI = $conn->prepare("INSERT INTO stock_records (user_id, branch_id, tanggal, tipe, produk, jumlah) VALUES (?,?,?,?,?,?)");
    foreach ($produkData as $pname => $jumlah) {
        $j = (int)$jumlah;
        if ($j >= 0) {
            $stmtI->bind_param('iisssi', $u['id'], $bid, $tanggal, $tipe, $pname, $j);
            $stmtI->execute();
        }
    }
    flashSet('success', "Stok " . ucfirst($tipe) . " berhasil disimpan!");
}

header('Location: index.php?page=stok');
exit;
