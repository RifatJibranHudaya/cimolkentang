<?php
// modules/produk/produk.php – Halaman Manajemen Produk
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin']);
global $conn;
$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/produk_handler.php'; exit;
}

$editData = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editData = $conn->query("SELECT * FROM products WHERE id=$eid")->fetch_assoc();
}

$showInactive = isset($_GET['show_inactive']);
$where = $showInactive ? '' : 'WHERE is_active=1';
$products = $conn->query("SELECT * FROM products $where ORDER BY urutan, nama")->fetch_all(MYSQLI_ASSOC);
$allCount    = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$activeCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE is_active=1")->fetch_assoc()['c'];

$emojis = ['🍢','🫙','🥔','🐟','🟡','🌭','🍡','🍖','🍗','🥩','🍜','🍛','🥗','🧆','🫔','🍱','🥟','🍘','🥮','🧁'];

renderHeader('Manajemen Produk', 'produk');
echo flashGet();
?>
<link rel="stylesheet" href="modules/produk/produk.css">

<!-- Stats & Actions -->
<div class="d-flex align-center justify-between flex-wrap gap-2 mb-3">
  <div class="d-flex gap-2 flex-wrap">
    <span class="badge badge-success">✅ <?= $activeCount ?> Aktif</span>
    <span class="badge" style="background:var(--card2);color:var(--text2);border:1px solid var(--border)">
      📦 <?= $allCount ?> Total Produk
    </span>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="?page=produk<?= $showInactive?'':'&show_inactive=1' ?>" class="btn btn-xs btn-secondary">
      <?= $showInactive ? '✅ Tampilkan Aktif Saja' : '👁️ Tampilkan Semua' ?>
    </a>
    <button class="btn btn-primary btn-sm" onclick="toggleForm('formProduk')">➕ Tambah Produk</button>
  </div>
</div>

<!-- Form Tambah Produk -->
<div id="formProduk" class="card mb-3" style="display:none">
  <div class="card-title">➕ Tambah Produk Baru</div>
  <form method="POST" action="index.php?page=produk">
    <input type="hidden" name="action" value="save">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nama Produk</label>
        <input type="text" name="nama" class="form-control" placeholder="cth: Cimol, Bakso Urat..." required>
      </div>
      <div class="form-group">
        <label class="form-label">Emoji</label>
        <input type="text" name="emoji" id="emojiInput" class="form-control" value="🍡" placeholder="🍡" style="font-size:1.4rem;max-width:80px">
        <div class="emoji-picker mt-1">
          <?php foreach ($emojis as $em): ?>
            <span class="emoji-opt" onclick="selectEmoji('<?= $em ?>')"><?= $em ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">💰 Harga Default (Rp) <span class="text-muted" style="font-weight:400">(Opsional)</span></label>
        <input type="number" name="harga_default" class="form-control" placeholder="0 = manual saat kasir" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">Urutan Tampil</label>
        <input type="number" name="urutan" class="form-control" placeholder="0" min="0" value="<?= count($products) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">📝 Deskripsi (Opsional)</label>
      <input type="text" name="deskripsi" class="form-control" placeholder="Deskripsi singkat produk...">
    </div>
    <button type="submit" class="btn btn-primary">💾 Simpan Produk</button>
  </form>
</div>

<!-- Form Edit Produk -->
<?php if ($editData): ?>
<div class="card mb-3" style="border-color:rgba(234,179,8,.3)">
  <div class="card-title">✏️ Edit Produk: <?= htmlspecialchars($editData['nama']) ?></div>
  <form method="POST" action="index.php?page=produk">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nama Produk</label>
        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($editData['nama']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Emoji</label>
        <input type="text" name="emoji" id="emojiInputEdit" class="form-control" value="<?= htmlspecialchars($editData['emoji']) ?>" style="font-size:1.4rem;max-width:80px">
        <div class="emoji-picker mt-1">
          <?php foreach ($emojis as $em): ?>
            <span class="emoji-opt <?= $editData['emoji']===$em?'selected':'' ?>" onclick="selectEmojiEdit('<?= $em ?>')"><?= $em ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">💰 Harga Default (Rp)</label>
        <input type="number" name="harga_default" class="form-control" value="<?= $editData['harga_default'] ?>" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">Urutan Tampil</label>
        <input type="number" name="urutan" class="form-control" value="<?= $editData['urutan'] ?>" min="0">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">📝 Deskripsi</label>
      <input type="text" name="deskripsi" class="form-control" value="<?= htmlspecialchars($editData['deskripsi'] ?? '') ?>">
    </div>
    <div class="form-group d-flex align-center gap-2">
      <input type="checkbox" name="is_active" id="is_active" <?= $editData['is_active'] ? 'checked' : '' ?> style="accent-color:var(--primary);width:16px;height:16px">
      <label for="is_active" class="form-label" style="margin:0">Produk Aktif (tampil di kasir)</label>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-warning">💾 Simpan Perubahan</button>
      <a href="index.php?page=produk" class="btn btn-secondary">✕ Batal</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Grid Produk -->
<div class="card">
  <div class="card-title">📦 Daftar Produk</div>
  <?php if (empty($products)): ?>
    <div class="empty-state"><div class="icon">📦</div><p>Belum ada produk</p></div>
  <?php else: ?>
  <div class="produk-grid">
    <?php foreach ($products as $p): ?>
    <div class="produk-card <?= !$p['is_active']?'inactive':'' ?>">
      <span class="produk-status-badge <?= $p['is_active']?'status-active':'status-inactive' ?>">
        <?= $p['is_active']?'Aktif':'Nonaktif' ?>
      </span>
      <span class="produk-card-emoji"><?= $p['emoji'] ?></span>
      <div class="produk-card-name"><?= htmlspecialchars($p['nama']) ?></div>
      <div class="produk-card-price">
        <?= $p['harga_default']>0 ? rupiah($p['harga_default']) : 'Harga manual' ?>
      </div>
      <div class="produk-card-desc"><?= htmlspecialchars($p['deskripsi'] ?: '–') ?></div>
      <div class="produk-card-actions">
        <a href="?page=produk&edit=<?= $p['id'] ?>" class="btn btn-xs btn-warning">✏️</a>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="btn btn-xs btn-secondary" title="<?= $p['is_active']?'Nonaktifkan':'Aktifkan' ?>">
            <?= $p['is_active']?'⏸️':'▶️' ?>
          </button>
        </form>
        <?php if (isOwner()): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus produk ini?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="btn btn-xs btn-danger">🗑️</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function selectEmoji(em) {
  document.getElementById('emojiInput').value = em;
  document.querySelectorAll('#formProduk .emoji-opt').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}
function selectEmojiEdit(em) {
  document.getElementById('emojiInputEdit').value = em;
  document.querySelectorAll('.card .emoji-opt').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}
function toggleForm(id) {
  const el = document.getElementById(id);
  const isHidden = el.style.display === 'none' || el.style.display === '';
  el.style.display = isHidden ? 'block' : 'none';
  if (isHidden) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
<?php renderFooter(); ?>
