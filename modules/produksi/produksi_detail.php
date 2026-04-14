<?php
// modules/produksi/produksi_detail.php – Detail Item Produksi
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php?page=produksi');
    exit;
}

$item = $conn->query("
    SELECT p.*, u.username as pembuat, eu.username as editor
    FROM production p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN users eu ON p.edited_by = eu.id
    WHERE p.id = $id
")->fetch_assoc();

if (!$item) {
    flashSet('error', 'Data tidak ditemukan.');
    header('Location: index.php?page=produksi');
    exit;
}

renderHeader('Detail Produksi', 'produksi');
?>
<link rel="stylesheet" href="modules/produksi/produksi.css">

<div class="d-flex gap-2 mb-3">
  <a href="index.php?page=produksi" class="btn btn-secondary">← Kembali</a>
  <a href="index.php?page=produksi&edit=<?= $id ?>" class="btn btn-warning">✏️ Edit</a>
</div>

<div class="card">
  <div class="card-title">🔍 Detail Catatan Belanja #<?= $id ?></div>

  <div class="produksi-detail-card">
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">📝 Nama Item</span>
      <span class="produksi-detail-val" style="font-size:1.05rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($item['nama_item']) ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">💰 Harga</span>
      <span class="produksi-detail-val" style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--primary)"><?= rupiah($item['harga']) ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">🏭 Supplier</span>
      <span class="produksi-detail-val"><?= htmlspecialchars($item['supplier'] ?: '–') ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">📍 Tempat Beli</span>
      <span class="produksi-detail-val"><?= htmlspecialchars($item['tempat'] ?: '–') ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">📅 Tanggal Beli</span>
      <span class="produksi-detail-val"><?= $item['tanggal'] ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">📝 Keterangan</span>
      <span class="produksi-detail-val"><?= htmlspecialchars($item['keterangan'] ?: '–') ?></span>
    </div>
  </div>

  <div class="produksi-detail-card" style="background:rgba(22,163,74,.05);border-color:rgba(22,163,74,.15)">
    <div class="card-title" style="font-size:.95rem;margin-bottom:10px">📌 Informasi Pencatat</div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">👤 Dicatat Oleh</span>
      <span class="produksi-detail-val"><?= htmlspecialchars($item['pembuat'] ?? '–') ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">🕐 Waktu Catat</span>
      <span class="produksi-detail-val"><?= date('d F Y, H:i', strtotime($item['created_at'])) ?></span>
    </div>
  </div>

  <?php if ($item['editor']): ?>
  <div class="produksi-detail-card" style="background:rgba(234,179,8,.05);border-color:rgba(234,179,8,.2)">
    <div class="card-title" style="font-size:.95rem;margin-bottom:10px">✏️ Riwayat Edit</div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">✏️ Diedit Oleh</span>
      <span class="produksi-detail-val" style="color:#FDE68A;font-weight:700"><?= htmlspecialchars($item['editor']) ?></span>
    </div>
    <div class="produksi-detail-row">
      <span class="produksi-detail-label">🕐 Waktu Edit</span>
      <span class="produksi-detail-val"><?= date('d F Y, H:i', strtotime($item['edited_at'])) ?></span>
    </div>
  </div>
  <?php else: ?>
  <div class="text-sm text-muted" style="padding:8px">✅ Data belum pernah diedit.</div>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>
