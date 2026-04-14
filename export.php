<?php
// export.php – CSV Export Handler

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

requireLogin();
requireLevel(['owner', 'admin']);

$type   = $_GET['type']   ?? '';
$filter = $_GET['filter'] ?? 'month';
$tempat = $_GET['tempat'] ?? '';
$alat   = $_GET['alat']   ?? '';

// Date ranges
$today = date('Y-m-d');
$week  = date('Y-m-d', strtotime('-7 days'));
$month = date('Y-m-01');

function getDateWhere(string $filter, string $col = 'created_at'): string {
    global $today, $week, $month;
    return match($filter) {
        'today' => "DATE($col) = '$today'",
        'week'  => "DATE($col) >= '$week'",
        default => "DATE($col) >= '$month'",
    };
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dapurku_'.$type.'_'.date('Ymd_His').'.csv"');

$out = fopen('php://output', 'w');
// BOM for Excel UTF-8
fwrite($out, "\xEF\xBB\xBF");

switch ($type) {
    // ── ORDERS ──────────────────────────────────────────────
    case 'orders':
        fputcsv($out, ['ID', 'Tanggal', 'Waktu', 'Kasir', 'Produk', 'Kategori', 'Total', 'Keterangan']);
        $where = getDateWhere($filter, 'o.created_at');
        $res = $conn->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE $where ORDER BY o.created_at DESC");
        $grandTotal = 0;
        while ($r = $res->fetch_assoc()) {
            // Get items
            $items = $conn->query("SELECT produk, harga FROM order_items WHERE order_id={$r['id']}");
            $prodList = [];
            while ($it = $items->fetch_assoc()) $prodList[] = $it['produk'].'(Rp'.number_format($it['harga'],0,',','.').')';
            fputcsv($out, [
                $r['id'],
                date('d/m/Y', strtotime($r['created_at'])),
                date('H:i:s', strtotime($r['created_at'])),
                $r['username'],
                implode(' | ', $prodList),
                $r['kategori'],
                $r['total'],
                $r['keterangan'],
            ]);
            $grandTotal += $r['total'];
        }
        fputcsv($out, ['', '', '', '', '', 'TOTAL', $grandTotal, '']);
        break;

    // ── STOK ────────────────────────────────────────────────
    case 'stok':
        fputcsv($out, ['Tanggal', 'Tipe', 'Produk', 'Jumlah', 'Satuan', 'Dicatat Oleh']);
        $where = getDateWhere($filter, 'sr.tanggal');
        $res = $conn->query("SELECT sr.*, u.username FROM stock_records sr LEFT JOIN users u ON sr.user_id=u.id WHERE $where ORDER BY sr.tanggal DESC, sr.tipe, sr.produk");
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$r['tanggal'], $r['tipe'], $r['produk'], $r['jumlah'], $r['satuan'], $r['username']]);
        }
        break;

    // ── PRODUKSI ────────────────────────────────────────────
    case 'produksi':
        fputcsv($out, ['Tanggal', 'Item/Bahan', 'Harga', 'Supplier', 'Tempat', 'Keterangan', 'Dicatat Oleh']);
        $where = getDateWhere($filter, 'p.tanggal');
        if ($tempat) $where .= " AND p.tempat LIKE '%".addslashes($tempat)."%'";
        $res = $conn->query("SELECT p.*, u.username FROM production p LEFT JOIN users u ON p.user_id=u.id WHERE $where ORDER BY p.tanggal DESC");
        $grandTotal = 0;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$r['tanggal'], $r['nama_item'], $r['harga'], $r['supplier'], $r['tempat'], $r['keterangan'], $r['username']]);
            $grandTotal += $r['harga'];
        }
        fputcsv($out, ['', 'TOTAL', $grandTotal, '', '', '', '']);
        break;

    // ── OPERASIONAL ─────────────────────────────────────────
    case 'operasional':
        fputcsv($out, ['Nama Alat', 'Merk', 'Harga', 'Tempat Beli', 'Tanggal Beli', 'Periode Ganti (Bulan)', 'Keterangan', 'Dicatat Oleh']);
        $where = $alat ? "WHERE o.nama_alat LIKE '%".addslashes($alat)."%'" : '';
        $res = $conn->query("SELECT o.*, u.username FROM operational o LEFT JOIN users u ON o.user_id=u.id $where ORDER BY o.nama_alat");
        $grandTotal = 0;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$r['nama_alat'], $r['merk'], $r['harga'], $r['tempat_beli'], $r['tanggal_beli'], $r['periode_ganti'], $r['keterangan'], $r['username']]);
            $grandTotal += $r['harga'];
        }
        fputcsv($out, ['TOTAL NILAI', '', $grandTotal, '', '', '', '', '']);
        break;

    default:
        fputcsv($out, ['Error: Tipe export tidak valid']);
}

fclose($out);
exit;
