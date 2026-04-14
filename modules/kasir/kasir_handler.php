<?php
// modules/kasir/kasir_handler.php – CRUD Logic Kasir
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;

$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=kasir');
    exit;
}

$action = $_POST['action'] ?? '';

// ── Simpan Order ─────────────────────────────────────────────
if ($action === 'save_order') {
    $produk   = $_POST['produk']     ?? [];
    $harga    = $_POST['harga']      ?? [];
    $kat      = $_POST['kategori']   ?? 'offline';
    $ket      = trim($_POST['keterangan'] ?? '');
    $branchId = (int)($u['branch_id'] ?? 1);
    $total    = 0;

    $items = [];
    foreach ($produk as $i => $p) {
        $h = (int)($harga[$i] ?? 0);
        if (!empty($p) && $h > 0) {
            $items[] = ['produk' => $p, 'harga' => $h];
            $total += $h;
        }
    }

    if (!empty($items) && $total > 0) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, branch_id, kategori, total, keterangan) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iisds', $u['id'], $branchId, $kat, $total, $ket);
        $stmt->execute();
        $orderId = $conn->insert_id;

        $stmtI = $conn->prepare("INSERT INTO order_items (order_id, produk, harga) VALUES (?,?,?)");
        foreach ($items as $it) {
            $stmtI->bind_param('isd', $orderId, $it['produk'], $it['harga']);
            $stmtI->execute();
        }
        flashSet('success', "✅ Order berhasil disimpan! Total: " . rupiah($total));
    } else {
        flashSet('error', "❌ Tambahkan minimal 1 produk dengan harga!");
    }
    header('Location: index.php?page=kasir');
    exit;
}

// ── Hapus Order (owner & admin saja) ─────────────────────────
if ($action === 'delete_order') {
    if (!isAdmin()) {
        flashSet('error', 'Anda tidak memiliki izin untuk menghapus order.');
        header('Location: index.php?page=kasir');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM orders WHERE id=$id");
        flashSet('success', 'Order berhasil dihapus.');
    }
    header('Location: index.php?page=kasir');
    exit;
}

header('Location: index.php?page=kasir');
exit;
